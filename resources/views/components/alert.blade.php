@props(['tone' => 'ok', 'dismiss' => false])

<div {{ $attributes->class(["tone-$tone flex items-start gap-3 rounded-xl px-4 py-3 text-sm"]) }} role="alert"
     @if ($dismiss) x-data="{ show: true }" x-show="show" x-cloak @endif>
    <div class="flex-1">{{ $slot }}</div>
    @if ($dismiss)
        <button type="button" @click="show = false" class="opacity-60 hover:opacity-100" aria-label="Tutup"><x-icon name="x" size="size-4" /></button>
    @endif
</div>
