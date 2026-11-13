<?php

namespace App\Models;

use App\Services\BillingService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    public const ROLE_ADMIN = 'admin';

    public const ROLE_STUDENT = 'student';

    protected $fillable = ['nisn', 'name', 'password', 'role', 'is_active'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',   // always stored as a hash
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

    public function bills(): HasMany
    {
        return $this->hasMany(Bill::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class);
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
            $w->where('nisn', 'like', "%{$term}%")->orWhere('name', 'like', "%{$term}%");
        }));
    }

    /**
     * Total bill / paid / remaining / status for the bills that are due now.
     * Eager-load `bills.allocations.transaction` when calling this in a loop.
     */
    public function paymentSummary(?PaymentSetting $setting = null): array
    {
        $bills = $this->relationLoaded('bills')
            ? $this->bills
            : $this->bills()->with('allocations.transaction')->get();

        return app(BillingService::class)->summarize($bills, $setting);
    }
}
