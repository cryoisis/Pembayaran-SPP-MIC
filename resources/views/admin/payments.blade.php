@extends('layouts.app')
@section('title', 'Pembayaran Siswa')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h4 mb-0">Daftar Siswa &amp; Pencatatan Pembayaran</h1>
        <small class="text-muted">{{ $period->name }} · batas akhir {{ $period->due_date->translatedFormat('d F Y') }} · standar Rp{{ number_format($period->standard_fee, 0, ',', '.') }}</small>
    </div>
    <form method="GET" class="d-flex gap-2">
        <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Cari NIS / nama">
        <button class="btn btn-outline-secondary">Cari</button>
    </form>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr><th>NIS</th><th>Nama Lengkap</th><th>Status</th>
                    <th class="text-end">Total Kewajiban</th><th class="text-end">Total Dibayar</th><th class="text-end">Sisa</th></tr>
            </thead>
            <tbody>
            @forelse ($students as $student)
                @php $sum = $student->paymentSummary($period); @endphp
                <tr class="student-row" data-id="{{ $student->id }}" data-show-url="{{ route('admin.payments.show', $student) }}">
                    <td>{{ $student->nis }}</td>
                    <td>{{ $student->name }}</td>
                    <td>@include('layouts.partials.status-badge', ['status' => $sum['status']])</td>
                    <td class="text-end">Rp{{ number_format($sum['total'], 0, ',', '.') }}</td>
                    <td class="text-end">Rp{{ number_format($sum['paid'], 0, ',', '.') }}</td>
                    <td class="text-end">Rp{{ number_format($sum['remaining'], 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted py-4">Tidak ada siswa ditemukan.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $students->links('pagination::bootstrap-5') }}</div>

{{-- Payment management modal (filled via JSON) --}}
<div class="modal fade" id="paymentModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="mName"></h5>
                    <small class="text-muted" id="mNis"></small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-2 mb-3 text-center">
                    <div class="col-6 col-md-3"><div class="border rounded p-2"><div class="small text-muted">Standar</div><div id="mStandard" class="fw-semibold"></div></div></div>
                    <div class="col-6 col-md-3"><div class="border rounded p-2"><div class="small text-muted">Total kewajiban</div><div id="mTotal" class="fw-semibold"></div></div></div>
                    <div class="col-6 col-md-3"><div class="border rounded p-2"><div class="small text-muted">Total dibayar</div><div id="mPaid" class="fw-semibold text-success"></div></div></div>
                    <div class="col-6 col-md-3"><div class="border rounded p-2"><div class="small text-muted">Sisa</div><div id="mRemaining" class="fw-semibold text-danger"></div></div></div>
                </div>
                <div class="mb-3">Status: <span id="mStatus" class="badge"></span></div>

                <h6>Penyesuaian Biaya</h6>
                <ul class="list-group mb-3" id="mAdjustments"></ul>

                {{-- Add adjustment --}}
                <form method="POST" id="adjForm" class="border rounded p-3 mb-4 bg-light">
                    @csrf
                    <input type="hidden" name="_student" class="studentField">
                    <div class="fw-semibold mb-2">Tambah penyesuaian</div>
                    <div class="row g-2">
                        <div class="col-md-3">
                            <select name="adjustment_type" class="form-select" required>
                                <option value="add">Biaya tambahan (+)</option>
                                <option value="reduce">Pengurangan (−)</option>
                            </select>
                        </div>
                        <div class="col-md-3"><input type="number" min="1" name="adjustment_amount" class="form-control" placeholder="Nominal (Rp)" required></div>
                        <div class="col-md-6"><input type="text" name="reason" maxlength="255" class="form-control" placeholder="Alasan perubahan (wajib)" required></div>
                    </div>
                    <div class="mt-2 text-end">
                        <button type="reset" class="btn btn-sm btn-secondary">Batal</button>
                        <button type="submit" class="btn btn-sm btn-primary">Simpan</button>
                    </div>
                </form>

                {{-- Record payment (2 steps: fill -> summary -> confirm) --}}
                <form method="POST" id="payForm" class="border rounded p-3 mb-4 bg-light">
                    @csrf
                    <input type="hidden" name="_student" class="studentField">
                    <div class="fw-semibold mb-2">Catat pembayaran (penuh / cicilan)</div>
                    <div class="row g-2">
                        <div class="col-md-4"><input type="number" min="1" name="amount" id="payAmount" class="form-control" placeholder="Nominal (Rp)" required></div>
                        <div class="col-md-4"><input type="date" name="paid_at" id="payDate" class="form-control" max="{{ now()->toDateString() }}" required></div>
                        <div class="col-md-4"><input type="text" name="note" id="payNote" maxlength="255" class="form-control" placeholder="Keterangan (opsional)"></div>
                    </div>
                    <div id="payActions" class="mt-2 text-end">
                        <button type="reset" class="btn btn-sm btn-secondary">Batal</button>
                        <button type="button" class="btn btn-sm btn-primary" id="payPreview">Lanjut</button>
                    </div>
                    <div id="confirmBox" class="alert alert-warning mt-3 mb-0 d-none">
                        <div id="confirmText"></div>
                        <div class="mt-2 text-end">
                            <button type="button" class="btn btn-sm btn-secondary" id="confirmBack">Kembali</button>
                            <button type="submit" class="btn btn-sm btn-success">Konfirmasi &amp; Simpan</button>
                        </div>
                    </div>
                </form>

                <h6>Riwayat Transaksi Terkonfirmasi</h6>
                <ul class="list-group" id="mTransactions"></ul>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const fmt = n => 'Rp' + Number(n).toLocaleString('id-ID');
    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const statusColors = {
        'Sudah bayar': 'success', 'Mencicil': 'info', 'Belum bayar': 'secondary',
        'Terlambat bayar': 'danger', 'Terlambat mencicil': 'warning'
    };
    const adjUrl = @json(route('admin.payments.adjustments.store', ['student' => '__ID__']));
    const trxUrl = @json(route('admin.payments.transactions.store', ['student' => '__ID__']));
    const today  = @json(now()->toDateString());

    const modalEl = document.getElementById('paymentModal');
    const modal = new bootstrap.Modal(modalEl);
    const payForm = document.getElementById('payForm');
    const confirmBox = document.getElementById('confirmBox');
    const payActions = document.getElementById('payActions');
    let current = null;

    function resetConfirm() {
        confirmBox.classList.add('d-none');
        payActions.classList.remove('d-none');
    }

    async function openStudent(row) {
        const res = await fetch(row.dataset.showUrl, {headers: {'Accept': 'application/json'}});
        if (!res.ok) { alert('Gagal memuat data siswa.'); return; }
        const d = current = await res.json();

        document.getElementById('mName').textContent = d.student.name;
        document.getElementById('mNis').textContent = 'NIS ' + d.student.nis + ' · ' + d.period.name + ' (batas ' + d.period.due_date + ')';
        document.getElementById('mStandard').textContent = fmt(d.summary.standard);
        document.getElementById('mTotal').textContent = fmt(d.summary.total);
        document.getElementById('mPaid').textContent = fmt(d.summary.paid);
        document.getElementById('mRemaining').textContent = fmt(d.summary.remaining);

        const badge = document.getElementById('mStatus');
        badge.textContent = d.summary.status;
        badge.className = 'badge text-bg-' + (statusColors[d.summary.status] || 'secondary');

        document.getElementById('mAdjustments').innerHTML = d.adjustments.length
            ? d.adjustments.map(a => `<li class="list-group-item d-flex justify-content-between">
                <span>${esc(a.reason)} <small class="text-muted">(${esc(a.date)})</small></span>
                <strong class="${a.amount < 0 ? 'text-success' : 'text-danger'}">${a.amount < 0 ? '−' : '+'}${fmt(Math.abs(a.amount))}</strong></li>`).join('')
            : '<li class="list-group-item text-muted">Belum ada penyesuaian.</li>';

        document.getElementById('mTransactions').innerHTML = d.transactions.length
            ? d.transactions.map(t => `<li class="list-group-item d-flex justify-content-between">
                <span>${esc(t.date)}${t.note ? ' — ' + esc(t.note) : ''}</span><strong>${fmt(t.amount)}</strong></li>`).join('')
            : '<li class="list-group-item text-muted">Belum ada transaksi.</li>';

        document.querySelectorAll('.studentField').forEach(i => i.value = d.student.id);
        document.getElementById('adjForm').action = adjUrl.replace('__ID__', d.student.id);
        payForm.action = trxUrl.replace('__ID__', d.student.id);
        document.getElementById('payDate').value = today;
        resetConfirm();
        modal.show();
    }

    document.querySelectorAll('.student-row').forEach(row => row.addEventListener('click', () => openStudent(row)));

    // Step 1: show summary of what will be recorded
    document.getElementById('payPreview').addEventListener('click', () => {
        if (!payForm.reportValidity()) return;
        const amount = Number(document.getElementById('payAmount').value);
        const date = document.getElementById('payDate').value;
        const note = document.getElementById('payNote').value;
        const remainingAfter = current.summary.remaining - amount;

        if (remainingAfter < 0) { alert('Nominal melebihi sisa kewajiban (' + fmt(current.summary.remaining) + ').'); return; }

        document.getElementById('confirmText').innerHTML =
            `Anda akan mencatat pembayaran <strong>${fmt(amount)}</strong> untuk <strong>${esc(current.student.name)}</strong>
             pada tanggal <strong>${esc(date)}</strong>${note ? ' (' + esc(note) + ')' : ''}.<br>
             Sisa kewajiban setelah pencatatan: <strong>${fmt(remainingAfter)}</strong>
             ${remainingAfter === 0 ? ' — <span class="text-success">LUNAS</span>' : ''}`;
        confirmBox.classList.remove('d-none');
        payActions.classList.add('d-none');
    });
    document.getElementById('confirmBack').addEventListener('click', resetConfirm);
    payForm.addEventListener('reset', () => { resetConfirm(); setTimeout(() => document.getElementById('payDate').value = today); });

    // Re-open the student's modal after a validation error
    @if ($errors->any() && old('_student'))
        (() => {
            const row = document.querySelector('.student-row[data-id="{{ old('_student') }}"]');
            if (row) openStudent(row);
        })();
    @endif
</script>
@endpush
