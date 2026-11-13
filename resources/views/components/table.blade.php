{{-- :head items are 'Label' or ['Label', 'right']. Cells are plain <td>; padding and row hover come from here. --}}
@props(['head' => [], 'compact' => false])

<div {{ $attributes->class('overflow-x-auto') }}>
    <table @class(['w-full', 'text-xs' => $compact, 'text-sm' => ! $compact])>
        <thead class="border-b border-line bg-forest-soft/60 text-xs font-semibold text-muted">
            <tr>
                @foreach ($head as $col)
                    @php [$label, $align] = (array) $col + [1 => 'left']; @endphp
                    <th @class(['whitespace-nowrap', 'px-3.5 py-2.5' => $compact, 'px-5 py-3' => ! $compact, 'text-left' => $align === 'left', 'text-right' => $align === 'right'])>{{ $label }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody @class([
            'divide-y divide-line [&>tr:hover]:bg-forest-soft/60',
            '[&_td]:px-3.5 [&_td]:py-2.5' => $compact,
            '[&_td]:px-5 [&_td]:py-3.5' => ! $compact,
        ])>
            {{ $slot }}
        </tbody>
    </table>
</div>
