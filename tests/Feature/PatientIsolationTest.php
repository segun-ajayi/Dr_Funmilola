<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PatientIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_patient_a_cannot_access_patient_b_appointment(): void
    {
        $patientA = User::factory()->create(['email_verified_at' => now()]);
        $patientB = User::factory()->create(['email_verified_at' => now()]);
        $appointment = $this->appointmentFor($patientB);
        Sanctum::actingAs($patientA);
        $this->getJson('/api/me')->assertOk();
        $this->getJson("/api/me/appointments/{$appointment->id}")->assertForbidden();
        Sanctum::actingAs($patientB);
        $this->getJson("/api/me/appointments/{$appointment->id}")->assertOk();
    }

    public function test_patient_list_contains_only_their_appointments_but_staff_policy_can_view_any(): void
    {
        $patientA = User::factory()->create(['email_verified_at' => now()]);
        $patientB = User::factory()->create(['email_verified_at' => now()]);
        $staff = User::factory()->create(['role' => UserRole::Admin, 'email_verified_at' => now()]);
        $a = $this->appointmentFor($patientA);
        $b = $this->appointmentFor($patientB, 2);

        Sanctum::actingAs($patientA);
        $this->getJson('/api/me')->assertOk();
        $this->getJson('/api/me/appointments')->assertOk()->assertJsonPath('total', 1)->assertJsonPath('data.0.id', $a->id);
        Sanctum::actingAs($staff);
        $this->getJson("/api/me/appointments/{$b->id}")->assertOk();
    }

    private function appointmentFor(User $patient, int $weeks = 1): Appointment
    {
        $service = Service::firstOrCreate(['slug' => 'review'], ['name' => 'Review', 'summary' => 'Review appointment']);
        $start = now()->addWeeks($weeks);

        return Appointment::create(['public_id' => Str::uuid(), 'patient_id' => $patient->id, 'service_id' => $service->id, 'starts_at' => $start, 'ends_at' => $start->copy()->addMinutes(45), 'status' => 'confirmed', 'consultation_method' => 'in_person', 'reason' => 'Specialist review appointment.']);
    }
}
