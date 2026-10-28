<?php

namespace Database\Seeders;

use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\ZohoSyncLog;
use Illuminate\Database\Seeder;

class ZohoSyncLogSeeder extends Seeder
{
    public function run(): void
    {
        $project = Project::first();
        $invoice = Invoice::first();
        $expense1 = Expense::where('status', 'approved')->first();
        $expense2 = Expense::where('status', 'rejected')->first();

        // 1. Synced Project
        if ($project) {
            ZohoSyncLog::create([
                'entity_type' => 'project',
                'entity_id' => $project->id,
                'reference' => $project->project_number,
                'action' => 'create_project',
                'status' => 'synced',
                'zoho_id' => 'zh_prj_894102941',
                'message' => 'Project created in Zoho Books under Commercial Projects.',
                'attempts' => 1,
                'synced_at' => now()->subDays(2),
            ]);
            $project->update(['zoho_project_id' => 'zh_prj_894102941', 'zoho_status' => 'synced']);
        }

        // 2. Synced Tax Invoice
        if ($invoice) {
            ZohoSyncLog::create([
                'entity_type' => 'invoice',
                'entity_id' => $invoice->id,
                'reference' => $invoice->invoice_number,
                'action' => 'create_invoice',
                'status' => 'synced',
                'zoho_id' => 'zh_inv_392019401',
                'message' => 'Tax Invoice posted with 5% UAE VAT line items.',
                'attempts' => 1,
                'synced_at' => now()->subDay(),
            ]);
            $invoice->update(['zoho_invoice_id' => 'zh_inv_392019401', 'zoho_status' => 'synced']);
        }

        // 3. Synced Petty Cash Expense
        if ($expense1) {
            ZohoSyncLog::create([
                'entity_type' => 'expense',
                'entity_id' => $expense1->id,
                'reference' => $expense1->expense_number,
                'action' => 'record_expense',
                'status' => 'synced',
                'zoho_id' => 'zh_exp_194029410',
                'message' => 'Recorded against engineer petty cash float account.',
                'attempts' => 1,
                'synced_at' => now()->subHours(8),
            ]);
            $expense1->update(['zoho_expense_id' => 'zh_exp_194029410', 'zoho_status' => 'synced']);
        }

        // 4. Failed Sync Item (Demonstrates retry and failure diagnostics)
        ZohoSyncLog::create([
            'entity_type' => 'expense',
            'entity_id' => $expense2?->id,
            'reference' => $expense2?->expense_number ?? 'EXP-2026-0007',
            'action' => 'record_expense',
            'status' => 'failed',
            'message' => 'Vendor TRN format invalid according to UAE FTA schema (missing 15-digit TRN prefix).',
            'attempts' => 2,
        ]);

        // 5. Queued Sync Item
        ZohoSyncLog::create([
            'entity_type' => 'invoice',
            'reference' => 'INV-2026-0002',
            'action' => 'create_invoice',
            'status' => 'queued',
            'message' => 'Awaiting automated dispatch worker.',
            'attempts' => 0,
        ]);
    }
}
