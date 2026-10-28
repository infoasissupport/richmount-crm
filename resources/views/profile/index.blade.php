@extends('layouts.app')

@section('title', 'My Profile')
@section('page_id', 'profile')

@section('breadcrumbs')
    <svg class="ic sep" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-chevron-right"/></svg>
    <span class="crumb current">My Profile</span>
@endsection

@section('content')
<section class="page-head" id="pageHead">
    <div>
        <h1>My Profile</h1>
        <p class="sub">Account information, branch assignment, security settings and active permissions.</p>
    </div>
</section>

<div class="dash-scroll">
    <div class="detail-grid">
        {{-- Profile Card --}}
        <section class="card profile-card" id="profileCard">
            <div class="dr-hero">
                <span class="tb-avatar xl" data-role="{{ $user->role_name }}">{{ $user->initials }}</span>
                <div>
                    <h4>{{ $user->name }}</h4>
                    <p>{{ $user->email }}</p>
                </div>
                <span class="role-chip">{{ $user->role_label }}</span>
            </div>

            <dl class="kv">
                <dt>Role</dt>
                <dd><strong>{{ $user->role_label }}</strong></dd>

                <dt>Branch Access</dt>
                <dd>{{ $user->all_branches ? 'All Branches (Enterprise)' : ($user->branch?->name ?? 'Unassigned') }}</dd>

                <dt>Cash in Hand</dt>
                <dd><b>AED {{ number_format($user->cash_balance, 2) }}</b></dd>

                <dt>Phone</dt>
                <dd>{{ $user->phone ?: 'Not set' }}</dd>

                <dt>Total Permissions</dt>
                <dd><strong>{{ $permissions->count() }} active permissions</strong></dd>

                <dt>Security</dt>
                <dd><span class="pill pill-green"><i></i>HTTPS &amp; Encrypted Sessions</span></dd>
            </dl>

            <div style="margin-top: 18px; padding-top: 14px; border-top: 1px solid var(--border-color, #eee);">
                <h4 style="margin-bottom: 10px; font-size: 14px;">Update Contact Details</h4>
                <form action="{{ route('profile.update') }}" method="POST" class="form-grid">
                    @csrf
                    @method('PUT')
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                        <label class="field">
                            <span class="field-label">Name</span>
                            <input name="name" value="{{ old('name', $user->name) }}" required>
                        </label>
                        <label class="field">
                            <span class="field-label">Phone</span>
                            <input name="phone" value="{{ old('phone', $user->phone) }}" placeholder="+971 50 000 0000">
                        </label>
                    </div>
                    <button class="btn btn-soft btn-sm" type="submit" style="align-self: flex-start; margin-top: 6px;">
                        <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-check"/></svg>
                        Save Contact Info
                    </button>
                </form>
            </div>
        </section>

        {{-- Switch User & Change Password --}}
        <div style="display: flex; flex-direction: column; gap: 16px;">
            {{-- Fast Switch User (Demo/Pairing Helper) --}}
            <section class="card">
                <header class="card-head">
                    <h2>
                        <span class="head-ic">
                            <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-users"/></svg>
                        </span>
                        Switch Demo Role
                    </h2>
                </header>
                <div class="user-switch" id="userSwitch">
                    @foreach($demoUsers as $du)
                        <div class="us-item" style="display: flex; align-items: center; justify-content: space-between; padding: 10px 14px; border-bottom: 1px solid #f0f0f0;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <span class="tb-avatar sm" data-role="{{ $du->role_name }}">{{ $du->initials }}</span>
                                <div>
                                    <strong>{{ $du->name }}</strong>
                                    <small style="display: block; color: #888;">{{ $du->role_label }} · {{ $du->email }}</small>
                                </div>
                            </div>
                            @if(auth()->id() === $du->id)
                                <span class="pill pill-green">Current</span>
                            @else
                                <form action="{{ route('auth.switch-user') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="user_id" value="{{ $du->id }}">
                                    <button type="submit" class="btn btn-ghost btn-sm">
                                        <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-log-out"/></svg>
                                        Sign in
                                    </button>
                                </form>
                            @endif
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- Update Password Card --}}
            <section class="card">
                <header class="card-head">
                    <h2>
                        <span class="head-ic">
                            <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-settings"/></svg>
                        </span>
                        Change Password
                    </h2>
                </header>
                <form action="{{ route('profile.password') }}" method="POST" class="form-grid" style="padding: 14px 18px;">
                    @csrf
                    @method('PUT')
                    <label class="field">
                        <span class="field-label">Current Password</span>
                        <input name="current_password" type="password" required autocomplete="current-password">
                    </label>
                    <label class="field">
                        <span class="field-label">New Password</span>
                        <input name="password" type="password" required autocomplete="new-password" minlength="6">
                    </label>
                    <label class="field">
                        <span class="field-label">Confirm New Password</span>
                        <input name="password_confirmation" type="password" required autocomplete="new-password">
                    </label>
                    <button class="btn btn-primary" type="submit" style="margin-top: 8px;">
                        Update Password
                    </button>
                </form>
            </section>
        </div>
    </div>

    {{-- Permissions Grid Card --}}
    <section class="card" style="margin-top: 16px;">
        <header class="card-head">
            <h2>
                <span class="head-ic">
                    <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-bookmark"/></svg>
                </span>
                Active Permissions Assigned to My Role
            </h2>
        </header>
        <div style="padding: 16px 20px;">
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 10px;">
                @foreach($permissions as $perm)
                    <div style="display: flex; align-items: center; gap: 8px; font-size: 13px; background: #fafafa; border: 1px solid #eee; padding: 8px 12px; border-radius: 6px;">
                        <svg class="ic sm" style="color: var(--primary);" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-check"/></svg>
                        <span>{{ ucwords(str_replace(['.', '_'], ' ', $perm->name)) }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
</div>
@endsection
