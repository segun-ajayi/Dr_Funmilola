<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppointmentCancellationRequest extends Model
{
    protected $guarded = [];
    protected function casts(): array { return ['reviewed_at' => 'datetime']; }
    public function appointment() { return $this->belongsTo(Appointment::class); }
}
