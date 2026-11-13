<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_can_login_with_seeded_credentials(): void
    {
        $this->seed(DatabaseSeeder::class);

        $response = $this->post('/login', [
            'nisn' => 'admin',
            'password' => 'admin123',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticated();
        $this->assertTrue(auth()->user()->isAdmin());
    }

    public function test_student_can_login(): void
    {
        $this->seed(DatabaseSeeder::class);

        User::create([
            'nisn' => '20260001',
            'name' => 'Siswa Test',
            'password' => 'siswa123',
            'role' => User::ROLE_STUDENT,
            'is_active' => true,
        ]);

        $response = $this->post('/login', [
            'nisn' => '20260001',
            'password' => 'siswa123',
        ]);

        $response->assertRedirect(route('student.dashboard'));
        $this->assertAuthenticated();
        $this->assertTrue(auth()->user()->isStudent());
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        $this->seed(DatabaseSeeder::class);

        $response = $this->from('/login')->post('/login', [
            'nisn' => 'admin',
            'password' => 'wrong-password',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('nisn');
        $this->assertGuest();
    }
}
