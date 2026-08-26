<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MyAppointmentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $appointments = $request->user()->appointments()->with('service:id,name,slug')->latest('starts_at')->paginate(15);

        return response()->json($appointments);
    }

    public function show(Request $request, Appointment $appointment): JsonResponse
    {
        $this->authorize('view', $appointment);

        return response()->json(['data' => $appointment->load('service:id,name,slug')]);
    }
}
