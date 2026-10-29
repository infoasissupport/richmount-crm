<?php

namespace App\Services;

use App\Models\Quotation;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class PdfService
{
    /**
     * Generate quotation PDF according to branch format and output type.
     * $inline shows it in the browser (e.g. a preview popup) instead of downloading it.
     */
    public function generateQuotationPdf(Quotation $quotation, ?string $outputType = null, bool $inline = false): Response
    {
        $quotation->load(['lead', 'branch', 'preparedBy', 'lineItems']);
        $branchFormat = $quotation->branch->quote_format ?? 'format_1';
        $outputMode = $outputType ?? $quotation->output_type ?? 'tax';

        $view = "quotations.pdf.{$branchFormat}";
        if (!view()->exists($view)) {
            $view = 'quotations.pdf.format_1';
        }

        $pdf = Pdf::loadView($view, [
            'quote' => $quotation,
            'lead' => $quotation->lead,
            'branch' => $quotation->branch,
            'items' => $quotation->lineItems,
            'outputMode' => $outputMode,
        ]);

        $pdf->setPaper('a4', 'portrait');

        $fileName = "Quotation_{$quotation->quote_number}_{$outputMode}.pdf";

        return $inline ? $pdf->stream($fileName) : $pdf->download($fileName);
    }

    /**
     * Generate Vendor Purchase Order PDF.
     */
    public function generatePurchaseOrderPdf(\App\Models\PurchaseOrder $po): Response
    {
        $po->load(['project', 'branch', 'preparedBy', 'approvedBy', 'lineItems']);

        $pdf = Pdf::loadView('pdf.purchase_order', [
            'po' => $po,
            'project' => $po->project,
            'branch' => $po->branch,
            'items' => $po->lineItems,
        ]);

        $pdf->setPaper('a4', 'portrait');

        return $pdf->download("PO_{$po->po_number}.pdf");
    }

    /**
     * Generate Work Completion Certificate PDF.
     */
    public function generateWorkCompletionCertificatePdf(\App\Models\WorkCompletionCertificate $wcc): Response
    {
        $wcc->load(['project.branch', 'project.lead', 'engineer']);

        $pdf = Pdf::loadView('pdf.work_completion_certificate', [
            'wcc' => $wcc,
            'project' => $wcc->project,
            'branch' => $wcc->project->branch,
            'engineer' => $wcc->engineer,
        ]);

        $pdf->setPaper('a4', 'portrait');

        return $pdf->download("WCC_{$wcc->certificate_number}.pdf");
    }

    /**
     * Generate Official UAE FTA Compliant Tax Invoice PDF.
     */
    public function generateTaxInvoicePdf(\App\Models\Invoice $invoice): Response
    {
        $invoice->load(['project.branch', 'project.lead', 'lineItems', 'payments', 'createdBy']);

        $pdf = Pdf::loadView('pdf.tax_invoice', [
            'invoice' => $invoice,
            'project' => $invoice->project,
            'branch' => $invoice->branch,
            'items' => $invoice->lineItems,
            'payments' => $invoice->payments,
        ]);

        $pdf->setPaper('a4', 'portrait');

        return $pdf->download("Tax_Invoice_{$invoice->invoice_number}.pdf");
    }
}
