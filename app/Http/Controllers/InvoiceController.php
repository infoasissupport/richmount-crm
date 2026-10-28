<?php

namespace App\Http\Controllers;

use App\Http\Requests\Invoice\RecordPaymentRequest;
use App\Http\Requests\Invoice\StoreInvoiceRequest;
use App\Models\Invoice;
use App\Models\Project;
use App\Services\InvoiceService;
use App\Services\PdfService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function __construct(
        protected InvoiceService $invoiceService,
        protected PdfService $pdfService
    ) {}

    /**
     * Store new progress or final invoice for a project.
     */
    public function store(StoreInvoiceRequest $request, Project $project): RedirectResponse|JsonResponse
    {
        $this->authorize('create', Invoice::class);

        $invoice = $this->invoiceService->createInvoice($project, $request->validated(), $request->user());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Tax Invoice {$invoice->invoice_number} generated.",
                'invoice' => $invoice->load('lineItems'),
            ], 201);
        }

        return redirect()->route('projects.show', $project)->with('toast_success', "Tax Invoice {$invoice->invoice_number} generated.");
    }

    /**
     * Record client payment against an invoice.
     */
    public function recordPayment(RecordPaymentRequest $request, Invoice $invoice): RedirectResponse|JsonResponse
    {
        $this->authorize('pay', $invoice);

        $payment = $this->invoiceService->recordPayment($invoice, $request->validated(), $request->user());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Payment of AED {$payment->amount} recorded.",
                'invoice' => $invoice->fresh(),
            ]);
        }

        return redirect()->back()->with('toast_success', "Payment of AED {$payment->amount} recorded.");
    }

    /**
     * Download UAE FTA Tax Invoice PDF.
     */
    public function downloadPdf(Invoice $invoice): Response
    {
        $this->authorize('view', $invoice);

        return $this->pdfService->generateTaxInvoicePdf($invoice);
    }
}
