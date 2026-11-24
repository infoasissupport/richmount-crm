<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_authenticated_user_can_view_dashboard(): void
    {
        $superAdmin = User::where('role_name', 'super_admin')->first();
        $this->actingAs($superAdmin);

        $response = $this->get('/');
        $response->assertOk();
        $response->assertViewHas(['kpis', 'pipeline', 'recentLeads', 'branches']);
        $response->assertSee('Lead Pipeline');
        $response->assertSee('Recent Leads');
    }

    public function test_engineer_dashboard_shows_role_tailored_kpis(): void
    {
        $engineer = User::where('role_name', 'engineer')->first();
        $this->actingAs($engineer);

        $response = $this->get('/');
        $response->assertOk();

        /** @var array $kpis */
        $kpis = $response->viewData('kpis');
        $keys = array_column($kpis, 'key');

        $this->assertContains('my_leads', $keys);
        $this->assertContains('cash_in_hand', $keys);
        $this->assertContains('my_projects', $keys);

        // Verify engineer cash balance format
        $cashKpi = collect($kpis)->firstWhere('key', 'cash_in_hand');
        $this->assertStringContainsString('AED', $cashKpi['value']);
    }

    public function test_accountant_dashboard_shows_financial_approval_kpis(): void
    {
        $accountant = User::where('role_name', 'accountant')->first();
        $this->actingAs($accountant);

        $response = $this->get('/');
        $response->assertOk();

        /** @var array $kpis */
        $kpis = $response->viewData('kpis');
        $keys = array_column($kpis, 'key');

        $this->assertContains('awarded', $keys);
        $this->assertContains('pending_approvals', $keys);
        $this->assertContains('invoiced', $keys);
    }

    public function test_accountant_does_not_see_leads_and_only_sees_approved_and_awarded_quotations(): void
    {
        $accountant = User::where('role_name', 'accountant')->first();
        $this->actingAs($accountant);

        $response = $this->get('/');
        $response->assertOk();

        // Dashboard shows approved and awarded quotations for accountant
        $response->assertSee('Approved & Awarded Quotations', false);
        $response->assertDontSee('Recent Leads');

        $recentQuotes = $response->viewData('recentQuotations');
        $this->assertNotNull($recentQuotes);

        foreach ($recentQuotes as $quote) {
            $this->assertContains($quote->status, ['approved', 'awarded']);
        }

        // Accountant cannot view /leads or any lead details page
        $this->get(route('leads.index'))->assertForbidden();

        $anyLead = Lead::first();
        if ($anyLead) {
            $this->get(route('leads.show', $anyLead))->assertForbidden();
        }

        // Accountant on quotations page only sees approved and awarded quotes
        $quotesResponse = $this->get(route('quotations.index'));
        $quotesResponse->assertOk();
        $accessibleQuotes = \App\Models\Quotation::accessibleBy($accountant)->get();
        foreach ($accessibleQuotes as $q) {
            $this->assertContains($q->status, ['approved', 'awarded']);
        }
    }

    public function test_dashboard_recent_leads_filters_by_search_and_status(): void
    {
        $superAdmin = User::where('role_name', 'super_admin')->first();
        $this->actingAs($superAdmin);

        $lead = Lead::first();
        $this->assertNotNull($lead);

        // Test search query
        $resSearch = $this->get("/?search={$lead->customer_name}");
        $resSearch->assertOk();
        $resSearch->assertSee($lead->customer_name);

        // Test status query
        $resStatus = $this->get('/?status=won');
        $resStatus->assertOk();
    }

    public function test_dashboard_pipeline_structure(): void
    {
        $superAdmin = User::where('role_name', 'super_admin')->first();
        $this->actingAs($superAdmin);

        $response = $this->get('/');
        $response->assertOk();

        /** @var array $pipeline */
        $pipeline = $response->viewData('pipeline');

        $this->assertArrayHasKey('new', $pipeline);
        $this->assertArrayHasKey('draft', $pipeline);
        $this->assertArrayHasKey('sent', $pipeline);
        $this->assertArrayHasKey('awarded', $pipeline);

        $this->assertEquals('New Leads', $pipeline['new']['name']);
        $this->assertEquals('Draft Quotation', $pipeline['draft']['name']);
        $this->assertEquals('Sent to Client', $pipeline['sent']['name']);
        $this->assertEquals('Awarded', $pipeline['awarded']['name']);
    }

    public function test_api_v1_dashboard_json_endpoint(): void
    {
        $engineer = User::where('role_name', 'engineer')->first();
        $token = $engineer->createToken('dashboard-test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/dashboard');

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'data' => [
                'kpis' => [
                    '*' => ['key', 'label', 'value', 'icon', 'note', 'href']
                ],
                'pipeline' => [
                    'new' => ['id', 'name', 'count', 'items', 'foot_label'],
                    'draft' => ['id', 'name', 'count', 'items', 'foot_label'],
                    'sent' => ['id', 'name', 'count', 'items', 'foot_label'],
                    'awarded' => ['id', 'name', 'count', 'items', 'foot_label'],
                ],
                'recent_leads' => [
                    'data' => [
                        '*' => ['id', 'lead_number', 'customer_name', 'status']
                    ]
                ]
            ]
        ]);
    }
}
