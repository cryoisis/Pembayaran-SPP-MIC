{{-- Slots: title (or prop), actions, footer. `flush` removes body padding (for tables/lists). --}}
@props(['title' => null, 'flush' => false])

<section {{ $attributes->class('flex flex-col overflow-hidden rounded-2xl border border-line bg-white shadow-sm') }}>
    @if ($title || isset($actions))
        <header class="flex items-center justify-between gap-3 border-b border-line px-5 py-3.5">
            <h2 class="text-sm font-semibold">{{ $title }}</h2>
            @isset($actions)<div class="flex items-center gap-2">{{ $actions }}</div>@endisset
        </header>
    @endif

    <div @class(['flex-1', 'p-5' => ! $flush])>{{ $slot }}</div>

    @isset($footer)
        <footer class="border-t border-line bg-forest-soft/50 px-5 py-3 text-xs text-muted">{{ $footer }}</footer>
    @endisset
</section>
