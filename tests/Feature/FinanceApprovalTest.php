<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\Transfer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceApprovalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_accountant_can_view_finance_approval_feed(): void
    {
        $accountant = User::where('role_name', 'accountant')->first();
        $this->actingAs($accountant);

        $response = $this->get('/finance');
        $response->assertOk();
        $response->assertViewHas(['pendingExpenses', 'pendingTransfers', 'invoices', 'kpis']);
    }

    public function test_accountant_can_approve_cash_expense_and_debit_engineer_cash(): void
    {
        $accountant = User::where('role_name', 'accountant')->first();
        $this->actingAs($accountant);

        $pendingCashExpense = Expense::where('status', 'pending')
            ->where('type', 'cash')
            ->first();

        $this->assertNotNull($pendingCashExpense);
        $engineer = $pendingCashExpense->user;
        $initialBalance = (float) $engineer->cash_balance;
        $expenseAmount = (float) $pendingCashExpense->grand_total;

        $response = $this->post("/finance/expenses/{$pendingCashExpense->id}/approve");
        $response->assertRedirect();

        $pendingCashExpense->refresh();
        $this->assertEquals('approved', $pendingCashExpense->status);
        $this->assertEquals($accountant->id, $pendingCashExpense->approved_by_id);

        $engineer->refresh();
        $this->assertEquals($initialBalance - $expenseAmount, (float) $engineer->cash_balance);
    }

    public function test_accountant_can_reject_expense_with_mandatory_reason(): void
    {
        $accountant = User::where('role_name', 'accountant')->first();
        $this->actingAs($accountant);

        $pendingExpense = Expense::where('status', 'pending')->first();
        $this->assertNotNull($pendingExpense);

        $response = $this->post("/finance/expenses/{$pendingExpense->id}/reject", [
            'reason' => 'Receipt is illegible. Please re-upload high-resolution photo.',
        ]);
        $response->assertRedirect();

        $pendingExpense->refresh();
        $this->assertEquals('rejected', $pendingExpense->status);
        $this->assertEquals('Receipt is illegible. Please re-upload high-resolution photo.', $pendingExpense->rejection_reason);
    }

    public function test_accountant_can_approve_transfer_and_credit_engineer_cash(): void
    {
        $accountant = User::where('role_name', 'accountant')->first();
        $this->actingAs($accountant);

        $pendingTransfer = Transfer::where('status', 'pending')->first();
        $this->assertNotNull($pendingTransfer);

        $recipient = $pendingTransfer->toUser;
        $initialBalance = (float) $recipient->cash_balance;
        $transferAmount = (float) $pendingTransfer->amount;

        $response = $this->post("/finance/transfers/{$pendingTransfer->id}/approve");
        $response->assertRedirect();

        $pendingTransfer->refresh();
        $this->assertEquals('approved', $pendingTransfer->status);
        $this->assertEquals($accountant->id, $pendingTransfer->approved_by_id);

        $recipient->refresh();
        $this->assertEquals($initialBalance + $transferAmount, (float) $recipient->cash_balance);
    }

    public function test_accountant_can_edit_submitted_transfer_before_approval(): void
    {
        $accountant = User::where('role_name', 'accountant')->first();
        $this->actingAs($accountant);

        $pendingTransfer = Transfer::where('status', 'pending')->first();
        $this->assertNotNull($pendingTransfer);

        $response = $this->put("/transfers/{$pendingTransfer->id}", [
            'amount' => 1950.00,
            'payment_method' => 'bank_transfer',
            'notes' => 'Adjusted amount per monthly branch budget ceiling.',
        ]);
        $response->assertRedirect();

        $pendingTransfer->refresh();
        $this->assertEquals(1950.00, (float) $pendingTransfer->amount);
        $this->assertEquals('Adjusted amount per monthly branch budget ceiling.', $pendingTransfer->notes);
    }

    public function test_engineer_cannot_approve_expenses_or_transfers(): void
    {
        $engineer = User::where('role_name', 'engineer')->first();
        $this->actingAs($engineer);

        $pendingExpense = Expense::where('status', 'pending')->first();
        $pendingTransfer = Transfer::where('status', 'pending')->first();

        $res1 = $this->post("/finance/expenses/{$pendingExpense->id}/approve");
        $res1->assertForbidden();

        $res2 = $this->post("/finance/transfers/{$pendingTransfer->id}/approve");
        $res2->assertForbidden();
    }
}
