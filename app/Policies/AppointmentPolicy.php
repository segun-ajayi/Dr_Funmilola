<?php

namespace App\Policies;

use App\Models\Appointment;
use App\Models\User;

class AppointmentPolicy
{
    public function view(User $user, Appointment $appointment): bool
    {
        return $user->isStaff() || $appointment->patient_id === $user->id;
    }

    public function update(User $user, Appointment $appointment): bool
    {
        return $user->isStaff() || ($appointment->patient_id === $user->id && in_array($appointment->status->value, ['requested', 'pending_confirmation', 'confirmed'], true));
    }
}
