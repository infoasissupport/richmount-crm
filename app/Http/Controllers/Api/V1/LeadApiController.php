<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Lead\AssignEngineerRequest;
use App\Http\Requests\Lead\StoreLeadRequest;
use App\Http\Requests\Lead\UpdateLeadRequest;
use App\Models\Lead;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeadApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Lead::class);

        $leads = Lead::accessibleBy($request->user())
            ->with(['branch', 'assignedEngineer'])
            ->when($request->filled('branch_id'), fn($q) => $q->where('branch_id', $request->branch_id))
            ->when($request->filled('stage'), fn($q) => $q->where('stage', $request->stage))
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%' . $request->search . '%';
                $q->where(fn($sq) => $sq->where('company_name', 'like', $term)
                    ->orWhere('subject', 'like', $term)
                    ->orWhere('lead_number', 'like', $term));
            })
            ->latest()
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $leads,
        ]);
    }

    public function store(StoreLeadRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $validated['lead_number'] = Lead::nextLeadNumber();
        $validated['lead_date'] = $validated['lead_date'] ?? now()->toDateString();
        $validated['created_by_id'] = $request->user()->id;
        $validated['stage'] = 'new';
        $validated['status'] = 'New';
        $validated['stage_date'] = now();

        $lead = Lead::create($validated);

        $lead->activities()->create([
            'user_id' => $request->user()->id,
            'kind' => 'created',
            'text' => "Lead {$lead->lead_number} created via API",
        ]);

        AuditService::log('api_created', $lead, "Created lead {$lead->lead_number} via API");

        return response()->json([
            'success' => true,
            'message' => 'Lead created successfully.',
            'lead' => $lead->load(['branch', 'assignedEngineer']),
        ], 201);
    }

    public function show(Lead $lead): JsonResponse
    {
        $this->authorize('view', $lead);

        return response()->json([
            'success' => true,
            'lead' => $lead->load(['branch', 'assignedEngineer', 'quotations', 'attachments', 'activities.user']),
        ]);
    }

    public function update(UpdateLeadRequest $request, Lead $lead): JsonResponse
    {
        $this->authorize('update', $lead);

        $lead->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Lead updated successfully.',
            'lead' => $lead->fresh()->load(['branch', 'assignedEngineer']),
        ]);
    }

    public function assign(AssignEngineerRequest $request, Lead $lead): JsonResponse
    {
        $this->authorize('assign', $lead);

        $engineer = User::findOrFail($request->engineer_id);
        $lead->update(['assigned_engineer_id' => $engineer->id]);

        $lead->activities()->create([
            'user_id' => $request->user()->id,
            'kind' => 'assigned',
            'text' => "Lead assigned to {$engineer->name} via API",
        ]);

        return response()->json([
            'success' => true,
            'message' => "Lead assigned to {$engineer->name}.",
        ]);
    }
}
