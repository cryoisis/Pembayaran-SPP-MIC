<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * One monthly SPP bill for one student. Paid in full (no installments) by a
 * confirmed transaction that allocates to it.
 * Always eager-load `allocations.transaction` when checking paid status in a loop.
 */
class Bill extends Model
{
    public const MONTHS = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
        7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    protected $fillable = ['user_id', 'fee_rate_id', 'period', 'amount'];

    protected function casts(): array
    {
        return ['period' => 'date', 'amount' => 'integer'];
    }

    public static function labelFor(Carbon $date): string
    {
        return self::MONTHS[$date->month].' '.$date->year;
    }

    public function getLabelAttribute(): string
    {
        return self::labelFor($this->period);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function feeRate(): BelongsTo
    {
        return $this->belongsTo(FeeRate::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    /** Paid = covered by a confirmed (valid) transaction. */
    public function isPaid(): bool
    {
        return $this->allocations->contains(
            fn (PaymentAllocation $a) => $a->transaction?->status === PaymentTransaction::STATUS_CONFIRMED
        );
    }

    /** Recorded but not yet confirmed by the admin (does not count as paid). */
    public function hasPendingTransaction(): bool
    {
        return ! $this->isPaid() && $this->allocations->contains(
            fn (PaymentAllocation $a) => $a->transaction?->status === PaymentTransaction::STATUS_PENDING
        );
    }

    public function dueDate(PaymentSetting $setting): Carbon
    {
        return $setting->dueDateFor($this->period);
    }

    /** Has this bill's payment window opened yet? (= is it an obligation now) */
    public function isObligation(PaymentSetting $setting): bool
    {
        return ! today()->lt($setting->startDateFor($this->period));
    }

    public function isLate(PaymentSetting $setting): bool
    {
        return ! $this->isPaid() && today()->gt($this->dueDate($setting));
    }

    public function statusFor(PaymentSetting $setting): string
    {
        if ($this->isPaid()) {
            return PaymentSetting::STATUS_PAID;
        }

        return $this->isLate($setting) ? PaymentSetting::STATUS_LATE : PaymentSetting::STATUS_UNPAID;
    }
}
