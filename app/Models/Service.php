<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['online_available' => 'boolean', 'is_active' => 'boolean'];
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }
}
