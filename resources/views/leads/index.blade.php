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
        <p class="sub">Track client inquiries, RFQs, engineer assignments, and quotation pipeline.</p>
    </div>
    <div class="page-actions">
        <a class="btn btn-ghost" href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}">
            <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-download"/></svg>
            Export CSV
        </a>
        <button class="btn btn-ghost" type="button" onclick="window.print()">
            <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-printer"/></svg>
            Print
        </button>
        @can('leads.assign')
        <button class="btn btn-ghost" type="button" onclick="openAssignBoardModal()">
            <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-users"/></svg>
            Assign Engineers
        </button>
        @endcan

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
    <section class="kpi-grid four" id="kpis" aria-label="Key figures">
        <div class="kpi">
            <span class="kpi-top">
                <span class="kpi-icon"><svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-users"/></svg></span>
            </span>
            <span class="kpi-value">{{ number_format($kpis['total']) }}</span>
            <span class="kpi-label">Total Leads</span>
            <span class="kpi-note">Active opportunities</span>
        </div>

        <div class="kpi">
            <span class="kpi-top">
                <span class="kpi-icon"><svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-file-plus"/></svg></span>
            </span>
            <span class="kpi-value">{{ number_format($kpis['new']) }}</span>
            <span class="kpi-label">New Leads</span>
            <span class="kpi-note">Awaiting quotation draft</span>
        </div>

        <div class="kpi">
            <span class="kpi-top">
                <span class="kpi-icon"><svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-file-text"/></svg></span>
            </span>
            <span class="kpi-value">{{ number_format($kpis['in_quotation']) }}</span>
            <span class="kpi-label">In Quotation</span>
            <span class="kpi-note">Draft or Sent to customer</span>
        </div>

        <div class="kpi">
            <span class="kpi-top">
                <span class="kpi-icon"><svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-award"/></svg></span>
            </span>
            <span class="kpi-value">{{ number_format($kpis['awarded']) }}</span>
            <span class="kpi-label">Awarded</span>
            <span class="kpi-note">Ready for PO &amp; project</span>
        </div>
    </section>

    {{-- Leads Table Card --}}
    <section class="card fill" id="leadsTable">
        <header class="card-head">
            <h2>
                <span class="head-ic">
                    <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-users"/></svg>
                </span>
                Leads Directory
            </h2>
            <div class="head-actions">
                <form method="GET" action="{{ route('leads.index') }}" style="display: flex; gap: 8px; flex-wrap: wrap;">
                    <label class="search-input recent-search">
                        <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-search"/></svg>
                        <input type="search" name="search" value="{{ request('search') }}" placeholder="Search company, subject, number..." aria-label="Search leads">
                    </label>

                    <span class="select-wrap mini">
                        <select name="branch_id" onchange="this.form.submit()">
                            <option value="">All Branches</option>
                            @foreach($branches as $b)
                                <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>{{ $b->short_name }}</option>
                            @endforeach
                        </select>
                    </span>

                    <span class="select-wrap mini">
                        <select name="stage" onchange="this.form.submit()">
                            <option value="">All Stages</option>
                            <option value="new" {{ request('stage') == 'new' ? 'selected' : '' }}>New Leads</option>
                            <option value="draft" {{ request('stage') == 'draft' ? 'selected' : '' }}>Draft Quotations</option>
                            <option value="sent" {{ request('stage') == 'sent' ? 'selected' : '' }}>Sent Quotations</option>
                            <option value="awarded" {{ request('stage') == 'awarded' ? 'selected' : '' }}>Awarded</option>
                        </select>
                    </span>

                    <span class="select-wrap mini">
                        <select name="status" onchange="this.form.submit()">
                            <option value="">All Status</option>
                            <option value="New" {{ request('status') == 'New' ? 'selected' : '' }}>New</option>
                            <option value="Contacted" {{ request('status') == 'Contacted' ? 'selected' : '' }}>Contacted</option>
                            <option value="Proposal" {{ request('status') == 'Proposal' ? 'selected' : '' }}>Proposal</option>
                            <option value="Negotiation" {{ request('status') == 'Negotiation' ? 'selected' : '' }}>Negotiation</option>
                            <option value="Won" {{ request('status') == 'Won' ? 'selected' : '' }}>Won</option>
                            <option value="Lost" {{ request('status') == 'Lost' ? 'selected' : '' }}>Lost</option>
                        </select>
                    </span>
                </form>
            </div>
        </header>

        <div class="table-wrap page">
            <table class="table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Lead No</th>
                        <th>Company / Customer</th>
                        <th>Project Subject</th>
                        <th>Branch</th>
                        <th>Assigned Engineer</th>
                        <th>Stage</th>
                        <th>Estimated (AED)</th>
                        <th>Created</th>
                        <th class="ta-c">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($leads as $idx => $l)
                        <tr>
                            <td>{{ $leads->firstItem() + $idx }}</td>
                            <td class="strong">
                                <a href="{{ route('leads.show', $l) }}" style="color: var(--primary); text-decoration: none;">
                                    {{ $l->lead_number }}
                                </a>
                            </td>
                            <td>
                                <span class="cell-who">
                                    <span class="avatar sm av-pink">{{ strtoupper(substr($l->company_name, 0, 2)) }}</span>
                                    <span class="cell-text">
                                        <strong>{{ $l->company_name }}</strong>
                                        <small>{{ $l->contact_person ? 'Attn: ' . $l->contact_person : 'RFQ: ' . ($l->rfq_number ?: 'Direct') }}</small>
                                    </span>
                                </span>
                            </td>
                            <td>
                                <span>{{ $l->subject }}</span>
                                <small style="display:block; color:#888;">{{ ucfirst($l->department) }} &bull; {{ $l->source }}</small>
                            </td>
                            <td>
                                <span class="pill pill-gray">{{ $l->branch->short_name }}</span>
                            </td>
                            <td>
                                @if($l->assignedEngineer)
                                    <span class="cell-who">
                                        <span class="avatar sm av-blue">{{ $l->assignedEngineer->initials }}</span>
                                        <span class="cell-text">{{ $l->assignedEngineer->name }}</span>
                                    </span>
                                @else
                                    <span style="color: #999; font-style: italic;">Unassigned</span>
                                @endif
                            </td>
                            <td>
                                @php
                                    $stageTone = match($l->stage) {
                                        'new' => 'green',
                                        'draft' => 'amber',
                                        'sent' => 'purple',
                                        'awarded' => 'primary',
                                        default => 'gray'
                                    };
                                    $stageLabel = match($l->stage) {
                                        'new' => 'New Lead',
                                        'draft' => 'Draft Quote',
                                        'sent' => 'Sent Quote',
                                        'awarded' => 'Awarded',
                                        default => ucfirst($l->stage)
                                    };
                                @endphp
                                <span class="pill pill-{{ $stageTone }}"><i></i>{{ $stageLabel }}</span>
                            </td>
                            <td class="num">
                                <b>{{ $l->estimated_value > 0 ? number_format($l->estimated_value, 2) : '—' }}</b>
                            </td>
                            <td>{{ $l->created_at->format('d M Y') }}</td>
                            <td class="ta-c">
                                <span class="row-actions">
                                    <a class="act view" href="{{ route('leads.show', $l) }}" title="View Details">
                                        <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-eye"/></svg>
                                    </a>

                                    @can('quotes.create')
                                    <a class="act" href="{{ route('quotations.builder', ['lead' => $l->id]) }}" title="Create / Open Quotation">
                                        <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-file-plus"/></svg>
                                    </a>
                                    @endcan

                                    @can('leads.edit')
                                    <button class="act edit" type="button" title="Edit Lead" onclick="openEditLeadModal({{ json_encode($l) }})">
                                        <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-edit"/></svg>
                                    </button>
                                    @endcan

                                    @can('leads.delete')
                                    @if($l->quotations->isEmpty())
                                    <form action="{{ route('leads.destroy', $l) }}" method="POST" style="display:inline;" onsubmit="return confirm('Delete lead {{ $l->lead_number }}?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="act delete" type="submit" title="Delete">
                                            <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-x"/></svg>
                                        </button>
                                    </form>
                                    @endif
                                    @endcan
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="empty">No leads found in this view.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($leads->total() > 0)
            {{ $leads->withQueryString()->links() }}
        @endif
    </section>
</div>

@include('leads.partials.form-modal')

{{-- Bulk Assign Board Modal --}}
<div class="modal-wrap" id="assignBoardModal" style="display: none;" onclick="if(event.target === this) closeAssignBoardModal()">
    <div class="modal modal-xl" role="dialog" aria-modal="true" aria-labelledby="assignBoardTitle">
        <div class="modal-head">
            <div>
                <h3 id="assignBoardTitle">Assign Engineers</h3>
                <p>Bulk allocate project engineers to active leads</p>
            </div>
            <button class="icon-btn" type="button" onclick="closeAssignBoardModal()" aria-label="Close">
                <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-x"/></svg>
            </button>
        </div>
        <form method="POST" action="{{ route('leads.bulk-assign') }}">
            @csrf
            <div class="modal-body">
                <div class="tm-toolbar" style="margin-bottom: 12px;">
                    <label class="search-input recent-search" style="display: flex; align-items: center; background: #fff; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 10px; height: 36px;">
                        <svg class="ic sm" style="width: 15px; height: 15px; color: #94a3b8; margin-right: 6px;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><use href="#i-search"/></svg>
                        <input type="search" id="assignSearch" placeholder="Search by name, company, subject..." oninput="filterAssignTable(this.value)" style="border: none; outline: none; background: transparent; width: 100%; font-size: 13px;">
                    </label>
                </div>
                <div class="table-wrap tall">
                    <table class="table cards assign-table" id="assignTable">
                        <thead>
                            <tr>
                                <th>Sl No</th>
                                <th>Lead No</th>
                                <th>Lead Name</th>
                                <th>Subject</th>
                                <th>Created</th>
                                <th>Assigned Engineer</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($leads as $i => $leadItem)
                                <tr class="assign-row" data-text="{{ strtolower($leadItem->lead_number . ' ' . $leadItem->company_name . ' ' . $leadItem->subject) }}">
                                    <td class="dt-no">{{ $i + 1 }}</td>
                                    <td>
                                        <input type="hidden" name="assignments[{{ $i }}][lead_id]" value="{{ $leadItem->id }}">
                                        <strong>{{ $leadItem->lead_number }}</strong>
                                    </td>
                                    <td class="strong">
                                        <span class="cell-who">
                                            <span class="avatar sm av-pink">{{ strtoupper(substr($leadItem->company_name, 0, 2)) }}</span>
                                            <span class="cell-text">
                                                {{ $leadItem->company_name }}
                                                <small>{{ $leadItem->contact_person }}</small>
                                            </span>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="cell-text">
                                            {{ $leadItem->subject }}
                                            <small>{{ ucfirst($leadItem->department) }} &bull; {{ $leadItem->branch->short_name }}</small>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="cell-date">
                                            {{ $leadItem->created_at->format('d M Y') }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="select-wrap mini">
                                            <select name="assignments[{{ $i }}][engineer_id]">
                                                <option value="">Select Engineer</option>
                                                @foreach($engineers as $eng)
                                                    <option value="{{ $eng->id }}" {{ $leadItem->assigned_engineer_id == $eng->id ? 'selected' : '' }}>
                                                        {{ $eng->name }} ({{ $eng->branch?->short_name }})
                                                    </option>
                                                @endforeach
                                            </select>
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 24px; color: var(--muted);">No active leads available to assign.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-foot">
                <button class="btn btn-ghost" type="button" onclick="closeAssignBoardModal()">Cancel</button>
                <button class="btn btn-primary" type="submit">
                    <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-check"/></svg>
                    Save Assignments
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function filterAssignTable(query) {
        query = query.toLowerCase().trim();
        document.querySelectorAll('#assignTable .assign-row').forEach(function(row) {
            var text = row.getAttribute('data-text') || '';
            row.style.display = text.indexOf(query) !== -1 ? '' : 'none';
        });
    }
</script>

@push('scripts')
<script>
    function openAssignBoardModal() {
        var m = document.getElementById('assignBoardModal');
        m.style.display = 'flex';
        m.classList.add('open');
    }

    function closeAssignBoardModal() {
        var m = document.getElementById('assignBoardModal');
        m.classList.remove('open');
        m.style.display = 'none';
    }

    document.addEventListener('DOMContentLoaded', function () {
        var params = new URLSearchParams(window.location.search);
        if (params.get('new') === '1') {
            openNewLeadModal();
        } else if (params.get('assign') === '1') {
            openAssignBoardModal();
        }
    });
</script>
@endpush
@endsection
