<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_registration_creates_only_a_patient_and_sends_verification(): void
    {
        Notification::fake();
        $response = $this->withHeader('Origin', 'http://localhost')->postJson('/api/auth/register', [
            'name' => 'Ada Patient', 'email' => 'ADA@EXAMPLE.TEST', 'phone' => '08012345678',
            'password' => 'Strong!Pass123', 'password_confirmation' => 'Strong!Pass123', 'role' => 'power_admin',
        ]);

        $response->assertCreated()->assertJsonPath('user.email', 'ada@example.test');
        $user = User::whereEmail('ada@example.test')->firstOrFail();
        $this->assertSame(UserRole::Patient, $user->role);
        $this->assertNotNull($user->account_claimed_at);
        Notification::assertSentTo($user, VerifyEmail::class);
        $this->assertDatabaseHas('audit_logs', ['actor_id' => $user->id, 'action' => 'identity.registered']);
    }

    public function test_active_user_can_sign_in_and_disabled_user_cannot(): void
    {
        $active = User::factory()->create(['password' => 'Strong!Pass123', 'account_claimed_at' => now()]);
        $this->withHeader('Origin', 'http://localhost')->postJson('/api/auth/login', ['email' => $active->email, 'password' => 'Strong!Pass123'])->assertOk();
        $this->assertAuthenticatedAs($active);

        auth()->logout();
        $disabled = User::factory()->create(['password' => 'Strong!Pass123', 'is_active' => false, 'account_claimed_at' => now()]);
        $this->withHeader('Origin', 'http://localhost')->postJson('/api/auth/login', ['email' => $disabled->email, 'password' => 'Strong!Pass123'])->assertUnprocessable();
        $this->assertGuest();
    }

    public function test_password_reset_request_does_not_disclose_account_existence(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $known = $this->postJson('/api/auth/forgot-password', ['email' => $user->email]);
        $unknown = $this->postJson('/api/auth/forgot-password', ['email' => 'unknown@example.test']);

        $known->assertOk();
        $unknown->assertOk();
        $this->assertSame($known->json('message'), $unknown->json('message'));
        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_mobile_token_is_scoped_and_revocable(): void
    {
        $user = User::factory()->create(['password' => 'Strong!Pass123', 'account_claimed_at' => now()]);
        $response = $this->postJson('/api/auth/mobile-token', ['email' => $user->email, 'password' => 'Strong!Pass123', 'device_name' => 'Patient iPhone']);

        $response->assertOk()->assertJsonPath('token_type', 'Bearer')->assertJsonFragment(['appointments:self']);
        $this->assertSame(1, PersonalAccessToken::count());
        $token = $response->json('token');
        $this->withToken($token)->postJson('/api/auth/logout')->assertOk();
        $this->assertSame(0, PersonalAccessToken::count());
    }

    public function test_user_can_reset_password_and_existing_mobile_tokens_are_revoked(): void
    {
        $user = User::factory()->create();
        $user->createToken('Old phone');
        $token = Password::createToken($user);

        $this->postJson('/api/auth/reset-password', ['email' => $user->email, 'token' => $token, 'password' => 'NewStrong!Pass123', 'password_confirmation' => 'NewStrong!Pass123'])->assertOk();

        $this->assertTrue(Hash::check('NewStrong!Pass123', $user->fresh()->password));
        $this->assertSame(0, $user->tokens()->count());
        $this->assertDatabaseHas('audit_logs', ['actor_id' => $user->id, 'action' => 'identity.password_reset']);
    }

    public function test_patient_cannot_access_staff_route(): void
    {
        $patient = User::factory()->create(['role' => UserRole::Patient]);
        $staff = User::factory()->create(['role' => UserRole::Moderator]);

        Sanctum::actingAs($patient);
        $this->getJson('/api/staff/ping')->assertForbidden();
        Sanctum::actingAs($staff);
        $this->getJson('/api/me')->assertOk();
        $this->getJson('/api/staff/ping')->assertOk();
    }
}
