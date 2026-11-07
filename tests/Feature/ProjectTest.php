<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Project;
use App\Models\PurchaseOrder;
use App\Models\Quotation;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkCompletionCertificate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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

    public function test_po_pending_projects_are_paginated_without_truncating_json_results(): void
    {
        $superAdmin = User::where('role_name', 'super_admin')->firstOrFail();
        $branch = Branch::firstOrFail();

        for ($index = 1; $index <= 16; $index++) {
            Project::create([
                'project_number' => sprintf('PRJ-PENDING-%02d', $index),
                'name' => 'Pending Project '.$index,
                'branch_id' => $branch->id,
                'client_name' => 'Pending Client '.$index,
                'contract_value' => 1000,
                'status' => 'popending',
                'created_at' => now()->subMinutes($index),
                'updated_at' => now()->subMinutes($index),
            ]);
        }

        $this->actingAs($superAdmin);

        $firstPage = $this->get(route('projects.index'));
        $firstPage->assertOk()
            ->assertSee('awaiting_po_page=2', false)
            ->assertSee('PRJ-PENDING-01')
            ->assertDontSee('PRJ-PENDING-16');

        $secondPage = $this->get(route('projects.index', ['awaiting_po_page' => 2]));
        $secondPage->assertOk()
            ->assertSee('PRJ-PENDING-16')
            ->assertDontSee('PRJ-PENDING-01');

        $this->getJson(route('projects.index'))
            ->assertOk()
            ->assertJsonCount(16, 'awaiting_po');
    }

    public function test_project_detail_transactions_paginate_independently_and_keep_full_totals(): void
    {
        $superAdmin = User::where('role_name', 'super_admin')->firstOrFail();
        $branch = Branch::firstOrFail();
        $project = Project::create([
            'project_number' => 'PRJ-DETAIL-PAGES',
            'name' => 'Paginated Detail Project',
            'branch_id' => $branch->id,
            'client_name' => 'Pagination Client',
            'contract_value' => 10000,
            'status' => 'open',
        ]);

        foreach (range(1, 11) as $index) {
            PurchaseOrder::create([
                'po_number' => sprintf('VPO-PAGE-%02d', $index),
                'project_id' => $project->id,
                'branch_id' => $branch->id,
                'vendor_name' => 'Pagination Vendor',
                'po_date' => now()->subDays($index),
                'subtotal' => 100,
                'vat_rate' => 0.05,
                'vat_amount' => 5,
                'grand_total' => 105,
                'status' => 'pending',
            ]);

            Expense::create([
                'expense_number' => sprintf('EXP-DETAIL-PAGE-%02d', $index),
                'project_id' => $project->id,
                'branch_id' => $branch->id,
                'user_id' => $superAdmin->id,
                'category' => 'materials',
                'vendor_name' => 'Pagination Vendor',
                'date' => now()->subDays($index),
                'type' => 'cash',
                'subtotal' => 50,
                'vat_rate' => 0.05,
                'vat_amount' => 2.5,
                'grand_total' => 52.5,
                'description' => 'Pagination expense',
                'status' => 'approved',
            ]);

            Invoice::create([
                'invoice_number' => sprintf('INV-DETAIL-PAGE-%02d', $index),
                'project_id' => $project->id,
                'branch_id' => $branch->id,
                'created_by_id' => $superAdmin->id,
                'client_name' => 'Pagination Client',
                'invoice_date' => now()->subDays($index),
                'subtotal' => 100,
                'vat_rate' => 0.05,
                'vat_amount' => 5,
                'grand_total' => 105,
                'balance_due' => 105,
                'status' => 'sent',
            ]);
        }

        $this->actingAs($superAdmin);

        $response = $this->get(route('projects.show', [
            'project' => $project,
            'vendor_pos_page' => 2,
            'project_expenses_page' => 2,
            'project_invoices_page' => 2,
        ]));

        $response->assertOk();
        $response->assertViewHas('projectTotals', fn (array $totals): bool =>
            $totals['total_invoiced'] === 1155.0
            && $totals['total_expenses'] === 577.5
            && $totals['sent_invoice_count'] === 11
        );
        $response->assertViewHas('vendorPurchaseOrders', fn ($rows) => $rows->total() === 11 && $rows->currentPage() === 2 && $rows->count() === 1);
        $response->assertViewHas('projectExpenses', fn ($rows) => $rows->total() === 11 && $rows->currentPage() === 2 && $rows->count() === 1);
        $response->assertViewHas('projectInvoices', fn ($rows) => $rows->total() === 11 && $rows->currentPage() === 2 && $rows->count() === 1);
    }

    public function test_project_expenses_can_be_filtered_by_search_and_status(): void
    {
        $superAdmin = User::where('role_name', 'super_admin')->firstOrFail();
        $project = Project::firstOrFail();

        Expense::create([
            'expense_number' => 'EXP-FILTER-MATCH',
            'project_id' => $project->id,
            'branch_id' => $project->branch_id,
            'user_id' => $superAdmin->id,
            'category' => 'materials',
            'vendor_name' => 'Filter Test Supplier',
            'date' => now(),
            'type' => 'cash',
            'subtotal' => 100,
            'vat_rate' => 0.05,
            'vat_amount' => 5,
            'grand_total' => 105,
            'description' => 'Approved search match',
            'status' => 'approved',
        ]);
        Expense::create([
            'expense_number' => 'EXP-FILTER-OTHER',
            'project_id' => $project->id,
            'branch_id' => $project->branch_id,
            'user_id' => $superAdmin->id,
            'category' => 'materials',
            'vendor_name' => 'Filter Test Supplier',
            'date' => now(),
            'type' => 'cash',
            'subtotal' => 100,
            'vat_rate' => 0.05,
            'vat_amount' => 5,
            'grand_total' => 105,
            'description' => 'Rejected search match',
            'status' => 'rejected',
        ]);

        $this->actingAs($superAdmin);

        $response = $this->get(route('projects.show', [
            'project' => $project,
            'expense_search' => 'EXP-FILTER-MATCH',
            'expense_status' => 'approved',
        ]));

        $response->assertOk();
        $response->assertSee('EXP-FILTER-MATCH');
        $response->assertDontSee('EXP-FILTER-OTHER');
        $response->assertViewHas('projectExpenses', fn ($rows) => $rows->total() === 1);
    }

    public function test_fuel_expense_requires_an_active_vehicle(): void
    {
        $superAdmin = User::where('role_name', 'super_admin')->firstOrFail();
        $project = Project::where('status', 'inprogress')->firstOrFail();
        $inactiveVehicle = Vehicle::create([
            'name' => 'Retired Pickup',
            'registration_number' => 'TEST-RETIRED-01',
            'is_active' => false,
        ]);

        $this->actingAs($superAdmin);
        $payload = [
            'project_id' => $project->id,
            'category' => 'fuel',
            'type' => 'cash',
            'subtotal' => 80,
            'date' => now()->toDateString(),
        ];

        $this->from(route('projects.show', $project))
            ->post(route('expenses.store'), $payload)
            ->assertRedirect(route('projects.show', $project))
            ->assertSessionHasErrors('vehicle_id');

        $this->from(route('projects.show', $project))
            ->post(route('expenses.store'), $payload + ['vehicle_id' => $inactiveVehicle->id])
            ->assertRedirect(route('projects.show', $project))
            ->assertSessionHasErrors('vehicle_id');
    }

    public function test_expense_approver_can_manage_vehicles_and_fuel_expense_saves_vehicle(): void
    {
        $accountant = User::where('role_name', 'accountant')->firstOrFail();
        $project = Project::where('status', 'inprogress')->firstOrFail();
        $this->actingAs($accountant);

        $vehicleResponse = $this->post(route('vehicles.store'), [
            'name' => 'Site Pickup 01',
            'registration_number' => 'TEST-DXB-1001',
            'make_model' => 'Toyota Hilux',
        ]);

        $vehicleResponse->assertRedirect(route('vehicles.index'));
        $vehicle = Vehicle::where('registration_number', 'TEST-DXB-1001')->firstOrFail();

        $this->get(route('projects.show', $project))
            ->assertOk()
            ->assertSee('TEST-DXB-1001 · Site Pickup 01');

        $this->from(route('projects.show', $project))
            ->post(route('expenses.store'), [
                'project_id' => $project->id,
                'vehicle_id' => $vehicle->id,
                'category' => 'fuel',
                'type' => 'cash',
                'subtotal' => 80,
                'date' => now()->toDateString(),
                'description' => 'Fuel for site pickup',
            ])
            ->assertRedirect(route('projects.show', $project));

        $this->assertDatabaseHas('expenses', [
            'project_id' => $project->id,
            'vehicle_id' => $vehicle->id,
            'category' => 'fuel',
        ]);
    }

    public function test_vehicle_can_be_added_from_fuel_expense_with_registration_number_only(): void
    {
        $accountant = User::where('role_name', 'accountant')->firstOrFail();
        $this->actingAs($accountant);

        $this->postJson(route('vehicles.store'), [
            'registration_number' => 'DXB A 12345',
        ])->assertCreated()
            ->assertJsonPath('vehicle.registration_number', 'DXB A 12345')
            ->assertJsonPath('vehicle.name', 'DXB A 12345');

        $this->assertDatabaseHas('vehicles', [
            'registration_number' => 'DXB A 12345',
            'name' => 'DXB A 12345',
            'is_active' => true,
        ]);
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

    public function test_authorized_user_can_edit_project_details_from_the_projects_list(): void
    {
        $superAdmin = User::where('role_name', 'super_admin')->firstOrFail();
        $branch = Branch::firstOrFail();
        $engineer = User::where('role_name', 'engineer')->firstOrFail();
        $project = Project::create([
            'project_number' => 'PRJ-EDIT-TEST',
            'name' => 'Original Project Name',
            'branch_id' => $branch->id,
            'engineer_id' => $engineer->id,
            'client_name' => 'Original Client',
            'client_po_number' => 'PO-KEEP-001',
            'contract_value' => 1000,
            'status' => 'open',
        ]);

        $this->actingAs($superAdmin);

        $this->get(route('projects.index'))
            ->assertOk()
            ->assertSee('aria-label="Edit project PRJ-EDIT-TEST"', false);

        $this->put(route('projects.update', $project), [
            'name' => 'Updated Project Name',
            'client_name' => 'Updated Client',
            'branch_id' => $branch->id,
            'engineer_id' => $engineer->id,
            'contract_value' => 1250.50,
            'start_date' => '2026-11-02',
            'expected_end_date' => '2026-12-15',
            'description' => 'Updated project scope.',
        ])
            ->assertRedirect(route('projects.index'))
            ->assertSessionHas('toast_success');

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'name' => 'Updated Project Name',
            'client_name' => 'Updated Client',
            'contract_value' => 1250.50,
            'client_po_number' => 'PO-KEEP-001',
            'status' => 'open',
        ]);
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

    public function test_awarded_quotation_builder_renders_prefilled_create_project_modal(): void
    {
        $superAdmin = User::where('role_name', 'super_admin')->first();
        $this->actingAs($superAdmin);

        $quote = Quotation::where('status', 'awarded')->firstOrFail();
        $quote->project?->delete();
        $quote->refresh();

        $response = $this->get(route('quotations.builder', ['id' => $quote->id]));

        $response->assertOk();
        $response->assertSee('onclick="openCreateProjectModal()"', false);
        $response->assertSee('id="createProjectModal"', false);
        $response->assertSee(route('projects.store'), false);
        $response->assertSee('name="quotation_id" value="'.$quote->id.'"', false);
        $response->assertSee('name="client_po_number"', false);
        $response->assertSee('name="project_documents[]"', false);
        $response->assertSee('id="projectAuthorizationStatus"', false);
        $response->assertSee('Purchase Order received');
        $response->assertSee('Approved by email — PO to follow');
        $response->assertSee('Not received yet (PO Pending)');
        $response->assertSee('Create Project');
    }

    public function test_project_created_from_awarded_quote_saves_po_and_project_documents(): void
    {
        Storage::fake('public');
        $accountant = User::where('role_name', 'accountant')->first();
        $this->actingAs($accountant);

        $quote = Quotation::where('status', 'awarded')->firstOrFail();
        $quote->project?->delete();
        $quote->refresh();
        $otherBranchId = Branch::where('id', '!=', $quote->branch_id)->value('id');
        $poFile = UploadedFile::fake()->createWithContent('purchase-order.pdf', '%PDF-1.4 purchase order');
        $projectDocument = UploadedFile::fake()->createWithContent('scope.pdf', '%PDF-1.4 project scope');

        $response = $this->post(route('projects.store'), [
            'name' => $quote->lead->subject,
            'client_name' => 'Untrusted customer name',
            'branch_id' => $otherBranchId,
            'lead_id' => Lead::where('id', '!=', $quote->lead_id)->value('id'),
            'quotation_id' => $quote->id,
            'contract_value' => 1,
            'start_date' => '2026-10-29',
            'client_po_number' => 'PO-TEST-2026',
            'client_po_date' => '2026-10-29',
            'client_po_amount' => $quote->subtotal,
            'po_file' => $poFile,
            'project_documents' => [$projectDocument],
            'description' => 'Project scope from awarded quotation.',
        ]);

        $response->assertRedirect();
        $project = Project::where('quotation_id', $quote->id)->firstOrFail();
        $this->assertSame('open', $project->status);
        $this->assertSame('PO-TEST-2026', $project->client_po_number);
        $this->assertSame($quote->lead->company_name, $project->client_name);
        $this->assertSame($quote->branch_id, $project->branch_id);
        $this->assertSame($quote->lead_id, $project->lead_id);
        $this->assertSame((float) $quote->subtotal, (float) $project->contract_value);
        $this->assertSame(2, $project->documents()->count());
        Storage::disk('public')->assertExists($project->client_po_file);
        Storage::disk('public')->assertExists($project->documents()->where('kind', 'general')->value('file_path'));
    }

    public function test_project_cannot_be_created_from_a_non_awarded_quotation(): void
    {
        $accountant = User::where('role_name', 'accountant')->first();
        $this->actingAs($accountant);

        $quote = Quotation::where('status', 'pending')->firstOrFail();

        $response = $this->post(route('projects.store'), [
            'name' => 'Project from pending quotation',
            'client_name' => $quote->lead->company_name,
            'branch_id' => $quote->branch_id,
            'lead_id' => $quote->lead_id,
            'quotation_id' => $quote->id,
            'contract_value' => $quote->subtotal,
        ]);

        $response->assertSessionHasErrors('quotation_id');
        $this->assertDatabaseMissing('projects', ['quotation_id' => $quote->id]);
    }

    public function test_project_without_purchase_order_is_created_as_po_pending(): void
    {
        $accountant = User::where('role_name', 'accountant')->first();
        $this->actingAs($accountant);

        $quote = Quotation::where('status', 'awarded')->firstOrFail();
        $quote->project?->delete();

        $response = $this->post(route('projects.store'), [
            'name' => $quote->lead->subject,
            'client_name' => $quote->lead->company_name,
            'branch_id' => $quote->branch_id,
            'lead_id' => $quote->lead_id,
            'quotation_id' => $quote->id,
            'contract_value' => $quote->subtotal,
            'start_date' => '2026-10-29',
            'client_po_number' => '',
            'client_po_date' => '2026-10-29',
            'client_po_amount' => '',
        ]);

        $response->assertRedirect();
        $project = Project::where('quotation_id', $quote->id)->firstOrFail();
        $this->assertSame('popending', $project->status);
        $this->assertSame('0.00', $project->client_po_amount);
        $this->assertNull($project->client_po_number);
        $this->assertNull($project->client_po_date);
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
        Storage::fake('public');
        $superAdmin = User::where('role_name', 'super_admin')->first();
        $this->actingAs($superAdmin);

        $project = Project::where('status', 'inprogress')->first();
        $handoverDocument = UploadedFile::fake()->createWithContent('handover.pdf', '%PDF-1.4 handover report');

        $response = $this->post("/projects/{$project->id}/complete-work", [
            'completion_date' => '2026-10-26',
            'work_description' => 'Complete waterproofing works executed according to consultant specifications.',
            'retention_pct' => 10.0,
            'retention_amount' => 19950.00,
            'client_representative_name' => 'Eng. Omar Al Qasimi',
            'client_representative_title' => 'Project Director',
            'project_documents' => [$handoverDocument],
        ]);

        $response->assertRedirect();
        $fresh = $project->fresh();
        $this->assertEquals('completed', $fresh->status);
        $this->assertEquals(100, $fresh->progress_pct);
        $this->assertNotNull($fresh->latestWorkCompletionCertificate);
        $this->assertEquals('Eng. Omar Al Qasimi', $fresh->latestWorkCompletionCertificate->client_representative_name);
        $completionDocument = $project->documents()->where('kind', 'completion')->firstOrFail();
        Storage::disk('public')->assertExists($completionDocument->file_path);
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
