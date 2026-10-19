@extends('layouts.app')
@section('title', 'Riwayat Pembayaran')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">Riwayat Pembayaran</h1>
    <div>Status: @include('layouts.partials.status-badge', ['status' => $summary['status']])</div>
</div>

@include('layouts.partials.summary-cards')

@forelse ($transactions as $trx)
    <div class="card timeline-card shadow-sm mb-3">
        <div class="card-body">
            <div class="d-flex justify-content-between flex-wrap">
                <div>
                    <div class="text-muted small">{{ $trx->paid_at->translatedFormat('d F Y') }}</div>
                    <div class="fs-5 fw-semibold">Rp{{ number_format($trx->amount, 0, ',', '.') }}</div>
                </div>
                <div class="text-end small text-muted">
                    <div>Akumulasi: Rp{{ number_format($trx->cumulative, 0, ',', '.') }}</div>
                    <div>Sisa setelahnya: Rp{{ number_format($trx->remaining_after, 0, ',', '.') }}</div>
                </div>
            </div>
            @if ($trx->note)
                <div class="mt-2">{{ $trx->note }}</div>
            @endif
        </div>
    </div>
@empty
    <div class="alert alert-info">Riwayat pembayaran masih kosong. Belum ada transaksi yang dikonfirmasi admin.</div>
@endforelse

<a href="{{ route('student.dashboard') }}" class="btn btn-outline-secondary mt-2">&larr; Kembali ke Dashboard</a>
@endsection
