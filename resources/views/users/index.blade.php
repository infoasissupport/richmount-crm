@extends('layouts.app')

@section('title', 'Users & Roles')
@section('page_id', 'users')

@section('breadcrumbs')
    <svg class="ic sep" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-chevron-right"/></svg>
    <span class="crumb current">Users &amp; Roles</span>
@endsection

@section('content')
<section class="page-head" id="pageHead">
    <div>
        <h1>Users &amp; Roles</h1>
        <p class="sub">System users, the role → permission matrix, and the audit log.</p>
    </div>
    <div class="page-actions">
        @can('users.edit')
        <button class="btn btn-primary" type="button" onclick="openCreateUserModal()">
            <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-plus"/></svg>
            New User
        </button>
        @endcan
    </div>
</section>

<div class="dash-scroll">
    {{-- Users Directory Card --}}
    <section class="card" id="usersTable">
        <header class="card-head">
            <h2>
                <span class="head-ic">
                    <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-users"/></svg>
                </span>
                Users
            </h2>
            <div class="head-actions">
                <form method="GET" action="{{ route('users.index') }}" style="display: flex; gap: 8px;">
                    <label class="search-input recent-search">
                        <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-search"/></svg>
                        <input type="search" name="search" value="{{ request('search') }}" placeholder="Search by name or email..." aria-label="Search users">
                    </label>
                    <span class="select-wrap mini">
                        <select name="role" onchange="this.form.submit()">
                            <option value="">All Roles</option>
                            <option value="super_admin" {{ request('role') == 'super_admin' ? 'selected' : '' }}>Super Admin</option>
                            <option value="estimation" {{ request('role') == 'estimation' ? 'selected' : '' }}>Estimation User</option>
                            <option value="engineer" {{ request('role') == 'engineer' ? 'selected' : '' }}>Project Engineer</option>
                            <option value="accountant" {{ request('role') == 'accountant' ? 'selected' : '' }}>Accountant</option>
                        </select>
                    </span>
                </form>
            </div>
        </header>

        <div class="table-wrap page">
            <table class="table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Role</th>
                        <th>Branch Access</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Status</th>
                        <th class="ta-c">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $u)
                        <tr>
                            <td class="strong">
                                <span class="cell-who">
                                    <span class="avatar sm av-pink">{{ $u->initials }}</span>
                                    <span class="cell-text">
                                        {{ $u->name }}
                                        @if($u->engineer_code)<small>Code: {{ $u->engineer_code }}</small>@endif
                                    </span>
                                </span>
                            </td>
                            <td>
                                @php
                                    $tone = match($u->role_name) {
                                        'super_admin' => 'primary',
                                        'accountant' => 'purple',
                                        'estimation' => 'amber',
                                        'engineer' => 'blue',
                                        default => 'gray'
                                    };
                                @endphp
                                <span class="pill pill-{{ $tone }}"><i></i>{{ $u->role_label }}</span>
                            </td>
                            <td>
                                {{ $u->all_branches ? 'All Branches' : ($u->branch?->name ?? '—') }}
                            </td>
                            <td>{{ $u->email }}</td>
                            <td>{{ $u->phone ?: '—' }}</td>
                            <td>
                                <span class="pill {{ $u->is_active ? 'pill-green' : 'pill-red' }}">
                                    <i></i>{{ $u->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="ta-c">
                                <span class="row-actions">
                                    @can('users.edit')
                                    <button class="act edit" type="button" title="Edit" onclick="openEditUserModal({{ json_encode($u) }})">
                                        <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-edit"/></svg>
                                    </button>
                                    @endcan

                                    @if(auth()->id() !== $u->id)
                                    <form action="{{ route('auth.switch-user') }}" method="POST" style="display:inline;">
                                        @csrf
                                        <input type="hidden" name="user_id" value="{{ $u->id }}">
                                        <button class="act" type="submit" title="Switch to this user">
                                            <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-log-out"/></svg>
                                        </button>
                                    </form>
                                    @endif

                                    @can('users.edit')
                                    @if(auth()->id() !== $u->id)
                                    <form action="{{ route('users.destroy', $u) }}" method="POST" style="display:inline;" onsubmit="return confirm('Delete user {{ $u->name }}?');">
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
                            <td colspan="7" class="empty">No users found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($users->hasPages())
        <div class="pager" style="padding: 12px 18px;">
            {{ $users->links() }}
        </div>
        @endif
        <p class="lock-note" style="margin-top:10px; padding: 12px 18px;">
            <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-info"/></svg>
            Engineers are assigned to project leads, expense tracking, and branch-specific quotation preparation.
        </p>
    </section>

    {{-- Roles & Permissions Matrix Card --}}
    @php
        $groups = [
            'Dashboard' => ['dashboard.view'],
            'Leads' => ['leads.view', 'leads.create', 'leads.edit', 'leads.assign', 'leads.delete'],
            'Quotations' => ['quotes.view', 'quotes.create', 'quotes.edit', 'quotes.submit', 'quotes.approve', 'quotes.send', 'quotes.award'],
            'Projects' => ['projects.view', 'projects.create', 'projects.open', 'projects.edit'],
            'Expenses & Cash' => ['expenses.view', 'expenses.create', 'expenses.approve', 'payments.create', 'transfers.view', 'transfers.create', 'transfers.edit', 'transfers.approve'],
            'Finance' => ['transactions.view', 'transactions.approve', 'invoices.view', 'invoices.create', 'invoices.submit'],
            'Reports & Integrations' => ['reports.view', 'reports.export', 'zoho.view', 'zoho.sync'],
            'Administration' => ['users.view', 'users.edit', 'roles.view', 'roles.edit', 'branches.view', 'branches.edit', 'settings.view', 'settings.edit', 'audit.view'],
        ];

        $roleList = [
            'super_admin' => 'Super Admin',
            'estimation' => 'Estimation User',
            'engineer' => 'Project Engineer',
            'accountant' => 'Accountant',
        ];
    @endphp

    <section class="card" id="matrix">
        <header class="card-head">
            <h2>
                <span class="head-ic">
                    <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-bookmark"/></svg>
                </span>
                Roles &amp; Permissions Matrix
            </h2>
        </header>
        <div class="table-wrap page">
            <table class="table matrix">
                <thead>
                    <tr>
                        <th>Permission</th>
                        <th>Key</th>
                        @foreach($roleList as $roleKey => $roleLabel)
                            <th class="ta-c">{{ $roleLabel }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($groups as $groupTitle => $permKeys)
                        <tr class="group"><td colspan="6"><strong>{{ $groupTitle }}</strong></td></tr>
                        @foreach($permKeys as $pKey)
                            <tr>
                                <td class="strong">{{ ucwords(str_replace(['.', '_'], ' ', $pKey)) }}</td>
                                <td><code>{{ $pKey }}</code></td>
                                @foreach($roleList as $roleKey => $roleLabel)
                                    @php
                                        $roleModel = $roles->firstWhere('name', $roleKey);
                                        $has = $roleModel && $roleModel->hasPermissionTo($pKey);
                                    @endphp
                                    <td class="ta-c">
                                        @if($has)
                                            <span class="tick yes"><svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-check"/></svg></span>
                                        @else
                                            <span class="tick no" style="color:#aaa;">—</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    {{-- Audit Log Card --}}
    @can('audit.view')
    <section class="card" id="auditLog">
        <header class="card-head">
            <h2>
                <span class="head-ic">
                    <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-clock"/></svg>
                </span>
                Audit Log (Recent Activity)
            </h2>
        </header>
        <div class="table-wrap page">
            <table class="table">
                <thead>
                    <tr>
                        <th>When</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Target</th>
                        <th>Details</th>
                        <th>IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($auditLogs as $log)
                        <tr>
                            <td>{{ $log->created_at->format('d M Y · h:i A') }}</td>
                            <td class="strong">{{ $log->user?->name ?? 'System' }}</td>
                            <td>
                                <span class="pill pill-{{ in_array($log->action, ['created', 'approved', 'login']) ? 'green' : (in_array($log->action, ['deleted', 'rejected']) ? 'red' : 'blue') }}">
                                    {{ ucfirst($log->action) }}
                                </span>
                            </td>
                            <td>{{ $log->entity_type }} @if($log->entity_id)#{{ $log->entity_id }}@endif</td>
                            <td>{{ $log->description ?: '—' }}</td>
                            <td><code>{{ $log->ip_address ?: '—' }}</code></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="empty">No audit events recorded yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
    @endcan
</div>

{{-- Create / Edit User Modal --}}
<div class="modal-wrap" id="userModal" style="display: none;">
    <div class="modal modal-lg" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
        <div class="modal-head">
            <div>
                <h3 id="modalTitle">Create New User</h3>
                <p id="modalSub">Assign roles, branch scope, and credentials</p>
            </div>
            <button class="icon-btn" type="button" onclick="closeUserModal()" aria-label="Close">
                <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-x"/></svg>
            </button>
        </div>
        <form id="userForm" method="POST" action="{{ route('users.store') }}">
            @csrf
            <input type="hidden" name="_method" id="userMethod" value="POST">

            <div class="modal-body">
                <div class="form-grid" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 14px;">
                    <label class="field">
                        <span class="field-label">Full Name *</span>
                        <input name="name" id="userNameInput" required maxlength="100" placeholder="e.g. Tariq Mansour">
                    </label>

                    <label class="field">
                        <span class="field-label">Email Address *</span>
                        <input name="email" id="userEmailInput" type="email" required maxlength="100" placeholder="name@richmount.ae">
                    </label>

                    <label class="field">
                        <span class="field-label">Password <span id="pwdHint" style="font-weight:normal; font-size:11px; color:#888;">(leave blank to keep existing)</span></span>
                        <input name="password" id="userPasswordInput" type="password" minlength="6" placeholder="Min 6 characters">
                    </label>

                    <label class="field">
                        <span class="field-label">Role *</span>
                        <span class="select-wrap">
                            <select name="role_name" id="userRoleSelect" required onchange="syncRoleFields(this.value)">
                                <option value="super_admin">Super Admin (All access)</option>
                                <option value="estimation">Estimation User</option>
                                <option value="engineer" selected>Project Engineer</option>
                                <option value="accountant">Accountant</option>
                            </select>
                        </span>
                    </label>

                    <label class="field" id="branchField">
                        <span class="field-label">Primary Branch</span>
                        <span class="select-wrap">
                            <select name="branch_id" id="userBranchSelect">
                                @foreach($branches as $b)
                                    <option value="{{ $b->id }}">{{ $b->name }} ({{ $b->code }})</option>
                                @endforeach
                            </select>
                        </span>
                    </label>

                    <label class="field" style="display: flex; align-items: center; gap: 8px; margin-top: 24px;">
                        <input type="checkbox" name="all_branches" id="userAllBranches" value="1">
                        <span>Has access to All Branches</span>
                    </label>

                    <label class="field">
                        <span class="field-label">Phone Number</span>
                        <input name="phone" id="userPhoneInput" maxlength="30" placeholder="+971 50 000 0000">
                    </label>

                    <label class="field" id="engCodeField">
                        <span class="field-label">Engineer Code</span>
                        <input name="engineer_code" id="userEngCodeInput" maxlength="20" placeholder="e.g. e7">
                    </label>

                    <label class="field" id="zohoAccountField">
                        <span class="field-label">Zoho Books Petty Cash Account ID</span>
                        <input name="zoho_petty_cash_account_id" id="userZohoAccountInput" maxlength="80" placeholder="e.g. zoho_petty_user">
                    </label>

                    <label class="field" style="display: flex; align-items: center; gap: 8px; margin-top: 24px;">
                        <input type="checkbox" name="is_active" id="userIsActive" value="1" checked>
                        <span>Account is Active</span>
                    </label>
                </div>
            </div>

            <div class="modal-foot">
                <button class="btn btn-ghost" type="button" onclick="closeUserModal()">Cancel</button>
                <button class="btn btn-primary" type="submit" id="modalSubmitBtn">
                    <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-check"/></svg>
                    Save User
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openCreateUserModal() {
        document.getElementById('modalTitle').textContent = 'Create New User';
        document.getElementById('modalSub').textContent = 'Assign role, branch scope, and credentials';
        document.getElementById('userMethod').value = 'POST';
        document.getElementById('userForm').action = "{{ route('users.store') }}";
        document.getElementById('userPasswordInput').required = true;
        document.getElementById('pwdHint').style.display = 'none';
        
        document.getElementById('userNameInput').value = '';
        document.getElementById('userEmailInput').value = '';
        document.getElementById('userPasswordInput').value = '';
        document.getElementById('userRoleSelect').value = 'engineer';
        document.getElementById('userPhoneInput').value = '';
        document.getElementById('userEngCodeInput').value = '';
        document.getElementById('userZohoAccountInput').value = '';
        document.getElementById('userAllBranches').checked = false;
        document.getElementById('userIsActive').checked = true;

        document.getElementById('userModal').style.display = 'grid';
    }

    function openEditUserModal(user) {
        document.getElementById('modalTitle').textContent = 'Edit ' + user.name;
        document.getElementById('modalSub').textContent = 'Update account details and role permissions';
        document.getElementById('userMethod').value = 'PUT';
        document.getElementById('userForm').action = "/users/" + user.id;
        document.getElementById('userPasswordInput').required = false;
        document.getElementById('pwdHint').style.display = 'inline';

        document.getElementById('userNameInput').value = user.name || '';
        document.getElementById('userEmailInput').value = user.email || '';
        document.getElementById('userPasswordInput').value = '';
        document.getElementById('userRoleSelect').value = user.role_name || 'engineer';
        document.getElementById('userBranchSelect').value = user.branch_id || '';
        document.getElementById('userPhoneInput').value = user.phone || '';
        document.getElementById('userEngCodeInput').value = user.engineer_code || '';
        document.getElementById('userZohoAccountInput').value = user.zoho_petty_cash_account_id || '';
        document.getElementById('userAllBranches').checked = !!user.all_branches;
        document.getElementById('userIsActive').checked = !!user.is_active;

        document.getElementById('userModal').style.display = 'grid';
    }

    function closeUserModal() {
        document.getElementById('userModal').style.display = 'none';
    }

    function syncRoleFields(role) {
        var isEng = role === 'engineer';
        var engField = document.getElementById('engCodeField');
        var zohoField = document.getElementById('zohoAccountField');
        if (engField) engField.style.display = isEng ? 'block' : 'none';
        if (zohoField) zohoField.style.display = isEng ? 'block' : 'none';
        
        if (role === 'super_admin' || role === 'accountant') {
            document.getElementById('userAllBranches').checked = true;
        }
    }
</script>
@endsection
