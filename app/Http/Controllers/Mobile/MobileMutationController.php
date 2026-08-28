<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Services\AppointmentWorkflowService;
use App\Services\AvailabilityService;
use App\Services\MobileMutationService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobileMutationController extends Controller
{
    public function rescheduleOptions(Request $request, Appointment $appointment, AvailabilityService $availability): JsonResponse
    {
        $this->authorize('update', $appointment);
        $data = $request->validate(['date' => ['required', 'date_format:Y-m-d']]);
        $appointment->loadMissing('service');
        $slots = $availability->slots(
            $appointment->service,
            CarbonImmutable::createFromFormat('Y-m-d', $data['date'], 'Africa/Lagos')->startOfDay(),
            $appointment->consultation_method,
            $appointment->id,
        );

        return response()->json(['data' => $slots]);
    }

    public function cancellation(Request $request, Appointment $appointment, MobileMutationService $mutations, AppointmentWorkflowService $workflow): JsonResponse
    {
        $this->authorize('update', $appointment);
        $data = $request->validate(['client_request_id' => ['required', 'uuid'], 'reason' => ['nullable', 'string', 'max:1000']]);
        $result = $mutations->run($request->user(), $data['client_request_id'], 'appointment.cancellation_request', function () use ($appointment, $request, $data, $workflow) {
            $item = $workflow->requestCancellation($appointment, $request->user(), $data['reason'] ?? null);

            return [['data' => ['id' => $item->id, 'status' => $item->status], 'message' => 'Cancellation request sent.'], 201];
        });

        return response()->json($result['body'], $result['status'], ['X-Idempotent-Replay' => $result['replayed'] ? 'true' : 'false']);
    }

    public function reschedule(Request $request, Appointment $appointment, MobileMutationService $mutations, AppointmentWorkflowService $workflow, AvailabilityService $availability): JsonResponse
    {
        $this->authorize('update', $appointment);
        $data = $request->validate([
            'client_request_id' => ['required', 'uuid'],
            'starts_at' => ['required', 'date'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);
        $result = $mutations->run($request->user(), $data['client_request_id'], 'appointment.reschedule_request', function () use ($appointment, $request, $data, $workflow, $availability) {
            $item = $workflow->requestReschedule(
                $appointment,
                $request->user(),
                CarbonImmutable::parse($data['starts_at'])->setTimezone('Africa/Lagos'),
                $data['reason'] ?? null,
                $availability,
            );

            return [['data' => ['id' => $item->id, 'status' => $item->status, 'requested_starts_at' => $item->requested_starts_at], 'message' => 'Reschedule request sent.'], 201];
        });

        return response()->json($result['body'], $result['status'], ['X-Idempotent-Replay' => $result['replayed'] ? 'true' : 'false']);
    }
}
