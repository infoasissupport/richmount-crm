@extends('layouts.app')

@section('title', 'Dashboard')
@section('page_id', 'dashboard')


@section('content')
<section class="page-head" id="pageHead">
    <div>
        <h1>Dashboard</h1>
    </div>
    <div class="page-actions">
        @can('leads.create')
        <div class="split-btn">
            <a class="btn btn-primary btn-lg" href="{{ route('leads.create') }}" id="newLeadBtn" onclick="event.preventDefault(); openNewLeadModal();">
                <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-plus-circle"/></svg>
                New Lead
            </a>
            <button class="btn btn-primary btn-lg split-caret" type="button" onclick="toggleQuickMenu(event)" aria-label="More create options" aria-haspopup="menu" aria-expanded="false" aria-controls="quickMenu">
                <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-chevron-down"/></svg>
            </button>
            <div class="popover split-menu" id="quickMenu" role="menu" hidden>
                <a href="{{ route('leads.create') }}" class="pop-item" role="menuitem" onclick="event.preventDefault(); setQuickMenu(false); openNewLeadModal();">
                    <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-plus"/></svg>
                    <span>Quick add lead</span>
                </a>
                @can('quotes.create')
                <a href="{{ route('quotations.builder') }}" class="pop-item" role="menuitem">
                    <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-file-plus"/></svg>
                    <span>Create quotation</span>
                </a>
                @endcan
                <a href="{{ route('leads.index') }}" class="pop-item" role="menuitem">
                    <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-users"/></svg>
                    <span>Open Lead Management</span>
                </a>
            </div>
        </div>
        @endcan
    </div>
</section>

<!-- Everything below the page header scrolls inside this box -->
<div class="dash-scroll">
    <!-- Live Dynamic KPIs -->
    <section class="kpi-grid" id="kpis" aria-label="Key figures">
        @foreach($kpis as $kpi)
            <a class="kpi" href="{{ $kpi['href'] }}" data-kpi="{{ $kpi['key'] }}" title="Open {{ $kpi['label'] }}">
                <span class="kpi-top">
                    <span class="kpi-icon">
                        <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true">
                            <use href="#i-{{ $kpi['icon'] }}"/>
                        </svg>
                    </span>
                    @if(!empty($kpi['trend']))
                        <span class="kpi-trend {{ $kpi['trend']['direction'] }}" title="vs previous period">
                            <svg class="ic xs" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                <use href="#i-arrow-{{ $kpi['trend']['direction'] }}"/>
                            </svg>
                            {{ $kpi['trend']['pct'] }}%
                        </span>
                    @endif
                </span>
                <span class="kpi-value">{{ $kpi['value'] }}</span>
                <span class="kpi-label">{{ $kpi['label'] }}</span>
             
            </a>
        @endforeach
    </section>

    <!-- Recent Leads with Backend Filter & Pagination -->
    <section class="bottom-grid fill">
        <article class="card">
            <header class="card-head recent-head">
                <h2>Recent Leads</h2>
                <div class="head-actions">
                    <form method="GET" action="{{ route('dashboard') }}" id="recentFilterForm" class="recent-filters">
                        <input type="hidden" name="pipeline_period" value="{{ $pipelinePeriod }}">
                        
                        <label class="search-input recent-search">
                            <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true">
                                <use href="#i-search"/>
                            </svg>
                            <input type="search" name="search" id="recentSearch" placeholder="Search leads by name, company, source..." aria-label="Search recent leads" value="{{ request('search') }}" onchange="document.getElementById('recentFilterForm').submit()">
                        </label>

                        <span class="select-wrap mini with-icon">
                            <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true">
                                <use href="#i-filter"/>
                            </svg>
                            <select name="status" id="recentStatus" aria-label="Filter by status" onchange="document.getElementById('recentFilterForm').submit()">
                                <option value="">All Status</option>
                                <option value="new" {{ request('status') === 'new' ? 'selected' : '' }}>New</option>
                                <option value="contacted" {{ request('status') === 'contacted' ? 'selected' : '' }}>Contacted</option>
                                <option value="proposal" {{ request('status') === 'proposal' ? 'selected' : '' }}>Proposal</option>
                                <option value="negotiation" {{ request('status') === 'negotiation' ? 'selected' : '' }}>Negotiation</option>
                                <option value="won" {{ request('status') === 'won' ? 'selected' : '' }}>Won</option>
                                <option value="lost" {{ request('status') === 'lost' ? 'selected' : '' }}>Lost</option>
                            </select>
                        </span>

                        <span class="select-wrap mini with-icon">
                            <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true">
                                <use href="#i-calendar"/>
                            </svg>
                            <select name="period" id="recentPeriod" aria-label="Date period" onchange="document.getElementById('recentFilterForm').submit()">
                                <option value="month" {{ request('period', 'month') === 'month' ? 'selected' : '' }}>This Month</option>
                                <option value="7" {{ request('period') === '7' ? 'selected' : '' }}>Last 7 Days</option>
                                <option value="30" {{ request('period') === '30' ? 'selected' : '' }}>Last 30 Days</option>
                                <option value="year" {{ request('period') === 'year' ? 'selected' : '' }}>This Year</option>
                                <option value="all" {{ request('period') === 'all' ? 'selected' : '' }}>All Time</option>
                            </select>
                        </span>
                    </form>
                </div>
            </header>

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
                        @forelse($recentLeads as $lead)
                            @php
                                [$srcIcon, $srcTone] = $lead->sourceStyle();
                                $leadName = $lead->customer_name ?: $lead->company_name;
                                // fields for the shared Edit Lead modal (leads.partials.form-modal)
                                $editLead = $lead->only(['id', 'lead_number', 'company_name', 'subject', 'contact_person', 'phone', 'email', 'rfq_number',
                                    'branch_id', 'assigned_engineer_id', 'source', 'estimated_value', 'notes', 'stage'])
                                    + ['lead_date' => $lead->lead_date?->format('Y-m-d')];
                            @endphp
                            <tr>
                                <td class="rl-no">{{ $loop->iteration + ($recentLeads->currentPage() - 1) * $recentLeads->perPage() }}</td>
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
                                    {{ request('search') || request('status') ? 'No leads match your search criteria.' : 'No recent leads found in the selected period.' }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- outside the scrolling table box, so it stays pinned under the rows --}}
            @if($recentLeads->total() > 0)
                {{ $recentLeads->links() }}
            @endif
        </article>
    </section>

    <!-- <section class="card pipeline" id="pipeline">
        <header class="card-head">
            <h2 class="lg">Lead Pipeline</h2>
            <div class="head-actions">
                <form method="GET" action="{{ route('dashboard') }}" id="pipelinePeriodForm">
                    <input type="hidden" name="search" value="{{ request('search') }}">
                    <input type="hidden" name="status" value="{{ request('status') }}">
                    <input type="hidden" name="period" value="{{ request('period') }}">

                    <span class="select-wrap mini with-icon">
                        <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true">
                            <use href="#i-calendar"/>
                        </svg>
                        <select name="pipeline_period" id="pipelinePeriod" aria-label="Pipeline period" onchange="document.getElementById('pipelinePeriodForm').submit()">
                            <option value="30" {{ $pipelinePeriod === '30' ? 'selected' : '' }}>Last 30 Days</option>
                            <option value="90" {{ $pipelinePeriod === '90' ? 'selected' : '' }}>Last 90 Days</option>
                            <option value="year" {{ $pipelinePeriod === 'year' ? 'selected' : '' }}>This Year</option>
                            <option value="all" {{ $pipelinePeriod === 'all' ? 'selected' : '' }}>All Time</option>
                        </select>
                    </span>
                </form>
            </div>
        </header>

        <div class="pipeline-grid" id="pipelineCols">
            @foreach(['new', 'draft', 'sent', 'awarded'] as $stageId)
                @php $col = $pipeline[$stageId]; @endphp
                <div class="pl-col pl-{{ $stageId }}">
                    <div class="pl-head">
                        <span>{{ $col['name'] }}</span>
                        <span class="pl-count">{{ $col['count'] }}</span>
                    </div>

                    <div class="pl-list">
                        @forelse($col['items'] as $item)
                            <div class="pl-card" onclick="window.location.href='{{ $item['url'] }}'" role="button" tabindex="0" aria-label="{{ $item['name'] }} – {{ $item['subject'] }}">
                                <span class="avatar" title="Engineer: {{ $item['engineer_name'] }}">{{ $item['initials'] }}</span>
                                <div class="pl-info">
                                    <strong>{{ $item['name'] }}</strong>
                                    <span>{{ $item['subject'] }}</span>
                                    <div class="pl-meta">
                                        <span>
                                            <svg class="ic xs" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><use href="#i-user"/></svg>
                                            {{ $item['branch_code'] }}
                                        </span>
                                        <span>
                                            <svg class="ic xs" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><use href="#i-calendar"/></svg>
                                            {{ $item['date'] }}
                                        </span>
                                    </div>
                                </div>
                                <a href="{{ $item['url'] }}" class="kebab" title="View details" onclick="event.stopPropagation();">
                                    <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><use href="#i-chevron-right"/></svg>
                                </a>
                            </div>
                        @empty
                            <div class="pl-empty">Nothing in {{ strtolower($col['name']) }} for this period</div>
                        @endforelse
                    </div>

                    <a class="pl-foot" href="{{ $col['foot_url'] }}" style="text-decoration:none;">
                        @if($col['foot_icon'])
                            <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><use href="#i-{{ $col['foot_icon'] }}"/></svg>
                        @endif
                        {{ $col['foot_label'] }}
                    </a>
                </div>
            @endforeach
        </div>
    </section> -->
</div>

{{-- Lead Quick view drawer, filled from the row's data-quick-view JSON --}}
<div class="drawer-wrap" id="leadDrawer" style="display: none;">
    <aside class="drawer" role="dialog" aria-modal="true" aria-labelledby="leadDrawerTitle">
        <div class="drawer-head">
            <h3 id="leadDrawerTitle">Lead Details</h3>
            <button class="icon-btn" type="button" data-drawer-close aria-label="Close">
                <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-x"/></svg>
            </button>
        </div>
        <div class="drawer-body">
            <div class="dr-hero">
                <span class="avatar lg" data-f="avatar"></span>
                <div><h4 data-f="name"></h4><p data-f="subject"></p></div>
                <span class="pill" data-f="status"></span>
            </div>
            <ol class="stepper" data-f="stepper"></ol>
            <dl class="kv" data-f="rows"></dl>
        </div>
        <div class="drawer-foot">
            <a class="btn btn-primary" data-f="open">
                <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-eye"/></svg>
                Open lead
            </a>
            <button type="button" class="btn btn-ghost" data-f="edit">
                <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-edit"/></svg>
                Edit lead
            </button>
        </div>
    </aside>
</div>

@canany(['leads.create', 'leads.edit'])
    @include('leads.partials.form-modal', ['returnTo' => url()->full()])
@endcanany
@endsection

@push('scripts')
<script>
    function setQuickMenu(open) {
        const menu = document.getElementById('quickMenu');
        if (!menu) return;
        menu.hidden = !open;
        menu.previousElementSibling.setAttribute('aria-expanded', open ? 'true' : 'false');
    }

    function toggleQuickMenu(event) {
        event.stopPropagation();
        setQuickMenu(document.getElementById('quickMenu').hidden);
    }

    // Recent Leads "more" menus: .popover is position:fixed, so place it under the button (flip up near the bottom edge)
    let openRowMenu = null;
    function closeRowMenu() {
        if (!openRowMenu) return;
        openRowMenu.menu.hidden = true;
        openRowMenu.btn.setAttribute('aria-expanded', 'false');
        openRowMenu = null;
    }
    function openRowMenuFor(btn) {
        const menu = btn.nextElementSibling;
        menu.hidden = false;
        const r = btn.getBoundingClientRect(), w = menu.offsetWidth, h = menu.offsetHeight;
        let top = r.bottom + 6;
        if (top + h > window.innerHeight - 8) top = Math.max(8, r.top - h - 6);
        menu.style.left = Math.max(8, Math.min(r.right - w, window.innerWidth - w - 8)) + 'px';
        menu.style.top = top + 'px';
        btn.setAttribute('aria-expanded', 'true');
        openRowMenu = { btn, menu };
    }
    // Lead Quick view drawer
    const LEAD_STAGES = [['new', 'New'], ['draft', 'Draft'], ['sent', 'Sent'], ['awarded', 'Awarded']];
    const svgIcon = (name, cls) => `<svg class="ic${cls ? ' ' + cls : ''}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-${name}"/></svg>`;
    const escHtml = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    function openLeadDrawer(d) {
        const wrap = document.getElementById('leadDrawer');
        const f = name => wrap.querySelector(`[data-f="${name}"]`);
        const avatar = f('avatar');
        avatar.className = `avatar lg av-${d.tone}`;
        avatar.textContent = d.initials;
        f('name').textContent = d.name;
        f('subject').textContent = d.subject || '';
        f('status').className = `pill pill-${d.statusTone}`;
        f('status').innerHTML = `<i></i>${escHtml(d.status)}`;
        const idx = LEAD_STAGES.findIndex(s => s[0] === d.stage);
        f('stepper').innerHTML = LEAD_STAGES.map((s, i) =>
            `<li class="${i < idx ? 'done' : i === idx ? 'current' : ''}"><span>${i < idx ? svgIcon('check', 'xs') : i + 1}</span>${s[1]}</li>`).join('');
        f('rows').innerHTML = d.rows.map(r => `<dt>${escHtml(r[0])}</dt><dd>${escHtml(r[1])}</dd>`).join('');
        f('open').href = d.url;
        f('edit').hidden = !d.edit;
        f('edit').onclick = d.edit ? () => { closeLeadDrawer(); openEditLeadModal(d.edit); } : null;
        wrap.style.display = 'flex';
        requestAnimationFrame(() => wrap.classList.add('open'));
        wrap.querySelector('[data-drawer-close]').focus();
    }
    function closeLeadDrawer() {
        const wrap = document.getElementById('leadDrawer');
        if (wrap.style.display === 'none') return;
        wrap.classList.remove('open');
        setTimeout(() => { wrap.style.display = 'none'; }, 200);
    }
    document.getElementById('leadDrawer').addEventListener('mousedown', e => { if (e.target.id === 'leadDrawer') closeLeadDrawer(); });
    document.querySelector('[data-drawer-close]').addEventListener('click', closeLeadDrawer);

    document.addEventListener('click', function(e) {
        const ed = e.target.closest('[data-edit-lead]');
        if (ed) {
            closeRowMenu();
            openEditLeadModal(JSON.parse(ed.dataset.editLead));
            return;
        }
        const qv = e.target.closest('[data-quick-view]');
        if (qv) {
            closeRowMenu();
            openLeadDrawer(JSON.parse(qv.dataset.quickView));
            return;
        }
        const btn = e.target.closest('[data-row-menu]');
        if (btn) {
            const same = openRowMenu && openRowMenu.btn === btn;
            closeRowMenu();
            if (!same) openRowMenuFor(btn);
        } else if (!e.target.closest('.row-menu')) {
            closeRowMenu();
        }
        setQuickMenu(false);
    });
    document.addEventListener('keydown', function(e) { if (e.key === 'Escape') { setQuickMenu(false); closeRowMenu(); closeLeadDrawer(); } });
    document.querySelector('.dash-scroll')?.addEventListener('scroll', closeRowMenu, { passive: true });
    window.addEventListener('resize', closeRowMenu);
</script>
@endpush
