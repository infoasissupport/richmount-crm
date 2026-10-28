<?php

namespace App\Http\Controllers;

use App\Models\WorkCompletionCertificate;
use App\Services\PdfService;
use Illuminate\Http\Response;

class WorkCompletionCertificateController extends Controller
{
    public function __construct(protected PdfService $pdfService) {}

    /**
     * Download Work Completion Certificate PDF.
     */
    public function downloadPdf(WorkCompletionCertificate $wcc): Response
    {
        $this->authorize('view', $wcc->project);

        return $this->pdfService->generateWorkCompletionCertificatePdf($wcc);
    }
}
