@extends('layouts.app')
@section('title', 'Dashboard Admin')

@section('content')
@php
    $announcementMap = $announcements->mapWithKeys(fn ($a) => [$a->id => ['title' => $a->title, 'body' => $a->body]]);
@endphp

<div x-data="announcementModal()">
    <x-page-header title="Dashboard Bendahara" sub="Ringkasan tagihan dan pembayaran seluruh siswa aktif." />

    <section class="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Ringkasan keuangan">
        <x-stat label="Siswa aktif" icon="users" :value="$studentCount" />
        <x-stat label="Total tagihan" icon="receipt" :money="$totals['total']" />
        <x-stat label="Total pembayaran tercatat" icon="wallet" tone="ok" :money="$totals['paid']" />
        <x-stat label="Sisa kewajiban" icon="alert" tone="bad" :money="$totals['remaining']" />
    </section>

    <section class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Jumlah siswa per status">
        @foreach ($statusCounts as $status => $count)
            <x-card>
                <div class="flex items-center justify-between">
                    <div>
                        <x-badge :status="$status" />
                        <div class="mt-2 text-xs text-muted">jumlah siswa</div>
                    </div>
                    <div class="text-2xl font-semibold">{{ $count }}</div>
                </div>
            </x-card>
        @endforeach
    </section>

    {{-- Arrears --}}
    <x-card flush class="mb-6">
        <x-slot:title>
            Siswa dengan Tunggakan
            <x-badge tone="bad" class="ml-1">{{ $statusCounts[\App\Models\PaymentSetting::STATUS_LATE] }}</x-badge>
        </x-slot:title>
        <x-slot:actions>
            <x-btn size="sm" variant="danger" :href="route('admin.arrears.index')">Lihat semua</x-btn>
        </x-slot:actions>

        <x-table :head="['NISN', 'Nama', 'Periode tertunggak', ['Sisa', 'right'], 'Jatuh tempo tertua']">
            @forelse ($arrears as $row)
                <tr>
                    <td>{{ $row['student']->nisn }}</td>
                    <td class="font-medium">{{ $row['student']->name }}</td>
                    <td>{{ $row['periods'] }}</td>
                    <td class="text-right"><x-money :value="$row['remaining']" /></td>
                    <td>{{ $row['due']->format('d/m/Y') }}</td>
                </tr>
            @empty
                <x-table.empty>Tidak ada tunggakan.</x-table.empty>
            @endforelse
        </x-table>
    </x-card>

    <div class="grid gap-4 lg:grid-cols-2">
        {{-- Monthly schedule --}}
        <x-card title="Jadwal Pembayaran Bulanan" x-data="{ editing: false }">
            <x-slot:actions>
                <x-btn size="sm" variant="soft" x-text="editing ? 'Batal' : 'Edit'"
                       @click="editing = ! editing; if (! editing) $refs.form.reset()">Edit</x-btn>
            </x-slot:actions>

            <form method="POST" action="{{ route('admin.schedule.update') }}" x-ref="form">
                @csrf @method('PUT')
                <fieldset :disabled="! editing" class="space-y-4">
                    <x-input label="Tanggal mulai pembayaran (setiap bulan)" name="start_day" type="number" min="1" max="31"
                             :value="old('start_day', $setting->start_day)" required />
                    <x-input label="Batas akhir pembayaran (setiap bulan)" name="due_day" type="number" min="1" max="31"
                             :value="old('due_day', $setting->due_day)" required
                             hint="Tagihan bulan itu terlambat jika belum lunas setelah tanggal ini." />
                </fieldset>
                <x-btn type="submit" class="mt-4" x-show="editing" x-cloak>Simpan</x-btn>
            </form>

            <x-slot:footer>
                @if ($feeRate)
                    Tarif berlaku: <strong><x-money :value="$feeRate->amount" /></strong>/bulan (TA {{ $feeRate->academic_year }}) &middot;
                @else
                    <span class="text-bad-fg">Belum ada tarif yang berlaku hari ini.</span>
                @endif
                <a href="{{ route('admin.fee-rates.index') }}" class="text-forest hover:underline">Kelola tarif</a>
            </x-slot:footer>
        </x-card>

        {{-- Announcements --}}
        <x-card flush title="Pengumuman & Instruksi Pembayaran">
            <x-slot:actions>
                <x-btn size="sm" @click="open()">+ Buat Pengumuman</x-btn>
            </x-slot:actions>

            <ul class="divide-y divide-line">
                @forelse ($announcements as $a)
                    <li class="flex justify-between gap-3 px-5 py-4">
                        <div class="min-w-0">
                            <div class="text-sm font-semibold">{{ $a->title }}</div>
                            <p class="mt-0.5 whitespace-pre-line text-sm">{{ $a->body }}</p>
                            <time class="mt-1 block text-xs text-muted">{{ $a->updated_at->format('d/m/Y H:i') }}</time>
                        </div>
                        <div class="flex shrink-0 items-start gap-2">
                            <x-btn size="sm" variant="soft" @click="open({{ $a->id }})">Ubah</x-btn>
                            <form method="POST" action="{{ route('admin.announcements.destroy', $a) }}"
                                  @submit="if (! confirm(@js('Hapus pengumuman “'.$a->title.'”?'))) $event.preventDefault()">
                                @csrf @method('DELETE')
                                <x-btn type="submit" size="sm" variant="danger">Hapus</x-btn>
                            </form>
                        </div>
                    </li>
                @empty
                    <li class="px-5 py-6 text-sm text-muted">Belum ada pengumuman.</li>
                @endforelse
            </ul>
        </x-card>
    </div>

    <x-modal show="show" title-js="id ? 'Ubah Pengumuman' : 'Buat Pengumuman'" x-bind:action="action">
        <input type="hidden" name="_method" value="PUT" :disabled="! id">
        <input type="hidden" name="_form" value="announcement">
        <input type="hidden" name="_id" :value="id">
        <x-input label="Judul" name="title" x-model="title" maxlength="150" required />
        <x-input type="textarea" label="Isi pengumuman / instruksi" name="body" x-model="body" rows="6" required />
    </x-modal>
</div>
@endsection

@push('scripts')
<script>
function announcementModal() {
    const storeUrl  = @js(route('admin.announcements.store'));
    const updateUrl = @js(route('admin.announcements.update', ['announcement' => '__ID__']));

    return {
        show: false, id: null, title: '', body: '',
        items: @js($announcementMap),

        get action() { return this.id ? updateUrl.replace('__ID__', this.id) : storeUrl; },

        open(id = null) { this.fill(id, this.items[id]); },
        fill(id = null, { title = '', body = '' } = {}) {
            Object.assign(this, { id, title: title ?? '', body: body ?? '', show: true });
        },

        @if ($errors->any() && old('_form') === 'announcement')
        init() { this.fill(@js(old('_id') ?: null), { title: @js(old('title')), body: @js(old('body')) }); },
        @endif
    };
}
</script>
@endpush
