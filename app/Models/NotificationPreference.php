<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationPreference extends Model
{
    protected $guarded=[];
    protected function casts(): array { return ['in_app_reminders'=>'boolean','email_reminders'=>'boolean','push_reminders'=>'boolean']; }
}
