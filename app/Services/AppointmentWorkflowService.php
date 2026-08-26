<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AppointmentWorkflowService
{
    private const TRANSITIONS = [
        'requested' => ['pending_confirmation', 'confirmed', 'cancelled'],
        'pending_confirmation' => ['confirmed', 'cancelled', 'rescheduled'],
        'confirmed' => ['checked_in', 'cancelled', 'rescheduled', 'no_show'],
        'checked_in' => ['in_progress', 'cancelled', 'no_show'],
        'in_progress' => ['completed'],
        'rescheduled' => [], 'completed' => [], 'cancelled' => [], 'no_show' => [],
    ];

    public function transition(Appointment $appointment, AppointmentStatus $status, User $actor, ?string $note = null): Appointment
    {
        $from = $appointment->status->value;
        if (! in_array($status->value, self::TRANSITIONS[$from] ?? [], true)) {
            throw ValidationException::withMessages(['status' => "An appointment cannot move from {$from} to {$status->value}."]);
        }

        return DB::transaction(function () use ($appointment, $status, $actor, $note, $from) {
            $appointment->update([
                'status' => $status,
                'confirmed_at' => $status === AppointmentStatus::Confirmed ? now() : $appointment->confirmed_at,
                'administrative_notes' => $note ?: $appointment->administrative_notes,
                'assigned_moderator_id' => $appointment->assigned_moderator_id ?: $actor->id,
            ]);
            $this->audit($appointment, $actor, 'appointment.status_changed', ['from' => $from, 'to' => $status->value]);

            return $appointment->fresh(['patient:id,name,email,phone', 'service:id,name,slug']);
        });
    }

    public function reschedule(Appointment $appointment, CarbonImmutable $start, User $actor, AvailabilityService $availability): Appointment
    {
        if (! in_array($appointment->status->value, ['pending_confirmation', 'confirmed'], true)) {
            throw ValidationException::withMessages(['starts_at' => 'Only pending or confirmed appointments can be rescheduled.']);
        }
        if (! $start->isFuture()) {
            throw ValidationException::withMessages(['starts_at' => 'Please choose a future appointment time.']);
        }
        $end = $start->addMinutes($appointment->service->duration_minutes);
        if ($availability->hasConflict($start, $end, $appointment->id)) {
            throw ValidationException::withMessages(['starts_at' => 'This appointment time conflicts with another appointment.']);
        }

        return DB::transaction(function () use ($appointment, $start, $end, $actor) {
            $before = $appointment->starts_at->toIso8601String();
            $appointment->update(['starts_at' => $start->utc(), 'ends_at' => $end->utc(), 'status' => AppointmentStatus::Confirmed, 'confirmed_at' => now(), 'assigned_moderator_id' => $actor->id]);
            $this->audit($appointment, $actor, 'appointment.rescheduled', ['from' => $before, 'to' => $start->toIso8601String()]);

            return $appointment->fresh(['patient:id,name,email,phone', 'service:id,name,slug']);
        });
    }

    private function audit(Appointment $appointment, User $actor, string $action, array $metadata): void
    {
        AuditLog::create(['actor_id' => $actor->id, 'action' => $action, 'subject_type' => Appointment::class, 'subject_id' => $appointment->id, 'metadata' => $metadata]);
    }
}
