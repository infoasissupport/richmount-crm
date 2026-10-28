<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign Up (Super Admin) · Rich Mount ERP</title>
    <link rel="icon" href="{{ asset('images/rich-mount-logo.png') }}" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body>
<div class="login" id="register">
    <form class="login-card" id="registerForm" method="POST" action="{{ route('register') }}">
        @csrf
        <div class="login-brand">
            <img src="{{ asset('images/rich-mount-logo.png') }}" alt="Rich Mount Logo" width="58" height="44">
            <div>
                <strong>RICH MOUNT</strong>
                <small>ERP SYSTEM</small>
            </div>
        </div>

        <h2>Sign up</h2>
        <p>Register a Super Admin account for company operations. Staff accounts (Engineers, Estimation, Accountants) are created inside the ERP from Users & Roles.</p>

        @if($errors->any())
            <div class="toast error show" style="position: static; margin-bottom: 12px;">
                <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-alert"/></svg>
                <span>{{ $errors->first() }}</span>
            </div>
        @endif

        <div style="background: rgba(148, 28, 60, 0.08); border: 1px solid rgba(148, 28, 60, 0.2); border-radius: 8px; padding: 10px 14px; margin-bottom: 14px; display: flex; align-items: center; gap: 10px;">
            <svg class="ic sm" style="color: #941C3C; flex-shrink: 0;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><use href="#i-shield"/></svg>
            <div style="font-size: 12px; line-height: 1.4; color: var(--text, #1e293b);">
                <strong style="color: #941C3C;">Account Level: Super Admin</strong>
                <div style="color: var(--muted, #64748b); font-size: 11px;">Public sign-up is reserved exclusively for Company Administrators.</div>
            </div>
        </div>

        <label class="field">
            <span class="field-label">Full Name *</span>
            <input type="text" name="name" id="regName" value="{{ old('name') }}" placeholder="e.g. John Doe" required autocomplete="name" autofocus>
        </label>

        <label class="field">
            <span class="field-label">Email Address *</span>
            <input type="email" name="email" id="regEmail" value="{{ old('email') }}" placeholder="admin@richmount.ae" required autocomplete="email">
        </label>

        <label class="field">
            <span class="field-label">Phone Number</span>
            <input type="tel" name="phone" id="regPhone" value="{{ old('phone') }}" placeholder="+971 50 000 0000" autocomplete="tel">
        </label>

        <label class="field">
            <span class="field-label">Password *</span>
            <input type="password" name="password" id="regPassword" placeholder="Min. 6 characters" required autocomplete="new-password">
        </label>

        <label class="field">
            <span class="field-label">Confirm Password *</span>
            <input type="password" name="password_confirmation" id="regPasswordConfirmation" placeholder="Re-enter password" required autocomplete="new-password">
        </label>

        <button class="btn btn-primary btn-block btn-lg" type="submit" style="margin-top: 6px;">Create Super Admin Account</button>

        <div style="text-align: center; margin-top: 18px; padding-top: 14px; border-top: 1px solid var(--line, #e2e8f0); font-size: 13px; color: var(--muted, #64748b);">
            Already have an account?
            <a href="{{ route('login') }}" style="color: var(--primary, #941C3C); font-weight: 600; text-decoration: none;">Sign in</a>
        </div>
    </form>
</div>

<script src="{{ asset('js/icons.js') }}"></script>
</body>
</html>
