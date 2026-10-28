<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\User;
use App\Models\ZohoSyncLog;
use App\Services\ZohoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ZohoSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_accountant_can_view_zoho_books_dashboard(): void
    {
        $accountant = User::where('role_name', 'accountant')->first();
        $this->actingAs($accountant);

        $response = $this->get('/zoho');
        $response->assertOk();
        $response->assertViewHas(['status', 'logs', 'tab']);
        $response->assertSee('Connected to Zoho Books');
        $response->assertSee('Sync Log');
    }

    public function test_zoho_service_syncs_project(): void
    {
        $zohoService = app(ZohoService::class);
        $project = Project::first();
        $this->assertNotNull($project);

        $log = $zohoService->syncProject($project);

        $this->assertEquals('synced', $log->status);
        $this->assertEquals('project', $log->entity_type);
        $this->assertNotNull($log->zoho_id);

        $project->refresh();
        $this->assertNotNull($project->zoho_project_id);
        $this->assertEquals('synced', $project->zoho_status);
    }

    public function test_zoho_service_syncs_tax_invoice(): void
    {
        $zohoService = app(ZohoService::class);
        $invoice = Invoice::first();
        $this->assertNotNull($invoice);

        $log = $zohoService->syncInvoice($invoice);

        $this->assertEquals('synced', $log->status);
        $this->assertEquals('invoice', $log->entity_type);
        $this->assertNotNull($log->zoho_id);

        $invoice->refresh();
        $this->assertNotNull($invoice->zoho_invoice_id);
        $this->assertEquals('synced', $invoice->zoho_status);
    }

    public function test_zoho_service_syncs_expense(): void
    {
        $zohoService = app(ZohoService::class);
        $expense = Expense::first();
        $this->assertNotNull($expense);

        $log = $zohoService->syncExpense($expense);

        $this->assertEquals('synced', $log->status);
        $this->assertEquals('expense', $log->entity_type);
        $this->assertNotNull($log->zoho_id);

        $expense->refresh();
        $this->assertNotNull($expense->zoho_expense_id);
        $this->assertEquals('synced', $expense->zoho_status);
    }

    public function test_accountant_can_trigger_sync_now(): void
    {
        $accountant = User::where('role_name', 'accountant')->first();
        $this->actingAs($accountant);

        $response = $this->post('/zoho/sync');
        $response->assertRedirect();
        $response->assertSessionHas('toast_success');
    }

    public function test_accountant_can_retry_failed_sync_item(): void
    {
        $accountant = User::where('role_name', 'accountant')->first();
        $this->actingAs($accountant);

        $failedLog = ZohoSyncLog::where('status', 'failed')->first();
        $this->assertNotNull($failedLog);

        $response = $this->post("/zoho/logs/{$failedLog->id}/retry");
        $response->assertRedirect();
        $response->assertSessionHas('toast_success');

        $failedLog->refresh();
        $this->assertEquals('synced', $failedLog->status);
    }

    public function test_engineer_cannot_access_or_trigger_zoho_sync(): void
    {
        $engineer = User::where('role_name', 'engineer')->first();
        $this->actingAs($engineer);

        $res1 = $this->get('/zoho');
        $res1->assertForbidden();

        $res2 = $this->post('/zoho/sync');
        $res2->assertForbidden();
    }
}
