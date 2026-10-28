<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\Transfer;
use App\Services\ExpenseService;
use App\Services\TransferService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FinanceController extends Controller
{
    public function __construct(
        protected ExpenseService $expenseService,
        protected TransferService $transferService
    ) {}

    /**
     * Display Finance Approval Feed & Center.
     */
    public function index(Request $request): View|JsonResponse
    {
        $this->authorize('viewAny', Expense::class);
        $user = $request->user();

        // 1. Pending Expense Approvals
        $pendingExpenses = Expense::with(['project', 'branch', 'user'])
            ->where('status', 'pending')
            ->latest('date')
            ->get();

        // 2. Pending Transfer Approvals
        $pendingTransfers = Transfer::with(['fromUser', 'toUser', 'branch'])
            ->where('status', 'pending')
            ->latest('date')
            ->get();

        // 3. Invoices Overview
        $invoices = Invoice::with(['project', 'branch'])
            ->latest('invoice_date')
            ->take(15)
            ->get();

        // 4. Financial KPIs
        $kpis = [
            'pending_approvals' => $pendingExpenses->count() + $pendingTransfers->count(),
            'total_expenses' => Expense::where('status', 'approved')->sum('grand_total'),
            'total_invoiced' => Invoice::whereNotIn('status', ['draft', 'void'])->sum('grand_total'),
            'awaiting_activation' => Project::where('status', 'popending')->count(),
        ];

        $branches = Branch::active()->get();

        if ($request->wantsJson()) {
            return response()->json([
                'kpis' => $kpis,
                'pending_expenses' => $pendingExpenses,
                'pending_transfers' => $pendingTransfers,
                'invoices' => $invoices,
            ]);
        }

        return view('finance.index', compact(
            'pendingExpenses',
            'pendingTransfers',
            'invoices',
            'kpis',
            'branches'
        ));
    }

    /**
     * Approve Expense.
     */
    public function approveExpense(Expense $expense, Request $request): RedirectResponse|JsonResponse
    {
        $this->authorize('approve', $expense);

        $this->expenseService->approveExpense($expense, $request->user());

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => "Expense {$expense->expense_number} approved."]);
        }

        return redirect()->back()->with('toast_success', "Expense {$expense->expense_number} approved & cleared.");
    }

    /**
     * Reject Expense.
     */
    public function rejectExpense(Expense $expense, Request $request): RedirectResponse|JsonResponse
    {
        $this->authorize('approve', $expense);

        $request->validate(['reason' => ['required', 'string', 'max:500']]);
        $this->expenseService->rejectExpense($expense, $request->reason, $request->user());

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => "Expense {$expense->expense_number} rejected."]);
        }

        return redirect()->back()->with('toast_info', "Expense {$expense->expense_number} rejected.");
    }

    /**
     * Approve Transfer.
     */
    public function approveTransfer(Transfer $transfer, Request $request): RedirectResponse|JsonResponse
    {
        $this->authorize('approve', $transfer);

        $this->transferService->approveTransfer($transfer, $request->user());

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => "Transfer {$transfer->transfer_number} approved."]);
        }

        return redirect()->back()->with('toast_success', "Transfer {$transfer->transfer_number} approved & cash balance credited.");
    }

    /**
     * Reject Transfer.
     */
    public function rejectTransfer(Transfer $transfer, Request $request): RedirectResponse|JsonResponse
    {
        $this->authorize('approve', $transfer);

        $request->validate(['reason' => ['required', 'string', 'max:500']]);
        $this->transferService->rejectTransfer($transfer, $request->reason, $request->user());

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => "Transfer {$transfer->transfer_number} rejected."]);
        }

        return redirect()->back()->with('toast_info', "Transfer {$transfer->transfer_number} rejected.");
    }
}
