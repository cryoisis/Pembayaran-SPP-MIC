<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StudentSeederTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_it_seeds_only_admin(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseHas('users', [
            'nisn' => 'admin',
            'role' => User::ROLE_ADMIN,
            'name' => 'Bendahara',
        ]);

        $this->assertDatabaseCount('users', 1);
        $this->assertSame(0, User::students()->count());

        $admin = User::where('nisn', 'admin')->firstOrFail();
        $this->assertTrue(Hash::check('admin123', $admin->password));
    }
}
