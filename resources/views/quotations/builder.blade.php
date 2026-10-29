{{-- ?embed=1: shown inside the Quotations list's edit popup (layouts.embed, no sidebar / top bar / tabs bar) --}}
@extends(request()->boolean('embed') ? 'layouts.embed' : 'layouts.app')

@section('title', ($quote ? $quote->quote_number : 'New Quotation') . ' · Builder')
@section('page_id', 'quote')

@section('breadcrumbs')
    <svg class="ic sep" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-chevron-right"/></svg>
    <a class="crumb" href="{{ route('quotations.index') }}">Quotations</a>
    <svg class="ic sep" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-chevron-right"/></svg>
    <span class="crumb current">{{ $quote ? $quote->quote_number : 'New Draft' }}</span>
@endsection

@section('content')
@php
    $statusTone = match($quote?->status) {
        'draft' => 'amber',
        'pending' => 'purple',
        'approved' => 'blue',
        'rejected' => 'red',
        'sent' => 'pink',
        'awarded' => 'primary',
        default => 'gray'
    };
    $statusLabel = match($quote?->status) {
        'pending' => 'Pending Approval',
        'rejected' => 'Revision Req.',
        default => ucfirst($quote?->status ?? 'Draft')
    };
@endphp

<section class="page-head" id="pageHead">
    <div>
        <h1>{{ $quote ? 'Quotation ' . $quote->quote_number : 'New Quotation' }}</h1>
        <p class="sub" style="text-transform: capitalize;">{{ $lead->company_name }} &middot; {{ $lead->subject }}</p>
    </div>
    <div class="page-actions" style="display: flex; align-items: center; gap: 8px;">
        @if($quote)
            <span class="pill pill-{{ $statusTone }}" style="margin-right: 4px;">
                <i></i>{{ $statusLabel }}
            </span>

            <button class="btn btn-ghost" type="button" onclick="openQuotePreviewModal()">
                <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-eye"/></svg>
                Preview
            </button>

            @can('quotes.create')
            @if($quote)
            <button class="btn btn-ghost" type="button" onclick="openCloneModal()">
                <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-clipboard"/></svg>
                Clone
            </button>
            @endif
            @endcan

            {{-- Workflow Buttons based on status & role --}}
            @if(in_array($quote->status, ['draft', 'rejected']))
                <button class="btn btn-ghost" type="button" onclick="submitQuoteForm()">
                    <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-bookmark"/></svg>
                    Save Draft
                </button>
                @can('quotes.submit')
                {{-- saves the form first, so edits made since the last save are submitted too --}}
                <button class="btn btn-primary" type="button" onclick="openSubmitConfirm()">
                    <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-send"/></svg>
                    Submit for Approval
                </button>
                @endcan
            @elseif($quote->status === 'pending')
                @can('quotes.approve')
                <button class="btn btn-ghost" type="button" onclick="openRejectModal()" style="color: #DC2626; border-color: #FCA5A5;">
                    <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-x"/></svg>
                    Reject
                </button>
                <button class="btn btn-primary" type="button" onclick="openApproveModal()">
                    <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-check"/></svg>
                    Approve
                </button>
                @endcan
            @elseif($quote->status === 'approved')
                @can('quotes.edit')
                <button class="btn btn-ghost" type="button" onclick="openReopenModal()">
                    <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-edit"/></svg>
                    Edit
                </button>
                @endcan
                @can('quotes.send')
                <button class="btn btn-primary" type="button" onclick="openSendModal()">
                    <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-send"/></svg>
                    Send Quotation
                </button>
                @endcan
            @elseif($quote->status === 'sent')
                @can('quotes.edit')
                <button class="btn btn-ghost" type="button" onclick="openReopenModal()">
                    <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-edit"/></svg>
                    Edit
                </button>
                @endcan
                @can('quotes.award')
                <button class="btn btn-primary" type="button" onclick="openAwardModal()">
                    <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-award"/></svg>
                    Mark as Awarded
                </button>
                @endcan
            @elseif($quote->status === 'awarded')
                @if($quote->project)
                    <a href="{{ route('projects.show', $quote->project) }}" class="btn btn-primary">
                        <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-briefcase"/></svg>
                        Open Project
                    </a>
                @else
                    @can('projects.create')
                    <button class="btn btn-primary" type="button" onclick="openCreateProjectModal()">
                        <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-briefcase"/></svg>
                        Create Project
                    </button>
                    @endcan
                @endif
            @endif
        @else
            {{-- new quotation: Save Draft creates it; Submit for Approval creates it and submits it in one go --}}
            <span class="pill pill-amber" style="margin-right: 4px;"><i></i>New draft</span>
            <button class="btn btn-ghost" type="button" onclick="submitQuoteForm()">
                <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-bookmark"/></svg>
                Save Draft
            </button>
            <button class="btn btn-ghost" type="button" onclick="openQuotePreviewModal()">
                <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-eye"/></svg>
                Preview
            </button>
            @can('quotes.submit')
            <button class="btn btn-primary" type="button" onclick="openSubmitConfirm()">
                <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-send"/></svg>
                Submit for Approval
            </button>
            @endcan
        @endif
    </div>
</section>

<div class="dash-scroll q-builder-scroll">
    {{-- Open Quotation Workspace Tabs (not in the edit popup) --}}
    @unless(request()->boolean('embed'))
    <div class="ws-tabs-bar" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0; gap: 8px;">
        <div style="display: flex; align-items: center; gap: 10px; overflow-x: auto; flex: 1;">
            <span style="display: inline-flex; align-items: center; gap: 5px; font-size: 12px; color: var(--muted, #64748B); white-space: nowrap;">
                <svg class="ic xs" style="width: 14px; height: 14px;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><use href="#i-file-text"/></svg>
                Open quotations
            </span>
            @if($quote)
                <div class="ws-tab active" style="padding: 4px 10px; border-radius: 6px; font-size: 12px; display: inline-flex; align-items: center; gap: 8px; background: #fff; border: 1.5px solid var(--primary, #A30D2D); color: var(--primary, #A30D2D); font-weight: 500;">
                    <span>{{ $quote->quote_number }} - {{ $quote->lead->company_name }}</span>
                    <a href="{{ route('quotations.index') }}" title="Close" style="color: var(--primary); text-decoration: none; font-size: 13px; line-height: 1; opacity: 0.8;">&times;</a>
                </div>
            @endif
            @foreach($openQuotes ?? [] as $oq)
                @if(!$quote || $quote->id !== $oq->id)
                    <a class="ws-tab" href="{{ route('quotations.builder', ['id' => $oq->id]) }}" style="padding: 4px 10px; border-radius: 6px; font-size: 12px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; background: #f8fafc; border: 1px solid #e2e8f0; color: #475569;">
                        <span>{{ $oq->quote_number }}</span>
                        <small style="color: #94a3b8;">({{ $oq->lead->company_name }})</small>
                    </a>
                @endif
            @endforeach
        </div>
    </div>
    @endunless

    {{-- Workflow Status Stepper --}}
    @php
        $st = $quote ? $quote->status : 'draft';
        $order = ['draft' => 1, 'pending' => 2, 'approved' => 3, 'sent' => 4, 'awarded' => 5];
        $curr = $order[$st] ?? 1;
    @endphp
    <section class="card status-bar">
        <ol class="stepper wide" id="qStepper">
            <li class="{{ $curr > 1 ? 'done' : ($curr == 1 ? 'current' : '') }}"><span>1</span>Draft</li>
            <li class="{{ $curr > 2 ? 'done' : ($curr == 2 ? 'current' : '') }}"><span>2</span>Pending Approval</li>
            <li class="{{ $curr > 3 ? 'done' : ($curr == 3 ? 'current' : '') }}"><span>3</span>Approved</li>
            <li class="{{ $curr > 4 ? 'done' : ($curr == 4 ? 'current' : '') }}"><span>4</span>Sent</li>
            <li class="{{ $curr >= 5 ? 'done' : '' }}"><span>5</span>Awarded</li>
        </ol>
        <div style="display: flex; align-items: center; gap: 10px;">
            @if($quote && in_array($quote->status, ['pending', 'approved', 'sent', 'awarded']))
                <span style="font-size: 12px; color: var(--muted, #64748B); display: inline-flex; align-items: center; gap: 5px;">
                    <svg class="ic xs" style="width: 13px; height: 13px;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><use href="#i-bookmark"/></svg>
                    Read-only &mdash; {{ $statusLabel }}
                </span>
            @else
                <div class="autosave" id="autosave" aria-live="polite" style="font-size: 11px; color: #888;">
                    @if($quote && $quote->auto_saved_at)
                        Last saved: {{ $quote->auto_saved_at->format('h:i:s A') }}
                    @endif
                </div>
            @endif
        </div>
    </section>

    {{-- Notification / Info Banner --}}
    @if($quote && $quote->status === 'pending')
        <div style="background: #FFF1F2; border: 1px solid #FFE4E6; border-radius: 8px; padding: 10px 14px; margin-bottom: 14px; display: flex; align-items: center; gap: 8px; color: #BE123C; font-size: 13px;">
            <svg class="ic sm" style="width: 16px; height: 16px; color: #E11D48;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-info"/></svg>
            <span>This quotation is waiting for your approval.</span>
        </div>
    @elseif($quote && $quote->status === 'approved')
        <div style="background: #F0FDF4; border: 1px solid #DCFCE7; border-radius: 8px; padding: 10px 14px; margin-bottom: 14px; display: flex; align-items: center; gap: 8px; color: #15803D; font-size: 13px;">
            <svg class="ic sm" style="width: 16px; height: 16px; color: #16A34A;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-check-circle"/></svg>
            <span>Approved {{ $quote->approvedBy ? 'by ' . $quote->approvedBy->name : '' }}. Ready to be sent to the customer.</span>
        </div>
    @elseif($quote && $quote->status === 'draft')
        @php
            $reopenedHist = $quote->histories ? $quote->histories->where('action', 'reopened')->last() : null;
        @endphp
        @if($reopenedHist)
            <div style="background: #EFF6FF; border: 1px solid #DBEAFE; border-radius: 8px; padding: 10px 14px; margin-bottom: 14px; display: flex; align-items: center; gap: 8px; color: #1E40AF; font-size: 13px;">
                <svg class="ic sm" style="width: 16px; height: 16px; color: #3B82F6;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-edit"/></svg>
                <span>{{ $reopenedHist->text }}. Make the changes and submit for approval again.</span>
            </div>
        @endif
    @endif

    @if($quote && $quote->rejection_reason)
        <div class="toast error show" style="position: static; margin-bottom: 14px;">
            <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-alert"/></svg>
            <span>Revision Required: {{ $quote->rejection_reason }}</span>
        </div>
    @endif

    <form id="quoteMainForm" method="POST" action="{{ $quote ? route('quotations.update', $quote) : route('quotations.store') }}">
        @csrf
        @if($quote)
            @method('PUT')
        @else
            <input type="hidden" name="lead_id" value="{{ $lead->id }}">
        @endif
        <input type="hidden" name="submit_for_approval" id="submitForApproval" value="0">

        <div class="q-layout">
            <div class="q-main">
                {{-- Customer & Project Card --}}
                <section class="card">
                    <header class="card-head">
                        <h2>
                            <span class="head-ic">
                                <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-file-text"/></svg>
                            </span>
                            Customer &amp; Project
                        </h2>
                        @if(!$quote && isset($availableLeads) && $availableLeads->count() > 1)
                            <div style="display: flex; align-items: center; gap: 8px; margin-left: auto; margin-right: 12px;">
                                <label style="font-size: 12px; color: var(--muted); font-weight: 500;">Select Lead:</label>
                                <span class="select-wrap mini">
                                    <select onchange="window.location.href='{{ route('quotations.builder') }}?lead=' + this.value">
                                        @foreach($availableLeads as $avl)
                                            <option value="{{ $avl->id }}" {{ $avl->id === $lead->id ? 'selected' : '' }}>
                                                {{ $avl->lead_number }} · {{ $avl->company_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </span>
                            </div>
                        @endif
                        <a class="link-btn" href="{{ route('leads.show', $lead) }}" target="_blank">
                            Open lead
                            <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-arrow-right"/></svg>
                        </a>
                    </header>

                    <dl class="kv cols-2" id="qLeadKv">
                        <dt>Quote Number</dt><dd><b>{{ $quote ? $quote->quote_number : 'Auto-generated on save' }}</b></dd>
                        <dt>Lead Number</dt><dd>{{ $lead->lead_number }}</dd>
                        <dt>Customer</dt><dd><strong>{{ $lead->company_name }}</strong></dd>
                        <dt>Contact</dt><dd>{{ $lead->contact_person }} ({{ $lead->phone }})</dd>
                        <dt>Project Subject</dt><dd>{{ $lead->subject }}</dd>
                        <dt>RFQ / PR Number</dt><dd>{{ $lead->rfq_number ?: '—' }}</dd>
                        <dt>Branch</dt><dd>{{ $lead->branch->name }}</dd>
                        <dt>Quote Format</dt><dd><span class="pill pill-primary"><i></i>{{ $lead->branch->quote_format }}</span></dd>
                        <dt>Assigned Engineer</dt><dd>{{ $lead->assignedEngineer?->name ?? 'Unassigned' }}</dd>
                    </dl>

                    <div class="form-grid three q-meta" style="margin-top: 14px; padding-top: 14px; border-top: 1px solid #eee;">
                        <label class="field">
                            <span class="field-label">Quote Date</span>
                            <input type="date" name="quote_date" id="quoteDate" value="{{ old('quote_date', $quote ? $quote->quote_date->format('Y-m-d') : date('Y-m-d')) }}" required>
                        </label>
                        <label class="field">
                            <span class="field-label">Validity</span>
                            <span class="select-wrap">
                                <select name="validity_days" id="validityDays">
                                    <option value="15" {{ ($quote && $quote->validity_days == 15) ? 'selected' : '' }}>15 days</option>
                                    <option value="30" {{ (!$quote || $quote->validity_days == 30) ? 'selected' : '' }}>30 days</option>
                                    <option value="45" {{ ($quote && $quote->validity_days == 45) ? 'selected' : '' }}>45 days</option>
                                    <option value="60" {{ ($quote && $quote->validity_days == 60) ? 'selected' : '' }}>60 days</option>
                                    <option value="90" {{ ($quote && $quote->validity_days == 90) ? 'selected' : '' }}>90 days</option>
                                </select>
                            </span>
                        </label>
                        <label class="field">
                            <span class="field-label">Output Mode</span>
                            <span class="select-wrap">
                                <select name="output_type" id="outputType">
                                    <option value="tax" {{ (!$quote || $quote->output_type == 'tax') ? 'selected' : '' }}>Quote with Total + Tax (5%)</option>
                                    <option value="notax" {{ ($quote && $quote->output_type == 'notax') ? 'selected' : '' }}>Quote with Total without Tax</option>
                                    <option value="tech" {{ ($quote && $quote->output_type == 'tech') ? 'selected' : '' }}>Technical Quote (No Pricing)</option>
                                </select>
                            </span>
                        </label>
                    </div>
                </section>

                {{-- Line Items Card --}}
                <section class="card">
                    <header class="card-head">
                        <h2>
                            <span class="head-ic">
                                <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-kanban"/></svg>
                            </span>
                            Line Items
                        </h2>
                        <div class="head-actions">
                            @php
                                // catalog menu: the lead's department in full, then two items from each other trade
                                $catalog = config('quotation_catalog', []);
                                $ownDept = array_key_exists($lead->department, $catalog) ? $lead->department : array_key_first($catalog);
                                $otherTrades = collect($catalog)->except($ownDept)->flatMap(fn ($items) => array_slice($items, 0, 2));
                            @endphp
                            <div class="catalog-wrap">
                                <button class="btn btn-ghost btn-sm" type="button" id="catalogBtn" onclick="toggleCatalogMenu(event)" aria-haspopup="menu" aria-expanded="false" aria-controls="catalogMenu">
                                    <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-book"/></svg>
                                    Add from catalog
                                </button>
                                <div class="popover catalog-menu" id="catalogMenu" role="menu" hidden>
                                    <div class="pop-head">{{ $lead->departmentLabel() }}</div>
                                    @foreach($catalog[$ownDept] ?? [] as [$desc, $unit])
                                        <button type="button" class="pop-item" role="menuitem" data-catalog-desc="{{ $desc }}" data-catalog-unit="{{ $unit }}">
                                            <span>{{ $desc }}<br><small>{{ $unit }}</small></span>
                                        </button>
                                    @endforeach
                                    @if($otherTrades->isNotEmpty())
                                        <div class="pop-div"></div>
                                        <div class="pop-head">Other trades</div>
                                        @foreach($otherTrades as [$desc, $unit])
                                            <button type="button" class="pop-item" role="menuitem" data-catalog-desc="{{ $desc }}" data-catalog-unit="{{ $unit }}">
                                                <span>{{ $desc }}<br><small>{{ $unit }}</small></span>
                                            </button>
                                        @endforeach
                                    @endif
                                </div>
                            </div>
                            <div class="split-btn section-btn-wrap" style="position: relative;">
                                <button class="btn btn-ghost btn-sm" type="button" onclick="addSectionRow('PRELIMINARIES')" title="Add section heading (e.g. PRELIMINARIES)" style="color: #C00000; font-weight: 700; border-color: #F1C9D3; background: #FFF7F8;">
                                    <svg class="ic sm" style="color: #C00000;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-menu"/></svg>
                                    Add Section
                                </button>
                                <button class="btn btn-ghost btn-sm split-caret" type="button" id="sectionMenuBtn" onclick="toggleSectionMenu(event)" aria-haspopup="menu" aria-expanded="false" title="Quick section presets" style="color: #C00000; border-color: #F1C9D3; background: #FFF7F8;">
                                    <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-chevron-down"/></svg>
                                </button>
                                <div class="popover catalog-menu" id="sectionMenu" role="menu" hidden style="width: 290px; top: calc(100% + 6px); right: 0;">
                                    <div class="pop-head">Standard Heading Presets</div>
                                    <button type="button" class="pop-item" role="menuitem" onclick="addSectionRow('PRELIMINARIES'); setSectionMenu(false);">
                                        <span style="color: #C00000; font-weight: 700;">PRELIMINARIES<br><small style="color: var(--muted); font-weight: normal;">Mobilization, Tarkhees approvals, Site setup</small></span>
                                    </button>
                                    <button type="button" class="pop-item" role="menuitem" onclick="addSectionRow('DEMOLITION & SITE PREPARATION'); setSectionMenu(false);">
                                        <span><b>DEMOLITION &amp; SITE PREPARATION</b><br><small style="color: var(--muted);">Dismantling, clearing &amp; waste disposal</small></span>
                                    </button>
                                    <button type="button" class="pop-item" role="menuitem" onclick="addSectionRow('CIVIL & MASONRY WORKS'); setSectionMenu(false);">
                                        <span><b>CIVIL &amp; MASONRY WORKS</b><br><small style="color: var(--muted);">Blockwork, plastering, screed &amp; concrete</small></span>
                                    </button>
                                    <button type="button" class="pop-item" role="menuitem" onclick="addSectionRow('MEP WORKS'); setSectionMenu(false);">
                                        <span><b>MEP WORKS</b><br><small style="color: var(--muted);">Electrical, containment &amp; plumbing</small></span>
                                    </button>
                                    <button type="button" class="pop-item" role="menuitem" onclick="addSectionRow('HVAC & AIR CONDITIONING'); setSectionMenu(false);">
                                        <span><b>HVAC &amp; AIR CONDITIONING</b><br><small style="color: var(--muted);">Ducting, FCU, diffuser &amp; insulation</small></span>
                                    </button>
                                    <button type="button" class="pop-item" role="menuitem" onclick="addSectionRow('CEILING & GYPSUM PARTITIONS'); setSectionMenu(false);">
                                        <span><b>CEILING &amp; GYPSUM PARTITIONS</b><br><small style="color: var(--muted);">False ceilings, drywalls, acoustic grid</small></span>
                                    </button>
                                    <button type="button" class="pop-item" role="menuitem" onclick="addSectionRow('JOINERY & CARPENTRY'); setSectionMenu(false);">
                                        <span><b>JOINERY &amp; CARPENTRY</b><br><small style="color: var(--muted);">Doors, cabinetry, wall cladding &amp; veneer</small></span>
                                    </button>
                                    <button type="button" class="pop-item" role="menuitem" onclick="addSectionRow('FLOORING & WALL FINISHES'); setSectionMenu(false);">
                                        <span><b>FLOORING &amp; WALL FINISHES</b><br><small style="color: var(--muted);">Tiles, marble, vinyl &amp; painting</small></span>
                                    </button>
                                    <button type="button" class="pop-item" role="menuitem" onclick="addSectionRow('GLASS & ALUMINIUM WORKS'); setSectionMenu(false);">
                                        <span><b>GLASS &amp; ALUMINIUM WORKS</b><br><small style="color: var(--muted);">Glass partitions, doors &amp; shop fronts</small></span>
                                    </button>
                                    <div class="pop-div"></div>
                                    <button type="button" class="pop-item" role="menuitem" onclick="addSectionRow(''); setSectionMenu(false);">
                                        <span style="font-style: italic; color: var(--muted);">+ Blank / Custom Section Heading...</span>
                                    </button>
                                </div>
                            </div>
                            <button class="btn btn-soft btn-sm" type="button" onclick="addLineItemRow()">
                                <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-plus"/></svg>
                                Add Line Item
                            </button>
                        </div>
                    </header>

                    <div class="table-wrap page items-wrap">
                        <table class="table items-table" id="itemsTable">
                            <thead>
                                <tr>
                                    <th style="width: 4%;">#</th>
                                    <th style="width: 45%;">Description</th>
                                    <th class="num" style="width: 10%;">Qty</th>
                                    <th style="width: 11%;">Unit</th>
                                    <th class="num" style="width: 13%;">Unit Price (AED)</th>
                                    <th class="num" style="width: 13%;">Total Amount</th>
                                    <th class="ta-c" style="width: 120px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="itemsBody">
                                @php
                                    $existingItems = $quote ? $quote->lineItems : [];
                                    $itemNumber = 0;
                                @endphp
                                @if(count($existingItems) > 0)
                                    @foreach($existingItems as $i => $item)
                                        @if($item->isSection())
                                            @include('quotations.partials.section-row', ['prefix' => "items[$i]", 'value' => $item->description])
                                            @continue
                                        @endif
                                        @if($item->isSubDesc())
                                            @include('quotations.partials.subdesc-row', ['prefix' => "items[$i]", 'value' => $item->description])
                                            @continue
                                        @endif
                                        @php
                                            $itemNumber++;
                                            $groupRowspan = 1 + $existingItems->slice($i + 1)->takeWhile(fn ($next) => $next->isSubDesc())->count();
                                        @endphp
                                        <tr class="item-row">
                                            <td class="li-no" rowspan="{{ $groupRowspan }}" style="vertical-align: middle;">{{ $itemNumber }}</td>
                                            <td>
                                                @include('quotations.partials.rte', ['name' => "items[$i][description]", 'value' => $item->description, 'placeholder' => 'Item specifications & scope'])
                                                @include('quotations.partials.line-photos', ['prefix' => "items[$i]", 'photos' => $item->photos ?? []])
                                                <div class="subdesc-add-wrap" style="margin-top: 4px;">
                                                    <button type="button" class="btn btn-ghost btn-xs subdesc-add-btn" onclick="addSubDescRow(this)" title="Add a sub-description line to this item" style="font-size: 11px; padding: 2px 8px; color: var(--muted);">
                                                        + Sub-description
                                                    </button>
                                                </div>
                                            </td>
                                            <td class="num" rowspan="{{ $groupRowspan }}" style="vertical-align: middle;">
                                                <input class="li-in li-qty" type="number" step="any" min="0" name="items[{{ $i }}][quantity]" value="{{ (float) $item->quantity }}" oninput="calcRow(this)" required>
                                            </td>
                                            <td rowspan="{{ $groupRowspan }}" style="vertical-align: middle;">
                                                <input class="li-in li-unit" list="unitList" name="items[{{ $i }}][unit]" value="{{ $item->unit }}" placeholder="unit">
                                            </td>
                                            <td class="num" rowspan="{{ $groupRowspan }}" style="vertical-align: middle;">
                                                <input class="li-in li-price" type="number" step="0.01" min="0" name="items[{{ $i }}][unit_price]" value="{{ $item->unit_price }}" oninput="calcRow(this)" required>
                                            </td>
                                            <td class="num li-total" rowspan="{{ $groupRowspan }}" style="vertical-align: middle;">
                                                <b>AED {{ number_format($item->total_amount, 2) }}</b>
                                            </td>
                                            <td class="ta-c" style="white-space: nowrap;">
                                                <div class="row-actions" style="display: inline-flex; gap: 3px; align-items: center; justify-content: center;">
                                                    <button type="button" class="act" style="width: 26px; height: 26px; border-radius: 6px;" onclick="moveRowUp(this)" title="Move up" aria-label="Move up">
                                                        <svg class="ic xs" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-arrow-up"/></svg>
                                                    </button>
                                                    <button type="button" class="act" style="width: 26px; height: 26px; border-radius: 6px;" onclick="moveRowDown(this)" title="Move down" aria-label="Move down">
                                                        <svg class="ic xs" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-arrow-down"/></svg>
                                                    </button>
                                                    <button type="button" class="act" style="width: 26px; height: 26px; border-radius: 6px;" onclick="removeRow(this)" title="Remove item" aria-label="Remove item">
                                                        <svg class="ic xs" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-x"/></svg>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr class="item-row">
                                        <td class="li-no">1</td>
                                        <td>
                                            @include('quotations.partials.rte', ['name' => 'items[0][description]', 'value' => '', 'placeholder' => ''])
                                            @include('quotations.partials.line-photos', ['prefix' => 'items[0]', 'photos' => []])
                                            <div class="subdesc-add-wrap" style="margin-top: 4px;">
                                                <button type="button" class="btn btn-ghost btn-xs subdesc-add-btn" onclick="addSubDescRow(this)" title="Add a sub-description line to this item" style="font-size: 11px; padding: 2px 8px; color: var(--muted);">
                                                    + Sub-description
                                                </button>
                                            </div>
                                        </td>
                                        <td class="num">
                                            <input class="li-in li-qty" type="number" step="any" min="0" name="items[0][quantity]" value="1" oninput="calcRow(this)" required>
                                        </td>
                                        <td>
                                            <input class="li-in li-unit" list="unitList" name="items[0][unit]" value="" placeholder="unit">
                                        </td>
                                        <td class="num">
                                            <input class="li-in li-price" type="number" step="0.01" min="0" name="items[0][unit_price]" value="0" oninput="calcRow(this)" required>
                                        </td>
                                        <td class="num li-total"><b>AED 0.00</b></td>
                                        <td class="ta-c" style="white-space: nowrap;">
                                            <div class="row-actions" style="display: inline-flex; gap: 3px; align-items: center; justify-content: center;">
                                                <button type="button" class="act" style="width: 26px; height: 26px; border-radius: 6px;" onclick="moveRowUp(this)" title="Move up" aria-label="Move up">
                                                    <svg class="ic xs" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-arrow-up"/></svg>
                                                </button>
                                                <button type="button" class="act" style="width: 26px; height: 26px; border-radius: 6px;" onclick="moveRowDown(this)" title="Move down" aria-label="Move down">
                                                    <svg class="ic xs" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-arrow-down"/></svg>
                                                </button>
                                                <button type="button" class="act" style="width: 26px; height: 26px; border-radius: 6px;" onclick="removeRow(this)" title="Remove item" aria-label="Remove item">
                                                    <svg class="ic xs" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-x"/></svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>

                    {{-- editor markup for rows added with "Add Line Item" (__NAME__ is replaced with the field name) --}}
                    <template id="rteTemplate">
                        @include('quotations.partials.rte', ['name' => '__NAME__', 'value' => '', 'placeholder' => 'Item specifications & scope'])
                    </template>
                    <template id="photoTemplate">
                        @include('quotations.partials.line-photos', ['prefix' => '__PREFIX__', 'photos' => []])
                    </template>
                    <template id="sectionTemplate">
                        <table><tbody>@include('quotations.partials.section-row', ['prefix' => '__PREFIX__', 'value' => ''])</tbody></table>
                    </template>
                    <template id="subdescTemplate">
                        <table><tbody>@include('quotations.partials.subdesc-row', ['prefix' => '__PREFIX__', 'value' => ''])</tbody></table>
                    </template>

                    {{-- enlarged line item photo (click a thumbnail); arrows step through that item's photos --}}
                    <div class="photo-viewer" id="photoViewer" hidden onclick="if (event.target === this) closePhotoViewer()">
                        <button type="button" class="pv-btn pv-close" onclick="closePhotoViewer()" aria-label="Close">
                            <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-x"/></svg>
                        </button>
                        <button type="button" class="pv-btn pv-prev" onclick="stepPhotoViewer(-1)" aria-label="Previous photo">
                            <svg class="ic flip" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-chevron-right"/></svg>
                        </button>
                        <img id="photoViewerImg" alt="Line item photo">
                        <button type="button" class="pv-btn pv-next" onclick="stepPhotoViewer(1)" aria-label="Next photo">
                            <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-chevron-right"/></svg>
                        </button>
                        <span class="pv-count" id="photoViewerCount"></span>
                    </div>

                    <datalist id="unitList">
                        <option value="Lsm">
                        <option value="LS">
                        <option value="lump sum">
                        <option value="m²">
                        <option value="m³">
                        <option value="m">
                        <option value="ton">
                        <option value="nos">
                        <option value="set">
                        <option value="day">
                    </datalist>

                    <datalist id="sectionSuggestions">
                        <option value="PRELIMINARIES">
                        <option value="DEMOLITION & SITE PREPARATION">
                        <option value="CIVIL & MASONRY WORKS">
                        <option value="MEP WORKS (MECHANICAL, ELECTRICAL & PLUMBING)">
                        <option value="HVAC & AIR CONDITIONING">
                        <option value="CEILING & GYPSUM PARTITIONS">
                        <option value="JOINERY & CARPENTRY">
                        <option value="FLOORING & WALL FINISHES">
                        <option value="GLASS & ALUMINIUM WORKS">
                        <option value="PAINTING & DECORATIVE FINISHES">
                        <option value="FIRE FIGHTING & FIRE ALARM">
                        <option value="TESTING, COMMISSIONING & HANDOVER">
                    </datalist>
                </section>

                {{-- Descriptions Card --}}
                <section class="card">
                    <div class="form-grid">
                        <label class="field full">
                            <span class="field-label">Technical Description &amp; Scope of Work</span>
                            <textarea name="technical_description" rows="3" maxlength="5000" placeholder="Method statement, execution timeline, materials grade...">{{ old('technical_description', $quote?->technical_description) }}</textarea>
                        </label>
                        <label class="field full">
                            <span class="field-label">Terms &amp; Conditions</span>
                            <textarea name="terms_conditions" rows="4" maxlength="5000" placeholder="Payment milestones, validity, warranty, client obligations...">{{ old('terms_conditions', $quote?->terms_conditions ?? "1. 50% Advance payment with confirmed Purchase Order.\n2. 40% Progressive payment against delivery and installation.\n3. 10% Final retention upon completion and client handover.\n4. Quotation valid for 30 calendar days from issue date.") }}</textarea>
                        </label>
                        <label class="field full">
                            <span class="field-label">Internal Notes (Not printed on client quotation)</span>
                            <textarea name="internal_notes" rows="2" maxlength="2000" placeholder="Cost margin notes, sub-contractor pricing...">{{ old('internal_notes', $quote?->internal_notes) }}</textarea>
                        </label>
                    </div>
                </section>

                {{-- Attachments Card: files upload right away (quotations.attachment); the hidden fields below are the
                     full current list, which Save / autosave make the quotation match (QuotationService::syncAttachments) --}}
                <section class="card" id="quoteAttachments">
                    <header class="card-head">
                        <h2>
                            <span class="head-ic">
                                <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-upload"/></svg>
                            </span>
                            Attachments
                        </h2>
                    </header>
                    <input type="hidden" name="attachments_sync" value="1">
                    <div class="field">
                        <span class="field-label">Add files</span>
                        <input type="file" id="attachmentInput" multiple aria-label="Add files">
                    </div>
                    @php $existingAttachments = $quote ? $quote->attachments : collect(); @endphp
                    <ul class="fd-list att-list" id="attachmentList">
                        @foreach($existingAttachments as $att)
                            <li>
                                <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-file-text"/></svg>
                                <span><a href="{{ asset('storage/' . $att->file_path) }}" download="{{ $att->file_name }}" target="_blank" rel="noopener">{{ $att->file_name }}</a></span>
                                <small>{{ $att->file_size >= 1048576 ? number_format($att->file_size / 1048576, 1) . ' MB' : max(1, round($att->file_size / 1024)) . ' KB' }}</small>
                                <button type="button" class="att-remove" data-att-remove title="Remove attachment" aria-label="Remove {{ $att->file_name }}">
                                    <svg class="ic xs" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-x"/></svg>
                                </button>
                                <input type="hidden" name="attachments[{{ $att->id }}][path]" value="{{ $att->file_path }}">
                                <input type="hidden" name="attachments[{{ $att->id }}][name]" value="{{ $att->file_name }}">
                                <input type="hidden" name="attachments[{{ $att->id }}][size]" value="{{ $att->file_size }}">
                            </li>
                        @endforeach
                    </ul>
                    <p class="att-empty" id="attachmentEmpty" @if($existingAttachments->isNotEmpty()) hidden @endif>No attachments</p>
                </section>
            </div>

            {{-- Summary Sidebar --}}
            <aside class="q-side">
                <section class="card q-summary">
                    <header class="card-head">
                        <h2>
                            <span class="head-ic">
                                <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-coins"/></svg>
                            </span>
                            Summary
                        </h2>
                    </header>

                    <div id="qTotals">
                        <div class="sum-row">
                            <span>Subtotal</span>
                            <b id="sumSubtotal">AED {{ number_format($quote ? $quote->subtotal : 0, 2) }}</b>
                        </div>

                        <div class="sum-row disc">
                            <span>Discount</span>
                            <span class="disc-ctl" style="display: flex; gap: 4px; align-items: center;">
                                <input type="number" name="discount_value" id="discountValue" min="0" step="0.01" style="width: 80px;" placeholder="0" value="{{ $quote && (float) $quote->discount_value > 0 ? (float) $quote->discount_value : '' }}" oninput="calcTotals()">
                                <span class="select-wrap mini">
                                    <select name="discount_type" id="discountType" onchange="calcTotals()">
                                        <option value="amt" {{ (!$quote || $quote->discount_type == 'amt') ? 'selected' : '' }}>AED</option>
                                        <option value="pct" {{ ($quote && $quote->discount_type == 'pct') ? 'selected' : '' }}>%</option>
                                    </select>
                                </span>
                            </span>
                        </div>

                        <div class="sum-row sub">
                            <span>Discount amount</span>
                            <span id="sumDiscount">AED {{ number_format($quote ? $quote->discount_amount : 0, 2) }}</span>
                        </div>

                        <div class="sum-row">
                            <span>Taxable amount</span>
                            <b id="sumTaxable">AED {{ number_format($quote ? $quote->taxable_amount : 0, 2) }}</b>
                        </div>

                        <div class="sum-row">
                            <span>VAT Rate</span>
                            <span class="select-wrap mini">
                                <select name="vat_rate" id="taxRate" onchange="calcTotals()">
                                    <option value="0.05" {{ (!$quote || $quote->vat_rate == 0.05) ? 'selected' : '' }}>5%</option>
                                    <option value="0.00" {{ ($quote && $quote->vat_rate == 0.00) ? 'selected' : '' }}>0% (Zero-rated)</option>
                                </select>
                            </span>
                        </div>

                        <div class="sum-row sub">
                            <span>VAT amount</span>
                            <span id="sumVat">AED {{ number_format($quote ? $quote->vat_amount : 0, 2) }}</span>
                        </div>

                        <div class="sum-row grand">
                            <span>Grand Total</span>
                            <b id="sumGrandTotal">AED {{ number_format($quote ? $quote->grand_total : 0, 2) }}</b>
                        </div>
                    </div>
                </section>

                @if($quote)
                <section class="card">
                    <header class="card-head">
                        <h2>
                            <span class="head-ic">
                                <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-clock"/></svg>
                            </span>
                            Quotation History
                        </h2>
                    </header>
                    <ul class="events" style="max-height: 280px; overflow-y: auto;">
                        @forelse($quote->histories as $hist)
                            <li class="ev">
                                <span class="ev-dot"></span>
                                <div>
                                    <strong>{{ $hist->text }}</strong>
                                    <small>{{ $hist->created_at->format('d M · h:i A') }} &bull; {{ $hist->user?->name ?? 'System' }}</small>
                                </div>
                            </li>
                        @empty
                            <li class="empty-state">No revision history yet.</li>
                        @endforelse
                    </ul>
                </section>
                @endif
            </aside>
        </div>
    </form>
</div>

{{-- Submit for approval confirmation (new quotation or draft): totals are read from the live Summary --}}
@if(!$quote || in_array($quote->status, ['draft', 'rejected']))
<div class="modal-wrap" id="submitConfirmModal" style="display: none;" onclick="if (event.target === this) closeSubmitConfirm()">
    <div class="modal modal-sm" role="dialog" aria-modal="true" aria-labelledby="submitConfirmTitle">
        <div class="modal-head">
            <div>
                <h3 id="submitConfirmTitle">Submit for approval?</h3>
            </div>
            <button class="icon-btn" type="button" onclick="closeSubmitConfirm()" aria-label="Close">
                <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-x"/></svg>
            </button>
        </div>
        <div class="modal-body">
            <p class="confirm-text">
                <b>{{ $quote ? $quote->quote_number : 'New quotation' }}</b> &middot; <span style="text-transform: capitalize;">{{ $lead->company_name }}</span><br>
                Grand total <b id="submitConfirmTotal">AED 0.00</b> (incl. VAT <span id="submitConfirmVat">AED 0.00</span>).
                The approver is notified and the quotation becomes read-only until it is approved or returned.
            </p>
        </div>
        <div class="modal-foot">
            <button class="btn btn-ghost" type="button" onclick="closeSubmitConfirm()">Cancel</button>
            <button class="btn btn-primary" type="button" onclick="closeSubmitConfirm(); submitQuoteForm(true);">Submit for Approval</button>
        </div>
    </div>
</div>
@endif

{{-- Reject Reason Modal --}}
@if($quote && $quote->status === 'pending')
<div class="modal-wrap" id="rejectModal" style="display: none;" onclick="if(event.target === this) closeRejectModal()">
    <div class="modal modal-md reject-modal" role="dialog" aria-modal="true" aria-labelledby="rejectModalTitle">
        <div class="modal-head">
            <h3 id="rejectModalTitle">Return {{ $quote->quote_number }} for revision?</h3>
            <button class="icon-btn" type="button" onclick="closeRejectModal()" aria-label="Close">
                <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-x"/></svg>
            </button>
        </div>
        <form method="POST" action="{{ route('quotations.reject', $quote) }}">
            @csrf
            <div class="modal-body">
                <label class="field reject-reason">
                    <span class="field-label">Reason</span>
                    <textarea id="rejectReason" name="reason" rows="4" required placeholder="Add a note explaining what needs to be revised..."></textarea>
                </label>
            </div>
            <div class="modal-foot">
                <button class="btn btn-ghost" type="button" onclick="closeRejectModal()">Cancel</button>
                <button class="btn btn-danger" type="submit">Request Revision</button>
            </div>
        </form>
    </div>
</div>
@endif

{{-- Approve Quotation Confirmation Modal --}}
@if($quote && $quote->status === 'pending')
<div class="modal-wrap" id="approveModal" style="display: none;" onclick="if(event.target === this) closeApproveModal()">
    <div class="modal modal-sm approve-modal" role="dialog" aria-modal="true" aria-labelledby="approveModalTitle">
        <div class="modal-head">
            <h3 id="approveModalTitle">Approve {{ $quote->quote_number }}?</h3>
            <button class="icon-btn" type="button" onclick="closeApproveModal()" aria-label="Close">
                <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-x"/></svg>
            </button>
        </div>
        <div class="modal-body">
            <p class="confirm-text">
                Grand total <b>AED {{ number_format($quote->grand_total, 2) }}</b> for {{ $lead->company_name }}.
                Once approved the quotation can be sent to the customer.
            </p>
        </div>
        <form action="{{ route('quotations.approve', $quote) }}" method="POST">
            @csrf
            <div class="modal-foot">
                <button class="btn btn-ghost" type="button" onclick="closeApproveModal()">Cancel</button>
                <button class="btn btn-primary" type="submit">Approve</button>
            </div>
        </form>
    </div>
</div>
@endif

{{-- Mark Quotation as Awarded Confirmation Modal --}}
@if($quote && $quote->status === 'sent')
<div class="modal-wrap" id="awardModal" style="display: none;" onclick="if(event.target === this) closeAwardModal()">
    <div class="modal modal-sm award-modal" role="dialog" aria-modal="true" aria-labelledby="awardModalTitle">
        <div class="modal-head">
            <h3 id="awardModalTitle">Mark quotation as awarded?</h3>
            <button class="icon-btn" type="button" onclick="closeAwardModal()" aria-label="Close">
                <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-x"/></svg>
            </button>
        </div>
        <div class="modal-body">
            <p class="confirm-text">
                Are you sure you want to mark Quote <b>#{{ $quote->quote_number }}</b>
                (AED {{ number_format($quote->grand_total, 2) }}) as Awarded?
                Project creation becomes available.
            </p>
        </div>
        <form action="{{ route('quotations.award', $quote) }}" method="POST">
            @csrf
            <div class="modal-foot">
                <button class="btn btn-ghost" type="button" onclick="closeAwardModal()">Cancel</button>
                <button class="btn btn-primary" type="submit">Mark as Awarded</button>
            </div>
        </form>
    </div>
</div>
@endif

{{-- Create Project from Awarded Quotation --}}
@if($quote && $quote->status === 'awarded' && !$quote->project)
    @can('projects.create')
    <div class="modal-wrap" id="createProjectModal" style="display: none;" onclick="if(event.target === this) closeCreateProjectModal()">
        <div class="modal modal-xl create-project-modal" role="dialog" aria-modal="true" aria-labelledby="createProjectModalTitle">
            <div class="modal-head">
                <div>
                    <h3 id="createProjectModalTitle">Create Project</h3>
                    <p>Details are taken from the awarded quotation. Add the PO now or later.</p>
                </div>
                <button class="icon-btn" type="button" onclick="closeCreateProjectModal()" aria-label="Close">
                    <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-x"/></svg>
                </button>
            </div>

            <form action="{{ route('projects.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="quotation_id" value="{{ $quote->id }}">
                <input type="hidden" name="lead_id" value="{{ $lead->id }}">
                <input type="hidden" name="branch_id" value="{{ $quote->branch_id }}">
                <input type="hidden" name="client_name" value="{{ $lead->company_name }}">
                <input type="hidden" name="contract_value" value="{{ $quote->subtotal }}">
                <input type="hidden" name="start_date" value="{{ now()->toDateString() }}">

                <div class="modal-body create-project-body">
                    @if($errors->hasAny(['name', 'branch_id', 'lead_id', 'quotation_id', 'engineer_id', 'contract_value', 'client_po_number', 'client_po_date', 'client_po_amount', 'po_file', 'project_documents', 'description']))
                        <div class="create-project-errors" role="alert">
                            @foreach(['name', 'branch_id', 'lead_id', 'quotation_id', 'engineer_id', 'contract_value', 'client_po_number', 'client_po_date', 'client_po_amount', 'po_file', 'project_documents', 'description'] as $projectField)
                                @foreach($errors->get($projectField) as $error)
                                    <div>{{ $error }}</div>
                                @endforeach
                                @foreach($errors->get($projectField.'.*') as $error)
                                    <div>{{ $error }}</div>
                                @endforeach
                            @endforeach
                        </div>
                    @endif

                    <label class="field create-project-quote">
                        <span class="field-label">Awarded Quotation</span>
                        <span class="create-project-quote-number">{{ $quote->quote_number }} · {{ $lead->company_name }} — AED {{ number_format($quote->subtotal, 2) }}</span>
                    </label>

                    <div class="create-project-summary" aria-label="Quotation details">
                        <span>Customer</span><strong>{{ $lead->company_name }}</strong>
                        <span>Quotation</span><strong>{{ $quote->quote_number }}</strong>
                        <span>Branch</span><strong>{{ $quote->branch->name }}</strong>
                        <span>Engineer</span><strong>{{ $lead->assignedEngineer?->name ?? 'Not assigned' }}</strong>
                        <span>Subject</span><strong>{{ $lead->subject }}</strong>
                        <span class="create-project-summary-total">Quotation amount (excl. VAT)</span>
                        <strong class="create-project-summary-total">AED {{ number_format($quote->subtotal, 2) }}</strong>
                    </div>

                    <label class="field">
                        <span class="field-label">Project Name</span>
                        <input name="name" type="text" required maxlength="200" value="{{ old('name', $lead->subject) }}">
                    </label>

                    <div class="create-project-grid">
                        <label class="field">
                            <span class="field-label">RFQ / PR Number</span>
                            <input type="text" value="{{ $lead->rfq_number ?: 'Not provided' }}" readonly>
                            <small class="create-project-hint">Taken from the lead.</small>
                        </label>
                        <label class="field">
                            <span class="field-label">Assigned Engineer</span>
                            <select name="engineer_id">
                                <option value="">Select engineer</option>
                                @foreach($branchEngineers as $engineer)
                                    <option value="{{ $engineer->id }}" @selected(old('engineer_id', $lead->assigned_engineer_id) == $engineer->id)>{{ $engineer->name }}</option>
                                @endforeach
                            </select>
                        </label>
                    </div>

                    <label class="field create-project-work-authorization">
                        <span class="field-label">Work Authorization</span>
                        <select id="projectAuthorizationStatus" aria-label="Work Authorization">
                            <option value="po_received">Purchase Order received</option>
                            <option value="email_approved">Approved by email — PO to follow</option>
                            <option value="po_pending" selected>Not received yet (PO Pending)</option>
                        </select>
                    </label>

                    <div class="create-project-grid">
                        <label class="field">
                            <span class="field-label">PO Number</span>
                            <input id="projectPoNumber" name="client_po_number" type="text" maxlength="100" value="{{ old('client_po_number') }}" placeholder="Optional — e.g. PO-2026-4471">
                        </label>
                        <label class="field">
                            <span class="field-label">PO Date</span>
                            <input id="projectPoDate" name="client_po_date" type="date" value="{{ old('client_po_date', now()->toDateString()) }}">
                        </label>
                        <label class="field">
                            <span class="field-label">PO Value (AED)</span>
                            <input name="client_po_amount" type="number" step="0.01" min="0" value="{{ old('client_po_amount', $quote->subtotal) }}">
                        </label>
                        <label class="field">
                            <span class="field-label">PO Document</span>
                            <input name="po_file" type="file" accept=".pdf,.jpg,.jpeg,.png">
                            <small class="create-project-hint">PDF, JPG, JPEG, or PNG</small>
                        </label>
                    </div>

                    <label class="field">
                        <span class="field-label">Project Documents</span>
                        <input name="project_documents[]" type="file" multiple accept=".pdf,.jpg,.jpeg,.png,.dwg,.xls,.xlsx,.doc,.docx">
                        <small class="create-project-hint">PDF, images, drawings, spreadsheets, or documents. Multiple files allowed.</small>
                    </label>

                    <label class="field">
                        <span class="field-label">Project Description</span>
                        <textarea name="description" rows="3" maxlength="2000" placeholder="Scope, site address, key milestones...">{{ old('description', $quote->technical_description) }}</textarea>
                    </label>
                </div>

                <div class="modal-foot">
                    <button class="btn btn-ghost" type="button" onclick="closeCreateProjectModal()">Cancel</button>
                    <button class="btn btn-primary" type="submit">
                        <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-briefcase"/></svg>
                        Create Project
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endcan
@endif

{{-- Send Quotation Modal --}}
@if($quote && $quote->status === 'approved')
<div class="modal-wrap" id="sendModal" style="display: none;" onclick="if(event.target === this) closeSendModal()">
    <div class="modal modal-md" role="dialog" aria-modal="true" aria-labelledby="sendModalTitle" style="max-width: 580px;">
        <div class="modal-head">
            <div>
                <h3 id="sendModalTitle">Send Quotation</h3>
                <p>Review before sending to the customer</p>
            </div>
            <button class="icon-btn" type="button" onclick="closeSendModal()" aria-label="Close">
                <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-x"/></svg>
            </button>
        </div>
        <form method="POST" action="{{ route('quotations.send', $quote) }}">
            @csrf
            <div class="modal-body" style="padding-top: 14px;">
                {{-- Quotation Summary Card --}}
                <div style="background: #FAFAFA; border: 1px solid #EDEDED; border-radius: 10px; padding: 16px; margin-bottom: 18px;">
                    <div style="display: grid; grid-template-columns: 130px 1fr; row-gap: 12px; column-gap: 16px; font-size: 13px;">
                        <span style="color: var(--muted, #6c757d);">Customer</span>
                        <span style="font-weight: 600; color: #111;">{{ $lead->company_name }}</span>

                        <span style="color: var(--muted, #6c757d);">Project</span>
                        <span style="font-weight: 600; color: #111;">{{ $lead->subject }}</span>

                        <span style="color: var(--muted, #6c757d);">Quote Number</span>
                        <span style="font-weight: 600; color: #111;">{{ $quote->quote_number }}</span>

                        <span style="color: var(--muted, #6c757d);">Amount</span>
                        <span style="font-weight: 600; color: #111;">AED {{ number_format($quote->subtotal, 2) }}</span>

                        <span style="color: var(--muted, #6c757d);">Tax (VAT 5%)</span>
                        <span style="font-weight: 600; color: #111;">AED {{ number_format($quote->vat_amount, 2) }}</span>

                        <span style="color: var(--muted, #6c757d);">Grand Total</span>
                        <span style="font-weight: 700; color: #111;">AED {{ number_format($quote->grand_total, 2) }}</span>
                    </div>
                </div>

                {{-- Fields: Mode of Share & Output --}}
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px;">
                    <label class="field" style="margin: 0;">
                        <span class="field-label">Mode of Share</span>
                        <span class="select-wrap">
                            <select name="share_mode" id="shareModeSelect">
                                <option value="Email" selected>Email</option>
                                <option value="WhatsApp">WhatsApp</option>
                                <option value="Direct Download">Direct Download</option>
                            </select>
                        </span>
                    </label>

                    <label class="field" style="margin: 0;">
                        <span class="field-label">Output</span>
                        <span class="select-wrap">
                            <select name="output_mode" id="outputModeSelect">
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
                    <input type="email" name="recipient" id="sendToInput" required value="{{ $lead->email ?? '' }}" placeholder="name@company.com">
                </label>

                {{-- Message --}}
                <label class="field" style="margin-bottom: 6px;">
                    <span class="field-label">Message</span>
                    <textarea name="message" id="sendMessageInput" rows="4" placeholder="Message to customer...">Dear {{ $lead->contact_person ?: ($lead->customer_name ?: $lead->company_name) }},

Please Find Attached Our Quotation {{ $quote->quote_number }} For {{ $lead->subject }}.</textarea>
                </label>

                <p style="font-size: 11.5px; color: var(--muted, #71717A); margin: 0 0 8px 0;">Demo: nothing is sent; the quotation status changes to Sent and the share mode is recorded.</p>
            </div>

            <div class="modal-foot">
                <button class="btn btn-ghost" type="button" onclick="closeSendModal()">Cancel</button>
                <button class="btn btn-primary" type="submit">
                    <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-send"/></svg>
                    Confirm Send
                </button>
            </div>
        </form>
    </div>
</div>
@endif

@if($quote && in_array($quote->status, ['approved', 'sent']))
{{-- Reopen / Edit Quotation Modal matching prototype --}}
<div class="modal-wrap" id="reopenModal" style="display: none;" onclick="if(event.target === this) closeReopenModal()">
    <div class="modal modal-md reopen-modal" role="dialog" aria-modal="true" aria-labelledby="reopenModalTitle">
        <div class="modal-head">
            <div>
                <h3 id="reopenModalTitle">Edit {{ $quote->quote_number }}?</h3>
                <p>
                    @if($quote->status === 'sent')
                        Revision after sending: the quotation moves back to Draft with a new revision number and today's date. Submit it for approval again before sending to the customer.
                    @else
                        The quotation moves back to Draft. After editing, submit it for approval again.
                    @endif
                </p>
            </div>
            <button class="icon-btn" type="button" onclick="closeReopenModal()" aria-label="Close">
                <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-x"/></svg>
            </button>
        </div>
        <form id="reopenForm" method="POST" action="{{ route('quotations.reopen', $quote) }}" enctype="multipart/form-data">
            @csrf
            <div class="modal-body reopen-modal-body">
                <div class="reopen-modal-fields">
                    <label class="field reopen-field">
                        <span class="field-label">
                        Reason for edit &amp; changes required <span style="color: var(--primary);">*</span>
                        </span>
                        <textarea name="reason" rows="4" required maxlength="500" placeholder="Note"></textarea>
                    </label>

                    <div class="field reopen-field">
                        <span class="field-label">Revised scope documents</span>
                        <label id="revisedScopeDropZone" class="file-drop" for="revisedScopeDocuments">
                            <span class="fd-icon">
                                <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-upload"/></svg>
                            </span>
                            <span class="fd-text">
                                <span><strong>Click to upload</strong> or drag &amp; drop</span>
                                <small>PDF, JPG, JPEG, PNG, DWG, XLSX, DOCX &middot; multiple files allowed</small>
                            </span>
                            <input id="revisedScopeDocuments" name="revised_scope_documents[]" type="file" multiple data-plain accept=".pdf,.jpg,.jpeg,.png,.dwg,.xlsx,.docx" aria-describedby="revisedScopeFileList">
                        </label>
                        <ul class="fd-list revised-scope-file-list" id="revisedScopeFileList" aria-live="polite"></ul>
                        <button class="revised-scope-clear" id="clearRevisedScopeDocuments" type="button" hidden>
                            <svg class="ic xs" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-x"/></svg>
                            Clear
                        </button>
                        <span class="reopen-field-hint">Shared with the assigned user; file names are stored with the quotation.</span>
                    </div>

                    <label class="field reopen-field">
                        <span class="field-label">Assign to <span style="color: var(--primary);">*</span></span>
                        <span class="select-wrap">
                            <select name="engineer_id">
                                <option value="">Myself ({{ auth()->user()->name }}) &mdash; edit directly</option>
                                @if(isset($branchEngineers))
                                    @foreach($branchEngineers as $eng)
                                        <option value="{{ $eng->id }}" {{ $eng->id === $lead->assigned_engineer_id ? 'selected' : '' }}>
                                            {{ $eng->name }} {{ $eng->id === $lead->assigned_engineer_id ? '(current engineer)' : '' }}
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                        </span>
                        <span class="reopen-field-hint">Edit it yourself, or pick an engineer from {{ $lead->branch->name }} (e.g. if the current one is on leave).</span>
                    </label>
                </div>
            </div>
            <div class="modal-foot">
                <button class="btn btn-ghost" type="button" onclick="closeReopenModal()">Cancel</button>
                <button class="btn btn-primary" type="submit">
                    <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-edit"/></svg>
                    Move to Draft
                </button>
            </div>
        </form>
    </div>
</div>
@endif

@if($quote)
{{-- Clone Quotation Modal matching prototype --}}
<div class="modal-wrap" id="cloneModal" style="display: none;" onclick="if(event.target === this) closeCloneModal()">
    <div class="modal modal-md" role="dialog" aria-modal="true" aria-labelledby="cloneModalTitle" style="max-width: 520px;">
        <div class="modal-head">
            <div>
                <h3 id="cloneModalTitle">Clone {{ $quote->quote_number }}</h3>
                <p>Creates a new draft with the same line items. The original quotation is not changed.</p>
            </div>
            <button class="icon-btn" type="button" onclick="closeCloneModal()" aria-label="Close">
                <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-x"/></svg>
            </button>
        </div>
        <form id="cloneForm" method="POST" action="{{ route('quotations.clone', $quote) }}">
            @csrf
            <div class="modal-body" style="padding: 20px;">
                <label class="field" style="margin: 0;">
                    <span class="field-label" style="font-weight: 600; font-size: 13px; margin-bottom: 8px; display: block;">Create the copy for</span>
                    <span class="select-wrap">
                        <select name="lead_id" id="cloneLeadSelect" required>
                            <option value="{{ $lead->id }}">Same Lead &mdash; {{ $lead->lead_number }} &bull; {{ $lead->company_name ?: $lead->customer_name }}</option>
                            @foreach($availableLeads as $al)
                                @if($al->id !== $lead->id)
                                    <option value="{{ $al->id }}">{{ $al->lead_number }} &bull; {{ $al->company_name ?: $al->customer_name }} &mdash; {{ $al->subject }}</option>
                                @endif
                            @endforeach
                        </select>
                    </span>
                </label>
            </div>
            <div class="modal-foot">
                <button class="btn btn-ghost" type="button" onclick="closeCloneModal()">Cancel</button>
                <button class="btn btn-primary" type="submit">
                    <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-file-plus"/></svg>
                    Clone Quotation
                </button>
            </div>
        </form>
    </div>
</div>
@endif

{{-- Interactive Quotation Preview Modal matching prototype --}}
<div class="modal-wrap" id="quotePreviewModal" style="display:none; position: fixed; inset: 0; z-index: 1000; background: rgba(0,0,0,0.5); place-items: center; padding: 20px; overflow-y: auto;">
    <div class="modal modal-lg" style="background: #fff; border-radius: 12px; width: min(1000px, calc(100vw - 40px)); max-width: none; max-height: 90vh; display: flex; flex-direction: column; overflow: hidden; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);">
        <div class="modal-head" style="display: flex; justify-content: space-between; align-items: center; padding: 16px 20px; border-bottom: 1px solid #e2e8f0;">
            <h3 style="margin: 0; font-size: 16px; font-weight: 600;">Preview &bull; {{ $quote ? $quote->quote_number : 'QT-' . date('Y') . '-DRAFT' }}</h3>
            <button class="icon-btn" type="button" onclick="closeQuotePreviewModal()" aria-label="Close" style="background: none; border: none; cursor: pointer; padding: 4px;">
                <svg class="ic" style="width: 20px; height: 20px;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><use href="#i-x"/></svg>
            </button>
        </div>

        <div class="modal-body" style="padding: 20px; overflow-y: auto; flex: 1; background: #f8fafc;">
            {{-- Segmented Controls: sticky, so the format tabs stay at the top while the document scrolls --}}
            <div style="display: flex; justify-content: flex-start; position: sticky; top: -20px; z-index: 2; margin: -20px -20px 4px; padding: 20px 20px 12px; background: #f8fafc;">
                <div class="seg preview-seg" style="display: inline-flex; border: 1px solid #cbd5e1; border-radius: 8px; overflow: hidden; background: #fff;">
                    <label style="cursor: pointer; position: relative;">
                        <input type="radio" name="previewMode" value="tax" checked onchange="switchPreviewMode('tax')" style="position: absolute; opacity: 0;">
                        <span id="tabTax" style="display: inline-flex; align-items: center; padding: 6px 16px; font-size: 12.5px; font-weight: 600; background: var(--primary, #A30D2D); color: #fff;">Total + Tax</span>
                    </label>
                    <label style="cursor: pointer; position: relative;">
                        <input type="radio" name="previewMode" value="notax" onchange="switchPreviewMode('notax')" style="position: absolute; opacity: 0;">
                        <span id="tabNoTax" style="display: inline-flex; align-items: center; padding: 6px 16px; font-size: 12.5px; font-weight: 500; color: #475569;">Without Tax</span>
                    </label>
                    <label style="cursor: pointer; position: relative;">
                        <input type="radio" name="previewMode" value="tech" onchange="switchPreviewMode('tech')" style="position: absolute; opacity: 0;">
                        <span id="tabTech" style="display: inline-flex; align-items: center; padding: 6px 16px; font-size: 12.5px; font-weight: 500; color: #475569;">Technical</span>
                    </label>
                </div>
            </div>

            {{-- Document Host --}}
            <div id="previewDocHost" style="background: #fff; border-radius: 4px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); border: 1px solid #e2e8f0; overflow: hidden;">
                {{-- Content dynamically injected via renderQuoteDoc() --}}
            </div>
        </div>

        <div class="modal-foot" style="display: flex; justify-content: flex-end; align-items: center; gap: 8px; padding: 12px 20px; border-top: 1px solid #e2e8f0; background: #fff;">
            <button class="btn btn-ghost" type="button" onclick="sharePreviewLink()">
                <svg class="ic sm" style="width: 14px; height: 14px;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><use href="#i-send"/></svg>
                Share link
            </button>
            <a class="btn btn-ghost" href="{{ $quote ? route('quotations.pdf', $quote) : '#' }}" target="_blank" style="text-decoration: none;">
                <svg class="ic sm" style="width: 14px; height: 14px;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><use href="#i-download"/></svg>
                Download
            </a>
            <button class="btn btn-primary" type="button" onclick="printPreviewDoc()">
                <svg class="ic sm" style="width: 14px; height: 14px;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><use href="#i-printer"/></svg>
                Print
            </button>
        </div>
    </div>
</div>

@include('quotations.partials.document-css')
<style>
@media print {
    body * { visibility: hidden !important; }
    #quotePreviewModal, #previewDocHost, #previewDocHost * { visibility: visible !important; }
    #quotePreviewModal { position: absolute !important; inset: 0 !important; background: transparent !important; padding: 0 !important; display: block !important; }
    #previewDocHost { position: absolute !important; left: 0 !important; top: 0 !important; width: 100% !important; border: none !important; box-shadow: none !important; }
}
</style>

<script>
    // Save Draft → submitQuoteForm(); Submit for Approval → submitQuoteForm(true): saves the form, then submits it
    function submitQuoteForm(forApproval) {
        var form = document.getElementById('quoteMainForm');
        if (!form) return;
        if (form.reportValidity && !form.reportValidity()) {
            return;
        }
        document.getElementById('submitForApproval').value = forApproval ? '1' : '0';
        form.submit();
    }

    // next free items[N] field index (item and section rows share the items[] list)
    function nextItemIndex() {
        var max = -1;
        document.querySelectorAll('#itemsBody [name^="items["]').forEach(function (el) {
            var m = el.name.match(/^items\[(\d+)\]/);
            if (m) max = Math.max(max, +m[1]);
        });
        return max + 1;
    }

    function groupRowsFor(itemRow) {
        var rows = [itemRow];
        var next = itemRow.nextElementSibling;
        while (next && next.classList.contains('subdesc-row')) {
            rows.push(next);
            next = next.nextElementSibling;
        }
        return rows;
    }

    function syncGroupRowspans() {
        document.querySelectorAll('#itemsBody > tr.item-row').forEach(function (row) {
            var rowspan = groupRowsFor(row).length;
            [row.querySelector('.li-no'), row.querySelector('.li-qty'), row.querySelector('.li-unit'),
                row.querySelector('.li-price'), row.querySelector('.li-total')].forEach(function (cellContent) {
                if (cellContent) {
                    var cell = cellContent.closest('td');
                    cell.rowSpan = rowspan;
                    cell.style.verticalAlign = 'middle';
                }
            });
        });
    }

    // Re-index all item fields in DOM order and update row numbers (1, 2, 3...)
    function reindexAndRenumber() {
        var rows = document.querySelectorAll('#itemsBody > tr');
        var itemNo = 0;
        rows.forEach(function (row, idx) {
            row.querySelectorAll('[name^="items["]').forEach(function (el) {
                el.name = el.name.replace(/^items\[\d+\]/, 'items[' + idx + ']');
            });
            var photoStrip = row.querySelector('.li-photo');
            if (photoStrip) {
                photoStrip.dataset.prefix = 'items[' + idx + ']';
                photoStrip.querySelectorAll('input[type="hidden"]').forEach(function (inp) {
                    inp.name = 'items[' + idx + '][photos][]';
                });
            }
            // Only item-rows get a visible number; section and subdesc rows do not
            if (row.classList.contains('item-row')) {
                itemNo++;
                var noEl = row.querySelector('.li-no');
                if (noEl) noEl.textContent = itemNo;
            }
        });
        syncGroupRowspans();
    }

    function renumberItems() {
        reindexAndRenumber();
    }

    function createSectionRowElement(val) {
        var holder = document.createElement('tbody');
        holder.innerHTML = document.getElementById('sectionTemplate').innerHTML
            .match(/<tr[\s\S]*<\/tr>/)[0]
            .replace(/__PREFIX__/g, 'items[' + nextItemIndex() + ']');
        var tr = holder.firstElementChild;
        if (val) {
            var inp = tr.querySelector('.li-section');
            if (inp) inp.value = val;
        }
        return tr;
    }

    function addSectionRow(val) {
        var tbody = document.getElementById('itemsBody');
        var tr = createSectionRowElement(val);
        var firstSection = tbody.querySelector('.section-row');
        if (!firstSection && tbody.firstElementChild) {
            // First section heading: insert at the top above the first item
            tbody.insertBefore(tr, tbody.firstElementChild);
        } else {
            // Subsequent sections: append at the bottom
            tbody.appendChild(tr);
        }
        reindexAndRenumber();
        fitItemsToThreeRows();
        var wrap = tr.closest('.items-wrap');
        if (wrap) {
            if (!firstSection && tbody.firstElementChild === tr) {
                wrap.scrollTop = 0;
            } else {
                wrap.scrollTop = wrap.scrollHeight;
            }
        }
        var inp = tr.querySelector('.li-section');
        if (inp) {
            inp.focus();
            if (val) inp.select();
        }
        return tr;
    }

    function addSectionAbove(btn, val) {
        var targetRow = btn.closest('tr');
        var tbody = document.getElementById('itemsBody');
        var tr = createSectionRowElement(val || 'PRELIMINARIES');
        tbody.insertBefore(tr, targetRow);
        reindexAndRenumber();
        fitItemsToThreeRows();
        var inp = tr.querySelector('.li-section');
        if (inp) {
            inp.focus();
            inp.select();
        }
        return tr;
    }

    // Sub-description row: inserts a description-only row immediately after the parent item-row (or after the
    // last subdesc already attached to it), sharing the parent's qty / unit / price / total.
    function addSubDescRow(btn) {
        var tbody = document.getElementById('itemsBody');
        var parentRow = btn.closest('tr.item-row');
        // find the insertion point: after the last consecutive subdesc-row following the parent
        var insertBefore = parentRow ? parentRow.nextElementSibling : null;
        while (insertBefore && insertBefore.classList.contains('subdesc-row')) {
            insertBefore = insertBefore.nextElementSibling;
        }

        var index = nextItemIndex();
        var tplHtml = document.getElementById('subdescTemplate').innerHTML
            .match(/<tr[\s\S]*<\/tr>/)[0]
            .replace(/__PREFIX__/g, 'items[' + index + ']');

        var holder = document.createElement('tbody');
        holder.innerHTML = tplHtml;
        var tr = holder.firstElementChild;

        if (insertBefore) {
            tbody.insertBefore(tr, insertBefore);
        } else {
            tbody.appendChild(tr);
        }
        reindexAndRenumber();
        rteInit(tr);
        fitItemsToThreeRows();
        var area = tr.querySelector('.rte-area');
        if (area) area.focus();
        return tr;
    }

    function removeSubDescRow(btn) {
        btn.closest('.subdesc-row').remove();
        reindexAndRenumber();
        fitItemsToThreeRows();
    }

    function itemBlocks() {
        var blocks = [];
        var row = document.getElementById('itemsBody').firstElementChild;
        while (row) {
            if (row.classList.contains('item-row')) {
                var group = groupRowsFor(row);
                blocks.push(group);
                row = group[group.length - 1].nextElementSibling;
            } else {
                blocks.push([row]);
                row = row.nextElementSibling;
            }
        }
        return blocks;
    }

    function moveGroupRow(row, direction) {
        if (row.classList.contains('subdesc-row')) {
            var sibling = direction < 0 ? row.previousElementSibling : row.nextElementSibling;
            if (!sibling || !sibling.classList.contains('subdesc-row')) return;
            if (direction < 0) {
                row.parentNode.insertBefore(row, sibling);
            } else {
                row.parentNode.insertBefore(sibling, row);
            }
            return;
        }

        var blocks = itemBlocks();
        var blockIndex = blocks.findIndex(function (block) { return block[0] === row; });
        var nextIndex = blockIndex + direction;
        if (blockIndex < 0 || nextIndex < 0 || nextIndex >= blocks.length) return;
        var moving = blocks.splice(blockIndex, 1)[0];
        blocks.splice(nextIndex, 0, moving);
        var tbody = document.getElementById('itemsBody');
        blocks.forEach(function (block) {
            block.forEach(function (blockRow) { tbody.appendChild(blockRow); });
        });
    }

    function moveRowUp(btn) {
        var row = btn.closest('tr');
        moveGroupRow(row, -1);
        reindexAndRenumber();
        calcTotals();
    }

    function moveRowDown(btn) {
        var row = btn.closest('tr');
        moveGroupRow(row, 1);
        reindexAndRenumber();
        calcTotals();
    }

    function removeSectionRow(btn) {
        btn.closest('.section-row').remove();
        reindexAndRenumber();
    }

    function setSectionMenu(open) {
        var menu = document.getElementById('sectionMenu');
        if (!menu) return;
        menu.hidden = !open;
        var btn = document.getElementById('sectionMenuBtn');
        if (btn) btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    }

    function toggleSectionMenu(event) {
        event.stopPropagation();
        var menu = document.getElementById('sectionMenu');
        if (menu) setSectionMenu(menu.hidden);
    }

    document.addEventListener('click', function (e) {
        if (!e.target.closest('#sectionMenu') && !e.target.closest('#sectionMenuBtn')) {
            setSectionMenu(false);
        }
    });

    document.addEventListener('DOMContentLoaded', renumberItems);

    function addLineItemRow() {
        var tbody = document.getElementById('itemsBody');
        var index = nextItemIndex();

        var tr = document.createElement('tr');
        tr.className = 'item-row';
        tr.innerHTML = `
            <td class="li-no">${tbody.querySelectorAll('.item-row').length + 1}</td>
            <td>${document.getElementById('rteTemplate').innerHTML.replace('__NAME__', 'items[' + index + '][description]')}${document.getElementById('photoTemplate').innerHTML.replace('__PREFIX__', 'items[' + index + ']')}<div class="subdesc-add-wrap" style="margin-top:4px;"><button type="button" class="btn btn-ghost btn-xs subdesc-add-btn" onclick="addSubDescRow(this)" style="font-size:11px;padding:2px 8px;color:var(--muted);">+ Sub-description</button></div></td>
            <td class="num"><input class="li-in li-qty" type="number" step="any" min="0" name="items[${index}][quantity]" value="1" oninput="calcRow(this)" required></td>
            <td><input class="li-in li-unit" list="unitList" name="items[${index}][unit]" value="nos" placeholder="unit"></td>
            <td class="num"><input class="li-in li-price" type="number" step="0.01" min="0" name="items[${index}][unit_price]" value="0" oninput="calcRow(this)" required></td>
            <td class="num li-total"><b>AED 0.00</b></td>
            <td class="ta-c" style="white-space: nowrap;">
                <div class="row-actions" style="display: inline-flex; gap: 3px; align-items: center; justify-content: center;">
                    <button type="button" class="act" style="width: 26px; height: 26px; border-radius: 6px;" onclick="moveRowUp(this)" title="Move up" aria-label="Move up">
                        <svg class="ic xs" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-arrow-up"/></svg>
                    </button>
                    <button type="button" class="act" style="width: 26px; height: 26px; border-radius: 6px;" onclick="moveRowDown(this)" title="Move down" aria-label="Move down">
                        <svg class="ic xs" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-arrow-down"/></svg>
                    </button>
                    <button type="button" class="act" style="width: 26px; height: 26px; border-radius: 6px;" onclick="removeRow(this)" title="Remove item" aria-label="Remove item">
                        <svg class="ic xs" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-x"/></svg>
                    </button>
                </div>
            </td>
        `;
        tbody.appendChild(tr);
        reindexAndRenumber();
        rteInit(tr);
        calcTotals();
        fitItemsToThreeRows();
        var wrap = tr.closest('.items-wrap');
        if (wrap) wrap.scrollTop = wrap.scrollHeight;
    }

    /* ---------- Line item description editor (quotations.partials.rte) ---------- */
    // B / text colour / background colour on the selected text; the HTML is mirrored into the hidden .li-desc field
    // (the server keeps only bold, italic, underline and colours — App\Support\RichText)
    var rteSaved = null; // last selection inside an editor, restored after a colour picker steals focus

    function rteEscape(s) {
        return String(s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; });
    }

    function rteSync(area) {
        var store = area.parentNode.querySelector('.li-desc');
        store.value = area.textContent.trim() ? area.innerHTML : '';
        store.dispatchEvent(new Event('input', { bubbles: true }));
    }

    function rteInit(root) {
        (root || document).querySelectorAll('.rte').forEach(function (rte) {
            if (rte.dataset.ready) return;
            rte.dataset.ready = '1';
            var area = rte.querySelector('.rte-area');
            var store = rte.querySelector('.li-desc');
            var value = store.value;
            // saved descriptions are cleaned HTML; older ones are plain text with line breaks
            area.innerHTML = /<[a-z][^>]*>/i.test(value) ? value : rteEscape(value).replace(/\n/g, '<br>');
            store.value = area.textContent.trim() ? area.innerHTML : '';
            area.addEventListener('input', function () { rteSync(area); });
            // pasted text comes in plain, so outside formatting never sneaks in
            area.addEventListener('paste', function (e) {
                e.preventDefault();
                document.execCommand('insertText', false, (e.clipboardData || window.clipboardData).getData('text'));
            });
        });
    }

    function rteExec(rte, cmd, value) {
        var area = rte.querySelector('.rte-area');
        area.focus();
        if (rteSaved && rteSaved.area === area) {
            var sel = window.getSelection();
            sel.removeAllRanges();
            sel.addRange(rteSaved.range);
        }
        document.execCommand('styleWithCSS', false, true);
        document.execCommand(cmd, false, value || null);
        rteSync(area);
    }

    document.addEventListener('selectionchange', function () {
        var sel = window.getSelection();
        if (!sel.rangeCount) return;
        var node = sel.anchorNode && (sel.anchorNode.nodeType === 1 ? sel.anchorNode : sel.anchorNode.parentNode);
        var area = node && node.closest ? node.closest('.rte-area') : null;
        if (area) rteSaved = { area: area, range: sel.getRangeAt(0).cloneRange() };
    });
    // toolbar buttons must not take focus away from the text being formatted
    document.addEventListener('mousedown', function (e) {
        if (e.target.closest('.rte-btn')) e.preventDefault();
    });
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.rte-btn[data-cmd]');
        if (!btn) return;
        rteExec(btn.closest('.rte'), btn.dataset.cmd, btn.dataset.color);
    });
    // picking a colour applies it and makes it the button's colour for the next click
    document.addEventListener('input', function (e) {
        if (!e.target.matches('.rte-pick input[type=color]')) return;
        var split = e.target.closest('.rte-split');
        var btn = split.querySelector('.rte-btn');
        btn.dataset.color = e.target.value;
        var swatch = btn.querySelector('.rte-chip, .rte-bg');
        if (swatch) swatch.style.background = e.target.value;
        rteExec(btn.closest('.rte'), btn.dataset.cmd, e.target.value);
    });
    document.addEventListener('DOMContentLoaded', function () { rteInit(document.getElementById('itemsBody')); });

    /* ---------- Edit popup mode (?embed=1, opened from the Quotations list) ---------- */
    @if(request()->boolean('embed'))
    document.addEventListener('DOMContentLoaded', function () {
        // every action posts back here with embed=1, so the result opens in the popup again
        document.querySelectorAll('form').forEach(function (form) {
            if ((form.method || '').toLowerCase() !== 'post') return;
            var flag = document.createElement('input');
            flag.type = 'hidden';
            flag.name = 'embed';
            flag.value = '1';
            form.appendChild(flag);
        });
        // anything that leaves the quotation (project pages, the lists) opens in the full window, not the popup
        document.querySelectorAll('a[href]').forEach(function (a) {
            if (a.target || a.hasAttribute('download') || a.getAttribute('href').charAt(0) === '#') return;
            a.target = '_top';
        });
        document.querySelectorAll('form[action*="/projects/"]').forEach(function (form) { form.target = '_top'; });
    });
    @endif

    /* ---------- Line items: 3 rows visible, the rest scroll (header stays) ---------- */
    // rows grow with the description / photos, so the box height follows the first three rows' real size
    function fitItemsToThreeRows() {
        var table = document.getElementById('itemsTable');
        if (!table) return;
        var wrap = table.closest('.items-wrap');
        var rows = Array.prototype.slice.call(table.tBodies[0].rows);
        if (rows.length <= 3) { wrap.style.maxHeight = ''; return; }
        var height = table.tHead.offsetHeight + rows.slice(0, 3).reduce(function (sum, r) { return sum + r.offsetHeight; }, 0);
        var chrome = wrap.offsetHeight - wrap.clientHeight; // borders + horizontal scrollbar
        wrap.style.maxHeight = (height + chrome) + 'px';
    }
    document.addEventListener('DOMContentLoaded', function () {
        var body = document.getElementById('itemsBody');
        if (!body) return;
        fitItemsToThreeRows();
        new MutationObserver(fitItemsToThreeRows).observe(body, { childList: true });
        if (window.ResizeObserver) new ResizeObserver(fitItemsToThreeRows).observe(body);
        window.addEventListener('resize', fitItemsToThreeRows);
    });

    /* ---------- Add from catalog ---------- */
    function setCatalogMenu(open) {
        var menu = document.getElementById('catalogMenu');
        if (!menu) return;
        menu.hidden = !open;
        document.getElementById('catalogBtn').setAttribute('aria-expanded', open ? 'true' : 'false');
    }
    function toggleCatalogMenu(event) {
        event.stopPropagation();
        setCatalogMenu(document.getElementById('catalogMenu').hidden);
    }
    // fills the first row with an empty description (or a new row) with the item and unit, then jumps to its price
    function addCatalogItem(desc, unit) {
        var rows = Array.prototype.slice.call(document.querySelectorAll('#itemsBody .item-row'));
        var row = rows.find(function (r) { return !r.querySelector('.li-desc').value.trim(); });
        if (!row) {
            addLineItemRow();
            rows = document.querySelectorAll('#itemsBody .item-row');
            row = rows[rows.length - 1];
        }
        var area = row.querySelector('.rte-area');
        area.textContent = desc;
        rteSync(area);
        row.querySelector('.li-unit').value = unit;
        var qty = row.querySelector('.li-qty');
        if (!parseFloat(qty.value)) qty.value = 1;
        calcRow(qty);
        var price = row.querySelector('.li-price');
        price.focus();
        price.select();
    }
    document.addEventListener('click', function (e) {
        var item = e.target.closest('[data-catalog-desc]');
        if (item) {
            setCatalogMenu(false);
            addCatalogItem(item.dataset.catalogDesc, item.dataset.catalogUnit);
            return;
        }
        if (!e.target.closest('#catalogMenu')) setCatalogMenu(false);
    });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') setCatalogMenu(false); });

    /* ---------- Line item photos (quotations.partials.line-photos): up to 3, click to enlarge ---------- */
    var MAX_LINE_PHOTOS = 3;

    function refreshPhotoAdd(strip) {
        var count = strip.querySelectorAll('.ph').length;
        var add = strip.querySelector('.ph-add');
        add.hidden = count >= MAX_LINE_PHOTOS;
        add.querySelector('.ph-add-text').textContent = count ? 'Add' : 'Add photo';
    }

    function addPhotoThumb(strip, path, url) {
        var n = strip.querySelectorAll('.ph').length + 1;
        var ph = document.createElement('span');
        ph.className = 'ph';
        ph.innerHTML = '<img alt="Item photo ' + n + '" title="Click to enlarge" data-photo-view>'
            + '<button type="button" data-photo-remove title="Remove photo" aria-label="Remove photo ' + n + '">'
            + '<svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-x"/></svg></button>'
            + '<input type="hidden">';
        ph.querySelector('img').src = url;
        var hidden = ph.querySelector('input');
        hidden.name = strip.dataset.prefix + '[photos][]';
        hidden.value = path;
        strip.insertBefore(ph, strip.querySelector('.ph-add'));
        refreshPhotoAdd(strip);
    }

    document.addEventListener('change', function (e) {
        var input = e.target.closest('[data-photo-input]');
        if (!input || !input.files.length) return;
        var strip = input.closest('.li-photo');
        var file = input.files[0];
        input.value = ''; // the same file can be picked again after removing it
        if (strip.querySelectorAll('.ph').length >= MAX_LINE_PHOTOS) return;
        if (!/^image\/(jpeg|png|webp|gif)$/.test(file.type)) { alert('Please choose a JPG, PNG, WebP or GIF image.'); return; }
        if (file.size > 5 * 1024 * 1024) { alert('Photos can be up to 5 MB.'); return; }

        var text = strip.querySelector('.ph-add-text');
        text.textContent = 'Uploading…';
        var body = new FormData();
        body.append('photo', file);
        fetch("{{ route('quotations.line-photo') }}", {
            method: 'POST',
            body: body,
            headers: { 'X-CSRF-TOKEN': window.csrfToken, 'Accept': 'application/json' }
        })
            .then(function (res) { return res.ok ? res.json() : Promise.reject(res); })
            .then(function (data) { addPhotoThumb(strip, data.path, data.url); })
            .catch(function () { alert('The photo could not be uploaded. Please try again.'); refreshPhotoAdd(strip); });
    });

    // enlarged view, stepping through the photos of the same line item
    var viewerPhotos = [], viewerIndex = 0;
    function showViewerPhoto() {
        document.getElementById('photoViewerImg').src = viewerPhotos[viewerIndex];
        document.getElementById('photoViewerCount').textContent = viewerPhotos.length > 1 ? (viewerIndex + 1) + ' / ' + viewerPhotos.length : '';
        document.querySelectorAll('#photoViewer .pv-prev, #photoViewer .pv-next').forEach(function (b) { b.hidden = viewerPhotos.length < 2; });
    }
    function openPhotoViewer(img) {
        viewerPhotos = Array.prototype.map.call(img.closest('.li-photo').querySelectorAll('img[data-photo-view]'), function (i) { return i.src; });
        viewerIndex = Math.max(0, viewerPhotos.indexOf(img.src));
        showViewerPhoto();
        document.getElementById('photoViewer').hidden = false;
    }
    function stepPhotoViewer(step) {
        viewerIndex = (viewerIndex + step + viewerPhotos.length) % viewerPhotos.length;
        showViewerPhoto();
    }
    function closePhotoViewer() {
        document.getElementById('photoViewer').hidden = true;
    }

    document.addEventListener('click', function (e) {
        var remove = e.target.closest('[data-photo-remove]');
        if (remove) {
            var strip = remove.closest('.li-photo');
            remove.closest('.ph').remove();
            refreshPhotoAdd(strip);
            return;
        }
        var img = e.target.closest('img[data-photo-view]');
        if (img) openPhotoViewer(img);
    });
    document.addEventListener('keydown', function (e) {
        if (document.getElementById('photoViewer').hidden) return;
        if (e.key === 'Escape') closePhotoViewer();
        if (e.key === 'ArrowRight' && viewerPhotos.length > 1) stepPhotoViewer(1);
        if (e.key === 'ArrowLeft' && viewerPhotos.length > 1) stepPhotoViewer(-1);
    });

    /* ---------- Attachments card ---------- */
    function attSize(b) {
        return b < 1048576 ? Math.max(1, Math.round(b / 1024)) + ' KB' : (b / 1048576).toFixed(1) + ' MB';
    }
    function refreshAttachmentsEmpty() {
        document.getElementById('attachmentEmpty').hidden = !!document.querySelector('#attachmentList li');
    }
    function addAttachmentRow(data) {
        var key = 'n' + Date.now() + Math.floor(Math.random() * 1000);
        var li = document.createElement('li');
        li.innerHTML = '<svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-file-text"/></svg>'
            + '<span><a target="_blank" rel="noopener"></a></span><small></small>'
            + '<button type="button" class="att-remove" data-att-remove title="Remove attachment"><svg class="ic xs" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-x"/></svg></button>';
        var link = li.querySelector('a');
        link.href = data.url;
        link.download = data.name;
        link.textContent = data.name;
        li.querySelector('small').textContent = attSize(data.size);
        li.querySelector('.att-remove').setAttribute('aria-label', 'Remove ' + data.name);
        ['path', 'name', 'size'].forEach(function (field) {
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'attachments[' + key + '][' + field + ']';
            input.value = data[field];
            li.appendChild(input);
        });
        document.getElementById('attachmentList').appendChild(li);
        refreshAttachmentsEmpty();
    }
    function uploadAttachment(file) {
        if (file.size > 20 * 1024 * 1024) {
            alert(file.name + ' is larger than 20 MB.');
            return Promise.resolve();
        }
        var pending = document.createElement('li');
        pending.className = 'att-pending';
        pending.textContent = 'Uploading ' + file.name + '…';
        document.getElementById('attachmentList').appendChild(pending);
        refreshAttachmentsEmpty();
        var body = new FormData();
        body.append('file', file);
        return fetch("{{ route('quotations.attachment') }}", {
            method: 'POST',
            body: body,
            headers: { 'X-CSRF-TOKEN': window.csrfToken, 'Accept': 'application/json' }
        })
            .then(function (res) { return res.ok ? res.json() : Promise.reject(res); })
            .then(function (data) { addAttachmentRow(data); })
            .catch(function () { alert(file.name + ' could not be uploaded. Please try again.'); })
            .finally(function () { pending.remove(); refreshAttachmentsEmpty(); });
    }
    document.addEventListener('change', function (e) {
        if (e.target.id !== 'attachmentInput' || !e.target.files.length) return;
        var files = Array.prototype.slice.call(e.target.files);
        e.target.value = ''; // the upload box shows the uploaded list below instead of the picked files
        files.reduce(function (chain, file) { return chain.then(function () { return uploadAttachment(file); }); }, Promise.resolve());
    });
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-att-remove]');
        if (!btn) return;
        btn.closest('li').remove();
        refreshAttachmentsEmpty();
    });

    function removeRow(btn) {
        var tr = btn.closest('.item-row');
        var tbody = document.getElementById('itemsBody');
        if (tbody.querySelectorAll('.item-row').length > 1) {
            groupRowsFor(tr).forEach(function (row) { row.remove(); });
            reindexAndRenumber();
            calcTotals();
        } else {
            alert('Quotation must contain at least one line item.');
        }
    }

    function calcRow(input) {
        var tr = input.closest('.item-row');
        var qty = parseFloat(tr.querySelector('.li-qty').value) || 0;
        var price = parseFloat(tr.querySelector('.li-price').value) || 0;
        var total = qty * price;
        tr.querySelector('.li-total b').textContent = 'AED ' + total.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        calcTotals();
    }

    function calcTotals() {
        var subtotal = 0;
        document.querySelectorAll('.item-row').forEach(function(tr) {
            var qty = parseFloat(tr.querySelector('.li-qty').value) || 0;
            var price = parseFloat(tr.querySelector('.li-price').value) || 0;
            subtotal += (qty * price);
        });

        var discVal = parseFloat(document.getElementById('discountValue').value) || 0;
        var discType = document.getElementById('discountType').value;
        var discountAmount = 0;
        if (discType === 'pct') {
            discountAmount = subtotal * (discVal / 100);
        } else {
            discountAmount = Math.min(subtotal, discVal);
        }

        var taxable = Math.max(0, subtotal - discountAmount);
        var vatRate = parseFloat(document.getElementById('taxRate').value) || 0.05;
        var vatAmount = taxable * vatRate;
        var grandTotal = taxable + vatAmount;

        document.getElementById('sumSubtotal').textContent = 'AED ' + subtotal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        document.getElementById('sumDiscount').textContent = 'AED ' + discountAmount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        document.getElementById('sumTaxable').textContent = 'AED ' + taxable.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        document.getElementById('sumVat').textContent = 'AED ' + vatAmount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        document.getElementById('sumGrandTotal').textContent = 'AED ' + grandTotal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    // 30-Second Auto-Save Endpoint Hook
    @if($quote && in_array($quote->status, ['draft', 'rejected']))
    setInterval(function() {
        var form = document.getElementById('quoteMainForm');
        var formData = new FormData(form);
        formData.append('_method', 'POST');

        fetch("{{ route('quotations.autosave', $quote) }}", {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': window.csrfToken,
                'Accept': 'application/json'
            }
        })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (data.success) {
                var autoEl = document.getElementById('autosave');
                if (autoEl) autoEl.textContent = 'Auto-saved at ' + data.saved_at;
            }
        })
        .catch(function(err) {
            console.warn('Auto-save skipped:', err);
        });
    }, 30000);
    @endif

    // "Submit for Approval" asks first: checks the form, shows the live grand total and VAT, then saves + submits
    function openSubmitConfirm() {
        var form = document.getElementById('quoteMainForm');
        if (form && form.reportValidity && !form.reportValidity()) return;
        calcTotals();
        document.getElementById('submitConfirmTotal').textContent = document.getElementById('sumGrandTotal').textContent;
        document.getElementById('submitConfirmVat').textContent = document.getElementById('sumVat').textContent;
        document.getElementById('submitConfirmModal').style.display = 'grid';
    }
    function closeSubmitConfirm() {
        document.getElementById('submitConfirmModal').style.display = 'none';
    }
    document.addEventListener('keydown', function (e) {
        var m = document.getElementById('submitConfirmModal');
        if (e.key === 'Escape' && m && m.style.display !== 'none') closeSubmitConfirm();
    });

    function openRejectModal() {
        var modal = document.getElementById('rejectModal');
        if (modal) {
            modal.style.display = 'grid';
            modal.classList.add('open');
            var reason = document.getElementById('rejectReason');
            if (reason) reason.focus();
        }
    }
    function closeRejectModal() {
        var modal = document.getElementById('rejectModal');
        if (modal) {
            modal.classList.remove('open');
            modal.style.display = 'none';
        }
    }

    function openApproveModal() {
        var modal = document.getElementById('approveModal');
        if (modal) {
            modal.style.display = 'grid';
            modal.classList.add('open');
        }
    }
    function closeApproveModal() {
        var modal = document.getElementById('approveModal');
        if (modal) {
            modal.classList.remove('open');
            modal.style.display = 'none';
        }
    }

    function openAwardModal() {
        var modal = document.getElementById('awardModal');
        if (modal) {
            modal.style.display = 'grid';
            modal.classList.add('open');
        }
    }
    function closeAwardModal() {
        var modal = document.getElementById('awardModal');
        if (modal) {
            modal.classList.remove('open');
            modal.style.display = 'none';
        }
    }

    function openCreateProjectModal() {
        var modal = document.getElementById('createProjectModal');
        if (modal) {
            modal.style.display = 'grid';
            modal.classList.add('open');
        }
    }
    function closeCreateProjectModal() {
        var modal = document.getElementById('createProjectModal');
        if (modal) {
            modal.classList.remove('open');
            modal.style.display = 'none';
        }
    }
    @if($errors->hasAny(['name', 'branch_id', 'lead_id', 'quotation_id', 'engineer_id', 'contract_value', 'client_po_number', 'client_po_date', 'client_po_amount', 'po_file', 'project_documents', 'description']))
    openCreateProjectModal();
    @endif

    function openSendModal() {
        var modal = document.getElementById('sendModal');
        if (modal) modal.style.display = 'grid';
    }
    function closeSendModal() {
        var modal = document.getElementById('sendModal');
        if (modal) modal.style.display = 'none';
    }

    function openCloneModal() {
        var modal = document.getElementById('cloneModal');
        if (modal) {
            modal.style.display = 'grid';
            modal.classList.add('open');
        }
    }
    function closeCloneModal() {
        var modal = document.getElementById('cloneModal');
        if (modal) {
            modal.classList.remove('open');
            modal.style.display = 'none';
        }
    }

    function openReopenModal() {
        var modal = document.getElementById('reopenModal');
        if (modal) {
            modal.style.display = 'grid';
            modal.classList.add('open');
        }
    }
    function closeReopenModal() {
        var modal = document.getElementById('reopenModal');
        if (modal) {
            modal.classList.remove('open');
            modal.style.display = 'none';
        }
    }
    function enableQuoteEditing() {
        openReopenModal();
    }

    var revisedScopeInput = document.getElementById('revisedScopeDocuments');
    var revisedScopeDropZone = document.getElementById('revisedScopeDropZone');
    var revisedScopeFileList = document.getElementById('revisedScopeFileList');
    var clearRevisedScopeDocuments = document.getElementById('clearRevisedScopeDocuments');
    function renderRevisedScopeFiles() {
        if (!revisedScopeInput || !revisedScopeFileList || !clearRevisedScopeDocuments) return;
        revisedScopeFileList.replaceChildren();
        Array.prototype.forEach.call(revisedScopeInput.files, function (file) {
            var row = document.createElement('li');
            row.innerHTML = '<svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-file-text"/></svg><span></span><small></small>';
            row.querySelector('span').textContent = file.name;
            row.querySelector('small').textContent = attSize(file.size);
            revisedScopeFileList.appendChild(row);
        });
        clearRevisedScopeDocuments.hidden = revisedScopeInput.files.length === 0;
    }
    if (revisedScopeInput) {
        revisedScopeInput.addEventListener('change', function () {
            renderRevisedScopeFiles();
        });
    }
    if (clearRevisedScopeDocuments && revisedScopeInput) {
        clearRevisedScopeDocuments.addEventListener('click', function () {
            revisedScopeInput.value = '';
            renderRevisedScopeFiles();
        });
    }
    if (revisedScopeDropZone && revisedScopeInput) {
        ['dragenter', 'dragover'].forEach(function (eventName) {
            revisedScopeDropZone.addEventListener(eventName, function (event) {
                event.preventDefault();
                revisedScopeDropZone.classList.add('drag');
            });
        });
        ['dragleave', 'drop'].forEach(function (eventName) {
            revisedScopeDropZone.addEventListener(eventName, function (event) {
                event.preventDefault();
                revisedScopeDropZone.classList.remove('drag');
                if (eventName === 'drop' && event.dataTransfer) {
                    revisedScopeInput.files = event.dataTransfer.files;
                    renderRevisedScopeFiles();
                }
            });
        });
    }

    /* ---------- Interactive Quotation Preview Modal ---------- */
    var currentPreviewMode = 'tax';

    function openQuotePreviewModal() {
        var m = document.getElementById('quotePreviewModal');
        if (m) {
            m.style.display = 'grid';
            renderQuoteDoc(currentPreviewMode);
        }
    }

    function closeQuotePreviewModal() {
        var m = document.getElementById('quotePreviewModal');
        if (m) m.style.display = 'none';
    }

    function switchPreviewMode(mode) {
        currentPreviewMode = mode;
        var tabTax = document.getElementById('tabTax');
        var tabNoTax = document.getElementById('tabNoTax');
        var tabTech = document.getElementById('tabTech');

        var activeBg = 'var(--primary, #A30D2D)';
        var inactiveBg = 'transparent';
        var activeCol = '#fff';
        var inactiveCol = '#475569';

        if (tabTax) {
            tabTax.style.background = mode === 'tax' ? activeBg : inactiveBg;
            tabTax.style.color = mode === 'tax' ? activeCol : inactiveCol;
            tabTax.style.fontWeight = mode === 'tax' ? '600' : '500';
        }
        if (tabNoTax) {
            tabNoTax.style.background = mode === 'notax' ? activeBg : inactiveBg;
            tabNoTax.style.color = mode === 'notax' ? activeCol : inactiveCol;
            tabNoTax.style.fontWeight = mode === 'notax' ? '600' : '500';
        }
        if (tabTech) {
            tabTech.style.background = mode === 'tech' ? activeBg : inactiveBg;
            tabTech.style.color = mode === 'tech' ? activeCol : inactiveCol;
            tabTech.style.fontWeight = mode === 'tech' ? '600' : '500';
        }

        renderQuoteDoc(mode);
    }

    function numFmt(v) {
        return Number(v).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    var ONES = ['', 'ONE', 'TWO', 'THREE', 'FOUR', 'FIVE', 'SIX', 'SEVEN', 'EIGHT', 'NINE', 'TEN', 'ELEVEN', 'TWELVE', 'THIRTEEN', 'FOURTEEN', 'FIFTEEN', 'SIXTEEN', 'SEVENTEEN', 'EIGHTEEN', 'NINETEEN'];
    var TENS = ['', '', 'TWENTY', 'THIRTY', 'FORTY', 'FIFTY', 'SIXTY', 'SEVENTY', 'EIGHTY', 'NINETY'];
    function words(n) {
        if (n < 20) return ONES[n];
        if (n < 100) return TENS[Math.floor(n / 10)] + (n % 10 ? ' ' + ONES[n % 10] : '');
        if (n < 1000) return ONES[Math.floor(n / 100)] + ' HUNDRED' + (n % 100 ? ' AND ' + words(n % 100) : '');
        var scales = [[1e9, 'BILLION'], [1e6, 'MILLION'], [1e3, 'THOUSAND']];
        for (var i = 0; i < scales.length; i++) {
            var v = scales[i][0], w = scales[i][1];
            if (n >= v) return words(Math.floor(n / v)) + ' ' + w + (n % v ? (n % v < 100 ? ' AND ' : ' ') + words(n % v) : '');
        }
        return '';
    }
    function amountWords(amt) {
        var fils = Math.round(amt * 100) % 100;
        var aed = Math.floor(Math.round(amt * 100) / 100);
        return 'UAE DIRHAMS ' + (aed ? words(aed) : 'ZERO') + (fils ? ' AND ' + words(fils) + ' FILS' : '') + ' ONLY';
    }

    function renderQuoteDoc(mode) {
        var host = document.getElementById('previewDocHost');
        if (!host) return;

        var priced = mode !== 'tech';
        var rowsHtml = '';
        var subtotal = 0;

        var itemRows = document.querySelectorAll('#itemsBody .item-row');
        var itemNo = 0;
        // A priced row and its following sub-descriptions form one item group.
        document.querySelectorAll('#itemsBody > tr').forEach(function(row) {
            if (row.classList.contains('section-row')) {
                var title = row.querySelector('.li-section').value.trim();
                if (title) rowsHtml += `<tr class="sect"><td colspan="${priced ? 6 : 4}">${rteEscape(title)}</td></tr>`;
                return;
            }
            if (!row.classList.contains('item-row')) return;
            var idx = itemNo++;
            var desc = row.querySelector('.li-desc') ? row.querySelector('.li-desc').value.trim() : '';
            var qty = parseFloat(row.querySelector('.li-qty') ? row.querySelector('.li-qty').value : 0) || 0;
            var unit = row.querySelector('.li-unit') ? row.querySelector('.li-unit').value : 'nos';
            var price = parseFloat(row.querySelector('.li-price') ? row.querySelector('.li-price').value : 0) || 0;
            var lineTot = qty * price;
            subtotal += lineTot;
            var groupRows = groupRowsFor(row);
            var rowspan = groupRows.length;
            // the row's photos (thumbnails already uploaded) show under the description, as in the document
            var photos = Array.prototype.map.call(row.querySelectorAll('.li-photo img[data-photo-view]'), function (img) {
                return '<img src="' + img.src + '" alt="Item ' + (idx + 1) + ' image">';
            }).join('');
            var priceCells = priced
                ? `<td class="c" rowspan="${rowspan}" style="vertical-align: middle;">${price ? numFmt(price) : '0.00'}</td><td class="r" rowspan="${rowspan}" style="vertical-align: middle;">${numFmt(lineTot)}</td>`
                : '';

            rowsHtml += `<tr>
                <td class="c" rowspan="${rowspan}" style="vertical-align: middle;">${idx + 1}</td>
                <td class="d">${desc || 'Item ' + (idx + 1)}${photos ? '<div class="qd-photos">' + photos + '</div>' : ''}</td>
                <td class="c" rowspan="${rowspan}" style="vertical-align: middle;">${qty}</td>
                <td class="c" rowspan="${rowspan}" style="vertical-align: middle;">${rteEscape(unit)}</td>
                ${priceCells}
            </tr>`;
            groupRows.slice(1).forEach(function (subdescRow) {
                var subdesc = subdescRow.querySelector('.li-desc') ? subdescRow.querySelector('.li-desc').value.trim() : '';
                rowsHtml += `<tr><td class="d">${subdesc}</td></tr>`;
            });
        });

        if (!itemRows.length) {
            rowsHtml = `<tr><td colspan="${priced ? 6 : 4}" class="c" style="padding: 16px;">No line items added yet.</td></tr>`;
        }

        var discVal = parseFloat(document.getElementById('discountValue') ? document.getElementById('discountValue').value : 0) || 0;
        var discType = document.getElementById('discountType') ? document.getElementById('discountType').value : 'fixed';
        var discountAmount = (discType === 'pct') ? (subtotal * (discVal / 100)) : Math.min(subtotal, discVal);
        var taxable = Math.max(0, subtotal - discountAmount);
        var vatRate = parseFloat(document.getElementById('taxRate') ? document.getElementById('taxRate').value : 0.05) || 0.05;
        var vatAmount = taxable * vatRate;
        var grandTotal = taxable + vatAmount;
        var effectiveTotal = mode === 'tax' ? grandTotal : taxable;

        var sumRow = function(label, val, cls) {
            return `<tr class="${cls || 'sum'}"><td colspan="4" style="text-align:right">${label}</td><td class="c">AED</td><td class="r">${val}</td></tr>`;
        };

        var totalsHtml = '';
        if (priced) {
            if (discountAmount > 0 || mode === 'tax') {
                totalsHtml += sumRow('SUB TOTAL', numFmt(subtotal));
            }
            if (discountAmount > 0) {
                totalsHtml += sumRow('DISCOUNT', '− ' + numFmt(discountAmount));
            }
            if (mode === 'tax') {
                totalsHtml += sumRow('VAT ' + Math.round(vatRate * 100) + '%', numFmt(vatAmount));
            }
            totalsHtml += sumRow(mode === 'tax' ? 'TOTAL AMOUNT (INCL. VAT)' : 'TOTAL AMOUNT', numFmt(effectiveTotal), 'grand');
            totalsHtml += `<tr class="words"><td colspan="6">${amountWords(effectiveTotal)}</td></tr>`;
        }

        var qDateInput = document.querySelector('input[name="quote_date"]');
        var qDate = qDateInput ? qDateInput.value : '{{ date("d M Y") }}';
        var quoteNum = '{{ $quote ? $quote->quote_number : "QT-" . date("Y") . "-DRAFT" }}';
        var clientName = '{{ addslashes($lead->company_name) }}';
        var clientAddr = '{{ addslashes($lead->address ?? "—") }}';
        var prNum = '{{ addslashes($lead->rfq_number ?: "N/A") }}';
        var engineerName = '{{ addslashes(strtoupper($lead->assignedEngineer?->name ?? "SARAH THOMAS")) }}';
        var subject = '{{ addslashes($lead->subject) }}';
        var termsVal = document.querySelector('textarea[name="terms_and_conditions"]') ? document.querySelector('textarea[name="terms_and_conditions"]').value : 'Standard commercial terms apply.';
        var techDescVal = document.querySelector('textarea[name="technical_description"]') ? document.querySelector('textarea[name="technical_description"]').value : '';

        var html = `
            <div class="qdoc">
                <div class="qd-head">
                    <img src="{{ asset('images/rich-mount-logo.png') }}" alt="Rich Mount Contracting &amp; Interiors">
                    <div class="qd-co">
                        <div class="ar">ريتش ماونت للمقاولات والداخلية ذ م م</div>
                        <div class="en">Rich Mount Contracting &amp; Interiors L.L.C</div>
                        <small>Tel: 042950285 | PO Box 97921 | Dubai, UAE</small>
                        <small>www.rich-mounts.com | info@rich-mounts.com</small>
                    </div>
                </div>

                <div class="qd-title">${mode === 'tech' ? 'TECHNICAL QUOTATION' : 'QUOTATION'}</div>

                <table class="qd-info">
                    <tr><th colspan="2">CLIENT DETAILS:</th><th colspan="2">QUOTE DETAILS:</th></tr>
                    <tr><td class="k">Company Name:</td><td>${clientName}</td><td class="k">Date:</td><td>${qDate}</td></tr>
                    <tr><td class="k">Address:</td><td>${clientAddr}</td><td class="k">Quote Number:</td><td>${quoteNum}</td></tr>
                    <tr><td class="k">PR Number:</td><td>${prNum}</td><td class="k">Site Visited By:</td><td>${engineerName}</td></tr>
                </table>

                <div class="qd-proj">PROJECT: ${subject}</div>

                <table class="qd-items">
                    <thead>
                        <tr>
                            <th>Sl. No.</th>
                            <th>Description</th>
                            <th>Qty</th>
                            <th>Unit</th>
                            ${priced ? '<th>Rate</th><th>Price</th>' : ''}
                        </tr>
                    </thead>
                    <tbody>
                        ${rowsHtml}
                        ${totalsHtml}
                    </tbody>
                </table>

                ${mode === 'tech' && techDescVal ? `<div class="qd-box"><div style="background:#DCE6F1; font-weight:700; padding:4px 6px; font-size:11px; border:1px solid #444;">TECHNICAL DESCRIPTION:</div><div style="border:1px solid #444; border-top:none; padding:6px; font-size:11px; white-space:pre-line;">${techDescVal}</div></div>` : ''}

                <table class="qd-box">
                    <tr><th>TERMS AND CONDITIONS:</th></tr>
                    <tr><td><p>${termsVal}</p></td></tr>
                </table>

                ${priced ? `
                <table class="qd-box">
                    <tr><th>PAYMENT TERMS:</th></tr>
                    <tr>
                        <td>
                            <p><span class="red">1. 50% Advance against security cheque and balance after completion of work.</span><br>2. All invoices will be charged ${Math.round(vatRate * 100)}% VAT as per UAE law.<br>3. Payment Cheque or Bank Payee to: <b>RICH MOUNT CONTRACTING &amp; INTERIORS L.L.C.</b></p>
                        </td>
                    </tr>
                </table>
                ` : ''}

                <table class="qd-box">
                    <tr><th colspan="2">POINT OF CONTACT</th></tr>
                    <tr><td class="k">Engineer Name:</td><td>${engineerName}</td></tr>
                    <tr><td class="k">Position:</td><td>Project Engineer</td></tr>
                    <tr><td class="k">Email:</td><td>{{ $lead->assignedEngineer?->email ?? "engineer@richmount.ae" }}</td></tr>
                </table>

                <div class="qd-foot">Office No. 05, Al Wasl Building, Street 47, Al Khor 2. Dubai UAE</div>
            </div>
        `;

        host.innerHTML = html;
    }

    function printPreviewDoc() {
        window.print();
    }

    function sharePreviewLink() {
        var url = window.location.href.split('#')[0];
        if (navigator.clipboard) {
            navigator.clipboard.writeText(url).then(function() {
                alert('Quotation link copied to clipboard!');
            });
        } else {
            prompt('Copy quotation link:', url);
        }
    }

    // Auto-open preview modal if requested in URL parameter (?preview=1)
    document.addEventListener('DOMContentLoaded', function() {
        if (window.location.search.indexOf('preview=1') !== -1) {
            openQuotePreviewModal();
        }
    });

    // Forward mouse-wheel scroll from fixed timeline bar and summary sidebar to content
    document.addEventListener('DOMContentLoaded', function() {
        var main = document.querySelector('.q-main');
        var bar = document.querySelector('.status-bar');
        var side = document.querySelector('.q-side');
        if (main && bar) {
            bar.addEventListener('wheel', function(e) {
                main.scrollTop += e.deltaY;
            }, { passive: true });
        }
        if (main && side) {
            side.addEventListener('wheel', function(e) {
                if (side.scrollHeight <= side.clientHeight) {
                    main.scrollTop += e.deltaY;
                }
            }, { passive: true });
        }
    });
</script>
@endsection
