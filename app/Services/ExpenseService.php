<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ExpenseService
{
    /**
     * Create new expense.
     */
    public function createExpense(array $data, User $author): Expense
    {
        return DB::transaction(function () use ($data, $author) {
            $subtotal = (float) ($data['subtotal'] ?? 0);
            $vatRate = (float) ($data['vat_rate'] ?? 0.05);
            $vatAmount = (float) ($data['vat_amount'] ?? round($subtotal * $vatRate, 2));
            $grandTotal = (float) ($data['grand_total'] ?? ($subtotal + $vatAmount));

            $isAccountant = $author->isSuperAdmin() || $author->isAccountant();
            $status = $isAccountant && ($data['auto_approve'] ?? false) ? 'approved' : 'pending';

            $expense = Expense::create([
                'expense_number' => Expense::nextExpenseNumber(),
                'project_id' => $data['project_id'] ?? null,
                'branch_id' => $data['branch_id'] ?? $author->branch_id,
                'user_id' => $data['user_id'] ?? $author->id,
                'category' => $data['category'] ?? 'materials',
                'vendor_name' => $data['vendor_name'] ?? null,
                'vendor_trn' => $data['vendor_trn'] ?? null,
                'bill_number' => $data['bill_number'] ?? null,
                'date' => $data['date'] ?? now()->toDateString(),
                'type' => $data['type'] ?? 'cash',
                'subtotal' => $subtotal,
                'vat_rate' => $vatRate,
                'vat_amount' => $vatAmount,
                'grand_total' => $grandTotal,
                'receipt_file' => $data['receipt_file'] ?? null,
                'ocr_data' => $data['ocr_data'] ?? null,
                'description' => $data['description'] ?? null,
                'status' => $status,
                'approved_by_id' => $status === 'approved' ? $author->id : null,
                'approved_at' => $status === 'approved' ? now() : null,
            ]);

            // If auto-approved and paid via cash, decrement user's cash balance
            if ($status === 'approved' && $expense->type === 'cash') {
                $user = User::find($expense->user_id);
                if ($user) {
                    $user->decrement('cash_balance', $expense->grand_total);
                }
            }

            if ($expense->project) {
                $expense->project->activities()->create([
                    'user_id' => $author->id,
                    'kind' => 'expense',
                    'text' => "Expense {$expense->expense_number} recorded for {$expense->grand_total} AED ({$expense->category})",
                ]);
            }

            AuditService::log('created', $expense, "Recorded expense {$expense->expense_number} for AED {$expense->grand_total}");

            return $expense;
        });
    }

    /**
     * Approve expense (by Accountant/Admin).
     */
    public function approveExpense(Expense $expense, User $approver): Expense
    {
        return DB::transaction(function () use ($expense, $approver) {
            $expense->update([
                'status' => 'approved',
                'approved_by_id' => $approver->id,
                'approved_at' => now(),
                'rejection_reason' => null,
            ]);

            // Deduct from engineer's cash balance if it was a cash expense
            if ($expense->type === 'cash') {
                $user = User::find($expense->user_id);
                if ($user) {
                    $user->decrement('cash_balance', $expense->grand_total);
                }
            }

            if ($expense->project) {
                $expense->project->activities()->create([
                    'user_id' => $approver->id,
                    'kind' => 'expense_approved',
                    'text' => "Expense {$expense->expense_number} approved by Accountant ({$expense->grand_total} AED)",
                ]);
            }

            AuditService::log('approved', $expense, "Approved expense {$expense->expense_number} of AED {$expense->grand_total}");

            \App\Jobs\SyncExpenseToZohoJob::dispatch($expense);

            return $expense;
        });
    }

    /**
     * Reject expense with reason.
     */
    public function rejectExpense(Expense $expense, string $reason, User $approver): Expense
    {
        return DB::transaction(function () use ($expense, $reason, $approver) {
            $expense->update([
                'status' => 'rejected',
                'rejection_reason' => $reason,
                'approved_by_id' => $approver->id,
                'approved_at' => now(),
            ]);

            AuditService::log('rejected', $expense, "Rejected expense {$expense->expense_number}: {$reason}");

            return $expense;
        });
    }
}
