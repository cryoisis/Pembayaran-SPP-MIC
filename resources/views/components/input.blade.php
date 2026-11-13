{{-- Text, number, date, search, password... or type="textarea". Old input is filled automatically. --}}
@props(['label' => null, 'name' => null, 'type' => 'text', 'hint' => null])

@php
    $invalid = $name && $errors->has($name);
    $old     = $name && $type !== 'password' && ! $attributes->has('x-model') ? old($name) : null;
    $class   = $invalid ? 'field !border-bad-fg' : 'field';
@endphp

<label class="block">
    @if ($label)
        <span class="mb-1.5 block text-sm font-medium text-muted">{{ $label }}</span>
    @endif

    @if ($type === 'textarea')
        <textarea name="{{ $name }}" {{ $attributes->class($class) }}>{{ $old }}</textarea>
    @else
        <input type="{{ $type }}" name="{{ $name }}" {{ $attributes->merge(['value' => $old])->class($class) }}>
    @endif

    @if ($hint)<span class="mt-1 block text-xs text-muted">{{ $hint }}</span>@endif
    @if ($invalid)<span class="mt-1 block text-xs text-bad-fg">{{ $errors->first($name) }}</span>@endif
</label>
