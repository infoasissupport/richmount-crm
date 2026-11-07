@extends('layouts.app')

@section('title', 'Projects')
@section('page_id', 'projects')

@section('breadcrumbs')
    <svg class="ic sep" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-chevron-right"/></svg>
    <span class="crumb current">Projects</span>
@endsection

@section('content')
<section class="page-head" id="pageHead">
    <div>
        <h1>Projects</h1>
    </div>
</section>

<div class="dash-scroll">
    {{-- Key Figures --}}
    <section class="kpi-grid four" id="kpis" aria-label="Key figures">
        <div class="kpi static">
            <span class="kpi-top">
                <span class="kpi-icon">
                    <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-briefcase"/></svg>
                </span>
            </span>
            <span class="kpi-value">{{ $kpis['active_projects'] }}</span>
            <span class="kpi-label">Active Projects</span>
        </div>

        <div class="kpi static">
            <span class="kpi-top">
                <span class="kpi-icon">
                    <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-clock"/></svg>
                </span>
            </span>
            <span class="kpi-value">{{ $kpis['awaiting_activation'] }}</span>
            <span class="kpi-label">Awaiting Activation</span>
        </div>

        <div class="kpi static">
            <span class="kpi-top">
                <span class="kpi-icon">
                    <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-check-circle"/></svg>
                </span>
            </span>
            <span class="kpi-value">{{ $kpis['completed_projects'] }}</span>
            <span class="kpi-label">Completed Projects</span>
        </div>

        <div class="kpi static">
            <span class="kpi-top">
                <span class="kpi-icon">
                    <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-coins"/></svg>
                </span>
            </span>
            <span class="kpi-value">AED {{ number_format($kpis['total_invoiced'], 0) }}</span>
            <span class="kpi-label">Total Invoiced</span>
        </div>
    </section>

    {{-- Activation Table: Awaiting Project / PO --}}
    <section class="card" id="activationTable">
        <header class="card-head">
            <h2>
                <span class="head-ic">
                    <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-clock"/></svg>
                </span>
                Awaiting Project / PO
            </h2>
        </header>
        <div class="table-wrap page">
            <table class="table" style="font-size: 12px;">
                <thead>
                    <tr>
                        <th>Stage</th>
                        <th>Quote / Project</th>
                        <th>Client</th>
                        <th>Subject</th>
                        <th>Branch</th>
                        <th class="num">Value (AED)</th>
                        <th>Since</th>
                        <th class="ta-c">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @php $hasAwaiting = false; @endphp

                    {{-- Uninitiated Awarded Quotations --}}
                    @foreach($awardedWithoutProject as $q)
                        @php $hasAwaiting = true; @endphp
                        <tr>
                            <td><span class="pill pill-primary"><i></i>Awarded</span></td>
                            <td class="strong">
                                <a href="{{ route('quotations.builder', ['id' => $q->id]) }}" style="color: var(--primary); text-decoration: none;">
                                    {{ $q->quote_number }}
                                </a>
                            </td>
                            <td><strong>{{ $q->lead?->company_name ?? 'Client' }}</strong></td>
                            <td>{{ $q->lead?->subject ?? 'Contract Award' }}</td>
                            <td><span class="pill pill-gray">{{ $q->branch->short_name }}</span></td>
                            <td class="num"><b>{{ number_format($q->grand_total, 2) }}</b></td>
                            <td>{{ $q->award_date ? \Carbon\Carbon::parse($q->award_date)->format('d M Y') : $q->updated_at->format('d M Y') }}</td>
                            <td class="ta-c">
                                @can('projects.create')
                                <form action="{{ route('projects.create-from-quote', $q) }}" method="POST" style="display:inline;">
                                    @csrf
                                    <button class="btn btn-soft btn-sm" type="submit">
                                        <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-briefcase"/></svg>
                                        Create Project
                                    </button>
                                </form>
                                @else
                                <span style="color:var(--muted)">Accountant only</span>
                                @endcan
                            </td>
                        </tr>
                    @endforeach

                    {{-- Projects Awaiting Client PO --}}
                    @foreach($projectsAwaitingPo as $p)
                        @php $hasAwaiting = true; @endphp
                        <tr>
                            <td><span class="pill pill-amber"><i></i>PO Pending</span></td>
                            <td class="strong">
                                <a href="{{ route('projects.show', $p) }}" style="color: var(--primary); text-decoration: none;">
                                    {{ $p->project_number }}
                                </a>
                            </td>
                            <td><strong>{{ $p->client_name }}</strong></td>
                            <td>{{ $p->name }}</td>
                            <td><span class="pill pill-gray">{{ $p->branch->short_name }}</span></td>
                            <td class="num"><b>{{ number_format($p->contract_value, 2) }}</b></td>
                            <td>{{ $p->created_at->format('d M Y') }}</td>
                            <td class="ta-c">
                                <span class="row-actions">
                                    <span class="project-action-menu">
                                        <button class="act" type="button" title="Project actions" aria-label="Project actions for {{ $p->project_number }}" aria-haspopup="menu" aria-expanded="false" onclick="toggleProjectActionMenu(event, this)">
                                            <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-more-vertical"/></svg>
                                        </button>
                                        <div class="popover row-menu" role="menu" hidden>
                                            <a class="pop-item" role="menuitem" href="{{ route('projects.show', $p) }}">
                                                <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-eye"/></svg>
                                                <span>View project</span>
                                            </a>
                                            @can('update', $p)
                                            <button class="pop-item" type="button" role="menuitem" onclick="closeProjectActionMenu(); openEditProjectModal(this)"
                                                data-project-id="{{ $p->id }}"
                                                data-project-number="{{ $p->project_number }}"
                                                data-project-name="{{ $p->name }}"
                                                data-client-name="{{ $p->client_name }}"
                                                data-branch-id="{{ $p->branch_id }}"
                                                data-engineer-id="{{ $p->engineer_id }}"
                                                data-contract-value="{{ $p->contract_value }}"
                                                data-start-date="{{ $p->start_date?->format('Y-m-d') }}"
                                                data-expected-end-date="{{ $p->expected_end_date?->format('Y-m-d') }}"
                                                data-description="{{ $p->description }}"
                                                data-client-po-number="{{ $p->client_po_number }}"
                                                data-status-label="{{ $p->statusLabel() }}">
                                                <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-edit"/></svg>
                                                <span>Edit project</span>
                                            </button>
                                            @endcan
                                        </div>
                                    </span>
                                @can('projects.open')
                                <button class="btn btn-soft btn-sm" type="button" onclick="openRecordPoModal({{ json_encode($p) }})">
                                    <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-file-plus"/></svg>
                                    Add PO
                                </button>
                                @else
                                <span style="color:var(--muted)">Accountant only</span>
                                @endcan
                                </span>
                            </td>
                        </tr>
                    @endforeach

                    @if(!$hasAwaiting)
                        <tr>
                            <td colspan="8" style="text-align: center; color: var(--muted); padding: 25px;">
                                Nothing is waiting &mdash; every awarded quotation has an open project.
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>

        {{ $projectsAwaitingPo->withQueryString()->links() }}
    </section>

    {{-- All Projects Table with status tabs --}}
    <section class="card fill" id="projectsTable" >
        <header class="card-head recent-head">
            <h2>
                <span class="head-ic">
                    <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-clipboard"/></svg>
                </span>
                All Projects
            </h2>

            <div class="head-actions">
                <form method="GET" action="{{ route('projects.index') }}" class="recent-filters">
                    <label class="search-input recent-search">
                        <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-search"/></svg>
                        <input type="search" name="search" placeholder="Search projects..." value="{{ request('search') }}" aria-label="Search projects" onchange="this.form.submit()">
                    </label>

                    <span class="select-wrap mini with-icon">
                        <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-filter"/></svg>
                        <select name="tab" aria-label="Filter projects by status" onchange="this.form.submit()">
                            <option value="all" {{ $tab === 'all' ? 'selected' : '' }}>All Statuses</option>
                            <option value="popending" {{ $tab === 'popending' ? 'selected' : '' }}>PO Pending</option>
                            <option value="open" {{ $tab === 'open' ? 'selected' : '' }}>Open</option>
                            <option value="inprogress" {{ $tab === 'inprogress' ? 'selected' : '' }}>In Progress</option>
                            <option value="onhold" {{ $tab === 'onhold' ? 'selected' : '' }}>On Hold</option>
                            <option value="completed" {{ $tab === 'completed' ? 'selected' : '' }}>Work Completed</option>
                            <option value="invoicepending" {{ $tab === 'invoicepending' ? 'selected' : '' }}>Invoice Pending</option>
                            <option value="closed" {{ $tab === 'closed' ? 'selected' : '' }}>Closed</option>
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
                        <th>Project Number</th>
                        <th>Project Name / Subject</th>
                        <th>Client</th>
                        <th>Branch</th>
                        <th>Engineer</th>
                        <th>Client PO</th>
                        <th class="num">Contract Value (AED)</th>
                        <th>Status</th>
                        <th class="ta-c">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($projects as $idx => $p)
                        <tr>
                            <td>{{ $projects->firstItem() + $idx }}</td>
                            <td class="strong">
                                <a href="{{ route('projects.show', $p) }}" style="color: var(--primary); text-decoration: none;">
                                    {{ $p->project_number }}
                                </a>
                            </td>
                            <td>
                                <strong>{{ $p->name }}</strong>
                            </td>
                            <td>{{ $p->client_name }}</td>
                            <td><span class="pill pill-gray">{{ $p->branch->short_name }}</span></td>
                            <td>{{ $p->assignedEngineer?->name ?? 'Unassigned' }}</td>
                            <td>
                                @if($p->client_po_number)
                                    <span class="pill pill-blue">PO #{{ $p->client_po_number }}</span>
                                @else
                                    <span style="color: var(--muted); font-size: 11px;">Waiting</span>
                                @endif
                            </td>
                            <td class="num"><b>{{ number_format($p->contract_value, 2) }}</b></td>
                            <td>
                                <span class="pill pill-{{ $p->statusTone() }}">
                                    <i></i>{{ $p->statusLabel() }}
                                </span>
                            </td>
                            <td class="ta-c">
                                <span class="row-actions">
                                    <a class="act view" href="{{ route('projects.show', $p) }}" title="View Project" aria-label="View project {{ $p->project_number }}">
                                        <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-eye"/></svg>
                                    </a>
                                    @can('update', $p)
                                    <button class="act edit" type="button" title="Edit Project" aria-label="Edit project {{ $p->project_number }}" onclick="openEditProjectModal(this)"
                                        data-project-id="{{ $p->id }}"
                                        data-project-number="{{ $p->project_number }}"
                                        data-project-name="{{ $p->name }}"
                                        data-client-name="{{ $p->client_name }}"
                                        data-branch-id="{{ $p->branch_id }}"
                                        data-engineer-id="{{ $p->engineer_id }}"
                                        data-contract-value="{{ $p->contract_value }}"
                                        data-start-date="{{ $p->start_date?->format('Y-m-d') }}"
                                        data-expected-end-date="{{ $p->expected_end_date?->format('Y-m-d') }}"
                                        data-description="{{ $p->description }}"
                                        data-client-po-number="{{ $p->client_po_number }}"
                                        data-status-label="{{ $p->statusLabel() }}">
                                        <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-edit"/></svg>
                                    </button>
                                    @endcan
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" style="text-align: center; color: var(--muted); padding: 30px;">
                                No projects found matching your criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($projects->total() > 0)
            {{ $projects->withQueryString()->links() }}
        @endif
    </section>
</div>

{{-- Edit Project Modal --}}
<div class="modal-wrap" id="editProjectModal" style="display: none;" onclick="if(event.target === this) closeEditProjectModal()">
    <div class="modal modal-lg" role="dialog" aria-modal="true" aria-labelledby="editProjectTitle">
        <div class="modal-head">
            <div>
                <h3 id="editProjectTitle">Edit Project</h3>
                <p>Update project details</p>
            </div>
            <button class="icon-btn" type="button" onclick="closeEditProjectModal()" aria-label="Close">
                <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-x"/></svg>
            </button>
        </div>

        <form id="editProjectForm" action="" method="POST">
            @csrf
            @method('PUT')
            <div class="modal-body">
                <div class="form-grid">
                    <div class="field span-2">
                        <label class="label req" for="editProjectName">Project Name / Subject</label>
                        <input class="input" id="editProjectName" name="name" type="text" required maxlength="200">
                    </div>

                    <div class="field">
                        <label class="label req" for="editProjectClient">Client / Customer Name</label>
                        <input class="input" id="editProjectClient" name="client_name" type="text" required maxlength="150">
                    </div>

                    <div class="field">
                        <label class="label req" for="editProjectBranch">Branch</label>
                        <select class="select" id="editProjectBranch" name="branch_id" required>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="field">
                        <label class="label req" for="editProjectValue">Contract Value (AED)</label>
                        <input class="input" id="editProjectValue" name="contract_value" type="number" step="0.01" min="0" required>
                    </div>

                    <div class="field">
                        <label class="label" for="editProjectEngineer">Assigned Project Engineer</label>
                        <select class="select" id="editProjectEngineer" name="engineer_id">
                            <option value="">Select Project Engineer</option>
                            @foreach($engineers as $engineer)
                                <option value="{{ $engineer->id }}">{{ $engineer->name }} ({{ $engineer->branch?->short_name ?? 'No branch' }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="field">
                        <label class="label" for="editProjectPoNumber">Client PO / LPO Number</label>
                        <input class="input" id="editProjectPoNumber" type="text" disabled>
                    </div>

                    <div class="field">
                        <label class="label" for="editProjectStartDate">Target Start Date</label>
                        <input class="input" id="editProjectStartDate" name="start_date" type="date">
                    </div>

                    <div class="field">
                        <label class="label" for="editProjectEndDate">Target Completion Date</label>
                        <input class="input" id="editProjectEndDate" name="expected_end_date" type="date">
                    </div>

                    <div class="field">
                        <span class="label">Current Status</span>
                        <input class="input" id="editProjectStatus" type="text" disabled>
                    </div>

                    <div class="field span-2">
                        <label class="label" for="editProjectDescription">Scope of Work & Notes</label>
                        <textarea class="textarea" id="editProjectDescription" name="description" rows="3" maxlength="2000"></textarea>
                    </div>
                </div>
            </div>

            <div class="modal-foot">
                <button class="btn btn-ghost" type="button" onclick="closeEditProjectModal()">Cancel</button>
                <button class="btn btn-primary" type="submit">
                    <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-check"/></svg>
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Create Project Modal --}}
<div class="modal-wrap" id="projectModal" style="display: none;">
    <div class="modal modal-lg" role="dialog" aria-modal="true" aria-labelledby="projectModalTitle">
        <div class="modal-head">
            <div>
                <h3 id="projectModalTitle">Create Project</h3>
                <p>Initialize project contract, assign engineer, and record client PO</p>
            </div>
            <button class="icon-btn" type="button" onclick="closeNewProjectModal()" aria-label="Close">
                <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-x"/></svg>
            </button>
        </div>

        <form action="{{ route('projects.store') }}" method="POST">
            @csrf
            <div class="modal-body">
                <div class="form-grid">
                    <div class="field span-2">
                        <label class="label req" for="projName">Project Name / Subject</label>
                        <input class="input" id="projName" name="name" type="text" required placeholder="e.g. Festival City Retail Fitout & Partition Package">
                    </div>

                    <div class="field">
                        <label class="label req" for="projClient">Client / Customer Name</label>
                        <input class="input" id="projClient" name="client_name" type="text" required placeholder="e.g. Al Futtaim Group LLC">
                    </div>

                    <div class="field">
                        <label class="label req" for="projBranch">Branch</label>
                        <select class="select" id="projBranch" name="branch_id" required>
                            @foreach($branches as $b)
                                <option value="{{ $b->id }}">{{ $b->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="field">
                        <label class="label req" for="projValue">Contract Value (AED)</label>
                        <input class="input" id="projValue" name="contract_value" type="number" step="0.01" min="0" required placeholder="0.00">
                    </div>

                    <div class="field">
                        <label class="label" for="projEngineer">Assigned Project Engineer</label>
                        <select class="select" id="projEngineer" name="engineer_id">
                            <option value="">Select Project Engineer</option>
                            @foreach($engineers as $eng)
                                <option value="{{ $eng->id }}">{{ $eng->name }} ({{ $eng->branch->short_name }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="field">
                        <label class="label" for="projPoNumber">Client PO / LPO Number (Optional)</label>
                        <input class="input" id="projPoNumber" name="client_po_number" type="text" placeholder="e.g. PO-AFG-9021">
                    </div>

                    <div class="field">
                        <label class="label" for="projPoDate">Client PO Date</label>
                        <input class="input" id="projPoDate" name="client_po_date" type="date">
                    </div>

                    <div class="field">
                        <label class="label" for="projStartDate">Target Start Date</label>
                        <input class="input" id="projStartDate" name="start_date" type="date" value="{{ date('Y-m-d') }}">
                    </div>

                    <div class="field">
                        <label class="label" for="projEndDate">Target Completion Date</label>
                        <input class="input" id="projEndDate" name="expected_end_date" type="date">
                    </div>

                    <div class="field span-2">
                        <label class="label" for="projDesc">Scope of Work & Notes</label>
                        <textarea class="textarea" id="projDesc" name="description" rows="3" placeholder="Key specifications, milestones, and scope summary..."></textarea>
                    </div>
                </div>
            </div>

            <div class="modal-foot">
                <button class="btn btn-ghost" type="button" onclick="closeNewProjectModal()">Cancel</button>
                <button class="btn btn-primary" type="submit">
                    <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-check"/></svg>
                    Create Project
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Record Client PO Modal --}}
<div class="modal-wrap" id="recordPoModal" style="display: none;">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="recordPoTitle">
        <div class="modal-head">
            <div>
                <h3 id="recordPoTitle">Record Client PO</h3>
            </div>
            <button class="icon-btn" type="button" onclick="closeRecordPoModal()" aria-label="Close">
                <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-x"/></svg>
            </button>
        </div>

        <form id="recordPoForm" action="" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="modal-body">
                <div class="form-grid">
                    <div class="field span-2">
                        <label class="label req" for="inPoNumber">Client PO / LPO Number</label>
                        <input class="input" id="inPoNumber" name="client_po_number" type="text" required placeholder="e.g. PO-2026-904">
                    </div>

                    <div class="field">
                        <label class="label req" for="inPoDate">PO Issue Date</label>
                        <input class="input" id="inPoDate" name="client_po_date" type="date" required value="{{ date('Y-m-d') }}">
                    </div>

                    <div class="field">
                        <label class="label" for="inPoAmount">PO Amount (AED)</label>
                        <input class="input" id="inPoAmount" name="client_po_amount" type="number" step="0.01" min="0">
                    </div>

                    <div class="field span-2">
                        <label class="label" for="inPoFile">Attach Client PO Document (PDF / Scan)</label>
                        <input class="input" id="inPoFile" name="po_file" type="file" accept=".pdf,.png,.jpg,.jpeg">
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

<script>
    let activeProjectActionMenu = null;

    function closeProjectActionMenu() {
        if (!activeProjectActionMenu) return;
        activeProjectActionMenu.menu.hidden = true;
        activeProjectActionMenu.button.setAttribute('aria-expanded', 'false');
        activeProjectActionMenu = null;
    }

    function toggleProjectActionMenu(event, button) {
        event.stopPropagation();
        if (activeProjectActionMenu?.button === button) {
            closeProjectActionMenu();
            return;
        }

        closeProjectActionMenu();
        const menu = button.nextElementSibling;
        menu.hidden = false;
        const bounds = button.getBoundingClientRect();
        const menuWidth = menu.offsetWidth;
        const menuHeight = menu.offsetHeight;
        let top = bounds.bottom + 6;
        if (top + menuHeight > window.innerHeight - 8) {
            top = Math.max(8, bounds.top - menuHeight - 6);
        }

        menu.style.left = Math.max(8, Math.min(bounds.right - menuWidth, window.innerWidth - menuWidth - 8)) + 'px';
        menu.style.top = top + 'px';
        button.setAttribute('aria-expanded', 'true');
        activeProjectActionMenu = { button, menu };
    }

    document.addEventListener('click', function (event) {
        if (!event.target.closest('.project-action-menu')) closeProjectActionMenu();
    });
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') closeProjectActionMenu();
    });
    document.addEventListener('scroll', closeProjectActionMenu, { passive: true, capture: true });
    window.addEventListener('resize', closeProjectActionMenu);

    function openEditProjectModal(button) {
        const project = button.dataset;
        const form = document.getElementById('editProjectForm');

        document.getElementById('editProjectTitle').textContent = 'Edit Project · ' + project.projectNumber;
        document.getElementById('editProjectName').value = project.projectName;
        document.getElementById('editProjectClient').value = project.clientName;
        document.getElementById('editProjectBranch').value = project.branchId;
        document.getElementById('editProjectEngineer').value = project.engineerId;
        document.getElementById('editProjectValue').value = project.contractValue;
        document.getElementById('editProjectPoNumber').value = project.clientPoNumber || 'Not recorded';
        document.getElementById('editProjectStartDate').value = project.startDate;
        document.getElementById('editProjectEndDate').value = project.expectedEndDate;
        document.getElementById('editProjectStatus').value = project.statusLabel;
        document.getElementById('editProjectDescription').value = project.description;
        form.action = '/projects/' + project.projectId;
        document.getElementById('editProjectModal').style.display = 'grid';
    }

    function closeEditProjectModal() {
        document.getElementById('editProjectModal').style.display = 'none';
    }

    function openNewProjectModal() {
        document.getElementById('projectModal').style.display = 'grid';
    }

    function closeNewProjectModal() {
        document.getElementById('projectModal').style.display = 'none';
    }

    function openRecordPoModal(project) {
        document.getElementById('recordPoTitle').textContent = 'Record PO · ' + project.project_number;
        document.getElementById('recordPoForm').action = '/projects/' + project.id + '/po';
        document.getElementById('inPoAmount').value = project.contract_value || '';
        document.getElementById('recordPoModal').style.display = 'grid';
    }

    function closeRecordPoModal() {
        document.getElementById('recordPoModal').style.display = 'none';
    }
</script>
@endsection
