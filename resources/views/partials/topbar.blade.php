@php
    $authUser = auth()->user();
    $unreadNotifCount = $authUser ? $authUser->unreadNotifications()->count() : 0;
    $recentNotifications = $authUser ? $authUser->notifications()->take(20)->get() : collect();
    $activeBranchSession = session('active_branch_id');
    $allBranches = \App\Models\Branch::active()->get();

    if ($activeBranchSession === 'all' || ($authUser && ($authUser->all_branches || $authUser->isSuperAdmin()) && !$activeBranchSession)) {
        $activeBranchId = 'all';
        $branchLabel = 'All Branches';
    } elseif ($activeBranchSession && $activeBranch = $allBranches->firstWhere('id', (int)$activeBranchSession)) {
        $activeBranchId = $activeBranch->id;
        $branchLabel = $activeBranch->short_name ?: $activeBranch->name;
    } elseif ($authUser && $authUser->branch) {
        $activeBranchId = $authUser->branch->id;
        $branchLabel = $authUser->branch->short_name ?: $authUser->branch->name;
    } else {
        $activeBranchId = 'all';
        $branchLabel = 'All Branches';
    }
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
        <div class="dropdown-wrap" style="position: relative;">
            <button class="tb-branch" id="branchBtn" type="button" aria-haspopup="true" aria-expanded="false" onclick="toggleBranchMenu(event)">
                <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-building"/></svg>
                <span id="branchLabel">{{ $branchLabel }}</span>
                <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-chevron-down"/></svg>
            </button>

            <div class="popover" id="branchMenuPop" style="display: none; position: absolute !important; right: 0; top: calc(100% + 8px); min-width: 240px; z-index: 1000; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.18), 0 8px 10px -6px rgba(0,0,0,0.12);">
                <div class="pop-head" style="padding: 8px 12px 4px; font-size: 11px; font-weight: 700; color: var(--muted); text-transform: uppercase;">
                    Select Branch
                </div>

                <div style="padding: 4px;">
                    @if($authUser && ($authUser->all_branches || $authUser->isSuperAdmin() || $authUser->isAccountant()))
                    <form action="{{ route('branches.switch') }}" method="POST" style="margin: 0;">
                        @csrf
                        <input type="hidden" name="branch_id" value="all">
                        <button type="submit" class="pop-item {{ $activeBranchId === 'all' ? 'active' : '' }}" style="background: none; border: none; width: 100%; cursor: pointer; text-align: left; display: flex; align-items: center; gap: 8px; padding: 8px 10px; border-radius: 6px; font-size: 13px; color: var(--text);">
                            <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-globe"/></svg>
                            <span style="flex: 1; font-weight: {{ $activeBranchId === 'all' ? '600' : 'normal' }};">All Branches</span>
                            @if($activeBranchId === 'all')
                            <svg class="ic sm" style="color: var(--primary, #941C3C); width: 16px; height: 16px;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-check"/></svg>
                            @endif
                        </button>
                    </form>
                    <div class="pop-div" style="height: 1px; background: var(--line, #e2e8f0); margin: 4px 0;"></div>
                    @endif

                    @foreach($allBranches as $b)
                        @if($authUser && $authUser->canAccessBranch($b->id))
                        <form action="{{ route('branches.switch') }}" method="POST" style="margin: 0;">
                            @csrf
                            <input type="hidden" name="branch_id" value="{{ $b->id }}">
                            <button type="submit" class="pop-item {{ (string)$activeBranchId === (string)$b->id ? 'active' : '' }}" style="background: none; border: none; width: 100%; cursor: pointer; text-align: left; display: flex; align-items: center; gap: 8px; padding: 8px 10px; border-radius: 6px; font-size: 13px; color: var(--text);">
                                <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-building"/></svg>
                                <span style="flex: 1;">
                                    <strong style="display: block; font-size: 13px; font-weight: {{ (string)$activeBranchId === (string)$b->id ? '600' : 'normal' }};">{{ $b->name }}</strong>
                                    <small style="color: var(--muted); font-size: 11px;">{{ $b->code }} &bull; {{ $b->country }}</small>
                                </span>
                                @if((string)$activeBranchId === (string)$b->id)
                                <svg class="ic sm" style="color: var(--primary, #941C3C); width: 16px; height: 16px;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-check"/></svg>
                                @endif
                            </button>
                        </form>
                        @endif
                    @endforeach
                </div>

                @can('branches.view')
                <div class="pop-div" style="height: 1px; background: var(--line, #e2e8f0); margin: 4px 0;"></div>
                <div style="padding: 4px;">
                    <a class="pop-item" href="{{ route('branches.index') }}" style="text-decoration: none; font-size: 12px; color: var(--primary, #941C3C); display: flex; align-items: center; gap: 8px; padding: 6px 10px;">
                        <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-settings"/></svg>
                        <span>Manage Branches</span>
                    </a>
                </div>
                @endcan
            </div>
        </div>

        {{-- Mobile search trigger --}}
        <button class="tb-btn only-mobile" id="searchToggle" aria-label="Search">
            <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-search"/></svg>
        </button>

        {{-- Notifications --}}
        <div class="dropdown-wrap" style="position: relative;">
            <button class="tb-btn" id="notifBtn" type="button" aria-label="Notifications" aria-haspopup="true" aria-expanded="false" onclick="toggleNotifMenu(event)">
                <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-bell"/></svg>
                <span class="badge {{ $unreadNotifCount === 0 ? 'zero' : '' }}" id="notifCount" style="{{ $unreadNotifCount === 0 ? 'display: none;' : '' }}">{{ $unreadNotifCount }}</span>
            </button>

            <div class="popover" id="notifMenuPop" style="display: none; position: absolute !important; right: 0; top: calc(100% + 8px); width: 340px; max-width: 90vw; z-index: 1000; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.18), 0 8px 10px -6px rgba(0,0,0,0.12); border-radius: 8px; background: var(--bg-surface, #fff); border: 1px solid var(--line, #e2e8f0); overflow: hidden;">
                <div class="notif-head" style="display: flex; align-items: center; justify-content: space-between; padding: 10px 14px; border-bottom: 1px solid var(--line, #e2e8f0); background: var(--bg, #f8fafc);">
                    <strong style="font-size: 13.5px; font-weight: 700; color: var(--text, #1e293b);">Notifications</strong>
                    @if($unreadNotifCount > 0)
                    <form action="{{ route('notifications.readAll') }}" method="POST" style="margin: 0;">
                        @csrf
                        <button type="submit" class="link-btn" style="background: none; border: none; color: var(--primary, #941C3C); font-size: 12px; font-weight: 600; cursor: pointer; padding: 0;">Mark all as read</button>
                    </form>
                    @endif
                </div>

                <div class="notif-list" id="notifList" style="max-height: 380px; overflow-y: auto;">
                    @forelse($recentNotifications as $n)
                        @php
                            $nData = $n->data;
                            $isUnread = is_null($n->read_at);
                            $icName = match($nData['type'] ?? '') {
                                'lead' => 'users',
                                'quote' => 'file-text',
                                default => 'bell',
                            };
                        @endphp
                        <form action="{{ route('notifications.read', $n->id) }}" method="POST" style="margin: 0; display: block;">
                            @csrf
                            <button type="submit" class="notif n-{{ $nData['type'] ?? 'lead' }} {{ $isUnread ? 'unread' : '' }}" style="width: 100%; border: none; background: {{ $isUnread ? 'rgba(148, 28, 60, 0.04)' : 'transparent' }}; border-bottom: 1px solid var(--line, #f1f5f9); display: flex; gap: 10px; padding: 10px 12px; text-align: left; cursor: pointer; transition: background 0.15s; position: relative;">
                                <span class="n-ic" style="width: 32px; height: 32px; border-radius: 8px; display: grid; place-items: center; background: rgba(148, 28, 60, 0.1); color: var(--primary, #941C3C); flex-shrink: 0; margin-top: 2px;">
                                    <svg class="ic" style="width: 16px; height: 16px;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-{{ $icName }}"/></svg>
                                </span>
                                <span style="flex: 1; overflow: hidden;">
                                    <strong style="display: block; font-size: 12.5px; font-weight: 600; color: var(--text, #1e293b); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $nData['title'] ?? 'Notification' }}</strong>
                                    <p style="font-size: 11.5px; color: var(--muted, #64748b); margin: 2px 0 0; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">{{ $nData['text'] ?? '' }}</p>
                                    <small style="display: block; font-size: 10.5px; color: var(--muted, #94a3b8); margin-top: 4px;">{{ $n->created_at->diffForHumans() }}</small>
                                </span>
                                @if($isUnread)
                                <span style="position: absolute; right: 12px; top: 12px; width: 7px; height: 7px; border-radius: 50%; background: var(--primary, #941C3C);"></span>
                                @endif
                            </button>
                        </form>
                    @empty
                        <div style="padding: 24px; text-align: center; color: var(--muted, #94a3b8); font-size: 13px;">
                            <svg class="ic" style="width: 28px; height: 28px; margin: 0 auto 8px; display: block; opacity: 0.5;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-bell"/></svg>
                            No notifications
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

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
    function toggleBranchMenu(e) {
        if (e) {
            e.preventDefault();
            e.stopPropagation();
        }
        var pop = document.getElementById('branchMenuPop');
        var btn = document.getElementById('branchBtn');
        var userPop = document.getElementById('userMenuPop');
        var notifPop = document.getElementById('notifMenuPop');
        if (userPop) userPop.style.display = 'none';
        if (notifPop) notifPop.style.display = 'none';
        if (!pop) return;
        var isVisible = pop.style.display === 'block';
        pop.style.display = isVisible ? 'none' : 'block';
        if (btn) btn.setAttribute('aria-expanded', !isVisible);
    }

    function toggleUserMenu(e) {
        if (e) {
            e.preventDefault();
            e.stopPropagation();
        }
        var pop = document.getElementById('userMenuPop');
        var btn = document.getElementById('userBtn');
        var branchPop = document.getElementById('branchMenuPop');
        var notifPop = document.getElementById('notifMenuPop');
        if (branchPop) branchPop.style.display = 'none';
        if (notifPop) notifPop.style.display = 'none';
        if (!pop) return;
        var isVisible = pop.style.display === 'block';
        pop.style.display = isVisible ? 'none' : 'block';
        if (btn) btn.setAttribute('aria-expanded', !isVisible);
    }

    function toggleNotifMenu(e) {
        if (e) {
            e.preventDefault();
            e.stopPropagation();
        }
        var pop = document.getElementById('notifMenuPop');
        var btn = document.getElementById('notifBtn');
        var branchPop = document.getElementById('branchMenuPop');
        var userPop = document.getElementById('userMenuPop');
        if (branchPop) branchPop.style.display = 'none';
        if (userPop) userPop.style.display = 'none';
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

        var bBtn = document.getElementById('branchBtn');
        var bPop = document.getElementById('branchMenuPop');
        if (bPop && bPop.style.display === 'block') {
            if (!bPop.contains(e.target) && (!bBtn || !bBtn.contains(e.target))) {
                bPop.style.display = 'none';
                if (bBtn) bBtn.setAttribute('aria-expanded', 'false');
            }
        }

        var nBtn = document.getElementById('notifBtn');
        var nPop = document.getElementById('notifMenuPop');
        if (nPop && nPop.style.display === 'block') {
            if (!nPop.contains(e.target) && (!nBtn || !nBtn.contains(e.target))) {
                nPop.style.display = 'none';
                if (nBtn) nBtn.setAttribute('aria-expanded', 'false');
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

            var bPop = document.getElementById('branchMenuPop');
            var bBtn = document.getElementById('branchBtn');
            if (bPop && bPop.style.display === 'block') {
                bPop.style.display = 'none';
                if (bBtn) bBtn.setAttribute('aria-expanded', 'false');
            }

            var nPop = document.getElementById('notifMenuPop');
            var nBtn = document.getElementById('notifBtn');
            if (nPop && nPop.style.display === 'block') {
                nPop.style.display = 'none';
                if (nBtn) nBtn.setAttribute('aria-expanded', 'false');
            }
        }
    });

    function refreshNotifications() {
        fetch('{{ route('notifications.index') }}', {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(r) { return r.json(); })
        .then(function(res) {
            if (!res || !res.success) return;
            var badge = document.getElementById('notifCount');
            if (badge) {
                badge.textContent = res.unread_count;
                if (res.unread_count > 0) {
                    badge.classList.remove('zero');
                    badge.style.display = '';
                } else {
                    badge.classList.add('zero');
                    badge.style.display = 'none';
                }
            }
        })
        .catch(function() {});
    }
    setInterval(refreshNotifications, 30000);
</script>
