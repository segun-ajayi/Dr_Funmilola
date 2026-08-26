<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\AvailabilityRule;
use App\Models\Service;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class AvailabilityService
{
    public function slots(Service $service, CarbonImmutable $date, string $method): Collection
    {
        if ($date->isPast() && ! $date->isToday()) {
            return collect();
        }
        $rules = AvailabilityRule::query()->where('weekday', $date->dayOfWeekIso)->where('is_active', true)->whereIn('consultation_method', ['both', $method])->get();

        return $rules->flatMap(function (AvailabilityRule $rule) use ($date, $service) {
            $cursor = $date->setTimeFromTimeString($rule->start_time);
            $limit = $date->setTimeFromTimeString($rule->end_time);
            $step = $service->duration_minutes + $rule->buffer_minutes;
            $slots = collect();
            while ($cursor->addMinutes($service->duration_minutes)->lessThanOrEqualTo($limit)) {
                $end = $cursor->addMinutes($service->duration_minutes);
                if ($cursor->isFuture() && ! $this->hasConflict($cursor, $end)) {
                    $slots->push(['starts_at' => $cursor->toIso8601String(), 'ends_at' => $end->toIso8601String(), 'label' => $cursor->format('g:i A')]);
                }
                $cursor = $cursor->addMinutes($step);
            }

            return $slots;
        })->values();
    }

    public function hasConflict(CarbonImmutable $start, CarbonImmutable $end, ?int $exceptAppointmentId = null): bool
    {
        return Appointment::query()->when($exceptAppointmentId, fn ($query) => $query->whereKeyNot($exceptAppointmentId))->whereNotIn('status', [AppointmentStatus::Cancelled->value, AppointmentStatus::Rescheduled->value, AppointmentStatus::NoShow->value])->where('starts_at', '<', $end->utc())->where('ends_at', '>', $start->utc())->exists();
    }
}
