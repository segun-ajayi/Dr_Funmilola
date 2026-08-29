<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Publication extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['published_at' => 'date', 'featured' => 'boolean', 'keywords' => 'array'];
    }

    public function sourceClaim()
    {
        return $this->belongsTo(ResearchClaim::class, 'source_claim_id');
    }
}
