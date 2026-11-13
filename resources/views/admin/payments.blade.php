@extends('layouts.app')
@section('title', 'Pembayaran Siswa')

@section('content')
@php
    $rows = $students->getCollection()->map(fn ($s) => ['student' => $s, 'sum' => $s->paymentSummary($setting)]);
    $statusCount = $rows->countBy(fn ($r) => $r['sum']['status']);
    $tabs = ['' => 'Semua', 'Belum lunas' => 'Belum lunas', 'Terlambat' => 'Terlambat', 'Lunas' => 'Lunas'];
@endphp

<div x-data="paymentDrawer()">
    <x-page-header title="Pembayaran SPP">
        <x-slot:sub>
            @if ($rate)
                Tarif berlaku TA {{ $rate->academic_year }}: <x-money :value="$rate->amount" /> per bulan.
            @else
                <span class="font-semibold text-bad-fg">Belum ada tarif yang berlaku.</span>
            @endif
            Tagihan dibuka tanggal {{ $setting->start_day }} dan jatuh tempo tanggal {{ $setting->due_day }} setiap bulan.
        </x-slot:sub>
    </x-page-header>

    <section class="mb-6 grid gap-4 sm:grid-cols-3" aria-label="Ringkasan keuangan">
        @if (isset($metrics))
            <x-stat label="Pendapatan bulan ini" icon="wallet" tone="ok" :money="$metrics['income_month']" hint="Pembayaran terkonfirmasi" />
            <x-stat label="Siswa belum lunas" icon="alert" tone="warn" :value="$metrics['unpaid_students']"
                    :hint="isset($metrics['active_students']) ? 'dari '.$metrics['active_students'].' siswa aktif' : 'seluruh siswa aktif'" />
            <x-stat label="Transaksi masuk hari ini" icon="receipt" :value="$metrics['trx_today']"
                    :hint="'Tercatat '.now()->translatedFormat('d F Y')" />
        @else
            <x-stat label="Terbayar (daftar ini)" icon="wallet" tone="ok" :money="$rows->sum(fn ($r) => $r['sum']['paid'])" hint="Hanya siswa di halaman ini" />
            <x-stat label="Belum lunas (daftar ini)" icon="alert" tone="warn" :value="$rows->filter(fn ($r) => $r['sum']['status'] !== 'Lunas')->count()"
                    :hint="'dari '.$rows->count().' siswa di halaman ini'" />
            <x-stat label="Sisa kewajiban (daftar ini)" icon="receipt" :money="$rows->sum(fn ($r) => $r['sum']['remaining'])" hint="Hanya siswa di halaman ini" />
        @endif
    </section>

    {{-- Search + status filter --}}
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <form method="GET" role="search" class="relative w-full max-w-sm">
            <x-icon name="search" size="size-4" class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-muted" />
            <x-input type="search" name="q" :value="request('q')" placeholder="Cari NISN atau nama siswa" autocomplete="off" aria-label="Cari siswa" class="pl-10" />
        </form>

        <div class="inline-flex rounded-xl border border-line bg-white p-0.5" role="group" aria-label="Saring status">
            @foreach ($tabs as $value => $label)
                <button type="button" @click="filter = '{{ $value }}'"
                        :class="filter === '{{ $value }}' ? 'bg-forest text-white' : 'text-muted hover:text-ink'"
                        class="rounded-lg px-3 py-1.5 text-xs font-medium transition">
                    {{ $label }} <span class="opacity-70">{{ $value === '' ? $rows->count() : ($statusCount[$value] ?? 0) }}</span>
                </button>
            @endforeach
        </div>
    </div>

    <x-card flush>
        <x-table :head="['NISN', 'Nama lengkap', 'Status', ['Total tagihan', 'right'], ['Total pembayaran', 'right'], ['Sisa kewajiban', 'right'], '']">
            @forelse ($rows as $r)
                @php($student = $r['student'])
                @php($sum = $r['sum'])
                <tr class="cursor-pointer" x-show="matches('{{ $sum['status'] }}')" @click="open({{ $student->id }})">
                    <td>{{ $student->nisn }}</td>
                    <td class="font-medium">{{ $student->name }}</td>
                    <td><x-badge :status="$sum['status']" /></td>
                    <td class="text-right"><x-money :value="$sum['total']" /></td>
                    <td class="text-right"><x-money :value="$sum['paid']" /></td>
                    <td @class(['text-right', 'font-semibold text-bad-fg' => $sum['remaining'] > 0, 'text-muted' => $sum['remaining'] <= 0])>
                        <x-money :value="$sum['remaining']" />
                    </td>
                    <td class="text-right">
                        <x-btn size="sm" variant="soft" @click.stop="open({{ $student->id }})">{{ $sum['status'] === 'Lunas' ? 'Lihat' : 'Catat' }}</x-btn>
                    </td>
                </tr>
            @empty
                <x-table.empty>Tidak ada siswa ditemukan. Coba kata kunci lain atau hapus filter.</x-table.empty>
            @endforelse

            @if ($rows->isNotEmpty())
                <tr x-show="filter && ! visibleCount" x-cloak>
                    <td colspan="7" class="!py-10 text-center text-muted">Tidak ada siswa dengan status ini di halaman ini.</td>
                </tr>
            @endif
        </x-table>
    </x-card>

    <div class="mt-4">{{ $students->withQueryString()->links() }}</div>
    <p class="mt-4 text-xs text-muted">Klik baris siswa atau tombol Catat / Lihat untuk membuka detail dan mencatat pembayaran.</p>

    {{-- Slide-over drawer --}}
    <div x-show="isOpen" x-cloak class="fixed inset-0 z-50" role="dialog" aria-modal="true" @keydown.escape.window="close()">
        <div class="absolute inset-0 bg-ink/40 backdrop-blur-sm" x-show="isOpen" x-transition.opacity @click="close()"></div>

        <aside class="absolute inset-y-0 right-0 flex w-full max-w-2xl flex-col bg-white shadow-2xl"
               x-show="isOpen"
               x-transition:enter="transition duration-300 ease-out" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
               x-transition:leave="transition duration-200 ease-in" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full">

            <header class="flex items-center justify-between gap-4 border-b border-line bg-forest-soft/50 px-6 py-4">
                <div class="min-w-0">
                    <h2 class="truncate text-lg font-semibold tracking-tight" x-text="data?.student?.name || 'Detail Siswa'"></h2>
                    <div class="mt-0.5 text-xs text-muted">
                        NISN: <strong x-text="data?.student?.nisn || '-'"></strong>
                        <template x-if="data?.rate">
                            <span>&middot; TA <span x-text="data.rate.academic_year"></span> (<span x-text="rp(data.rate.amount) + '/bln'"></span>)</span>
                        </template>
                    </div>
                </div>
                <button type="button" @click="close()" class="rounded-xl p-2 text-muted hover:bg-forest-soft hover:text-ink" aria-label="Tutup">
                    <x-icon name="x" />
                </button>
            </header>

            <div class="flex-1 space-y-6 overflow-y-auto p-6">
                {{-- Loading --}}
                <div x-show="loading" class="py-16 text-center text-muted">
                    <svg class="mx-auto mb-3 size-8 animate-spin text-forest" viewBox="0 0 24 24" fill="none">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
                    </svg>
                    <p class="text-sm font-medium">Memuat data pembayaran...</p>
                </div>

                {{-- Error --}}
                <div x-show="! loading && error" class="tone-bad rounded-xl p-4 text-sm">
                    <div class="font-medium">Gagal Memuat Data</div>
                    <p class="mt-1 text-xs" x-text="error"></p>
                    <x-btn size="sm" variant="danger" class="mt-3" @click="open(studentId)">Coba Lagi</x-btn>
                </div>

                {{-- Loaded --}}
                <div x-show="! loading && data" class="space-y-6">
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                        <template x-for="tile in tiles" :key="tile.label">
                            <div class="rounded-xl border border-line p-3">
                                <div class="text-[11px] font-semibold uppercase tracking-wide text-muted" x-text="tile.label"></div>
                                <div class="mt-1 text-base font-semibold" :class="tile.class" x-text="rp(tile.value)"></div>
                            </div>
                        </template>
                        <div class="rounded-xl border border-line p-3">
                            <div class="text-[11px] font-semibold uppercase tracking-wide text-muted">Status</div>
                            <x-badge class="mt-1" x-bind:class="tone(data?.summary?.status)"><span x-text="data?.summary?.status"></span></x-badge>
                        </div>
                    </div>

                    {{-- Record payment --}}
                    <section class="rounded-2xl border border-line bg-forest-soft/50 p-5">
                        <h3 class="mb-3 flex items-center justify-between text-sm font-semibold">
                            Pencatatan Pembayaran Baru
                            <span class="text-xs font-normal text-muted" x-show="payable.length"><span x-text="payable.length"></span> periode dapat dibayar</span>
                        </h3>

                        <template x-if="! payable.length">
                            <div class="tone-ok flex items-start gap-3 rounded-xl p-4 text-sm">
                                <x-icon name="check" class="mt-0.5" />
                                <div>
                                    <strong class="font-semibold">Semua tagihan telah terselesaikan</strong>
                                    <p class="mt-0.5 text-xs">Tidak ada tagihan tertunggak atau tagihan yang menunggu pembayaran saat ini.</p>
                                </div>
                            </div>
                        </template>

                        <template x-if="payable.length">
                            <form method="POST" :action="storeUrl" class="space-y-4">
                                @csrf
                                <input type="hidden" name="_student" :value="data?.student?.id">

                                <div>
                                    <div class="mb-2 flex items-center justify-between text-xs">
                                        <span class="font-semibold uppercase tracking-wide text-muted">Pilih Periode Tagihan <span class="text-bad-fg">*</span></span>
                                        <span class="space-x-2">
                                            <button type="button" @click="selected = payable.map(b => b.id)" class="font-medium text-forest hover:underline">Pilih Semua</button>
                                            <span class="text-line">&bull;</span>
                                            <button type="button" @click="selected = []" class="text-muted hover:underline">Hapus</button>
                                        </span>
                                    </div>
                                    <div class="grid max-h-52 gap-2 overflow-y-auto rounded-xl border border-line bg-white p-1.5 sm:grid-cols-2">
                                        <template x-for="bill in payable" :key="bill.id">
                                            <label class="flex cursor-pointer select-none items-center gap-2.5 rounded-lg border p-2.5 text-xs transition"
                                                   :class="selected.includes(bill.id) ? 'border-forest bg-forest-tint' : 'border-line hover:border-forest/40'">
                                                <input type="checkbox" name="bill_ids[]" :value="bill.id" x-model.number="selected" class="size-4 rounded accent-forest">
                                                <div class="min-w-0 flex-1">
                                                    <div class="truncate font-medium" x-text="bill.label"></div>
                                                    <div class="text-[11px] text-muted" x-text="'Jatuh tempo: ' + bill.due_date"></div>
                                                </div>
                                                <div class="font-semibold" x-text="rp(bill.amount)"></div>
                                            </label>
                                        </template>
                                    </div>
                                </div>

                                <div>
                                    <div class="mb-1.5 text-sm font-medium text-muted">Nominal Pembayaran</div>
                                    <div class="flex items-center justify-between rounded-xl border border-line bg-white px-4 py-2.5">
                                        <span class="text-xs text-muted">Total (<span x-text="selected.length"></span> periode dipilih):</span>
                                        <span class="text-lg font-bold text-forest" x-text="rp(total)"></span>
                                    </div>
                                    <input type="hidden" name="amount" :value="total">
                                    <p class="mt-1 text-xs text-muted">Nominal pembayaran dihitung otomatis sesuai tagihan periode yang dipilih.</p>
                                </div>

                                <div class="grid gap-3 sm:grid-cols-2">
                                    <x-input label="Tanggal Transaksi" name="paid_at" type="date" x-model="paidAt" x-bind:max="today" required />
                                    <x-select label="Metode Pembayaran" name="method" x-model="method" required>
                                        <option value="tunai">Tunai</option>
                                        <option value="transfer">Transfer</option>
                                    </x-select>
                                </div>

                                <x-input label="Keterangan (Opsional)" name="note" x-model="note" placeholder="Catatan transaksi, misal nomor resi transfer atau titip wali..." />

                                <div class="flex flex-wrap justify-end gap-2 border-t border-line pt-4">
                                    <x-btn type="submit" variant="secondary" name="action" value="save" x-bind:disabled="! selected.length">Simpan (Menunggu Konfirmasi)</x-btn>
                                    <x-btn type="submit" name="action" value="confirm" x-bind:disabled="! selected.length">Konfirmasi Langsung</x-btn>
                                </div>
                            </form>
                        </template>
                    </section>

                    {{-- Bills --}}
                    <section>
                        <h3 class="mb-3 text-sm font-semibold">Rincian Tagihan SPP (<span x-text="bills.length"></span>)</h3>
                        <div class="overflow-hidden rounded-xl border border-line">
                            <x-table compact :head="['Bulan', ['Nominal', 'right'], 'Jatuh Tempo', 'Status']">
                                <template x-for="bill in bills" :key="bill.id">
                                    <tr>
                                        <td class="font-medium" x-text="bill.label"></td>
                                        <td class="text-right" x-text="rp(bill.amount)"></td>
                                        <td class="text-muted" x-text="bill.due_date"></td>
                                        <td>
                                            <x-badge x-bind:class="tone(bill.status, bill.pending)">
                                                <span x-text="bill.pending ? 'Menunggu konfirmasi' : bill.status"></span>
                                            </x-badge>
                                        </td>
                                    </tr>
                                </template>
                            </x-table>
                        </div>
                    </section>

                    {{-- Transactions --}}
                    <section>
                        <h3 class="mb-3 text-sm font-semibold">Riwayat Transaksi Pembayaran (<span x-text="transactions.length"></span>)</h3>

                        <template x-if="! transactions.length">
                            <div class="rounded-xl border border-line p-6 text-center text-xs text-muted">Belum ada transaksi pembayaran untuk siswa ini.</div>
                        </template>

                        <template x-if="transactions.length">
                            <div class="overflow-hidden rounded-xl border border-line">
                                <x-table compact :head="['Tanggal', 'Periode', 'Metode', ['Nominal', 'right'], 'Status', '']">
                                    <template x-for="trx in transactions" :key="trx.url">
                                        <tr>
                                            <td class="font-medium" x-text="trx.date"></td>
                                            <td class="text-muted" x-text="trx.periods"></td>
                                            <td class="capitalize" x-text="trx.method"></td>
                                            <td class="text-right font-semibold" x-text="rp(trx.amount)"></td>
                                            <td>
                                                <x-badge x-bind:class="trxTone(trx.status_color)"><span x-text="trx.status_label"></span></x-badge>
                                            </td>
                                            <td class="text-right"><a :href="trx.url" class="font-medium text-forest hover:underline">Rincian</a></td>
                                        </tr>
                                    </template>
                                </x-table>
                            </div>
                        </template>
                    </section>
                </div>
            </div>
        </aside>
    </div>
</div>
@endsection

@push('scripts')
<script>
function paymentDrawer() {
    const base  = @js(url('admin/payments'));
    const today = @js(now()->toDateString());
    const tones    = { 'Lunas': 'ok', 'Belum lunas': 'warn', 'Terlambat': 'bad' };
    const trxTones = { success: 'ok', warning: 'warn', danger: 'bad', info: 'info' };

    return {
        // list filter
        filter: '',
        statuses: @js($rows->pluck('sum.status')->values()),

        // drawer
        isOpen: false, loading: false, error: null, data: null, studentId: null,

        // new-payment form
        selected: [], paidAt: today, today, method: 'tunai', note: '',

        get bills()        { return this.data?.bills ?? []; },
        get transactions() { return this.data?.transactions ?? []; },
        get payable()      { return this.bills.filter(b => b.selectable); },
        get storeUrl()     { return this.data?.student?.id ? `${base}/${this.data.student.id}/transactions` : ''; },
        get visibleCount() { return this.statuses.filter(s => this.matches(s)).length; },
        get total() {
            const ids = this.selected.map(Number);
            return this.bills.filter(b => ids.includes(Number(b.id))).reduce((sum, b) => sum + Number(b.amount || 0), 0);
        },
        get tiles() {
            const s = this.data?.summary ?? {};
            return [
                { label: 'Total Kewajiban', value: s.total },
                { label: 'Total Terbayar',  value: s.paid, class: 'text-ok-fg' },
                { label: 'Sisa Tagihan',    value: s.remaining, class: (s.remaining || 0) > 0 ? 'text-bad-fg' : 'text-muted' },
            ];
        },

        matches(status) { return ! this.filter || status === this.filter; },
        rp(value)       { return 'Rp' + Number(value || 0).toLocaleString('id-ID'); },
        tone(status, pending = false) { return 'tone-' + (pending ? 'warn' : tones[status] ?? 'neutral'); },
        trxTone(color)  { return 'tone-' + (trxTones[color] ?? 'neutral'); },

        async open(id) {
            Object.assign(this, { isOpen: true, loading: true, error: null, studentId: id });
            try {
                const res = await fetch(`${base}/${id}`, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                if (! res.ok) throw new Error('Gagal mengambil data siswa.');
                this.data = await res.json();
                Object.assign(this, { selected: this.payable.slice(0, 1).map(b => b.id), paidAt: today, method: 'tunai', note: '' });
            } catch (err) {
                this.error = err.message || 'Terjadi kesalahan saat memuat data.';
            } finally {
                this.loading = false;
            }
        },
        close() { this.isOpen = false; },

        init() {
            @if ($errors->any() && old('_student'))
                this.open({{ (int) old('_student') }});
            @elseif (request('student'))
                this.open({{ (int) request('student') }});
            @endif
        },
    };
}
</script>
@endpush
