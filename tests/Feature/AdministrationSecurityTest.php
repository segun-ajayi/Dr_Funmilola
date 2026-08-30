<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdministrationSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_management_role_boundary_and_minimum_list_are_enforced(): void
    {
        $patient = User::factory()->create();
        $staff = User::factory()->create(['role' => UserRole::Moderator]);
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $power = User::factory()->create(['role' => UserRole::PowerAdmin]);

        Sanctum::actingAs($staff);
        $this->getJson('/api/staff/accounts')->assertForbidden();
        Sanctum::actingAs($admin);
        $this->getJson('/api/staff/accounts')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $patient->id)->assertJsonMissingPath('data.0.password');
        $this->patchJson("/api/staff/accounts/{$staff->id}", ['name' => 'Not allowed'])->assertForbidden();
        Sanctum::actingAs($power);
        $this->getJson('/api/staff/accounts?role=moderator')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $staff->id);
    }

    public function test_admin_can_invite_and_edit_only_patient_accounts(): void
    {
        Notification::fake();
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/staff/accounts', ['name' => 'New Patient', 'email' => 'NEW.PATIENT@EXAMPLE.TEST', 'phone' => '08020000000', 'role' => 'patient'])->assertCreated();
        $patient = User::findOrFail($response->json('data.id'));
        $this->assertNull($patient->account_claimed_at);
        $this->assertNull($patient->email_verified_at);
        Notification::assertSentTo($patient, ResetPassword::class);
        $this->patchJson("/api/staff/accounts/{$patient->id}", ['name' => 'Updated Patient', 'phone' => '08021111111'])->assertOk();
        $this->postJson('/api/staff/accounts', ['name' => 'Staff Attempt', 'email' => 'staff@example.test', 'role' => 'moderator'])->assertForbidden();
        $this->assertDatabaseHas('audit_logs', ['actor_id' => $admin->id, 'action' => 'identity.account_invited', 'subject_id' => $patient->id]);
        $this->assertDatabaseHas('audit_logs', ['actor_id' => $admin->id, 'action' => 'identity.account_updated', 'subject_id' => $patient->id]);
    }

    public function test_sensitive_changes_require_password_protect_power_admin_invariants_and_revoke_access(): void
    {
        $actor = User::factory()->create(['role' => UserRole::PowerAdmin]);
        $otherPower = User::factory()->create(['role' => UserRole::PowerAdmin]);
        $target = User::factory()->create(['role' => UserRole::Moderator]);
        $target->createToken('Clinic tablet');
        DB::table('sessions')->insert($this->sessionRow('target-session', $target->id));
        Sanctum::actingAs($actor);

        $this->patchJson("/api/staff/accounts/{$target->id}", ['role' => 'admin'])->assertUnprocessable()->assertJsonValidationErrors('current_password');
        $this->patchJson("/api/staff/accounts/{$target->id}", ['role' => 'admin', 'current_password' => 'password'])->assertOk()->assertJsonPath('data.role', 'admin');
        $this->patchJson("/api/staff/accounts/{$target->id}", ['is_active' => false, 'current_password' => 'password'])->assertOk();
        $this->assertSame(0, $target->tokens()->count());
        $this->assertDatabaseMissing('sessions', ['id' => 'target-session']);
        $this->patchJson("/api/staff/accounts/{$actor->id}", ['role' => 'admin', 'current_password' => 'password'])->assertUnprocessable();

        $otherPower->update(['is_active' => false]);
        $this->patchJson("/api/staff/accounts/{$actor->id}", ['is_active' => false, 'current_password' => 'password'])->assertUnprocessable();
        $log = AuditLog::where('action', 'identity.account_updated')->where('subject_id', $target->id)->latest()->firstOrFail();
        $this->assertArrayHasKey('before', $log->metadata);
        $this->assertArrayHasKey('after', $log->metadata);
        $this->assertArrayNotHasKey('current_password', $log->metadata);
    }

    public function test_user_can_list_and_revoke_only_owned_other_browser_sessions(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        DB::table('sessions')->insert([
            $this->sessionRow('owner-one', $owner->id, '203.0.113.25', 'Mozilla/5.0 Chrome/140'),
            $this->sessionRow('owner-two', $owner->id, '203.0.113.26', 'Mozilla/5.0 Firefox/141'),
            $this->sessionRow('other-one', $other->id),
        ]);
        Sanctum::actingAs($owner);

        $response = $this->getJson('/api/me/sessions')->assertOk()->assertJsonCount(2, 'data')->assertJsonPath('data.0.ip_address', '203.0.113.x');
        $ownedReference = collect($response->json('data'))->firstWhere('device', 'Chrome')['reference'];
        $otherReference = hash('sha256', 'other-one');
        $this->deleteJson("/api/me/sessions/{$otherReference}")->assertNotFound();
        $this->deleteJson("/api/me/sessions/{$ownedReference}")->assertOk();
        $this->assertDatabaseMissing('sessions', ['id' => 'owner-one']);
        $this->assertDatabaseHas('sessions', ['id' => 'other-one']);
        $this->assertDatabaseHas('audit_logs', ['actor_id' => $owner->id, 'action' => 'identity.web_session_revoked']);
    }

    public function test_power_admin_can_query_paginated_audit_records_by_actor_action_and_dates(): void
    {
        $power = User::factory()->create(['role' => UserRole::PowerAdmin]);
        $actor = User::factory()->create(['role' => UserRole::Admin]);
        AuditLog::create(['actor_id' => $actor->id, 'action' => 'identity.account_updated', 'subject_type' => User::class, 'subject_id' => $actor->id, 'metadata' => ['fields' => ['role'], 'secret' => 'hidden'], 'created_at' => now()->subDay()]);
        AuditLog::create(['actor_id' => $actor->id, 'action' => 'cms.page_updated', 'subject_type' => User::class, 'subject_id' => $actor->id]);
        Sanctum::actingAs($power);

        $this->getJson('/api/cms/audit-logs?action=identity.&actor_id='.$actor->id.'&from='.now()->subDays(2)->toDateString().'&to='.now()->toDateString())
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.action', 'identity.account_updated')
            ->assertJsonMissingPath('data.0.metadata.secret')
            ->assertJsonStructure(['current_page', 'last_page', 'per_page', 'data']);
    }

    private function sessionRow(string $id, int $userId, string $ip = '127.0.0.1', string $agent = 'Test browser'): array
    {
        return ['id' => $id, 'user_id' => $userId, 'ip_address' => $ip, 'user_agent' => $agent, 'payload' => 'test', 'last_activity' => now()->timestamp];
    }
}
