<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\AvailabilityException;
use App\Models\AvailabilityRule;
use App\Models\Service;
use App\Models\User;
use App\Services\AvailabilityService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdvancedSchedulingTest extends TestCase
{
    use RefreshDatabase;

    public function test_closure_removes_regular_slots_and_additional_clinic_adds_slots(): void
    {
        $staff = User::factory()->create(['role' => UserRole::Admin]);
        $service = $this->service();
        $date = CarbonImmutable::now('Africa/Lagos')->addWeek()->startOfDay();
        AvailabilityRule::create(['weekday' => $date->dayOfWeekIso, 'start_time' => '09:00', 'end_time' => '11:00', 'slot_minutes' => 45, 'buffer_minutes' => 15, 'consultation_method' => 'both', 'is_active' => true]);
        $this->assertCount(2, app(AvailabilityService::class)->slots($service, $date, 'in_person'));
        AvailabilityException::create(['kind' => 'closed', 'label' => 'Practice closed', 'starts_at' => $date->setTime(8, 0)->utc(), 'ends_at' => $date->setTime(12, 0)->utc(), 'consultation_method' => 'both', 'created_by' => $staff->id]);
        $this->assertCount(0, app(AvailabilityService::class)->slots($service, $date, 'in_person'));
        AvailabilityException::query()->delete();
        AvailabilityException::create(['kind' => 'additional', 'label' => 'Evening clinic', 'starts_at' => $date->setTime(17, 0)->utc(), 'ends_at' => $date->setTime(19, 0)->utc(), 'consultation_method' => 'both', 'created_by' => $staff->id]);
        $slots = app(AvailabilityService::class)->slots($service, $date, 'in_person');
        $this->assertTrue($slots->contains(fn ($slot) => str_contains($slot['label'], '5:00 PM')));
    }

    public function test_slot_minutes_is_the_minimum_start_cadence_with_duration_and_buffer_floor(): void
    {
        $service = Service::create(['name' => 'Short review', 'slug' => 'short-review', 'summary' => 'Specialist review', 'duration_minutes' => 30]);
        $date = CarbonImmutable::now('Africa/Lagos')->addWeek()->startOfDay();
        AvailabilityRule::create(['weekday' => $date->dayOfWeekIso, 'start_time' => '09:00', 'end_time' => '13:00', 'slot_minutes' => 90, 'buffer_minutes' => 10, 'consultation_method' => 'both', 'is_active' => true]);

        $slots = app(AvailabilityService::class)->slots($service, $date, 'in_person');

        $this->assertSame(['9:00 AM', '10:30 AM', '12:00 PM'], $slots->pluck('label')->all());
    }

    public function test_only_staff_can_manage_audited_schedule_exceptions(): void
    {
        $payload = ['kind' => 'closed', 'label' => 'Annual leave', 'starts_at' => now()->addWeek()->toIso8601String(), 'ends_at' => now()->addWeek()->addDay()->toIso8601String(), 'consultation_method' => 'both'];
        Sanctum::actingAs(User::factory()->create());
        $this->postJson('/api/staff/availability-exceptions', $payload)->assertForbidden();
        $staff = User::factory()->create(['role' => UserRole::Moderator]);
        Sanctum::actingAs($staff);
        $response = $this->postJson('/api/staff/availability-exceptions', $payload)->assertCreated();
        $this->assertDatabaseHas('audit_logs', ['action' => 'availability_exception.created', 'subject_id' => $response->json('data.id')]);
        $this->deleteJson('/api/staff/availability-exceptions/'.$response->json('data.id'))->assertOk();
    }

    public function test_patient_can_set_supported_reminder_preferences_but_not_push_yet(): void
    {
        $patient = User::factory()->create();
        Sanctum::actingAs($patient);
        $this->putJson('/api/me/notification-preferences', ['in_app_reminders' => true, 'email_reminders' => false, 'push_reminders' => false])->assertOk()->assertJsonPath('data.email_reminders', false);
        $this->putJson('/api/me/notification-preferences', ['in_app_reminders' => true, 'email_reminders' => false, 'push_reminders' => true])->assertStatus(422);
    }

    public function test_reminder_command_is_idempotent_and_honours_channel_preferences(): void
    {
        $patient = User::factory()->create();
        $patient->notificationPreference()->create(['in_app_reminders' => true, 'email_reminders' => false, 'push_reminders' => false]);
        $this->appointment($patient, now()->addHour());
        Artisan::call('appointments:send-reminders');
        Artisan::call('appointments:send-reminders');
        $this->assertDatabaseCount('notification_deliveries', 2);
        $this->assertDatabaseCount('notifications', 2);
        $this->assertDatabaseMissing('notification_deliveries', ['channel' => 'email']);
    }

    public function test_calendar_range_returns_lagos_timezone_and_is_bounded(): void
    {
        $staff = User::factory()->create(['role' => UserRole::PowerAdmin]);
        $patient = User::factory()->create();
        $appointment = $this->appointment($patient, now()->addWeek());
        Sanctum::actingAs($staff);
        $this->getJson('/api/staff/calendar?from='.now()->toDateString().'&to='.now()->addDays(14)->toDateString())->assertOk()->assertJsonPath('timezone', 'Africa/Lagos')->assertJsonPath('data.0.id', $appointment->id);
    }

    private function service(): Service
    {
        return Service::create(['name' => 'Review', 'slug' => 'advanced-review-'.Str::random(5), 'summary' => 'Specialist review', 'duration_minutes' => 45]);
    }

    private function appointment(User $patient, $start): Appointment
    {
        $service = $this->service();

        return Appointment::create(['public_id' => Str::uuid(), 'patient_id' => $patient->id, 'service_id' => $service->id, 'starts_at' => $start, 'ends_at' => $start->copy()->addMinutes(45), 'status' => 'confirmed', 'consultation_method' => 'in_person', 'reason' => 'Review']);
    }
}
