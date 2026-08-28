<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class QaRemediationSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException('QA remediation accounts must never be seeded in production.');
        }

        foreach ([
            ['QA Patient A 20260828', 'qa.patient.a.20260828@example.test', 'patient'],
            ['QA Patient B 20260828', 'qa.patient.b.20260828@example.test', 'patient'],
            ['QA Moderator 20260828', 'qa.moderator.20260828@example.test', 'moderator'],
            ['QA Admin 20260828', 'qa.admin.20260828@example.test', 'admin'],
            ['QA Power Admin 20260828', 'qa.power.admin.20260828@example.test', 'power_admin'],
        ] as [$name, $email, $role]) {
            User::updateOrCreate(['email' => $email], [
                'name' => $name,
                'role' => $role,
                'password' => 'QaOnly!20260828',
                'is_active' => true,
                'email_verified_at' => now(),
                'account_claimed_at' => now(),
            ]);
        }
    }
}
