<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    public const ROLE_ADMIN = 'admin';
    public const ROLE_STUDENT = 'student';

    protected $fillable = ['nis', 'name', 'password', 'role', 'is_active'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password'  => 'hashed',   // always stored as a hash
            'is_active' => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isStudent(): bool
    {
        return $this->role === self::ROLE_STUDENT;
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class);
    }

    public function feeAdjustments(): HasMany
    {
        return $this->hasMany(FeeAdjustment::class);
    }

    public function scopeStudents(Builder $query): Builder
    {
        return $query->where('role', self::ROLE_STUDENT);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        return $query->when($term, fn ($q) => $q->where(function ($w) use ($term) {
            $w->where('nis', 'like', "%{$term}%")->orWhere('name', 'like', "%{$term}%");
        }));
    }

    /** Eager-load confirmed payment total + adjustment total for a period (avoids N+1). */
    public function scopeWithPaymentTotals(Builder $query, PaymentSetting $period): Builder
    {
        return $query
            ->withSum(['transactions as paid_total' => fn ($t) => $t
                ->where('payment_setting_id', $period->id)
                ->where('status', PaymentTransaction::STATUS_CONFIRMED)], 'amount')
            ->withSum(['feeAdjustments as adjustment_total' => fn ($a) => $a
                ->where('payment_setting_id', $period->id)], 'amount');
    }

    /**
     * Total obligation = standard fee +/- adjustments.
     * Paid = confirmed transactions only. Remaining = total - paid.
     */
    public function paymentSummary(?PaymentSetting $period = null): array
    {
        $period ??= PaymentSetting::current();

        $adjustments = array_key_exists('adjustment_total', $this->attributes)
            ? (int) $this->attributes['adjustment_total']
            : (int) $this->feeAdjustments()->where('payment_setting_id', $period->id)->sum('amount');

        $paid = array_key_exists('paid_total', $this->attributes)
            ? (int) $this->attributes['paid_total']
            : (int) $this->transactions()
                ->where('payment_setting_id', $period->id)
                ->where('status', PaymentTransaction::STATUS_CONFIRMED)
                ->sum('amount');

        $total = max(0, $period->standard_fee + $adjustments);

        return [
            'standard'    => (int) $period->standard_fee,
            'adjustments' => $adjustments,
            'total'       => $total,
            'paid'        => $paid,
            'remaining'   => max(0, $total - $paid),
            'status'      => $period->statusFor($total, $paid),
        ];
    }
}
