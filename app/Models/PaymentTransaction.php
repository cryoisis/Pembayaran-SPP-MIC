<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One manual payment record (cash or transfer) covering one or more monthly bills.
 * Rows are never deleted; a wrong transaction is cancelled and keeps an audit log.
 */
class PaymentTransaction extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_LABELS = [
        self::STATUS_PENDING => 'Belum dikonfirmasi',
        self::STATUS_CONFIRMED => 'Terkonfirmasi',
        self::STATUS_CANCELLED => 'Dibatalkan',
    ];

    public const STATUS_COLORS = [
        self::STATUS_PENDING => 'warning',
        self::STATUS_CONFIRMED => 'success',
        self::STATUS_CANCELLED => 'secondary',
    ];

    public const METHOD_CASH = 'tunai';

    public const METHOD_TRANSFER = 'transfer';

    public const METHODS = [
        self::METHOD_CASH => 'Tunai',
        self::METHOD_TRANSFER => 'Transfer',
    ];

    protected $fillable = [
        'user_id', 'amount', 'paid_at', 'method', 'note', 'status',
        'created_by', 'confirmed_by', 'confirmed_at',
        'cancelled_by', 'cancelled_at', 'cancel_reason',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'paid_at' => 'date',
            'confirmed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function scopeConfirmed(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_CONFIRMED);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isConfirmed(): bool
    {
        return $this->status === self::STATUS_CONFIRMED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function getMethodLabelAttribute(): string
    {
        return self::METHODS[$this->method] ?? $this->method;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function getStatusColorAttribute(): string
    {
        return self::STATUS_COLORS[$this->status] ?? 'secondary';
    }

    /** "Juli 2026, Agustus 2026" (needs `bills` loaded to avoid N+1). */
    public function getPeriodLabelsAttribute(): string
    {
        return $this->bills->pluck('label')->implode(', ');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    public function bills(): BelongsToMany
    {
        return $this->belongsToMany(Bill::class, 'payment_allocations')
            ->withPivot('amount')
            ->orderBy('bills.period');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(PaymentTransactionLog::class)->latest('id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function confirmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }
}
