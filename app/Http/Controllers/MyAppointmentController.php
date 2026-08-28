<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Services\AppointmentWorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MyAppointmentController extends Controller
{
    public function index(Request $request, AppointmentWorkflowService $workflow): JsonResponse
    {
        $appointments = $request->user()->appointments()->with([
            'service:id,name,slug',
            'cancellationRequest:id,appointment_id,status',
            'rescheduleRequest:id,appointment_id,status,requested_starts_at',
        ])->latest('starts_at')->paginate(15)->through(function (Appointment $appointment) use ($workflow) {
            $appointment->setAttribute('allowed_actions', $workflow->allowedPatientActions($appointment));

            return $appointment;
        });

        return response()->json($appointments);
    }

    public function show(Request $request, Appointment $appointment, AppointmentWorkflowService $workflow): JsonResponse
    {
        $this->authorize('view', $appointment);

        $appointment->load([
            'service:id,name,slug',
            'cancellationRequest:id,appointment_id,status,reason,created_at',
            'rescheduleRequest:id,appointment_id,status,requested_starts_at,reason,created_at',
        ])->setAttribute('allowed_actions', $workflow->allowedPatientActions($appointment));

        return response()->json(['data' => $appointment]);
    }
}
