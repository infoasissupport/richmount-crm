{{--
    Lead list table shared by the dashboard (Recent Leads) and Lead Management, same columns and cells as the prototype.
    Needs: $leads (paginator, eager-load assignedEngineer, branch, latestQuotation, projects).
    Optional: $emptyText. Include leads.partials.row-tools once on the page for the ⋮ menu, Quick view and edit buttons.
--}}
<div class="table-wrap recent-wrap">
    <table class="table" id="recentTable">
        <thead>
            <tr>
                <th>Sl No</th>
                <th>Lead No</th>
                <th>Lead Name</th>
                <th>Company</th>
                <th>Assigned To</th>
                <th>Source</th>
                <th>Status</th>
                <th>
                    Date 
                    <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true">
                        <use href="#i-arrow-down"/>
                    </svg>
                </th>
                <th class="ta-c">Action</th>
            </tr>
        </thead>
        @php
            // same initials + colour hash as the prototype's avatar() helper
            $initials = fn ($name) => strtoupper(collect(preg_split('/\s+/', trim((string) $name)))
                ->filter(fn ($w) => preg_match('/^[A-Za-z]/', $w))->take(2)->map(fn ($w) => $w[0])->implode('')) ?: '—';
            $avTone = fn ($name) => ['pink', 'blue', 'amber', 'purple', 'green', 'gray'][array_sum(array_map('ord', str_split((string) $name))) % 6];
        @endphp
        <tbody>
            @forelse($leads as $lead)
                @php
                    [$srcIcon, $srcTone] = $lead->sourceStyle();
                    $leadName = $lead->customer_name ?: $lead->company_name;
                    // fields for the shared Edit Lead modal (leads.partials.form-modal)
                    $editLead = $lead->only(['id', 'lead_number', 'company_name', 'subject', 'contact_person', 'phone', 'email', 'rfq_number',
                        'branch_id', 'assigned_engineer_id', 'source', 'estimated_value', 'notes', 'stage'])
                        + ['lead_date' => $lead->lead_date?->format('Y-m-d')];
                @endphp
                <tr>
                    <td class="rl-no">{{ $loop->iteration + ($leads->currentPage() - 1) * $leads->perPage() }}</td>
                    <td class="rl-leadno">{{ $lead->lead_number }}</td>
                    <td class="strong rl-name" title="{{ $leadName }}">
                        <span class="cell-who">
                            <span class="avatar sm av-{{ $avTone($leadName) }}" aria-hidden="true">{{ $initials($leadName) }}</span>
                            <span class="cell-text">
                                <a href="{{ route('leads.show', $lead) }}" class="rl-link">{{ $leadName }}</a>
                            </span>
                        </span>
                    </td>
                    <td class="rl-field rl-company" data-label="Company" title="{{ $lead->company_name }}">
                        <span class="cell-text">
                            {{ $lead->company_name ?: '—' }}
                            <small>{{ $lead->departmentLabel() }}</small>
                        </span>
                    </td>
                    <td class="rl-field rl-eng" data-label="Assigned To">
                        @if($lead->assignedEngineer)
                            <span class="cell-who">
                                <span class="avatar sm av-{{ $avTone($lead->assignedEngineer->name) }}" aria-hidden="true">{{ $initials($lead->assignedEngineer->name) }}</span>
                                <span class="cell-text">
                                    {{ $lead->assignedEngineer->name }}
                                    <small>Project Engineer</small>
                                </span>
                            </span>
                        @else
                            <span style="color: var(--muted); font-size: 11px;">Unassigned</span>
                        @endif
                    </td>
                    <td class="rl-field rl-src" data-label="Source">
                        <span class="pill pill-{{ $srcTone }} src-pill">
                            <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-{{ $srcIcon }}"/></svg>
                            {{ $lead->source ?: 'Direct' }}
                        </span>
                    </td>
                    <td class="rl-status">
                        <span class="pill pill-{{ $lead->statusTone() }}"><i></i>{{ ucfirst($lead->status) }}</span>
                    </td>
                    <td class="rl-field rl-date" data-label="Date">
                        <span class="cell-date">
                            <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true">
                                <use href="#i-calendar"/>
                            </svg>
                            {{ $lead->lead_date ? $lead->lead_date->format('d M Y') : $lead->created_at->format('d M Y') }}
                        </span>
                    </td>
                    <td class="ta-c rl-act">
                        <span class="row-actions">
                            <a href="{{ route('leads.show', $lead) }}" class="act view" title="View Lead Details">
                                <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true">
                                    <use href="#i-eye"/>
                                </svg>
                            </a>
                            @can('leads.edit')
                            <button type="button" class="act edit" title="Edit Lead" aria-label="Edit {{ $leadName }}" data-edit-lead='@json($editLead)'>
                                <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true">
                                    <use href="#i-edit"/>
                                </svg>
                            </button>
                            @endcan
                            <button type="button" class="act" data-row-menu title="More" aria-label="More actions for {{ $leadName }}" aria-haspopup="menu" aria-expanded="false">
                                <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-more-vertical"/></svg>
                            </button>
                            @php
                                // next workflow step, same rules as the prototype's nextStep()
                                $q = $lead->latestQuotation;
                                $project = $lead->projects->first();
                                $next = null;
                                if ($lead->stage === 'new') {
                                    if (auth()->user()->can('quotes.create')) $next = ['Create Quotation', 'file-plus', route('quotations.builder', ['lead' => $lead->id])];
                                } elseif ($q && in_array($q->status, ['draft', 'pending', 'approved', 'rejected'])) {
                                    $next = [$q->status === 'pending' && auth()->user()->can('quotes.approve') ? 'Review & Approve' : ($q->status === 'approved' ? 'Open & Send' : 'Open Quotation'), 'file-text', route('quotations.show', $q)];
                                } elseif ($q && $q->status === 'sent') {
                                    $next = ['Open Quotation', 'file-text', route('quotations.show', $q)];
                                } elseif ($project) {
                                    $next = ['Open Project', 'briefcase', route('projects.show', $project)];
                                } elseif ($lead->stage === 'awarded' && $q && auth()->user()->can('projects.create')) {
                                    $next = ['Create Project', 'briefcase', route('projects.create-from-quote', $q), 'post'];
                                }

                                // data for the Quick view drawer
                                $quick = [
                                    'name' => $leadName,
                                    'initials' => $initials($leadName),
                                    'tone' => $avTone($leadName),
                                    'subject' => $lead->subject,
                                    'status' => ucfirst($lead->status),
                                    'statusTone' => $lead->statusTone(),
                                    'stage' => $lead->stage,
                                    'url' => route('leads.show', $lead),
                                    'edit' => auth()->user()->can('leads.edit') ? $editLead : null,
                                    'rows' => array_values(array_filter([
                                        ['Lead No', $lead->lead_number],
                                        ['Company', $lead->company_name ?: '—'],
                                        ['Contact', collect([$lead->contact_person, $lead->phone])->filter()->implode(' · ') ?: '—'],
                                        ['Project Subject', $lead->subject ?: '—'],
                                        ['Branch', $lead->branch?->name ?? '—'],
                                        ['Assigned Engineer', $lead->assignedEngineer?->name ?? 'Unassigned'],
                                        ['Source', $lead->source ?: '—'],
                                        ['Estimated Value', $lead->estimated_value > 0 ? 'AED ' . number_format($lead->estimated_value) : '—'],
                                        ['Created', $lead->created_at->format('d M Y')],
                                        $q ? ['Quotation', $q->quote_number . ' · ' . ucfirst($q->status)] : null,
                                        $project ? ['Project', $project->project_number] : null,
                                    ])),
                                ];
                            @endphp
                            <div class="popover row-menu" role="menu" hidden>
                                <a href="{{ route('leads.show', $lead) }}" class="pop-item" role="menuitem">
                                    <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-eye"/></svg>
                                    <span>Open lead</span>
                                </a>
                                <button type="button" class="pop-item" role="menuitem" data-quick-view='@json($quick)'>
                                    <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-file-user"/></svg>
                                    <span>Quick view</span>
                                </button>
                                @can('leads.edit')
                                <button type="button" class="pop-item" role="menuitem" data-edit-lead='@json($editLead)'>
                                    <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-edit"/></svg>
                                    <span>Edit lead</span>
                                </button>
                                @endcan
                                @can('leads.assign')
                                <a href="{{ route('leads.show', ['lead' => $lead, 'open' => 'assign']) }}" class="pop-item" role="menuitem">
                                    <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-user"/></svg>
                                    <span>Assign engineer</span>
                                </a>
                                @endcan
                                @if($next)
                                    <div class="pop-div"></div>
                                    @if(($next[3] ?? null) === 'post')
                                        <form method="POST" action="{{ $next[2] }}" onsubmit="return confirm('Create a project from quotation {{ $q->quote_number }}?')">
                                            @csrf
                                            <button type="submit" class="pop-item" role="menuitem">
                                                <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-{{ $next[1] }}"/></svg>
                                                <span>{{ $next[0] }}</span>
                                            </button>
                                        </form>
                                    @else
                                        <a href="{{ $next[2] }}" class="pop-item" role="menuitem">
                                            <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-{{ $next[1] }}"/></svg>
                                            <span>{{ $next[0] }}</span>
                                        </a>
                                    @endif
                                @endif
                                @can('leads.delete')
                                @if(!$q)
                                <div class="pop-div"></div>
                                <form method="POST" action="{{ route('leads.destroy', $lead) }}" onsubmit="return confirm('Delete lead {{ $lead->lead_number }}? This cannot be undone.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="pop-item danger" role="menuitem">
                                        <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-x"/></svg>
                                        <span>Delete lead</span>
                                    </button>
                                </form>
                                @endif
                                @endcan
                            </div>
                        </span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td class="empty" colspan="9" style="padding: 24px; text-align: center; color: var(--muted);">
                        {{ $emptyText ?? 'No leads found.' }}
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
