{{-- Pass a number via :value, or an amount in rupiah via :money. --}}
@props(['label', 'value' => null, 'money' => null, 'hint' => null, 'icon' => 'receipt', 'tone' => 'brand'])

<div class="flex items-center gap-4 rounded-2xl border border-line bg-white p-5 shadow-sm">
    <span class="tone-{{ $tone }} grid size-11 shrink-0 place-items-center rounded-xl">
        <x-icon :name="$icon" />
    </span>
    <div class="min-w-0">
        <div class="text-sm text-muted">{{ $label }}</div>
        <div class="truncate text-2xl font-semibold leading-tight tracking-tight">
            @if ($money !== null)<x-money :value="$money" />@else{{ $value }}@endif
        </div>
        @if ($hint)<div class="text-xs text-muted">{{ $hint }}</div>@endif
    </div>
</div>
