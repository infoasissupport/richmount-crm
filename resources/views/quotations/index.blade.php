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
    @include('partials.kpi-cards', ['kpis' => $kpiCards, 'gridClass' => 'four'])

    {{-- Quotations Table Card (same header, filters and cell design as Lead Management) --}}
    <section class="card fill" id="quotesTable">
        <header class="card-head recent-head">
            <h2>Quotations</h2>
            <div class="head-actions">
                <form method="GET" action="{{ route('quotations.index') }}" class="recent-filters">
                    <label class="search-input recent-search">
                        <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-search"/></svg>
                        <input type="search" name="search" value="{{ request('search') }}" placeholder="Search by quote no, client, subject..." aria-label="Search quotations" onchange="this.form.submit()">
                    </label>

                    {{-- the status dropdown replaces the old tabs (same "tab" filter, with counts) --}}
                    <span class="select-wrap mini with-icon">
                        <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-filter"/></svg>
                        <select name="tab" aria-label="Filter by status" onchange="this.form.submit()">
                            @foreach([
                                'all' => 'All Status',
                                'draft' => 'Draft',
                                'pending' => 'Pending Approval',
                                'approved' => 'Approved',
                                'rejected' => 'Revision Req.',
                                'sent' => 'Sent',
                                'awarded' => 'Awarded',
                                'awaiting_project' => 'Awaiting Project',
                            ] as $tKey => $tLabel)
                                <option value="{{ $tKey }}" {{ $tab === $tKey ? 'selected' : '' }}>{{ $tLabel }} ({{ $tabCounts[$tKey] ?? 0 }})</option>
                            @endforeach
                        </select>
                    </span>

                    <span class="select-wrap mini with-icon">
                        <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-calendar"/></svg>
                        <select name="period" aria-label="Date period" onchange="this.form.submit()">
                            <option value="month" {{ $period === 'month' ? 'selected' : '' }}>This Month</option>
                            <option value="7" {{ $period === '7' ? 'selected' : '' }}>Last 7 Days</option>
                            <option value="30" {{ $period === '30' ? 'selected' : '' }}>Last 30 Days</option>
                            <option value="year" {{ $period === 'year' ? 'selected' : '' }}>This Year</option>
                            <option value="all" {{ $period === 'all' ? 'selected' : '' }}>All Time</option>
                        </select>
                    </span>
                </form>
            </div>
        </header>

        <div class="table-wrap page">
            <table class="table cards" id="quotesList">
                <thead>
                    <tr>
                        <th>Sl No</th>
                        <th>Quote No</th>
                        <th>Client</th>
                        <th>Project</th>
                        <th>Engineer</th>
                        <th class="num">Total (AED)</th>
                        <th>Status</th>
                        <th>
                            Date
                            <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-arrow-down"/></svg>
                        </th>
                        <th class="ta-c">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($quotations as $q)
                        @php
                            $qLead = $q->lead;
                            $client = $qLead?->company_name ?: '—';
                            $engineerName = $qLead?->assignedEngineer?->name ?? $q->preparedBy?->name;
                            $qTone = match ($q->status) {
                                'draft' => 'amber',
                                'pending' => 'purple',
                                'approved' => 'blue',
                                'rejected' => 'red',
                                'sent' => 'pink',
                                'awarded' => 'primary',
                                default => 'gray',
                            };
                            $qLabel = match ($q->status) {
                                'pending' => 'Pending Approval',
                                'rejected' => 'Revision Req.',
                                default => ucfirst($q->status),
                            };
                            // data for the shared preview popup (quotations.partials.preview-modal)
                            $quotePreview = [
                                'number' => $q->quote_number,
                                'doc' => route('quotations.document', $q),
                                'pdf' => route('quotations.pdf', $q),
                                'output' => $q->output_type ?: 'tax',
                            ];
                        @endphp
                        <tr>
                            <td class="dt-no" data-label="Sl No">{{ $quotations->firstItem() + $loop->index }}</td>
                            <td data-label="Quote No">{{ $q->quote_number }}</td>
                            <td class="strong" data-label="Client" title="{{ $client }}">
                                <span class="cell-who">
                                    <span class="avatar sm av-{{ \App\Support\Avatar::tone($client) }}" aria-hidden="true">{{ \App\Support\Avatar::initials($client) }}</span>
                                    <span class="cell-text">
                                        {{ $client }}
                                        @if($qLead?->contact_person)<small>Attn: {{ $qLead->contact_person }}</small>@endif
                                    </span>
                                </span>
                            </td>
                            <td data-label="Project" title="{{ $qLead?->subject }}">
                                <span class="cell-text">
                                    {{ $qLead?->subject ?: '—' }}
                                    @if($qLead)<small>{{ $qLead->departmentLabel() }}</small>@endif
                                </span>
                            </td>
                            <td data-label="Engineer">
                                @if($engineerName)
                                    <span class="cell-who">
                                        <span class="avatar sm av-{{ \App\Support\Avatar::tone($engineerName) }}" aria-hidden="true">{{ \App\Support\Avatar::initials($engineerName) }}</span>
                                        <span class="cell-text">
                                            {{ $engineerName }}
                                            <small>Project Engineer</small>
                                        </span>
                                    </span>
                                @else
                                    <span style="color: var(--muted); font-size: 11px;">Unassigned</span>
                                @endif
                            </td>
                            <td class="num" data-label="Total (AED)"><b>{{ number_format($q->grand_total, 2) }}</b></td>
                            <td data-label="Status">
                                <span class="pill pill-{{ $qTone }}" title="{{ $q->rejection_reason }}"><i></i>{{ $qLabel }}</span>
                            </td>
                            <td data-label="Date">
                                <span class="cell-date">
                                    <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-calendar"/></svg>
                                    {{ $q->quote_date->format('d M Y') }}
                                </span>
                            </td>
                            <td class="ta-c" data-label="Action">
                                <span class="row-actions">
                                    <a class="act view" href="{{ route('quotations.builder', ['id' => $q->id, 'preview' => 1]) }}" title="View Quotation"
                                       aria-label="View {{ $q->quote_number }}" data-quote-preview='@json($quotePreview)'>
                                        <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-eye"/></svg>
                                    </a>
                                    <a class="act edit" href="{{ route('quotations.builder', ['id' => $q->id]) }}" title="Edit Quotation" aria-label="Edit {{ $q->quote_number }}"
                                       data-edit-quote="{{ route('quotations.builder', ['id' => $q->id, 'embed' => 1]) }}" data-quote-number="{{ $q->quote_number }}">
                                        <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-edit"/></svg>
                                    </a>
                                    <button type="button" class="act" data-row-menu title="More" aria-label="More actions for {{ $q->quote_number }}" aria-haspopup="menu" aria-expanded="false">
                                        <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-more-vertical"/></svg>
                                    </button>
                                    <div class="popover row-menu" role="menu" hidden>
                                        <a href="{{ route('quotations.builder', ['id' => $q->id]) }}" class="pop-item" role="menuitem">
                                            <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-file-text"/></svg>
                                            <span>Open in builder</span>
                                        </a>
                                        <a href="{{ route('quotations.pdf', $q) }}" class="pop-item" role="menuitem">
                                            <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-download"/></svg>
                                            <span>Download PDF</span>
                                        </a>

                                        {{-- next workflow step for this status --}}
                                        @if($q->status === 'approved')
                                            @can('quotes.send')
                                            <div class="pop-div"></div>
                                            <button type="button" class="pop-item" role="menuitem" onclick="closeRowMenu(); openSendModalFor({{ $q->id }}, '{{ $q->quote_number }}', '{{ addslashes($client) }}', '{{ addslashes($qLead?->subject ?? '') }}', '{{ number_format($q->subtotal, 2) }}', '{{ number_format($q->vat_amount, 2) }}', '{{ number_format($q->grand_total, 2) }}', '{{ addslashes($qLead?->email ?? '') }}', '{{ addslashes($qLead?->contact_person ?: ($qLead?->customer_name ?: $client)) }}')">
                                                <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-send"/></svg>
                                                <span>Send to Client</span>
                                            </button>
                                            @endcan
                                        @elseif($q->status === 'pending')
                                            <div class="pop-div"></div>
                                            <a href="{{ route('quotations.builder', ['id' => $q->id]) }}" class="pop-item" role="menuitem">
                                                <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-check"/></svg>
                                                <span>Review &amp; Approve</span>
                                            </a>
                                        @elseif($q->status === 'sent')
                                            @can('quotes.award')
                                            <div class="pop-div"></div>
                                            <form action="{{ route('quotations.award', $q) }}" method="POST" onsubmit="return confirm('Mark quotation {{ $q->quote_number }} as Awarded?');">
                                                @csrf
                                                <button type="submit" class="pop-item" role="menuitem">
                                                    <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-award"/></svg>
                                                    <span>Mark as Awarded</span>
                                                </button>
                                            </form>
                                            @endcan
                                        @elseif($q->status === 'awarded')
                                            @if($q->project)
                                                <div class="pop-div"></div>
                                                <a href="{{ route('projects.show', $q->project) }}" class="pop-item" role="menuitem">
                                                    <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-briefcase"/></svg>
                                                    <span>Open Project</span>
                                                </a>
                                            @else
                                                @can('projects.create')
                                                <div class="pop-div"></div>
                                                <form action="{{ route('projects.create-from-quote', $q) }}" method="POST" onsubmit="return confirm('Create a project from quotation {{ $q->quote_number }}?');">
                                                    @csrf
                                                    <button type="submit" class="pop-item" role="menuitem">
                                                        <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-briefcase"/></svg>
                                                        <span>Create Project</span>
                                                    </button>
                                                </form>
                                                @endcan
                                            @endif
                                        @endif
                                    </div>
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="empty">No quotations found for these filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($quotations->total() > 0)
            {{ $quotations->withQueryString()->links() }}
        @endif
    </section>
</div>

@include('quotations.partials.preview-modal')
@include('leads.partials.row-tools')

@include('quotations.partials.create-modal')

{{-- Edit Quotation popup: the full builder (?embed=1) in a frame; the list refreshes on close if anything was saved --}}
<div class="modal-wrap" id="quoteEditModal" style="display: none;" onclick="if (event.target === this) closeQuoteEdit()">
    <div class="modal quote-edit" role="dialog" aria-modal="true" aria-labelledby="quoteEditTitle">
        <div class="modal-head">
            <div>
                <h3 id="quoteEditTitle">Edit Quotation</h3>
            </div>
            <div class="quote-edit-actions">
                <a class="btn btn-ghost btn-sm" id="quoteEditFull" href="#" title="Open the quotation on its own page">
                    <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-arrow-right"/></svg>
                    Open full page
                </a>
                <button class="icon-btn" type="button" onclick="closeQuoteEdit()" aria-label="Close">
                    <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-x"/></svg>
                </button>
            </div>
        </div>
        <iframe id="quoteEditFrame" class="quote-edit-frame" title="Edit quotation"></iframe>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Edit Quotation popup (pencil): loads the builder in embed mode; each extra page load inside the frame
    // means an action ran (save, submit, approve…), so the list is refreshed when the popup closes
    var quoteEditLoads = 0;
    function openQuoteEdit(link) {
        quoteEditLoads = 0;
        document.getElementById('quoteEditTitle').textContent = 'Edit Quotation · ' + link.dataset.quoteNumber;
        document.getElementById('quoteEditFull').href = link.href;
        document.getElementById('quoteEditFrame').src = link.dataset.editQuote;
        var m = document.getElementById('quoteEditModal');
        m.style.display = 'flex';
        m.classList.add('open');
    }
    function closeQuoteEdit() {
        var m = document.getElementById('quoteEditModal');
        m.classList.remove('open');
        m.style.display = 'none';
        document.getElementById('quoteEditFrame').src = 'about:blank';
        if (quoteEditLoads > 1) location.reload();
    }
    document.getElementById('quoteEditFrame').addEventListener('load', function () {
        if (this.src && this.src !== 'about:blank' && !this.src.endsWith('about:blank')) quoteEditLoads++;
    });
    document.addEventListener('click', function (e) {
        var link = e.target.closest('[data-edit-quote]');
        // Ctrl / middle click keeps opening the full builder page in a new tab
        if (!link || e.ctrlKey || e.metaKey || e.shiftKey || e.button !== 0) return;
        e.preventDefault();
        openQuoteEdit(link);
    });
    document.addEventListener('keydown', function (e) {
        var m = document.getElementById('quoteEditModal');
        if (e.key === 'Escape' && m.style.display !== 'none') closeQuoteEdit();
    });

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
