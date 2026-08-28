<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppointmentRescheduleRequest extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['requested_starts_at' => 'immutable_datetime', 'reviewed_at' => 'datetime'];
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function patient()
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
