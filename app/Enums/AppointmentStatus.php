<?php

namespace App\Enums;

enum AppointmentStatus: string
{
    case Requested = 'requested';
    case PendingConfirmation = 'pending_confirmation';
    case Confirmed = 'confirmed';
    case CheckedIn = 'checked_in';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Rescheduled = 'rescheduled';
    case NoShow = 'no_show';
}
