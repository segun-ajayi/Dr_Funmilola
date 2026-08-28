<?php

namespace App\Http\Controllers\Staff;

use App\Enums\AppointmentStatus;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Services\AppointmentWorkflowService;
use App\Services\AvailabilityService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
