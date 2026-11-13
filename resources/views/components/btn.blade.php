@props(['variant' => 'primary', 'size' => 'md', 'href' => null, 'type' => 'button'])

@php
    $variants = [
        'primary'     => 'bg-forest text-white hover:bg-forest-hover',
        'secondary'   => 'border border-line bg-white hover:bg-forest-soft',
        'soft'        => 'bg-forest-tint text-forest hover:bg-forest hover:text-white',
        'danger'      => 'bg-bad-bg text-bad-fg hover:bg-bad-fg hover:text-white',
        'destructive' => 'bg-bad-fg text-white hover:opacity-90',
        'ok'          => 'bg-ok-fg text-white hover:opacity-90',
    ];
    $sizes = ['sm' => 'px-3 py-1.5', 'md' => 'px-4 py-2'];
    $classes = "inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-xl text-sm font-medium transition disabled:cursor-not-allowed disabled:opacity-50 {$sizes[$size]} {$variants[$variant]}";
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->class($classes) }}>{{ $slot }}</button>
@endif
