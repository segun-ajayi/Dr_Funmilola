<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Consultation;
use App\Services\AppointmentWorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobilePatientController extends Controller
{
    public function capabilities(): JsonResponse
    {
        return response()->json(['data' => [
            'api_version' => 'v1',
            'practice_timezone' => 'Africa/Lagos',
            'features' => [
                'appointments' => true,
                'messages' => true,
                'documents' => true,
                'consultations' => true,
                'push_notifications' => false,
                'live_video' => false,
            ],
            'uploads' => ['max_bytes' => 10485760, 'mime_types' => ['application/pdf', 'image/jpeg', 'image/png']],
        ]]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load('patientProfile');
        $profile = $user->patientProfile;

        return response()->json(['data' => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'profile' => [
                'date_of_birth' => $profile?->date_of_birth?->toDateString(),
                'address' => $profile?->address,
                'emergency_contact_name' => $profile?->emergency_contact_name,
                'emergency_contact_phone' => $profile?->emergency_contact_phone,
                'preferred_communication' => $profile?->preferred_communication ?? 'email',
            ],
        ]]);
    }

    public function appointments(Request $request, AppointmentWorkflowService $workflow): JsonResponse
    {
        return $this->page($request->user()->appointments()
            ->with(['service:id,name,slug', 'consultation:id,appointment_id,status', 'cancellationRequest:id,appointment_id,status', 'rescheduleRequest:id,appointment_id,status,requested_starts_at'])
            ->latest('starts_at')->paginate($this->size($request))
            ->through(function (Appointment $appointment) use ($workflow) {
                $appointment->setAttribute('allowed_actions', $workflow->allowedPatientActions($appointment));

                return $appointment;
            }));
    }

    public function documents(Request $request): JsonResponse
    {
        return $this->page($request->user()->documents()->latest()->paginate($this->size($request))
            ->through(fn ($document) => $document->makeHidden(['storage_path', 'patient_id'])));
    }

    public function threads(Request $request): JsonResponse
    {
        return $this->page($request->user()->messageThreads()
            ->with(['messages' => fn ($query) => $query->with('sender:id,name,role')->oldest()])
            ->latest('last_message_at')->paginate($this->size($request)));
    }

    public function notifications(Request $request): JsonResponse
    {
        return $this->page($request->user()->notifications()->latest()->paginate($this->size($request)));
    }

    public function consultations(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $consultations = Consultation::whereHas('appointment', fn ($query) => $query->where('patient_id', $userId))
            ->with(['appointment.service:id,name', 'consents' => fn ($query) => $query->where('patient_id', $userId)->where('consent_version', 'v1')])
            ->latest()->paginate($this->size($request))
            ->through(function (Consultation $consultation) {
                $consultation->setAttribute('has_consent', $consultation->consents->isNotEmpty());

                return $consultation->makeHidden(['room_locator', 'consents']);
            });

        return $this->page($consultations);
    }

    public function registerPush(): JsonResponse
    {
        abort(409, 'Push notifications are unavailable until the native push provider is configured.');
    }

    private function size(Request $request): int
    {
        return min(max((int) $request->query('per_page', 20), 1), 50);
    }

    private function page($paginator): JsonResponse
    {
        return response()->json([
            'data' => $paginator->items(),
            'meta' => ['current_page' => $paginator->currentPage(), 'per_page' => $paginator->perPage(), 'total' => $paginator->total(), 'last_page' => $paginator->lastPage()],
            'links' => ['next' => $paginator->nextPageUrl(), 'previous' => $paginator->previousPageUrl()],
        ]);
    }
}
