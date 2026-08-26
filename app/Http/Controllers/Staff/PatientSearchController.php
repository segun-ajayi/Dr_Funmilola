<?php

namespace App\Http\Controllers\Staff;

use App\Enums\AppointmentStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PatientSearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->validate(['q' => ['required', 'string', 'min:2', 'max:100']]);
        $term = str_replace(['%', '_'], ['\\%', '\\_'], $data['q']);
        $patients = User::query()->select('id', 'name', 'email', 'phone', 'created_at', 'account_claimed_at')->where('role', UserRole::Patient)->where(fn ($q) => $q->where('name', 'like', "%{$term}%")->orWhere('email', 'like', "%{$term}%")->orWhere('phone', 'like', "%{$term}%"))->withMin(['appointments as next_appointment_at' => fn ($q) => $q->where('starts_at', '>=', now())->whereNot('status', AppointmentStatus::Cancelled)], 'starts_at')->limit(20)->get();

        return response()->json(['data' => $patients]);
    }
}
