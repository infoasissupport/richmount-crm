<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Project\CompleteWorkRequest;
use App\Http\Requests\Project\RecordClientPoRequest;
use App\Http\Requests\Project\StoreProjectRequest;
use App\Models\Project;
use App\Services\ProjectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectApiController extends Controller
{
    public function __construct(protected ProjectService $projectService) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Project::class);

        $projects = Project::accessibleBy($request->user())
            ->with(['branch', 'assignedEngineer'])
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->when($request->filled('branch_id'), fn($q) => $q->where('branch_id', $request->branch_id))
            ->latest()
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $projects,
        ]);
    }

    public function store(StoreProjectRequest $request): JsonResponse
    {
        $project = $this->projectService->createProject($request->validated(), $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Project created successfully.',
            'project' => $project,
        ], 201);
    }

    public function show(Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        return response()->json([
            'success' => true,
            'project' => $project->load([
                'branch',
                'assignedEngineer',
                'lead',
                'quotation',
                'documents',
                'activities.user',
                'purchaseOrders',
                'workCompletionCertificates',
                'invoices',
            ]),
        ]);
    }

    public function recordPo(RecordClientPoRequest $request, Project $project): JsonResponse
    {
        $this->authorize('open', $project);

        $data = $request->validated();
        if ($request->hasFile('po_file')) {
            $data['client_po_file'] = $request->file('po_file')->store('project_po', 'public');
        }

        $this->projectService->recordClientPo($project, $data, $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Client PO recorded.',
            'project' => $project->fresh(),
        ]);
    }

    public function completeWork(CompleteWorkRequest $request, Project $project): JsonResponse
    {
        $this->authorize('update', $project);

        $this->projectService->completeWork($project, $request->validated(), $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Work completion recorded.',
            'project' => $project->fresh()->load('latestWorkCompletionCertificate'),
        ]);
    }
}
