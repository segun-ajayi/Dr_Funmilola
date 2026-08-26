<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PatientProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->payload($request)]);
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'], 'phone' => ['nullable', 'string', 'max:30'],
            'date_of_birth' => ['nullable', 'date', 'before:today'], 'address' => ['nullable', 'string', 'max:500'],
            'emergency_contact_name' => ['nullable', 'string', 'max:120'], 'emergency_contact_phone' => ['nullable', 'string', 'max:30'],
            'preferred_communication' => ['required', Rule::in(['email', 'phone', 'sms'])],
        ]);
        $user = $request->user();
        $user->update(['name' => $data['name'], 'phone' => $data['phone'] ?? null]);
        $user->patientProfile()->updateOrCreate([], collect($data)->except(['name', 'phone'])->all());
        AuditLog::create(['actor_id' => $user->id, 'action' => 'patient.profile_updated', 'subject_type' => $user::class, 'subject_id' => $user->id, 'metadata' => ['fields' => array_keys($data)]]);

        return response()->json(['data' => $this->payload($request), 'message' => 'Your profile has been updated.']);
    }

    private function payload(Request $request): array
    {
        $user = $request->user()->load('patientProfile'); $profile = $user->patientProfile;
        return ['name' => $user->name, 'email' => $user->email, 'phone' => $user->phone, 'date_of_birth' => $profile?->date_of_birth?->toDateString(), 'address' => $profile?->address, 'emergency_contact_name' => $profile?->emergency_contact_name, 'emergency_contact_phone' => $profile?->emergency_contact_phone, 'preferred_communication' => $profile?->preferred_communication ?? 'email'];
    }
}
