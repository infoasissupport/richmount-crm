<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Expense\StoreExpenseRequest;
use App\Models\Expense;
use App\Services\ExpenseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExpenseApiController extends Controller
{
    public function __construct(protected ExpenseService $expenseService) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Expense::class);

        $expenses = Expense::accessibleBy($request->user())
            ->with(['project', 'branch', 'user'])
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->latest('date')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $expenses,
        ]);
    }

    public function store(StoreExpenseRequest $request): JsonResponse
    {
        $data = $request->validated();
        if ($request->hasFile('receipt')) {
            $data['receipt_file'] = $request->file('receipt')->store('receipts', 'public');
        }

        $expense = $this->expenseService->createExpense($data, $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Expense recorded successfully.',
            'expense' => $expense,
        ], 201);
    }

    public function show(Expense $expense): JsonResponse
    {
        $this->authorize('view', $expense);

        return response()->json([
            'success' => true,
            'expense' => $expense->load(['project', 'branch', 'user', 'approvedBy']),
        ]);
    }
}
