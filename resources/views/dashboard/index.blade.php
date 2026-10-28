@extends('layouts.app')

@section('title', 'Dashboard')
@section('page_id', 'dashboard')


@section('content')
<section class="page-head" id="pageHead">
    <div>
        <h1>Dashboard</h1>
        <p class="sub">Welcome to Rich Mount ERP. Overview of company operations, pipeline, and activities.</p>
    </div>
    <div class="page-actions">
        @can('leads.create')
        <div class="split-btn">
            <a class="btn btn-primary btn-lg" href="{{ route('leads.create') }}" id="newLeadBtn">
                <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-plus-circle"/></svg>
                New Lead
            </a>
            <button class="btn btn-primary btn-lg split-caret" type="button" onclick="toggleQuickMenu(event)" aria-label="More create options">
                <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-chevron-down"/></svg>
            </button>
            <div class="dropdown-menu" id="quickMenu" style="display:none; position:absolute; right:0; top:100%; margin-top:4px; z-index:50;">
                <a href="{{ route('leads.create') }}" class="dropdown-item">
                    <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><use href="#i-plus"/></svg>
                    Quick add lead
                </a>
                @can('quotes.create')
                <a href="{{ route('quotations.builder') }}" class="dropdown-item">
                    <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><use href="#i-file-text"/></svg>
                    Create quotation
                </a>
                @endcan
                <a href="{{ route('leads.index') }}" class="dropdown-item">
                    <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><use href="#i-users"/></svg>
                    Open Lead Management
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
                @if(!empty($kpi['note']))
                    <span class="kpi-note">{{ $kpi['note'] }}</span>
                @endif
            </a>
        @endforeach
    </section>

    <!-- Recent Leads with Backend Filter & Pagination -->
    <section class="bottom-grid">
        <article class="card">
            <header class="card-head recent-head">
                <h2>Recent Leads</h2>
                <div class="head-actions">
                    <form method="GET" action="{{ route('dashboard') }}" id="recentFilterForm" style="display:flex; align-items:center; gap:8px;">
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
                    <tbody>
                        @forelse($recentLeads as $lead)
                            <tr>
                                <td class="rl-no">{{ $loop->iteration + ($recentLeads->currentPage() - 1) * $recentLeads->perPage() }}</td>
                                <td class="rl-leadno strong">{{ $lead->lead_number }}</td>
                                <td class="strong rl-name" title="{{ $lead->customer_name }}">
                                    <span class="cell-who">
                                        <span class="avatar">{{ strtoupper(substr($lead->customer_name ?: 'LD', 0, 2)) }}</span>
                                        <span class="cell-text">
                                            <a href="{{ route('leads.show', $lead) }}" style="color: inherit; text-decoration: none;">
                                                {{ $lead->customer_name }}
                                            </a>
                                        </span>
                                    </span>
                                </td>
                                <td class="rl-field rl-company" data-label="Company" title="{{ $lead->company_name }}">
                                    <span class="cell-text">
                                        {{ $lead->company_name ?: '—' }}
                                        <small>{{ ucfirst($lead->department ?: 'Civil Contracting') }}</small>
                                    </span>
                                </td>
                                <td class="rl-field rl-eng" data-label="Assigned To">
                                    @if($lead->assignedEngineer)
                                        <span class="cell-who">
                                            <span class="avatar">{{ strtoupper(substr($lead->assignedEngineer->name, 0, 2)) }}</span>
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
                                    <span class="pill pill-muted">{{ ucfirst($lead->source ?: 'Direct') }}</span>
                                </td>
                                <td class="rl-status">
                                    <span class="pill pill-{{ $lead->statusTone() }}">{{ ucfirst($lead->status) }}</span>
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
                                        <a href="{{ route('leads.edit', $lead) }}" class="act edit" title="Edit Lead">
                                            <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true">
                                                <use href="#i-edit"/>
                                            </svg>
                                        </a>
                                        @endcan
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

                @if($recentLeads->total() > $recentLeads->perPage())
                    <div class="pager" id="recentPager">
                        <span class="pager-info">
                            Showing {{ $recentLeads->firstItem() }}–{{ $recentLeads->lastItem() }} of {{ $recentLeads->total() }}
                        </span>
                        <span class="pager-btns">
                            {{-- Previous Page Link --}}
                            @if ($recentLeads->onFirstPage())
                                <button class="btn btn-ghost btn-sm" disabled aria-label="Previous page">
                                    <svg class="ic sm flip" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><use href="#i-chevron-right"/></svg>
                                </button>
                            @else
                                <a href="{{ $recentLeads->previousPageUrl() }}" class="btn btn-ghost btn-sm" aria-label="Previous page">
                                    <svg class="ic sm flip" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><use href="#i-chevron-right"/></svg>
                                </a>
                            @endif

                            {{-- Numeric Links --}}
                            @foreach ($recentLeads->getUrlRange(1, $recentLeads->lastPage()) as $page => $url)
                                @if ($page == $recentLeads->currentPage())
                                    <button class="btn btn-ghost btn-sm pager-num active" aria-current="page">{{ $page }}</button>
                                @else
                                    <a href="{{ $url }}" class="btn btn-ghost btn-sm pager-num">{{ $page }}</a>
                                @endif
                            @endforeach

                            {{-- Next Page Link --}}
                            @if ($recentLeads->hasMorePages())
                                <a href="{{ $recentLeads->nextPageUrl() }}" class="btn btn-ghost btn-sm" aria-label="Next page">
                                    <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><use href="#i-chevron-right"/></svg>
                                </a>
                            @else
                                <button class="btn btn-ghost btn-sm" disabled aria-label="Next page">
                                    <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><use href="#i-chevron-right"/></svg>
                                </button>
                            @endif
                        </span>
                    </div>
                @endif
            </div>
        </article>
    </section>

    <!-- Lead Pipeline (Live 4-Stage Kanban) -->
    <section class="card pipeline" id="pipeline">
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
    </section>
</div>
@endsection

@push('scripts')
<script>
    function toggleQuickMenu(event) {
        event.stopPropagation();
        const menu = document.getElementById('quickMenu');
        menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
    }

    document.addEventListener('click', function() {
        const menu = document.getElementById('quickMenu');
        if (menu) menu.style.display = 'none';
    });
</script>
@endpush
