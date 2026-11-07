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
}
