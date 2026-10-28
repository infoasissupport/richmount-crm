<?php

namespace App\Http\Controllers;

use App\Http\Requests\Quotation\AutosaveQuotationRequest;
use App\Http\Requests\Quotation\StoreQuotationRequest;
use App\Models\Branch;
use App\Models\Lead;
use App\Models\Quotation;
use App\Models\User;
use App\Services\PdfService;
use App\Services\QuotationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class QuotationController extends Controller
{
    public function __construct(
        protected QuotationService $quotationService,
        protected PdfService $pdfService
    ) {}

    /**
     * Display a listing of quotations with tabbed status filtering.
     */
    public function index(Request $request): View|JsonResponse
    {
        $this->authorize('viewAny', Quotation::class);
        $user = $request->user();

        $tab = $request->get('tab', 'all');

        $query = Quotation::accessibleBy($user)
            ->with(['lead.assignedEngineer', 'branch', 'preparedBy', 'project'])
            ->when($tab === 'awaiting_project', fn($q) => $q->where('status', 'awarded')->whereDoesntHave('project'))
            ->when($tab !== 'all' && $tab !== 'awaiting_project', fn($q) => $q->where('status', $tab))
            ->when($request->filled('branch_id'), fn($q) => $q->where('branch_id', $request->branch_id))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%' . $request->search . '%';
                $q->where(function ($sq) use ($term) {
                    $sq->where('quote_number', 'like', $term)
                       ->orWhereHas('lead', fn($lq) => $lq->where('company_name', 'like', $term)->orWhere('subject', 'like', $term))
                       ->orWhereHas('preparedBy', fn($pq) => $pq->where('name', 'like', $term));
                });
            })
            ->latest();

        if ($request->get('export') === 'csv') {
            $exportQuotes = (clone $query)->get();
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="quotations_' . date('Y-m-d') . '.csv"',
            ];
            $callback = function () use ($exportQuotes) {
                $handle = fopen('php://output', 'w');
                fputcsv($handle, ['Quote Number', 'Client', 'Project Subject', 'Branch', 'Format', 'Engineer', 'Grand Total (AED)', 'Status', 'Date']);
                foreach ($exportQuotes as $q) {
                    fputcsv($handle, [
                        $q->quote_number,
                        $q->lead?->company_name ?? '',
                        $q->lead?->subject ?? '',
                        $q->branch?->short_name ?? $q->branch?->name ?? '',
                        $q->branch?->quote_format ?? 'Quote Format 1',
                        $q->lead?->assignedEngineer?->name ?? ($q->preparedBy?->name ?? 'Unassigned'),
                        $q->grand_total,
                        $q->status,
                        $q->quote_date?->format('Y-m-d') ?? '',
                    ]);
                }
                fclose($handle);
            };
            return response()->stream($callback, 200, $headers);
        }

        $baseKpiQuery = Quotation::accessibleBy($user);
        $kpis = [
            'total' => (clone $baseKpiQuery)->count(),
            'drafts' => (clone $baseKpiQuery)->where('status', 'draft')->count(),
            'pending' => (clone $baseKpiQuery)->where('status', 'pending')->count(),
            'awarded_value' => (clone $baseKpiQuery)->where('status', 'awarded')->sum('grand_total'),
        ];

        $tabCounts = [
            'all' => (clone $baseKpiQuery)->count(),
            'draft' => (clone $baseKpiQuery)->where('status', 'draft')->count(),
            'pending' => (clone $baseKpiQuery)->where('status', 'pending')->count(),
            'approved' => (clone $baseKpiQuery)->where('status', 'approved')->count(),
            'sent' => (clone $baseKpiQuery)->where('status', 'sent')->count(),
            'awarded' => (clone $baseKpiQuery)->where('status', 'awarded')->count(),
            'awaiting_project' => (clone $baseKpiQuery)->where('status', 'awarded')->whereDoesntHave('project')->count(),
        ];

        $quotations = $query->paginate(15);
        $branches = Branch::active()->get();
        $availableLeads = Lead::accessibleBy($user)
            ->whereNotIn('stage', ['awarded', 'lost'])
            ->latest()
            ->take(50)
            ->get();

        if ($request->wantsJson()) {
            return response()->json([
                'kpis' => $kpis,
                'quotations' => $quotations,
            ]);
        }

        return view('quotations.index', compact('quotations', 'kpis', 'branches', 'tab', 'availableLeads', 'tabCounts'));
    }

    /**
     * Quotation Editor / Builder Workspace.
     */
    public function builder(Request $request): View|RedirectResponse
    {
        $this->authorize('create', Quotation::class);
        $user = $request->user();

        $quote = null;
        $lead = null;

        if ($request->filled('id')) {
            $quote = Quotation::with(['lead', 'branch', 'lineItems', 'histories.user', 'attachments', 'project'])->findOrFail($request->id);
            $this->authorize('view', $quote);
            $lead = $quote->lead;
        } elseif ($request->filled('lead')) {
            $lead = Lead::accessibleBy($user)->with('branch')->findOrFail($request->lead);
        } else {
            // Find first available lead
            $lead = Lead::accessibleBy($user)->where('stage', 'new')->latest()->first()
                ?? Lead::accessibleBy($user)->latest()->first();
        }

        if (!$lead) {
            return redirect()->route('leads.index', ['new' => 1])
                ->with('toast_info', 'Please create a lead first before opening the quotation builder.');
        }

        // Available leads for switching if drafting a new quote or cloning
        $availableLeads = Lead::accessibleBy($user)
            ->whereNotIn('stage', ['awarded', 'lost'])
            ->latest()
            ->take(50)
            ->get();

        // Open workspace tabs (quotes in draft/pending)
        $openQuotes = Quotation::accessibleBy($user)
            ->whereIn('status', ['draft', 'pending', 'approved'])
            ->latest()
            ->take(8)
            ->get();

        return view('quotations.builder', compact('quote', 'lead', 'openQuotes', 'availableLeads'));
    }

    /**
     * Store new quotation.
     */
    public function store(StoreQuotationRequest $request): RedirectResponse|JsonResponse
    {
        $lead = Lead::findOrFail($request->lead_id);
        $quote = $this->quotationService->createDraft($lead, $request->validated(), $request->user());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Quotation {$quote->quote_number} created.",
                'quotation' => $quote->load(['lineItems', 'lead']),
            ], 201);
        }

        return redirect()->route('quotations.builder', ['id' => $quote->id])
            ->with('toast_success', "Quotation {$quote->quote_number} created as draft.");
    }

    /**
     * Show quotation detail (redirects to builder workspace).
     */
    public function show(Quotation $quotation): View|JsonResponse|RedirectResponse
    {
        $this->authorize('view', $quotation);

        $quotation->load(['lead', 'branch', 'preparedBy', 'approvedBy', 'lineItems', 'attachments', 'histories.user']);

        if (request()->wantsJson()) {
            return response()->json($quotation);
        }

        return redirect()->route('quotations.builder', ['id' => $quotation->id]);
    }

    /**
     * Update quotation.
     */
    public function update(StoreQuotationRequest $request, Quotation $quotation): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $quotation);

        $quote = $this->quotationService->autosaveDraft($quotation, $request->validated());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Quotation {$quote->quote_number} updated.",
                'quotation' => $quote->fresh()->load('lineItems'),
            ]);
        }

        return redirect()->route('quotations.builder', ['id' => $quote->id])
            ->with('toast_success', "Quotation {$quote->quote_number} saved.");
    }

    /**
     * Auto-save draft every 30s.
     */
    public function autosave(AutosaveQuotationRequest $request, Quotation $quotation): JsonResponse
    {
        $this->authorize('update', $quotation);

        $quote = $this->quotationService->autosaveDraft($quotation, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Saved',
            'saved_at' => $quote->auto_saved_at->format('h:i:s A'),
            'subtotal' => $quote->subtotal,
            'discount' => $quote->discount_amount,
            'taxable' => $quote->taxable_amount,
            'vat' => $quote->vat_amount,
            'grand_total' => $quote->grand_total,
        ]);
    }

    /**
     * Clone quotation into new draft.
     */
    public function clone(Quotation $quotation, Request $request): RedirectResponse|JsonResponse
    {
        $this->authorize('create', Quotation::class);

        $targetLeadId = $request->input('lead_id');
        $clone = $this->quotationService->cloneQuote($quotation, $request->user(), $targetLeadId ? (int)$targetLeadId : null);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Quotation cloned as {$clone->quote_number}.",
                'quotation' => $clone->load('lineItems'),
            ], 201);
        }

        return redirect()->route('quotations.builder', ['id' => $clone->id])
            ->with('toast_success', "Quotation cloned into new draft {$clone->quote_number}.");
    }

    /**
     * Submit for approval.
     */
    public function submit(Quotation $quotation, Request $request): RedirectResponse|JsonResponse
    {
        $this->authorize('submit', $quotation);

        $this->quotationService->submitForApproval($quotation, $request->user());

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => "Quotation {$quotation->quote_number} submitted for approval."]);
        }

        return redirect()->back()->with('toast_success', "Quotation {$quotation->quote_number} submitted for approval.");
    }

    /**
     * Approve quotation.
     */
    public function approve(Quotation $quotation, Request $request): RedirectResponse|JsonResponse
    {
        $this->authorize('approve', $quotation);

        $this->quotationService->approve($quotation, $request->user());

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => "Quotation {$quotation->quote_number} approved."]);
        }

        return redirect()->back()->with('toast_success', "Quotation {$quotation->quote_number} approved.");
    }

    /**
     * Reject quotation.
     */
    public function reject(Quotation $quotation, Request $request): RedirectResponse|JsonResponse
    {
        $this->authorize('reject', $quotation);

        $request->validate(['reason' => ['required', 'string', 'max:500']]);
        $this->quotationService->reject($quotation, $request->reason, $request->user());

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => "Quotation {$quotation->quote_number} rejected."]);
        }

        return redirect()->back()->with('toast_info', "Quotation {$quotation->quote_number} returned for revision.");
    }

    /**
     * Mark quotation as Sent to customer.
     */
    public function send(Quotation $quotation, Request $request): RedirectResponse|JsonResponse
    {
        $this->authorize('send', $quotation);

        $this->quotationService->sendToCustomer($quotation, $request->user(), $request->all());

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => "Quotation {$quotation->quote_number} sent to customer."]);
        }

        return redirect()->back()->with('toast_success', "Quotation {$quotation->quote_number} sent to customer.");
    }

    /**
     * Mark quotation as Awarded.
     */
    public function award(Quotation $quotation, Request $request): RedirectResponse|JsonResponse
    {
        $this->authorize('award', $quotation);

        $this->quotationService->markAwarded($quotation, $request->user());

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => "Quotation {$quotation->quote_number} marked as Awarded."]);
        }

        return redirect()->back()->with('toast_success', "Quotation {$quotation->quote_number} marked as Awarded.");
    }

    /**
     * Download Branch-specific quotation PDF.
     */
    public function downloadPdf(Quotation $quotation, Request $request): Response
    {
        $this->authorize('view', $quotation);

        $outputType = $request->get('output', $quotation->output_type);

        return $this->pdfService->generateQuotationPdf($quotation, $outputType);
    }
}
