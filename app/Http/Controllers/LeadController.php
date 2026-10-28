<?php

namespace App\Http\Controllers;

use App\Http\Requests\Lead\AssignEngineerRequest;
use App\Http\Requests\Lead\StoreLeadRequest;
use App\Http\Requests\Lead\UpdateLeadRequest;
use App\Models\Branch;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeadController extends Controller
{
    /**
     * Display a listing of leads with server-side filters and KPIs.
     */
    public function index(Request $request): View|JsonResponse
    {
        $this->authorize('viewAny', Lead::class);
        $user = $request->user();

        $query = Lead::accessibleBy($user)
            ->with(['branch', 'assignedEngineer', 'latestQuotation'])
            ->when($request->filled('branch_id'), fn($q) => $q->where('branch_id', $request->branch_id))
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->when($request->filled('stage'), fn($q) => $q->where('stage', $request->stage))
            ->when($request->filled('department'), fn($q) => $q->where('department', $request->department))
            ->when($request->filled('engineer_id'), fn($q) => $q->where('assigned_engineer_id', $request->engineer_id))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%' . $request->search . '%';
                $q->where(function ($sq) use ($term) {
                    $sq->where('company_name', 'like', $term)
                       ->orWhere('subject', 'like', $term)
                       ->orWhere('lead_number', 'like', $term)
                       ->orWhere('contact_person', 'like', $term);
                });
            })
            ->latest();

        // Calculate KPI values
        $baseKpiQuery = Lead::accessibleBy($user);
        $kpis = [
            'total' => (clone $baseKpiQuery)->count(),
            'new' => (clone $baseKpiQuery)->where('stage', 'new')->count(),
            'in_quotation' => (clone $baseKpiQuery)->whereIn('stage', ['draft', 'sent'])->count(),
            'awarded' => (clone $baseKpiQuery)->where('stage', 'awarded')->count(),
        ];

        if ($request->get('export') === 'csv') {
            $exportLeads = (clone $query)->get();
            $filename = 'Leads_' . date('Ymd_His') . '.csv';
            return response()->streamDownload(function () use ($exportLeads) {
                $handle = fopen('php://output', 'w');
                fputcsv($handle, ['Lead No', 'Company', 'Subject', 'Branch', 'Assigned Engineer', 'Stage', 'Status', 'Estimated Value (AED)', 'Created Date']);
                foreach ($exportLeads as $l) {
                    fputcsv($handle, [
                        $l->lead_number,
                        $l->company_name,
                        $l->subject,
                        $l->branch->short_name ?? $l->branch->name,
                        $l->assignedEngineer?->name ?? 'Unassigned',
                        ucfirst($l->stage),
                        $l->status,
                        number_format($l->estimated_value, 2),
                        $l->created_at->format('Y-m-d'),
                    ]);
                }
                fclose($handle);
            }, $filename, ['Content-Type' => 'text/csv']);
        }

        $leads = $query->paginate(15);
        $branches = Branch::active()->get();
        $engineers = User::where('role_name', 'engineer')->get();
        $nextLeadNumber = Lead::nextLeadNumber();
        $clientNames = Lead::accessibleBy($user)->distinct()->pluck('company_name')->filter()->values();

        if ($request->wantsJson()) {
            return response()->json([
                'kpis' => $kpis,
                'leads' => $leads,
            ]);
        }

        return view('leads.index', compact('leads', 'kpis', 'branches', 'engineers', 'nextLeadNumber', 'clientNames'));
    }

    /**
     * Show form to create a new lead (redirects to modal in index).
     */
    public function create(): RedirectResponse
    {
        $this->authorize('create', Lead::class);

        return redirect()->route('leads.index', ['new' => 1]);
    }

    /**
     * Store a newly created lead in storage.
     */
    public function store(StoreLeadRequest $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validated();
        $validated['lead_number'] = Lead::nextLeadNumber();
        $validated['lead_date'] = $validated['lead_date'] ?? now()->toDateString();
        $validated['created_by_id'] = $request->user()->id;
        $isDraft = (bool) $request->input('save_as_draft', false);
        $validated['stage'] = $isDraft ? 'draft' : 'new';
        $validated['status'] = $isDraft ? 'Draft' : 'New';
        $validated['stage_date'] = now();
        $validated['subject'] = !empty($validated['subject']) ? $validated['subject'] : ($validated['company_name'] . ' - Project Inquiry');

        $lead = Lead::create($validated);

        // Handle file attachments if uploaded
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('lead_documents', 'public');
                $lead->attachments()->create([
                    'file_name' => $file->getClientOriginalName(),
                    'file_path' => $path,
                    'file_size' => $file->getSize(),
                    'file_type' => $file->getClientOriginalExtension(),
                    'uploaded_by_id' => $request->user()->id,
                ]);
            }
        }

        // Log creation activity
        $lead->activities()->create([
            'user_id' => $request->user()->id,
            'kind' => 'created',
            'text' => "Lead {$lead->lead_number} created by {$request->user()->name}",
        ]);

        AuditService::log('created', $lead, "Created lead {$lead->lead_number} for {$lead->company_name}");

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Lead {$lead->lead_number} created successfully.",
                'lead' => $lead->load(['branch', 'assignedEngineer']),
            ], 201);
        }

        return $this->redirectAfterSave($request, $lead)->with('toast_success', "Lead {$lead->lead_number} created successfully.");
    }

    /**
     * Display the specified lead.
     */
    public function show(Lead $lead): View|JsonResponse
    {
        $this->authorize('view', $lead);

        $lead->load(['branch', 'assignedEngineer', 'createdBy', 'quotations.preparedBy', 'attachments', 'activities.user']);
        $engineers = User::where('role_name', 'engineer')->with('branch')->get();
        $branches = Branch::active()->get();

        if (request()->wantsJson()) {
            return response()->json($lead);
        }

        return view('leads.show', compact('lead', 'engineers', 'branches'));
    }

    /**
     * Update the specified lead.
     */
    public function update(UpdateLeadRequest $request, Lead $lead): RedirectResponse|JsonResponse
    {
        $old = $lead->toArray();
        $validated = $request->validated();

        $lead->update($validated);

        // Attachments added from the edit form are appended to the existing ones
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('lead_documents', 'public');
                $lead->attachments()->create([
                    'file_name' => $file->getClientOriginalName(),
                    'file_path' => $path,
                    'file_size' => $file->getSize(),
                    'file_type' => $file->getClientOriginalExtension(),
                    'uploaded_by_id' => $request->user()->id,
                ]);
            }
        }

        // Activity log
        $lead->activities()->create([
            'user_id' => $request->user()->id,
            'kind' => 'updated',
            'text' => "Lead details updated by {$request->user()->name}",
        ]);

        AuditService::log('updated', $lead, "Updated lead {$lead->lead_number}", $old, $lead->fresh()->toArray());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Lead {$lead->lead_number} updated.",
                'lead' => $lead->fresh(),
            ]);
        }

        return $this->redirectAfterSave($request, $lead)->with('toast_success', "Lead {$lead->lead_number} updated.");
    }

    /**
     * Forms opened elsewhere (e.g. the dashboard) send _return to come back to the same page;
     * only same-app URLs are followed, otherwise go to the lead.
     */
    protected function redirectAfterSave(Request $request, Lead $lead): RedirectResponse
    {
        $return = $request->input('_return');
        $root = url('/');
        $sameApp = is_string($return)
            && ($return === $root || str_starts_with($return, $root . '/') || str_starts_with($return, $root . '?'));

        return $sameApp ? redirect()->to($return) : redirect()->route('leads.show', $lead);
    }

    /**
     * Assign lead to engineer.
     */
    public function assign(AssignEngineerRequest $request, Lead $lead): RedirectResponse|JsonResponse
    {
        $this->authorize('assign', $lead);

        $engineer = User::findOrFail($request->engineer_id);
        $lead->update(['assigned_engineer_id' => $engineer->id]);

        $lead->activities()->create([
            'user_id' => $request->user()->id,
            'kind' => 'assigned',
            'text' => "Lead assigned to {$engineer->name}",
        ]);

        AuditService::log('assigned', $lead, "Assigned lead {$lead->lead_number} to {$engineer->name}");

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Lead assigned to {$engineer->name}.",
            ]);
        }

        return redirect()->back()->with('toast_success', "Lead assigned to {$engineer->name}.");
    }

    /**
     * Bulk assign engineers from the Assignment Board.
     */
    public function bulkAssign(Request $request): RedirectResponse|JsonResponse
    {
        $this->authorize('create', Lead::class);

        $request->validate([
            'assignments' => ['required', 'array'],
            'assignments.*.lead_id' => ['required', 'exists:leads,id'],
            'assignments.*.engineer_id' => ['required', 'exists:users,id'],
        ]);

        $count = 0;
        foreach ($request->assignments as $item) {
            $lead = Lead::find($item['lead_id']);
            if ($lead && $lead->assigned_engineer_id !== (int) $item['engineer_id']) {
                $lead->update(['assigned_engineer_id' => $item['engineer_id']]);
                $count++;
            }
        }

        AuditService::log('bulk_assigned', 'Lead', "Bulk assigned {$count} leads to engineers");

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "{$count} leads assigned successfully.",
            ]);
        }

        return redirect()->back()->with('toast_success', "{$count} leads assigned successfully.");
    }

    /**
     * Remove the specified lead from storage.
     */
    public function destroy(Lead $lead): RedirectResponse|JsonResponse
    {
        $this->authorize('delete', $lead);

        $number = $lead->lead_number;
        $lead->delete();

        AuditService::log('deleted', $lead, "Deleted lead {$number}");

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Lead {$number} deleted.",
            ]);
        }

        return redirect()->route('leads.index')->with('toast_info', "Lead {$number} deleted.");
    }
}
