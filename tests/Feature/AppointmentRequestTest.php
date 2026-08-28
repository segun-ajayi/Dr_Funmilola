<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\AvailabilityRule;
use App\Models\Service;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_request_an_available_appointment(): void
    {
        $service = Service::create(['name' => 'Consultation', 'slug' => 'consultation', 'summary' => 'Specialist review', 'duration_minutes' => 45, 'online_available' => true]);
        $start = $this->availableStart();
        $this->rule($start);

        $this->postJson('/api/appointment-requests', ['name' => 'Demo Patient', 'email' => 'patient@example.test', 'phone' => '+2348000000000', 'service_id' => $service->id, 'starts_at' => $start->toIso8601String(), 'consultation_method' => 'online', 'reason' => 'I would like a specialist review of a recent breast concern.'])
            ->assertCreated()->assertJsonStructure(['message', 'reference']);
        $this->assertDatabaseCount('appointments', 1);
    }

    public function test_overlapping_appointment_is_rejected(): void
    {
        $service = Service::create(['name' => 'Consultation', 'slug' => 'consultation', 'summary' => 'Specialist review', 'duration_minutes' => 45]);
        $start = $this->availableStart();
        $this->rule($start);
        $payload = ['name' => 'First Patient', 'email' => 'first@example.test', 'phone' => '08000000001', 'service_id' => $service->id, 'starts_at' => $start->toIso8601String(), 'consultation_method' => 'in_person', 'reason' => 'A detailed reason for requesting this specialist appointment.'];
        $this->postJson('/api/appointment-requests', $payload)->assertCreated();
        $this->postJson('/api/appointment-requests', $payload + ['email' => 'second@example.test'])->assertUnprocessable()->assertJsonValidationErrors('starts_at');
        $this->assertSame(1, Appointment::count());
    }

    public function test_submission_rejects_off_rule_and_service_method_invalid_times(): void
    {
        $start = $this->availableStart();
        $this->rule($start, 'in_person');
        $service = Service::create(['name' => 'Surgery', 'slug' => 'surgery', 'summary' => 'Surgical review', 'duration_minutes' => 45, 'online_available' => false]);
        $payload = ['name' => 'Demo Patient', 'email' => 'patient@example.test', 'phone' => '+2348000000000', 'service_id' => $service->id, 'starts_at' => $start->toIso8601String(), 'consultation_method' => 'online', 'reason' => 'I would like a specialist review of a recent breast concern.'];

        $this->postJson('/api/appointment-requests', $payload)->assertUnprocessable()->assertJsonValidationErrors('starts_at');
        $this->postJson('/api/appointment-requests', $payload + ['consultation_method' => 'in_person', 'starts_at' => $start->addMinutes(5)->toIso8601String()])->assertUnprocessable()->assertJsonValidationErrors('starts_at');
        $this->assertDatabaseCount('appointments', 0);
    }

    private function availableStart(): CarbonImmutable
    {
        return CarbonImmutable::now('Africa/Lagos')->next('Monday')->setTime(9, 0);
    }

    private function rule(CarbonImmutable $start, string $method = 'both'): void
    {
        AvailabilityRule::create(['weekday' => $start->dayOfWeekIso, 'start_time' => '09:00', 'end_time' => '12:00', 'slot_minutes' => 45, 'buffer_minutes' => 15, 'consultation_method' => $method, 'is_active' => true]);
    }
}
