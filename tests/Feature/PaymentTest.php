<?php

namespace Tests\Feature;

use App\Models\PaymentTransaction;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use LazilyRefreshDatabase;

    private User $admin;

    private User $student;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);

        $this->admin = User::where('role', User::ROLE_ADMIN)->firstOrFail();
        $this->student = User::create([
            'nisn' => '20261001',
            'name' => 'Siswa Baru',
            'password' => 'password123',
            'role' => User::ROLE_STUDENT,
            'is_active' => true,
        ]);
    }

    public function test_admin_can_view_payments_index(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.payments.index'));

        $response->assertOk();
        $response->assertSee('Pembayaran SPP');
        $response->assertSee('20261001');
        $response->assertSee('Siswa Baru');
    }

    public function test_payments_show_returns_json_when_requested_via_ajax(): void
    {
        $response = $this->actingAs($this->admin)->getJson(route('admin.payments.show', $this->student));

        $response->assertOk();
        $response->assertJsonStructure([
            'student' => ['id', 'nisn', 'name'],
            'rate' => ['academic_year', 'amount'],
            'summary' => ['total', 'paid', 'remaining', 'status'],
            'bills',
            'transactions',
        ]);
        $response->assertJsonPath('student.nisn', '20261001');
        $response->assertJsonPath('student.name', 'Siswa Baru');
    }

    public function test_payments_show_redirects_to_index_when_visited_directly_in_browser(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.payments.show', $this->student));

        $response->assertRedirect(route('admin.payments.index', ['student' => $this->student->id]));
    }

    public function test_admin_can_record_payment(): void
    {
        // First fetch student bills to get a selectable bill ID
        $json = $this->actingAs($this->admin)->getJson(route('admin.payments.show', $this->student));
        $bills = $json->json('bills');
        $this->assertNotEmpty($bills);

        $selectableBill = collect($bills)->firstWhere('selectable', true);
        $this->assertNotNull($selectableBill);

        $response = $this->actingAs($this->admin)->post(
            route('admin.payments.transactions.store', $this->student),
            [
                'bill_ids' => [$selectableBill['id']],
                'amount' => $selectableBill['amount'],
                'paid_at' => today()->toDateString(),
                'method' => PaymentTransaction::METHOD_CASH,
                'action' => 'confirm',
            ]
        );

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('payment_transactions', [
            'user_id' => $this->student->id,
            'amount' => $selectableBill['amount'],
            'method' => PaymentTransaction::METHOD_CASH,
            'status' => PaymentTransaction::STATUS_CONFIRMED,
        ]);
    }
}
