<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\Service;
use App\Models\User;
use App\Services\AvailabilityService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AppointmentRequestController extends Controller
{
    public function __invoke(Request $request, AvailabilityService $availability): JsonResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:150'], 'email' => ['required', 'email', 'max:255'], 'phone' => ['required', 'string', 'max:30'], 'service_id' => ['required', 'exists:services,id'], 'starts_at' => ['required', 'date'], 'consultation_method' => ['required', 'in:online,in_person'], 'reason' => ['required', 'string', 'min:10', 'max:2000']]);
        $service = Service::findOrFail($data['service_id']);
        $start = CarbonImmutable::parse($data['starts_at'])->setTimezone('Africa/Lagos');
        $end = $start->addMinutes($service->duration_minutes);
        if (! $start->isFuture()) {
            throw ValidationException::withMessages(['starts_at' => 'Please select a future appointment time.']);
        }

        $appointment = DB::transaction(function () use ($data, $start, $end, $availability) {
            if ($availability->hasConflict($start, $end)) {
                throw ValidationException::withMessages(['starts_at' => 'This appointment slot is no longer available. Please select another time.']);
            }
            $patient = User::firstOrCreate(['email' => mb_strtolower($data['email'])], ['name' => $data['name'], 'phone' => $data['phone'], 'role' => UserRole::Patient, 'password' => Hash::make(Str::password(32))]);
            $appointment = Appointment::create(['public_id' => Str::uuid(), 'patient_id' => $patient->id, 'service_id' => $data['service_id'], 'starts_at' => $start, 'ends_at' => $end, 'timezone' => 'Africa/Lagos', 'status' => 'requested', 'consultation_method' => $data['consultation_method'], 'reason' => $data['reason'], 'location' => $data['consultation_method'] === 'in_person' ? 'Practice location shared after confirmation' : null]);
            AuditLog::create(['actor_id' => $patient->id, 'action' => 'appointment.requested', 'subject_type' => Appointment::class, 'subject_id' => $appointment->id, 'metadata' => ['method' => $data['consultation_method']]]);

            return $appointment;
        });

        return response()->json(['message' => 'Your appointment request has been received. The practice will contact you to confirm it.', 'reference' => $appointment->public_id], 201);
    }
}
