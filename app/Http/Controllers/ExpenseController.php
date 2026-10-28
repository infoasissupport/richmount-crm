<?php

namespace App\Http\Controllers;

use App\Http\Requests\Expense\StoreExpenseRequest;
use App\Models\Branch;
use App\Models\Expense;
use App\Models\InvoicePayment;
use App\Models\Project;
use App\Models\Transfer;
use App\Models\User;
use App\Services\ExpenseService;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExpenseController extends Controller
{
    public function __construct(
        protected ExpenseService $expenseService,
        protected ReportService $reportService
    ) {}

    /**
     * Display a listing of expenses, cash in hand, transfers, and payments.
     */
    public function index(Request $request): View|JsonResponse
    {
        $this->authorize('viewAny', Expense::class);
        $user = $request->user();

        $tab = $request->get('tab', 'all');

        // 1. Expenses Query
        $expensesQuery = Expense::accessibleBy($user)
            ->with(['project', 'branch', 'user'])
            ->when($tab === 'cash', fn($q) => $q->where('type', 'cash'))
            ->when($tab === 'card', fn($q) => $q->where('type', 'card'))
            ->when(in_array($tab, ['pending', 'approved', 'rejected']), fn($q) => $q->where('status', $tab))
            ->when($request->filled('branch_id'), fn($q) => $q->where('branch_id', $request->branch_id))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%' . $request->search . '%';
                $q->where(function ($sq) use ($term) {
                    $sq->where('expense_number', 'like', $term)
                       ->orWhere('vendor_name', 'like', $term)
                       ->orWhere('description', 'like', $term)
                       ->orWhereHas('user', fn($uq) => $uq->where('name', 'like', $term));
                });
            })
            ->latest('date')
            ->latest('id');

        $expenses = $expensesQuery->paginate(15);

        // 2. Cash in Hand by Engineer
        $engineers = User::where('role_name', 'engineer')
            ->when(!$user->all_branches && !$user->isSuperAdmin() && !$user->isAccountant(), fn($q) => $q->where('id', $user->id))
            ->with('branch')
            ->get();

        // 3. Internal Transfers
        $transfers = Transfer::accessibleBy($user)
            ->with(['fromUser', 'toUser', 'branch'])
            ->latest('date')
            ->take(15)
            ->get();

        // 4. Customer Payments
        $payments = InvoicePayment::with(['invoice.project', 'recordedBy'])
            ->latest('payment_date')
            ->take(10)
            ->get();

        // 5. KPIs
        $baseExpense = Expense::accessibleBy($user);
        $kpis = [
            'cash_in_hand' => $engineers->sum('cash_balance'),
            'cash_expense' => (clone $baseExpense)->where('type', 'cash')->where('status', 'approved')->sum('grand_total'),
            'card_expense' => (clone $baseExpense)->where('type', 'card')->where('status', 'approved')->sum('grand_total'),
            'transfers' => Transfer::accessibleBy($user)->where('status', 'approved')->sum('amount'),
        ];

        $branches = Branch::active()->get();
        $openProjects = Project::accessibleBy($user)->whereIn('status', ['open', 'inprogress'])->get();

        if ($request->wantsJson()) {
            return response()->json([
                'kpis' => $kpis,
                'expenses' => $expenses,
                'engineers' => $engineers,
                'transfers' => $transfers,
            ]);
        }

        return view('expenses.index', compact(
            'expenses',
            'engineers',
            'transfers',
            'payments',
            'kpis',
            'branches',
            'openProjects',
            'tab'
        ));
    }

    /**
     * Store new expense (with receipt upload).
     */
    public function store(StoreExpenseRequest $request): RedirectResponse|JsonResponse
    {
        $data = $request->validated();

        if ($request->hasFile('receipt')) {
            $data['receipt_file'] = $request->file('receipt')->store('receipts', 'public');
            // Simulated OCR parsing
            $data['ocr_data'] = [
                'merchant' => $data['vendor_name'] ?? 'Vendor',
                'amount' => $data['subtotal'],
                'scanned_at' => now()->toIso8601String(),
            ];
        }

        $expense = $this->expenseService->createExpense($data, $request->user());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Expense {$expense->expense_number} recorded successfully.",
                'expense' => $expense,
            ], 201);
        }

        return redirect()->back()->with('toast_success', "Expense {$expense->expense_number} recorded.");
    }

    /**
     * Get Cash Ledger for an engineer (JSON).
     */
    public function ledger(User $user): JsonResponse
    {
        $ledger = $this->reportService->engineerCashLedger($user);

        return response()->json([
            'success' => true,
            'ledger' => $ledger,
        ]);
    }
}
