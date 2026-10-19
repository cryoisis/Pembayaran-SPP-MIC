@extends('layouts.app')
@section('title', 'Dashboard Admin')

@section('content')
<h1 class="h4 mb-3">Dashboard Admin <small class="text-muted fs-6">· {{ $period->name }}</small></h1>

{{-- Summary of the running period --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3"><div class="card"><div class="card-body">
        <div class="text-muted small">Siswa aktif</div><div class="fs-4 fw-semibold">{{ $studentCount }}</div>
    </div></div></div>
    <div class="col-6 col-md-3"><div class="card"><div class="card-body">
        <div class="text-muted small">Total kewajiban</div><div class="fs-5 fw-semibold">Rp{{ number_format($totals['obligation'], 0, ',', '.') }}</div>
    </div></div></div>
    <div class="col-6 col-md-3"><div class="card"><div class="card-body">
        <div class="text-muted small">Terkumpul</div><div class="fs-5 fw-semibold text-success">Rp{{ number_format($totals['paid'], 0, ',', '.') }}</div>
    </div></div></div>
    <div class="col-6 col-md-3"><div class="card"><div class="card-body">
        <div class="text-muted small">Belum terkumpul</div><div class="fs-5 fw-semibold text-danger">Rp{{ number_format($totals['remaining'], 0, ',', '.') }}</div>
    </div></div></div>
</div>

<div class="card mb-4">
    <div class="card-header fw-semibold">Rekap Status Pembayaran</div>
    <div class="card-body d-flex flex-wrap gap-3">
        @foreach ($statusCounts as $status => $count)
            <div>@include('layouts.partials.status-badge', ['status' => $status]) <strong>{{ $count }}</strong></div>
        @endforeach
    </div>
</div>

<div class="row g-3">
    {{-- Schedule --}}
    <div class="col-lg-6">
        <form method="POST" action="{{ route('admin.schedule.update') }}" class="card h-100" data-edit-form>
            @csrf @method('PUT')
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-semibold">Jadwal Pembayaran</span>
                <button type="button" class="btn btn-sm btn-outline-primary" data-edit-toggle>Edit</button>
            </div>
            <fieldset class="card-body" disabled>
                <div class="mb-3">
                    <label class="form-label">Nama periode</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $period->name) }}" required>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Tanggal mulai</label>
                        <input type="date" name="start_date" class="form-control" value="{{ old('start_date', $period->start_date->toDateString()) }}" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Batas akhir</label>
                        <input type="date" name="due_date" class="form-control" value="{{ old('due_date', $period->due_date->toDateString()) }}" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Nominal standar (Rp)</label>
                    <input type="number" min="0" name="standard_fee" class="form-control" value="{{ old('standard_fee', $period->standard_fee) }}" required>
                </div>
                <button type="submit" class="btn btn-primary d-none" data-save>Simpan</button>
            </fieldset>
        </form>
    </div>

    {{-- Announcement --}}
    <div class="col-lg-6">
        <form method="POST" action="{{ route('admin.announcement.update') }}" class="card h-100" data-edit-form>
            @csrf @method('PUT')
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-semibold">Pengumuman &amp; Instruksi Pembayaran</span>
                <button type="button" class="btn btn-sm btn-outline-primary" data-edit-toggle>Edit</button>
            </div>
            <fieldset class="card-body" disabled>
                <div class="mb-3">
                    <label class="form-label">Judul</label>
                    <input type="text" name="announcement_title" class="form-control" value="{{ old('announcement_title', $period->announcement_title) }}">
                </div>
                <div class="mb-3">
                    <label class="form-label">Isi pengumuman</label>
                    <textarea name="announcement_body" rows="6" class="form-control">{{ old('announcement_body', $period->announcement_body) }}</textarea>
                </div>
                <button type="submit" class="btn btn-primary d-none" data-save>Simpan</button>
            </fieldset>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Edit / Simpan toggle: sections are read-only until "Edit" is pressed.
    document.querySelectorAll('[data-edit-form]').forEach(form => {
        const btn = form.querySelector('[data-edit-toggle]');
        const fieldset = form.querySelector('fieldset');
        const save = form.querySelector('[data-save]');
        const setEditing = on => {
            fieldset.disabled = !on;
            save.classList.toggle('d-none', !on);
            btn.textContent = on ? 'Batal' : 'Edit';
            if (!on) form.reset();
        };
        btn.addEventListener('click', () => setEditing(fieldset.disabled));
        @if ($errors->any())
            // keep the form open if validation failed
            if (form.querySelector('[name="{{ array_key_first($errors->messages()) }}"]')) setEditing(true);
        @endif
    });
</script>
@endpush
