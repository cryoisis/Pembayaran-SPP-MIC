<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One manual payment record. Each installment is its own row; rows are never overwritten. */
class PaymentTransaction extends Model
{
    public const STATUS_PENDING   = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';

    protected $fillable = [
        'user_id', 'payment_setting_id', 'amount', 'paid_at', 'note',
        'status', 'confirmed_by', 'confirmed_at',
    ];

    protected function casts(): array
    {
        return [
            'amount'       => 'integer',
            'paid_at'      => 'date',
            'confirmed_at' => 'datetime',
        ];
    }

    public function scopeConfirmed(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_CONFIRMED);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(PaymentSetting::class, 'payment_setting_id');
    }

    public function confirmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }
}
