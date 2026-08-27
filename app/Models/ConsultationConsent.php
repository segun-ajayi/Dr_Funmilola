<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ConsultationConsent extends Model { protected $guarded=[];protected function casts(): array{return ['accepted_at'=>'datetime'];} }
