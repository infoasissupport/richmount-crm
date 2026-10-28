<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Lead;
use App\Models\Project;
use App\Models\PurchaseOrder;
use App\Models\Quotation;
use App\Models\User;
use App\Models\WorkCompletionCertificate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_user_can_view_projects_index_and_kpis(): void
    {
        $superAdmin = User::where('role_name', 'super_admin')->first();
        $this->actingAs($superAdmin);

        $response = $this->get('/projects');
        $response->assertStatus(200);
        $response->assertSee('Projects');
        $response->assertSee('Awaiting Project / PO');
        $response->assertSee('Active Projects');
        $response->assertSee('PRJ-2026-0001');
    }

    public function test_projects_status_tabs_filtering(): void
    {
        $superAdmin = User::where('role_name', 'super_admin')->first();
        $this->actingAs($superAdmin);

        // Filter by 'inprogress'
        $responseInProgress = $this->get('/projects?tab=inprogress');
        $responseInProgress->assertStatus(200);
        $responseInProgress->assertSee('PRJ-2026-0001'); // Sharjah is inprogress

        // Filter by 'popending'
        $responsePoPending = $this->get('/projects?tab=popending');
        $responsePoPending->assertStatus(200);
        $responsePoPending->assertSee('PRJ-2026-0002'); // Abu Dhabi is popending
    }

    public function test_can_create_project_manually(): void
    {
        $accountant = User::where('role_name', 'accountant')->first();
        $this->actingAs($accountant);

        $branch = Branch::first();

        $response = $this->post('/projects', [
            'name' => 'Dubai Marina Tower Terrace Waterproofing',
            'client_name' => 'Select Group Real Estate',
            'branch_id' => $branch->id,
            'contract_value' => 310000.00,
            'start_date' => '2026-11-01',
            'expected_end_date' => '2026-12-30',
            'client_po_number' => 'SG-PO-4091',
            'client_po_date' => '2026-10-25',
            'client_po_amount' => 310000.00,
            'description' => 'Polyurethane deck waterproofing coating system.',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('projects', [
            'name' => 'Dubai Marina Tower Terrace Waterproofing',
            'client_name' => 'Select Group Real Estate',
            'client_po_number' => 'SG-PO-4091',
            'status' => 'open', // opened immediately since PO was provided
        ]);

        $created = Project::where('client_name', 'Select Group Real Estate')->first();
        $this->assertStringStartsWith('PRJ-', $created->project_number);
    }

    public function test_can_create_project_from_awarded_quotation(): void
    {
        $accountant = User::where('role_name', 'accountant')->first();
        $this->actingAs($accountant);

        // Find awarded quotation without project
        $quote = Quotation::where('status', 'awarded')->first();
        $this->assertNotNull($quote);

        $response = $this->post(route('projects.create-from-quote', $quote));
        $response->assertRedirect();

        $this->assertDatabaseHas('projects', [
            'quotation_id' => $quote->id,
            'client_name' => $quote->lead->company_name,
        ]);
    }

    public function test_record_client_po_transitions_project_to_open(): void
    {
        $accountant = User::where('role_name', 'accountant')->first();
        $this->actingAs($accountant);

        $project = Project::where('status', 'popending')->first();
        $this->assertNotNull($project);

        $response = $this->post("/projects/{$project->id}/po", [
            'client_po_number' => 'PO-ALDAR-9902',
            'client_po_date' => '2026-10-25',
            'client_po_amount' => $project->contract_value,
        ]);

        $response->assertRedirect();
        $fresh = $project->fresh();
        $this->assertEquals('open', $fresh->status);
        $this->assertEquals('PO-ALDAR-9902', $fresh->client_po_number);
    }

    public function test_start_work_and_put_on_hold_workflow(): void
    {
        $superAdmin = User::where('role_name', 'super_admin')->first();
        $this->actingAs($superAdmin);

        $project = Project::where('status', 'popending')->first();
        $project->update(['status' => 'open', 'client_po_number' => 'PO-TEST-1']);

        // 1. Start Work
        $startResponse = $this->post("/projects/{$project->id}/start-work");
        $startResponse->assertRedirect();
        $this->assertEquals('inprogress', $project->fresh()->status);

        // 2. Put On Hold
        $holdResponse = $this->post("/projects/{$project->id}/put-on-hold", [
            'reason' => 'Site access delayed by main contractor.',
        ]);
        $holdResponse->assertRedirect();
        $this->assertEquals('onhold', $project->fresh()->status);
        $this->assertEquals('Site access delayed by main contractor.', $project->fresh()->hold_reason);

        // 3. Resume Work
        $resumeResponse = $this->post("/projects/{$project->id}/resume-work");
        $resumeResponse->assertRedirect();
        $this->assertEquals('inprogress', $project->fresh()->status);
    }

    public function test_complete_work_issues_wcc_certificate(): void
    {
        $superAdmin = User::where('role_name', 'super_admin')->first();
        $this->actingAs($superAdmin);

        $project = Project::where('status', 'inprogress')->first();

        $response = $this->post("/projects/{$project->id}/complete-work", [
            'completion_date' => '2026-10-26',
            'work_description' => 'Complete waterproofing works executed according to consultant specifications.',
            'retention_pct' => 10.0,
            'retention_amount' => 19950.00,
            'client_representative_name' => 'Eng. Omar Al Qasimi',
            'client_representative_title' => 'Project Director',
        ]);

        $response->assertRedirect();
        $fresh = $project->fresh();
        $this->assertEquals('completed', $fresh->status);
        $this->assertEquals(100, $fresh->progress_pct);
        $this->assertNotNull($fresh->latestWorkCompletionCertificate);
        $this->assertEquals('Eng. Omar Al Qasimi', $fresh->latestWorkCompletionCertificate->client_representative_name);
    }

    public function test_vendor_purchase_order_creation_and_pdf_download(): void
    {
        $superAdmin = User::where('role_name', 'super_admin')->first();
        $this->actingAs($superAdmin);

        $project = Project::first();

        // 1. Create vendor purchase order
        $response = $this->post("/projects/{$project->id}/purchase-orders", [
            'vendor_name' => 'Jotun Paints UAE LLC',
            'vendor_trn' => '100123456789001',
            'po_date' => '2026-10-24',
            'delivery_date' => '2026-10-28',
            'vat_rate' => 0.05,
            'items' => [
                [
                    'description' => 'Jotashield Extreme Exterior Paint (20L Drum)',
                    'quantity' => 40,
                    'unit' => 'drums',
                    'unit_price' => 320.00,
                ],
            ],
        ]);

        $response->assertRedirect();
        $po = PurchaseOrder::where('vendor_name', 'Jotun Paints UAE LLC')->first();
        $this->assertNotNull($po);
        $this->assertEquals(12800.00, (float) $po->subtotal);
        $this->assertEquals(640.00, (float) $po->vat_amount);
        $this->assertEquals(13440.00, (float) $po->grand_total);

        // 2. Download Vendor PO PDF
        $pdfResponse = $this->get("/purchase-orders/{$po->id}/pdf");
        $pdfResponse->assertStatus(200);
        $pdfResponse->assertHeader('content-type', 'application/pdf');
    }

    public function test_work_completion_certificate_pdf_download(): void
    {
        $superAdmin = User::where('role_name', 'super_admin')->first();
        $this->actingAs($superAdmin);

        $wcc = WorkCompletionCertificate::first();
        $this->assertNotNull($wcc);

        $response = $this->get("/work-completion-certificates/{$wcc->id}/pdf");
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_mobile_api_project_endpoints(): void
    {
        $superAdmin = User::where('role_name', 'super_admin')->first();
        $token = $superAdmin->createToken('flutter-test')->plainTextToken;

        // List
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/projects');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'data' => [
                    '*' => ['id', 'project_number', 'name', 'client_name', 'status'],
                ],
            ],
        ]);

        // Detail
        $project = Project::first();
        $detailResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/v1/projects/{$project->id}");

        $detailResponse->assertStatus(200);
        $detailResponse->assertJson(['success' => true]);
    }
}
