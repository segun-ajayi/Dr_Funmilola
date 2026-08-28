<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\Service;
use App\Models\User;
use App\Services\AvailabilityService;
use App\Services\Security\SecureDocumentService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class AppointmentRequestController extends Controller
{
    public function __invoke(Request $request, AvailabilityService $availability, SecureDocumentService $documents): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'service_id' => ['required', 'exists:services,id'],
            'starts_at' => ['required', 'date'],
            'consultation_method' => ['required', 'in:online,in_person'],
            'reason' => ['required', 'string', 'min:10', 'max:2000'],
            'client_request_id' => ['nullable', 'uuid'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ]);
        $service = Service::findOrFail($data['service_id']);
        $start = CarbonImmutable::parse($data['starts_at'])->setTimezone('Africa/Lagos');
        if (! $start->isFuture()) {
            throw ValidationException::withMessages(['starts_at' => 'Please select a future appointment time.']);
        }

        $storedDocument = null;
        $wasDuplicate = false;

        try {
            $appointment = DB::transaction(function () use ($data, $service, $start, $availability, $documents, $request, &$storedDocument, &$wasDuplicate) {
                $availability->lockSchedule($start);

                if (! empty($data['client_request_id'])) {
                    $existing = Appointment::with('patient')->where('booking_request_id', $data['client_request_id'])->first();
                    if ($existing) {
                        if (mb_strtolower($existing->patient->email) !== mb_strtolower($data['email'])) {
                            throw ValidationException::withMessages(['client_request_id' => 'This booking request identifier cannot be reused.']);
                        }
                        $wasDuplicate = true;

                        return $existing;
                    }
                }

                if (! $availability->isBookableSlot($service, $start, $data['consultation_method'])) {
                    throw ValidationException::withMessages(['starts_at' => 'This appointment slot is unavailable or no longer current. Please refresh availability and select another time.']);
                }

                $end = $start->addMinutes($service->duration_minutes);
                $patient = User::firstOrCreate(
                    ['email' => mb_strtolower($data['email'])],
                    ['name' => $data['name'], 'phone' => $data['phone'], 'role' => UserRole::Patient, 'password' => Hash::make(Str::password(32))],
                );
                $appointment = Appointment::create([
                    'public_id' => Str::uuid(),
                    'booking_request_id' => $data['client_request_id'] ?? null,
                    'patient_id' => $patient->id,
                    'service_id' => $data['service_id'],
                    'starts_at' => $start->utc(),
                    'ends_at' => $end->utc(),
                    'timezone' => 'Africa/Lagos',
                    'status' => 'requested',
                    'consultation_method' => $data['consultation_method'],
                    'reason' => $data['reason'],
                    'location' => $data['consultation_method'] === 'in_person' ? 'Practice location shared after confirmation' : null,
                ]);

                if (($data['attachment'] ?? null) instanceof UploadedFile) {
                    $storedDocument = $documents->store(
                        $data['attachment'],
                        $patient,
                        $patient,
                        'Booking attachment',
                        $appointment,
                        $request->ip(),
                        'attachment',
                    );
                }

                AuditLog::create([
                    'actor_id' => $patient->id,
                    'action' => 'appointment.requested',
                    'subject_type' => Appointment::class,
                    'subject_id' => $appointment->id,
                    'metadata' => ['method' => $data['consultation_method'], 'has_attachment' => $storedDocument !== null],
                    'ip_address' => $request->ip(),
                ]);

                return $appointment;
            });
        } catch (Throwable $exception) {
            if ($storedDocument) {
                $documents->discard($storedDocument);
            }
            throw $exception;
        }

        return response()->json([
            'message' => $wasDuplicate
                ? 'This appointment request was already received. No duplicate was created.'
                : 'Your appointment request has been received. The practice will contact you to confirm it.',
            'reference' => $appointment->public_id,
        ], $wasDuplicate ? 200 : 201);
    }
}
