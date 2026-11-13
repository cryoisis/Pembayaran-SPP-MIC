@props(['label'])
<div class="grid grid-cols-5 gap-4 px-5 py-3 text-sm">
    <dt class="col-span-2 text-muted">{{ $label }}</dt>
    <dd class="col-span-3">{{ $slot }}</dd>
</div>
