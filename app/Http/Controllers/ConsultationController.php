<?php
namespace App\Http\Controllers;
use App\Contracts\VideoProviderInterface;
use App\Models\Consultation;
use App\Services\ConsultationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class ConsultationController extends Controller
{
    public function index(Request $request):JsonResponse{return response()->json(['data'=>Consultation::whereHas('appointment',fn($q)=>$q->where('patient_id',$request->user()->id))->with('appointment.service:id,name')->get()->makeHidden('room_locator')]);}
    public function show(Request $request,Consultation $consultation):JsonResponse{$this->authorizeParticipant($request,$consultation);return response()->json(['data'=>$consultation->load('appointment.service:id,name')->makeHidden('room_locator'),'has_consent'=>$consultation->consents()->where('patient_id',$request->user()->id)->where('consent_version','v1')->exists()]);}
    public function consent(Request $request,Consultation $consultation):JsonResponse
    {
        abort_unless($consultation->appointment->patient_id===$request->user()->id,403);$request->validate(['accepted'=>['accepted']]);$consent=$consultation->consents()->firstOrCreate(['patient_id'=>$request->user()->id,'consent_version'=>'v1'],['accepted_at'=>now(),'ip_address'=>$request->ip()]);return response()->json(['data'=>$consent],201);
    }
    public function wait(Request $request,Consultation $consultation,ConsultationService $service):JsonResponse
    {
        abort_unless($consultation->appointment->patient_id===$request->user()->id,403);abort_unless($service->isWithinJoinWindow($consultation),403,'The waiting room opens 30 minutes before the appointment.');abort_unless($consultation->consents()->where('patient_id',$request->user()->id)->where('consent_version','v1')->exists(),422,'Consultation consent is required.');if($consultation->status==='scheduled')$service->transition($consultation,'waiting',$request->user());return response()->json(['data'=>$consultation->fresh()->makeHidden('room_locator')]);
    }
    public function authorizeJoin(Request $request,Consultation $consultation,ConsultationService $service):JsonResponse
    {
        $this->authorizeParticipant($request,$consultation);abort_unless($service->isWithinJoinWindow($consultation),403,'This consultation is outside its join window.');if(!$request->user()->isStaff()){abort_unless($consultation->status==='ready'||$consultation->status==='in_progress',403,'Please wait for the practice team to admit you.');abort_unless($consultation->consents()->where('patient_id',$request->user()->id)->where('consent_version','v1')->exists(),403);}
        return response()->json(['join_url'=>URL::temporarySignedRoute('consultations.room',now()->addMinutes(10),['consultation'=>$consultation->id])]);
    }
    public function room(Request $request,Consultation $consultation,VideoProviderInterface $provider):JsonResponse
    {
        $this->authorizeParticipant($request,$consultation);abort_if($consultation->status==='ended',410,'This consultation has ended.');$role=$request->user()->isStaff()?'staff':'patient';$attendance=$consultation->attendances()->create(['user_id'=>$request->user()->id,'participant_role'=>$role,'joined_at'=>now()]);return response()->json(['consultation_id'=>$consultation->public_id,'attendance_id'=>$attendance->id,'configuration'=>$provider->participantConfiguration($consultation,$request->user()->name,$role)]);
    }
    public function leave(Request $request,Consultation $consultation):JsonResponse{$this->authorizeParticipant($request,$consultation);$attendance=$consultation->attendances()->where('user_id',$request->user()->id)->whereNull('left_at')->latest()->firstOrFail();$attendance->update(['left_at'=>now()]);return response()->json(['message'=>'You have left the consultation room.']);}
    private function authorizeParticipant(Request $request,Consultation $consultation):void{$consultation->loadMissing('appointment');abort_unless($request->user()->isStaff()||$consultation->appointment->patient_id===$request->user()->id,403);}
}
