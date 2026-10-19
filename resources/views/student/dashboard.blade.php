@extends('layouts.app')
@section('title', 'Dashboard Siswa')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
    <div>
        <h1 class="h4 mb-0">Halo, {{ auth()->user()->name }}</h1>
        <small class="text-muted">NIS {{ auth()->user()->nis }} · {{ $period->name }}</small>
    </div>
    <div class="fs-5">Status: @include('layouts.partials.status-badge', ['status' => $summary['status']])</div>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header fw-semibold">Jadwal Pembayaran</div>
            <table class="table mb-0">
                <tr><th class="w-50">Tanggal mulai</th><td>{{ $period->start_date->translatedFormat('d F Y') }}</td></tr>
                <tr><th>Batas akhir</th><td>{{ $period->due_date->translatedFormat('d F Y') }}</td></tr>
                <tr>
                    <th>Sisa waktu</th>
                    <td>
                        @if ($daysLeft > 0) {{ $daysLeft }} hari lagi
                        @elseif ($daysLeft === 0) Hari ini batas akhir
                        @else <span class="text-danger">Terlewat {{ abs($daysLeft) }} hari</span>
                        @endif
                    </td>
                </tr>
            </table>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header fw-semibold">Pengumuman &amp; Instruksi Pembayaran</div>
            <div class="card-body">
                @if ($period->announcement_title || $period->announcement_body)
                    @if ($period->announcement_title)
                        <h2 class="h6">{{ $period->announcement_title }}</h2>
                    @endif
                    <div style="white-space: pre-line">{{ $period->announcement_body }}</div>
                @else
                    <span class="text-muted">Belum ada pengumuman.</span>
                @endif
            </div>
        </div>
    </div>
</div>

@include('layouts.partials.summary-cards')

<a href="{{ route('student.history') }}" class="btn btn-outline-primary">Lihat Riwayat Pembayaran</a>
@endsection
