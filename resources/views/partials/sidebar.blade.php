<aside class="sidebar" id="sidebar" aria-label="Main navigation">
    <a class="brand" href="{{ route('dashboard') }}" aria-label="Rich Mount ERP home">
        <img src="{{ asset('images/rich-mount-logo.png') }}" alt="Rich Mount ERP Logo" width="64" height="49">
        <span class="brand-text">
            <span class="brand-name">RICH MOUNT</span>
            <span class="brand-sub">ERP SYSTEM</span>
        </span>
    </a>

    <div class="sidebar-inner">
        <label class="side-search">
            <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-search"/></svg>
            <input type="search" id="menuSearch" placeholder="Search menu..." aria-label="Search menu">
        </label>

        <nav class="nav" id="nav">
            {{-- Dashboard --}}
            @can('dashboard.view')
            <div class="nav-group">
                <a class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}" title="Dashboard" {{ request()->routeIs('dashboard') ? 'aria-current="page"' : '' }}>
                    <span class="nav-ic"><svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-nav-dashboard"/></svg></span>
                    <span class="nav-label">Dashboard</span>
                </a>
            </div>
            @endcan

            {{-- Lead Management --}}
            @can('leads.view')
            <div class="nav-group" data-group="leads">
                <a class="nav-item {{ request()->routeIs('leads.*') ? 'active' : '' }}" href="{{ route('leads.index') }}" data-nav="leads" title="Lead Management" {{ request()->routeIs('leads.*') ? 'aria-current="page"' : '' }} aria-haspopup="true" aria-expanded="false">
                    <span class="nav-ic"><svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-nav-leads"/></svg></span>
                    <span class="nav-label">Lead Management</span>
                    <svg class="ic nav-chev" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-chevron-right"/></svg>
                </a>
                <div class="nav-sub">
                    <a class="nav-subitem {{ request()->routeIs('leads.index') && !request()->has('new') ? 'active' : '' }}" href="{{ route('leads.index') }}">All Leads</a>
                    @can('leads.create')
                    <a class="nav-subitem {{ request()->routeIs('leads.index') && request()->get('new') == 1 ? 'active' : '' }}" href="{{ route('leads.index', ['new' => 1]) }}">Add Lead</a>
                    @endcan
                </div>
            </div>
            @endcan

            {{-- Quotations --}}
            @can('quotes.view')
            <div class="nav-group" data-group="quotes">
                <a class="nav-item {{ request()->routeIs('quotations.*') ? 'active' : '' }}" href="{{ route('quotations.index') }}" data-nav="quotes" title="Quotations" {{ request()->routeIs('quotations.*') ? 'aria-current="page"' : '' }} aria-haspopup="true" aria-expanded="false">
                    <span class="nav-ic"><svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-nav-quotes"/></svg></span>
                    <span class="nav-label">Quotations</span>
                    <svg class="ic nav-chev" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-chevron-right"/></svg>
                </a>
                <div class="nav-sub">
                    {{-- the Quotations list filters on ?tab=…; Create Quotation opens its lead picker popup (?create=1) --}}
                    <a class="nav-subitem {{ request()->routeIs('quotations.index') && in_array(request('tab', 'all'), ['all', '']) && !request()->has('create') ? 'active' : '' }}" href="{{ route('quotations.index') }}">All Quotations</a>
                    @can('quotes.create')
                    <a class="nav-subitem {{ request()->routeIs('quotations.index') && request('create') == 1 ? 'active' : '' }}" href="{{ route('quotations.index', ['create' => 1]) }}">Create Quotation</a>
                    @endcan
                    <a class="nav-subitem {{ request()->routeIs('quotations.index') && request('tab') === 'pending' ? 'active' : '' }}" href="{{ route('quotations.index', ['tab' => 'pending']) }}">Pending Approval</a>
                    <a class="nav-subitem {{ request()->routeIs('quotations.index') && request('tab') === 'sent' ? 'active' : '' }}" href="{{ route('quotations.index', ['tab' => 'sent']) }}">Sent Quotations</a>
                    <a class="nav-subitem {{ request()->routeIs('quotations.index') && request('tab') === 'awarded' ? 'active' : '' }}" href="{{ route('quotations.index', ['tab' => 'awarded']) }}">Awarded Quotes</a>
                </div>
            </div>
            @endcan

            {{-- Projects --}}
            @can('projects.view')
            <div class="nav-group" data-group="projects">
                <a class="nav-item {{ request()->routeIs('projects.*') ? 'active' : '' }}" href="{{ route('projects.index') }}" data-nav="projects" title="Projects" {{ request()->routeIs('projects.*') ? 'aria-current="page"' : '' }} aria-haspopup="true" aria-expanded="false">
                    <span class="nav-ic"><svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-hard-hat"/></svg></span>
                    <span class="nav-label">Projects</span>
                    <svg class="ic nav-chev" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-chevron-right"/></svg>
                </a>
                <div class="nav-sub">
                    <a class="nav-subitem {{ request()->routeIs('projects.index') && !request()->has('tab') ? 'active' : '' }}" href="{{ route('projects.index') }}">All Projects</a>
                    @can('projects.create')
                    <a class="nav-subitem {{ request()->get('tab') === 'activation' ? 'active' : '' }}" href="{{ route('projects.index', ['tab' => 'activation']) }}">Create Project / PO</a>
                    @endcan
                    <a class="nav-subitem {{ request()->get('tab') === 'inprogress' ? 'active' : '' }}" href="{{ route('projects.index', ['tab' => 'inprogress']) }}">In Progress</a>
                    <a class="nav-subitem {{ request()->get('tab') === 'completed' ? 'active' : '' }}" href="{{ route('projects.index', ['tab' => 'completed']) }}">Work Completed</a>
                </div>
            </div>
            @endcan

            {{-- Expenses --}}
            @can('expenses.view')
            <div class="nav-group" data-group="expenses">
                <a class="nav-item {{ request()->routeIs('expenses.*') ? 'active' : '' }}" href="{{ route('expenses.index') }}" data-nav="expenses" title="Expenses" {{ request()->routeIs('expenses.*') ? 'aria-current="page"' : '' }} aria-haspopup="true" aria-expanded="false">
                    <span class="nav-ic"><svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-nav-expenses"/></svg></span>
                    <span class="nav-label">Expenses</span>
                    <svg class="ic nav-chev" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-chevron-right"/></svg>
                </a>
                <div class="nav-sub">
                    <a class="nav-subitem {{ request()->routeIs('expenses.index') && !request()->has('tab') && !request()->has('new') ? 'active' : '' }}" href="{{ route('expenses.index') }}">All Expenses</a>
                    @can('expenses.create')
                    <a class="nav-subitem {{ request()->get('new') == 1 ? 'active' : '' }}" href="{{ route('expenses.index', ['new' => 1]) }}">Add Expense / Scan Receipt</a>
                    @endcan
                    <a class="nav-subitem {{ request()->get('tab') === 'cashinhand' ? 'active' : '' }}" href="{{ route('expenses.index', ['tab' => 'cashinhand']) }}">Cash in Hand</a>
                    <a class="nav-subitem {{ request()->get('tab') === 'transfers' ? 'active' : '' }}" href="{{ route('expenses.index', ['tab' => 'transfers']) }}">Internal Transfers</a>
                </div>
            </div>
            @endcan

            {{-- Finance / Approval Center --}}
            @can('transactions.view')
            <div class="nav-group" data-group="finance">
                <a class="nav-item {{ request()->routeIs('finance.*') ? 'active' : '' }}" href="{{ route('finance.index') }}" data-nav="finance" title="Finance" {{ request()->routeIs('finance.*') ? 'aria-current="page"' : '' }} aria-haspopup="true" aria-expanded="false">
                    <span class="nav-ic"><svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-nav-finance"/></svg></span>
                    <span class="nav-label">Finance</span>
                    <svg class="ic nav-chev" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-chevron-right"/></svg>
                </a>
                <div class="nav-sub">
                    <a class="nav-subitem {{ request()->routeIs('finance.index') && !request()->has('tab') ? 'active' : '' }}" href="{{ route('finance.index') }}">Transactions / Approvals</a>
                    @can('transfers.approve')
                    <a class="nav-subitem {{ request()->get('tab') === 'transfers' ? 'active' : '' }}" href="{{ route('finance.index', ['tab' => 'transfers']) }}">Transfer Approvals</a>
                    @endcan
                    @can('invoices.view')
                    <a class="nav-subitem {{ request()->get('tab') === 'invoices' ? 'active' : '' }}" href="{{ route('finance.index', ['tab' => 'invoices']) }}">Invoices</a>
                    @endcan
                </div>
            </div>
            @endcan

            {{-- Zoho Books --}}
            @can('zoho.view')
            <div class="nav-group">
                <a class="nav-item {{ request()->routeIs('zoho.*') ? 'active' : '' }}" href="{{ route('zoho.index') }}" title="Zoho Books" {{ request()->routeIs('zoho.*') ? 'aria-current="page"' : '' }}>
                    <span class="nav-ic"><svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-nav-zoho"/></svg></span>
                    <span class="nav-label">Zoho Books</span>
                </a>
            </div>
            @endcan

            {{-- Reports --}}
            @can('reports.view')
            <div class="nav-group">
                <a class="nav-item {{ request()->routeIs('reports.*') ? 'active' : '' }}" href="{{ route('reports.index') }}" title="Reports" {{ request()->routeIs('reports.*') ? 'aria-current="page"' : '' }}>
                    <span class="nav-ic"><svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-nav-reports"/></svg></span>
                    <span class="nav-label">Reports</span>
                </a>
            </div>
            @endcan

            {{-- Users & Roles --}}
            @can('users.view')
            <div class="nav-group" data-group="users">
                <a class="nav-item {{ request()->routeIs('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}" data-nav="users" title="Users & Roles" {{ request()->routeIs('users.*') ? 'aria-current="page"' : '' }} aria-haspopup="true" aria-expanded="false">
                    <span class="nav-ic"><svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-nav-users"/></svg></span>
                    <span class="nav-label">Users & Roles</span>
                    <svg class="ic nav-chev" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-chevron-right"/></svg>
                </a>
                <div class="nav-sub">
                    <a class="nav-subitem {{ request()->routeIs('users.index') ? 'active' : '' }}" href="{{ route('users.index') }}">Users</a>
                    @can('roles.view')
                    <a class="nav-subitem" href="{{ url('/users#roles') }}">Roles & Permissions</a>
                    @endcan
                    @can('audit.view')
                    <a class="nav-subitem" href="{{ url('/users#audit') }}">Audit Log</a>
                    @endcan
                </div>
            </div>
            @endcan

            {{-- Branches --}}
            @can('branches.view')
            <div class="nav-group">
                <a class="nav-item {{ request()->routeIs('branches.*') ? 'active' : '' }}" href="{{ route('branches.index') }}" title="Branches" {{ request()->routeIs('branches.*') ? 'aria-current="page"' : '' }}>
                    <span class="nav-ic"><svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-nav-branches"/></svg></span>
                    <span class="nav-label">Branches</span>
                </a>
            </div>
            @endcan

            {{-- Settings --}}
            @can('settings.view')
            <div class="nav-group">
                <a class="nav-item {{ request()->routeIs('settings.*') ? 'active' : '' }}" href="{{ route('settings.index') }}" title="Settings" {{ request()->routeIs('settings.*') ? 'aria-current="page"' : '' }}>
                    <span class="nav-ic"><svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-settings"/></svg></span>
                    <span class="nav-label">Settings</span>
                </a>
            </div>
            @endcan
        </nav>
    </div>
</aside>
