<form method="POST" action="{{ route('logout') }}" {{ $attributes }}>
    @csrf
    <x-btn type="submit" variant="secondary" size="sm" class="w-full">Keluar</x-btn>
</form>
