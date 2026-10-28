<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_estimator_and_admin_can_view_leads_index(): void
    {
        $estimator = User::where('role_name', 'estimation')->first();
        $this->actingAs($estimator);

        $response = $this->get('/leads');
        $response->assertStatus(200);
        $response->assertSee('Leads');
        $response->assertSee('Al Futtaim Group');
    }

    public function test_leads_index_renders_action_buttons_and_modals(): void
    {
        $superAdmin = User::where('role_name', 'super_admin')->first();
        $this->actingAs($superAdmin);

        $response = $this->get('/leads');
        $response->assertStatus(200);
        $response->assertSee('openNewLeadModal()', false);
        $response->assertSee('openAssignBoardModal()', false);
        $response->assertSee('id="leadModal"', false);
        $response->assertSee('id="assignBoardModal"', false);
    }

    public function test_engineer_only_sees_assigned_active_leads(): void
    {
        $aliKhan = User::where('email', 'ali.khan@richmount.ae')->first();
        $this->actingAs($aliKhan);

        $response = $this->get('/leads');
        $response->assertStatus(200);

        // Ali Khan is assigned to Lead 1, 2, and 5 in the seeder
        $response->assertSee('Al Futtaim Group');

        // Lead 3 is assigned to Ramesh Nair in Abu Dhabi, Ali Khan must NOT see it
        $response->assertDontSee('Aldar Properties PJSC');

        // Lead 4 is in Sharjah assigned to Tariq, Ali Khan must NOT see it
        $response->assertDontSee('Arada Developments');
    }

    public function test_lead_disappears_from_engineer_view_when_quote_sent_or_closed(): void
    {
        $aliKhan = User::where('email', 'ali.khan@richmount.ae')->first();

        // Find a lead assigned to Ali Khan
        $lead = Lead::where('assigned_engineer_id', $aliKhan->id)->where('stage', 'draft')->first();
        $this->assertNotNull($lead);

        // Verify Ali can see it initially
        $this->actingAs($aliKhan);
        $response = $this->get('/leads/' . $lead->id);
        $response->assertStatus(200);

        // Mark lead stage as 'sent' and status as 'Closed'
        $lead->update([
            'stage' => 'sent',
            'status' => 'Closed',
        ]);

        // Engineer should now get 403 Forbidden or not find it in scope
        $response2 = $this->get('/leads/' . $lead->id);
        $response2->assertStatus(403);

        // Should not be in the leads list
        $response3 = $this->get('/leads');
        $response3->assertDontSee($lead->lead_number);
    }

    public function test_can_create_new_lead_with_auto_generated_number(): void
    {
        $superAdmin = User::where('role_name', 'super_admin')->first();
        $this->actingAs($superAdmin);

        $branch = Branch::first();

        $response = $this->post('/leads', [
            'company_name' => 'Nakheel PJSC',
            'customer_name' => 'Fahad Al Qubaisi',
            'rfq_number' => 'RFQ-NKH-2026',
            'subject' => 'Palm Jumeirah Boardwalk Structural Maintenance',
            'department' => 'construction',
            'branch_id' => $branch->id,
            'source' => 'Website',
            'estimated_value' => 350000.00,
            'contact_person' => 'Fahad Al Qubaisi',
            'phone' => '+971 50 777 8899',
            'email' => 'fahad.q@nakheel.ae',
            'notes' => 'Urgent marine grade coating required.',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('leads', [
            'company_name' => 'Nakheel PJSC',
            'subject' => 'Palm Jumeirah Boardwalk Structural Maintenance',
            'department' => 'construction',
        ]);

        $newLead = Lead::where('company_name', 'Nakheel PJSC')->first();
        $this->assertMatchesRegularExpression('/^(RM-)?LD-\d{4}-\d+$/', $newLead->lead_number);
        $this->assertCount(1, $newLead->activities);
    }

    public function test_can_update_lead(): void
    {
        $superAdmin = User::where('role_name', 'super_admin')->first();
        $this->actingAs($superAdmin);

        $lead = Lead::first();

        $response = $this->put('/leads/' . $lead->id, [
            'company_name' => $lead->company_name,
            'customer_name' => $lead->customer_name,
            'contact_person' => $lead->contact_person,
            'phone' => $lead->phone,
            'subject' => 'Updated Project Subject Line',
            'department' => 'interior',
            'branch_id' => $lead->branch_id,
            'source' => $lead->source,
            'estimated_value' => 400000.00,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('leads', [
            'id' => $lead->id,
            'subject' => 'Updated Project Subject Line',
            'estimated_value' => 400000.00,
        ]);
    }

    public function test_can_assign_engineer_to_lead(): void
    {
        $superAdmin = User::where('role_name', 'super_admin')->first();
        $this->actingAs($superAdmin);

        $lead = Lead::whereNull('assigned_engineer_id')->first() ?? Lead::first();
        $engineer = User::where('role_name', 'engineer')->first();

        $response = $this->post("/leads/{$lead->id}/assign", [
            'engineer_id' => $engineer->id,
        ]);

        $response->assertRedirect();
        $this->assertEquals($engineer->id, $lead->fresh()->assigned_engineer_id);
    }

    public function test_bulk_assign_engineers(): void
    {
        $superAdmin = User::where('role_name', 'super_admin')->first();
        $this->actingAs($superAdmin);

        $leads = Lead::take(2)->get();
        $engineer = User::where('role_name', 'engineer')->first();

        $response = $this->post('/leads/bulk-assign', [
            'assignments' => [
                ['lead_id' => $leads[0]->id, 'engineer_id' => $engineer->id],
                ['lead_id' => $leads[1]->id, 'engineer_id' => $engineer->id],
            ],
        ]);

        $response->assertRedirect();
        $this->assertEquals($engineer->id, $leads[0]->fresh()->assigned_engineer_id);
        $this->assertEquals($engineer->id, $leads[1]->fresh()->assigned_engineer_id);
    }

    public function test_mobile_api_leads_list_and_detail(): void
    {
        $estimator = User::where('role_name', 'estimation')->first();
        $token = $estimator->createToken('flutter-test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/leads');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'data' => [
                    '*' => ['id', 'lead_number', 'company_name', 'subject', 'status', 'stage'],
                ],
            ],
        ]);
    }
}
