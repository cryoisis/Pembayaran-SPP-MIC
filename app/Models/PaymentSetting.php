<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** One row = one payment period (schedule, deadline, standard fee, announcement). */
class PaymentSetting extends Model
{
    public const STATUS_UNPAID           = 'Belum bayar';
    public const STATUS_PAID             = 'Sudah bayar';
    public const STATUS_LATE             = 'Terlambat bayar';
    public const STATUS_INSTALLMENT      = 'Mencicil';
    public const STATUS_LATE_INSTALLMENT = 'Terlambat mencicil';

    public const DEFAULT_FEE = 1000000;

    protected $fillable = [
        'name', 'start_date', 'due_date', 'standard_fee',
        'announcement_title', 'announcement_body', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'start_date'   => 'date',
            'due_date'     => 'date',
            'standard_fee' => 'integer',
            'is_active'    => 'boolean',
        ];
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class);
    }

    public function feeAdjustments(): HasMany
    {
        return $this->hasMany(FeeAdjustment::class);
    }

    /** The running period (created with defaults if none exists yet). */
    public static function current(): self
    {
        return static::where('is_active', true)->latest('id')->first()
            ?? static::create([
                'name'         => 'Pembayaran Periode Berjalan',
                'start_date'   => today(),
                'due_date'     => today()->addDays(30),
                'standard_fee' => self::DEFAULT_FEE,
                'is_active'    => true,
            ]);
    }

    public function isOverdue(): bool
    {
        return today()->gt($this->due_date);
    }

    public function statusFor(int $total, int $paid): string
    {
        if ($paid >= $total) {
            return self::STATUS_PAID;
        }
        if ($paid <= 0) {
            return $this->isOverdue() ? self::STATUS_LATE : self::STATUS_UNPAID;
        }
        return $this->isOverdue() ? self::STATUS_LATE_INSTALLMENT : self::STATUS_INSTALLMENT;
    }

    public static function statusColor(string $status): string
    {
        return match ($status) {
            self::STATUS_PAID             => 'success',
            self::STATUS_INSTALLMENT      => 'info',
            self::STATUS_UNPAID           => 'secondary',
            self::STATUS_LATE             => 'danger',
            self::STATUS_LATE_INSTALLMENT => 'warning',
            default                       => 'secondary',
        };
    }
}
