<?php

namespace Tests\Feature;

use App\Models\FeeRate;
use App\Models\PaymentTransaction;
use App\Models\User;
use App\Services\BillingService;
use App\Services\PaymentService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use LazilyRefreshDatabase;

    private User $admin;

    private User $student1;

    private User $student2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);

        $this->admin = User::where('role', User::ROLE_ADMIN)->firstOrFail();

        $this->student1 = User::create([
            'nisn' => '1001',
            'name' => 'Siswa 1',
            'password' => 'password123',
            'role' => User::ROLE_STUDENT,
            'is_active' => true,
        ]);

        $this->student2 = User::create([
            'nisn' => '1002',
            'name' => 'Siswa 2',
            'password' => 'password123',
            'role' => User::ROLE_STUDENT,
            'is_active' => true,
        ]);

        app(BillingService::class)->syncBills();
    }

    public function test_report_defaults_to_active_academic_year_and_matches_totals(): void
    {
        $rate = FeeRate::current();
        $this->assertNotNull($rate);

        // Record a confirmed payment for student 1 covering 2 bills
        $bills = $this->student1->bills()->take(2)->get();
        $amount = (int) $bills->sum('amount');
        app(PaymentService::class)->create(
            $this->student1,
            [
                'bill_ids' => $bills->pluck('id')->all(),
                'amount' => $amount,
                'paid_at' => today()->toDateString(),
                'method' => PaymentTransaction::METHOD_CASH,
            ],
            $this->admin,
            confirm: true
        );

        $response = $this->actingAs($this->admin)->get(route('admin.reports.index'));

        $response->assertOk();
        $response->assertSee('Laporan Pembayaran SPP');

        $viewData = $response->viewData('summary');
        $this->assertNotNull($viewData);

        // Total tagihan should sum all bills of the students in the academic year
        $totalExpected = (int) ($this->student1->bills()->sum('amount') + $this->student2->bills()->sum('amount'));
        $this->assertSame($totalExpected, $viewData['total']);
        $this->assertSame($amount, $viewData['paid']);
        $this->assertSame($totalExpected - $amount, $viewData['remaining']);
    }

    public function test_summarize_reflects_all_bills_and_confirmed_payments(): void
    {
        // Pay 3 bills for student 1
        $bills = $this->student1->bills()->take(3)->get();
        $amount = (int) $bills->sum('amount');
        app(PaymentService::class)->create(
            $this->student1,
            [
                'bill_ids' => $bills->pluck('id')->all(),
                'amount' => $amount,
                'paid_at' => today()->toDateString(),
                'method' => PaymentTransaction::METHOD_CASH,
            ],
            $this->admin,
            confirm: true
        );

        $summary = $this->student1->paymentSummary();

        $totalBillsAmount = (int) $this->student1->bills()->sum('amount');
        $this->assertSame($totalBillsAmount, $summary['total']);
        $this->assertSame($amount, $summary['paid']);
        $this->assertSame($totalBillsAmount - $amount, $summary['remaining']);
    }
}
