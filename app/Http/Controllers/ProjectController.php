<?php

namespace App\Http\Controllers;

use App\Http\Requests\Project\CompleteWorkRequest;
use App\Http\Requests\Project\RecordClientPoRequest;
use App\Http\Requests\Project\StoreProjectRequest;
use App\Models\Branch;
use App\Models\Lead;
use App\Models\Project;
use App\Models\Quotation;
use App\Models\User;
use App\Services\ProjectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProjectController extends Controller
{
    public function __construct(protected ProjectService $projectService) {}

    /**
     * Display a listing of projects and activation board.
     */
    public function index(Request $request): View|JsonResponse
    {
        $this->authorize('viewAny', Project::class);
        $user = $request->user();

        $tab = $request->get('tab', 'all');

        // 1. Projects Query
        $projectsQuery = Project::accessibleBy($user)
            ->with(['branch', 'assignedEngineer', 'quotation', 'lead'])
            ->when($tab !== 'all', fn($q) => $q->where('status', $tab))
            ->when($request->filled('branch_id'), fn($q) => $q->where('branch_id', $request->branch_id))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%' . $request->search . '%';
                $q->where(function ($sq) use ($term) {
                    $sq->where('project_number', 'like', $term)
                       ->orWhere('name', 'like', $term)
                       ->orWhere('client_name', 'like', $term)
                       ->orWhere('client_po_number', 'like', $term);
                });
            })
            ->latest();

        $projects = $projectsQuery->paginate(15);

        // 2. Awaiting Activation:
        // (a) Awarded quotes without project
        $awardedWithoutProject = Quotation::accessibleBy($user)
            ->where('status', 'awarded')
            ->whereDoesntHave('project')
            ->with(['lead', 'branch'])
            ->get();

        // (b) Projects waiting for PO
        $projectsAwaitingPo = Project::accessibleBy($user)
            ->where('status', 'popending')
            ->with(['lead', 'quotation', 'branch'])
            ->get();

        // 3. KPIs
        $baseKpi = Project::accessibleBy($user);
        $kpis = [
            'active_projects' => (clone $baseKpi)->whereIn('status', ['open', 'inprogress'])->count(),
            'awaiting_activation' => $awardedWithoutProject->count() + $projectsAwaitingPo->count(),
            'completed_projects' => (clone $baseKpi)->whereIn('status', ['completed', 'closed'])->count(),
            'total_invoiced' => (clone $baseKpi)->with('invoices')->get()->sum(fn($p) => $p->totalInvoiced()),
        ];

        $branches = Branch::active()->get();
        $engineers = User::where('role_name', 'engineer')->get();

        if ($request->wantsJson()) {
            return response()->json([
                'kpis' => $kpis,
                'projects' => $projects,
                'awaiting_po' => $projectsAwaitingPo,
                'awarded_uninitiated' => $awardedWithoutProject,
            ]);
        }

        return view('projects.index', compact(
            'projects',
            'awardedWithoutProject',
            'projectsAwaitingPo',
            'kpis',
            'branches',
            'engineers',
            'tab'
        ));
    }

    /**
     * Store newly created project.
     */
    public function store(StoreProjectRequest $request): RedirectResponse|JsonResponse
    {
        $project = $this->projectService->createProject($request->validated(), $request->user());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Project {$project->project_number} created.",
                'project' => $project,
            ], 201);
        }

        return redirect()->route('projects.show', $project)->with('toast_success', "Project {$project->project_number} created.");
    }

    /**
     * Create project directly from an awarded quotation.
     */
    public function createFromQuote(Quotation $quotation, Request $request): RedirectResponse|JsonResponse
    {
        $this->authorize('create', Project::class);

        $project = $this->projectService->createFromAwardedQuotation($quotation, $request->user());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Project {$project->project_number} initiated from quotation {$quotation->quote_number}.",
                'project' => $project,
            ], 201);
        }

        return redirect()->route('projects.show', $project)->with('toast_success', "Project {$project->project_number} initiated.");
    }

    /**
     * Display the specified project.
     */
    public function show(Project $project): View|JsonResponse
    {
        $this->authorize('view', $project);

        $project->load([
            'branch',
            'assignedEngineer',
            'lead',
            'quotation',
            'documents.uploadedBy',
            'activities.user',
            'purchaseOrders.preparedBy',
            'workCompletionCertificates.engineer',
            'invoices.lineItems',
            'invoices.payments',
            'expenses.user',
        ]);

        if (request()->wantsJson()) {
            return response()->json($project);
        }

        return view('projects.show', compact('project'));
    }

    /**
     * Record client PO and transition project to 'open'.
     */
    public function recordPo(RecordClientPoRequest $request, Project $project): RedirectResponse|JsonResponse
    {
        $this->authorize('open', $project);

        $data = $request->validated();
        if ($request->hasFile('po_file')) {
            $data['client_po_file'] = $request->file('po_file')->store('project_po', 'public');
        }

        $this->projectService->recordClientPo($project, $data, $request->user());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Client PO recorded. Project {$project->project_number} is now Open.",
            ]);
        }

        return redirect()->route('projects.show', $project)->with('toast_success', "Client PO recorded. Project is now Open.");
    }

    /**
     * Start work (transition to inprogress).
     */
    public function startWork(Project $project, Request $request): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $project);

        $this->projectService->updateStatus($project, 'inprogress', null, $request->user());

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => "Project {$project->project_number} is now In Progress."]);
        }

        return redirect()->back()->with('toast_success', "Project {$project->project_number} is now In Progress.");
    }

    /**
     * Put project on hold.
     */
    public function putOnHold(Project $project, Request $request): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $project);

        $request->validate(['reason' => ['required', 'string', 'max:500']]);
        $this->projectService->updateStatus($project, 'onhold', $request->reason, $request->user());

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => "Project {$project->project_number} put on hold."]);
        }

        return redirect()->back()->with('toast_info', "Project {$project->project_number} put on hold.");
    }

    /**
     * Resume project work.
     */
    public function resumeWork(Project $project, Request $request): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $project);

        $this->projectService->updateStatus($project, 'inprogress', null, $request->user());

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => "Work resumed on {$project->project_number}."]);
        }

        return redirect()->back()->with('toast_success', "Work resumed on {$project->project_number}.");
    }

    /**
     * Complete work and issue Work Completion Certificate.
     */
    public function completeWork(CompleteWorkRequest $request, Project $project): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $project);

        $this->projectService->completeWork($project, $request->validated(), $request->user());

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => "Work completed report recorded."]);
        }

        return redirect()->route('projects.show', $project)->with('toast_success', "Work Completion Certificate generated. Project ready for billing.");
    }
}
