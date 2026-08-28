<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PatientPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_patient_can_update_minimum_profile_and_change_is_audited(): void
    {
        $patient = User::factory()->create();
        Sanctum::actingAs($patient);
        $this->putJson('/api/me/profile', ['name' => 'Ada Patient', 'phone' => '08012345678', 'date_of_birth' => '1990-04-12', 'address' => 'Ile-Ife', 'emergency_contact_name' => 'Tola', 'emergency_contact_phone' => '08022222222', 'preferred_communication' => 'sms'])->assertOk()->assertJsonPath('data.name', 'Ada Patient');
        $this->assertDatabaseHas('patient_profiles', ['user_id' => $patient->id, 'preferred_communication' => 'sms']);
        $this->assertDatabaseHas('audit_logs', ['actor_id' => $patient->id, 'action' => 'patient.profile_updated']);
    }

    public function test_cancellation_is_a_request_until_staff_approves_it(): void
    {
        $patient = User::factory()->create();
        $staff = User::factory()->create(['role' => UserRole::Moderator]);
        $appointment = $this->appointment($patient);
        Sanctum::actingAs($patient);
        $response = $this->postJson("/api/me/appointments/{$appointment->id}/cancellation-request", ['reason' => 'Unable to attend'])->assertCreated();
        $appointment->refresh();
        $this->assertSame('confirmed', $appointment->status->value);
        Sanctum::actingAs($staff);
        $this->patchJson('/api/staff/cancellation-requests/'.$response->json('data.id'), ['decision' => 'approved'])->assertOk();
        $this->assertSame('cancelled', $appointment->fresh()->status->value);
    }

    public function test_private_document_metadata_and_download_are_patient_isolated(): void
    {
        Storage::fake('local');
        $owner = User::factory()->create();
        $other = User::factory()->create();
        Sanctum::actingAs($owner);
        $response = $this->post('/api/me/documents', ['label' => 'Referral letter', 'document' => UploadedFile::fake()->createWithContent('referral.pdf', "%PDF-1.4\nbenign referral")], ['Accept' => 'application/json'])->assertCreated()->assertJsonMissingPath('data.storage_path');
        $this->get('/api/documents/'.$response->json('data.id').'/download')->assertOk();
        Sanctum::actingAs($other);
        $this->getJson('/api/documents/'.$response->json('data.id').'/download')->assertForbidden();
        $this->getJson('/api/me/documents')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_messages_are_isolated_and_staff_can_reply_with_notification(): void
    {
        $patient = User::factory()->create();
        $other = User::factory()->create();
        $staff = User::factory()->create(['role' => UserRole::Admin]);
        Sanctum::actingAs($patient);
        $response = $this->postJson('/api/me/message-threads', ['subject' => 'Before my visit', 'body' => 'May I bring a companion?'])->assertCreated();
        Sanctum::actingAs($other);
        $this->postJson('/api/me/message-threads/'.$response->json('data.id').'/messages', ['body' => 'Not my thread'])->assertForbidden();
        Sanctum::actingAs($staff);
        $this->postJson('/api/staff/message-threads/'.$response->json('data.id').'/messages', ['body' => 'Yes, you may.'])->assertCreated();
        $this->assertDatabaseCount('notifications', 2);
    }

    public function test_unverified_patient_cannot_open_portal_core_endpoints(): void
    {
        Sanctum::actingAs(User::factory()->unverified()->create());
        $this->getJson('/api/me/profile')->assertForbidden();
        $this->getJson('/api/me/message-threads')->assertForbidden();
        $this->getJson('/api/me/documents')->assertForbidden();
    }

    private function appointment(User $patient): Appointment
    {
        $service = Service::create(['name' => 'Review', 'slug' => 'portal-review', 'summary' => 'Specialist review']);
        $start = now()->addWeek();

        return Appointment::create(['public_id' => Str::uuid(), 'patient_id' => $patient->id, 'service_id' => $service->id, 'starts_at' => $start, 'ends_at' => $start->copy()->addMinutes(45), 'status' => 'confirmed', 'consultation_method' => 'in_person', 'reason' => 'Review request']);
    }
}
