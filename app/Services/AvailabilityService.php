<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\AvailabilityRule;
use App\Models\AvailabilityException;
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
        $dayStart=$date->startOfDay()->utc(); $dayEnd=$date->endOfDay()->utc();
        $exceptions=AvailabilityException::query()->where('starts_at','<',$dayEnd)->where('ends_at','>',$dayStart)->get();
        $closures=$exceptions->where('kind','closed');
        $rules = AvailabilityRule::query()->where('weekday', $date->dayOfWeekIso)->where('is_active', true)->whereIn('consultation_method', ['both', $method])->get();

        return $rules->flatMap(function (AvailabilityRule $rule) use ($date, $service, $closures) {
            $cursor = $date->setTimeFromTimeString($rule->start_time);
            $limit = $date->setTimeFromTimeString($rule->end_time);
            $step = $service->duration_minutes + $rule->buffer_minutes;
            $slots = collect();
            while ($cursor->addMinutes($service->duration_minutes)->lessThanOrEqualTo($limit)) {
                $end = $cursor->addMinutes($service->duration_minutes);
                $blocked=$closures->contains(fn($exception)=>$exception->starts_at->lessThan($end->utc()) && $exception->ends_at->greaterThan($cursor->utc()));
                if ($cursor->isFuture() && ! $blocked && ! $this->hasConflict($cursor, $end)) {
                    $slots->push(['starts_at' => $cursor->toIso8601String(), 'ends_at' => $end->toIso8601String(), 'label' => $cursor->format('g:i A')]);
                }
                $cursor = $cursor->addMinutes($step);
            }

            return $slots;
        })->concat($exceptions->where('kind','additional')->whereIn('consultation_method',['both',$method])->flatMap(function($exception) use($date,$service,$closures){
            $cursor=CarbonImmutable::parse($exception->starts_at)->setTimezone($date->timezone);$limit=CarbonImmutable::parse($exception->ends_at)->setTimezone($date->timezone);$slots=collect();
            while($cursor->addMinutes($service->duration_minutes)->lessThanOrEqualTo($limit)){$end=$cursor->addMinutes($service->duration_minutes);$blocked=$closures->contains(fn($closed)=>$closed->starts_at->lessThan($end->utc())&&$closed->ends_at->greaterThan($cursor->utc()));if($cursor->isFuture()&&!$blocked&&!$this->hasConflict($cursor,$end))$slots->push(['starts_at'=>$cursor->toIso8601String(),'ends_at'=>$end->toIso8601String(),'label'=>$cursor->format('g:i A')]);$cursor=$end;}
            return $slots;
        }))->unique('starts_at')->sortBy('starts_at')->values();
    }

    public function hasConflict(CarbonImmutable $start, CarbonImmutable $end, ?int $exceptAppointmentId = null): bool
    {
        return Appointment::query()->when($exceptAppointmentId, fn ($query) => $query->whereKeyNot($exceptAppointmentId))->whereNotIn('status', [AppointmentStatus::Cancelled->value, AppointmentStatus::Rescheduled->value, AppointmentStatus::NoShow->value])->where('starts_at', '<', $end->utc())->where('ends_at', '>', $start->utc())->exists();
    }
}
