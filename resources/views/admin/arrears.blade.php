@extends('layouts.app')
@section('title', 'Tunggakan')

@section('content')
<x-page-header title="Daftar Siswa dengan Tunggakan">
    <x-slot:sub>Siswa yang belum melunasi tagihan setelah jatuh tempo &middot; {{ $rows->count() }} siswa</x-slot:sub>

    <form method="GET" class="flex gap-2">
        <x-input type="search" name="q" :value="request('q')" placeholder="Cari NISN / nama" />
        <x-btn type="submit" variant="secondary">Cari</x-btn>
    </form>
</x-page-header>

<x-card flush>
    <x-table :head="['NISN', 'Nama Lengkap', 'Periode Tertunggak', ['Jumlah Kewajiban', 'right'], ['Sisa Pembayaran', 'right'], 'Jatuh Tempo Tertua', 'Status']">
        @forelse ($rows as $row)
            <tr>
                <td>{{ $row['student']->nisn }}</td>
                <td class="font-medium">{{ $row['student']->name }}</td>
                <td>{{ $row['periods'] }}</td>
                <td class="text-right"><x-money :value="$row['total']" /></td>
                <td class="text-right font-semibold text-bad-fg"><x-money :value="$row['remaining']" /></td>
                <td>
                    {{ $row['due']->format('d/m/Y') }}
                    <span class="block text-xs text-bad-fg">terlambat {{ (int) $row['due']->diffInDays(today()) }} hari</span>
                </td>
                <td><x-badge :status="\App\Models\PaymentSetting::STATUS_LATE" /></td>
            </tr>
        @empty
            <x-table.empty>Tidak ada siswa dengan tunggakan.</x-table.empty>
        @endforelse
    </x-table>
</x-card>
@endsection
