<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\Project;
use App\Models\Transfer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_user_can_view_expenses_index_page(): void
    {
        $superAdmin = User::where('role_name', 'super_admin')->first();
        $this->actingAs($superAdmin);

        $response = $this->get('/expenses');
        $response->assertOk();
        $response->assertViewHas(['expenses', 'engineers', 'transfers', 'payments', 'kpis']);
    }

    public function test_engineer_can_submit_petty_cash_expense(): void
    {
        $engineer = User::where('role_name', 'engineer')->first();
        $this->actingAs($engineer);

        $project = Project::first();
        $initialBalance = (float) $engineer->cash_balance;

        $response = $this->post('/expenses', [
            'project_id' => $project->id,
            'branch_id' => $engineer->branch_id,
            'vendor_name' => 'Speedy Hardware Deira',
            'date' => now()->toDateString(),
            'type' => 'cash',
            'category' => 'tools',
            'subtotal' => 200.00,
            'description' => 'Masonry bits and cutting discs',
        ]);

        $response->assertRedirect();

        $expense = Expense::where('vendor_name', 'Speedy Hardware Deira')->first();
        $this->assertNotNull($expense);
        $this->assertEquals(200.00, (float) $expense->subtotal);
        $this->assertEquals(10.00, (float) $expense->vat_amount);
        $this->assertEquals(210.00, (float) $expense->grand_total);
        $this->assertEquals('pending', $expense->status);

        // Engineer cash balance must NOT be debited while status is pending
        $engineer->refresh();
        $this->assertEquals($initialBalance, (float) $engineer->cash_balance);
    }

    public function test_engineer_can_submit_card_expense(): void
    {
        $engineer = User::where('role_name', 'engineer')->first();
        $this->actingAs($engineer);

        $response = $this->post('/expenses', [
            'branch_id' => $engineer->branch_id,
            'vendor_name' => 'ENOC Fuel Station',
            'date' => now()->toDateString(),
            'type' => 'card',
            'category' => 'fuel',
            'subtotal' => 150.00,
            'description' => 'Company car fuel',
        ]);

        $response->assertRedirect();

        $expense = Expense::where('vendor_name', 'ENOC Fuel Station')->first();
        $this->assertNotNull($expense);
        $this->assertEquals('card', $expense->type);
        $this->assertEquals('pending', $expense->status);
    }

    public function test_engineer_can_create_transfer_request(): void
    {
        $engineer = User::where('role_name', 'engineer')->first();
        $this->actingAs($engineer);

        $response = $this->post('/transfers', [
            'to_user_id' => $engineer->id,
            'amount' => 1200.00,
            'payment_method' => 'cash',
            'notes' => 'Site petty cash replenishment request',
        ]);

        $response->assertRedirect();

        $transfer = Transfer::where('notes', 'Site petty cash replenishment request')->first();
        $this->assertNotNull($transfer);
        $this->assertEquals(1200.00, (float) $transfer->amount);
        $this->assertEquals('pending', $transfer->status);
    }

    public function test_reports_index_returns_all_tabs_correctly(): void
    {
        $superAdmin = User::where('role_name', 'super_admin')->first();
        $this->actingAs($superAdmin);

        // Profitability Report
        $res1 = $this->get('/reports?type=profitability');
        $res1->assertOk();
        $res1->assertViewHas('reportType', 'profitability');

        // UAE VAT Summary Report
        $res2 = $this->get('/reports?type=vat');
        $res2->assertOk();
        $res2->assertViewHas('reportType', 'vat');

        // Cash in Hand Ledgers Report
        $res3 = $this->get('/reports?type=cash');
        $res3->assertOk();
        $res3->assertViewHas('reportType', 'cash');
    }

    public function test_reports_export_generates_csv(): void
    {
        $superAdmin = User::where('role_name', 'super_admin')->first();
        $this->actingAs($superAdmin);

        $response = $this->get('/reports/export?type=profitability');
        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('Project Number,Project Name,Client', $response->getContent());
    }

    public function test_api_v1_expenses_mobile_endpoint(): void
    {
        $engineer = User::where('role_name', 'engineer')->first();
        $token = $engineer->createToken('test-mobile-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/expenses');

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'data' => [
                'data' => [
                    '*' => ['id', 'expense_number', 'vendor_name', 'grand_total', 'status']
                ]
            ]
        ]);
    }
}
