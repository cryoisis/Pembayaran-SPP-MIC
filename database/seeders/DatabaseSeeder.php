<?php

namespace Database\Seeders;

use App\Models\FeeRate;
use App\Models\PaymentSetting;
use App\Models\User;
use App\Services\BillingService;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Default admin (bendahara). Change the password after first login!
        User::updateOrCreate(
            ['nisn' => env('ADMIN_NISN', 'admin')],
            [
                'name' => 'Bendahara',
                'password' => env('ADMIN_PASSWORD', 'admin123'),
                'role' => User::ROLE_ADMIN,
                'is_active' => true,
            ]
        );

        // Monthly schedule (opens day 1, due day 10) - editable on the admin dashboard.
        PaymentSetting::current();

        // SPP rate of the running academic year (July - June) - editable under "Tarif SPP".
        $startYear = today()->month >= 7 ? today()->year : today()->year - 1;
        FeeRate::firstOrCreate(
            ['academic_year' => $startYear.'/'.($startYear + 1)],
            [
                'amount' => FeeRate::DEFAULT_AMOUNT,
                'effective_from' => "{$startYear}-07-01",
                'effective_to' => ($startYear + 1).'-06-30',
            ]
        );

        app(BillingService::class)->syncBills();
    }
}
