@props(['name', 'size' => 'size-5'])

@php
    $paths = [
        'home'    => 'M3 11l9-8 9 8M5 10v10h5v-6h4v6h5V10',
        'users'   => 'M16 8a4 4 0 11-8 0 4 4 0 018 0zM4 21a8 8 0 0116 0',
        'tag'     => 'M3 12V4h8l10 10-8 8L3 12zM7.5 8.5h.01',
        'wallet'  => 'M3 7h16a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V7zm0 0l2-3h12M16 14h2',
        'receipt' => 'M6 3h12v18l-3-2-3 2-3-2-3 2V3zM9 8h6M9 12h6',
        'alert'   => 'M12 9v4M12 17h.01M10.3 3.9L2.4 18a2 2 0 001.7 3h15.8a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z',
        'chart'   => 'M4 20V10M10 20V4M16 20v-7M22 20H2',
        'menu'    => 'M4 6h16M4 12h16M4 18h16',
        'x'       => 'M6 18L18 6M6 6l12 12',
        'search'  => 'M21 21l-4.3-4.3M17 11a6 6 0 11-12 0 6 6 0 0112 0z',
        'check'   => 'M5 13l4 4L19 7',
    ];
@endphp

<svg {{ $attributes->class([$size, 'shrink-0']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $paths[$name] }}"/></svg>
