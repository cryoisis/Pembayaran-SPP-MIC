@extends('layouts.app')
@section('title', 'Laporan Pembayaran')

@section('content')
<x-page-header title="Laporan Pembayaran SPP">
    <x-slot:sub>
        Periode laporan: <strong>{{ $from->format('d/m/Y') }} &ndash; {{ $to->format('d/m/Y') }}</strong>
        <span class="text-xs">(tagihan bulan-bulan dalam rentang ini; transaksi menurut tanggal transaksi; hanya pembayaran terkonfirmasi yang berlaku)</span>
    </x-slot:sub>
</x-page-header>

<x-card class="mb-5">
    <form method="GET" class="space-y-4">
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            <x-input label="Bulan" name="month" type="month" :value="$filters['month'] ?? ''" />
            <x-input label="Tanggal dari" name="from" type="date" :value="$filters['from'] ?? ''" />
            <x-input label="Tanggal sampai" name="to" type="date" :value="$filters['to'] ?? ''" />
            <x-select label="Siswa" name="student_id">
                <option value="">Semua siswa</option>
                @foreach ($students as $s)
                    <option value="{{ $s->id }}" @selected(($filters['student_id'] ?? '') == $s->id)>{{ $s->nisn }} &ndash; {{ $s->name }}</option>
                @endforeach
            </x-select>
            <x-input label="Cari NISN / nama" name="q" type="search" :value="$filters['q'] ?? ''" />
            <x-select label="Status pembayaran" name="status">
                <option value="">Semua</option>
                @foreach ([\App\Models\PaymentSetting::STATUS_PAID, \App\Models\PaymentSetting::STATUS_UNPAID, \App\Models\PaymentSetting::STATUS_LATE] as $st)
                    <option value="{{ $st }}" @selected(($filters['status'] ?? '') === $st)>{{ $st }}</option>
                @endforeach
            </x-select>
        </div>
        <p class="text-xs text-muted">Isi <em>Bulan</em> atau rentang tanggal. Tanpa filter waktu, laporan menampilkan seluruh tahun ajaran berjalan.</p>
        <div class="flex justify-end gap-2">
            <x-btn variant="secondary" :href="route('admin.reports.index')">Reset</x-btn>
            <x-btn type="submit">Tampilkan</x-btn>
        </div>
    </form>
</x-card>

<section class="mb-6 grid gap-4 sm:grid-cols-3" aria-label="Ringkasan laporan">
    <x-stat label="Total tagihan" icon="receipt" :money="$summary['total']" />
    <x-stat label="Total pembayaran (terkonfirmasi)" icon="wallet" tone="ok" :money="$summary['paid']" />
    <x-stat label="Sisa kewajiban" icon="alert" tone="bad" :money="$summary['remaining']" />
</section>

<x-card flush title="Rekap Tagihan per Siswa" class="mb-6">
    <x-table :head="['NISN', 'Nama Lengkap', 'Periode', ['Tagihan', 'right'], ['Dibayar', 'right'], ['Sisa', 'right'], 'Status']">
        @forelse ($rows as $row)
            <tr>
                <td>{{ $row['student']->nisn }}</td>
                <td class="font-medium">{{ $row['student']->name }}</td>
                <td class="text-xs">{{ $row['periods'] }}</td>
                <td class="text-right"><x-money :value="$row['total']" /></td>
                <td class="text-right"><x-money :value="$row['paid']" /></td>
                <td class="text-right"><x-money :value="$row['remaining']" /></td>
                <td><x-badge :status="$row['status']" /></td>
            </tr>
        @empty
            <x-table.empty>Tidak ada data pada filter ini.</x-table.empty>
        @endforelse
    </x-table>
</x-card>

<x-card flush title="Transaksi Pembayaran">
    <x-slot:actions>
        <span class="text-xs text-muted">{{ $transactions->count() }} transaksi &middot; <x-money :value="$transactions->sum('amount')" /></span>
    </x-slot:actions>

    <x-table :head="['Tanggal', 'NISN', 'Nama', 'Periode Dibayar', 'Metode', ['Nominal', 'right']]">
        @forelse ($transactions as $trx)
            <tr>
                <td>{{ $trx->paid_at->format('d/m/Y') }}</td>
                <td>{{ $trx->student->nisn }}</td>
                <td class="font-medium">{{ $trx->student->name }}</td>
                <td class="text-xs">{{ $trx->period_labels }}</td>
                <td>{{ $trx->method_label }}</td>
                <td class="text-right"><x-money :value="$trx->amount" /></td>
            </tr>
        @empty
            <x-table.empty>Tidak ada transaksi terkonfirmasi pada rentang ini.</x-table.empty>
        @endforelse
    </x-table>
</x-card>
@endsection
