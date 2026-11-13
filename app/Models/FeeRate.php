<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/** SPP rate (per month) for one academic year, with the period in which it applies. */
class FeeRate extends Model
{
    public const DEFAULT_AMOUNT = 1000000;

    protected $fillable = ['academic_year', 'amount', 'effective_from', 'effective_to'];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'effective_from' => 'date',
            'effective_to' => 'date',
        ];
    }

    public function bills(): HasMany
    {
        return $this->hasMany(Bill::class);
    }

    public function scopeCovering(Builder $query, $date): Builder
    {
        return $query->whereDate('effective_from', '<=', $date)->whereDate('effective_to', '>=', $date);
    }

    /** The rate in force today (null if none is configured). */
    public static function current(): ?self
    {
        return static::covering(today())->first();
    }

    /** First day of every month covered by this rate. @return Carbon[] */
    public function periods(): array
    {
        $periods = [];
        $month = $this->effective_from->copy()->startOfMonth();
        $last = $this->effective_to->copy()->startOfMonth();

        while ($month->lte($last)) {
            $periods[] = $month->copy();
            $month->addMonthNoOverflow();
        }

        return $periods;
    }

    /** A rate that already has transactions is locked so history stays traceable. */
    public function hasTransactions(): bool
    {
        return PaymentAllocation::whereHas('bill', fn ($b) => $b->where('fee_rate_id', $this->id))->exists();
    }

    public function state(): string
    {
        if ($this->effective_from->gt(today())) {
            return 'Akan datang';
        }

        return $this->effective_to->lt(today()) ? 'Sebelumnya' : 'Aktif';
    }
}
