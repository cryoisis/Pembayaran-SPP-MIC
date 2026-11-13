{{-- <x-badge :status="$status" /> | <x-badge :trx="$transaction" /> | <x-badge tone="ok">Teks</x-badge> --}}
@props(['status' => null, 'trx' => null, 'tone' => null, 'dot' => true])

@php
    if ($status !== null) {
        $tone  = \App\Models\PaymentSetting::statusColor($status);
        $label = $status;
    } elseif ($trx) {
        $tone  = $trx->status_color;
        $label = $trx->status_label;
    }
    $tone = ['success' => 'ok', 'warning' => 'warn', 'danger' => 'bad', 'secondary' => 'neutral'][$tone] ?? $tone;
@endphp

<span {{ $attributes->class(['inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold', "tone-$tone" => $tone]) }}>
    @if ($dot)<span class="size-1.5 rounded-full bg-current"></span>@endif
    {{ $label ?? $slot }}
</span>
