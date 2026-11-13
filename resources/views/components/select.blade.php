@props(['label' => null, 'name' => null])

<label class="block">
    @if ($label)
        <span class="mb-1.5 block text-sm font-medium text-muted">{{ $label }}</span>
    @endif
    <select name="{{ $name }}" {{ $attributes->class('field') }}>{{ $slot }}</select>
    @if ($name && $errors->has($name))<span class="mt-1 block text-xs text-bad-fg">{{ $errors->first($name) }}</span>@endif
</label>
