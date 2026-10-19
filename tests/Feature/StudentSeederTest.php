<?php

namespace Tests\Feature;

use App\Models\FeeAdjustment;
use App\Models\PaymentSetting;
use App\Models\PaymentTransaction;
use App\Models\User;
use Database\Seeders\StudentSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StudentSeederTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_it_keeps_one_demo_student_and_removes_legacy_demo_students_and_their_payment_data(): void
    {
        $period = PaymentSetting::create([
            'name' => 'Test period',
            'start_date' => today(),
            'due_date' => today()->addDays(30),
            'standard_fee' => PaymentSetting::DEFAULT_FEE,
            'is_active' => true,
        ]);

        $legacyStudents = collect(['12346', '12347', '12348', '12349', '12350'])->mapWithKeys(function (string $nis) use ($period): array {
            $student = User::create([
                'nis' => $nis,
                'name' => 'Legacy demo student',
                'password' => 'old-password',
                'role' => User::ROLE_STUDENT,
                'is_active' => true,
            ]);

            PaymentTransaction::create([
                'user_id' => $student->id,
                'payment_setting_id' => $period->id,
                'amount' => 100000,
                'paid_at' => today(),
                'status' => PaymentTransaction::STATUS_CONFIRMED,
            ]);

            if ($nis === '12347') {
                FeeAdjustment::create([
                    'user_id' => $student->id,
                    'payment_setting_id' => $period->id,
                    'amount' => -10000,
                    'reason' => 'Legacy demo adjustment',
                ]);
            }

            return [$nis => $student->id];
        });

        $unrelatedStudent = User::create([
            'nis' => '90001',
            'name' => 'Unrelated student',
            'password' => 'password',
            'role' => User::ROLE_STUDENT,
            'is_active' => true,
        ]);

        $this->seed(StudentSeeder::class);

        $this->assertDatabaseHas('users', ['nis' => '12345', 'name' => 'Andi Pratama']);
        $this->assertDatabaseHas('users', ['id' => $unrelatedStudent->id]);

        foreach ($legacyStudents as $id) {
            $this->assertDatabaseMissing('users', ['id' => $id]);
            $this->assertDatabaseMissing('payment_transactions', ['user_id' => $id]);
            $this->assertDatabaseMissing('fee_adjustments', ['user_id' => $id]);
        }

        $this->assertDatabaseCount('payment_transactions', 1);
        $this->assertDatabaseCount('fee_adjustments', 0);
        $this->assertSame(
            ['12345', '90001'],
            User::students()->orderBy('nis')->pluck('nis')->all()
        );
        $this->assertTrue(Hash::check('siswa123', User::where('nis', '12345')->firstOrFail()->password));
    }
}
