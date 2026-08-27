<?php
namespace App\Http\Controllers\Staff;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Consultation;
use App\Services\ConsultationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConsultationController extends Controller
{
    public function index():JsonResponse{return response()->json(['data'=>Consultation::with(['appointment.patient:id,name,email,phone','appointment.service:id,name'])->latest()->limit(50)->get()->makeHidden('room_locator')]);}
    public function store(Request $request,Appointment $appointment,ConsultationService $service):JsonResponse{return response()->json(['data'=>$service->create($appointment->loadMissing('consultation'),$request->user())->makeHidden('room_locator')],201);}
    public function transition(Request $request,Consultation $consultation,ConsultationService $service):JsonResponse{$data=$request->validate(['status'=>['required','in:ready,in_progress,ended']]);return response()->json(['data'=>$service->transition($consultation,$data['status'],$request->user())]);}
}
