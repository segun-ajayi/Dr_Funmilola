<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\User;
use App\Notifications\PortalActivityNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class CancellationRequestController extends Controller
{
    public function store(Request $request, Appointment $appointment): JsonResponse
    {
        $this->authorize('update', $appointment);
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:1000']]);
        $item = $appointment->cancellationRequest()->firstOrCreate(['patient_id' => $request->user()->id], ['reason' => $data['reason'] ?? null]);
        AuditLog::create(['actor_id' => $request->user()->id, 'action' => 'appointment.cancellation_requested', 'subject_type' => Appointment::class, 'subject_id' => $appointment->id]);
        Notification::send(User::query()->whereIn('role', ['admin','moderator','power_admin'])->where('is_active', true)->get(), new PortalActivityNotification('Cancellation request', 'A patient has requested an appointment cancellation.', 'cancellation'));
        return response()->json(['data' => $item, 'message' => 'Your request has been sent to the practice team.'], 201);
    }
}
