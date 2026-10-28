<?php

namespace App\Http\Controllers;

use App\Http\Requests\Transfer\StoreTransferRequest;
use App\Http\Requests\Transfer\UpdateTransferRequest;
use App\Models\Transfer;
use App\Services\TransferService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class TransferController extends Controller
{
    public function __construct(protected TransferService $transferService) {}

    /**
     * Store new internal transfer request.
     */
    public function store(StoreTransferRequest $request): RedirectResponse|JsonResponse
    {
        $transfer = $this->transferService->createTransfer($request->validated(), $request->user());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Transfer {$transfer->transfer_number} submitted.",
                'transfer' => $transfer,
            ], 201);
        }

        return redirect()->back()->with('toast_success', "Transfer {$transfer->transfer_number} submitted.");
    }

    /**
     * Update submitted transfer (Accountant only).
     */
    public function update(UpdateTransferRequest $request, Transfer $transfer): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $transfer);

        $this->transferService->updateTransfer($transfer, $request->validated(), $request->user());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Transfer {$transfer->transfer_number} updated.",
                'transfer' => $transfer->fresh(),
            ]);
        }

        return redirect()->back()->with('toast_success', "Transfer {$transfer->transfer_number} updated.");
    }
}
