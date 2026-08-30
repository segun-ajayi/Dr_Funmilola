<?php

namespace App\Http\Controllers\Mobile;

use App\Contracts\VideoProviderInterface;
use App\Http\Controllers\Controller;
use App\Models\Consultation;
use App\Models\MessageThread;
use App\Models\PatientDocument;
use App\Models\User;
use App\Notifications\PortalActivityNotification;
use App\Services\AuditService;
use App\Services\ConsultationService;
use App\Services\MobileMutationService;
use App\Services\Security\SecureDocumentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MobileOperationsController extends Controller
{
    public function updateProfile(Request $request, MobileMutationService $mutations, AuditService $audit): JsonResponse
    {
        $data = $request->validate([
            'client_request_id' => ['required', 'uuid'],
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:30'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'address' => ['nullable', 'string', 'max:500'],
            'emergency_contact_name' => ['nullable', 'string', 'max:120'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:30'],
            'preferred_communication' => ['required', Rule::in(['email', 'phone', 'sms'])],
        ]);

        return $this->mutation($mutations->run($request->user(), $data['client_request_id'], 'profile.update', function () use ($request, $data, $audit) {
            $user = $request->user();
            $before = $this->profilePayload($user);
            $user->update(['name' => $data['name'], 'phone' => $data['phone'] ?? null]);
            $user->patientProfile()->updateOrCreate([], collect($data)->except(['client_request_id', 'name', 'phone'])->all());
            $after = $this->profilePayload($user->fresh());
            $audit->record($user, 'patient.profile_updated', $user, $audit->changes($before, $after, array_keys($after)), $request);

            return [['data' => $after, 'message' => 'Your profile has been updated.'], 200];
        }));
    }

    public function uploadDocument(Request $request, MobileMutationService $mutations, SecureDocumentService $documents): JsonResponse
    {
        $data = $request->validate([
            'client_request_id' => ['required', 'uuid'],
            'label' => ['required', 'string', 'max:120'],
            'document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ]);

        return $this->mutation($mutations->run($request->user(), $data['client_request_id'], 'document.upload', function () use ($request, $data, $documents) {
            $document = $documents->store($data['document'], $request->user(), $request->user(), $data['label'], ipAddress: $request->ip());

            return [['data' => $document->makeHidden(['storage_path', 'patient_id']), 'message' => 'Document scanned and stored securely.'], 201];
        }));
    }

    public function downloadDocument(Request $request, PatientDocument $document, AuditService $audit): StreamedResponse
    {
        abort_unless($document->patient_id === $request->user()->id, 403);
        abort_unless(Storage::disk('local')->exists($document->storage_path), 404);
        $audit->record($request->user(), 'document.downloaded', $document, ['owner_id' => $document->patient_id], $request);

        return Storage::disk('local')->download($document->storage_path, $document->original_name, ['Content-Type' => $document->mime_type]);
    }

    public function createThread(Request $request, MobileMutationService $mutations): JsonResponse
    {
        $data = $request->validate([
            'client_request_id' => ['required', 'uuid'],
            'subject' => ['required', 'string', 'max:150'],
            'body' => ['required', 'string', 'max:4000'],
        ]);

        return $this->mutation($mutations->run($request->user(), $data['client_request_id'], 'message.thread.create', function () use ($request, $data) {
            $thread = DB::transaction(function () use ($request, $data) {
                $thread = MessageThread::create([
                    'public_id' => (string) Str::uuid(),
                    'patient_id' => $request->user()->id,
                    'subject' => $data['subject'],
                    'last_message_at' => now(),
                ]);
                $thread->messages()->create(['sender_id' => $request->user()->id, 'body' => $data['body']]);

                return $thread;
            });
            $this->notifyStaff('New patient message', $thread->subject);

            return [['data' => $thread->load('messages.sender:id,name,role'), 'message' => 'Your message has been sent.'], 201];
        }));
    }

    public function reply(Request $request, MessageThread $thread, MobileMutationService $mutations): JsonResponse
    {
        abort_unless($thread->patient_id === $request->user()->id, 403);
        $data = $request->validate(['client_request_id' => ['required', 'uuid'], 'body' => ['required', 'string', 'max:4000']]);

        return $this->mutation($mutations->run($request->user(), $data['client_request_id'], 'message.reply.'.$thread->id, function () use ($request, $data, $thread) {
            $message = $thread->messages()->create(['sender_id' => $request->user()->id, 'body' => $data['body']]);
            $thread->update(['last_message_at' => now()]);
            $this->notifyStaff('Patient replied', $thread->subject);

            return [['data' => $message->load('sender:id,name,role'), 'message' => 'Your reply has been sent.'], 201];
        }));
    }

    public function readNotification(Request $request, string $id, MobileMutationService $mutations): JsonResponse
    {
        $data = $request->validate(['client_request_id' => ['required', 'uuid']]);
        $notification = $request->user()->notifications()->findOrFail($id);

        return $this->mutation($mutations->run($request->user(), $data['client_request_id'], 'notification.read.'.$id, function () use ($notification) {
            $notification->markAsRead();

            return [['data' => ['id' => $notification->id, 'read_at' => $notification->fresh()->read_at], 'message' => 'Notification marked as read.'], 200];
        }));
    }

    public function preferences(Request $request): JsonResponse
    {
        return response()->json(['data' => $request->user()->notificationPreference()->firstOrCreate([])]);
    }

    public function updatePreferences(Request $request, MobileMutationService $mutations): JsonResponse
    {
        $data = $request->validate([
            'client_request_id' => ['required', 'uuid'],
            'in_app_reminders' => ['required', 'boolean'],
            'email_reminders' => ['required', 'boolean'],
            'push_reminders' => ['required', 'boolean'],
        ]);
        abort_if($data['push_reminders'], 422, 'Push reminders are unavailable until the native push provider is configured.');

        return $this->mutation($mutations->run($request->user(), $data['client_request_id'], 'notification_preferences.update', function () use ($request, $data) {
            $preference = $request->user()->notificationPreference()->updateOrCreate([], collect($data)->except('client_request_id')->all());

            return [['data' => $preference, 'message' => 'Reminder preferences saved.'], 200];
        }));
    }

    public function consultation(Request $request, Consultation $consultation): JsonResponse
    {
        $this->authorizePatient($request, $consultation);

        return response()->json(['data' => $this->consultationPayload($consultation, $request)]);
    }

    public function consent(Request $request, Consultation $consultation, MobileMutationService $mutations): JsonResponse
    {
        $this->authorizePatient($request, $consultation);
        $data = $request->validate(['client_request_id' => ['required', 'uuid'], 'accepted' => ['accepted']]);

        return $this->mutation($mutations->run($request->user(), $data['client_request_id'], 'consultation.consent.'.$consultation->id, function () use ($request, $consultation) {
            $consultation->consents()->firstOrCreate(
                ['patient_id' => $request->user()->id, 'consent_version' => 'v1'],
                ['accepted_at' => now(), 'ip_address' => $request->ip()],
            );

            return [['data' => $this->consultationPayload($consultation, $request), 'message' => 'Consultation consent recorded.'], 201];
        }));
    }

    public function wait(Request $request, Consultation $consultation, MobileMutationService $mutations, ConsultationService $service): JsonResponse
    {
        $this->authorizePatient($request, $consultation);
        $data = $request->validate(['client_request_id' => ['required', 'uuid']]);

        return $this->mutation($mutations->run($request->user(), $data['client_request_id'], 'consultation.wait.'.$consultation->id, function () use ($request, $consultation, $service) {
            abort_unless($service->isWithinJoinWindow($consultation), 403, 'The waiting room opens 30 minutes before the appointment.');
            abort_unless($consultation->consents()->where('patient_id', $request->user()->id)->where('consent_version', 'v1')->exists(), 422, 'Consultation consent is required.');
            if ($consultation->status === 'scheduled') {
                $service->transition($consultation, 'waiting', $request->user());
            }

            return [['data' => $this->consultationPayload($consultation->fresh(), $request), 'message' => 'You are in the waiting room.'], 200];
        }));
    }

    public function join(Request $request, Consultation $consultation, MobileMutationService $mutations, ConsultationService $service, VideoProviderInterface $provider): JsonResponse
    {
        $this->authorizePatient($request, $consultation);
        $data = $request->validate(['client_request_id' => ['required', 'uuid']]);

        return $this->mutation($mutations->run($request->user(), $data['client_request_id'], 'consultation.join.'.$consultation->id, function () use ($request, $consultation, $service, $provider) {
            abort_unless($service->isWithinJoinWindow($consultation), 403, 'This consultation is outside its join window.');
            abort_unless(in_array($consultation->status, ['ready', 'in_progress'], true), 403, 'Please wait for the practice team to admit you.');
            abort_unless($consultation->consents()->where('patient_id', $request->user()->id)->where('consent_version', 'v1')->exists(), 403, 'Consultation consent is required.');
            $attendance = $consultation->attendances()->create(['user_id' => $request->user()->id, 'participant_role' => 'patient', 'joined_at' => now()]);

            return [['data' => [
                'consultation_id' => $consultation->public_id,
                'attendance_id' => $attendance->id,
                'configuration' => $provider->participantConfiguration($consultation, $request->user()->name, 'patient'),
            ], 'message' => 'Consultation connection prepared.'], 200];
        }));
    }

    public function leave(Request $request, Consultation $consultation, MobileMutationService $mutations): JsonResponse
    {
        $this->authorizePatient($request, $consultation);
        $data = $request->validate(['client_request_id' => ['required', 'uuid']]);

        return $this->mutation($mutations->run($request->user(), $data['client_request_id'], 'consultation.leave.'.$consultation->id, function () use ($request, $consultation) {
            $attendance = $consultation->attendances()->where('user_id', $request->user()->id)->whereNull('left_at')->latest()->firstOrFail();
            $attendance->update(['left_at' => now()]);

            return [['data' => null, 'message' => 'You have left the consultation room.'], 200];
        }));
    }

    private function profilePayload(User $user): array
    {
        $user->load('patientProfile');
        $profile = $user->patientProfile;

        return [
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
        ];
    }

    private function consultationPayload(Consultation $consultation, Request $request): array
    {
        $consultation->load('appointment.service:id,name');

        return [
            ...$consultation->makeHidden(['room_locator'])->toArray(),
            'has_consent' => $consultation->consents()->where('patient_id', $request->user()->id)->where('consent_version', 'v1')->exists(),
        ];
    }

    private function authorizePatient(Request $request, Consultation $consultation): void
    {
        $consultation->loadMissing('appointment');
        abort_unless($consultation->appointment->patient_id === $request->user()->id, 403);
    }

    private function notifyStaff(string $title, string $subject): void
    {
        Notification::send(
            User::query()->whereIn('role', ['admin', 'moderator', 'power_admin'])->where('is_active', true)->get(),
            new PortalActivityNotification($title, $subject, 'message'),
        );
    }

    private function mutation(array $result): JsonResponse
    {
        return response()->json(
            $result['body'],
            $result['status'],
            ['X-Idempotent-Replay' => $result['replayed'] ? 'true' : 'false'],
        );
    }
}
