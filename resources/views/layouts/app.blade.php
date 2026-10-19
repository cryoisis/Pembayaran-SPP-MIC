<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Pencatatan Pembayaran') — {{ config('app.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .student-row { cursor: pointer; }
        .student-row:hover { background: rgba(13,110,253,.06); }
        .timeline-card { border-left: 4px solid var(--bs-success); }
    </style>
    @stack('styles')
</head>
<body class="bg-light">
    @auth
        @include('layouts.partials.navbar')
    @endauth

    <main class="container py-4">
        @include('layouts.partials.alerts')
        @yield('content')
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
