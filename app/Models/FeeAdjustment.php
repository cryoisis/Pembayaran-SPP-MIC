<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Custom fee change for one student. amount > 0 = extra charge, amount < 0 = reduction. */
class FeeAdjustment extends Model
{
    protected $fillable = ['user_id', 'payment_setting_id', 'amount', 'reason', 'created_by'];

    protected function casts(): array
    {
        return ['amount' => 'integer'];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(PaymentSetting::class, 'payment_setting_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
