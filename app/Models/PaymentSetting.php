<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Monthly payment schedule (single row): each month's bill opens on `start_day`
 * and is due on `due_day`. Also holds the shared payment-status constants.
 */
class PaymentSetting extends Model
{
    public const STATUS_PAID = 'Lunas';

    public const STATUS_UNPAID = 'Belum lunas';

    public const STATUS_LATE = 'Terlambat';

    public const DEFAULT_START_DAY = 1;

    public const DEFAULT_DUE_DAY = 10;

    protected $fillable = ['start_day', 'due_day'];

    protected function casts(): array
    {
        return ['start_day' => 'integer', 'due_day' => 'integer'];
    }

    /** The schedule in use (created with defaults if none exists yet). */
    public static function current(): self
    {
        return static::query()->first() ?? static::create([
            'start_day' => self::DEFAULT_START_DAY,
            'due_day' => self::DEFAULT_DUE_DAY,
        ]);
    }

    /** Date a month's payment window opens. */
    public function startDateFor(Carbon $period): Carbon
    {
        return $this->dayOf($period, $this->start_day);
    }

    /** Due date (deadline) of a month's bill. */
    public function dueDateFor(Carbon $period): Carbon
    {
        return $this->dayOf($period, $this->due_day);
    }

    private function dayOf(Carbon $period, int $day): Carbon
    {
        $month = $period->copy()->startOfMonth();

        return $month->copy()->day(min($day, $month->daysInMonth));
    }

    public static function statusColor(string $status): string
    {
        return match ($status) {
            self::STATUS_PAID => 'success',
            self::STATUS_UNPAID => 'warning',
            self::STATUS_LATE => 'danger',
            default => 'secondary',
        };
    }
}
