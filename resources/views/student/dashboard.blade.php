@extends('layouts.app')
@section('title', 'Dashboard Siswa')

@section('content')
@php
    $user   = auth()->user();
    $status = $summary['status'];
    $next   = $summary['next'];

    $message = match (true) {
        $status === \App\Models\PaymentSetting::STATUS_PAID => 'Seluruh tagihan yang menjadi kewajiban sudah terpenuhi.',
        $status === \App\Models\PaymentSetting::STATUS_LATE => $summary['late']->count().' tagihan belum dibayar dan melewati batas waktu.',
        default => 'Masih ada tagihan yang belum dibayar.',
    };
@endphp

<x-page-header :title="'Halo, '.$user->name">
    <x-slot:sub>NISN {{ $user->nisn }}</x-slot:sub>
</x-page-header>

<div class="mb-6 grid gap-4 lg:grid-cols-3">
    {{-- Outstanding --}}
    <section class="flex flex-col gap-1 rounded-2xl bg-forest p-5 text-white shadow-sm">
        <div class="flex items-start justify-between gap-2">
            <span class="text-sm text-white/80">Tagihan yang harus dibayar</span>
            <x-badge :status="$status" />
        </div>
        <x-money :value="$summary['remaining']" class="my-1 text-3xl font-bold tracking-tight" />
        <p class="text-sm text-white/90">{{ $summary['unpaid']->count() }} periode belum dibayar.</p>
        <p class="text-sm text-white/90">{{ $message }}</p>
    </section>

    {{-- Current period --}}
    <x-card>
        <div class="mb-1 text-sm text-muted">Periode berjalan</div>
        @if ($currentBill)
            <div class="flex items-center justify-between gap-2">
                <div>
                    <div class="text-lg font-semibold">{{ $currentBill->label }}</div>
                    <x-money :value="$currentBill->amount" class="text-sm text-muted" />
                </div>
                <x-badge :status="$currentBill->statusFor($setting)" />
            </div>
        @else
            <p class="text-sm text-muted">Belum ada tagihan untuk bulan ini.</p>
        @endif
    </x-card>

    {{-- Next due --}}
    <x-card>
        <div class="mb-1 text-sm text-muted">Jadwal pembayaran berikutnya</div>
        @if ($next)
            <div class="flex items-center justify-between gap-2">
                <div>
                    <div class="text-lg font-semibold">{{ $next->label }}</div>
                    <x-money :value="$next->amount" class="text-sm text-muted" />
                </div>
                <div class="text-right text-sm">
                    <div class="text-muted">Jatuh tempo {{ $next->dueDate($setting)->format('d/m/Y') }}</div>
                    @if ($daysLeft > 0)
                        <div class="font-semibold">{{ $daysLeft }} hari lagi</div>
                    @elseif ($daysLeft === 0)
                        <div class="font-semibold text-warn-fg">Hari ini batas akhir</div>
                    @else
                        <div class="font-semibold text-bad-fg">Terlewat {{ abs($daysLeft) }} hari</div>
                    @endif
                </div>
            </div>
        @else
            <p class="text-sm text-muted">Tidak ada tagihan berikutnya.</p>
        @endif
    </x-card>
</div>

@if ($summary['unpaid']->isNotEmpty())
    <x-card flush title="Tagihan yang belum dibayar" class="mb-6">
        <x-table :head="['Periode', ['Nominal', 'right'], 'Jatuh tempo', 'Status']">
            @foreach ($summary['unpaid'] as $bill)
                <tr>
                    <td>{{ $bill->label }}</td>
                    <td class="text-right"><x-money :value="$bill->amount" /></td>
                    <td>{{ $bill->dueDate($setting)->format('d/m/Y') }}</td>
                    <td><x-badge :status="$bill->statusFor($setting)" /></td>
                </tr>
            @endforeach
        </x-table>
    </x-card>
@endif

<x-card flush title="Pengumuman & instruksi pembayaran">
    <ul class="divide-y divide-line">
        @forelse ($announcements as $a)
            <li class="px-5 py-4">
                <h3 class="text-sm font-semibold">{{ $a->title }}</h3>
                <p class="mt-1 whitespace-pre-line text-sm">{{ $a->body }}</p>
                <time class="mt-1 block text-xs text-muted">{{ $a->updated_at->format('d/m/Y') }}</time>
            </li>
        @empty
            <li class="px-5 py-6 text-sm text-muted">Belum ada pengumuman.</li>
        @endforelse
    </ul>
</x-card>
@endsection
