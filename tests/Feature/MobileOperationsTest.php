<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\Service;
use App\Models\User;
use App\Notifications\PortalActivityNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MobileOperationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_updates_are_retry_safe_and_request_ids_cannot_cross_operations(): void
    {
        [$patient, $token] = $this->patientToken();
        $requestId = (string) Str::uuid();
        $payload = [
            'client_request_id' => $requestId,
            'name' => 'Updated Patient',
            'phone' => '+2348000000000',
            'date_of_birth' => '1990-05-04',
            'address' => 'Private address',
            'emergency_contact_name' => 'Trusted Person',
            'emergency_contact_phone' => '+2348111111111',
            'preferred_communication' => 'sms',
        ];

        $this->withToken($token)->patchJson('/api/v1/me', $payload)
            ->assertOk()->assertHeader('X-Idempotent-Replay', 'false')
            ->assertJsonPath('data.profile.preferred_communication', 'sms');
        $this->withToken($token)->patchJson('/api/v1/me', $payload)
            ->assertOk()->assertHeader('X-Idempotent-Replay', 'true');
        $this->assertDatabaseCount('patient_profiles', 1);
        $this->assertDatabaseCount('mobile_mutations', 1);
        $this->assertDatabaseHas('audit_logs', ['actor_id' => $patient->id, 'action' => 'patient.profile_updated']);

        $this->withToken($token)->putJson('/api/v1/notification-preferences', [
            'client_request_id' => $requestId,
            'in_app_reminders' => true,
            'email_reminders' => true,
            'push_reminders' => false,
        ])->assertStatus(409)->assertJsonPath('error.code', 'conflict');
    }

    public function test_documents_are_scanned_downloadable_by_owner_and_isolated(): void
    {
        Storage::fake('local');
        [$owner, $token] = $this->patientToken();
        [$other] = $this->patientToken();
        $payload = [
            'client_request_id' => (string) Str::uuid(),
            'label' => 'Pathology report',
            'document' => UploadedFile::fake()->createWithContent('pathology.pdf', "%PDF-1.4\nbenign mobile fixture"),
        ];
        $documentId = $this->withToken($token)->post('/api/v1/documents', $payload, ['Accept' => 'application/json'])
            ->assertCreated()->assertJsonMissingPath('data.storage_path')->json('data.id');

        $this->withToken($token)->get('/api/v1/documents/'.$documentId.'/download')->assertOk();
        Sanctum::actingAs($other, ['mobile:v1']);
        $this->get('/api/v1/documents/'.$documentId.'/download')->assertForbidden();
        $this->assertDatabaseHas('patient_documents', ['id' => $documentId, 'patient_id' => $owner->id]);
        $this->assertDatabaseHas('audit_logs', ['actor_id' => $owner->id, 'action' => 'document.downloaded']);
    }

    public function test_message_threads_and_replies_are_retry_safe_and_isolated(): void
    {
        [$patient, $token] = $this->patientToken();
        [$other] = $this->patientToken();
        $createId = (string) Str::uuid();
        $threadId = $this->withToken($token)->postJson('/api/v1/message-threads', [
            'client_request_id' => $createId,
            'subject' => 'Medication question',
            'body' => 'Can I take this with food?',
        ])->assertCreated()->json('data.id');
        $this->withToken($token)->postJson('/api/v1/message-threads', [
            'client_request_id' => $createId,
            'subject' => 'Medication question',
            'body' => 'Can I take this with food?',
        ])->assertCreated()->assertHeader('X-Idempotent-Replay', 'true');

        $replyId = (string) Str::uuid();
        $this->withToken($token)->postJson("/api/v1/message-threads/{$threadId}/messages", ['client_request_id' => $replyId, 'body' => 'Additional detail'])
            ->assertCreated();
        $this->withToken($token)->postJson("/api/v1/message-threads/{$threadId}/messages", ['client_request_id' => $replyId, 'body' => 'Additional detail'])
            ->assertCreated()->assertHeader('X-Idempotent-Replay', 'true');
        Sanctum::actingAs($other, ['mobile:v1']);
        $this->postJson("/api/v1/message-threads/{$threadId}/messages", ['client_request_id' => (string) Str::uuid(), 'body' => 'Intrusion'])
            ->assertForbidden();
        $this->assertDatabaseCount('message_threads', 1);
        $this->assertDatabaseCount('messages', 2);
        Sanctum::actingAs($patient, ['mobile:v1']);
        $this->assertSame($patient->id, (int) $this->getJson('/api/v1/message-threads?per_page=50')->assertOk()->json('data.0.patient_id'));
    }

    public function test_notifications_preferences_and_device_revocation_are_patient_owned(): void
    {
        [$patient, $token] = $this->patientToken('Current phone');
        $otherToken = $patient->createToken('Old phone', ['mobile:v1'], now()->addDay());
        $patient->notify(new PortalActivityNotification('Appointment updated', 'Your time changed.'));
        $notificationId = $patient->notifications()->firstOrFail()->id;

        $this->withToken($token)->patchJson('/api/v1/notifications/'.$notificationId.'/read', ['client_request_id' => (string) Str::uuid()])
            ->assertOk()->assertJsonPath('data.id', $notificationId);
        $this->assertNotNull($patient->notifications()->findOrFail($notificationId)->read_at);

        $this->withToken($token)->putJson('/api/v1/notification-preferences', [
            'client_request_id' => (string) Str::uuid(),
            'in_app_reminders' => false,
            'email_reminders' => true,
            'push_reminders' => false,
        ])->assertOk()->assertJsonPath('data.in_app_reminders', false);
        $this->withToken($token)->putJson('/api/v1/notification-preferences', [
            'client_request_id' => (string) Str::uuid(),
            'in_app_reminders' => true,
            'email_reminders' => true,
            'push_reminders' => true,
        ])->assertUnprocessable();

        $devices = $this->withToken($token)->getJson('/api/v1/devices')->assertOk();
        $this->assertTrue(collect($devices->json('data'))->firstWhere('name', 'Current phone')['current']);
        $this->withToken($token)->deleteJson('/api/v1/devices/'.$otherToken->accessToken->id)->assertOk();
        $this->withToken($token)->deleteJson('/api/v1/devices/'.$patient->tokens()->where('name', 'Current phone')->value('id'))->assertUnprocessable();
    }

    public function test_consultation_workflow_is_owned_and_truthful_when_video_is_unconfigured(): void
    {
        [$patient, $token] = $this->patientToken();
        [$other] = $this->patientToken();
        $appointment = $this->appointment($patient);
        $consultation = Consultation::create([
            'public_id' => (string) Str::uuid(),
            'appointment_id' => $appointment->id,
            'provider_key' => 'unconfigured',
            'room_locator' => 'private-room-reference',
            'created_by' => $patient->id,
        ]);

        Sanctum::actingAs($other, ['mobile:v1']);
        $this->getJson('/api/v1/consultations/'.$consultation->id)->assertForbidden();
        Sanctum::actingAs($patient, ['mobile:v1']);
        $this->postJson('/api/v1/consultations/'.$consultation->id.'/consent', ['client_request_id' => (string) Str::uuid(), 'accepted' => true])
            ->assertCreated()->assertJsonPath('data.has_consent', true)->assertJsonMissingPath('data.room_locator');
        $this->withToken($token)->postJson('/api/v1/consultations/'.$consultation->id.'/waiting-room', ['client_request_id' => (string) Str::uuid()])
            ->assertOk()->assertJsonPath('data.status', 'waiting');
        $consultation->update(['status' => 'ready']);
        $join = $this->withToken($token)->postJson('/api/v1/consultations/'.$consultation->id.'/join', ['client_request_id' => (string) Str::uuid()])
            ->assertOk()->assertJsonPath('data.configuration.provider', 'unconfigured')->assertJsonPath('data.configuration.ready', false);
        $this->withToken($token)->postJson('/api/v1/consultations/'.$consultation->id.'/leave', ['client_request_id' => (string) Str::uuid()])->assertOk();
        $this->assertDatabaseHas('consultation_attendances', ['id' => $join->json('data.attendance_id'), 'participant_role' => 'patient']);
    }

    private function patientToken(string $name = 'Test phone'): array
    {
        $patient = User::factory()->create();

        return [$patient, $patient->createToken($name, ['mobile:v1'], now()->addDay())->plainTextToken];
    }

    private function appointment(User $patient): Appointment
    {
        $service = Service::create(['name' => 'Mobile consultation', 'slug' => 'mobile-'.Str::random(6), 'summary' => 'Secure review']);
        $start = now()->addMinutes(10);

        return Appointment::create([
            'public_id' => (string) Str::uuid(),
            'patient_id' => $patient->id,
            'service_id' => $service->id,
            'starts_at' => $start,
            'ends_at' => $start->copy()->addMinutes(45),
            'status' => 'confirmed',
            'consultation_method' => 'online',
            'reason' => 'Review',
        ]);
    }
}
