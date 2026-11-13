@extends('layouts.app')
@section('title', 'Tarif SPP')

@section('content')
@php
    $rateMap = $rates->mapWithKeys(fn ($r) => [$r->id => [
        'url'    => route('admin.fee-rates.update', $r),
        'year'   => $r->academic_year,
        'amount' => $r->amount,
        'from'   => $r->effective_from->toDateString(),
        'to'     => $r->effective_to->toDateString(),
    ]]);
    $stateTones = ['Aktif' => 'ok', 'Akan datang' => 'info'];
@endphp

<div x-data="rateModal()">
    <x-page-header title="Tarif SPP">
        <x-btn @click="open()">+ Tambah Tarif</x-btn>
    </x-page-header>

    @if ($current)
        <x-alert class="mb-5">
            Tarif yang sedang berlaku: <strong><x-money :value="$current->amount" /></strong> per bulan
            &middot; TA {{ $current->academic_year }}
            ({{ $current->effective_from->format('d/m/Y') }} &ndash; {{ $current->effective_to->format('d/m/Y') }})
        </x-alert>
    @else
        <x-alert tone="warn" class="mb-5">Belum ada tarif yang berlaku hari ini. Tambahkan tarif agar tagihan dapat dibuat.</x-alert>
    @endif

    <x-card flush>
        <x-table :head="['Tahun Ajaran', ['SPP / bulan', 'right'], 'Periode Berlaku', 'Status', '']">
            @forelse ($rates as $rate)
                @php $locked = in_array($rate->id, $lockedIds); @endphp
                <tr>
                    <td>{{ $rate->academic_year }}</td>
                    <td class="text-right"><x-money :value="$rate->amount" /></td>
                    <td>{{ $rate->effective_from->format('d/m/Y') }} &ndash; {{ $rate->effective_to->format('d/m/Y') }}</td>
                    <td>
                        <x-badge :tone="$stateTones[$rate->state()] ?? 'neutral'">{{ $rate->state() }}</x-badge>
                        @if ($locked)
                            <x-badge tone="neutral" :dot="false" class="ml-1">Sudah dipakai transaksi</x-badge>
                        @endif
                    </td>
                    <td class="text-right">
                        @if ($locked)
                            <x-btn size="sm" variant="secondary" disabled title="Tarif sudah dipakai transaksi">Ubah</x-btn>
                        @else
                            <x-btn size="sm" variant="soft" @click="open({{ $rate->id }})">Ubah</x-btn>
                        @endif
                    </td>
                </tr>
            @empty
                <x-table.empty>Belum ada tarif.</x-table.empty>
            @endforelse
        </x-table>
    </x-card>

    <p class="mt-3 text-xs text-muted">
        Tarif baru untuk tahun ajaran berikutnya tidak mengubah tagihan maupun transaksi tahun ajaran sebelumnya.
        Tarif yang sudah dipakai transaksi tidak dapat diubah agar tetap dapat ditelusuri.
    </p>

    <x-modal show="show" title-js="url ? 'Ubah Tarif' : 'Tambah Tarif'" x-bind:action="action"
             @submit="if (! confirm('Simpan tarif SPP Rp' + Number(amount).toLocaleString('id-ID') + ' per bulan untuk tahun ajaran ' + year + '?')) $event.preventDefault()">
        <input type="hidden" name="_method" value="PUT" :disabled="! url">
        <input type="hidden" name="_form" value="rate">
        <input type="hidden" name="_url" :value="url">

        <x-input label="Tahun ajaran" name="academic_year" x-model="year" placeholder="2026/2027" maxlength="20" required />
        <x-input label="Nominal SPP per bulan (Rp)" name="amount" type="number" min="1" x-model="amount" required />
        <div class="grid grid-cols-2 gap-3">
            <x-input label="Berlaku mulai" name="effective_from" type="date" x-model="from" required />
            <x-input label="Berlaku sampai" name="effective_to" type="date" x-model="to" required />
        </div>
        <p class="text-xs text-muted">Setiap bulan dalam periode berlaku akan menjadi satu tagihan SPP.</p>
    </x-modal>
</div>
@endsection

@push('scripts')
<script>
function rateModal() {
    const storeUrl = @js(route('admin.fee-rates.store'));

    return {
        show: false, url: null, year: '', amount: '', from: '', to: '',
        rates: @js($rateMap),

        get action() { return this.url ?? storeUrl; },

        open(id = null) { this.fill(this.rates[id]); },
        fill({ url = null, year = '', amount = '', from = '', to = '' } = {}) {
            Object.assign(this, { url, year: year ?? '', amount: amount ?? '', from: from ?? '', to: to ?? '', show: true });
        },

        @if ($errors->any() && old('_form') === 'rate')
        init() {
            this.fill({
                url: @js(old('_url') ?: null),
                year: @js(old('academic_year')),
                amount: @js(old('amount')),
                from: @js(old('effective_from')),
                to: @js(old('effective_to')),
            });
        },
        @endif
    };
}
</script>
@endpush
