<?php

namespace App\Http\Controllers\Staff;

use App\Enums\AppointmentStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $today = [now('Africa/Lagos')->startOfDay()->utc(), now('Africa/Lagos')->endOfDay()->utc()];
        $base = Appointment::query();

        return response()->json([
            'role' => $request->user()->role->value,
            'metrics' => [
                'today' => (clone $base)->whereBetween('starts_at', $today)->where('status', '!=', AppointmentStatus::Cancelled)->count(),
                'pending' => (clone $base)->whereIn('status', [AppointmentStatus::Requested, AppointmentStatus::PendingConfirmation])->count(),
                'online_today' => (clone $base)->whereBetween('starts_at', $today)->where('consultation_method', 'online')->where('status', '!=', AppointmentStatus::Cancelled)->count(),
                'unclaimed_patients' => User::where('role', UserRole::Patient)->whereNull('account_claimed_at')->count(),
            ],
            'today_appointments' => Appointment::with(['patient:id,name,phone', 'service:id,name'])->whereBetween('starts_at', $today)->orderBy('starts_at')->limit(12)->get(),
            'pending_requests' => Appointment::with(['patient:id,name,phone', 'service:id,name'])->whereIn('status', [AppointmentStatus::Requested, AppointmentStatus::PendingConfirmation])->oldest()->limit(8)->get(),
        ]);
    }
}
