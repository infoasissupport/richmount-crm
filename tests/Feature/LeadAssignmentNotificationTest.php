<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Lead;
use App\Models\User;
use App\Notifications\LeadAssignedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class LeadAssignmentNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_assigning_engineer_on_lead_creation_sends_notification_to_engineer(): void
    {
        $estimator = User::where('role_name', 'estimation')->first();
        $engineer = User::where('role_name', 'engineer')->first();
        $branch = Branch::first();

        $this->actingAs($estimator);

        $response = $this->post('/leads', [
            'customer_name' => 'John Client',
            'company_name' => 'Emaar Properties',
            'phone' => '+971 50 123 4567',
            'email' => 'john@emaar.ae',
            'department' => 'interior',
            'branch_id' => $branch->id,
            'source' => 'Website',
            'assigned_engineer_id' => $engineer->id,
            'subject' => 'Villa Interior Renovation',
        ]);

        $response->assertRedirect();

        $lead = Lead::where('company_name', 'Emaar Properties')->first();
        $this->assertNotNull($lead);
        $this->assertEquals($engineer->id, $lead->assigned_engineer_id);

        // Check database notification on engineer
        $this->assertEquals(1, $engineer->unreadNotifications()->count());
        $notification = $engineer->unreadNotifications()->first();
        $this->assertEquals('lead', $notification->data['type']);
        $this->assertEquals($lead->id, $notification->data['lead_id']);
        $this->assertStringContainsString($lead->lead_number, $notification->data['title']);

        // Engineer logs in and views notifications
        $this->actingAs($engineer);
        $notifResponse = $this->getJson('/notifications');
        $notifResponse->assertOk();
        $notifResponse->assertJsonFragment(['unread_count' => 1]);

        // Engineer marks notification as read
        $readResponse = $this->post("/notifications/{$notification->id}/read");
        $readResponse->assertRedirect(route('leads.show', $lead));
        $this->assertEquals(0, $engineer->fresh()->unreadNotifications()->count());
    }

    public function test_editing_lead_to_assign_engineer_sends_notification(): void
    {
        $estimator = User::where('role_name', 'estimation')->first();
        $engineer = User::where('role_name', 'engineer')->first();
        $branch = Branch::first();

        // Create an unassigned lead
        $lead = Lead::create([
            'lead_number' => Lead::nextLeadNumber(),
            'lead_date' => now()->toDateString(),
            'customer_name' => 'Manohar Client',
            'company_name' => 'Manohar Corp',
            'phone' => '9645143506',
            'email' => 'manoharan@gmail.com',
            'department' => 'interior',
            'branch_id' => $branch->id,
            'created_by_id' => $estimator->id,
            'source' => 'Website',
            'status' => 'New',
            'stage' => 'new',
            'subject' => 'Renovation Project',
            'assigned_engineer_id' => null,
        ]);

        $this->actingAs($estimator);

        // Update lead to assign engineer (as in the modal screenshot)
        $response = $this->put("/leads/{$lead->id}", [
            'customer_name' => 'Manohar Client',
            'company_name' => 'Manohar Corp',
            'phone' => '9645143506',
            'email' => 'manoharan@gmail.com',
            'department' => 'interior',
            'branch_id' => $branch->id,
            'source' => 'Website',
            'assigned_engineer_id' => $engineer->id,
            'subject' => 'Renovation Project',
        ]);

        $response->assertRedirect();
        $lead->refresh();
        $this->assertEquals($engineer->id, $lead->assigned_engineer_id);

        // Engineer should have received notification
        $this->assertEquals(1, $engineer->unreadNotifications()->count());
        $notification = $engineer->unreadNotifications()->first();
        $this->assertEquals($lead->id, $notification->data['lead_id']);
        $this->assertStringContainsString('Renovation Project', $notification->data['text']);
    }

    public function test_assign_endpoint_sends_notification(): void
    {
        $estimator = User::where('role_name', 'estimation')->first();
        $engineer = User::where('role_name', 'engineer')->first();
        $branch = Branch::first();

        $lead = Lead::create([
            'lead_number' => Lead::nextLeadNumber(),
            'lead_date' => now()->toDateString(),
            'customer_name' => 'Test Client',
            'company_name' => 'Alpha LLC',
            'phone' => '0501234567',
            'email' => 'alpha@test.ae',
            'department' => 'interior',
            'branch_id' => $branch->id,
            'created_by_id' => $estimator->id,
            'source' => 'Website',
            'subject' => 'Fitout Scope',
            'status' => 'New',
            'stage' => 'new',
            'assigned_engineer_id' => null,
        ]);

        $this->actingAs($estimator);

        $response = $this->post("/leads/{$lead->id}/assign", [
            'engineer_id' => $engineer->id,
        ]);

        $response->assertRedirect();
        $this->assertEquals(1, $engineer->unreadNotifications()->count());
    }

    public function test_engineer_submitting_quotation_notifies_estimation_user_and_super_admin(): void
    {
        $estimator = User::where('role_name', 'estimation')->first();
        $engineer = User::where('role_name', 'engineer')->first();
        $superAdmin = User::where('role_name', 'super_admin')->first();
        $branch = Branch::first();

        // Lead created by estimator and assigned to engineer
        $lead = Lead::create([
            'lead_number' => Lead::nextLeadNumber(),
            'lead_date' => now()->toDateString(),
            'customer_name' => 'Villa Owner',
            'company_name' => 'Private Residence',
            'phone' => '0559876543',
            'email' => 'owner@residence.ae',
            'department' => 'interior',
            'branch_id' => $branch->id,
            'created_by_id' => $estimator->id,
            'assigned_engineer_id' => $engineer->id,
            'source' => 'Direct',
            'subject' => 'Luxury Home Cinema',
            'status' => 'Contacted',
            'stage' => 'draft',
        ]);

        // Engineer creates a draft quotation for that lead
        $quotation = \App\Models\Quotation::create([
            'quote_number' => \App\Models\Quotation::nextQuoteNumber(),
            'lead_id' => $lead->id,
            'branch_id' => $branch->id,
            'prepared_by_id' => $engineer->id,
            'status' => 'draft',
            'quote_date' => now()->toDateString(),
            'validity_days' => 30,
            'subtotal' => 12000.00,
            'vat_amount' => 600.00,
            'grand_total' => 12600.00,
        ]);

        // Clear existing notifications
        $estimator->notifications()->delete();
        $superAdmin->notifications()->delete();

        // Engineer submits quotation for approval
        $this->actingAs($engineer);
        $response = $this->post("/quotations/{$quotation->id}/submit");
        $response->assertRedirect();

        $quotation->refresh();
        $this->assertEquals('pending', $quotation->status);

        // 1. Estimation User should have received notification
        $this->assertEquals(1, $estimator->unreadNotifications()->count());
        $estimatorNotif = $estimator->unreadNotifications()->first();
        $this->assertEquals('quote', $estimatorNotif->data['type']);
        $this->assertEquals($quotation->id, $estimatorNotif->data['quotation_id']);
        $this->assertStringContainsString($quotation->quote_number, $estimatorNotif->data['title']);
        $this->assertStringContainsString($engineer->name, $estimatorNotif->data['text']);
        $this->assertEquals(route('quotations.builder', ['id' => $quotation->id]), $estimatorNotif->data['link']);

        // 2. Super Admin should have received notification
        $this->assertEquals(1, $superAdmin->unreadNotifications()->count());
        $adminNotif = $superAdmin->unreadNotifications()->first();
        $this->assertEquals('quote', $adminNotif->data['type']);
        $this->assertEquals($quotation->id, $adminNotif->data['quotation_id']);
        $this->assertStringContainsString($quotation->quote_number, $adminNotif->data['title']);

        // 3. Estimator marks notification as read (e.g. clicking it in topbar)
        $this->actingAs($estimator);
        $clickResponse = $this->post("/notifications/{$estimatorNotif->id}/read");
        $clickResponse->assertRedirect(route('quotations.builder', ['id' => $quotation->id]));
        $this->assertEquals(0, $estimator->fresh()->unreadNotifications()->count());
    }
}
