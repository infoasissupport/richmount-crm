<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\Project;
use App\Models\PurchaseOrder;
use App\Models\Transfer;
use App\Models\User;
use Illuminate\Support\Collection;

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
     * Project Profitability Report with real invoice/payment/expense totals.
     */
    public function projectProfitability(array $filters = []): array
    {
        $query = Project::query()->with(['branch', 'invoices.payments', 'expenses.branch', 'expenses.user', 'purchaseOrders']);

        if (! empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('project_number', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('client_name', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['project_id'])) {
            $query->where('id', $filters['project_id']);
        }

        if (! empty($filters['branch_id'])) {
            $query->where('branch_id', $filters['branch_id']);
        }

        if (! empty($filters['client'])) {
            $query->where('client_name', 'like', "%{$filters['client']}%");
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('start_date', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('start_date', '<=', $filters['date_to']);
        }

        return $query->get()->map(function (Project $project) {
            return $this->projectFinancialSummaryRow($project);
        })->values()->all();
    }

    public function projectFinancialSummaryRow(Project $project): array
    {
        $contractValue = (float) $project->contract_value;
        $totalInvoiced = (float) $project->totalInvoiced();
        $totalReceived = (float) $this->projectReceivedPayments($project);
        $totalExpenses = (float) $project->expenses()->where('status', 'approved')->sum('grand_total');
        $vendorPos = (float) $project->purchaseOrders()->where('status', 'approved')->sum('grand_total');
        $totalProjectCost = $totalExpenses + $vendorPos;
        $outstanding = max(0.0, $totalInvoiced - $totalReceived);
        $contractProfit = $contractValue - $totalProjectCost;
        $invoicedProfit = $totalInvoiced - $totalProjectCost;
        $profitMargin = $totalInvoiced > 0 ? round(($invoicedProfit / $totalInvoiced) * 100, 2) : 0.0;
        $cashPosition = $totalReceived - $totalProjectCost;

        return [
            'id' => $project->id,
            'project_id' => $project->id,
            'project_number' => $project->project_number,
            'name' => $project->name,
            'client' => $project->client_name,
            'branch' => $project->branch?->short_name ?? $project->branch?->name,
            'status' => $project->statusLabel(),
            'contract_value' => $contractValue,
            'total_invoiced' => $totalInvoiced,
            'total_received' => $totalReceived,
            'total_expenses' => $totalExpenses,
            'vendor_pos' => $vendorPos,
            'total_project_cost' => $totalProjectCost,
            'outstanding_amount' => $outstanding,
            'contract_profit' => $contractProfit,
            'invoiced_profit' => $invoicedProfit,
            'net_profit' => $invoicedProfit,
            'profit_margin' => $profitMargin,
            'cash_position' => $cashPosition,
            'invoiced' => $totalInvoiced,
            'expenses' => $totalExpenses,
            'total_cost' => $totalProjectCost,
            'profit' => $invoicedProfit,
            'margin_pct' => $profitMargin,
        ];
    }

    public function projectFinancialDetail(Project $project): array
    {
        $projectInvoices = $project->invoices()->with('payments', 'branch')->orderByDesc('invoice_date')->get();
        $approvedExpenses = $project->expenses()->with('branch', 'user')->where('status', 'approved')->orderByDesc('date')->get();
        $pendingExpenses = $project->expenses()->with('branch', 'user')->where('status', 'pending')->orderByDesc('date')->get();
        $allProjectExpenses = $project->expenses()->with('branch', 'user')->orderByDesc('date')->get();

        $invoiceRows = $projectInvoices->map(function (Invoice $invoice) {
            $paidAmount = (float) $invoice->payments()->sum('amount');
            $paymentStatus = $paidAmount >= (float) $invoice->grand_total ? 'Received' : ($paidAmount > 0 ? 'Partially Received' : 'Unpaid');

            return [
                'date' => $invoice->invoice_date?->format('d M Y') ?? '—',
                'invoice_number' => $invoice->invoice_number,
                'description' => $invoice->notes ?: ($invoice->is_final ? 'Final invoice' : 'Project invoice'),
                'client' => $invoice->client_name ?: $project->client_name,
                'invoice_amount' => (float) $invoice->grand_total,
                'tax_amount' => (float) $invoice->vat_amount,
                'invoice_status' => ucfirst($invoice->status),
                'payment_status' => $paymentStatus,
                'is_payment' => false,
            ];
        })->values()->all();

        $paymentRows = InvoicePayment::query()
            ->whereIn('invoice_id', $projectInvoices->pluck('id'))
            ->with('invoice')
            ->orderByDesc('payment_date')
            ->get()
            ->map(function (InvoicePayment $payment) {
                return [
                    'date' => $payment->payment_date?->format('d M Y') ?? '—',
                    'invoice_number' => $payment->invoice?->invoice_number ?? '—',
                    'description' => $payment->payment_method ? 'Payment received via ' . ucfirst(str_replace('_', ' ', $payment->payment_method)) : 'Payment received',
                    'client' => $payment->invoice?->client_name ?: '—',
                    'invoice_amount' => (float) $payment->amount,
                    'tax_amount' => 0.0,
                    'invoice_status' => $payment->invoice?->status ? ucfirst($payment->invoice->status) : 'Received',
                    'payment_status' => 'Received',
                    'is_payment' => true,
                    'reference' => $payment->payment_reference,
                ];
            })->values()->all();

        $expenseRows = $allProjectExpenses->map(function (Expense $expense) {
            return [
                'date' => $expense->date?->format('d M Y') ?? '—',
                'expense_number' => $expense->expense_number,
                'category' => $this->expenseCategoryLabel($expense->category),
                'description' => $expense->description ?: 'Project expense',
                'vendor' => $expense->vendor_name ?: '—',
                'quantity' => '—',
                'unit' => '—',
                'amount' => (float) $expense->grand_total,
                'tax' => (float) $expense->vat_amount,
                'payment_method' => ucfirst(str_replace('_', ' ', (string) $expense->type)),
                'receipt' => $expense->receipt_file ? asset('storage/' . $expense->receipt_file) : null,
                'branch' => $expense->branch?->name ?? '—',
                'created_by' => $expense->user?->name ?? '—',
                'status' => ucfirst($expense->status),
            ];
        })->values()->all();

        $categoryTotals = collect($expenseRows)->groupBy('category')->map(function ($items, $category) {
            return [
                'label' => $category,
                'amount' => round((float) collect($items)->sum('amount'), 2),
                'count' => count($items),
            ];
        })->sortByDesc('amount')->values()->all();

        $summary = [
            'contract_amount' => (float) $project->contract_value,
            'total_invoiced' => (float) $projectInvoices->sum('grand_total'),
            'total_received' => $this->projectReceivedPayments($project),
            'total_expenses' => (float) $approvedExpenses->sum('grand_total'),
            'outstanding_amount' => max(0.0, ((float) $projectInvoices->sum('grand_total')) - $this->projectReceivedPayments($project)),
            'contract_profit' => (float) $project->contract_value - (float) $approvedExpenses->sum('grand_total'),
            'invoiced_profit' => ((float) $projectInvoices->sum('grand_total')) - (float) $approvedExpenses->sum('grand_total'),
            'cash_position' => $this->projectReceivedPayments($project) - (float) $approvedExpenses->sum('grand_total'),
        ];
        $summary['profit_margin'] = $summary['total_invoiced'] > 0 ? round(($summary['invoiced_profit'] / $summary['total_invoiced']) * 100, 2) : 0.0;
        $summary['net_profit'] = $summary['invoiced_profit'];

        return [
            'project' => $project,
            'summary' => $summary,
            'income_rows' => $invoiceRows,
            'payment_rows' => $paymentRows,
            'expense_rows' => $expenseRows,
            'category_totals' => $categoryTotals,
            'approved_expenses' => $approvedExpenses,
            'pending_expenses' => $pendingExpenses,
            'project_expense_total' => (float) $approvedExpenses->sum('grand_total'),
        ];
    }

    protected function projectReceivedPayments(Project $project): float
    {
        $invoiceIds = $project->invoices()->whereNotIn('status', ['draft', 'void'])->pluck('id');

        return (float) InvoicePayment::query()->whereIn('invoice_id', $invoiceIds)->sum('amount');
    }

    protected function expenseCategoryLabel(?string $category): string
    {
        return match ($category) {
            'materials' => 'Material Costs',
            'labor' => 'Labour Costs',
            'transport' => 'Transportation Costs',
            'tools' => 'Equipment / Tools Costs',
            'fuel' => 'Transportation Costs',
            'meals' => 'Other Expenses',
            'permits' => 'Other Expenses',
            'miscellaneous' => 'Other Expenses',
            default => ucfirst((string) $category ?: 'Other Expenses'),
        };
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
