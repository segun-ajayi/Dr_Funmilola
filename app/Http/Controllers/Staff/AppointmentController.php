<?php

namespace App\Http\Controllers\Staff;

use App\Enums\AppointmentStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\NotificationDelivery;
use App\Models\Service;
use App\Models\User;
use App\Notifications\PortalActivityNotification;
use App\Services\AppointmentWorkflowService;
use App\Services\AvailabilityService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AppointmentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'from' => ['nullable', 'date'], 'to' => ['nullable', 'date', 'after_or_equal:from'],
            'status' => ['nullable', Rule::enum(AppointmentStatus::class)], 'method' => ['nullable', 'in:online,in_person'], 'patient_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);
        $appointments = Appointment::query()->with(['patient:id,name,email,phone', 'service:id,name,slug'])
            ->when($data['from'] ?? null, fn ($q, $from) => $q->where('starts_at', '>=', CarbonImmutable::parse($from, 'Africa/Lagos')->startOfDay()->utc()))
            ->when($data['to'] ?? null, fn ($q, $to) => $q->where('starts_at', '<=', CarbonImmutable::parse($to, 'Africa/Lagos')->endOfDay()->utc()))
            ->when($data['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($data['method'] ?? null, fn ($q, $method) => $q->where('consultation_method', $method))
            ->when($data['patient_id'] ?? null, fn ($q, $patient) => $q->where('patient_id', $patient))
            ->orderBy('starts_at')->paginate(25);

        return response()->json($appointments);
    }

    public function store(Request $request, AvailabilityService $availability): JsonResponse
    {
        $data = $request->validate([
            'patient_id' => ['required', Rule::exists('users', 'id')->where(fn ($query) => $query->where('role', UserRole::Patient->value)->where('is_active', true))],
            'service_id' => ['required', Rule::exists('services', 'id')->where('is_active', true)],
            'starts_at' => ['required', 'date'], 'consultation_method' => ['required', 'in:online,in_person'],
            'reason' => ['required', 'string', 'min:3', 'max:2000'], 'administrative_notes' => ['nullable', 'string', 'max:2000'],
        ]);
        $service = Service::findOrFail($data['service_id']);
        $patient = User::findOrFail($data['patient_id']);
        $start = CarbonImmutable::parse($data['starts_at'])->setTimezone('Africa/Lagos');
        if (! $start->isFuture()) {
            throw ValidationException::withMessages(['starts_at' => 'Choose a future appointment time.']);
        }
        if ($data['consultation_method'] === 'online' && ! $service->online_available) {
            throw ValidationException::withMessages(['consultation_method' => 'This service is not available online.']);
        }

        $appointment = DB::transaction(function () use ($availability, $service, $patient, $start, $data, $request) {
            $availability->lockSchedule($start);
            if (! $availability->isBookableSlot($service, $start, $data['consultation_method'])) {
                throw ValidationException::withMessages(['starts_at' => 'This appointment time is no longer available. Refresh the calendar and choose another slot.']);
            }
            $appointment = Appointment::create([
                'public_id' => Str::uuid(), 'patient_id' => $patient->id, 'service_id' => $service->id,
                'starts_at' => $start->utc(), 'ends_at' => $start->addMinutes($service->duration_minutes)->utc(), 'timezone' => 'Africa/Lagos',
                'status' => AppointmentStatus::Confirmed, 'consultation_method' => $data['consultation_method'], 'reason' => $data['reason'],
                'administrative_notes' => $data['administrative_notes'] ?? null, 'assigned_moderator_id' => $request->user()->id, 'confirmed_at' => now(),
                'location' => $data['consultation_method'] === 'in_person' ? 'Practice location shared after confirmation' : null,
            ]);
            AuditLog::create(['actor_id' => $request->user()->id, 'action' => 'appointment.created_by_staff', 'subject_type' => Appointment::class, 'subject_id' => $appointment->id, 'metadata' => ['patient_id' => $patient->id, 'service_id' => $service->id, 'starts_at' => $start->toIso8601String(), 'method' => $data['consultation_method']]]);
            $patient->notify(new PortalActivityNotification('Appointment confirmed', "Your {$service->name} appointment has been scheduled for ".$start->format('g:i A, j M Y').'.', 'appointment'));
            NotificationDelivery::create(['appointment_id' => $appointment->id, 'user_id' => $patient->id, 'notification_type' => 'status_confirmed', 'channel' => 'in_app', 'status' => 'delivered', 'delivered_at' => now()]);

            return $appointment;
        });

        return response()->json(['message' => 'Appointment created and confirmed.', 'data' => $appointment->fresh(['patient:id,name,email,phone', 'service:id,name,slug'])], 201);
    }

    public function show(Appointment $appointment, AppointmentWorkflowService $workflow): JsonResponse
    {
        $appointment->load(['patient:id,name,email,phone', 'service:id,name,slug,duration_minutes,online_available']);
        $appointment->setAttribute('allowed_statuses', $workflow->allowedStaffTransitions($appointment));

        return response()->json(['data' => $appointment]);
    }

    public function rescheduleOptions(Request $request, Appointment $appointment, AvailabilityService $availability): JsonResponse
    {
        $data = $request->validate(['date' => ['required', 'date']]);
        $date = CarbonImmutable::parse($data['date'], 'Africa/Lagos')->startOfDay();

        return response()->json(['data' => $availability->slots($appointment->service, $date, $appointment->consultation_method, $appointment->id), 'timezone' => 'Africa/Lagos']);
    }

    public function update(Request $request, Appointment $appointment): JsonResponse
    {
        $data = $request->validate(['administrative_notes' => ['nullable', 'string', 'max:2000'], 'location' => ['nullable', 'string', 'max:255']]);
        $appointment->update($data);
        AuditLog::create(['actor_id' => $request->user()->id, 'action' => 'appointment.details_updated', 'subject_type' => Appointment::class, 'subject_id' => $appointment->id, 'metadata' => ['fields' => array_keys($data)]]);

        return response()->json(['message' => 'Appointment details updated.', 'data' => $appointment->fresh(['patient:id,name,email,phone', 'service:id,name,slug'])]);
    }

    public function updateStatus(Request $request, Appointment $appointment, AppointmentWorkflowService $workflow): JsonResponse
    {
        $data = $request->validate(['status' => ['required', Rule::enum(AppointmentStatus::class)], 'note' => ['nullable', 'string', 'max:2000']]);
        $updated = $workflow->transition($appointment, AppointmentStatus::from($data['status']), $request->user(), $data['note'] ?? null);

        return response()->json(['message' => 'Appointment status updated.', 'data' => $updated]);
    }

    public function reschedule(Request $request, Appointment $appointment, AppointmentWorkflowService $workflow, AvailabilityService $availability): JsonResponse
    {
        $data = $request->validate(['starts_at' => ['required', 'date'], 'timezone' => ['nullable', 'timezone']]);
        $start = CarbonImmutable::parse($data['starts_at'])->setTimezone('Africa/Lagos');
        $updated = $workflow->reschedule($appointment->loadMissing('service'), $start, $request->user(), $availability);

        return response()->json(['message' => 'Appointment rescheduled.', 'data' => $updated]);
    }
}
