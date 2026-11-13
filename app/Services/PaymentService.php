<?php

namespace App\Services;

use App\Models\Bill;
use App\Models\PaymentAllocation;
use App\Models\PaymentTransaction;
use App\Models\PaymentTransactionLog;
use App\Models\User;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Records, corrects, confirms and cancels manual payments.
 * A payment is always for whole monthly bills (several months allowed, no installments).
 */
class PaymentService
{
    /** Bills of a student that no pending/confirmed transaction covers yet. */
    public function bookableBills(User $student, ?PaymentTransaction $except = null): Collection
    {
        return $this->filterBookable(
            $student->bills()->with('allocations.transaction')->orderBy('period')->get(),
            $except
        );
    }

    /** @param Collection<int, Bill> $bills with `allocations.transaction` loaded */
    public function filterBookable(Collection $bills, ?PaymentTransaction $except = null): Collection
    {
        return $bills
            ->reject(fn (Bill $bill) => $bill->allocations->contains(
                fn (PaymentAllocation $a) => $a->transaction
                    && $a->transaction->status !== PaymentTransaction::STATUS_CANCELLED
                    && $a->payment_transaction_id != $except?->id
            ))
            ->sortBy(fn (Bill $b) => $b->period->timestamp)
            ->values();
    }

    /** Shared form validation: chosen months are payable and the amount equals their total. */
    public function validateSelection(Validator $v, User $student, array $billIds, int $amount, ?PaymentTransaction $except = null): void
    {
        $available = $this->bookableBills($student, $except)->keyBy('id');
        $selected = collect($billIds)->map(fn ($id) => (int) $id)->unique();

        if ($selected->reject(fn ($id) => $available->has($id))->isNotEmpty()) {
            $v->errors()->add('bill_ids', 'Ada periode yang tidak valid atau sudah tercakup transaksi lain.');

            return;
        }

        $sum = (int) $selected->sum(fn ($id) => $available[$id]->amount);

        if ($amount !== $sum) {
            $v->errors()->add(
                'amount',
                'Nominal harus sama dengan total tagihan periode terpilih (Rp'.number_format($sum, 0, ',', '.').'). Pembayaran cicilan tidak tersedia.'
            );
        }
    }

    public function create(User $student, array $data, User $admin, bool $confirm = false): PaymentTransaction
    {
        return DB::transaction(function () use ($student, $data, $admin, $confirm) {
            $bills = $student->bills()->whereIn('id', $data['bill_ids'])->orderBy('period')->get();

            $trx = PaymentTransaction::create([
                'user_id' => $student->id,
                'amount' => (int) $bills->sum('amount'),
                'paid_at' => $data['paid_at'],
                'method' => $data['method'],
                'note' => $data['note'] ?? null,
                'status' => PaymentTransaction::STATUS_PENDING,
                'created_by' => $admin->id,
            ]);

            $this->allocate($trx, $bills);
            $trx->load('bills');
            $this->log($trx, $admin, 'dibuat', $this->describe($trx));

            if ($confirm) {
                $this->confirm($trx, $admin);
            }

            return $trx;
        });
    }

    public function confirm(PaymentTransaction $trx, User $admin): void
    {
        $trx->update([
            'status' => PaymentTransaction::STATUS_CONFIRMED,
            'confirmed_by' => $admin->id,
            'confirmed_at' => now(),
        ]);

        $this->log($trx, $admin, 'dikonfirmasi', 'Transaksi dinyatakan sah.');
    }

    /** Correct a recording mistake. Only this transaction (and its own allocations) is touched. */
    public function update(PaymentTransaction $trx, array $data, User $admin): void
    {
        DB::transaction(function () use ($trx, $data, $admin) {
            $trx->load('bills');
            $before = $this->describe($trx);

            $bills = $trx->student->bills()->whereIn('id', $data['bill_ids'])->orderBy('period')->get();

            $trx->update([
                'amount' => (int) $bills->sum('amount'),
                'paid_at' => $data['paid_at'],
                'method' => $data['method'],
                'note' => $data['note'] ?? null,
            ]);

            $trx->allocations()->delete();
            $this->allocate($trx, $bills);
            $trx->load('bills');

            $this->log($trx, $admin, 'diubah', "Sebelum: {$before}\nSesudah: ".$this->describe($trx));
        });
    }

    /** "Delete" = cancel: the row stays for traceability but no longer counts as a payment. */
    public function cancel(PaymentTransaction $trx, User $admin, string $reason): void
    {
        DB::transaction(function () use ($trx, $admin, $reason) {
            $trx->update([
                'status' => PaymentTransaction::STATUS_CANCELLED,
                'cancelled_by' => $admin->id,
                'cancelled_at' => now(),
                'cancel_reason' => $reason,
            ]);

            $this->log($trx, $admin, 'dibatalkan', "Alasan: {$reason}");
        });
    }

    private function allocate(PaymentTransaction $trx, Collection $bills): void
    {
        foreach ($bills as $bill) {
            PaymentAllocation::create([
                'payment_transaction_id' => $trx->id,
                'bill_id' => $bill->id,
                'amount' => $bill->amount,
            ]);
        }
    }

    private function describe(PaymentTransaction $trx): string
    {
        return sprintf(
            'Rp%s, tgl %s, %s, periode: %s%s',
            number_format($trx->amount, 0, ',', '.'),
            $trx->paid_at->format('d/m/Y'),
            $trx->method_label,
            $trx->bills->pluck('label')->implode(', '),
            $trx->note ? ", ket: {$trx->note}" : ''
        );
    }

    private function log(PaymentTransaction $trx, User $admin, string $action, string $detail): void
    {
        PaymentTransactionLog::create([
            'payment_transaction_id' => $trx->id,
            'user_id' => $admin->id,
            'action' => $action,
            'detail' => $detail,
        ]);
    }
}
