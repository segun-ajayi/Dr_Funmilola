<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Services\AppointmentWorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CancellationRequestController extends Controller
{
    public function store(Request $request, Appointment $appointment, AppointmentWorkflowService $workflow): JsonResponse
    {
        $this->authorize('update', $appointment);
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:1000']]);
        $item = $workflow->requestCancellation($appointment, $request->user(), $data['reason'] ?? null);

        return response()->json(['data' => $item, 'message' => 'Your request has been sent to the practice team.'], 201);
    }
}
