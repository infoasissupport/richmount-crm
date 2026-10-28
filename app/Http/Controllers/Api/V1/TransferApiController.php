<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Transfer\StoreTransferRequest;
use App\Models\Transfer;
use App\Services\TransferService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransferApiController extends Controller
{
    public function __construct(protected TransferService $transferService) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Transfer::class);

        $transfers = Transfer::accessibleBy($request->user())
            ->with(['fromUser', 'toUser', 'branch'])
            ->latest('date')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $transfers,
        ]);
    }

    public function store(StoreTransferRequest $request): JsonResponse
    {
        $transfer = $this->transferService->createTransfer($request->validated(), $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Transfer request created.',
            'transfer' => $transfer,
        ], 201);
    }
}
