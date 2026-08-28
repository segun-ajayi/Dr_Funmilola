<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Services\AppointmentWorkflowService;
use App\Services\AvailabilityService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RescheduleRequestController extends Controller
{
    public function store(Request $request, Appointment $appointment, AppointmentWorkflowService $workflow, AvailabilityService $availability): JsonResponse
    {
        $this->authorize('update', $appointment);
        $data = $request->validate([
            'starts_at' => ['required', 'date'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);
        $item = $workflow->requestReschedule(
            $appointment,
            $request->user(),
            CarbonImmutable::parse($data['starts_at'])->setTimezone('Africa/Lagos'),
            $data['reason'] ?? null,
            $availability,
        );

        return response()->json(['data' => $item, 'message' => 'Your preferred time was sent to the practice team for approval.'], 201);
    }
}
