@extends('layouts.app')

@section('title', 'Lead Management')
@section('page_id', 'leads')

@section('breadcrumbs')
    <svg class="ic sep" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-chevron-right"/></svg>
    <span class="crumb current">Lead Management</span>
@endsection

@section('content')
<section class="page-head" id="pageHead">
    <div>
        <h1>Lead Management</h1>
    </div>
    <div class="page-actions">
        <a class="btn btn-ghost" href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}">
            <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-download"/></svg>
            Export CSV
        </a>
        @can('leads.create')
        <button class="btn btn-primary" type="button" onclick="openNewLeadModal()">
            <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-plus"/></svg>
            New Lead
        </button>
        @endcan
    </div>
</section>

<div class="dash-scroll">
    {{-- KPIs --}}
    @include('partials.kpi-cards', ['kpis' => $kpiCards, 'gridClass' => 'four'])

    {{-- Leads Table Card --}}
    <section class="card fill" id="leadsTable">
        <header class="card-head recent-head">
            <h2>Leads Directory</h2>
            <div class="head-actions">
                {{-- same filter bar design as the dashboard's Recent Leads --}}
                <form method="GET" action="{{ route('leads.index') }}" class="recent-filters">
                    <label class="search-input recent-search">
                        <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-search"/></svg>
                        <input type="search" name="search" value="{{ request('search') }}" placeholder="Search leads by name, company, subject..." aria-label="Search leads" onchange="this.form.submit()">
                    </label>

                    <span class="select-wrap mini with-icon">
                        <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-filter"/></svg>
                        <select name="status" aria-label="Filter by status" onchange="this.form.submit()">
                            <option value="">All Status</option>
                            <option value="New" {{ request('status') == 'New' ? 'selected' : '' }}>New</option>
                            <option value="Contacted" {{ request('status') == 'Contacted' ? 'selected' : '' }}>Contacted</option>
                            <option value="Proposal" {{ request('status') == 'Proposal' ? 'selected' : '' }}>Proposal</option>
                            <option value="Negotiation" {{ request('status') == 'Negotiation' ? 'selected' : '' }}>Negotiation</option>
                            <option value="Won" {{ request('status') == 'Won' ? 'selected' : '' }}>Won</option>
                            <option value="Lost" {{ request('status') == 'Lost' ? 'selected' : '' }}>Lost</option>
                        </select>
                    </span>

                    <span class="select-wrap mini with-icon">
                        <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-calendar"/></svg>
                        <select name="period" aria-label="Date period" onchange="this.form.submit()">
                            @php $leadPeriod = request('period', 'all'); @endphp
                            <option value="month" {{ $leadPeriod === 'month' ? 'selected' : '' }}>This Month</option>
                            <option value="7" {{ $leadPeriod === '7' ? 'selected' : '' }}>Last 7 Days</option>
                            <option value="30" {{ $leadPeriod === '30' ? 'selected' : '' }}>Last 30 Days</option>
                            <option value="year" {{ $leadPeriod === 'year' ? 'selected' : '' }}>This Year</option>
                            <option value="all" {{ $leadPeriod === 'all' ? 'selected' : '' }}>All Time</option>
                        </select>
                    </span>
                </form>
            </div>
        </header>

        @include('leads.partials.table', ['leads' => $leads, 'emptyText' => 'No leads found in this view.'])

        @if($leads->total() > 0)
            {{ $leads->withQueryString()->links() }}
        @endif
    </section>
</div>

@include('leads.partials.form-modal')
@include('leads.partials.row-tools')

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var params = new URLSearchParams(window.location.search);
        if (params.get('new') === '1') {
            openNewLeadModal();
        }
    });
</script>
@endpush
@endsection
