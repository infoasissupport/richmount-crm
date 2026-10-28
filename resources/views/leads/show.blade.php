@extends('layouts.app')

@section('title', $lead->lead_number . ' · ' . $lead->company_name)
@section('page_id', 'lead')

@section('breadcrumbs')
    <svg class="ic sep" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-chevron-right"/></svg>
    <a class="crumb" href="{{ route('leads.index') }}">Leads</a>
    <svg class="ic sep" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-chevron-right"/></svg>
    <span class="crumb current">{{ $lead->lead_number }}</span>
@endsection

@section('content')
<section class="page-head" id="pageHead">
    <div>
        <h1>{{ $lead->company_name }}</h1>
        <p class="sub">{{ $lead->lead_number }} &bull; {{ $lead->subject }} &bull; {{ $lead->branch->name }}</p>
    </div>
    <div class="page-actions">
        @can('leads.edit')
        <button class="btn btn-ghost" type="button" onclick="openEditLeadModal()">
            <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-edit"/></svg>
            Edit Lead
        </button>
        @endcan

        @can('leads.assign')
        <button class="btn btn-ghost" type="button" onclick="openAssignModal()">
            <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-user"/></svg>
            Assign Engineer
        </button>
        @endcan

        @can('leads.delete')
        @if(!$lead->latestQuotation)
        <form action="{{ route('leads.destroy', $lead) }}" method="POST" style="display:inline;" onsubmit="return confirm('Delete lead {{ $lead->lead_number }} ({{ $lead->company_name }})?');">
            @csrf
            @method('DELETE')
            <button class="btn btn-ghost" type="submit" style="color: #dc2626;">
                <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-x"/></svg>
                Delete
            </button>
        </form>
        @endif
        @endcan

        @can('quotes.create')
        <a class="btn btn-primary" href="{{ route('quotations.builder', ['lead' => $lead->id]) }}">
            <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-file-plus"/></svg>
            Create Quotation
        </a>
        @endcan
    </div>
</section>

<div class="dash-scroll">
    {{-- Status Stepper --}}
    @php
        $steps = ['new' => 1, 'draft' => 2, 'sent' => 3, 'awarded' => 4, 'project' => 5];
        $currentStep = $steps[$lead->stage] ?? 1;
    @endphp
    <section class="card status-bar">
        <ol class="stepper wide" id="leadStepper">
            <li class="{{ $currentStep > 1 ? 'done' : ($currentStep == 1 ? 'current' : '') }}">
                <span>1</span>New Lead
            </li>
            <li class="{{ $currentStep > 2 ? 'done' : ($currentStep == 2 ? 'current' : '') }}">
                <span>2</span>Draft Quotation
            </li>
            <li class="{{ $currentStep > 3 ? 'done' : ($currentStep == 3 ? 'current' : '') }}">
                <span>3</span>Sent Quotation
            </li>
            <li class="{{ $currentStep > 4 ? 'done' : ($currentStep == 4 ? 'current' : '') }}">
                <span>4</span>Awarded
            </li>
            <li class="{{ $currentStep >= 5 ? 'done' : '' }}">
                <span>5</span>Project
            </li>
        </ol>
    </section>

    {{-- Details Grid --}}
    <div class="detail-grid">
        <section class="card">
            <header class="card-head">
                <h2>
                    <span class="head-ic">
                        <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-file-text"/></svg>
                    </span>
                    Lead Details
                </h2>
                <span class="pill pill-primary"><i></i>{{ $lead->status }}</span>
            </header>
            <dl class="kv" id="leadKv">
                <dt>Lead Number</dt><dd><strong>{{ $lead->lead_number }}</strong></dd>
                <dt>Lead Date</dt><dd>{{ $lead->lead_date ? $lead->lead_date->format('d M Y') : '—' }}</dd>
                <dt>Customer / Company</dt><dd><b>{{ $lead->company_name }}</b></dd>
                <dt>RFQ / PR Number</dt><dd><code>{{ $lead->rfq_number ?: 'N/A' }}</code></dd>
                <dt>Project Subject</dt><dd>{{ $lead->subject }}</dd>
                <dt>Department</dt><dd>{{ ucfirst($lead->department) }}</dd>
                <dt>Branch</dt><dd>{{ $lead->branch->name }} ({{ $lead->branch->quote_format }})</dd>
                <dt>Assigned Engineer</dt>
                <dd>
                    @if($lead->assignedEngineer)
                        <span class="cell-who">
                            <span class="avatar sm av-blue">{{ $lead->assignedEngineer->initials }}</span>
                            <span class="cell-text"><b>{{ $lead->assignedEngineer->name }}</b></span>
                        </span>
                    @else
                        <span style="color: #999; font-style: italic;">Unassigned</span>
                    @endif
                </dd>
                <dt>Lead Source</dt><dd>{{ $lead->source }}</dd>
                <dt>Estimated Value</dt>
                <dd><b style="color: var(--primary);">AED {{ number_format($lead->estimated_value, 2) }}</b></dd>
            </dl>
        </section>

        <section class="card">
            <header class="card-head">
                <h2>
                    <span class="head-ic">
                        <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-user"/></svg>
                    </span>
                    Contact &amp; Documents
                </h2>
            </header>
            <dl class="kv" id="contactKv">
                <dt>Contact Person</dt><dd>{{ $lead->contact_person ?: '—' }}</dd>
                <dt>Phone</dt><dd><a href="tel:{{ $lead->phone }}">{{ $lead->phone ?: '—' }}</a></dd>
                <dt>Email</dt><dd><a href="mailto:{{ $lead->email }}">{{ $lead->email ?: '—' }}</a></dd>
            </dl>

            <div class="dr-section" style="margin-top: 15px;">
                <h5>Notes &amp; Scope Requirements</h5>
                <p class="notes" style="background:#fafafa; border:1px solid #eee; padding:10px; border-radius:4px; font-size:12px;">
                    {{ $lead->notes ?: 'No additional notes provided.' }}
                </p>
            </div>

            <div class="dr-section" style="margin-top: 15px;">
                <h5>Attachments ({{ $lead->attachments->count() }})</h5>
                <ul class="file-list">
                    @forelse($lead->attachments as $att)
                        <li>
                            <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-file-text"/></svg>
                            <a href="{{ asset('storage/' . $att->file_path) }}" target="_blank">{{ $att->file_name }}</a>
                            <small style="color: #888;">({{ round($att->file_size / 1024) }} KB)</small>
                        </li>
                    @empty
                        <li style="color: #888; font-style: italic;">No attached files.</li>
                    @endforelse
                </ul>
            </div>
        </section>
    </div>

    {{-- Quotations & Timeline Grid --}}
    <div class="detail-grid">
        <section class="card">
            <header class="card-head">
                <h2>
                    <span class="head-ic">
                        <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-file-plus"/></svg>
                    </span>
                    Linked Quotations ({{ $lead->quotations->count() }})
                </h2>
            </header>
            <div class="table-wrap page">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Quote No</th>
                            <th>Date</th>
                            <th>Prepared By</th>
                            <th>Status</th>
                            <th class="num">Grand Total</th>
                            <th class="ta-c">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($lead->quotations as $q)
                            <tr>
                                <td class="strong">
                                    <a href="{{ route('quotations.builder', ['id' => $q->id]) }}" style="color: var(--primary);">
                                        {{ $q->quote_number }}
                                    </a>
                                </td>
                                <td>{{ $q->quote_date->format('d M Y') }}</td>
                                <td>{{ $q->preparedBy->name }}</td>
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
                                    @endphp
                                    <span class="pill pill-{{ $qTone }}"><i></i>{{ ucfirst($q->status) }}</span>
                                </td>
                                <td class="num"><b>AED {{ number_format($q->grand_total, 2) }}</b></td>
                                <td class="ta-c">
                                    <span class="row-actions">
                                        <a class="act" href="{{ route('quotations.builder', ['id' => $q->id]) }}" title="Open Builder">
                                            <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-edit"/></svg>
                                        </a>
                                        <a class="act" href="{{ route('quotations.pdf', $q) }}" title="Download PDF">
                                            <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-download"/></svg>
                                        </a>
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="empty">No quotations drafted yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="card">
            <header class="card-head">
                <h2>
                    <span class="head-ic">
                        <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-clock"/></svg>
                    </span>
                    Activity Timeline
                </h2>
            </header>
            <ul class="events">
                @forelse($lead->activities as $act)
                    <li class="ev">
                        <span class="ev-dot"></span>
                        <div>
                            <strong>{{ $act->text }}</strong>
                            <small>{{ $act->created_at->format('d M Y · h:i A') }} &bull; {{ $act->user?->name ?? 'System' }}</small>
                        </div>
                    </li>
                @empty
                    <li class="empty-state">No timeline activity logged.</li>
                @endforelse
            </ul>
        </section>
    </div>
</div>

{{-- Edit Lead Modal --}}
@can('leads.edit')
<div class="modal-wrap" id="editLeadModal" style="display: none;" onclick="if(event.target === this) closeEditLeadModal()">
    <div class="modal modal-lg" role="dialog" aria-modal="true" aria-labelledby="editLeadModalTitle">
        <div class="modal-head">
            <div>
                <h3 id="editLeadModalTitle">Edit Lead &bull; {{ $lead->lead_number }}</h3>
                <p id="editLeadModalSub">{{ $lead->company_name }} &bull; {{ ucfirst($lead->stage) }}</p>
            </div>
            <button class="icon-btn" type="button" onclick="closeEditLeadModal()" aria-label="Close">
                <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-x"/></svg>
            </button>
        </div>
        <form method="POST" action="{{ route('leads.update', $lead) }}">
            @csrf
            @method('PUT')

            <div class="modal-body">
                <div class="form-grid" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 14px;">
                    <label class="field">
                        <span class="field-label">Lead Number</span>
                        <input value="{{ $lead->lead_number }}" readonly style="background: #F8F9FA;">
                    </label>

                    <label class="field">
                        <span class="field-label">Lead Date</span>
                        <input name="lead_date" type="date" value="{{ $lead->lead_date ? $lead->lead_date->format('Y-m-d') : date('Y-m-d') }}" required>
                    </label>

                    <div class="field" style="grid-column: span 2;">
                        <label class="field-label">Client / Company Name <span style="color: var(--primary);">*</span></label>
                        <input name="company_name" value="{{ $lead->company_name }}" required maxlength="120" placeholder="Client Name">
                    </div>

                    <label class="field">
                        <span class="field-label">RFQ / PR Number</span>
                        <input name="rfq_number" value="{{ $lead->rfq_number }}" maxlength="50" placeholder="RFQ / PR Number">
                    </label>

                    <label class="field">
                        <span class="field-label">Project Subject <span style="color: var(--primary);">*</span></span>
                        <input name="subject" value="{{ $lead->subject }}" required maxlength="150" placeholder="Project Subject">
                    </label>

                    <label class="field">
                        <span class="field-label">Contact Person</span>
                        <input name="contact_person" value="{{ $lead->contact_person }}" maxlength="100" placeholder="Contact Person">
                    </label>

                    <label class="field">
                        <span class="field-label">Phone</span>
                        <input name="phone" value="{{ $lead->phone }}" maxlength="30" placeholder="Phone Number">
                    </label>

                    <label class="field">
                        <span class="field-label">Email</span>
                        <input name="email" type="email" value="{{ $lead->email }}" maxlength="100" placeholder="Email">
                    </label>

                    <label class="field">
                        <span class="field-label">Budget / Estimated Value (AED)</span>
                        <input name="estimated_value" type="number" step="100" min="0" value="{{ $lead->estimated_value }}" placeholder="0">
                    </label>

                    <label class="field">
                        <span class="field-label">Source <span style="color: var(--primary);">*</span></span>
                        <span class="select-wrap">
                            <select name="source" required>
                                @foreach(['Website', 'Referral', 'Exhibition', 'Email', 'Phone', 'Social Media'] as $src)
                                    <option value="{{ $src }}" {{ $lead->source === $src ? 'selected' : '' }}>{{ $src }}</option>
                                @endforeach
                            </select>
                        </span>
                    </label>

                    <label class="field">
                        <span class="field-label">Branch <span style="color: var(--primary);">*</span></span>
                        <span class="select-wrap">
                            <select name="branch_id" required>
                                @foreach($branches as $b)
                                    <option value="{{ $b->id }}" {{ $lead->branch_id === $b->id ? 'selected' : '' }}>{{ $b->name }} ({{ $b->code }})</option>
                                @endforeach
                            </select>
                        </span>
                    </label>

                    <label class="field" style="grid-column: span 2;">
                        <span class="field-label">Assigned Engineer <span style="color: var(--primary);">*</span></span>
                        <span class="select-wrap">
                            <select name="assigned_engineer_id" required>
                                <option value="">Select Engineer</option>
                                @foreach($engineers as $eng)
                                    <option value="{{ $eng->id }}" {{ $lead->assigned_engineer_id == $eng->id ? 'selected' : '' }}>
                                        {{ $eng->name }} ({{ $eng->branch?->short_name ?? 'All Branches' }})
                                    </option>
                                @endforeach
                            </select>
                        </span>
                    </label>

                    <label class="field" style="grid-column: span 2;">
                        <span class="field-label">Notes &amp; Scope Requirements</span>
                        <textarea name="notes" rows="3" maxlength="1000" placeholder="Notes & scope requirements">{{ $lead->notes }}</textarea>
                    </label>
                </div>
            </div>

            <div class="modal-foot">
                <button class="btn btn-ghost" type="button" onclick="closeEditLeadModal()">Cancel</button>
                <button class="btn btn-primary" type="submit">
                    <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-check"/></svg>
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>
@endcan

{{-- Assign Engineer Modal --}}
@can('leads.assign')
<div class="modal-wrap" id="assignModal" style="display: none;" onclick="if(event.target === this) closeAssignModal()">
    <div class="modal modal-sm" role="dialog" aria-modal="true">
        <div class="modal-head">
            <div>
                <h3>Assign Engineer</h3>
                <p>{{ $lead->lead_number }} &bull; {{ $lead->company_name }}</p>
            </div>
            <button class="icon-btn" type="button" onclick="closeAssignModal()" aria-label="Close">
                <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-x"/></svg>
            </button>
        </div>
        <form method="POST" action="{{ route('leads.assign', $lead) }}">
            @csrf
            <div class="modal-body">
                <label class="field">
                    <span class="field-label">Project Engineer</span>
                    <span class="select-wrap">
                        <select name="engineer_id" required>
                            @foreach($engineers as $eng)
                                <option value="{{ $eng->id }}" {{ $lead->assigned_engineer_id == $eng->id ? 'selected' : '' }}>
                                    {{ $eng->name }} ({{ $eng->branch?->short_name ?? 'All Branches' }})
                                </option>
                            @endforeach
                        </select>
                    </span>
                </label>
            </div>
            <div class="modal-foot">
                <button class="btn btn-ghost" type="button" onclick="closeAssignModal()">Cancel</button>
                <button class="btn btn-primary" type="submit">Assign</button>
            </div>
        </form>
    </div>
</div>
@endcan

<script>
    function openEditLeadModal() {
        var m = document.getElementById('editLeadModal');
        if (m) {
            m.style.display = 'flex';
            m.classList.add('open');
        }
    }
    function closeEditLeadModal() {
        var m = document.getElementById('editLeadModal');
        if (m) {
            m.classList.remove('open');
            m.style.display = 'none';
        }
    }
    function openAssignModal() {
        var m = document.getElementById('assignModal');
        if (m) {
            m.style.display = 'flex';
            m.classList.add('open');
        }
    }
    function closeAssignModal() {
        var m = document.getElementById('assignModal');
        if (m) {
            m.classList.remove('open');
            m.style.display = 'none';
        }
    }

    // ?open=edit / ?open=assign (links from the dashboard row menu) opens that modal on load
    (function () {
        var open = new URLSearchParams(window.location.search).get('open');
        if (open === 'edit') openEditLeadModal();
        if (open === 'assign') openAssignModal();
    })();
</script>
@endsection
