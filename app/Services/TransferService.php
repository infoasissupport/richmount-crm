<?php

namespace App\Services;

use App\Models\Transfer;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TransferService
{
    /**
     * Create an internal petty cash transfer.
     */
    public function createTransfer(array $data, User $author): Transfer
    {
        return DB::transaction(function () use ($data, $author) {
            $isAccountant = $author->isSuperAdmin() || $author->isAccountant();
            $status = $isAccountant && ($data['auto_approve'] ?? false) ? 'approved' : 'pending';

            $transfer = Transfer::create([
                'transfer_number' => Transfer::nextTransferNumber(),
                'date' => $data['date'] ?? now()->toDateString(),
                'from_user_id' => $data['from_user_id'] ?? $author->id,
                'to_user_id' => $data['to_user_id'],
                'branch_id' => $data['branch_id'] ?? $author->branch_id,
                'created_by_id' => $author->id,
                'amount' => $data['amount'],
                'payment_method' => $data['payment_method'] ?? 'cash',
                'reference_number' => $data['reference_number'] ?? null,
                'status' => $status,
                'approved_by_id' => $status === 'approved' ? $author->id : null,
                'approved_at' => $status === 'approved' ? now() : null,
                'notes' => $data['notes'] ?? null,
            ]);

            // If auto-approved, immediately update cash balances
            if ($status === 'approved') {
                $toUser = User::find($transfer->to_user_id);
                $toUser?->increment('cash_balance', $transfer->amount);

                if ($transfer->from_user_id && $transfer->fromUser?->isEngineer()) {
                    $transfer->fromUser->decrement('cash_balance', $transfer->amount);
                }
            }

            AuditService::log('created', $transfer, "Initiated internal transfer {$transfer->transfer_number} of AED {$transfer->amount}");

            return $transfer;
        });
    }

    /**
     * Approve internal transfer (by Accountant).
     */
    public function approveTransfer(Transfer $transfer, User $approver): Transfer
    {
        return DB::transaction(function () use ($transfer, $approver) {
            $transfer->update([
                'status' => 'approved',
                'approved_by_id' => $approver->id,
                'approved_at' => now(),
                'rejection_reason' => null,
            ]);

            // Credit recipient engineer's petty cash balance
            $toUser = User::find($transfer->to_user_id);
            $toUser?->increment('cash_balance', $transfer->amount);

            // If sender was an engineer, decrement sender's petty cash
            if ($transfer->from_user_id && $transfer->fromUser?->isEngineer()) {
                $transfer->fromUser->decrement('cash_balance', $transfer->amount);
            }

            AuditService::log('approved', $transfer, "Approved transfer {$transfer->transfer_number} of AED {$transfer->amount}");

            return $transfer;
        });
    }

    /**
     * Reject internal transfer with reason.
     */
    public function rejectTransfer(Transfer $transfer, string $reason, User $approver): Transfer
    {
        return DB::transaction(function () use ($transfer, $reason, $approver) {
            $transfer->update([
                'status' => 'rejected',
                'rejection_reason' => $reason,
                'approved_by_id' => $approver->id,
                'approved_at' => now(),
            ]);

            AuditService::log('rejected', $transfer, "Rejected transfer {$transfer->transfer_number}: {$reason}");

            return $transfer;
        });
    }

    /**
     * Update submitted transfer (Accountant privilege).
     */
    public function updateTransfer(Transfer $transfer, array $data, User $editor): Transfer
    {
        return DB::transaction(function () use ($transfer, $data, $editor) {
            $old = $transfer->toArray();
            $transfer->update($data);

            AuditService::log('updated', $transfer, "Accountant edited transfer {$transfer->transfer_number}", $old, $transfer->fresh()->toArray());

            return $transfer;
        });
    }
}
