@extends('layouts.app')
@section('title', 'Transaksi Pembayaran')

@section('content')
<x-page-header title="Transaksi Pembayaran" />

<x-card class="mb-5">
    <form method="GET" class="space-y-4">
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            <x-input label="Cari NISN / nama" name="q" type="search" :value="request('q')" />
            <x-input label="Periode dibayar" name="period" type="month" :value="request('period')" />
            <x-input label="Tanggal dari" name="from" type="date" :value="request('from')" />
            <x-input label="Tanggal sampai" name="to" type="date" :value="request('to')" />
            <x-select label="Metode" name="method">
                <option value="">Semua</option>
                @foreach (\App\Models\PaymentTransaction::METHODS as $value => $label)
                    <option value="{{ $value }}" @selected(request('method') === $value)>{{ $label }}</option>
                @endforeach
            </x-select>
            <x-select label="Status" name="status">
                <option value="">Semua</option>
                @foreach (\App\Models\PaymentTransaction::STATUS_LABELS as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </x-select>
        </div>
        <div class="flex justify-end gap-2">
            <x-btn variant="secondary" :href="route('admin.transactions.index')">Reset</x-btn>
            <x-btn type="submit">Terapkan</x-btn>
        </div>
    </form>
</x-card>

<x-card flush>
    <x-table :head="['Tanggal', 'NISN', 'Nama', 'Periode', 'Metode', ['Nominal', 'right'], 'Status', '']">
        @forelse ($transactions as $trx)
            <tr>
                <td>{{ $trx->paid_at->format('d/m/Y') }}</td>
                <td>{{ $trx->student->nisn }}</td>
                <td class="font-medium">{{ $trx->student->name }}</td>
                <td>{{ $trx->period_labels }}</td>
                <td>{{ $trx->method_label }}</td>
                <td class="text-right"><x-money :value="$trx->amount" /></td>
                <td><x-badge :trx="$trx" /></td>
                <td class="text-right"><x-btn size="sm" variant="soft" :href="route('admin.transactions.show', $trx)">Rincian</x-btn></td>
            </tr>
        @empty
            <x-table.empty>Belum ada transaksi.</x-table.empty>
        @endforelse
    </x-table>
</x-card>

<div class="mt-4">{{ $transactions->links() }}</div>
@endsection
