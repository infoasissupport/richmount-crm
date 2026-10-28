<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Invoice\RecordPaymentRequest;
use App\Http\Requests\Invoice\StoreInvoiceRequest;
use App\Models\Invoice;
use App\Models\Project;
use App\Services\InvoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvoiceApiController extends Controller
{
    public function __construct(protected InvoiceService $invoiceService) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Invoice::class);

        $invoices = Invoice::accessibleBy($request->user())
            ->with(['project', 'branch'])
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $invoices,
        ]);
    }

    public function store(StoreInvoiceRequest $request, Project $project): JsonResponse
    {
        $this->authorize('create', Invoice::class);

        $invoice = $this->invoiceService->createInvoice($project, $request->validated(), $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Invoice created successfully.',
            'invoice' => $invoice->load('lineItems'),
        ], 201);
    }

    public function show(Invoice $invoice): JsonResponse
    {
        $this->authorize('view', $invoice);

        return response()->json([
            'success' => true,
            'invoice' => $invoice->load(['project', 'branch', 'lineItems', 'payments']),
        ]);
    }

    public function recordPayment(RecordPaymentRequest $request, Invoice $invoice): JsonResponse
    {
        $this->authorize('pay', $invoice);

        $payment = $this->invoiceService->recordPayment($invoice, $request->validated(), $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Payment recorded.',
            'payment' => $payment,
            'invoice' => $invoice->fresh(),
        ]);
    }
}
