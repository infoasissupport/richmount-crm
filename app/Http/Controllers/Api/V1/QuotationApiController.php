<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Quotation\AutosaveQuotationRequest;
use App\Http\Requests\Quotation\StoreQuotationRequest;
use App\Models\Lead;
use App\Models\Quotation;
use App\Services\QuotationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuotationApiController extends Controller
{
    public function __construct(protected QuotationService $quotationService) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Quotation::class);

        $quotations = Quotation::accessibleBy($request->user())
            ->with(['lead', 'branch', 'preparedBy'])
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->when($request->filled('branch_id'), fn($q) => $q->where('branch_id', $request->branch_id))
            ->latest()
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $quotations,
        ]);
    }

    public function store(StoreQuotationRequest $request): JsonResponse
    {
        $lead = Lead::findOrFail($request->lead_id);
        $quote = $this->quotationService->createDraft($lead, $request->validated(), $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Quotation created.',
            'quotation' => $quote->load('lineItems'),
        ], 201);
    }

    public function show(Quotation $quotation): JsonResponse
    {
        $this->authorize('view', $quotation);

        return response()->json([
            'success' => true,
            'quotation' => $quotation->load(['lead', 'branch', 'preparedBy', 'approvedBy', 'lineItems', 'histories.user']),
        ]);
    }

    public function autosave(AutosaveQuotationRequest $request, Quotation $quotation): JsonResponse
    {
        $this->authorize('update', $quotation);

        $quote = $this->quotationService->autosaveDraft($quotation, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Draft autosaved.',
            'auto_saved_at' => $quote->auto_saved_at->toIso8601String(),
            'totals' => [
                'subtotal' => (float) $quote->subtotal,
                'discount' => (float) $quote->discount_amount,
                'taxable' => (float) $quote->taxable_amount,
                'vat' => (float) $quote->vat_amount,
                'grand_total' => (float) $quote->grand_total,
            ],
        ]);
    }

    public function clone(Quotation $quotation, Request $request): JsonResponse
    {
        $this->authorize('create', Quotation::class);

        $clone = $this->quotationService->cloneQuote($quotation, $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Quotation cloned successfully.',
            'quotation' => $clone->load('lineItems'),
        ], 201);
    }

    public function submit(Quotation $quotation, Request $request): JsonResponse
    {
        $this->authorize('submit', $quotation);

        $this->quotationService->submitForApproval($quotation, $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Quotation submitted for approval.',
        ]);
    }

    public function approve(Quotation $quotation, Request $request): JsonResponse
    {
        $this->authorize('approve', $quotation);

        $this->quotationService->approve($quotation, $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Quotation approved.',
        ]);
    }

    public function reject(Quotation $quotation, Request $request): JsonResponse
    {
        $this->authorize('reject', $quotation);

        $request->validate(['reason' => ['required', 'string', 'max:500']]);
        $this->quotationService->reject($quotation, $request->reason, $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Quotation rejected.',
        ]);
    }

    public function send(Quotation $quotation, Request $request): JsonResponse
    {
        $this->authorize('send', $quotation);

        $this->quotationService->sendToCustomer($quotation, $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Quotation marked as Sent to customer.',
        ]);
    }

    public function award(Quotation $quotation, Request $request): JsonResponse
    {
        $this->authorize('award', $quotation);

        $this->quotationService->markAwarded($quotation, $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Quotation marked as Awarded.',
        ]);
    }

    public function reopen(Quotation $quotation, Request $request): JsonResponse
    {
        $this->authorize('reopen', $quotation);

        $request->validate([
            'reason' => ['required', 'string', 'max:500'],
            'engineer_id' => ['nullable', 'exists:users,id'],
        ]);

        $quote = $this->quotationService->reopenQuote(
            $quotation,
            $request->user(),
            $request->input('reason'),
            $request->filled('engineer_id') ? (int)$request->input('engineer_id') : null
        );

        return response()->json([
            'success' => true,
            'message' => "Quotation {$quote->quote_number} moved back to Draft.",
            'quotation' => $quote->load('lineItems'),
        ]);
    }
}
