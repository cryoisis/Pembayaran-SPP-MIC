@extends('layouts.app')
@section('title', 'Masuk')

@section('content')
<div class="grid min-h-screen place-items-center px-4 py-8">
    <div class="w-full max-w-sm">
        <div class="mb-6 text-center">
            <span class="mb-3 inline-grid size-12 place-items-center rounded-xl bg-forest text-lg font-bold text-white">MIC</span>
            <h1 class="text-lg font-semibold">Pembayaran SPP Siswa</h1>
        </div>

        @if (session('success'))
            <x-alert class="mb-4">{{ session('success') }}</x-alert>
        @endif

        <x-card class="shadow-lg shadow-forest/5">
            <form method="POST" action="{{ route('login.attempt') }}" class="space-y-4" novalidate>
                @csrf
                <x-input label="NISN" name="nisn" autofocus required />
                <x-input label="Kata sandi" name="password" type="password" required />
                <x-btn type="submit" class="w-full">Masuk</x-btn>
            </form>
        </x-card>
    </div>
</div>
@endsection
