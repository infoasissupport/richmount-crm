<?php

namespace Database\Seeders;

use App\Models\Invoice;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;

class InvoiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $accountant = User::where('role_name', 'accountant')->first();

        // 1. Progress Invoice on Project 1 (Sharjah)
        $p1 = Project::where('project_number', 'PRJ-2026-0001')->first();
        if ($p1) {
            $inv1 = Invoice::create([
                'invoice_number' => 'INV-2026-0001',
                'project_id' => $p1->id,
                'quotation_id' => $p1->quotation_id,
                'branch_id' => $p1->branch_id,
                'created_by_id' => $accountant?->id,
                'client_name' => $p1->client_name,
                'client_trn' => '100456789000003',
                'client_address' => 'Arada Sales Centre, Aljada, Sharjah, UAE',
                'client_po_number' => $p1->client_po_number,
                'invoice_date' => '2026-10-22',
                'due_date' => '2026-11-21',
                'is_final' => false,
                'subtotal' => 60000.00,
                'discount_amount' => 0.00,
                'taxable_amount' => 60000.00,
                'vat_rate' => 0.05,
                'vat_amount' => 3000.00,
                'grand_total' => 63000.00,
                'paid_amount' => 63000.00,
                'balance_due' => 0.00,
                'status' => 'paid',
                'payment_terms' => '30 days payment term from invoice submission date.',
                'notes' => 'Advance milestone payment (30% of contract value).',
            ]);

            $inv1->lineItems()->create([
                'sort_order' => 1,
                'description' => 'Advance Payment Milestone (30% of contract value)',
                'quantity' => 1,
                'unit' => 'lump sum',
                'unit_price' => 60000.00,
                'total_amount' => 60000.00,
            ]);

            $inv1->payments()->create([
                'recorded_by_id' => $accountant?->id,
                'payment_reference' => 'PAY-ARD-8812',
                'payment_date' => '2026-10-24',
                'amount' => 63000.00,
                'payment_method' => 'bank_transfer',
                'bank_reference' => 'ENBD-WIRE-881204',
                'notes' => 'Received via wire transfer to Emirates NBD account.',
            ]);
        }

        // 2. Final Invoice on Project 3 (Dubai Fitout - Completed)
        $p3 = Project::where('project_number', 'PRJ-2026-0003')->first();
        if ($p3) {
            $inv2 = Invoice::create([
                'invoice_number' => 'INV-2026-0002',
                'project_id' => $p3->id,
                'quotation_id' => $p3->quotation_id,
                'branch_id' => $p3->branch_id,
                'created_by_id' => $accountant?->id,
                'client_name' => $p3->client_name,
                'client_trn' => '100123456700003',
                'client_address' => 'Emaar Square, Building 3, Downtown Dubai, UAE',
                'client_po_number' => $p3->client_po_number,
                'invoice_date' => '2026-10-18',
                'due_date' => '2026-11-17',
                'is_final' => true,
                'subtotal' => 245000.00,
                'discount_amount' => 0.00,
                'taxable_amount' => 245000.00,
                'vat_rate' => 0.05,
                'vat_amount' => 12250.00,
                'grand_total' => 257250.00,
                'paid_amount' => 0.00,
                'balance_due' => 257250.00,
                'status' => 'sent',
                'payment_terms' => 'Net 30 days upon submission of Work Completion Certificate.',
                'notes' => 'Final 100% contract invoice with 10% retention payable after DLP.',
            ]);

            $inv2->lineItems()->create([
                'sort_order' => 1,
                'description' => 'Final Project Billing upon Practical Handover (WCC-2026-0001)',
                'quantity' => 1,
                'unit' => 'lump sum',
                'unit_price' => 245000.00,
                'total_amount' => 245000.00,
            ]);
        }
    }
}
