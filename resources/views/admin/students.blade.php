@extends('layouts.app')
@section('title', 'Data Siswa')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="h4 mb-0">Data Siswa</h1>
    <div class="d-flex gap-2">
        <form method="GET" class="d-flex gap-2">
            <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Cari NIS / nama">
            <button class="btn btn-outline-secondary">Cari</button>
        </form>
        <button class="btn btn-primary text-nowrap" data-bs-toggle="modal" data-bs-target="#createModal">+ Tambah Siswa</button>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr><th>NIS</th><th>Nama Lengkap</th><th>Akun</th></tr></thead>
            <tbody>
            @forelse ($students as $student)
                <tr class="student-row"
                    data-id="{{ $student->id }}"
                    data-nis="{{ $student->nis }}"
                    data-name="{{ $student->name }}"
                    data-active="{{ $student->is_active ? 1 : 0 }}"
                    data-update-url="{{ route('admin.students.update', $student) }}"
                    data-delete-url="{{ route('admin.students.destroy', $student) }}">
                    <td>{{ $student->nis }}</td>
                    <td>{{ $student->name }}</td>
                    <td>
                        <span class="badge text-bg-{{ $student->is_active ? 'success' : 'secondary' }}">
                            {{ $student->is_active ? 'Aktif' : 'Nonaktif' }}
                        </span>
                    </td>
                </tr>
            @empty
                <tr><td colspan="3" class="text-center text-muted py-4">Belum ada data siswa.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $students->links('pagination::bootstrap-5') }}</div>

{{-- Add student --}}
<div class="modal fade" id="createModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('admin.students.store') }}" class="modal-content">
            @csrf
            <input type="hidden" name="_form" value="create">
            <div class="modal-header"><h5 class="modal-title">Tambah Siswa</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-3"><label class="form-label">NIS</label>
                    <input type="text" name="nis" class="form-control" value="{{ old('_form') === 'create' ? old('nis') : '' }}" required></div>
                <div class="mb-3"><label class="form-label">Nama lengkap</label>
                    <input type="text" name="name" class="form-control" value="{{ old('_form') === 'create' ? old('name') : '' }}" required></div>
                <div class="mb-3"><label class="form-label">Password (min. 6 karakter)</label>
                    <input type="password" name="password" class="form-control" minlength="6" required></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>

{{-- Edit / delete student --}}
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="editForm">
                @csrf @method('PUT')
                <input type="hidden" name="_form" value="edit">
                <input type="hidden" name="_id" id="editId">
                <div class="modal-header"><h5 class="modal-title">Kelola Siswa</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label">NIS</label>
                        <input type="text" name="nis" id="editNis" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Nama lengkap</label>
                        <input type="text" name="name" id="editName" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Password baru <small class="text-muted">(kosongkan jika tidak diubah)</small></label>
                        <input type="password" name="password" class="form-control" minlength="6" autocomplete="new-password"></div>
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" name="is_active" id="editActive" value="1">
                        <label class="form-check-label" for="editActive">Akun aktif (bisa login)</label>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="submit" form="deleteForm" class="btn btn-outline-danger" id="deleteBtn">Hapus / Nonaktifkan</button>
                    <div>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </div>
            </form>
            <form method="POST" id="deleteForm">@csrf @method('DELETE')</form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const editModal = new bootstrap.Modal(document.getElementById('editModal'));
    const $ = id => document.getElementById(id);

    function openEdit(row, old = null) {
        $('editForm').action = row.dataset.updateUrl;
        $('deleteForm').action = row.dataset.deleteUrl;
        $('editId').value = row.dataset.id;
        $('editNis').value = old?.nis ?? row.dataset.nis;
        $('editName').value = old?.name ?? row.dataset.name;
        $('editActive').checked = old ? old.active : row.dataset.active === '1';
        $('deleteBtn').dataset.name = row.dataset.name;
        editModal.show();
    }

    document.querySelectorAll('.student-row').forEach(row => row.addEventListener('click', () => openEdit(row)));

    // Confirmation before delete / deactivate
    $('deleteForm').addEventListener('submit', e => {
        const name = $('deleteBtn').dataset.name;
        if (!confirm(`Hapus siswa "${name}"?\n\nJika siswa sudah memiliki riwayat pembayaran, akun hanya akan dinonaktifkan agar catatan tetap tersimpan.`)) {
            e.preventDefault();
        }
    });

    // Re-open the right modal after a validation error
    @if ($errors->any() && old('_form') === 'create')
        new bootstrap.Modal(document.getElementById('createModal')).show();
    @elseif ($errors->any() && old('_form') === 'edit')
        (() => {
            const row = document.querySelector('.student-row[data-id="{{ old('_id') }}"]');
            if (row) openEdit(row, {nis: @json(old('nis')), name: @json(old('name')), active: {{ old('is_active') ? 'true' : 'false' }}});
        })();
    @endif
</script>
@endpush
