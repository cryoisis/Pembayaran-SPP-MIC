@extends('layouts.app')
@section('title', 'Rincian Transaksi')

@section('content')
@php
    $trx    = $transaction;
    $picked = collect(old('bill_ids', $selectedIds))->map(fn ($id) => (int) $id)->values();
@endphp

<div x-data="{
    editOpen: @js($errors->any() && old('_form') === 'edit'),
    cancelOpen: @js($errors->any() && old('_form') === 'cancel'),
    picked: @js($picked),
    prices: @js($available->pluck('amount', 'id')),
    amount: @js(old('amount', $trx->amount)),
    init() {
        // Amount follows the selected periods
        this.$watch('picked', ids => this.amount = ids.reduce((sum, id) => sum + Number(this.prices[id] || 0), 0) || '');
    },
}">
    <x-page-header>
        <x-slot:title>Rincian Transaksi #{{ $trx->id }} <x-badge :trx="$trx" /></x-slot:title>
        <x-btn variant="secondary" :href="route('admin.transactions.index')">&larr; Daftar Transaksi</x-btn>
    </x-page-header>

    <div class="mb-6 grid gap-4 lg:grid-cols-2">
        <x-card flush title="Informasi Transaksi">
            <dl class="divide-y divide-line">
                <x-detail label="Siswa">{{ $trx->student->name }} ({{ $trx->student->nisn }})</x-detail>
                <x-detail label="Tanggal transaksi">{{ $trx->paid_at->format('d/m/Y') }}</x-detail>
                <x-detail label="Metode">{{ $trx->method_label }}</x-detail>
                <x-detail label="Nominal"><x-money :value="$trx->amount" class="font-semibold" /></x-detail>
                <x-detail label="Keterangan">{{ $trx->note ?: '-' }}</x-detail>
                <x-detail label="Dicatat oleh">{{ $trx->creator?->name ?? '-' }} ({{ $trx->created_at->format('d/m/Y H:i') }})</x-detail>
                @if ($trx->isConfirmed())
                    <x-detail label="Dikonfirmasi oleh">{{ $trx->confirmer?->name ?? '-' }} ({{ $trx->confirmed_at?->format('d/m/Y H:i') }})</x-detail>
                @endif
                @if ($trx->isCancelled())
                    <x-detail label="Dibatalkan oleh">{{ $trx->canceller?->name ?? '-' }} ({{ $trx->cancelled_at?->format('d/m/Y H:i') }})</x-detail>
                    <x-detail label="Alasan pembatalan">{{ $trx->cancel_reason }}</x-detail>
                @endif
            </dl>
        </x-card>

        <x-card flush title="Periode yang Dibayarkan">
            <x-table :head="['Periode', ['Nominal', 'right']]">
                @foreach ($trx->bills as $bill)
                    <tr>
                        <td>{{ $bill->label }}</td>
                        <td class="text-right"><x-money :value="$bill->pivot->amount" /></td>
                    </tr>
                @endforeach
            </x-table>
        </x-card>
    </div>

    @unless ($trx->isCancelled())
        <div class="mb-6 flex flex-wrap gap-2">
            @if ($trx->isPending())
                <form method="POST" action="{{ route('admin.transactions.confirm', $trx) }}"
                      @submit="if (! confirm('Konfirmasi transaksi ini sebagai pembayaran sah?')) $event.preventDefault()">
                    @csrf
                    <x-btn type="submit" variant="ok">Konfirmasi Transaksi</x-btn>
                </form>
            @endif
            <x-btn @click="editOpen = true">Edit</x-btn>
            <x-btn variant="danger" @click="cancelOpen = true">Batalkan / Hapus</x-btn>
        </div>
    @endunless

    <x-card flush title="Riwayat Perubahan" class="mb-6">
        <ul class="divide-y divide-line">
            @forelse ($trx->logs as $log)
                <li class="px-5 py-4">
                    <div class="flex justify-between gap-2">
                        <strong class="text-sm capitalize">{{ $log->action }}</strong>
                        <small class="text-xs text-muted">{{ $log->created_at->format('d/m/Y H:i') }} &middot; {{ $log->user?->name ?? '-' }}</small>
                    </div>
                    <p class="mt-1 whitespace-pre-line text-sm">{{ $log->detail }}</p>
                </li>
            @empty
                <li class="px-5 py-6 text-sm text-muted">Belum ada riwayat.</li>
            @endforelse
        </ul>
    </x-card>

    @unless ($trx->isCancelled())
        {{-- Edit --}}
        <x-modal show="editOpen" title="Edit Transaksi" size="2xl" method="PUT" submit="Simpan Perubahan"
                 :action="route('admin.transactions.update', $trx)"
                 @submit="if (! confirm('Simpan perubahan pada transaksi ini?')) $event.preventDefault()">
            <input type="hidden" name="_form" value="edit">

            <div>
                <div class="mb-2 text-sm font-medium text-muted">Periode yang dibayarkan</div>
                <div class="grid grid-cols-2 gap-2 md:grid-cols-3">
                    @foreach ($available as $bill)
                        <label class="cursor-pointer">
                            <input type="checkbox" name="bill_ids[]" value="{{ $bill->id }}" x-model.number="picked" class="peer sr-only">
                            <span class="block rounded-xl border-2 border-line p-3 text-sm transition peer-checked:border-forest peer-checked:bg-forest-tint">
                                <strong class="block">{{ $bill->label }}</strong>
                                <x-money :value="$bill->amount" class="text-muted" />
                            </span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="grid gap-3 sm:grid-cols-2">
                <x-input label="Nominal (Rp)" name="amount" type="number" min="1" x-model="amount" required />
                <x-input label="Tanggal transaksi" name="paid_at" type="date" max="{{ now()->toDateString() }}"
                         :value="old('paid_at', $trx->paid_at->toDateString())" required />
                <x-select label="Metode" name="method" required>
                    @foreach (\App\Models\PaymentTransaction::METHODS as $value => $label)
                        <option value="{{ $value }}" @selected(old('method', $trx->method) === $value)>{{ $label }}</option>
                    @endforeach
                </x-select>
                <x-input label="Keterangan" name="note" maxlength="255" :value="old('note', $trx->note)" />
            </div>

            <p class="text-xs text-muted">Nominal otomatis mengikuti periode terpilih. Perubahan dicatat di riwayat dan status pembayaran dihitung ulang.</p>
        </x-modal>

        {{-- Cancel --}}
        <x-modal show="cancelOpen" title="Batalkan Transaksi" method="DELETE" cancel="Kembali" submit="Batalkan Transaksi" variant="destructive"
                 :action="route('admin.transactions.cancel', $trx)"
                 @submit="if (! confirm('Batalkan transaksi ini? Tindakan ini tercatat di riwayat.')) $event.preventDefault()">
            <input type="hidden" name="_form" value="cancel">
            <p class="text-sm">Transaksi tidak dihapus permanen: tetap tercatat sebagai <strong>Dibatalkan</strong> dan tidak dihitung sebagai pembayaran sah.</p>
            <x-input label="Alasan pembatalan" name="cancel_reason" maxlength="255" required />
        </x-modal>
    @endunless
</div>
@endsection
