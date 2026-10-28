<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Transfer;
use App\Models\User;
use Illuminate\Database\Seeder;

class TransferSeeder extends Seeder
{
    public function run(): void
    {
        $accountant = User::where('role_name', 'accountant')->first()
            ?? User::where('email', 'admin@richmount.ae')->first();
        $engineers = User::where('role_name', 'engineer')->get();
        $dubai = Branch::where('code', 'DXB')->first();
        $abudhabi = Branch::where('code', 'AUH')->first();

        if ($engineers->isEmpty() || !$accountant) {
            return;
        }

        $eng1 = $engineers->first();
        $eng2 = $engineers->count() > 1 ? $engineers->get(1) : $eng1;

        // Reset balances before seeding
        $eng1->update(['cash_balance' => 0]);
        $eng2->update(['cash_balance' => 0]);

        // Transfer 1: Approved Float to Engineer 1 (AED 5,000)
        Transfer::create([
            'transfer_number' => 'TR-2026-0001',
            'branch_id' => $dubai?->id ?? 1,
            'from_user_id' => $accountant->id,
            'to_user_id' => $eng1->id,
            'amount' => 5000.00,
            'date' => now()->subDays(10)->toDateString(),
            'payment_method' => 'bank_transfer',
            'status' => 'approved',
            'approved_by_id' => $accountant->id,
            'approved_at' => now()->subDays(10),
            'notes' => 'Initial monthly site petty cash float disbursement.',
        ]);
        $eng1->increment('cash_balance', 5000.00);

        // Transfer 2: Approved Float to Engineer 2 (AED 4,000)
        Transfer::create([
            'transfer_number' => 'TR-2026-0002',
            'branch_id' => $abudhabi?->id ?? 1,
            'from_user_id' => $accountant->id,
            'to_user_id' => $eng2->id,
            'amount' => 4000.00,
            'date' => now()->subDays(7)->toDateString(),
            'payment_method' => 'cash',
            'status' => 'approved',
            'approved_by_id' => $accountant->id,
            'approved_at' => now()->subDays(7),
            'notes' => 'Petty cash top-up for Abu Dhabi site operations.',
        ]);
        $eng2->increment('cash_balance', 4000.00);

        // Transfer 3: Pending Float Top-up for Engineer 1 (AED 2,500)
        Transfer::create([
            'transfer_number' => 'TR-2026-0003',
            'branch_id' => $dubai?->id ?? 1,
            'from_user_id' => $accountant->id,
            'to_user_id' => $eng1->id,
            'amount' => 2500.00,
            'date' => now()->subDay()->toDateString(),
            'payment_method' => 'bank_transfer',
            'status' => 'pending',
            'notes' => 'Emergency float request for MEP subcontractor cash advance.',
        ]);

        // Transfer 4: Rejected Float Request (AED 8,000)
        Transfer::create([
            'transfer_number' => 'TR-2026-0004',
            'branch_id' => $dubai?->id ?? 1,
            'from_user_id' => $accountant->id,
            'to_user_id' => $eng2->id,
            'amount' => 8000.00,
            'date' => now()->subDays(15)->toDateString(),
            'payment_method' => 'cheque',
            'status' => 'rejected',
            'rejection_reason' => 'Exceeds maximum single engineer petty cash float limit (AED 5,000).',
            'notes' => 'Requested direct material bulk purchase via cash.',
        ]);
    }
}
