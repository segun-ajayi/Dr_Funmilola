<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AvailabilityException extends Model
{
    protected $guarded=[];
    protected function casts(): array { return ['starts_at'=>'immutable_datetime','ends_at'=>'immutable_datetime']; }
}
