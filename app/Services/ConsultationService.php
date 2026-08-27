<?php
namespace App\Services;
use App\Contracts\VideoProviderInterface;
use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\Consultation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ConsultationService
{
    private const TRANSITIONS=['scheduled'=>['waiting','ready'],'waiting'=>['ready'],'ready'=>['in_progress'],'in_progress'=>['ended'],'ended'=>[]];
    public function __construct(private readonly VideoProviderInterface $provider){}
    public function create(Appointment $appointment,User $actor): Consultation
    {
        if($appointment->consultation_method!=='online'||$appointment->status->value!=='confirmed')throw ValidationException::withMessages(['appointment'=>'Only confirmed online appointments can receive a consultation room.']);
        return DB::transaction(function()use($appointment,$actor){$reference=(string)Str::uuid();$room=$this->provider->createRoom($reference);$consultation=Consultation::firstOrCreate(['appointment_id'=>$appointment->id],['public_id'=>$reference,'provider_key'=>$room['provider_key'],'room_locator'=>$room['room_locator'],'created_by'=>$actor->id]);$this->audit($consultation,$actor,'consultation.created');return $consultation->load('appointment.patient:id,name,email');});
    }
    public function transition(Consultation $consultation,string $status,User $actor): Consultation
    {
        if(!in_array($status,self::TRANSITIONS[$consultation->status]??[],true))throw ValidationException::withMessages(['status'=>"Consultation cannot move from {$consultation->status} to {$status}."]);
        $fields=['status'=>$status];$column=match($status){'waiting'=>'patient_waiting_at','ready'=>'admitted_at','in_progress'=>'started_at','ended'=>'ended_at',default=>null};if($column)$fields[$column]=now();$consultation->update($fields);$this->audit($consultation,$actor,'consultation.status_changed',['to'=>$status]);return $consultation->fresh();
    }
    public function isWithinJoinWindow(Consultation $consultation): bool { $appointment=$consultation->appointment;return now()->between($appointment->starts_at->subMinutes(30),$appointment->ends_at->addMinutes(60)); }
    private function audit(Consultation $consultation,User $actor,string $action,array $metadata=[]):void{AuditLog::create(['actor_id'=>$actor->id,'action'=>$action,'subject_type'=>Consultation::class,'subject_id'=>$consultation->id,'metadata'=>$metadata]);}
}
