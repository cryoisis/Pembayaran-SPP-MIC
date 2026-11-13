<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Links a transaction to the monthly bill(s) it pays. */
class PaymentAllocation extends Model
{
    protected $fillable = ['payment_transaction_id', 'bill_id', 'amount'];

    protected function casts(): array
    {
        return ['amount' => 'integer'];
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(PaymentTransaction::class, 'payment_transaction_id');
    }

    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }
}
