<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoAccountsSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_one_verified_active_account_for_every_role(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        foreach ([
            'patient@example.test' => 'patient',
            'moderator@example.test' => 'moderator',
            'admin@example.test' => 'admin',
            'power.admin@example.test' => 'power_admin',
        ] as $email => $role) {
            $this->assertDatabaseCount('users', 4);
            $this->assertDatabaseHas('users', ['email' => $email, 'role' => $role, 'is_active' => true]);
            $this->assertNotNull(\App\Models\User::where('email', $email)->sole()->email_verified_at);
        }
    }
}
