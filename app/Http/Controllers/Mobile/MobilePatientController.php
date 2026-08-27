<?php
namespace App\Http\Controllers\Mobile;
use App\Http\Controllers\Controller;use Illuminate\Http\JsonResponse;use Illuminate\Http\Request;
class MobilePatientController extends Controller{
 public function capabilities():JsonResponse{return response()->json(['data'=>['api_version'=>'v1','practice_timezone'=>'Africa/Lagos','features'=>['appointments'=>true,'messages'=>true,'documents'=>true,'consultations'=>true,'push_notifications'=>false,'live_video'=>false],'uploads'=>['max_bytes'=>10485760,'mime_types'=>['application/pdf','image/jpeg','image/png']]]]);}
 public function me(Request $request):JsonResponse{$u=$request->user()->load('patientProfile');return response()->json(['data'=>['id'=>$u->id,'name'=>$u->name,'email'=>$u->email,'phone'=>$u->phone,'profile'=>$u->patientProfile]]);}
 public function appointments(Request $request):JsonResponse{return $this->page($request->user()->appointments()->with(['service:id,name,slug','consultation:id,appointment_id,status'])->latest('starts_at')->paginate($this->size($request)));}
 public function documents(Request $request):JsonResponse{return $this->page($request->user()->documents()->latest()->paginate($this->size($request))->through(fn($d)=>$d->makeHidden(['storage_path','patient_id'])));}
 public function threads(Request $request):JsonResponse{return $this->page($request->user()->messageThreads()->with(['messages'=>fn($q)=>$q->with('sender:id,name,role')->oldest()])->latest('last_message_at')->paginate($this->size($request)));}
 public function notifications(Request $request):JsonResponse{return $this->page($request->user()->notifications()->latest()->paginate($this->size($request)));}
 public function consultations(Request $request):JsonResponse{return $this->page(\App\Models\Consultation::whereHas('appointment',fn($q)=>$q->where('patient_id',$request->user()->id))->with('appointment.service:id,name')->latest()->paginate($this->size($request))->through(fn($c)=>$c->makeHidden('room_locator')));}
 public function registerPush():JsonResponse{abort(409,'Push notifications are unavailable until the native push provider is configured.');}
 private function size(Request $request):int{return min(max((int)$request->query('per_page',20),1),50);}
 private function page($paginator):JsonResponse{return response()->json(['data'=>$paginator->items(),'meta'=>['current_page'=>$paginator->currentPage(),'per_page'=>$paginator->perPage(),'total'=>$paginator->total(),'last_page'=>$paginator->lastPage()],'links'=>['next'=>$paginator->nextPageUrl(),'previous'=>$paginator->previousPageUrl()]]);}
}
