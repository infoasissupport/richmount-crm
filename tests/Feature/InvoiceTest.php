<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_can_create_progress_tax_invoice(): void
    {
        $superAdmin = User::where('role_name', 'super_admin')->first();
        $this->actingAs($superAdmin);

        $project = Project::where('status', 'inprogress')->first();

        $response = $this->post("/projects/{$project->id}/invoices", [
            'invoice_date' => '2026-10-25',
            'due_date' => '2026-11-24',
            'is_final' => false,
            'client_trn' => '100987654300003',
            'amount' => 50000.00,
            'items' => [
                [
                    'description' => 'Milestone 2: 50% completion of structural waterproofing',
                    'quantity' => 1,
                    'unit' => 'lump sum',
                    'unit_price' => 50000.00,
                ],
            ],
        ]);

        $response->assertRedirect();

        $invoice = Invoice::where('project_id', $project->id)->latest('id')->first();
        $this->assertNotNull($invoice);
        $this->assertEquals(50000.00, (float) $invoice->subtotal);
        $this->assertEquals(2500.00, (float) $invoice->vat_amount);
        $this->assertEquals(52500.00, (float) $invoice->grand_total);
        $this->assertEquals(52500.00, (float) $invoice->balance_due);
        $this->assertEquals('sent', $invoice->status);
    }

    public function test_create_final_invoice_transitions_project_to_invoicepending(): void
    {
        $superAdmin = User::where('role_name', 'super_admin')->first();
        $this->actingAs($superAdmin);

        $project = Project::where('status', 'completed')->first();
        $this->assertNotNull($project);

        $response = $this->post("/projects/{$project->id}/invoices", [
            'invoice_date' => '2026-10-25',
            'due_date' => '2026-11-24',
            'is_final' => true,
            'amount' => 100000.00,
        ]);

        $response->assertRedirect();
        $this->assertEquals('invoicepending', $project->fresh()->status);
    }

    public function test_recording_payment_updates_balance_and_closes_project_on_final_settlement(): void
    {
        $superAdmin = User::where('role_name', 'super_admin')->first();
        $this->actingAs($superAdmin);

        $project = Project::where('status', 'completed')->first();

        // 1. Raise final invoice
        $this->post("/projects/{$project->id}/invoices", [
            'invoice_date' => '2026-10-25',
            'due_date' => '2026-11-24',
            'is_final' => true,
            'amount' => 100000.00, // Grand total = 105,000 AED
        ]);

        $finalInvoice = Invoice::where('project_id', $project->id)->where('is_final', true)->latest('id')->first();
        $this->assertEquals(105000.00, (float) $finalInvoice->grand_total);

        // 2. Partial Payment (50,000 AED)
        $partialPayResponse = $this->post("/invoices/{$finalInvoice->id}/payment", [
            'amount' => 50000.00,
            'payment_date' => '2026-10-26',
            'payment_method' => 'bank_transfer',
            'payment_reference' => 'WIRE-PARTIAL-1',
        ]);
        $partialPayResponse->assertRedirect();

        $freshInv = $finalInvoice->fresh();
        $this->assertEquals(50000.00, (float) $freshInv->paid_amount);
        $this->assertEquals(55000.00, (float) $freshInv->balance_due);
        $this->assertEquals('partially_paid', $freshInv->status);
        $this->assertNotEquals('closed', $project->fresh()->status);

        // 3. Final Settlement Payment (remaining 55,000 AED)
        $finalPayResponse = $this->post("/invoices/{$finalInvoice->id}/payment", [
            'amount' => 55000.00,
            'payment_date' => '2026-10-27',
            'payment_method' => 'bank_transfer',
            'payment_reference' => 'WIRE-FINAL-SETTLEMENT',
        ]);
        $finalPayResponse->assertRedirect();

        $fullyPaidInv = $finalInvoice->fresh();
        $this->assertEquals(105000.00, (float) $fullyPaidInv->paid_amount);
        $this->assertEquals(0.00, (float) $fullyPaidInv->balance_due);
        $this->assertEquals('paid', $fullyPaidInv->status);

        // Project must now be officially closed!
        $this->assertEquals('closed', $project->fresh()->status);
        $this->assertNotNull($project->fresh()->closed_at);
    }

    public function test_tax_invoice_pdf_download(): void
    {
        $superAdmin = User::where('role_name', 'super_admin')->first();
        $this->actingAs($superAdmin);

        $invoice = Invoice::first();
        $this->assertNotNull($invoice);

        $response = $this->get("/invoices/{$invoice->id}/pdf");
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_mobile_api_invoice_endpoints(): void
    {
        $superAdmin = User::where('role_name', 'super_admin')->first();
        $token = $superAdmin->createToken('flutter-test')->plainTextToken;

        // List
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/invoices');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'data' => [
                    '*' => ['id', 'invoice_number', 'grand_total', 'balance_due', 'status'],
                ],
            ],
        ]);

        // Payment via API
        $invoice = Invoice::where('balance_due', '>', 0)->first();
        if ($invoice) {
            $payResponse = $this->withHeader('Authorization', "Bearer {$token}")
                ->postJson("/api/v1/invoices/{$invoice->id}/payment", [
                    'amount' => 5000.00,
                    'payment_date' => '2026-10-27',
                    'payment_method' => 'bank_transfer',
                ]);

            $payResponse->assertStatus(200);
            $payResponse->assertJson(['success' => true]);
        }
    }
}
