<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Consultation extends Model
{
    protected $guarded=[];
    protected function casts(): array { return ['room_locator'=>'encrypted','patient_waiting_at'=>'datetime','admitted_at'=>'datetime','started_at'=>'datetime','ended_at'=>'datetime']; }
    public function appointment(){return $this->belongsTo(Appointment::class);}
    public function consents(){return $this->hasMany(ConsultationConsent::class);}
    public function attendances(){return $this->hasMany(ConsultationAttendance::class);}
}
