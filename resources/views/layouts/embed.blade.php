{{--
    Minimal layout for pages shown inside a popup frame (e.g. the quotation builder opened from the Quotations
    list with ?embed=1): same styles, icons and scripts as layouts.app, without the sidebar and top bar.
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Rich Mount ERP') · Rich Mount ERP</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <style>
        /* no top bar here, so the page fills the frame */
        :root { --topbar-h: 0px; }
        body.embedded { background: var(--bg); }
        body.embedded .content { padding: 16px 20px 0; }
        body.embedded .page-foot { display: none; }
    </style>
    @stack('styles')
</head>
<body class="embedded" data-page="@yield('page_id', 'dashboard')">
    <main class="content" id="content">
        @yield('content')
    </main>

    @include('partials.toasts')

    <script src="{{ asset('js/icons.js') }}"></script>
    <script src="{{ asset('js/file-drop.js') }}"></script>
    <script>
        window.csrfToken = '{{ csrf_token() }}';
    </script>
    @stack('scripts')
</body>
</html>
