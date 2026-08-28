<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\AvailabilityRule;
use App\Models\Service;
use App\Models\User;
use App\Services\AvailabilityService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AppointmentChangeRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_declined_cancellation_can_be_resubmitted_and_allowed_actions_are_truthful(): void
    {
        [$patient, $staff, $appointment] = $this->actorsAndAppointment();
        Sanctum::actingAs($patient);
        $this->getJson('/api/me/appointments')->assertOk()
            ->assertJsonPath('data.0.allowed_actions', ['request_cancellation', 'request_reschedule']);
        $first = $this->postJson("/api/me/appointments/{$appointment->id}/cancellation-request", ['reason' => 'First reason'])->assertCreated();
        $this->getJson('/api/me/appointments')->assertJsonPath('data.0.cancellation_request.status', 'pending')
            ->assertJsonPath('data.0.allowed_actions', []);

        Sanctum::actingAs($staff);
        $this->patchJson('/api/staff/cancellation-requests/'.$first->json('data.id'), ['decision' => 'declined'])->assertOk();

        Sanctum::actingAs($patient);
        $this->getJson('/api/me/appointments')->assertJsonPath('data.0.cancellation_request.status', 'declined')
            ->assertJsonPath('data.0.allowed_actions', ['request_cancellation', 'request_reschedule']);
        $second = $this->postJson("/api/me/appointments/{$appointment->id}/cancellation-request", ['reason' => 'Updated reason'])->assertCreated();

        $this->assertSame($first->json('data.id'), $second->json('data.id'));
        $this->assertDatabaseHas('appointment_cancellation_requests', ['id' => $first->json('data.id'), 'status' => 'pending', 'reason' => 'Updated reason', 'reviewed_by' => null, 'reviewed_at' => null]);
        $this->assertDatabaseCount('appointment_cancellation_requests', 1);
        $this->assertSame(2, AuditLog::query()->where('subject_type', Appointment::class)->where('subject_id', $appointment->id)->where('action', 'appointment.cancellation_requested')->count());
    }

    public function test_patient_reschedule_decline_resubmit_and_approval_is_consistent(): void
    {
        [$patient, $staff, $appointment, $service] = $this->actorsAndAppointment();
        $firstStart = CarbonImmutable::now('Africa/Lagos')->next('Monday')->addWeek()->setTime(9, 0);
        $secondStart = $firstStart->addWeek();
        $this->rule($firstStart);

        Sanctum::actingAs($patient);
        $first = $this->postJson("/api/me/appointments/{$appointment->id}/reschedule-request", ['starts_at' => $firstStart->toIso8601String(), 'reason' => 'A better day'])->assertCreated();
        $this->assertSame('confirmed', $appointment->fresh()->status->value);
        $this->getJson('/api/me/appointments')->assertJsonPath('data.0.reschedule_request.status', 'pending')
            ->assertJsonPath('data.0.allowed_actions', []);
        $this->postJson("/api/me/appointments/{$appointment->id}/cancellation-request")->assertUnprocessable()->assertJsonValidationErrors('appointment');

        Sanctum::actingAs($staff);
        $this->getJson('/api/staff/inbox')->assertJsonPath('reschedules.0.id', $first->json('data.id'));
        $this->patchJson('/api/staff/reschedule-requests/'.$first->json('data.id'), ['decision' => 'declined', 'note' => 'Clinic unavailable'])->assertOk();

        Sanctum::actingAs($patient);
        $this->getJson('/api/me/appointments')->assertJsonPath('data.0.reschedule_request.status', 'declined')
            ->assertJsonPath('data.0.allowed_actions', ['request_cancellation', 'request_reschedule']);
        $second = $this->postJson("/api/me/appointments/{$appointment->id}/reschedule-request", ['starts_at' => $secondStart->toIso8601String()])->assertCreated();
        $this->assertSame($first->json('data.id'), $second->json('data.id'));

        Sanctum::actingAs($staff);
        $this->patchJson('/api/staff/reschedule-requests/'.$second->json('data.id'), ['decision' => 'approved'])->assertOk();

        $appointment->refresh();
        $this->assertSame('rescheduled', $appointment->status->value);
        $this->assertTrue($appointment->starts_at->equalTo($secondStart->utc()));
        $this->assertTrue(app(AvailabilityService::class)->hasConflict($secondStart, $secondStart->addMinutes($service->duration_minutes)));
        $this->assertDatabaseHas('appointment_reschedule_requests', ['id' => $second->json('data.id'), 'status' => 'approved', 'reviewed_by' => $staff->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'appointment.rescheduled', 'subject_id' => $appointment->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'appointment.reschedule_reviewed', 'subject_id' => $appointment->id]);

        Sanctum::actingAs($patient);
        $this->getJson('/api/me/appointments')->assertJsonPath('data.0.status', 'rescheduled')
            ->assertJsonPath('data.0.allowed_actions', ['request_cancellation', 'request_reschedule']);
    }

    public function test_reschedule_approval_rechecks_conflict_and_keeps_request_pending_on_failure(): void
    {
        [$patient, $staff, $appointment, $service] = $this->actorsAndAppointment();
        $requestedStart = CarbonImmutable::now('Africa/Lagos')->next('Monday')->addWeeks(2)->setTime(9, 0);
        $this->rule($requestedStart);
        Sanctum::actingAs($patient);
        $requestId = $this->postJson("/api/me/appointments/{$appointment->id}/reschedule-request", ['starts_at' => $requestedStart->toIso8601String()])->assertCreated()->json('data.id');

        $other = User::factory()->create();
        $this->appointment($other, $service, 'confirmed', $requestedStart);
        Sanctum::actingAs($staff);
        $this->patchJson('/api/staff/reschedule-requests/'.$requestId, ['decision' => 'approved'])->assertUnprocessable()->assertJsonValidationErrors('starts_at');

        $this->assertDatabaseHas('appointment_reschedule_requests', ['id' => $requestId, 'status' => 'pending', 'reviewed_by' => null]);
        $this->assertSame('confirmed', $appointment->fresh()->status->value);
    }

    public function test_mobile_reschedule_is_idempotent_and_actions_match_every_server_state(): void
    {
        $patient = User::factory()->create();
        $service = Service::create(['name' => 'Review', 'slug' => 'mobile-change-review', 'summary' => 'Specialist review', 'duration_minutes' => 45]);
        $base = CarbonImmutable::now('Africa/Lagos')->next('Monday')->addWeeks(4)->setTime(9, 0);
        $this->rule($base);
        $appointments = collect(['requested', 'pending_confirmation', 'confirmed', 'checked_in', 'in_progress', 'completed', 'cancelled', 'rescheduled', 'no_show'])
            ->mapWithKeys(fn (string $status, int $index) => [$status => $this->appointment($patient, $service, $status, $base->addWeeks($index + 1))]);
        $token = $patient->createToken('Phone', ['mobile:v1'], now()->addDay())->plainTextToken;

        $response = $this->withToken($token)->getJson('/api/v1/appointments?per_page=50')->assertOk();
        $byStatus = collect($response->json('data'))->keyBy('status');
        foreach (['requested', 'pending_confirmation', 'confirmed', 'rescheduled'] as $status) {
            $this->assertSame(['request_cancellation', 'request_reschedule'], $byStatus[$status]['allowed_actions']);
        }
        foreach (['checked_in', 'in_progress', 'completed', 'cancelled', 'no_show'] as $status) {
            $this->assertSame([], $byStatus[$status]['allowed_actions']);
        }

        $target = $appointments['confirmed'];
        $requestId = (string) Str::uuid();
        $payload = ['client_request_id' => $requestId, 'starts_at' => $base->toIso8601String()];
        $this->withToken($token)->postJson("/api/v1/appointments/{$target->id}/reschedule-requests", $payload)->assertCreated()->assertHeader('X-Idempotent-Replay', 'false');
        $this->withToken($token)->postJson("/api/v1/appointments/{$target->id}/reschedule-requests", $payload)->assertCreated()->assertHeader('X-Idempotent-Replay', 'true');
        $this->assertDatabaseCount('appointment_reschedule_requests', 1);
        $this->assertDatabaseHas('mobile_mutations', ['user_id' => $patient->id, 'client_request_id' => $requestId, 'operation' => 'appointment.reschedule_request']);
    }

    public function test_another_patient_cannot_submit_change_requests(): void
    {
        [$owner, , $appointment] = $this->actorsAndAppointment();
        $other = User::factory()->create();
        Sanctum::actingAs($other);

        $this->postJson("/api/me/appointments/{$appointment->id}/cancellation-request")->assertForbidden();
        $this->postJson("/api/me/appointments/{$appointment->id}/reschedule-request", ['starts_at' => now()->addMonth()->toIso8601String()])->assertForbidden();
    }

    private function actorsAndAppointment(): array
    {
        $patient = User::factory()->create();
        $staff = User::factory()->create(['role' => UserRole::Moderator]);
        $service = Service::create(['name' => 'Review', 'slug' => 'change-review', 'summary' => 'Specialist review', 'duration_minutes' => 45]);

        return [$patient, $staff, $this->appointment($patient, $service), $service];
    }

    private function appointment(User $patient, Service $service, string $status = 'confirmed', ?CarbonImmutable $start = null): Appointment
    {
        $start ??= CarbonImmutable::now('Africa/Lagos')->addDays(2)->startOfHour();

        return Appointment::create(['public_id' => Str::uuid(), 'patient_id' => $patient->id, 'service_id' => $service->id, 'starts_at' => $start->utc(), 'ends_at' => $start->addMinutes($service->duration_minutes)->utc(), 'status' => $status, 'consultation_method' => 'in_person', 'reason' => 'Specialist breast health consultation request.']);
    }

    private function rule(CarbonImmutable $start): void
    {
        AvailabilityRule::firstOrCreate(['weekday' => $start->dayOfWeekIso, 'start_time' => '09:00'], ['end_time' => '12:00', 'slot_minutes' => 45, 'buffer_minutes' => 15, 'consultation_method' => 'both', 'is_active' => true]);
    }
}
