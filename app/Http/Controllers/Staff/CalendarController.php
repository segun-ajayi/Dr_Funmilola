<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CalendarController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->validate(['from' => ['required', 'date'], 'to' => ['required', 'date', 'after_or_equal:from']]);
        $from = CarbonImmutable::parse($data['from'], 'Africa/Lagos')->startOfDay();
        $to = CarbonImmutable::parse($data['to'], 'Africa/Lagos')->endOfDay();
        if ($from->diffInDays($to) > 62) {
            throw ValidationException::withMessages(['to' => 'Calendar ranges cannot exceed 62 days.']);
        }
        $events = Appointment::with(['patient:id,name,phone', 'service:id,name'])->whereBetween('starts_at', [$from->utc(), $to->utc()])->orderBy('starts_at')->get()->map(fn ($appointment) => [
            'id' => $appointment->id, 'title' => $appointment->service->name, 'start' => $appointment->starts_at->toIso8601String(), 'end' => $appointment->ends_at->toIso8601String(),
            'status' => $appointment->status->value, 'method' => $appointment->consultation_method, 'patient' => $appointment->patient->only('id', 'name', 'phone'),
        ]);

        return response()->json(['data' => $events, 'timezone' => 'Africa/Lagos']);
    }
}
