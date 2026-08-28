<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\QaRemediationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class QaRemediationSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_isolated_verified_accounts_for_the_retest_role_matrix(): void
    {
        $this->seed(QaRemediationSeeder::class);
        $this->seed(QaRemediationSeeder::class);

        foreach ([
            'qa.patient.a.20260828@example.test' => 'patient',
            'qa.patient.b.20260828@example.test' => 'patient',
            'qa.moderator.20260828@example.test' => 'moderator',
            'qa.admin.20260828@example.test' => 'admin',
            'qa.power.admin.20260828@example.test' => 'power_admin',
        ] as $email => $role) {
            $user = User::where('email', $email)->sole();

            $this->assertSame($role, $user->role->value);
            $this->assertTrue($user->is_active);
            $this->assertNotNull($user->email_verified_at);
            $this->assertNotNull($user->account_claimed_at);
        }

        $this->assertDatabaseCount('users', 5);
    }

    public function test_it_refuses_to_create_qa_accounts_in_production(): void
    {
        $this->app['env'] = 'production';

        $this->expectException(RuntimeException::class);
        $this->app->call([new QaRemediationSeeder, 'run']);
    }
}
