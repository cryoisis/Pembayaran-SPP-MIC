<a href="{{ route(auth()->user()->isAdmin() ? 'admin.dashboard' : 'student.dashboard') }}" class="flex items-center gap-3">
    <span class="grid size-9 place-items-center rounded-xl bg-forest text-sm font-bold text-white">S</span>
    <span class="leading-tight">
        <span class="block text-sm font-semibold">Pembayaran SPP</span>
        <span class="block text-xs text-muted">{{ config('app.name') }}</span>
    </span>
</a>
