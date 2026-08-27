<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ConsultationAttendance extends Model { protected $guarded=[];protected function casts(): array{return ['joined_at'=>'datetime','left_at'=>'datetime'];} }
