@extends('layouts.app')

@section('title', 'Settings')
@section('page_id', 'settings')

@section('breadcrumbs')
    <svg class="ic sep" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-chevron-right"/></svg>
    <span class="crumb current">Settings</span>
@endsection

@section('content')
<section class="page-head" id="pageHead">
    <div>
        <h1>Settings</h1>
        <p class="sub">Display preferences, quotation approval workflow configuration, and system information.</p>
    </div>
</section>

<div class="dash-scroll">
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(380px, 1fr)); gap: 18px; margin-bottom: 24px;">
        {{-- Display Preferences --}}
        <section class="card" id="prefs">
            <header class="card-head">
                <h2>
                    <span class="head-ic">
                        <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-settings"/></svg>
                    </span>
                    Preferences
                </h2>
            </header>
            <div style="padding: 16px 20px;">
                <div class="toggle-row" style="display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid var(--border-color, #f1f5f9);">
                    <div>
                        <strong>Collapse sidebar</strong>
                        <small style="display: block; color: var(--muted, #64748B); font-size: 12px;">Show icons only on desktop</small>
                    </div>
                    <label class="switch" style="position: relative; display: inline-block; width: 44px; height: 24px;">
                        <input type="checkbox" id="prefCollapse" onchange="toggleSidebarCollapse(this.checked)">
                        <span class="slider" style="position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #cbd5e1; transition: .3s; border-radius: 24px;"></span>
                    </label>
                </div>

                <div class="toggle-row" style="display: flex; justify-content: space-between; align-items: center; padding: 12px 0;">
                    <div>
                        <strong>Compact tables</strong>
                        <small style="display: block; color: var(--muted, #64748B); font-size: 12px;">Tighter row spacing in all tables</small>
                    </div>
                    <label class="switch" style="position: relative; display: inline-block; width: 44px; height: 24px;">
                        <input type="checkbox" id="prefCompact" onchange="toggleCompactTables(this.checked)">
                        <span class="slider" style="position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #cbd5e1; transition: .3s; border-radius: 24px;"></span>
                    </label>
                </div>
            </div>
        </section>

        {{-- Workflow & Demo Controls --}}
        <section class="card" id="demo">
            <header class="card-head">
                <h2>
                    <span class="head-ic">
                        <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-shield"/></svg>
                    </span>
                    Workflow &amp; Role Controls
                </h2>
            </header>
            <div style="padding: 16px 20px;">
                <div class="toggle-row" style="display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid var(--border-color, #f1f5f9); gap: 16px;">
                    <div>
                        <strong>Current role</strong>
                        <small style="display: block; color: var(--muted, #64748B); font-size: 12px;">Switch active user to experience permissions</small>
                    </div>
                    <form action="{{ route('auth.switch-user') }}" method="POST" id="switchRoleForm">
                        @csrf
                        <span class="select-wrap" style="min-width: 220px;">
                            <select name="user_id" onchange="this.form.submit()" style="height: 36px; font-size: 13px;">
                                @foreach($users as $u)
                                    <option value="{{ $u->id }}" {{ auth()->id() === $u->id ? 'selected' : '' }}>
                                        {{ $u->name }} ({{ $u->role_label }})
                                    </option>
                                @endforeach
                            </select>
                        </span>
                    </form>
                </div>

                <div class="toggle-row" style="display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid var(--border-color, #f1f5f9); gap: 16px;">
                    <div>
                        <strong>Quotation approver</strong>
                        <small style="display: block; color: var(--muted, #64748B); font-size: 12px;">Choose who can approve quotations</small>
                    </div>
                    <form action="{{ route('settings.approver') }}" method="POST" id="approverForm">
                        @csrf
                        <span class="select-wrap" style="min-width: 220px;">
                            <select name="quote_approver" onchange="this.form.submit()" style="height: 36px; font-size: 13px;">
                                <option value="estimation" {{ $quoteApprover === 'estimation' ? 'selected' : '' }}>Estimation User (and Admin)</option>
                                <option value="admin" {{ $quoteApprover === 'admin' ? 'selected' : '' }}>Super Admin only</option>
                            </select>
                        </span>
                    </form>
                </div>

                <div class="toggle-row" style="display: flex; justify-content: space-between; align-items: center; padding: 12px 0;">
                    <div>
                        <strong>Saved filter view</strong>
                        <small style="display: block; color: var(--muted, #64748B); font-size: 12px;">Reset custom branch / period filters</small>
                    </div>
                    <button class="btn btn-ghost btn-sm" type="button" onclick="localStorage.removeItem('rm_filters'); alert('Saved filters cleared.');">
                        Clear
                    </button>
                </div>
            </div>
        </section>
    </div>

    {{-- Company & System Info --}}
    <section class="card" id="about">
        <header class="card-head">
            <h2>
                <span class="head-ic">
                    <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-info"/></svg>
                </span>
                Company &amp; ERP Configuration
            </h2>
        </header>
        <div style="padding: 16px 20px;">
            <dl class="kv" style="display: grid; grid-template-columns: 200px 1fr; row-gap: 12px; font-size: 13px;">
                <dt style="color: var(--muted, #64748B); font-weight: 500;">Company</dt>
                <dd><strong>Rich Mount Contracting LLC</strong></dd>

                <dt style="color: var(--muted, #64748B); font-weight: 500;">Base Currency</dt>
                <dd><b>AED (United Arab Emirates Dirham)</b></dd>

                <dt style="color: var(--muted, #64748B); font-weight: 500;">Standard UAE VAT Rate</dt>
                <dd>5.00%</dd>

                <dt style="color: var(--muted, #64748B); font-weight: 500;">Configured Branches</dt>
                <dd>{{ $branchesCount }} operational branches (Dubai, Abu Dhabi, Sharjah, Al Ain)</dd>

                <dt style="color: var(--muted, #64748B); font-weight: 500;">Accounting Integration</dt>
                <dd>Zoho Books (two-way sync for Petty Cash, Invoices, and Projects)</dd>

                <dt style="color: var(--muted, #64748B); font-weight: 500;">Audit Journal Entries</dt>
                <dd>{{ number_format($auditCount) }} logged events in system audit trail</dd>
            </dl>
        </div>
    </section>
</div>

<style>
    .switch input { opacity: 0; width: 0; height: 0; }
    .switch input:checked + .slider { background-color: var(--primary, #A30D2D); }
    .switch input:checked + .slider:before { transform: translateX(20px); }
    .slider:before {
        position: absolute; content: ""; height: 18px; width: 18px; left: 3px; bottom: 3px;
        background-color: white; transition: .3s; border-radius: 50%;
    }
</style>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const app = document.getElementById('app');
        if (localStorage.getItem('rm_sidebar_collapsed') === 'true') {
            document.getElementById('prefCollapse').checked = true;
            if (app) app.classList.add('collapsed');
        }
        if (localStorage.getItem('rm_compact_tables') === 'true') {
            document.getElementById('prefCompact').checked = true;
            document.body.classList.add('compact');
        }
    });

    function toggleSidebarCollapse(checked) {
        const app = document.getElementById('app');
        if (app) app.classList.toggle('collapsed', checked);
        localStorage.setItem('rm_sidebar_collapsed', checked);
    }

    function toggleCompactTables(checked) {
        document.body.classList.toggle('compact', checked);
        localStorage.setItem('rm_compact_tables', checked);
    }
</script>
@endpush
@endsection
