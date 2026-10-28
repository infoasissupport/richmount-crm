@php
    $authUser = auth()->user();
    $currentBranch = $authUser?->branch;
    $branchLabel = $authUser?->all_branches || !$currentBranch ? 'All Branches' : $currentBranch->short_name;
@endphp

<header class="topbar" id="topbar">
    <button class="tb-btn" id="menuToggle" aria-label="Toggle menu">
        <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-menu"/></svg>
    </button>

    <nav class="crumbs" aria-label="Breadcrumb">
        <a class="crumb-home" href="{{ route('dashboard') }}" aria-label="Dashboard">
            <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-home"/></svg>
        </a>
        <a class="crumb" href="{{ route('dashboard') }}">Dashboard</a>
        @yield('breadcrumbs')
    </nav>

    <div class="gsearch" id="gsearch">
        <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-search"/></svg>
        <input id="globalSearch" type="search" placeholder="Search across Leads, Projects, Quotes, Expenses..." autocomplete="off" aria-label="Global search">
        <kbd>Ctrl + K</kbd>
        <div class="gs-results" id="gsResults" hidden></div>
    </div>

    <div class="tb-right">
        {{-- Branch indicator / switcher --}}
        <button class="tb-branch" id="branchBtn" aria-haspopup="true">
            <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-building"/></svg>
            <span id="branchLabel">{{ $branchLabel }}</span>
            <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-chevron-down"/></svg>
        </button>

        {{-- Mobile search trigger --}}
        <button class="tb-btn only-mobile" id="searchToggle" aria-label="Search">
            <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-search"/></svg>
        </button>

        {{-- Notifications --}}
        <button class="tb-btn" id="notifBtn" aria-label="Notifications" aria-haspopup="true">
            <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-bell"/></svg>
            <span class="badge zero" id="notifCount">0</span>
        </button>

        {{-- Settings --}}
        @can('settings.view')
        <a class="tb-btn" id="settingsBtn" href="{{ route('settings.index') }}" aria-label="Settings">
            <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-settings"/></svg>
        </a>
        @endcan

        {{-- User Profile Menu --}}
        @if($authUser)
        <div class="dropdown-wrap" style="position: relative;">
            <button class="tb-user" id="userBtn" type="button" aria-haspopup="true" aria-expanded="false" onclick="toggleUserMenu(event)">
                <span class="tb-avatar" id="userAvatar" aria-hidden="true" data-role="{{ $authUser->role_name }}">{{ $authUser->initials }}</span>
                <span class="tb-user-text">
                    <strong id="userName">{{ $authUser->name }}</strong>
                    <small id="userRole">{{ $authUser->role_label }}</small>
                </span>
                <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-chevron-down"/></svg>
            </button>

            <div class="popover" id="userMenuPop" style="display: none; position: absolute !important; right: 0; top: calc(100% + 8px); min-width: 250px; z-index: 1000; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.18), 0 8px 10px -6px rgba(0,0,0,0.12);">
                <div class="pop-user" style="display: flex; align-items: center; gap: 10px; padding: 10px 12px; border-bottom: 1px solid var(--line, #e2e8f0);">
                    <span class="tb-avatar lg" data-role="{{ $authUser->role_name }}" style="width: 38px; height: 38px; border-radius: 50%; display: grid; place-items: center; font-weight: 700; font-size: 13px; flex-shrink: 0;">{{ $authUser->initials }}</span>
                    <div style="overflow: hidden; flex: 1;">
                        <strong style="display: block; font-size: 13.5px; color: var(--text, #1e293b); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $authUser->name }}</strong>
                        <small style="display: block; font-size: 11.5px; color: var(--muted, #64748b); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $authUser->email }}</small>
                        <span class="role-chip" style="margin-top: 4px; display: inline-block; font-size: 11px; padding: 2px 8px; border-radius: 999px; background: rgba(148, 28, 60, 0.1); color: #941C3C; font-weight: 600;">{{ $authUser->role_label }}</span>
                    </div>
                </div>

                <div style="padding: 4px;">
                    <a class="pop-item" href="{{ route('profile.edit') }}" style="text-decoration: none;">
                        <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-user"/></svg>
                        <span>My Profile</span>
                    </a>
                    @can('settings.view')
                    <a class="pop-item" href="{{ route('settings.index') }}" style="text-decoration: none;">
                        <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-settings"/></svg>
                        <span>Settings</span>
                    </a>
                    @endcan
                </div>

                <div class="pop-div" style="height: 1px; background: var(--line, #e2e8f0); margin: 4px 0;"></div>

                <div style="padding: 4px;">
                    <form action="{{ route('logout') }}" method="POST" style="margin: 0;">
                        @csrf
                        <button type="submit" class="pop-item danger" style="background: none; border: none; width: 100%; cursor: pointer;">
                            <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-log-out"/></svg>
                            <span>Sign Out</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
        @endif
    </div>
</header>

<script>
    function toggleUserMenu(e) {
        if (e) {
            e.preventDefault();
            e.stopPropagation();
        }
        var pop = document.getElementById('userMenuPop');
        var btn = document.getElementById('userBtn');
        if (!pop) return;
        var isVisible = pop.style.display === 'block';
        pop.style.display = isVisible ? 'none' : 'block';
        if (btn) btn.setAttribute('aria-expanded', !isVisible);
    }

    document.addEventListener('click', function(e) {
        var btn = document.getElementById('userBtn');
        var pop = document.getElementById('userMenuPop');
        if (pop && pop.style.display === 'block') {
            if (!pop.contains(e.target) && (!btn || !btn.contains(e.target))) {
                pop.style.display = 'none';
                if (btn) btn.setAttribute('aria-expanded', 'false');
            }
        }
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            var pop = document.getElementById('userMenuPop');
            var btn = document.getElementById('userBtn');
            if (pop && pop.style.display === 'block') {
                pop.style.display = 'none';
                if (btn) btn.setAttribute('aria-expanded', 'false');
            }
        }
    });
</script>
