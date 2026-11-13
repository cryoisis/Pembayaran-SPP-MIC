@extends('layouts.app')
@section('title', 'Data Siswa')

@section('content')
@php
    // Create-form values are only restored when the create form itself failed validation.
    $old = fn ($key) => old('_form') === 'create' ? old($key) : null;

    $studentMap = $students->getCollection()->mapWithKeys(fn ($s) => [$s->id => [
        'id'        => $s->id,
        'nisn'      => $s->nisn,
        'name'      => $s->name,
        'active'    => (bool) $s->is_active,
        'updateUrl' => route('admin.students.update', $s),
        'deleteUrl' => route('admin.students.destroy', $s),
    ]]);
@endphp

<div x-data="studentModals()">
    <x-page-header title="Data Siswa">
        <form method="GET" class="flex gap-2">
            <x-input type="search" name="q" :value="request('q')" placeholder="Cari NISN / nama" />
            <x-btn type="submit" variant="secondary">Cari</x-btn>
        </form>
        <x-btn @click="createOpen = true">+ Tambah Siswa</x-btn>
    </x-page-header>

    <x-card flush>
        <x-table :head="['NISN', 'Nama Lengkap', 'Status Akun', '']">
            @forelse ($students as $student)
                <tr class="cursor-pointer" @click="openEdit({{ $student->id }})">
                    <td>{{ $student->nisn }}</td>
                    <td class="font-medium">{{ $student->name }}</td>
                    <td>
                        <x-badge :tone="$student->is_active ? 'ok' : 'neutral'">{{ $student->is_active ? 'Aktif' : 'Nonaktif' }}</x-badge>
                    </td>
                    <td class="text-right"><x-btn size="sm" variant="soft">Kelola</x-btn></td>
                </tr>
            @empty
                <x-table.empty>Belum ada data siswa.</x-table.empty>
            @endforelse
        </x-table>
    </x-card>

    <div class="mt-4">{{ $students->withQueryString()->links() }}</div>

    {{-- Create --}}
    <x-modal show="createOpen" title="Tambah Siswa" :action="route('admin.students.store')">
        <input type="hidden" name="_form" value="create">
        <x-input label="NISN" name="nisn" :value="$old('nisn')" required />
        <x-input label="Nama lengkap" name="name" :value="$old('name')" required />
        <x-input label="Kata sandi (min. 6 karakter)" name="password" type="password" minlength="6" required />
    </x-modal>

    {{-- Edit --}}
    <x-modal show="editOpen" title="Kelola Siswa" method="PUT" x-bind:action="edit.updateUrl">
        <input type="hidden" name="_form" value="edit">
        <input type="hidden" name="_id" :value="edit.id">
        <x-input label="NISN" name="nisn" x-model="edit.nisn" required />
        <x-input label="Nama lengkap" name="name" x-model="edit.name" required />
        <x-input label="Kata sandi baru (kosongkan jika tidak diubah)" name="password" type="password" minlength="6" autocomplete="new-password" />
        <label class="flex cursor-pointer items-center gap-2 text-sm">
            <input type="checkbox" name="is_active" value="1" x-model="edit.active" class="size-4 rounded accent-forest">
            Akun aktif (bisa login)
        </label>

        <x-slot:footer>
            <x-btn type="submit" form="deleteForm" variant="danger"
                   @click="if (! confirm('Nonaktifkan siswa «' + edit.name + '»?\n\nSiswa tidak dapat login lagi, namun data dan riwayat pembayarannya tetap tersimpan. Akun dapat diaktifkan kembali lewat menu ini.')) $event.preventDefault()">
                Nonaktifkan
            </x-btn>
        </x-slot:footer>
    </x-modal>

    <form id="deleteForm" method="POST" x-bind:action="edit.deleteUrl">@csrf @method('DELETE')</form>
</div>
@endsection

@push('scripts')
<script>
function studentModals() {
    return {
        createOpen: @js($errors->any() && old('_form') === 'create'),
        editOpen: false,
        edit: {},
        students: @js($studentMap),

        openEdit(id) { this.show(this.students[id]); },
        show(student) { this.edit = { ...student }; this.editOpen = true; },

        @if ($errors->any() && old('_form') === 'edit' && old('_id'))
        init() {
            this.show({
                id: @js(old('_id')),
                nisn: @js(old('nisn')),
                name: @js(old('name')),
                active: @js((bool) old('is_active')),
                updateUrl: @js(route('admin.students.update', old('_id'))),
                deleteUrl: @js(route('admin.students.destroy', old('_id'))),
            });
        },
        @endif
    };
}
</script>
@endpush
