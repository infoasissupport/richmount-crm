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
                <a href="{{ route('quotations.index', ['create' => 1]) }}" class="pop-item" role="menuitem" onclick="event.preventDefault(); setQuickMenu(false); openCreateQuoteModal();">
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
    @include('partials.kpi-cards', ['kpis' => $kpis])

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

            @include('leads.partials.table', [
                'leads' => $recentLeads,
                'emptyText' => request('search') || request('status') ? 'No leads match your search criteria.' : 'No recent leads found in the selected period.',
            ])

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

@include('leads.partials.row-tools')

@canany(['leads.create', 'leads.edit'])
    @include('leads.partials.form-modal', ['returnTo' => url()->full()])
@endcanany
@can('quotes.create')
    @include('quotations.partials.create-modal')
@endcan
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

    document.addEventListener('click', function () { setQuickMenu(false); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') setQuickMenu(false); });
</script>
@endpush
