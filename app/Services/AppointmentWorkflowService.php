<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\AppointmentCancellationRequest;
use App\Models\AppointmentRescheduleRequest;
use App\Models\AuditLog;
use App\Models\NotificationDelivery;
use App\Models\User;
use App\Notifications\PortalActivityNotification;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class AppointmentWorkflowService
{
    private const TRANSITIONS = [
        'requested' => ['pending_confirmation', 'confirmed', 'cancelled'],
        'pending_confirmation' => ['confirmed', 'cancelled', 'rescheduled'],
        'confirmed' => ['checked_in', 'cancelled', 'rescheduled', 'no_show'],
        'checked_in' => ['in_progress', 'cancelled', 'no_show'],
        'in_progress' => ['completed'],
        'rescheduled' => ['checked_in', 'cancelled', 'rescheduled', 'no_show'],
        'completed' => [], 'cancelled' => [], 'no_show' => [],
    ];

    private const PATIENT_REQUEST_STATUSES = ['requested', 'pending_confirmation', 'confirmed', 'rescheduled'];

    public function allowedPatientActions(Appointment $appointment): array
    {
        $appointment->loadMissing(['cancellationRequest:id,appointment_id,status', 'rescheduleRequest:id,appointment_id,status']);
        if (! in_array($appointment->status->value, self::PATIENT_REQUEST_STATUSES, true)) {
            return [];
        }
        if ($appointment->cancellationRequest?->status === 'pending' || $appointment->rescheduleRequest?->status === 'pending') {
            return [];
        }

        $actions = [];
        if (! $appointment->cancellationRequest || $appointment->cancellationRequest->status === 'declined') {
            $actions[] = 'request_cancellation';
        }
        if (! $appointment->rescheduleRequest || $appointment->rescheduleRequest->status !== 'pending') {
            $actions[] = 'request_reschedule';
        }

        return $actions;
    }

    public function requestCancellation(Appointment $appointment, User $patient, ?string $reason = null): AppointmentCancellationRequest
    {
        return DB::transaction(function () use ($appointment, $patient, $reason) {
            $appointment = Appointment::query()->lockForUpdate()->findOrFail($appointment->id);
            $this->assertPatientRequestEligible($appointment);
            if ($appointment->rescheduleRequest()->where('status', 'pending')->exists()) {
                throw ValidationException::withMessages(['appointment' => 'Resolve the pending reschedule request before requesting cancellation.']);
            }
            $request = $appointment->cancellationRequest()->lockForUpdate()->first();

            if ($request?->status === 'pending') {
                return $request;
            }

            if ($request) {
                $request->update(['reason' => $reason, 'status' => 'pending', 'reviewed_by' => null, 'reviewed_at' => null]);
            } else {
                $request = $appointment->cancellationRequest()->create(['patient_id' => $patient->id, 'reason' => $reason]);
            }

            $this->audit($appointment, $patient, 'appointment.cancellation_requested', ['request_id' => $request->id]);
            $this->notifyStaff('Cancellation request', 'A patient has requested an appointment cancellation.', 'cancellation');

            return $request->fresh();
        });
    }

    public function requestReschedule(Appointment $appointment, User $patient, CarbonImmutable $start, ?string $reason, AvailabilityService $availability): AppointmentRescheduleRequest
    {
        if (! $start->isFuture()) {
            throw ValidationException::withMessages(['starts_at' => 'Please choose a future appointment time.']);
        }
        $appointment->loadMissing('service');
        $this->assertPatientRequestEligible($appointment);
        if (! $availability->isBookableSlot($appointment->service, $start, $appointment->consultation_method, $appointment->id)) {
            throw ValidationException::withMessages(['starts_at' => 'This appointment time is unavailable. Refresh the schedule and choose another current slot.']);
        }

        return DB::transaction(function () use ($appointment, $patient, $start, $reason) {
            $appointment = Appointment::query()->lockForUpdate()->findOrFail($appointment->id);
            $this->assertPatientRequestEligible($appointment);
            if ($appointment->cancellationRequest()->where('status', 'pending')->exists()) {
                throw ValidationException::withMessages(['appointment' => 'Resolve the pending cancellation request before requesting a new time.']);
            }
            $request = $appointment->rescheduleRequest()->lockForUpdate()->first();

            if ($request?->status === 'pending') {
                return $request;
            }

            $values = ['requested_starts_at' => $start->utc(), 'reason' => $reason, 'status' => 'pending', 'reviewed_by' => null, 'reviewed_at' => null, 'decision_note' => null];
            if ($request) {
                $request->update($values);
            } else {
                $request = $appointment->rescheduleRequest()->create($values + ['patient_id' => $patient->id]);
            }

            $this->audit($appointment, $patient, 'appointment.reschedule_requested', ['request_id' => $request->id, 'requested_starts_at' => $start->toIso8601String()]);
            $this->notifyStaff('Reschedule request', 'A patient has requested a new appointment time.', 'reschedule');

            return $request->fresh();
        });
    }

    public function reviewCancellation(AppointmentCancellationRequest $request, string $decision, User $actor): AppointmentCancellationRequest
    {
        if ($request->status !== 'pending') {
            throw ValidationException::withMessages(['decision' => 'Only a pending cancellation request can be reviewed.']);
        }

        return DB::transaction(function () use ($request, $decision, $actor) {
            if ($decision === 'approved') {
                $this->transition($request->appointment, AppointmentStatus::Cancelled, $actor, 'Cancelled following patient request.');
            }
            $request->update(['status' => $decision, 'reviewed_by' => $actor->id, 'reviewed_at' => now()]);
            $this->audit($request->appointment, $actor, 'appointment.cancellation_reviewed', ['request_id' => $request->id, 'decision' => $decision]);
            $this->notifyPatient($request->appointment, 'cancellation_'.$decision, 'Cancellation request updated', "Your cancellation request was {$decision}.");

            return $request->fresh();
        });
    }

    public function reviewReschedule(AppointmentRescheduleRequest $request, string $decision, User $actor, AvailabilityService $availability, ?string $note = null): AppointmentRescheduleRequest
    {
        if ($request->status !== 'pending') {
            throw ValidationException::withMessages(['decision' => 'Only a pending reschedule request can be reviewed.']);
        }

        return DB::transaction(function () use ($request, $decision, $actor, $availability, $note) {
            if ($decision === 'approved') {
                $this->reschedule($request->appointment->loadMissing('service'), $request->requested_starts_at, $actor, $availability);
            }
            $request->update(['status' => $decision, 'reviewed_by' => $actor->id, 'reviewed_at' => now(), 'decision_note' => $note]);
            $this->audit($request->appointment, $actor, 'appointment.reschedule_reviewed', ['request_id' => $request->id, 'decision' => $decision]);
            if ($decision === 'declined') {
                $this->notifyPatient($request->appointment, 'reschedule_declined', 'Reschedule request updated', 'Your reschedule request was declined. Your existing appointment time remains unchanged.');
            }

            return $request->fresh();
        });
    }

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
            if (in_array($status->value, ['confirmed', 'cancelled'], true)) {
                $this->notifyPatient($appointment, 'status_'.$status->value, 'Appointment '.ucfirst($status->value), "Your {$appointment->service->name} appointment is now {$status->value}.");
            }

            return $appointment->fresh(['patient:id,name,email,phone', 'service:id,name,slug']);
        });
    }

    public function reschedule(Appointment $appointment, CarbonImmutable $start, User $actor, AvailabilityService $availability): Appointment
    {
        if (! in_array($appointment->status->value, ['pending_confirmation', 'confirmed', 'rescheduled'], true)) {
            throw ValidationException::withMessages(['starts_at' => 'Only pending, confirmed or previously rescheduled appointments can be rescheduled.']);
        }
        if (! $start->isFuture()) {
            throw ValidationException::withMessages(['starts_at' => 'Please choose a future appointment time.']);
        }

        return DB::transaction(function () use ($appointment, $start, $actor, $availability) {
            $availability->lockSchedule($start);
            if (! $availability->isBookableSlot($appointment->service, $start, $appointment->consultation_method, $appointment->id)) {
                throw ValidationException::withMessages(['starts_at' => 'This appointment time is unavailable. Refresh the schedule and choose another current slot.']);
            }
            $end = $start->addMinutes($appointment->service->duration_minutes);
            $before = $appointment->starts_at->toIso8601String();
            $appointment->update(['starts_at' => $start->utc(), 'ends_at' => $end->utc(), 'status' => AppointmentStatus::Rescheduled, 'confirmed_at' => now(), 'assigned_moderator_id' => $actor->id]);
            $this->audit($appointment, $actor, 'appointment.rescheduled', ['from' => $before, 'to' => $start->toIso8601String()]);
            $this->notifyPatient($appointment, 'rescheduled', 'Appointment rescheduled', 'Your appointment has been moved to '.$start->setTimezone('Africa/Lagos')->format('g:i A, j M Y').'.');

            return $appointment->fresh(['patient:id,name,email,phone', 'service:id,name,slug']);
        });
    }

    private function audit(Appointment $appointment, User $actor, string $action, array $metadata): void
    {
        AuditLog::create(['actor_id' => $actor->id, 'action' => $action, 'subject_type' => Appointment::class, 'subject_id' => $appointment->id, 'metadata' => $metadata]);
    }

    private function assertPatientRequestEligible(Appointment $appointment): void
    {
        if (! in_array($appointment->status->value, self::PATIENT_REQUEST_STATUSES, true)) {
            throw ValidationException::withMessages(['appointment' => 'This appointment no longer accepts patient change requests.']);
        }
    }

    private function notifyStaff(string $title, string $message, string $kind): void
    {
        Notification::send(
            User::query()->whereIn('role', ['admin', 'moderator', 'power_admin'])->where('is_active', true)->get(),
            new PortalActivityNotification($title, $message, $kind),
        );
    }

    private function notifyPatient(Appointment $appointment, string $type, string $title, string $message): void
    {
        $appointment->loadMissing(['patient', 'service']);
        $appointment->patient->notify(new PortalActivityNotification($title, $message, 'appointment'));
        NotificationDelivery::updateOrCreate(['appointment_id' => $appointment->id, 'user_id' => $appointment->patient_id, 'notification_type' => $type, 'channel' => 'in_app'], ['status' => 'delivered', 'delivered_at' => now()]);
    }
}
