<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Publication extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['published_at' => 'date', 'featured' => 'boolean'];
    }
}
