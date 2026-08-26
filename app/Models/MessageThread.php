<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MessageThread extends Model
{
    protected $guarded = [];
    protected function casts(): array { return ['last_message_at' => 'datetime']; }
    public function patient() { return $this->belongsTo(User::class, 'patient_id'); }
    public function messages() { return $this->hasMany(Message::class); }
}
