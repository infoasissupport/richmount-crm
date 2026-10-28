<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Rich Mount ERP') · Rich Mount ERP</title>
    <link rel="icon" href="{{ asset('images/rich-mount-logo.png') }}" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    @stack('styles')
</head>
<body data-page="@yield('page_id', 'dashboard')">
<div class="app" id="app">
    {{-- Reusable Sidebar --}}
    @include('partials.sidebar')

    <div class="scrim" id="scrim" onclick="document.body.classList.remove('nav-open')"></div>

    <div class="main">
        {{-- Reusable Topbar --}}
        @include('partials.topbar')

        <main class="content" id="content">
            @yield('content')
            @include('partials.footer')
        </main>
    </div>
</div>

{{-- Global Toast Notifications --}}
@include('partials.toasts')

{{-- Standard Icon Sprite & Global Scripts --}}
<script src="{{ asset('js/icons.js') }}"></script>
<script src="{{ asset('js/sidebar.js') }}"></script>
<script src="{{ asset('js/file-drop.js') }}"></script>
<script>
    // Setup AJAX CSRF
    window.csrfToken = '{{ csrf_token() }}';
    
    // Toggle mobile sidebar
    document.getElementById('menuToggle')?.addEventListener('click', function () {
        document.body.classList.toggle('nav-open');
    });

    // Global keyboard shortcut Ctrl + K / Cmd + K
    document.addEventListener('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            document.getElementById('globalSearch')?.focus();
        }
    });
</script>
@stack('scripts')
</body>
</html>
