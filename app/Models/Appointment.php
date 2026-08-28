<?php

namespace App\Models;

use App\Enums\AppointmentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Appointment extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['starts_at' => 'immutable_datetime', 'ends_at' => 'immutable_datetime', 'confirmed_at' => 'datetime', 'status' => AppointmentStatus::class];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(PatientDocument::class);
    }

    public function cancellationRequest() { return $this->hasOne(AppointmentCancellationRequest::class); }
    public function rescheduleRequest() { return $this->hasOne(AppointmentRescheduleRequest::class); }
    public function consultation() { return $this->hasOne(Consultation::class); }
}
