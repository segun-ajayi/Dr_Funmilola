<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResearchClaim extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['source_date' => 'date', 'reviewed_at' => 'datetime', 'target_payload' => 'array', 'retracted_at' => 'datetime'];
    }
}
