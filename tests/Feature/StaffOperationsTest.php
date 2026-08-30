<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\AvailabilityRule;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StaffOperationsTest extends TestCase
{
    use RefreshDatabase;

    private User $moderator;

    private Service $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->moderator = User::factory()->create(['role' => UserRole::Moderator]);
        $this->service = Service::create(['name' => 'Breast consultation', 'slug' => 'breast-consultation', 'summary' => 'Specialist consultation', 'duration_minutes' => 45]);
    }

    public function test_staff_dashboard_is_role_protected(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $this->getJson('/api/staff/dashboard')->assertForbidden();
        Sanctum::actingAs($this->moderator);
        $this->getJson('/api/staff/dashboard')->assertOk()->assertJsonPath('role', 'moderator')->assertJsonStructure(['metrics' => ['today', 'pending', 'online_today', 'unclaimed_patients']]);
    }

    public function test_staff_can_confirm_request_and_invalid_transition_is_rejected(): void
    {
        $appointment = $this->appointment('requested');
        Sanctum::actingAs($this->moderator);

        $this->patchJson("/api/staff/appointments/{$appointment->id}/status", ['status' => 'confirmed'])->assertOk()->assertJsonPath('data.status', 'confirmed');
        $this->assertDatabaseHas('audit_logs', ['actor_id' => $this->moderator->id, 'action' => 'appointment.status_changed', 'subject_id' => $appointment->id]);
        $this->patchJson("/api/staff/appointments/{$appointment->id}/status", ['status' => 'completed'])->assertUnprocessable()->assertJsonValidationErrors('status');
    }

    public function test_rescheduling_rejects_conflicts_and_audits_valid_change(): void
    {
        $target = $this->appointment('confirmed', now()->addWeek()->startOfHour());
        $occupied = $this->appointment('confirmed', now()->addWeeks(2)->startOfHour());
        Sanctum::actingAs($this->moderator);

        $this->patchJson("/api/staff/appointments/{$target->id}/reschedule", ['starts_at' => $occupied->starts_at->toIso8601String()])->assertUnprocessable()->assertJsonValidationErrors('starts_at');
        $newTime = now('Africa/Lagos')->addWeeks(3)->setTime(9, 0);
        AvailabilityRule::create(['weekday' => $newTime->dayOfWeekIso, 'start_time' => '09:00', 'end_time' => '12:00', 'slot_minutes' => 45, 'buffer_minutes' => 15, 'consultation_method' => 'both', 'is_active' => true]);
        $this->patchJson("/api/staff/appointments/{$target->id}/reschedule", ['starts_at' => $newTime->toIso8601String()])->assertOk();
        $this->assertDatabaseHas('audit_logs', ['action' => 'appointment.rescheduled', 'subject_id' => $target->id]);
    }

    public function test_patient_search_returns_only_minimum_operational_fields(): void
    {
        User::factory()->create(['name' => 'Ada Lovelace Patient', 'email' => 'ada.patient@example.test', 'phone' => '08012345678']);
        Sanctum::actingAs($this->moderator);
        $response = $this->getJson('/api/staff/patients/search?q=Ada');

        $response->assertOk()->assertJsonPath('data.0.name', 'Ada Lovelace Patient')->assertJsonMissingPath('data.0.password')->assertJsonMissingPath('data.0.remember_token')->assertJsonMissingPath('data.0.address');
    }

    public function test_staff_can_create_validated_availability_rule_and_calendar_range_is_bounded(): void
    {
        Sanctum::actingAs($this->moderator);
        $this->postJson('/api/staff/availability-rules', ['weekday' => 1, 'start_time' => '09:00', 'end_time' => '13:00', 'slot_minutes' => 45, 'buffer_minutes' => 15, 'consultation_method' => 'both', 'is_active' => true])->assertCreated();
        $this->assertDatabaseHas('audit_logs', ['action' => 'availability.created']);
        $this->getJson('/api/staff/calendar?from=2026-01-01&to=2026-04-01')->assertUnprocessable()->assertJsonValidationErrors('to');
    }

    public function test_staff_calendar_can_create_view_edit_and_safely_move_an_appointment(): void
    {
        $patient = User::factory()->create(['role' => UserRole::Patient]);
        $start = now('Africa/Lagos')->addWeeks(2)->startOfWeek()->setTime(9, 0);
        AvailabilityRule::create(['weekday' => $start->dayOfWeekIso, 'start_time' => '09:00', 'end_time' => '13:00', 'slot_minutes' => 45, 'buffer_minutes' => 15, 'consultation_method' => 'both', 'is_active' => true]);
        Sanctum::actingAs($this->moderator);

        $created = $this->postJson('/api/staff/appointments', [
            'patient_id' => $patient->id, 'service_id' => $this->service->id, 'starts_at' => $start->toIso8601String(),
            'consultation_method' => 'in_person', 'reason' => 'Staff arranged follow-up', 'administrative_notes' => 'Bring prior imaging.',
        ])->assertCreated()->assertJsonPath('data.status', 'confirmed');
        $id = $created->json('data.id');

        $this->getJson("/api/staff/appointments/{$id}")->assertOk()->assertJsonPath('data.patient.id', $patient->id)->assertJsonPath('data.allowed_statuses.0', 'checked_in');
        $this->getJson("/api/staff/appointments/{$id}/reschedule-options?date=".$start->toDateString())->assertOk()->assertJsonPath('timezone', 'Africa/Lagos')->assertJsonPath('data.0.label', '9:00 AM');
        $this->patchJson("/api/staff/appointments/{$id}", ['administrative_notes' => 'Imaging received.', 'location' => 'Clinic room 2'])->assertOk()->assertJsonPath('data.administrative_notes', 'Imaging received.');
        $this->getJson('/api/staff/calendar?from='.$start->toDateString().'&to='.$start->toDateString())->assertOk()
            ->assertJsonPath('data.0.service.id', $this->service->id)->assertJsonPath('data.0.allowed_statuses.0', 'checked_in')->assertJsonPath('filters.services.0.id', $this->service->id);

        $occupied = $this->appointment('confirmed', $start->addWeek());
        $this->patchJson("/api/staff/appointments/{$id}/reschedule", ['starts_at' => $occupied->starts_at->toIso8601String()])->assertUnprocessable()->assertJsonValidationErrors('starts_at');
        $this->assertDatabaseHas('audit_logs', ['action' => 'appointment.created_by_staff', 'subject_id' => $id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'appointment.details_updated', 'subject_id' => $id]);
    }

    private function appointment(string $status, $start = null): Appointment
    {
        $patient = User::factory()->create();
        $start ??= now()->addDays(2)->startOfHour();

        return Appointment::create(['public_id' => Str::uuid(), 'patient_id' => $patient->id, 'service_id' => $this->service->id, 'starts_at' => $start, 'ends_at' => $start->copy()->addMinutes(45), 'status' => $status, 'consultation_method' => 'in_person', 'reason' => 'Specialist breast health consultation request.']);
    }
}
