@extends('layouts.app')

@section('title', 'Quotations')
@section('page_id', 'quotations')

@section('breadcrumbs')
    <svg class="ic sep" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-chevron-right"/></svg>
    <span class="crumb current">Quotations</span>
@endsection

@section('content')
<section class="page-head" id="pageHead">
    <div>
        <h1>Quotations</h1>
        <p class="sub">Draft, review, approve, and send branch-specific quotations to clients.</p>
    </div>
    <div class="page-actions">
        @can('quotes.create')
        <button class="btn btn-primary" type="button" onclick="openCreateQuoteModal()">
            <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-file-text"/></svg>
            Create Quotation
        </button>
        @endcan
    </div>
</section>

<div class="dash-scroll">
    {{-- KPIs --}}
    <section class="kpi-grid four" id="kpis" aria-label="Key figures">
        <div class="kpi">
            <span class="kpi-top">
                <span class="kpi-icon"><svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-file-text"/></svg></span>
            </span>
            <span class="kpi-value">{{ number_format($kpis['total']) }}</span>
            <span class="kpi-label">Total Quotations</span>
            <span class="kpi-note">All stages</span>
        </div>

        <div class="kpi">
            <span class="kpi-top">
                <span class="kpi-icon"><svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-edit"/></svg></span>
            </span>
            <span class="kpi-value">{{ number_format($kpis['drafts']) }}</span>
            <span class="kpi-label">Drafts</span>
            <span class="kpi-note">In preparation</span>
        </div>

        <div class="kpi">
            <span class="kpi-top">
                <span class="kpi-icon"><svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-clock"/></svg></span>
            </span>
            <span class="kpi-value">{{ number_format($kpis['pending']) }}</span>
            <span class="kpi-label">Pending Approval</span>
            <span class="kpi-note">Awaiting estimation sign-off</span>
        </div>

        <div class="kpi">
            <span class="kpi-top">
                <span class="kpi-icon"><svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-award"/></svg></span>
            </span>
            <span class="kpi-value">AED {{ number_format($kpis['awarded_value'], 2) }}</span>
            <span class="kpi-label">Awarded Value</span>
            <span class="kpi-note">Won opportunities</span>
        </div>
    </section>

    {{-- Quotations Table Card --}}
    <section class="card" id="quotesTable">
        <header class="card-head" style="border-bottom: 1px solid #eee; padding-bottom: 12px;">
            <h2>
                <span class="head-ic">
                    <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-file-text"/></svg>
                </span>
                Quotations
            </h2>
        </header>

        <div style="padding: 14px 18px 0 18px;">
            {{-- Status Tabs with Counts --}}
            <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 14px;">
                @php
                    $tabsList = [
                        'all' => ['label' => 'All', 'count' => $tabCounts['all'] ?? 0],
                        'draft' => ['label' => 'Draft', 'count' => $tabCounts['draft'] ?? 0],
                        'pending' => ['label' => 'Pending Approval', 'count' => $tabCounts['pending'] ?? 0],
                        'approved' => ['label' => 'Approved', 'count' => $tabCounts['approved'] ?? 0],
                        'sent' => ['label' => 'Sent', 'count' => $tabCounts['sent'] ?? 0],
                        'awarded' => ['label' => 'Awarded', 'count' => $tabCounts['awarded'] ?? 0],
                        'awaiting_project' => ['label' => 'Awaiting Project', 'count' => $tabCounts['awaiting_project'] ?? 0],
                    ];
                @endphp
                @foreach($tabsList as $tKey => $tData)
                    @php $isActive = ($tab === $tKey); @endphp
                    <a href="{{ route('quotations.index', array_merge(request()->query(), ['tab' => $tKey])) }}" 
                       style="padding: 5px 12px; border-radius: 20px; font-size: 13px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; font-weight: {{ $isActive ? '600' : '500' }}; background: {{ $isActive ? 'var(--primary-soft, #FFF0F3)' : '#F8FAFC' }}; color: {{ $isActive ? 'var(--primary, #A30D2D)' : '#64748B' }}; border: 1px solid {{ $isActive ? '#FFE4E8' : '#E2E8F0' }}; transition: all 0.15s ease;">
                        <span>{{ $tData['label'] }}</span>
                        <span style="font-size: 11px; padding: 1px 7px; border-radius: 10px; font-weight: 600; background: {{ $isActive ? 'var(--primary, #A30D2D)' : '#E2E8F0' }}; color: {{ $isActive ? '#ffffff' : '#64748B' }};">
                            {{ $tData['count'] }}
                        </span>
                    </a>
                @endforeach
            </div>

            {{-- Search & Export Toolbar --}}
            <form method="GET" action="{{ route('quotations.index') }}" id="quotesSearchForm" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; flex-wrap: wrap; gap: 10px;">
                <input type="hidden" name="tab" value="{{ $tab }}">
                <div style="display: flex; align-items: center; gap: 10px; flex: 1; max-width: 450px;">
                    <label class="search-input" style="flex: 1; display: flex; align-items: center; background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 0 10px; height: 36px;">
                        <svg class="ic sm" style="width: 15px; height: 15px; color: #94a3b8; margin-right: 6px;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><use href="#i-search"/></svg>
                        <input type="search" name="search" value="{{ request('search') }}" placeholder="Search records..." style="border: none; outline: none; background: transparent; width: 100%; font-size: 13px;" onkeydown="if(event.key === 'Enter') this.form.submit()">
                    </label>
                    <span style="font-size: 12.5px; color: var(--muted, #64748B); white-space: nowrap;">{{ $quotations->total() }} records</span>
                </div>

                <div style="display: flex; align-items: center; gap: 8px;">
                    <a class="btn btn-ghost btn-sm" href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" style="border: 1px solid #e2e8f0; height: 34px; padding: 0 12px; font-size: 12px; border-radius: 6px; display: inline-flex; align-items: center; gap: 6px; color: #334155; text-decoration: none;">
                        <svg class="ic xs" style="width: 14px; height: 14px;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><use href="#i-download"/></svg>
                        Export CSV
                    </a>

                    <button class="btn btn-ghost btn-sm" type="button" onclick="window.print()" style="border: 1px solid #e2e8f0; height: 34px; padding: 0 12px; font-size: 12px; border-radius: 6px; display: inline-flex; align-items: center; gap: 6px; color: #334155; cursor: pointer;">
                        <svg class="ic xs" style="width: 14px; height: 14px;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><use href="#i-printer"/></svg>
                        Print
                    </button>

                    <span class="select-wrap mini" style="height: 34px;">
                        <select name="branch_id" onchange="this.form.submit()" style="height: 34px; font-size: 12.5px; border-radius: 6px;">
                            <option value="">All Branches</option>
                            @foreach($branches as $b)
                                <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>{{ $b->short_name }}</option>
                            @endforeach
                        </select>
                    </span>
                </div>
            </form>
        </div>

        <div class="table-wrap page">
            <table class="table">
                <thead>
                    <tr>
                        <th>Quote No</th>
                        <th>Client</th>
                        <th>Subject</th>
                        <th>Branch</th>
                        <th>Format</th>
                        <th>Engineer</th>
                        <th class="num">Total (incl. VAT)</th>
                        <th>Status</th>
                        <th>Quote Date</th>
                        <th class="ta-c">Next Step</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($quotations as $idx => $q)
                        <tr>
                            <td class="strong">
                                <a href="{{ route('quotations.builder', ['id' => $q->id]) }}" style="color: var(--primary); font-weight: 600; text-decoration: none;">
                                    {{ $q->quote_number }}
                                </a>
                            </td>
                            <td>
                                <strong>{{ $q->lead->company_name }}</strong>
                            </td>
                            <td>
                                <span>{{ $q->lead->subject }}</span>
                            </td>
                            <td>{{ $q->branch->short_name ?? $q->branch->name }}</td>
                            <td><span style="font-size: 12.5px; color: var(--muted);">{{ $q->branch->quote_format ?? 'Quote Format 1' }}</span></td>
                            <td>{{ $q->lead->assignedEngineer?->name ?? ($q->preparedBy->name ?? 'Unassigned') }}</td>
                            <td class="num">
                                <b>AED {{ number_format($q->grand_total, 2) }}</b>
                            </td>
                            <td>
                                @php
                                    $qTone = match($q->status) {
                                        'draft' => 'amber',
                                        'pending' => 'purple',
                                        'approved' => 'blue',
                                        'rejected' => 'red',
                                        'sent' => 'pink',
                                        'awarded' => 'primary',
                                        default => 'gray'
                                    };
                                    $qLabel = match($q->status) {
                                        'pending' => 'Pending Approval',
                                        'rejected' => 'Revision Req.',
                                        default => ucfirst($q->status)
                                    };
                                @endphp
                                <span class="pill pill-{{ $qTone }}" title="{{ $q->rejection_reason }}">
                                    <i></i>{{ $qLabel }}
                                </span>
                            </td>
                            <td>{{ $q->quote_date->format('d M Y') }}</td>
                            <td class="ta-c">
                                <div style="display: inline-flex; align-items: center; gap: 6px; justify-content: center;">
                                    @if($q->status === 'approved')
                                        @can('quotes.send')
                                        <button type="button" class="btn btn-soft btn-sm" onclick="openSendModalFor({{ $q->id }}, '{{ $q->quote_number }}', '{{ addslashes($q->lead->company_name) }}', '{{ addslashes($q->lead->subject) }}', '{{ number_format($q->subtotal, 2) }}', '{{ number_format($q->vat_amount, 2) }}', '{{ number_format($q->grand_total, 2) }}', '{{ addslashes($q->lead->email ?? '') }}', '{{ addslashes($q->lead->contact_person ?: ($q->lead->customer_name ?: $q->lead->company_name)) }}')" style="color: var(--primary); background: #FFF0F3; border: 1px solid #FFE2E7; padding: 4px 10px; font-size: 12px; font-weight: 500; border-radius: 6px; display: inline-flex; align-items: center; gap: 5px; cursor: pointer;">
                                            <svg class="ic xs" style="width: 13px; height: 13px;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-send"/></svg>
                                            Send
                                        </button>
                                        @endcan
                                    @elseif(in_array($q->status, ['draft', 'rejected']))
                                        @can('quotes.edit')
                                        <a href="{{ route('quotations.builder', ['id' => $q->id]) }}" class="btn btn-soft btn-sm" style="color: var(--primary); background: #FFF0F3; border: 1px solid #FFE2E7; padding: 4px 10px; font-size: 12px; font-weight: 500; border-radius: 6px; display: inline-flex; align-items: center; gap: 5px; text-decoration: none;">
                                            <svg class="ic xs" style="width: 13px; height: 13px;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-edit"/></svg>
                                            Edit
                                        </a>
                                        @endcan
                                    @elseif($q->status === 'pending')
                                        <a href="{{ route('quotations.builder', ['id' => $q->id]) }}" class="btn btn-soft btn-sm" style="color: var(--primary); background: #FFF0F3; border: 1px solid #FFE2E7; padding: 4px 10px; font-size: 12px; font-weight: 500; border-radius: 6px; display: inline-flex; align-items: center; gap: 5px; text-decoration: none;">
                                            <svg class="ic xs" style="width: 13px; height: 13px;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-check"/></svg>
                                            Review
                                        </a>
                                    @elseif($q->status === 'sent')
                                        @can('quotes.award')
                                        <form action="{{ route('quotations.award', $q) }}" method="POST" style="display:inline;" onsubmit="return confirm('Mark quotation {{ $q->quote_number }} as Awarded?');">
                                            @csrf
                                            <button type="submit" class="btn btn-soft btn-sm" style="color: var(--primary); background: #FFF0F3; border: 1px solid #FFE2E7; padding: 4px 10px; font-size: 12px; font-weight: 500; border-radius: 6px; display: inline-flex; align-items: center; gap: 5px; cursor: pointer;">
                                                <svg class="ic xs" style="width: 13px; height: 13px;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-award"/></svg>
                                                Award
                                            </button>
                                        </form>
                                        @endcan
                                    @elseif($q->status === 'awarded')
                                        @if($q->project)
                                            <a href="{{ route('projects.show', $q->project) }}" class="btn btn-soft btn-sm" style="color: var(--primary); background: #FFF0F3; border: 1px solid #FFE2E7; padding: 4px 10px; font-size: 12px; font-weight: 500; border-radius: 6px; display: inline-flex; align-items: center; gap: 5px; text-decoration: none;">
                                                <svg class="ic xs" style="width: 13px; height: 13px;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-briefcase"/></svg>
                                                Project
                                            </a>
                                        @else
                                            @can('projects.create')
                                            <form action="{{ route('projects.create-from-quote', $q) }}" method="POST" style="display:inline;">
                                                @csrf
                                                <button type="submit" class="btn btn-soft btn-sm" style="color: var(--primary); background: #FFF0F3; border: 1px solid #FFE2E7; padding: 4px 10px; font-size: 12px; font-weight: 500; border-radius: 6px; display: inline-flex; align-items: center; gap: 5px; cursor: pointer;">
                                                    <svg class="ic xs" style="width: 13px; height: 13px;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-briefcase"/></svg>
                                                    Create Project
                                                </button>
                                            </form>
                                            @endcan
                                        @endif
                                    @endif

                                    {{-- Eye View link --}}
                                    <a class="act" href="{{ route('quotations.builder', ['id' => $q->id, 'preview' => 1]) }}" title="Preview Quotation" style="color: #64748B; margin-left: 2px;">
                                        <svg class="ic sm" style="width: 16px; height: 16px;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-eye"/></svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="empty">No quotations found in this tab.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($quotations->hasPages())
        <div class="pager" style="padding: 12px 18px;">
            {{ $quotations->links() }}
        </div>
        @endif
    </section>
</div>

{{-- Create Quotation Modal --}}
<div class="modal-wrap" id="createQuoteModal" style="display: none;" onclick="if(event.target === this) closeCreateQuoteModal()">
    <div class="modal modal-md" role="dialog" aria-modal="true" aria-labelledby="createQuoteModalTitle" style="max-width: 540px;">
        <div class="modal-head">
            <div>
                <h3 id="createQuoteModalTitle">Create Quotation</h3>
                <p>Choose the lead &mdash; the quotation editor opens with its details filled in.</p>
            </div>
            <button class="icon-btn" type="button" onclick="closeCreateQuoteModal()" aria-label="Close">
                <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-x"/></svg>
            </button>
        </div>
        <form method="GET" action="{{ route('quotations.builder') }}">
            <div class="modal-body" style="padding-top: 14px;">
                @if(isset($availableLeads) && $availableLeads->isNotEmpty())
                    <label class="field" style="margin-bottom: 6px;">
                        <span class="field-label">Lead</span>
                        <span class="select-wrap">
                            <select name="lead" id="quoteLeadSelect" required>
                                @foreach($availableLeads as $lead)
                                    <option value="{{ $lead->id }}">
                                        {{ $lead->lead_number }} &middot; {{ $lead->company_name }} &mdash; {{ $lead->subject }}
                                    </option>
                                @endforeach
                            </select>
                        </span>
                    </label>
                @else
                    <div style="padding: 16px; background: #FFF8E6; border: 1px solid #FFE0A3; border-radius: 8px; font-size: 13px; color: #8A5B00;">
                        No active leads found to quote. Please <a href="{{ route('leads.index', ['new' => 1]) }}" style="color: var(--primary); font-weight: 600; text-decoration: underline;">create a lead first</a>.
                    </div>
                @endif
            </div>

            <div class="modal-foot">
                <button class="btn btn-ghost" type="button" onclick="closeCreateQuoteModal()">Cancel</button>
                @if(isset($availableLeads) && $availableLeads->isNotEmpty())
                    <button class="btn btn-primary" type="submit">
                        <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-file-text"/></svg>
                        Open Quotation Editor
                    </button>
                @else
                    <a class="btn btn-primary" href="{{ route('leads.index', ['new' => 1]) }}">
                        <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-plus"/></svg>
                        Create New Lead
                    </a>
                @endif
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function openCreateQuoteModal() {
        var modal = document.getElementById('createQuoteModal');
        if (modal) modal.style.display = 'grid';
    }
    function closeCreateQuoteModal() {
        var modal = document.getElementById('createQuoteModal');
        if (modal) modal.style.display = 'none';
    }

    function openSendModalFor(id, quoteNo, customer, project, subtotal, vat, total, email, contact) {
        var form = document.getElementById('listSendForm');
        if (form) form.action = '/quotations/' + id + '/send';
        document.getElementById('smQuoteNo').textContent = quoteNo;
        document.getElementById('smCustomer').textContent = customer;
        document.getElementById('smProject').textContent = project;
        document.getElementById('smAmount').textContent = 'AED ' + subtotal;
        document.getElementById('smTax').textContent = 'AED ' + vat;
        document.getElementById('smTotal').textContent = 'AED ' + total;
        document.getElementById('smSendToInput').value = email || '';
        document.getElementById('smMessageInput').value = 'Dear ' + (contact || customer) + ',\n\nPlease Find Attached Our Quotation ' + quoteNo + ' For ' + project + '.';
        var modal = document.getElementById('listSendModal');
        if (modal) modal.style.display = 'grid';
    }
    function closeListSendModal() {
        var modal = document.getElementById('listSendModal');
        if (modal) modal.style.display = 'none';
    }
</script>
@endpush

{{-- Send Quotation Modal for List Screen --}}
<div class="modal-wrap" id="listSendModal" style="display: none;" onclick="if(event.target === this) closeListSendModal()">
    <div class="modal modal-md" role="dialog" aria-modal="true" aria-labelledby="listSendModalTitle" style="max-width: 580px;">
        <div class="modal-head">
            <div>
                <h3 id="listSendModalTitle">Send Quotation</h3>
                <p>Review before sending to the customer</p>
            </div>
            <button class="icon-btn" type="button" onclick="closeListSendModal()" aria-label="Close">
                <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-x"/></svg>
            </button>
        </div>
        <form id="listSendForm" method="POST" action="">
            @csrf
            <div class="modal-body" style="padding-top: 14px;">
                {{-- Quotation Summary Card --}}
                <div style="background: #FAFAFA; border: 1px solid #EDEDED; border-radius: 10px; padding: 16px; margin-bottom: 18px;">
                    <div style="display: grid; grid-template-columns: 130px 1fr; row-gap: 12px; column-gap: 16px; font-size: 13px;">
                        <span style="color: var(--muted, #6c757d);">Customer</span>
                        <span style="font-weight: 600; color: #111;" id="smCustomer"></span>

                        <span style="color: var(--muted, #6c757d);">Project</span>
                        <span style="font-weight: 600; color: #111;" id="smProject"></span>

                        <span style="color: var(--muted, #6c757d);">Quote Number</span>
                        <span style="font-weight: 600; color: #111;" id="smQuoteNo"></span>

                        <span style="color: var(--muted, #6c757d);">Amount</span>
                        <span style="font-weight: 600; color: #111;" id="smAmount"></span>

                        <span style="color: var(--muted, #6c757d);">Tax (VAT 5%)</span>
                        <span style="font-weight: 600; color: #111;" id="smTax"></span>

                        <span style="color: var(--muted, #6c757d);">Grand Total</span>
                        <span style="font-weight: 700; color: #111;" id="smTotal"></span>
                    </div>
                </div>

                {{-- Fields: Mode of Share & Output --}}
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px;">
                    <label class="field" style="margin: 0;">
                        <span class="field-label">Mode of Share</span>
                        <span class="select-wrap">
                            <select name="share_mode">
                                <option value="Email" selected>Email</option>
                                <option value="WhatsApp">WhatsApp</option>
                                <option value="Direct Download">Direct Download</option>
                            </select>
                        </span>
                    </label>

                    <label class="field" style="margin: 0;">
                        <span class="field-label">Output</span>
                        <span class="select-wrap">
                            <select name="output_mode">
                                <option value="Total + Tax" selected>Total + Tax</option>
                                <option value="Total without Tax">Total without Tax</option>
                                <option value="Technical (No Pricing)">Technical (No Pricing)</option>
                            </select>
                        </span>
                    </label>
                </div>

                {{-- Send to --}}
                <label class="field" style="margin-bottom: 14px;">
                    <span class="field-label">Send to <span style="color: var(--primary);">*</span></span>
                    <input type="email" name="recipient" id="smSendToInput" required placeholder="name@company.com">
                </label>

                {{-- Message --}}
                <label class="field" style="margin-bottom: 6px;">
                    <span class="field-label">Message</span>
                    <textarea name="message" id="smMessageInput" rows="4" placeholder="Message to customer..."></textarea>
                </label>

                <p style="font-size: 11.5px; color: var(--muted, #71717A); margin: 0 0 8px 0;">Demo: nothing is sent; the quotation status changes to Sent and the share mode is recorded.</p>
            </div>

            <div class="modal-foot">
                <button class="btn btn-ghost" type="button" onclick="closeListSendModal()">Cancel</button>
                <button class="btn btn-primary" type="submit">
                    <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-send"/></svg>
                    Confirm Send
                </button>
            </div>
        </form>
    </div>
</div>
