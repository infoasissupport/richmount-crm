<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\Project;
use App\Models\PurchaseOrder;
use App\Models\Transfer;
use App\Models\User;

class ReportService
{
    /**
     * Get Cash in Hand ledger for a specific engineer.
     */
    public function engineerCashLedger(User $engineer): array
    {
        $transfersIn = Transfer::where('to_user_id', $engineer->id)
            ->where('status', 'approved')
            ->get()
            ->map(fn($t) => [
                'date' => $t->date->toDateString(),
                'type' => 'transfer_in',
                'type_label' => 'Float Received',
                'ref' => $t->transfer_number,
                'reference' => $t->transfer_number,
                'description' => "Cash transferred from {$t->fromUser?->name}",
                'notes' => $t->notes ?: "Cash transferred from {$t->fromUser?->name}",
                'amount' => (float) $t->amount,
                'is_credit' => true,
                'in' => (float) $t->amount,
                'out' => 0.00,
            ]);

        $cashExpenses = Expense::where('user_id', $engineer->id)
            ->where('type', 'cash')
            ->where('status', 'approved')
            ->get()
            ->map(fn($e) => [
                'date' => $e->date->toDateString(),
                'type' => 'expense_out',
                'type_label' => 'Cash Expense',
                'ref' => $e->expense_number,
                'reference' => $e->expense_number,
                'description' => "{$e->category} - {$e->vendor_name} ({$e->project?->project_number})",
                'notes' => "{$e->category}: {$e->description}",
                'amount' => (float) $e->grand_total,
                'is_credit' => false,
                'in' => 0.00,
                'out' => (float) $e->grand_total,
            ]);

        $ledger = $transfersIn->concat($cashExpenses)->sortBy('date')->values()->all();

        return [
            'engineer' => $engineer,
            'engineer_name' => $engineer->name,
            'email' => $engineer->email,
            'branch' => $engineer->branch?->name ?? 'All Branches',
            'current_balance' => (float) $engineer->cash_balance,
            'current_cash_balance' => (float) $engineer->cash_balance,
            'total_credited' => (float) $transfersIn->sum('in'),
            'total_spent' => (float) $cashExpenses->sum('out'),
            'entries' => $ledger,
            'history' => $ledger,
        ];
    }

    /**
     * Project Profitability Report.
     */
    public function projectProfitability(): array
    {
        return Project::with(['branch', 'invoices', 'expenses', 'purchaseOrders'])
            ->get()
            ->map(function ($p) {
                $contractValue = (float) $p->contract_value;
                $invoiced = (float) $p->totalInvoiced();
                $expenses = (float) $p->expenses->where('status', 'approved')->sum('grand_total');
                $vendorPos = (float) $p->purchaseOrders->where('status', 'approved')->sum('grand_total');
                $totalCost = $expenses + $vendorPos;
                $profit = $invoiced - $totalCost;
                $marginPct = $invoiced > 0 ? round(($profit / $invoiced) * 100, 1) : 0;

                return [
                    'id' => $p->id,
                    'project_id' => $p->id,
                    'project_number' => $p->project_number,
                    'name' => $p->name,
                    'client' => $p->client_name,
                    'branch' => $p->branch?->short_name ?? $p->branch?->name,
                    'status' => $p->statusLabel(),
                    'contract_value' => $contractValue,
                    'invoiced' => $invoiced,
                    'expenses' => $expenses,
                    'vendor_pos' => $vendorPos,
                    'total_cost' => $totalCost,
                    'profit' => $profit,
                    'margin_pct' => $marginPct,
                ];
            })
            ->toArray();
    }

    /**
     * VAT Return Summary (UAE FTA 5%).
     */
    public function vatSummary(): array
    {
        $taxPeriod = now()->startOfQuarter()->format('d M Y') . ' - ' . now()->endOfQuarter()->format('d M Y');
        $standardRatedSupplies = (float) Invoice::whereNotIn('status', ['draft', 'void'])->sum('subtotal');
        $outputVat = (float) Invoice::whereNotIn('status', ['draft', 'void'])->sum('vat_amount');

        $standardRatedExpenses = (float) Expense::where('status', 'approved')->sum('subtotal') + (float) PurchaseOrder::where('status', 'approved')->sum('subtotal');
        $inputVatExpenses = (float) Expense::where('status', 'approved')->sum('vat_amount');
        $inputVatPos = (float) PurchaseOrder::where('status', 'approved')->sum('vat_amount');
        $totalInputVat = $inputVatExpenses + $inputVatPos;
        $netVatPayable = $outputVat - $totalInputVat;

        return [
            'tax_period' => $taxPeriod,
            'standard_rated_supplies' => $standardRatedSupplies,
            'output_vat' => $outputVat,
            'standard_rated_expenses' => $standardRatedExpenses,
            'input_vat' => $totalInputVat,
            'input_vat_expenses' => $inputVatExpenses,
            'input_vat_pos' => $inputVatPos,
            'total_input_vat' => $totalInputVat,
            'net_vat_payable' => $netVatPayable,
        ];
    }
}
