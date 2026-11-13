@php
    // [route pattern, route name, label, icon]
    $nav = [
        ['admin.dashboard',      'admin.dashboard',          'Dashboard',  'home'],
        ['admin.students.*',     'admin.students.index',     'Data Siswa', 'users'],
        ['admin.fee-rates.*',    'admin.fee-rates.index',    'Tarif SPP',  'tag'],
        ['admin.payments.*',     'admin.payments.index',     'Pembayaran', 'wallet'],
        ['admin.transactions.*', 'admin.transactions.index', 'Transaksi',  'receipt'],
        ['admin.arrears.*',      'admin.arrears.index',      'Tunggakan',  'alert'],
        ['admin.reports.*',      'admin.reports.index',      'Laporan',    'chart'],
    ];
@endphp

{{-- One sidebar: static on desktop, slide-in drawer on mobile --}}
<div x-show="sidebar" x-cloak x-transition.opacity @click="sidebar = false" class="fixed inset-0 z-40 bg-ink/40 lg:hidden"></div>

<aside :class="sidebar ? 'translate-x-0' : '-translate-x-full'"
       class="fixed inset-y-0 left-0 z-50 flex w-64 flex-col gap-6 border-r border-line bg-white p-5 transition-transform duration-200 lg:sticky lg:top-0 lg:h-screen lg:shrink-0 lg:translate-x-0">
    <div class="flex items-center justify-between">
        <x-brand />
        <button type="button" @click="sidebar = false" class="rounded-lg p-1.5 hover:bg-forest-soft lg:hidden" aria-label="Tutup menu">
            <x-icon name="x" />
        </button>
    </div>

    <nav class="flex flex-col gap-1">
        @foreach ($nav as [$pattern, $route, $label, $icon])
            @php $active = request()->routeIs($pattern); @endphp
            <a href="{{ route($route) }}" @click="sidebar = false" @if ($active) aria-current="page" @endif
               @class([
                   'flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition',
                   'bg-forest-tint text-forest' => $active,
                   'text-muted hover:bg-forest-soft hover:text-ink' => ! $active,
               ])>
                <x-icon :name="$icon" size="size-[18px]" />
                {{ $label }}
            </a>
        @endforeach
    </nav>

    <div class="mt-auto space-y-3 border-t border-line pt-4">
        <div class="truncate px-1 text-sm font-medium">{{ auth()->user()->name }}</div>
        <x-logout />
    </div>
</aside>
