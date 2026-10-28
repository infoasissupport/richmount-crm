@extends('layouts.app')

@section('title', $project->project_number . ' · Project')
@section('page_id', 'project')

@section('breadcrumbs')
    <svg class="ic sep" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-chevron-right"/></svg>
    <a class="crumb" href="{{ route('projects.index') }}">Projects</a>
    <svg class="ic sep" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-chevron-right"/></svg>
    <span class="crumb current">{{ $project->project_number }}</span>
@endsection

@section('content')
<section class="page-head" id="pageHead">
    <div>
        <h1>{{ $project->project_number }} &bull; {{ $project->name }}</h1>
        <p class="sub">{{ $project->client_name }} &bull; {{ $project->branch->name }}</p>
    </div>

    <div class="page-actions">
        {{-- Status Pill --}}
        <span class="pill pill-{{ $project->statusTone() }}" style="margin-right: 6px;">
            <i></i>{{ $project->statusLabel() }}
        </span>

        {{-- Add PO button if PO is missing --}}
        @if(!$project->hasClientPo() && $project->status !== 'closed')
            @can('projects.open')
            <button class="btn {{ $project->status === 'popending' ? 'btn-primary' : 'btn-ghost' }}" type="button" onclick="openRecordPoModal()">
                <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-file-plus"/></svg>
                Add PO
            </button>
            @endcan
        @endif

        {{-- Start Work --}}
        @if($project->status === 'open')
            @can('projects.edit')
            <form action="{{ route('projects.start-work', $project) }}" method="POST" style="display:inline;">
                @csrf
                <button class="btn btn-primary" type="submit">
                    <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-hard-hat"/></svg>
                    Start Work
                </button>
            </form>
            @endcan
        @endif

        {{-- In Progress actions: Complete Work & Put On Hold --}}
        @if($project->status === 'inprogress')
            @can('projects.edit')
            <button class="btn btn-ghost" type="button" onclick="openHoldModal()">
                <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-clock"/></svg>
                Put On Hold
            </button>
            <button class="btn btn-primary" type="button" onclick="openCompleteModal()">
                <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-check-circle"/></svg>
                Complete Work
            </button>
            @endcan

            @can('invoices.create')
            <button class="btn btn-ghost" type="button" onclick="openInvoiceModal(false)">
                <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-invoice"/></svg>
                Progress Invoice
            </button>
            @endcan
        @endif

        {{-- On Hold action: Resume Work --}}
        @if($project->status === 'onhold')
            @can('projects.edit')
            <form action="{{ route('projects.resume-work', $project) }}" method="POST" style="display:inline;">
                @csrf
                <button class="btn btn-primary" type="submit">
                    <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-refresh"/></svg>
                    Resume Work
                </button>
            </form>
            @endcan
        @endif

        {{-- Completed action: Final Invoice --}}
        @if($project->status === 'completed')
            @can('invoices.create')
            <button class="btn btn-primary" type="button" onclick="openInvoiceModal(true)">
                <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-invoice"/></svg>
                Create Final Invoice
            </button>
            @endcan
        @endif

        {{-- Vendor PO --}}
        <button class="btn btn-soft" type="button" onclick="openVendorPoModal()">
            <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-file-text"/></svg>
            Vendor PO
        </button>

        {{-- Customer Payment Received on Site --}}
        @if(in_array($project->status, ['open', 'inprogress', 'completed']))
        @can('payments.create')
        <button class="btn btn-ghost" type="button" onclick="openCustomerPaymentModal()">
            <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-coins"/></svg>
            Customer Payment
        </button>
        @endcan
        @endif

        {{-- Add Project Expense --}}
        @if($project->canRecordExpenses())
        @can('expenses.create')
        <a class="btn btn-ghost" href="{{ route('expenses.index', ['new' => 1, 'project_id' => $project->id]) }}">
            <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-wallet"/></svg>
            Add Expense
        </a>
        @endcan
        @endif
    </div>
</section>

<div class="dash-scroll">
    {{-- Stepper (7 steps matching prototype) --}}
    @php
        $steps = ['awarded', 'popending', 'open', 'inprogress', 'completed', 'invoicepending', 'closed'];
        $currentStep = $project->status === 'onhold' ? 'inprogress' : $project->status;
        $currentIndex = array_search($currentStep, $steps);
        if ($currentIndex === false) $currentIndex = 1;
    @endphp
    <section class="card status-bar">
        <ol class="stepper wide" id="projStepper">
            <li class="{{ $currentIndex > 0 ? 'done' : ($currentIndex === 0 ? 'current' : '') }}">
                <span>{!! $currentIndex > 0 ? '<svg class="ic xs" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-check"/></svg>' : '1' !!}</span>
                Awarded
            </li>
            <li class="{{ $currentIndex > 1 ? 'done' : ($currentIndex === 1 ? 'current' : '') }}">
                <span>{!! $currentIndex > 1 ? '<svg class="ic xs" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-check"/></svg>' : '2' !!}</span>
                PO Pending
            </li>
            <li class="{{ $currentIndex > 2 ? 'done' : ($currentIndex === 2 ? 'current' : '') }}">
                <span>{!! $currentIndex > 2 ? '<svg class="ic xs" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-check"/></svg>' : '3' !!}</span>
                Open
            </li>
            <li class="{{ $currentIndex > 3 ? 'done' : ($currentIndex === 3 ? ($project->status === 'onhold' ? 'current warn' : 'current') : '') }}">
                <span>{!! $currentIndex > 3 ? '<svg class="ic xs" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-check"/></svg>' : '4' !!}</span>
                In Progress
            </li>
            <li class="{{ $currentIndex > 4 ? 'done' : ($currentIndex === 4 ? 'current' : '') }}">
                <span>{!! $currentIndex > 4 ? '<svg class="ic xs" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-check"/></svg>' : '5' !!}</span>
                Work Completed
            </li>
            <li class="{{ $currentIndex > 5 ? 'done' : ($currentIndex === 5 ? 'current' : '') }}">
                <span>{!! $currentIndex > 5 ? '<svg class="ic xs" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-check"/></svg>' : '6' !!}</span>
                Invoice Pending
            </li>
            <li class="{{ $currentIndex >= 6 ? 'done' : '' }}">
                <span>{!! $currentIndex >= 6 ? '<svg class="ic xs" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-check"/></svg>' : '7' !!}</span>
                Closed
            </li>
        </ol>
    </section>

    {{-- Informative Banner based on status --}}
    @php
        $bannerTone = match($project->status) {
            'popending' => '',
            'open' => '',
            'inprogress' => '',
            'onhold' => 'bad',
            'completed' => '',
            'invoicepending' => '',
            'closed' => 'ok',
            default => '',
        };
        $bannerIcon = match($project->status) {
            'onhold' => '#i-alert',
            'closed' => '#i-check-circle',
            default => '#i-info',
        };
        $bannerMsg = match($project->status) {
            'popending' => 'Project created &mdash; waiting for client PO. The Accountant records the PO to officially open the project.',
            'open' => 'Project is open. Expenses can now be recorded. Start work when site mobilises.',
            'inprogress' => 'Work in progress. Submit the work completion report when the works are handed over.',
            'onhold' => 'Project is on hold: ' . ($project->hold_reason ?? 'Awaiting client instructions.') . ' Expenses are blocked until work resumes.',
            'completed' => 'Work completed &mdash; the Accountant must raise the final invoice.',
            'invoicepending' => 'Final invoice raised &mdash; submit payment receipt to close project.',
            'closed' => 'Project officially closed on ' . ($project->closed_at ? $project->closed_at->format('d M Y') : 'settlement') . ' after final invoice settlement.',
            default => 'Project active.',
        };
    @endphp
    <div class="info-box {{ $bannerTone }}" style="margin: 15px 0;">
        <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="{{ $bannerIcon }}"/></svg>
        <span>{!! $bannerMsg !!}</span>
    </div>

    {{-- Project Stats KPIs --}}
    @php
        $totalInvoiced = $project->invoices->whereNotIn('status', ['draft', 'void'])->sum('grand_total');
        $totalExpenses = $project->expenses->where('status', 'approved')->sum('grand_total');
    @endphp
    <section class="kpi-grid four" id="projStats">
        <div class="kpi static">
            <span class="kpi-top">
                <span class="kpi-icon">
                    <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-file-text"/></svg>
                </span>
            </span>
            <span class="kpi-value">AED {{ number_format($project->contract_value, 2) }}</span>
            <span class="kpi-label">PO / Contract Value</span>
            <span class="kpi-note">{{ $project->client_po_number ? 'PO #' . $project->client_po_number : 'Awaiting PO' }}</span>
        </div>

        <div class="kpi static">
            <span class="kpi-top">
                <span class="kpi-icon">
                    <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-wallet"/></svg>
                </span>
            </span>
            <span class="kpi-value">AED {{ number_format($totalExpenses, 2) }}</span>
            <span class="kpi-label">Project Expenses</span>
            <span class="kpi-note">Petty cash & site expenses</span>
        </div>

        <div class="kpi static">
            <span class="kpi-top">
                <span class="kpi-icon">
                    <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-invoice"/></svg>
                </span>
            </span>
            <span class="kpi-value">AED {{ number_format($totalInvoiced, 2) }}</span>
            <span class="kpi-label">Invoiced</span>
            <span class="kpi-note">{{ $project->contract_value > 0 ? round(($totalInvoiced / $project->contract_value) * 100, 1) . '% billed' : '0%' }}</span>
        </div>

        <div class="kpi static">
            <span class="kpi-top">
                <span class="kpi-icon">
                    <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-clock"/></svg>
                </span>
            </span>
            <span class="kpi-value">{{ $project->invoices->where('status', 'sent')->count() }}</span>
            <span class="kpi-label">Pending Invoices</span>
            <span class="kpi-note">Awaiting client payment</span>
        </div>
    </section>

    {{-- Detail Grid: Left (KV), Right (Description & Docs) --}}
    <div class="detail-grid" style="margin-top: 15px;">
        <section class="card">
            <header class="card-head">
                <h2>
                    <span class="head-ic">
                        <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-briefcase"/></svg>
                    </span>
                    Project Details
                </h2>
                <span class="pill pill-{{ $project->statusTone() }}">{{ $project->statusLabel() }}</span>
            </header>

            <dl class="kv" id="projKv">
                <dt>Project Number</dt>
                <dd><b>{{ $project->project_number }}</b></dd>

                <dt>Customer</dt>
                <dd><b>{{ $project->client_name }}</b></dd>

                <dt>Quotation</dt>
                <dd>
                    @if($project->quotation)
                        <a href="{{ route('quotations.builder', ['id' => $project->quotation->id]) }}" style="color: var(--primary); text-decoration: none; font-weight: 600;">
                            {{ $project->quotation->quote_number }}
                        </a>
                    @else
                        &mdash;
                    @endif
                </dd>

                <dt>Lead</dt>
                <dd>
                    @if($project->lead)
                        <a href="{{ route('leads.show', $project->lead) }}" style="color: var(--primary); text-decoration: none;">
                            {{ $project->lead->lead_number }}
                        </a>
                    @else
                        &mdash;
                    @endif
                </dd>

                <dt>Branch</dt>
                <dd><span class="pill pill-gray">{{ $project->branch->name }}</span></dd>

                <dt>Project Engineer</dt>
                <dd>{{ $project->assignedEngineer?->name ?? 'Unassigned' }}</dd>

                <dt>Client PO Number</dt>
                <dd>{{ $project->client_po_number ?? 'PO Pending' }}</dd>

                <dt>Client PO Date</dt>
                <dd>{{ $project->client_po_date ? $project->client_po_date->format('d M Y') : '&mdash;' }}</dd>

                <dt>Start Date</dt>
                <dd>{{ $project->start_date ? $project->start_date->format('d M Y') : '&mdash;' }}</dd>

                <dt>Work Completed</dt>
                <dd>{{ $project->actual_end_date ? $project->actual_end_date->format('d M Y') : '&mdash;' }}</dd>

                <dt>Closed</dt>
                <dd>{{ $project->closed_at ? $project->closed_at->format('d M Y') : '&mdash;' }}</dd>

                <dt>Zoho Books</dt>
                <dd>{{ $project->zoho_project_id ?? 'Synced' }}</dd>
            </dl>
        </section>

        <section class="card">
            <header class="card-head">
                <h2>
                    <span class="head-ic">
                        <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-file-text"/></svg>
                    </span>
                    Description &amp; Documents
                </h2>
            </header>

            <p class="notes" style="font-size: 12px; margin-bottom: 15px;">
                {{ $project->description ?: 'No detailed scope notes recorded.' }}
            </p>

            @if($project->completion_report)
                <div class="dr-section" style="margin-bottom: 15px; background: #fafafa; padding: 10px; border-radius: 4px; border-left: 3px solid var(--primary);">
                    <h5 style="margin: 0 0 5px 0; font-size: 11px; text-transform: uppercase; color: var(--primary);">Work Completion Report</h5>
                    <p class="notes" style="margin: 0; font-size: 11.5px;">{{ $project->completion_report }}</p>

                    @if($project->latestWorkCompletionCertificate)
                        <div style="margin-top: 8px;">
                            <a class="btn btn-ghost btn-sm" href="{{ route('wcc.pdf', $project->latestWorkCompletionCertificate) }}" target="_blank">
                                <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-download"/></svg>
                                Download WCC PDF ({{ $project->latestWorkCompletionCertificate->certificate_number }})
                            </a>
                        </div>
                    @endif
                </div>
            @endif

            <div class="dr-section">
                <h5 style="font-size: 11px; text-transform: uppercase; color: var(--muted); margin-bottom: 8px;">Documents</h5>
                <ul class="file-list" style="list-style: none; padding: 0; margin: 0; font-size: 11.5px;">
                    @if($project->client_po_file)
                        <li style="display: flex; align-items: center; gap: 8px; padding: 6px 0; border-bottom: 1px solid var(--border);">
                            <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-file-text"/></svg>
                            <a href="{{ asset('storage/' . $project->client_po_file) }}" target="_blank" style="color: var(--primary); text-decoration: none;">
                                Client PO Attachment ({{ $project->client_po_number }})
                            </a>
                        </li>
                    @endif

                    @forelse($project->documents as $doc)
                        <li style="display: flex; align-items: center; gap: 8px; padding: 6px 0; border-bottom: 1px solid var(--border);">
                            <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-file-text"/></svg>
                            <a href="{{ asset('storage/' . $doc->file_path) }}" target="_blank" style="color: var(--text); text-decoration: none;">
                                {{ $doc->file_name }}
                            </a>
                            <small style="color: var(--muted);">({{ $doc->kind }})</small>
                        </li>
                    @empty
                        @if(!$project->client_po_file)
                            <li style="color: var(--muted);">No documents attached yet.</li>
                        @endif
                    @endforelse
                </ul>
            </div>
        </section>
    </div>

    {{-- Purchase Orders Section (Vendor POs) --}}
    <section class="card" style="margin-top: 15px;">
        <header class="card-head">
            <h2>
                <span class="head-ic">
                    <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-file-plus"/></svg>
                </span>
                Vendor Purchase Orders (POs)
            </h2>
            <button class="btn btn-primary btn-sm" type="button" onclick="openVendorPoModal()">
                <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-plus"/></svg>
                New Vendor PO
            </button>
        </header>

        <div class="table-wrap">
            <table class="table" style="font-size: 11.5px;">
                <thead>
                    <tr>
                        <th>PO Number</th>
                        <th>Vendor / Supplier</th>
                        <th>Issue Date</th>
                        <th>Delivery Date</th>
                        <th>Status</th>
                        <th class="num">Amount (AED)</th>
                        <th class="ta-c">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($project->purchaseOrders as $vpo)
                        <tr>
                            <td class="strong">{{ $vpo->po_number }}</td>
                            <td><b>{{ $vpo->vendor_name }}</b></td>
                            <td>{{ $vpo->po_date->format('d M Y') }}</td>
                            <td>{{ $vpo->delivery_date ? $vpo->delivery_date->format('d M Y') : 'Standard' }}</td>
                            <td><span class="pill pill-primary"><i></i>{{ ucfirst($vpo->status) }}</span></td>
                            <td class="num"><b>{{ number_format($vpo->grand_total, 2) }}</b></td>
                            <td class="ta-c">
                                <a class="act view" href="{{ route('po.pdf', $vpo) }}" target="_blank" title="Download PO PDF">
                                    <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-download"/></svg>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="text-align: center; color: var(--muted); padding: 20px;">
                                No vendor purchase orders issued for this project.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    {{-- Project Expenses Section --}}
    <section class="card" id="projExpenses" style="margin-top: 15px;">
        <header class="card-head">
            <h2>
                <span class="head-ic">
                    <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-receipt"/></svg>
                </span>
                Project Expenses
            </h2>
            @if($project->canRecordExpenses())
                @can('expenses.create')
                <a class="btn btn-primary btn-sm" href="{{ route('expenses.index', ['new' => 1, 'project_id' => $project->id]) }}">
                    <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-plus"/></svg>
                    Add Expense
                </a>
                @endcan
            @endif
        </header>

        <div class="table-wrap">
            <table class="table" style="font-size: 11.5px;">
                <thead>
                    <tr>
                        <th>Expense No</th>
                        <th>Date</th>
                        <th>Submitted By</th>
                        <th>Category</th>
                        <th>Vendor / Description</th>
                        <th>Type</th>
                        <th class="num">Amount (AED)</th>
                        <th>Status</th>
                        <th class="ta-c">Receipt</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($project->expenses as $exp)
                        <tr>
                            <td class="strong">{{ $exp->expense_number }}</td>
                            <td>{{ $exp->date->format('d M Y') }}</td>
                            <td>
                                <b>{{ $exp->user->name }}</b>
                                <div style="font-size: 10px; color: var(--muted);">{{ $exp->user->role_label }}</div>
                            </td>
                            <td>{{ ucfirst(str_replace('_', ' ', $exp->category)) }}</td>
                            <td>
                                <strong>{{ $exp->vendor_name }}</strong>
                                @if($exp->description)<div style="font-size: 10.5px; color: var(--muted);">{{ $exp->description }}</div>@endif
                            </td>
                            <td>
                                <span class="pill pill-{{ $exp->type === 'cash' ? 'primary' : 'purple' }}">{{ ucfirst($exp->type) }}</span>
                            </td>
                            <td class="num"><b>{{ number_format($exp->grand_total, 2) }}</b></td>
                            <td>
                                @php
                                    $eTone = match($exp->status) {
                                        'approved' => 'green',
                                        'rejected' => 'red',
                                        default => 'warning'
                                    };
                                @endphp
                                <span class="pill pill-{{ $eTone }}">{{ ucfirst($exp->status) }}</span>
                            </td>
                            <td class="ta-c">
                                @if($exp->receipt_file)
                                    <a class="act view" href="{{ asset('storage/' . $exp->receipt_file) }}" target="_blank" title="View receipt">
                                        <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-file-text"/></svg>
                                    </a>
                                @else
                                    <span style="color: var(--muted);">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" style="text-align: center; color: var(--muted); padding: 20px;">
                                No site expenses recorded for this project yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    {{-- Detail Grid: Invoices & Activity Timeline --}}
    <div class="detail-grid" style="margin-top: 15px;">
        <section class="card" id="projInvoices">
            <header class="card-head">
                <h2>
                    <span class="head-ic">
                        <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-invoice"/></svg>
                    </span>
                    Tax Invoices
                </h2>
                @can('invoices.create')
                <button class="btn btn-primary btn-sm" type="button" onclick="openInvoiceModal(false)">
                    <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-plus"/></svg>
                    New Invoice
                </button>
                @endcan
            </header>

            <div class="table-wrap">
                <table class="table" style="font-size: 11.5px;">
                    <thead>
                        <tr>
                            <th>Invoice No</th>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th class="num">Total (AED)</th>
                            <th class="num">Balance</th>
                            <th class="ta-c">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($project->invoices as $inv)
                            <tr>
                                <td class="strong">{{ $inv->invoice_number }}</td>
                                <td>{{ $inv->invoice_date->format('d M Y') }}</td>
                                <td>{{ $inv->is_final ? 'Final' : 'Progress' }}</td>
                                <td><span class="pill pill-{{ $inv->statusTone() }}">{{ ucfirst($inv->status) }}</span></td>
                                <td class="num"><b>{{ number_format($inv->grand_total, 2) }}</b></td>
                                <td class="num">{{ number_format($inv->balance_due, 2) }}</td>
                                <td class="ta-c" style="display: flex; gap: 4px; justify-content: center;">
                                    <a class="act view" href="{{ route('invoices.pdf', $inv) }}" target="_blank" title="Download Tax Invoice PDF">
                                        <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-download"/></svg>
                                    </a>
                                    @if($inv->balance_due > 0)
                                        @can('payments.create')
                                        <button class="btn btn-soft btn-sm" type="button" onclick="openPaymentModal({{ json_encode($inv) }})" title="Record Payment">
                                            Pay
                                        </button>
                                        @endcan
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" style="text-align: center; color: var(--muted); padding: 20px;">
                                    No invoices raised yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        {{-- Activity Timeline --}}
        <section class="card">
            <header class="card-head">
                <h2>
                    <span class="head-ic">
                        <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-clock"/></svg>
                    </span>
                    Activity Timeline
                </h2>
            </header>

            <ul class="events" id="projTimeline" style="list-style: none; padding: 0 16px 16px 16px; margin: 0;">
                @forelse($project->activities as $act)
                    <li class="ev" style="display: flex; gap: 12px; margin-bottom: 14px; position: relative;">
                        <span class="ev-dot" style="width: 10px; height: 10px; border-radius: 50%; background: var(--primary); margin-top: 5px; flex-shrink: 0;"></span>
                        <div>
                            <strong style="font-size: 12px; display: block;">{{ $act->text }}</strong>
                            <small style="color: var(--muted); font-size: 10.5px;">
                                {{ $act->created_at->diffForHumans() }} &bull; {{ $act->user?->name ?? 'System' }}
                            </small>
                        </div>
                    </li>
                @empty
                    <li style="color: var(--muted); font-size: 12px;">No activity logged yet.</li>
                @endforelse
            </ul>
        </section>
    </div>
</div>

{{-- Record Client PO Modal --}}
<div class="modal-wrap" id="recordPoModal" style="display: none;">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="recordPoTitle">
        <div class="modal-head">
            <div>
                <h3 id="recordPoTitle">Record Client PO</h3>
                <p>Contract Value: AED {{ number_format($project->contract_value, 2) }}</p>
            </div>
            <button class="icon-btn" type="button" onclick="closeRecordPoModal()" aria-label="Close">
                <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-x"/></svg>
            </button>
        </div>

        <form action="{{ route('projects.record-po', $project) }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="modal-body">
                <div class="form-grid">
                    <div class="field span-2">
                        <label class="label req" for="poNum">Client PO / LPO Number</label>
                        <input class="input" id="poNum" name="client_po_number" type="text" required value="{{ $project->client_po_number }}" placeholder="e.g. PO-2026-904">
                    </div>

                    <div class="field">
                        <label class="label req" for="poDate">PO Issue Date</label>
                        <input class="input" id="poDate" name="client_po_date" type="date" required value="{{ $project->client_po_date ? $project->client_po_date->format('Y-m-d') : date('Y-m-d') }}">
                    </div>

                    <div class="field">
                        <label class="label" for="poAmt">PO Amount (AED)</label>
                        <input class="input" id="poAmt" name="client_po_amount" type="number" step="0.01" value="{{ $project->client_po_amount ?: $project->contract_value }}">
                    </div>

                    <div class="field span-2">
                        <label class="label" for="poFile">Attach Signed PO Copy</label>
                        <input class="input" id="poFile" name="po_file" type="file" accept=".pdf,.png,.jpg,.jpeg">
                    </div>
                </div>
            </div>

            <div class="modal-foot">
                <button class="btn btn-ghost" type="button" onclick="closeRecordPoModal()">Cancel</button>
                <button class="btn btn-primary" type="submit">
                    <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-check"/></svg>
                    Save & Open Project
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Complete Work Modal --}}
<div class="modal-wrap" id="completeModal" style="display: none;">
    <div class="modal modal-lg" role="dialog" aria-modal="true" aria-labelledby="completeTitle">
        <div class="modal-head">
            <div>
                <h3 id="completeTitle">Work Completion &amp; Handover Certificate</h3>
                <p>Generate formal Work Completion Certificate (WCC)</p>
            </div>
            <button class="icon-btn" type="button" onclick="closeCompleteModal()" aria-label="Close">
                <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-x"/></svg>
            </button>
        </div>

        <form action="{{ route('projects.complete-work', $project) }}" method="POST">
            @csrf
            <div class="modal-body">
                <div class="form-grid">
                    <div class="field">
                        <label class="label req" for="wccDate">Handover / Completion Date</label>
                        <input class="input" id="wccDate" name="completion_date" type="date" required value="{{ date('Y-m-d') }}">
                    </div>

                    <div class="field">
                        <label class="label" for="retPct">Retention Percentage (%)</label>
                        <input class="input" id="retPct" name="retention_pct" type="number" step="0.5" value="10.0">
                    </div>

                    <div class="field">
                        <label class="label" for="clientRep">Client Representative Name</label>
                        <input class="input" id="clientRep" name="client_representative_name" type="text" placeholder="e.g. Eng. Tariq Al Nuaimi">
                    </div>

                    <div class="field">
                        <label class="label" for="clientTitle">Client Representative Designation</label>
                        <input class="input" id="clientTitle" name="client_representative_title" type="text" placeholder="e.g. Senior Project Consultant">
                    </div>

                    <div class="field span-2">
                        <label class="label req" for="wccDesc">Completion Scope Summary &amp; Remarks</label>
                        <textarea class="textarea" id="wccDesc" name="work_description" rows="3" required placeholder="All contracted scope executed in accordance with authority approvals and client sign-off."></textarea>
                    </div>
                </div>
            </div>

            <div class="modal-foot">
                <button class="btn btn-ghost" type="button" onclick="closeCompleteModal()">Cancel</button>
                <button class="btn btn-primary" type="submit">
                    <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-check-circle"/></svg>
                    Issue Certificate & Complete
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Put On Hold Modal --}}
<div class="modal-wrap" id="holdModal" style="display: none;">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="holdTitle">
        <div class="modal-head">
            <div>
                <h3 id="holdTitle">Put Project On Hold</h3>
                <p>Site expenses will be blocked until resumed</p>
            </div>
            <button class="icon-btn" type="button" onclick="closeHoldModal()" aria-label="Close">
                <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-x"/></svg>
            </button>
        </div>

        <form action="{{ route('projects.put-on-hold', $project) }}" method="POST">
            @csrf
            <div class="modal-body">
                <div class="field">
                    <label class="label req" for="holdReason">Reason for Hold</label>
                    <textarea class="textarea" id="holdReason" name="reason" rows="3" required placeholder="e.g. Awaiting client revised architectural layout approvals..."></textarea>
                </div>
            </div>

            <div class="modal-foot">
                <button class="btn btn-ghost" type="button" onclick="closeHoldModal()">Cancel</button>
                <button class="btn btn-primary" type="submit" style="background: var(--danger, #dc2626);">
                    Put On Hold
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Raise Invoice Modal --}}
<div class="modal-wrap" id="invoiceModal" style="display: none;">
    <div class="modal modal-lg" role="dialog" aria-modal="true" aria-labelledby="invoiceTitle">
        <div class="modal-head">
            <div>
                <h3 id="invoiceTitle">Raise Tax Invoice</h3>
                <p>Generate official UAE VAT invoice</p>
            </div>
            <button class="icon-btn" type="button" onclick="closeInvoiceModal()" aria-label="Close">
                <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-x"/></svg>
            </button>
        </div>

        <form action="{{ route('invoices.store', $project) }}" method="POST">
            @csrf
            <input type="hidden" id="invIsFinal" name="is_final" value="0">
            <div class="modal-body">
                <div class="form-grid">
                    <div class="field">
                        <label class="label req" for="invDate">Invoice Date</label>
                        <input class="input" id="invDate" name="invoice_date" type="date" required value="{{ date('Y-m-d') }}">
                    </div>

                    <div class="field">
                        <label class="label req" for="invDue">Payment Due Date</label>
                        <input class="input" id="invDue" name="due_date" type="date" required value="{{ date('Y-m-d', strtotime('+30 days')) }}">
                    </div>

                    <div class="field">
                        <label class="label" for="invTrn">Client TRN</label>
                        <input class="input" id="invTrn" name="client_trn" type="text" placeholder="100 ...">
                    </div>

                    <div class="field">
                        <label class="label req" for="invAmt">Taxable Amount (AED)</label>
                        <input class="input" id="invAmt" name="amount" type="number" step="0.01" min="1" required value="{{ $project->contract_value }}">
                    </div>

                    <div class="field span-2">
                        <label class="label" for="invNotes">Payment Terms & Remarks</label>
                        <textarea class="textarea" id="invNotes" name="notes" rows="2" placeholder="Payment within 30 days via bank transfer..."></textarea>
                    </div>
                </div>
            </div>

            <div class="modal-foot">
                <button class="btn btn-ghost" type="button" onclick="closeInvoiceModal()">Cancel</button>
                <button class="btn btn-primary" type="submit">
                    <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-check"/></svg>
                    Generate Tax Invoice
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Vendor PO Modal --}}
<div class="modal-wrap" id="vendorPoModal" style="display: none;">
    <div class="modal modal-lg" role="dialog" aria-modal="true" aria-labelledby="vendorPoTitle">
        <div class="modal-head">
            <div>
                <h3 id="vendorPoTitle">Create Vendor Purchase Order</h3>
                <p>Issue material/subcontractor order for {{ $project->project_number }}</p>
            </div>
            <button class="icon-btn" type="button" onclick="closeVendorPoModal()" aria-label="Close">
                <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-x"/></svg>
            </button>
        </div>

        <form action="{{ route('po.store', $project) }}" method="POST">
            @csrf
            <div class="modal-body">
                <div class="form-grid">
                    <div class="field">
                        <label class="label req" for="vName">Vendor / Supplier Name</label>
                        <input class="input" id="vName" name="vendor_name" type="text" required placeholder="e.g. Saint-Gobain Gyproc Middle East">
                    </div>

                    <div class="field">
                        <label class="label" for="vTrn">Vendor TRN</label>
                        <input class="input" id="vTrn" name="vendor_trn" type="text" placeholder="100 ...">
                    </div>

                    <div class="field">
                        <label class="label" for="vPoDate">PO Date</label>
                        <input class="input" id="vPoDate" name="po_date" type="date" value="{{ date('Y-m-d') }}">
                    </div>

                    <div class="field">
                        <label class="label" for="vDelivery">Target Delivery Date</label>
                        <input class="input" id="vDelivery" name="delivery_date" type="date">
                    </div>

                    <div class="field span-2">
                        <label class="label req" for="itDesc">Item Description</label>
                        <input class="input" id="itDesc" name="items[0][description]" type="text" required placeholder="Supply of gypsum acoustic panels 1200x600mm">
                    </div>

                    <div class="field">
                        <label class="label req" for="itQty">Quantity</label>
                        <input class="input" id="itQty" name="items[0][quantity]" type="number" step="0.01" min="1" required value="100">
                    </div>

                    <div class="field">
                        <label class="label req" for="itPrice">Unit Price (AED)</label>
                        <input class="input" id="itPrice" name="items[0][unit_price]" type="number" step="0.01" min="0" required value="50">
                    </div>
                </div>
            </div>

            <div class="modal-foot">
                <button class="btn btn-ghost" type="button" onclick="closeVendorPoModal()">Cancel</button>
                <button class="btn btn-primary" type="submit">
                    <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-check"/></svg>
                    Issue Purchase Order
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Record Payment Modal --}}
<div class="modal-wrap" id="paymentModal" style="display: none;">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="paymentTitle">
        <div class="modal-head">
            <div>
                <h3 id="paymentTitle">Record Customer Payment</h3>
                <p id="paymentSub">Apply payment to tax invoice</p>
            </div>
            <button class="icon-btn" type="button" onclick="closePaymentModal()" aria-label="Close">
                <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-x"/></svg>
            </button>
        </div>

        <form id="paymentForm" action="" method="POST">
            @csrf
            <div class="modal-body">
                <div class="form-grid">
                    <div class="field span-2">
                        <label class="label req" for="payAmt">Payment Amount (AED)</label>
                        <input class="input" id="payAmt" name="amount" type="number" step="0.01" min="1" required>
                    </div>

                    <div class="field">
                        <label class="label req" for="payDate">Payment Date</label>
                        <input class="input" id="payDate" name="payment_date" type="date" required value="{{ date('Y-m-d') }}">
                    </div>

                    <div class="field">
                        <label class="label req" for="payMethod">Payment Method</label>
                        <select class="select" id="payMethod" name="payment_method" required>
                            <option value="bank_transfer">Bank Transfer (Wire)</option>
                            <option value="cheque">Cheque</option>
                            <option value="cash">Cash</option>
                        </select>
                    </div>

                    <div class="field span-2">
                        <label class="label" for="payBankRef">Bank Reference / Cheque No</label>
                        <input class="input" id="payBankRef" name="bank_reference" type="text" placeholder="e.g. ENBD-TRF-90214">
                    </div>
                </div>
            </div>

            <div class="modal-foot">
                <button class="btn btn-ghost" type="button" onclick="closePaymentModal()">Cancel</button>
                <button class="btn btn-primary" type="submit">
                    <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-check"/></svg>
                    Record Payment
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openRecordPoModal() {
        document.getElementById('recordPoModal').style.display = 'grid';
    }
    function closeRecordPoModal() {
        document.getElementById('recordPoModal').style.display = 'none';
    }

    function openCompleteModal() {
        document.getElementById('completeModal').style.display = 'grid';
    }
    function closeCompleteModal() {
        document.getElementById('completeModal').style.display = 'none';
    }

    function openHoldModal() {
        document.getElementById('holdModal').style.display = 'grid';
    }
    function closeHoldModal() {
        document.getElementById('holdModal').style.display = 'none';
    }

    function openInvoiceModal(isFinal) {
        document.getElementById('invIsFinal').value = isFinal ? '1' : '0';
        document.getElementById('invoiceTitle').textContent = isFinal ? 'Raise Final Tax Invoice' : 'Raise Progress Tax Invoice';
        document.getElementById('invoiceModal').style.display = 'grid';
    }
    function closeInvoiceModal() {
        document.getElementById('invoiceModal').style.display = 'none';
    }

    function openVendorPoModal() {
        document.getElementById('vendorPoModal').style.display = 'grid';
    }
    function closeVendorPoModal() {
        document.getElementById('vendorPoModal').style.display = 'none';
    }

    function openPaymentModal(invoice) {
        document.getElementById('paymentTitle').textContent = 'Record Payment · ' + invoice.invoice_number;
        document.getElementById('paymentSub').textContent = 'Balance Due: AED ' + (invoice.balance_due || 0).toLocaleString();
        document.getElementById('paymentForm').action = '/invoices/' + invoice.id + '/payment';
        document.getElementById('payAmt').value = invoice.balance_due || '';
        document.getElementById('paymentModal').style.display = 'grid';
    }
    function closePaymentModal() {
        document.getElementById('paymentModal').style.display = 'none';
    }
</script>
@endsection
