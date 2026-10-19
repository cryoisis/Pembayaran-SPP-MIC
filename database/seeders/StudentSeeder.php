<?php

namespace Database\Seeders;

use App\Models\FeeAdjustment;
use App\Models\PaymentSetting;
use App\Models\PaymentTransaction;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/** Optional dummy data: php artisan db:seed --class=StudentSeeder */
class StudentSeeder extends Seeder
{
    private const LEGACY_DEMO_NIS = ['12346', '12347', '12348', '12349', '12350'];

    public function run(): void
    {
        $period = PaymentSetting::current();
        $admin = User::where('role', User::ROLE_ADMIN)->first();

        DB::transaction(function () use ($period, $admin): void {
            $legacyStudentIds = User::query()
                ->where('role', User::ROLE_STUDENT)
                ->whereIn('nis', self::LEGACY_DEMO_NIS)
                ->pluck('id');

            PaymentTransaction::whereIn('user_id', $legacyStudentIds)->delete();
            FeeAdjustment::whereIn('user_id', $legacyStudentIds)->delete();
            User::whereKey($legacyStudentIds)->delete();

            $student = User::updateOrCreate(
                ['nis' => '12345'],
                ['name' => 'Andi Pratama', 'password' => 'siswa123', 'role' => User::ROLE_STUDENT, 'is_active' => true]
            );

            PaymentTransaction::firstOrCreate(
                ['user_id' => $student->id, 'payment_setting_id' => $period->id, 'amount' => 1000000],
                [
                    'paid_at' => today()->subDays(3),
                    'note' => 'Pelunasan',
                    'status' => PaymentTransaction::STATUS_CONFIRMED,
                    'confirmed_by' => $admin?->id,
                    'confirmed_at' => now(),
                ]
            );
        });
    }
}
