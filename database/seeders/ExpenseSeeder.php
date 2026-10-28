<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Expense;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;

class ExpenseSeeder extends Seeder
{
    public function run(): void
    {
        $accountant = User::where('role_name', 'accountant')->first()
            ?? User::where('email', 'admin@richmount.ae')->first();
        $engineers = User::where('role_name', 'engineer')->get();
        $projects = Project::all();
        $dubai = Branch::where('code', 'DXB')->first();
        $abudhabi = Branch::where('code', 'AUH')->first();

        if ($engineers->isEmpty() || !$accountant) {
            return;
        }

        $eng1 = $engineers->first();
        $eng2 = $engineers->count() > 1 ? $engineers->get(1) : $eng1;
        $proj1 = $projects->first();
        $proj2 = $projects->count() > 1 ? $projects->get(1) : $proj1;

        // Expense 1: Approved Cash Expense (debited from Eng 1)
        Expense::create([
            'expense_number' => 'EXP-2026-0001',
            'branch_id' => $dubai?->id ?? 1,
            'user_id' => $eng1->id,
            'project_id' => $proj1?->id,
            'vendor_name' => 'Al Futtaim Building Materials',
            'date' => now()->subDays(6)->toDateString(),
            'type' => 'cash',
            'category' => 'Materials',
            'subtotal' => 600.00,
            'vat_rate' => 0.05,
            'vat_amount' => 30.00,
            'grand_total' => 630.00,
            'description' => 'Fast-drying gypsum plaster and drywall joint tape for reception ceiling.',
            'status' => 'approved',
            'approved_by_id' => $accountant->id,
            'approved_at' => now()->subDays(5),
        ]);
        $eng1->decrement('cash_balance', 630.00);

        // Expense 2: Approved Cash Expense (debited from Eng 1)
        Expense::create([
            'expense_number' => 'EXP-2026-0002',
            'branch_id' => $dubai?->id ?? 1,
            'user_id' => $eng1->id,
            'project_id' => $proj1?->id,
            'vendor_name' => 'ADNOC Fuel Station #742',
            'date' => now()->subDays(4)->toDateString(),
            'type' => 'cash',
            'category' => 'Fuel & Transport',
            'subtotal' => 320.00,
            'vat_rate' => 0.05,
            'vat_amount' => 16.00,
            'grand_total' => 336.00,
            'description' => 'Diesel fuel refill for mobile backup generator on site.',
            'status' => 'approved',
            'approved_by_id' => $accountant->id,
            'approved_at' => now()->subDays(3),
        ]);
        $eng1->decrement('cash_balance', 336.00);

        // Expense 3: Approved Card Expense (Company credit card)
        Expense::create([
            'expense_number' => 'EXP-2026-0003',
            'branch_id' => $dubai?->id ?? 1,
            'user_id' => $eng1->id,
            'project_id' => $proj1?->id,
            'vendor_name' => 'Hilti Emirates LLC',
            'date' => now()->subDays(5)->toDateString(),
            'type' => 'card',
            'category' => 'Equipment Rental',
            'subtotal' => 2400.00,
            'vat_rate' => 0.05,
            'vat_amount' => 120.00,
            'grand_total' => 2520.00,
            'description' => 'Diamond core drill and vacuum suction kit weekly rental.',
            'status' => 'approved',
            'approved_by_id' => $accountant->id,
            'approved_at' => now()->subDays(4),
        ]);

        // Expense 4: Approved Card Expense (Company credit card)
        Expense::create([
            'expense_number' => 'EXP-2026-0004',
            'branch_id' => $abudhabi?->id ?? 1,
            'user_id' => $eng2->id,
            'project_id' => $proj2?->id,
            'vendor_name' => 'Emirates Glass Solutions',
            'date' => now()->subDays(3)->toDateString(),
            'type' => 'card',
            'category' => 'Materials',
            'subtotal' => 5500.00,
            'vat_rate' => 0.05,
            'vat_amount' => 275.00,
            'grand_total' => 5775.00,
            'description' => '12mm tempered safety glass panels for executive boardroom partition.',
            'status' => 'approved',
            'approved_by_id' => $accountant->id,
            'approved_at' => now()->subDays(2),
        ]);

        // Expense 5: Pending Cash Expense (Awaiting Accountant in Approval Center)
        Expense::create([
            'expense_number' => 'EXP-2026-0005',
            'branch_id' => $dubai?->id ?? 1,
            'user_id' => $eng1->id,
            'project_id' => $proj1?->id,
            'vendor_name' => 'ACE Hardware Festival City',
            'date' => now()->subDay()->toDateString(),
            'type' => 'cash',
            'category' => 'Tools & Safety',
            'subtotal' => 450.00,
            'vat_rate' => 0.05,
            'vat_amount' => 22.50,
            'grand_total' => 472.50,
            'description' => 'Heavy duty safety helmets, gloves, and masonry drill bit set.',
            'status' => 'pending',
        ]);

        // Expense 6: Pending Card Expense (Awaiting Accountant in Approval Center)
        Expense::create([
            'expense_number' => 'EXP-2026-0006',
            'branch_id' => $dubai?->id ?? 1,
            'user_id' => $eng1->id,
            'project_id' => $proj1?->id,
            'vendor_name' => 'Speedy Scaffolding UAE',
            'date' => now()->toDateString(),
            'type' => 'card',
            'category' => 'Equipment Rental',
            'subtotal' => 1800.00,
            'vat_rate' => 0.05,
            'vat_amount' => 90.00,
            'grand_total' => 1890.00,
            'description' => 'Aluminum tower mobile scaffolding for double-height atrium painting.',
            'status' => 'pending',
        ]);

        // Expense 7: Rejected Expense
        Expense::create([
            'expense_number' => 'EXP-2026-0007',
            'branch_id' => $abudhabi?->id ?? 1,
            'user_id' => $eng2->id,
            'project_id' => $proj2?->id,
            'vendor_name' => 'Al Madina Supermarket',
            'date' => now()->subDays(8)->toDateString(),
            'type' => 'cash',
            'category' => 'Site Refreshments',
            'subtotal' => 380.00,
            'vat_rate' => 0.05,
            'vat_amount' => 19.00,
            'grand_total' => 399.00,
            'description' => 'Site staff team lunch meals.',
            'status' => 'rejected',
            'rejection_reason' => 'Staff personal meals are not reimbursable under project petty cash policy.',
            'approved_by_id' => $accountant->id,
            'approved_at' => now()->subDays(7),
        ]);
    }
}
