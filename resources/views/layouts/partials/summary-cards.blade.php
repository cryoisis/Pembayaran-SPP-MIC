{{-- Needs: $summary (obligation, paid, remaining) --}}
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card h-100"><div class="card-body">
            <div class="text-muted small">Total Kewajiban</div>
            <div class="fs-4 fw-semibold">Rp{{ number_format($summary['total'], 0, ',', '.') }}</div>
        </div></div>
    </div>
    <div class="col-md-4">
        <div class="card h-100"><div class="card-body">
            <div class="text-muted small">Total Dibayar</div>
            <div class="fs-4 fw-semibold text-success">Rp{{ number_format($summary['paid'], 0, ',', '.') }}</div>
        </div></div>
    </div>
    <div class="col-md-4">
        <div class="card h-100"><div class="card-body">
            <div class="text-muted small">Sisa Pembayaran</div>
            <div class="fs-4 fw-semibold text-danger">Rp{{ number_format($summary['remaining'], 0, ',', '.') }}</div>
        </div></div>
    </div>
</div>
