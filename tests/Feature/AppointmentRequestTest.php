<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_request_an_available_appointment(): void
    {
        $service = Service::create(['name' => 'Consultation', 'slug' => 'consultation', 'summary' => 'Specialist review', 'duration_minutes' => 45, 'online_available' => true]);
        $start = now('Africa/Lagos')->addWeek()->startOfHour();

        $this->postJson('/api/appointment-requests', ['name' => 'Demo Patient', 'email' => 'patient@example.test', 'phone' => '+2348000000000', 'service_id' => $service->id, 'starts_at' => $start->toIso8601String(), 'consultation_method' => 'online', 'reason' => 'I would like a specialist review of a recent breast concern.'])
            ->assertCreated()->assertJsonStructure(['message', 'reference']);
        $this->assertDatabaseCount('appointments', 1);
    }

    public function test_overlapping_appointment_is_rejected(): void
    {
        $service = Service::create(['name' => 'Consultation', 'slug' => 'consultation', 'summary' => 'Specialist review', 'duration_minutes' => 45]);
        $start = now('Africa/Lagos')->addWeek()->startOfHour();
        $payload = ['name' => 'First Patient', 'email' => 'first@example.test', 'phone' => '08000000001', 'service_id' => $service->id, 'starts_at' => $start->toIso8601String(), 'consultation_method' => 'in_person', 'reason' => 'A detailed reason for requesting this specialist appointment.'];
        $this->postJson('/api/appointment-requests', $payload)->assertCreated();
        $this->postJson('/api/appointment-requests', $payload + ['email' => 'second@example.test'])->assertUnprocessable()->assertJsonValidationErrors('starts_at');
        $this->assertSame(1, Appointment::count());
    }
}
