<?php

namespace App\Http\Controllers;

use App\Models\ZohoSyncLog;
use App\Services\ZohoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ZohoController extends Controller
{
    public function __construct(protected ZohoService $zohoService) {}

    /**
     * Display Zoho Books status, connection diagnostics, and sync logs.
     */
    public function index(Request $request): View|JsonResponse
    {
        $this->authorize('viewAny', ZohoSyncLog::class);

        $tab = $request->get('tab', 'all');
        $status = $this->zohoService->getConnectionStatus();

        $query = ZohoSyncLog::query()
            ->when($tab === 'failed', fn($q) => $q->where('status', 'failed'))
            ->when($tab === 'queued', fn($q) => $q->whereIn('status', ['queued', 'syncing']))
            ->when($tab === 'synced', fn($q) => $q->where('status', 'synced'))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%' . $request->search . '%';
                $q->where(function ($sq) use ($term) {
                    $sq->where('reference', 'like', $term)
                       ->orWhere('entity_type', 'like', $term)
                       ->orWhere('action', 'like', $term)
                       ->orWhere('message', 'like', $term);
                });
            })
            ->latest('id');

        $logs = $query->paginate(15)->withQueryString();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'status' => $status,
                'logs' => $logs,
            ]);
        }

        return view('zoho.index', compact('status', 'logs', 'tab'));
    }

    /**
     * Trigger immediate synchronization of all pending/failed records.
     */
    public function syncNow(Request $request): RedirectResponse|JsonResponse
    {
        $this->authorize('sync', ZohoSyncLog::class);

        $syncedCount = $this->zohoService->syncAllPending();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Zoho Books sync completed. {$syncedCount} item(s) processed.",
            ]);
        }

        return redirect()->back()->with('toast_success', "Zoho Books sync completed. {$syncedCount} item(s) processed.");
    }

    /**
     * Retry a specific failed sync log item.
     */
    public function retry(ZohoSyncLog $log, Request $request): RedirectResponse|JsonResponse
    {
        $this->authorize('sync', ZohoSyncLog::class);

        $this->zohoService->retrySync($log);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Item {$log->reference} re-queued and synchronized with Zoho Books.",
            ]);
        }

        return redirect()->back()->with('toast_success', "Item {$log->reference} re-queued and synchronized.");
    }
}
