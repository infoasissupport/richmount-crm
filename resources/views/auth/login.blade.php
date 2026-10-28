<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign In · Rich Mount ERP</title>
    <link rel="icon" href="{{ asset('images/rich-mount-logo.png') }}" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body>
<div class="login" id="login">
    <form class="login-card" id="loginForm" method="POST" action="{{ route('login') }}">
        @csrf
        <div class="login-brand">
            <img src="{{ asset('images/rich-mount-logo.png') }}" alt="Rich Mount Logo" width="58" height="44">
            <div>
                <strong>RICH MOUNT</strong>
                <small>ERP SYSTEM</small>
            </div>
        </div>

        <h2>Sign in</h2>
        <p>Enter your credentials to access your Rich Mount ERP account.</p>

        @if($errors->any())
            <div class="toast error show" style="position: static; margin-bottom: 8px;">
                <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-alert"/></svg>
                <span>{{ $errors->first() }}</span>
            </div>
        @endif

        <label class="field">
            <span class="field-label">Email</span>
            <input type="email" name="email" id="loginEmail" value="{{ old('email') }}" placeholder="admin@richmount.ae" required autocomplete="email" autofocus>
        </label>

        <label class="field">
            <span class="field-label">Password</span>
            <input type="password" name="password" id="loginPassword" value="" placeholder="Enter your password" required autocomplete="current-password">
        </label>

        <div style="display: flex; justify-content: space-between; align-items: center; font-size: 13px;">
            <label style="display: flex; align-items: center; gap: 6px; cursor: pointer;">
                <input type="checkbox" name="remember" value="1" checked>
                <span>Remember me</span>
            </label>
            <a href="#" style="color: var(--primary); text-decoration: none;" onclick="alert('Please contact your administrator to reset credentials.')">Forgot password?</a>
        </div>

        <button class="btn btn-primary btn-block btn-lg" type="submit">Sign in</button>

        <div style="text-align: center; margin-top: 18px; padding-top: 14px; border-top: 1px solid var(--line, #e2e8f0); font-size: 13px; color: var(--muted, #64748b);">
            Don't have an account?
            <a href="{{ route('register') }}" style="color: var(--primary, #941C3C); font-weight: 600; text-decoration: none;">Sign up as Super Admin</a>
        </div>
    </form>
</div>

<script src="{{ asset('js/icons.js') }}"></script>
</body>
</html>
