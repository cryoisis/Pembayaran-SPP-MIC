{{-- Student top bar (admins use the sidebar) --}}
<header class="border-b border-line bg-white">
    <div class="mx-auto flex h-16 max-w-5xl items-center justify-between px-4">
        <x-brand />
        <div class="flex items-center gap-4">
            <span class="hidden text-sm font-medium sm:block">{{ auth()->user()->name }}</span>
            <x-logout />
        </div>
    </div>
</header>
