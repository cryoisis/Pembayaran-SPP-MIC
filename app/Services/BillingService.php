<?php

namespace App\Services;

use App\Models\Bill;
use App\Models\FeeRate;
use App\Models\PaymentSetting;
use App\Models\User;
use Illuminate\Support\Collection;

/** Generates monthly bills from fee rates and computes payment summaries/status. */
class BillingService
{
    /**
     * Create any missing monthly bills for every fee rate that has started.
     * A student is billed from the month the account was created (never for months before).
     * Safe to call repeatedly. Returns the number of bills created.
     */
    public function syncBills(?User $student = null): int
    {
        $rates = FeeRate::whereDate('effective_from', '<=', today())->get();
        if ($rates->isEmpty()) {
            return 0;
        }

        $students = $student
            ? collect([$student])
            : User::students()->active()->get(['id', 'created_at']);
        if ($students->isEmpty()) {
            return 0;
        }

        $existing = Bill::whereIn('user_id', $students->pluck('id'))
            ->get(['user_id', 'period'])
            ->map(fn (Bill $b) => $b->user_id.'|'.$b->period->toDateString())
            ->flip();

        $now = now();
        $rows = [];

        foreach ($students as $s) {
            $joined = $s->created_at->copy()->startOfMonth();

            foreach ($rates as $rate) {
                foreach ($rate->periods() as $period) {
                    if ($period->lt($joined) || $existing->has($s->id.'|'.$period->toDateString())) {
                        continue;
                    }
                    $rows[] = [
                        'user_id' => $s->id,
                        'fee_rate_id' => $rate->id,
                        'period' => $period->toDateString(),
                        'amount' => $rate->amount,      // snapshot of the rate
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            Bill::insert($chunk);
        }

        return count($rows);
    }

    /**
     * Summary of one student's bills.
     *  - total     : sum of all active bills of the student
     *  - paid      : sum of bills covered by confirmed transactions
     *  - remaining : total - paid
     *  - status    : Lunas / Belum lunas / Terlambat
     *  - next      : earliest unpaid bill
     *
     * @param  Collection<int, Bill>  $bills  with `allocations.transaction` loaded
     */
    public function summarize(Collection $bills, ?PaymentSetting $setting = null): array
    {
        $setting ??= PaymentSetting::current();

        $total = (int) $bills->sum('amount');
        $paid = (int) $bills->filter(fn (Bill $b) => $b->isPaid())->sum('amount');
        $remaining = max(0, $total - $paid);

        $unpaid = $bills->reject(fn (Bill $b) => $b->isPaid())->sortBy(fn (Bill $b) => $b->period->timestamp)->values();
        $dueUnpaid = $unpaid->filter(fn (Bill $b) => $b->isObligation($setting))->values();
        $late = $dueUnpaid->filter(fn (Bill $b) => $b->isLate($setting))->values();

        $status = $late->isNotEmpty()
            ? PaymentSetting::STATUS_LATE
            : ($dueUnpaid->isNotEmpty() ? PaymentSetting::STATUS_UNPAID : PaymentSetting::STATUS_PAID);

        $next = $unpaid->first();

        return [
            'total' => $total,
            'paid' => $paid,
            'remaining' => $remaining,
            'status' => $status,
            'unpaid' => $unpaid,
            'late' => $late,
            'next' => $next,
        ];
    }
}
