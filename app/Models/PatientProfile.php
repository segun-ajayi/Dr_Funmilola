<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PatientProfile extends Model
{
    protected $guarded = [];

    protected function casts(): array { return ['date_of_birth' => 'date']; }
}
