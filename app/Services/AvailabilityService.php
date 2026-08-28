<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\AvailabilityException;
use App\Models\AvailabilityRule;
use App\Models\Service;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AvailabilityService
{
    public function slots(Service $service, CarbonImmutable $date, string $method, ?int $exceptAppointmentId = null): Collection
    {
        if (($method === 'online' && ! $service->online_available) || ($date->isPast() && ! $date->isToday())) {
            return collect();
        }

        $dayStart = $date->startOfDay()->utc();
        $dayEnd = $date->endOfDay()->utc();
        $exceptions = AvailabilityException::query()->where('starts_at', '<', $dayEnd)->where('ends_at', '>', $dayStart)->get();
        $closures = $exceptions->where('kind', 'closed');
        $rules = AvailabilityRule::query()->where('weekday', $date->dayOfWeekIso)->where('is_active', true)->whereIn('consultation_method', ['both', $method])->get();

        $regular = $rules->flatMap(function (AvailabilityRule $rule) use ($date, $service, $closures, $exceptAppointmentId) {
            $cursor = $date->setTimeFromTimeString($rule->start_time);
            $limit = $date->setTimeFromTimeString($rule->end_time);
            $step = max($rule->slot_minutes, $service->duration_minutes + $rule->buffer_minutes);

            return $this->slotsWithinWindow($cursor, $limit, $step, $service, $closures, $exceptAppointmentId);
        });

        $additional = $exceptions->where('kind', 'additional')->whereIn('consultation_method', ['both', $method])->flatMap(function (AvailabilityException $exception) use ($date, $service, $closures, $exceptAppointmentId) {
            $cursor = CarbonImmutable::parse($exception->starts_at)->setTimezone($date->timezone);
            $limit = CarbonImmutable::parse($exception->ends_at)->setTimezone($date->timezone);

            return $this->slotsWithinWindow($cursor, $limit, $service->duration_minutes, $service, $closures, $exceptAppointmentId);
        });

        return $regular->concat($additional)->unique('starts_at')->sortBy('starts_at')->values();
    }

    public function isBookableSlot(Service $service, CarbonImmutable $start, string $method, ?int $exceptAppointmentId = null): bool
    {
        $localStart = $start->setTimezone('Africa/Lagos');

        return $this->slots($service, $localStart->startOfDay(), $method, $exceptAppointmentId)
            ->contains(fn (array $slot) => CarbonImmutable::parse($slot['starts_at'])->equalTo($localStart));
    }

    public function lockSchedule(CarbonImmutable $start): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        $lockKey = hexdec(substr(hash('sha256', 'appointment-schedule:'.$start->setTimezone('Africa/Lagos')->toDateString()), 0, 7));
        DB::select('select pg_advisory_xact_lock(?)', [$lockKey]);
    }

    public function hasConflict(CarbonImmutable $start, CarbonImmutable $end, ?int $exceptAppointmentId = null): bool
    {
        return Appointment::query()->when($exceptAppointmentId, fn ($query) => $query->whereKeyNot($exceptAppointmentId))->whereNotIn('status', [AppointmentStatus::Cancelled->value, AppointmentStatus::Rescheduled->value, AppointmentStatus::NoShow->value])->where('starts_at', '<', $end->utc())->where('ends_at', '>', $start->utc())->exists();
    }

    private function slotsWithinWindow(CarbonImmutable $cursor, CarbonImmutable $limit, int $step, Service $service, Collection $closures, ?int $exceptAppointmentId): Collection
    {
        $slots = collect();
        while ($cursor->addMinutes($service->duration_minutes)->lessThanOrEqualTo($limit)) {
            $end = $cursor->addMinutes($service->duration_minutes);
            $blocked = $closures->contains(fn (AvailabilityException $closure) => $closure->starts_at->lessThan($end->utc()) && $closure->ends_at->greaterThan($cursor->utc()));
            if ($cursor->isFuture() && ! $blocked && ! $this->hasConflict($cursor, $end, $exceptAppointmentId)) {
                $slots->push(['starts_at' => $cursor->toIso8601String(), 'ends_at' => $end->toIso8601String(), 'label' => $cursor->format('g:i A')]);
            }
            $cursor = $cursor->addMinutes($step);
        }

        return $slots;
    }
}
