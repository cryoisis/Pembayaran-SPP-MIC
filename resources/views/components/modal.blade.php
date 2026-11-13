{{--
  Lives inside an Alpine scope that owns the `show` flag. The root element is the <form>,
  so any extra attributes (:action, x-bind:action, @submit...) land on it.
  Slots: default (fields), footer (extra buttons on the left).
--}}
@props(['show', 'title' => null, 'titleJs' => null, 'method' => null, 'submit' => 'Simpan', 'cancel' => 'Batal', 'variant' => 'primary', 'size' => 'md'])

@php $width = ['md' => 'max-w-md', 'xl' => 'max-w-xl', '2xl' => 'max-w-2xl'][$size]; @endphp

<div x-show="{{ $show }}" x-cloak x-transition.opacity role="dialog" aria-modal="true"
     @keydown.escape.window="{{ $show }} = false"
     class="fixed inset-0 z-50 grid place-items-center p-4">
    <div class="absolute inset-0 bg-ink/50 backdrop-blur-sm" @click="{{ $show }} = false"></div>

    <form method="POST" {{ $attributes->class("relative flex max-h-[90vh] w-full flex-col overflow-hidden rounded-2xl bg-white shadow-2xl $width") }}>
        @csrf
        @if ($method)@method($method)@endif

        <header class="flex items-center justify-between border-b border-line px-6 py-4">
            <h3 class="font-semibold" @if ($titleJs) x-text="{{ $titleJs }}" @endif>{{ $title }}</h3>
            <button type="button" @click="{{ $show }} = false" class="rounded-lg p-1 text-muted hover:bg-forest-soft hover:text-ink" aria-label="Tutup">
                <x-icon name="x" />
            </button>
        </header>

        <div class="space-y-4 overflow-y-auto px-6 py-5">{{ $slot }}</div>

        <footer class="flex items-center gap-2 border-t border-line px-6 py-4">
            {{ $footer ?? '' }}
            <div class="ml-auto flex gap-2">
                <x-btn variant="secondary" @click="{{ $show }} = false">{{ $cancel }}</x-btn>
                <x-btn type="submit" :variant="$variant">{{ $submit }}</x-btn>
            </div>
        </footer>
    </form>
</div>
