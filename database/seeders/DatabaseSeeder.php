<?php

namespace Database\Seeders;

use App\Models\PaymentSetting;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Default admin (secretary). Change the password after first login!
        User::updateOrCreate(
            ['nis' => '0000'],
            [
                'name' => 'Sekretaris',
                'password' => 'admin123',
                'role' => User::ROLE_ADMIN,
                'is_active' => true,
            ]
        );

        // Initial payment period: standard fee Rp1.000.000
        PaymentSetting::firstOrCreate(['is_active' => true], [
            'name' => 'Pembayaran Periode Berjalan',
            'start_date' => today(),
            'due_date' => today()->addDays(30),
            'standard_fee' => PaymentSetting::DEFAULT_FEE,
            'announcement_title' => 'Informasi Pembayaran',
            'announcement_body' => "Pembayaran dilakukan secara manual kepada sekretaris.\nSimpan bukti pembayaran Anda.",
        ]);

        if (app()->environment('local')) {
            $this->call(StudentSeeder::class);
        }
    }
}
