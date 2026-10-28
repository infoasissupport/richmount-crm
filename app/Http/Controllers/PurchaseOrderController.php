<?php

namespace App\Http\Controllers;

use App\Http\Requests\PurchaseOrder\StorePurchaseOrderRequest;
use App\Models\Project;
use App\Models\PurchaseOrder;
use App\Services\PdfService;
use App\Services\PurchaseOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;

class PurchaseOrderController extends Controller
{
    public function __construct(
        protected PurchaseOrderService $poService,
        protected PdfService $pdfService
    ) {}

    /**
     * Store new vendor purchase order for a project.
     */
    public function store(StorePurchaseOrderRequest $request, Project $project): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $project);

        $po = $this->poService->createPo($project, $request->validated(), $request->user());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Purchase Order {$po->po_number} created.",
                'purchase_order' => $po->load('lineItems'),
            ], 201);
        }

        return redirect()->route('projects.show', $project)->with('toast_success', "Purchase Order {$po->po_number} created.");
    }

    /**
     * Download PO PDF.
     */
    public function downloadPdf(PurchaseOrder $purchaseOrder): Response
    {
        $this->authorize('view', $purchaseOrder->project);

        return $this->pdfService->generatePurchaseOrderPdf($purchaseOrder);
    }
}
