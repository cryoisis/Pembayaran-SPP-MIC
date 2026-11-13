{{-- Title (prop or slot), optional `sub` (prop or slot); default slot = action buttons. --}}
@props(['title', 'sub' => null])

<div class="mb-6 flex flex-wrap items-end justify-between gap-3">
    <div>
        <h1 class="flex flex-wrap items-center gap-3 text-2xl font-semibold tracking-tight">{{ $title }}</h1>
        @if ($sub)<p class="mt-1 text-sm text-muted">{{ $sub }}</p>@endif
    </div>
    @if (trim($slot) !== '')<div class="flex flex-wrap items-center gap-2">{{ $slot }}</div>@endif
</div>
