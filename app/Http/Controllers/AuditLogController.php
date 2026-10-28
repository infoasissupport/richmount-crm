<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    /**
     * Display a listing of audit logs with search and filtering.
     */
    public function index(Request $request): View|JsonResponse
    {
        $this->authorize('viewAny', \App\Models\User::class);

        $logs = AuditLog::with('user')
            ->when($request->filled('action'), fn($q) => $q->where('action', $request->action))
            ->when($request->filled('user_id'), fn($q) => $q->where('user_id', $request->user_id))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%' . $request->search . '%';
                $q->where(fn($sq) => $sq->where('description', 'like', $term)
                    ->orWhere('entity_type', 'like', $term)
                    ->orWhere('ip_address', 'like', $term));
            })
            ->latest()
            ->paginate(25);

        if ($request->wantsJson()) {
            return response()->json($logs);
        }

        return view('audit.index', compact('logs'));
    }
}
