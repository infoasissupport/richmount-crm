<?php

namespace App\Http\Controllers;

use App\Http\Requests\Project\CompleteWorkRequest;
use App\Http\Requests\Project\RecordClientPoRequest;
use App\Http\Requests\Project\StoreProjectRequest;
use App\Models\Branch;
use App\Models\Project;
use App\Models\Quotation;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\AuditService;
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
            ->when($tab !== 'all', fn ($q) => $q->where('status', $tab))
            ->when($request->filled('branch_id'), fn ($q) => $q->where('branch_id', $request->branch_id))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.$request->search.'%';
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
        $projectsAwaitingPoQuery = Project::accessibleBy($user)
            ->where('status', 'popending')
            ->with(['lead', 'quotation', 'branch'])
            ->latest();

        if ($request->wantsJson()) {
            $projectsAwaitingPo = $projectsAwaitingPoQuery->get();
            $projectsAwaitingPoCount = $projectsAwaitingPo->count();
        } else {
            $projectsAwaitingPo = $projectsAwaitingPoQuery->paginate(15, ['*'], 'awaiting_po_page');
            $projectsAwaitingPoCount = $projectsAwaitingPo->total();
        }

        // 3. KPIs
        $baseKpi = Project::accessibleBy($user);
        $kpis = [
            'active_projects' => (clone $baseKpi)->whereIn('status', ['open', 'inprogress'])->count(),
            'awaiting_activation' => $awardedWithoutProject->count() + $projectsAwaitingPoCount,
            'completed_projects' => (clone $baseKpi)->whereIn('status', ['completed', 'closed'])->count(),
            'total_invoiced' => (clone $baseKpi)->with('invoices')->get()->sum(fn ($p) => $p->totalInvoiced()),
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
        $data = $request->validated();
        $poFile = $data['po_file'] ?? null;
        $projectDocuments = $data['project_documents'] ?? [];
        unset($data['po_file'], $data['project_documents']);

        if ($poFile) {
            $data['client_po_file'] = $poFile->store('project_po', 'public');
        }

        $project = $this->projectService->createProject($data, $request->user());

        if ($poFile) {
            $project->documents()->create([
                'file_name' => $poFile->getClientOriginalName(),
                'file_path' => $data['client_po_file'],
                'file_size' => $poFile->getSize(),
                'file_type' => $poFile->getClientMimeType(),
                'kind' => 'po',
                'uploaded_by_id' => $request->user()->id,
            ]);
        }

        foreach ($projectDocuments as $document) {
            $project->documents()->create([
                'file_name' => $document->getClientOriginalName(),
                'file_path' => $document->store('project_documents', 'public'),
                'file_size' => $document->getSize(),
                'file_type' => $document->getClientMimeType(),
                'kind' => 'general',
                'uploaded_by_id' => $request->user()->id,
            ]);
        }

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
     * Update editable project details.
     */
    public function update(Request $request, Project $project): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $project);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:200'],
            'client_name' => ['required', 'string', 'max:150'],
            'branch_id' => ['required', 'exists:branches,id'],
            'engineer_id' => ['nullable', 'exists:users,id'],
            'contract_value' => ['required', 'numeric', 'min:0'],
            'start_date' => ['nullable', 'date'],
            'expected_end_date' => ['nullable', 'date'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $oldValues = $project->only(array_keys($validated));
        $project->update($validated);
        $project->refresh();

        AuditService::log('updated', $project, "Updated project {$project->project_number}", $oldValues, $project->only(array_keys($validated)));

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Project {$project->project_number} updated.",
                'project' => $project,
            ]);
        }

        return redirect()->route('projects.index')->with('toast_success', "Project {$project->project_number} updated successfully.");
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
    public function show(Request $request, Project $project): View|JsonResponse
    {
        $this->authorize('view', $project);

        $project->load([
            'branch',
            'assignedEngineer',
            'lead',
            'quotation',
            'workCompletionCertificates.engineer',
        ]);

        if ($request->wantsJson()) {
            $project->load([
                'documents.uploadedBy',
                'activities.user',
                'purchaseOrders.preparedBy',
                'invoices.lineItems',
                'invoices.payments',
                'expenses.user',
            ]);

            return response()->json($project);
        }

        $projectTotals = [
            'total_invoiced' => (float) $project->invoices()->whereNotIn('status', ['draft', 'void'])->sum('grand_total'),
            'total_expenses' => (float) $project->expenses()->where('status', 'approved')->sum('grand_total'),
            'sent_invoice_count' => $project->invoices()->where('status', 'sent')->count(),
        ];
        $vendorPurchaseOrders = $project->purchaseOrders()
            ->with('preparedBy')
            ->paginate(10, ['*'], 'vendor_pos_page');
        $expenseSearch = $request->query('expense_search');
        $expenseSearch = is_string($expenseSearch) ? trim($expenseSearch) : '';
        $expenseStatus = $request->query('expense_status');
        $expenseStatus = is_string($expenseStatus) && in_array($expenseStatus, ['pending', 'approved', 'rejected'], true)
            ? $expenseStatus
            : '';

        $projectExpensesQuery = $project->expenses()
            ->with(['user', 'vehicle'])
            ->when($expenseStatus !== '', fn ($query) => $query->where('status', $expenseStatus))
            ->when($expenseSearch !== '', function ($query) use ($expenseSearch) {
                $query->where(function ($query) use ($expenseSearch) {
                    $query->where('expense_number', 'like', "%{$expenseSearch}%")
                        ->orWhere('category', 'like', "%{$expenseSearch}%")
                        ->orWhere('vendor_name', 'like', "%{$expenseSearch}%")
                        ->orWhere('description', 'like', "%{$expenseSearch}%");
                });
            });
        $projectExpenses = $projectExpensesQuery->paginate(10, ['*'], 'project_expenses_page');
        $projectInvoices = $project->invoices()
            ->with(['lineItems', 'payments'])
            ->paginate(10, ['*'], 'project_invoices_page');
        $vehicles = Vehicle::active()->orderBy('registration_number')->get();

        return view('projects.show', compact(
            'project',
            'projectTotals',
            'vendorPurchaseOrders',
            'projectExpenses',
            'projectInvoices',
            'vehicles',
            'expenseSearch',
            'expenseStatus'
        ));
    }

    /**
     * Record client PO and transition project to 'open'.
     */
    public function recordPo(RecordClientPoRequest $request, Project $project): RedirectResponse|JsonResponse
    {
        $this->authorize('open', $project);

        $data = $request->validated();
        if ($request->hasFile('po_file')) {
            $poFile = $request->file('po_file');
            $data['client_po_file'] = $poFile->store('project_po', 'public');
            $project->documents()->create([
                'file_name' => $poFile->getClientOriginalName(),
                'file_path' => $data['client_po_file'],
                'file_size' => $poFile->getSize(),
                'file_type' => $poFile->getClientMimeType(),
                'kind' => 'po',
                'uploaded_by_id' => $request->user()->id,
            ]);
        }

        $this->projectService->recordClientPo($project, $data, $request->user());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Client PO recorded. Project {$project->project_number} is now Open.",
            ]);
        }

        return redirect()->route('projects.show', $project)->with('toast_success', 'Client PO recorded. Project is now Open.');
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

        $data = $request->validated();
        $projectDocuments = $data['project_documents'] ?? [];
        unset($data['project_documents']);

        $this->projectService->completeWork($project, $data, $request->user());

        foreach ($projectDocuments as $document) {
            $project->documents()->create([
                'file_name' => $document->getClientOriginalName(),
                'file_path' => $document->store('project_documents', 'public'),
                'file_size' => $document->getSize(),
                'file_type' => $document->getClientMimeType(),
                'kind' => 'completion',
                'uploaded_by_id' => $request->user()->id,
            ]);
        }

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Work completed report recorded.']);
        }

        return redirect()->route('projects.show', $project)->with('toast_success', 'Work Completion Certificate generated. Project ready for billing.');
    }
}
