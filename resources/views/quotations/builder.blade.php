@extends('layouts.app')

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
        <p class="sub">{{ $lead->company_name }} &bull; {{ $lead->subject }}</p>
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
                <button class="btn btn-soft" type="button" onclick="submitQuoteForm()">
                    <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-check"/></svg>
                    Save Draft
                </button>
                @can('quotes.submit')
                <form action="{{ route('quotations.submit', $quote) }}" method="POST" style="display:inline;">
                    @csrf
                    <button class="btn btn-primary" type="submit">
                        <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-send"/></svg>
                        Submit for Approval
                    </button>
                </form>
                @endcan
            @elseif($quote->status === 'pending')
                @can('quotes.approve')
                <button class="btn btn-ghost" type="button" onclick="openRejectModal()" style="color: #DC2626; border-color: #FCA5A5;">
                    <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-x"/></svg>
                    Reject
                </button>
                <form action="{{ route('quotations.approve', $quote) }}" method="POST" style="display:inline;">
                    @csrf
                    <button class="btn btn-primary" type="submit" style="background: #16A34A; border-color: #16A34A; color: #fff;">
                        <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-check"/></svg>
                        Approve
                    </button>
                </form>
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
                <form action="{{ route('quotations.award', $quote) }}" method="POST" style="display:inline;" onsubmit="return confirm('Mark quotation as Awarded? This initiates project creation.');">
                    @csrf
                    <button class="btn btn-primary" type="submit">
                        <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-award"/></svg>
                        Mark as Awarded
                    </button>
                </form>
                @endcan
            @elseif($quote->status === 'awarded')
                @if($quote->project)
                    <a href="{{ route('projects.show', $quote->project) }}" class="btn btn-primary">
                        <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-briefcase"/></svg>
                        Open Project
                    </a>
                @else
                    @can('projects.create')
                    <form action="{{ route('projects.create-from-quote', $quote) }}" method="POST" style="display:inline;">
                        @csrf
                        <button class="btn btn-primary" type="submit">
                            <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-briefcase"/></svg>
                            Create Project
                        </button>
                    </form>
                    @endcan
                @endif
            @endif
        @else
            <button class="btn btn-primary" type="button" onclick="submitQuoteForm()">
                <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-check"/></svg>
                Create Draft Quotation
            </button>
        @endif
    </div>
</section>

<div class="dash-scroll">
    {{-- Open Quotation Workspace Tabs with Minimize option --}}
    <div class="ws-tabs-bar" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; gap: 8px;">
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
        <button class="btn btn-ghost btn-sm" type="button" id="minimizeBtn" onclick="toggleMinimize()" style="font-size: 12px; padding: 4px 10px; height: 28px; border-radius: 6px; display: inline-flex; align-items: center; gap: 4px;">
            <svg class="ic xs" id="minimizeIcon" style="width: 13px; height: 13px;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-chevron-down"/></svg>
            <span id="minimizeText">Minimize</span>
        </button>
    </div>

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
                                    <th style="width: 5%;">#</th>
                                    <th style="width: 48%;">Description</th>
                                    <th class="num" style="width: 12%;">Qty</th>
                                    <th style="width: 12%;">Unit</th>
                                    <th class="num" style="width: 13%;">Unit Price (AED)</th>
                                    <th class="num" style="width: 15%;">Total Amount</th>
                                    <th style="width: 5%;"></th>
                                </tr>
                            </thead>
                            <tbody id="itemsBody">
                                @php
                                    $existingItems = $quote ? $quote->lineItems : [];
                                @endphp
                                @if(count($existingItems) > 0)
                                    @foreach($existingItems as $i => $item)
                                        <tr class="item-row">
                                            <td class="li-no">{{ $i + 1 }}</td>
                                            <td>
                                                <textarea class="li-in li-desc" name="items[{{ $i }}][description]" rows="3" placeholder="Item specifications &amp; scope">{!! e($item->description) !!}</textarea>
                                            </td>
                                            <td class="num">
                                                <input class="li-in li-qty" type="number" step="any" min="0" name="items[{{ $i }}][quantity]" value="{{ (float) $item->quantity }}" oninput="calcRow(this)" required>
                                            </td>
                                            <td>
                                                <input class="li-in li-unit" list="unitList" name="items[{{ $i }}][unit]" value="{{ $item->unit }}" placeholder="unit">
                                            </td>
                                            <td class="num">
                                                <input class="li-in li-price" type="number" step="0.01" min="0" name="items[{{ $i }}][unit_price]" value="{{ $item->unit_price }}" oninput="calcRow(this)" required>
                                            </td>
                                            <td class="num li-total">
                                                <b>AED {{ number_format($item->total_amount, 2) }}</b>
                                            </td>
                                            <td class="ta-c">
                                                <button type="button" class="act" onclick="removeRow(this)" title="Remove item">
                                                    <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-x"/></svg>
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr class="item-row">
                                        <td class="li-no">1</td>
                                        <td>
                                            <textarea class="li-in li-desc" name="items[0][description]" rows="3" placeholder="Supply and installation of works according to specifications..."></textarea>
                                        </td>
                                        <td class="num">
                                            <input class="li-in li-qty" type="number" step="any" min="0" name="items[0][quantity]" value="1" oninput="calcRow(this)" required>
                                        </td>
                                        <td>
                                            <input class="li-in li-unit" list="unitList" name="items[0][unit]" value="lump sum" placeholder="unit">
                                        </td>
                                        <td class="num">
                                            <input class="li-in li-price" type="number" step="0.01" min="0" name="items[0][unit_price]" value="0" oninput="calcRow(this)" required>
                                        </td>
                                        <td class="num li-total"><b>AED 0.00</b></td>
                                        <td class="ta-c">
                                            <button type="button" class="act" onclick="removeRow(this)" title="Remove item">
                                                <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-x"/></svg>
                                            </button>
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>

                    <datalist id="unitList">
                        <option value="lump sum">
                        <option value="m²">
                        <option value="m³">
                        <option value="m">
                        <option value="ton">
                        <option value="nos">
                        <option value="set">
                        <option value="day">
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
                                <input type="number" name="discount_value" id="discountValue" min="0" step="0.01" style="width: 80px;" value="{{ $quote ? $quote->discount_value : 0 }}" oninput="calcTotals()">
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

{{-- Reject Reason Modal --}}
@if($quote && $quote->status === 'pending')
<div class="modal-wrap" id="rejectModal" style="display: none;">
    <div class="modal modal-md" role="dialog" aria-modal="true">
        <div class="modal-head">
            <div>
                <h3>Revision Required</h3>
                <p>Explain changes needed before quotation can be approved</p>
            </div>
            <button class="icon-btn" type="button" onclick="closeRejectModal()" aria-label="Close">
                <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-x"/></svg>
            </button>
        </div>
        <form method="POST" action="{{ route('quotations.reject', $quote) }}">
            @csrf
            <div class="modal-body">
                <label class="field">
                    <span class="field-label">Reason for Rejection / Revision Notes *</span>
                    <textarea name="reason" rows="4" required placeholder="e.g. Unit rates on line item 2 must be checked against current supplier discount..."></textarea>
                </label>
            </div>
            <div class="modal-foot">
                <button class="btn btn-ghost" type="button" onclick="closeRejectModal()">Cancel</button>
                <button class="btn btn-primary" type="submit" style="background: red;">Submit Revision Request</button>
            </div>
        </form>
    </div>
</div>
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
    <div class="modal modal-md" role="dialog" aria-modal="true" aria-labelledby="reopenModalTitle" style="max-width: 540px;">
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
        <form id="reopenForm" method="POST" action="{{ route('quotations.reopen', $quote) }}">
            @csrf
            <div class="modal-body" style="padding: 20px;">
                <label class="field" style="margin-bottom: 14px;">
                    <span class="field-label" style="font-weight: 600; font-size: 13px; margin-bottom: 6px; display: block;">
                        Reason for edit &amp; changes required <span style="color: var(--primary);">*</span>
                    </span>
                    <textarea name="reason" rows="4" required maxlength="500" placeholder="Describe the changes required in scope, quantities, or pricing..."></textarea>
                </label>

                <label class="field" style="margin: 0;">
                    <span class="field-label" style="font-weight: 600; font-size: 13px; margin-bottom: 6px; display: block;">Assign to</span>
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
                    <span style="font-size: 11.5px; color: var(--muted); margin-top: 4px; display: block;">
                        Edit it yourself, or assign to an engineer from {{ $lead->branch->name }}
                    </span>
                </label>
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
    <div class="modal modal-lg" style="background: #fff; border-radius: 12px; width: 100%; max-width: 900px; max-height: 90vh; display: flex; flex-direction: column; overflow: hidden; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);">
        <div class="modal-head" style="display: flex; justify-content: space-between; align-items: center; padding: 16px 20px; border-bottom: 1px solid #e2e8f0;">
            <h3 style="margin: 0; font-size: 16px; font-weight: 600;">Preview &bull; {{ $quote ? $quote->quote_number : 'QT-' . date('Y') . '-DRAFT' }}</h3>
            <button class="icon-btn" type="button" onclick="closeQuotePreviewModal()" aria-label="Close" style="background: none; border: none; cursor: pointer; padding: 4px;">
                <svg class="ic" style="width: 20px; height: 20px;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><use href="#i-x"/></svg>
            </button>
        </div>

        <div class="modal-body" style="padding: 20px; overflow-y: auto; flex: 1; background: #f8fafc;">
            {{-- Segmented Controls --}}
            <div style="display: flex; justify-content: flex-start; margin-bottom: 16px;">
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

<style>
.qdoc { font: 12px Inter, Arial, sans-serif; color: #111; background: #fff; max-width: 820px; margin: 0 auto; padding: 24px 28px; }
.qdoc table { width: 100%; border-collapse: collapse; margin-bottom: 0; }
.qdoc td, .qdoc th { border: 1px solid #444; padding: 5px 6px; vertical-align: middle; }
.qd-head { display: flex; justify-content: space-between; align-items: center; gap: 16px; border-bottom: 1.5px solid #333; padding-bottom: 10px; }
.qd-head img { width: 150px; height: auto; }
.qd-co { text-align: right; line-height: 1.45; }
.qd-co .ar { color: #941C3C; font-size: 16px; font-weight: 700; direction: rtl; }
.qd-co .en { color: #941C3C; font-size: 14px; font-weight: 600; }
.qd-co small { display: block; font-size: 11.5px; color: #222; }
.qd-title { text-align: center; font-size: 18px; font-weight: 700; letter-spacing: 0.5px; margin: 16px 0 10px; }
.qd-info td { font-size: 11px; width: 25%; }
.qd-info th { background: #F2F2F2; text-align: left; font-size: 11px; }
.qd-info td.k { font-weight: 700; }
.qd-proj { border: 1px solid #444; margin: 10px 0; padding: 6px 8px 16px 8px; font-weight: 700; font-size: 11px; }
.qd-items th { background: #F2F2F2; font-size: 11px; text-align: center; }
.qd-items td { font-size: 11px; }
.qd-items .c { text-align: center; }
.qd-items .r { text-align: right; font-weight: 700; white-space: nowrap; }
.qd-items td.d { width: 45%; white-space: pre-line; }
.qd-items .sum td { background: #DDF3E3; font-weight: 700; }
.qd-items .grand td { background: #C9EDD3; font-weight: 700; }
.qd-items .words td { background: #DDF3E3; text-align: center; font-weight: 700; font-size: 11px; }
.qd-box { margin-top: 10px; }
.qd-box th { background: #DCE6F1; text-align: left; font-size: 11px; }
.qd-box td { font-size: 11px; line-height: 1.5; }
.qd-box p { margin: 0; white-space: pre-line; }
.qd-box .red { color: #C00000; font-weight: 700; }
.qd-box td.k { font-weight: 700; width: 40%; }
.qd-foot { margin-top: 24px; background: #941C3C; color: #fff; text-align: center; border-radius: 999px; padding: 6px 12px; font-size: 11.5px; }
@media print {
    body * { visibility: hidden !important; }
    #quotePreviewModal, #previewDocHost, #previewDocHost * { visibility: visible !important; }
    #quotePreviewModal { position: absolute !important; inset: 0 !important; background: transparent !important; padding: 0 !important; display: block !important; }
    #previewDocHost { position: absolute !important; left: 0 !important; top: 0 !important; width: 100% !important; border: none !important; box-shadow: none !important; }
}
</style>

<script>
    function submitQuoteForm() {
        var form = document.getElementById('quoteMainForm');
        if (!form) return;
        if (form.reportValidity && !form.reportValidity()) {
            return;
        }
        form.submit();
    }

    function addLineItemRow() {
        var tbody = document.getElementById('itemsBody');
        var index = tbody.querySelectorAll('.item-row').length;

        var tr = document.createElement('tr');
        tr.className = 'item-row';
        tr.innerHTML = `
            <td class="li-no">${index + 1}</td>
            <td><textarea class="li-in li-desc" name="items[${index}][description]" rows="3" placeholder="Item specifications & scope"></textarea></td>
            <td class="num"><input class="li-in li-qty" type="number" step="any" min="0" name="items[${index}][quantity]" value="1" oninput="calcRow(this)" required></td>
            <td><input class="li-in li-unit" list="unitList" name="items[${index}][unit]" value="nos" placeholder="unit"></td>
            <td class="num"><input class="li-in li-price" type="number" step="0.01" min="0" name="items[${index}][unit_price]" value="0" oninput="calcRow(this)" required></td>
            <td class="num li-total"><b>AED 0.00</b></td>
            <td class="ta-c"><button type="button" class="act" onclick="removeRow(this)" title="Remove item"><svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-x"/></svg></button></td>
        `;
        tbody.appendChild(tr);
        calcTotals();
    }

    function removeRow(btn) {
        var tr = btn.closest('.item-row');
        var tbody = document.getElementById('itemsBody');
        if (tbody.querySelectorAll('.item-row').length > 1) {
            tr.remove();
            // re-index
            tbody.querySelectorAll('.item-row').forEach(function(row, idx) {
                row.querySelector('.li-no').textContent = idx + 1;
            });
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

    function openRejectModal() {
        document.getElementById('rejectModal').style.display = 'grid';
    }
    function closeRejectModal() {
        document.getElementById('rejectModal').style.display = 'none';
    }

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

    function toggleMinimize() {
        var layout = document.querySelector('.q-layout');
        var btnText = document.getElementById('minimizeText');
        var iconUse = document.querySelector('#minimizeIcon use');
        if (layout) {
            if (layout.style.display === 'none') {
                layout.style.display = 'grid';
                btnText.textContent = 'Minimize';
                if (iconUse) iconUse.setAttribute('href', '#i-chevron-down');
            } else {
                layout.style.display = 'none';
                btnText.textContent = 'Expand';
                if (iconUse) iconUse.setAttribute('href', '#i-chevron-right');
            }
        }
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
        itemRows.forEach(function(row, idx) {
            var desc = row.querySelector('.li-desc') ? row.querySelector('.li-desc').value.trim() : '';
            var qty = parseFloat(row.querySelector('.li-qty') ? row.querySelector('.li-qty').value : 0) || 0;
            var unit = row.querySelector('.li-unit') ? row.querySelector('.li-unit').value : 'nos';
            var price = parseFloat(row.querySelector('.li-price') ? row.querySelector('.li-price').value : 0) || 0;
            var lineTot = qty * price;
            subtotal += lineTot;

            rowsHtml += `<tr>
                <td class="c">${idx + 1}</td>
                <td class="d">${desc || 'Item ' + (idx + 1)}</td>
                <td class="c">${qty}</td>
                <td class="c">${unit}</td>
                ${priced ? `<td class="c">${price ? numFmt(price) : '0.00'}</td><td class="r">${numFmt(lineTot)}</td>` : ''}
            </tr>`;
        });

        if (!itemRows.length) {
            rowsHtml = `<tr><td colspan="${priced ? 6 : 4}" class="c" style="padding: 16px;">No line items added yet.</td></tr>`;
        }

        var discVal = parseFloat(document.getElementById('discountInput') ? document.getElementById('discountInput').value : 0) || 0;
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
                            <p>
                                <span class="red">1. 50% Advance against security cheque and balance after completion of work.</span><br>
                                2. All invoices will be charged ${Math.round(vatRate * 100)}% VAT as per UAE law.<br>
                                3. Payment Cheque or Bank Payee to: <b>RICH MOUNT CONTRACTING &amp; INTERIORS L.L.C.</b>
                            </p>
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
</script>
@endsection
