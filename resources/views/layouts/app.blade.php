<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Pencatatan Pembayaran') — {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'system-ui', '-apple-system', 'Segoe UI', 'sans-serif'] },
                    colors: {
                        forest: { DEFAULT: '#1F5C43', hover: '#174A35', tint: '#EAF3ED', soft: '#F4F6F3' },
                        line: '#E3E8E2',
                        ink: '#1D2B24',
                        muted: '#66746B',
                        // status tones: bg / fg pairs
                        ok:      { bg: '#E1F3E7', fg: '#1B6B3A' },
                        warn:    { bg: '#FFF0C7', fg: '#805300' },
                        bad:     { bg: '#FBE3E0', fg: '#A3281C' },
                        info:    { bg: '#E1ECF7', fg: '#1D4F82' },
                        neutral: { bg: '#E8ECE7', fg: '#46544B' },
                    },
                },
            },
        }
    </script>
    <style>[x-cloak] { display: none !important; }</style>
    <style type="text/tailwindcss">
        @layer base {
            body { font-feature-settings: 'cv11', 'ss01'; }
        }
        @layer components {
            .field { @apply w-full rounded-xl border border-line bg-white px-3.5 py-2.5 text-sm transition focus:border-forest focus:outline-none focus:ring-2 focus:ring-forest/20 disabled:bg-forest-soft disabled:text-muted; }
            .tone-brand   { @apply bg-forest-tint text-forest; }
            .tone-ok      { @apply bg-ok-bg text-ok-fg; }
            .tone-warn    { @apply bg-warn-bg text-warn-fg; }
            .tone-bad     { @apply bg-bad-bg text-bad-fg; }
            .tone-info    { @apply bg-info-bg text-info-fg; }
            .tone-neutral { @apply bg-neutral-bg text-neutral-fg; }
        }
    </style>
    @stack('styles')
</head>
<body class="min-h-full bg-forest-soft font-sans text-ink tabular-nums antialiased" x-data="{ sidebar: false }">
    @auth
        @if (auth()->user()->isAdmin())
            <div class="lg:flex">
                @include('layouts.partials.admin-sidebar')

                <div class="min-w-0 flex-1">
                    <header class="sticky top-0 z-30 flex items-center gap-3 border-b border-line bg-white px-4 py-3 lg:hidden">
                        <button type="button" @click="sidebar = true" class="rounded-lg p-1.5 hover:bg-forest-soft" aria-label="Buka menu">
                            <x-icon name="menu" />
                        </button>
                        <span class="text-sm font-semibold">Pembayaran SPP</span>
                    </header>

                    <main class="mx-auto w-full max-w-6xl px-4 py-6 lg:px-8 lg:py-8">
                        @include('layouts.partials.alerts')
                        @yield('content')
                    </main>
                </div>
            </div>
        @else
            @include('layouts.partials.navbar')
            <main class="mx-auto max-w-5xl px-4 py-6 lg:py-8">
                @include('layouts.partials.alerts')
                @yield('content')
            </main>
        @endif
    @else
        @yield('content')
    @endauth

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @stack('scripts')
</body>
</html>
