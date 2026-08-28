<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\AppointmentCancellationRequest;
use App\Models\AppointmentRescheduleRequest;
use App\Models\AuditLog;
use App\Models\MessageThread;
use App\Models\User;
use App\Notifications\PortalActivityNotification;
use App\Services\AppointmentWorkflowService;
use App\Services\AvailabilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InboxController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'threads'=>MessageThread::with(['patient:id,name,email,phone','messages'=>fn($q)=>$q->with('sender:id,name,role')->oldest()])->latest('last_message_at')->limit(30)->get(),
            'cancellations'=>AppointmentCancellationRequest::with(['appointment.patient:id,name,email,phone','appointment.service:id,name'])->where('status','pending')->oldest()->get(),
            'reschedules'=>AppointmentRescheduleRequest::with(['appointment.patient:id,name,email,phone','appointment.service:id,name'])->where('status','pending')->oldest()->get(),
        ]);
    }
    public function reply(Request $request, MessageThread $thread): JsonResponse
    {
        $data=$request->validate(['body'=>['required','string','max:4000']]);$message=$thread->messages()->create(['sender_id'=>$request->user()->id,'body'=>$data['body']]);$thread->update(['last_message_at'=>now()]);
        $thread->patient->notify(new PortalActivityNotification('Practice reply',$thread->subject,'message'));
        return response()->json(['data'=>$message->load('sender:id,name,role')],201);
    }
    public function reviewCancellation(Request $request, AppointmentCancellationRequest $cancellation, AppointmentWorkflowService $workflow): JsonResponse
    {
        $data=$request->validate(['decision'=>['required','in:approved,declined']]);
        return response()->json(['data'=>$workflow->reviewCancellation($cancellation, $data['decision'], $request->user())]);
    }
    public function reviewReschedule(Request $request, AppointmentRescheduleRequest $reschedule, AppointmentWorkflowService $workflow, AvailabilityService $availability): JsonResponse
    {
        $data=$request->validate(['decision'=>['required','in:approved,declined'],'note'=>['nullable','string','max:1000']]);
        return response()->json(['data'=>$workflow->reviewReschedule($reschedule, $data['decision'], $request->user(), $availability, $data['note']??null)]);
    }
    public function patient(User $patient): JsonResponse
    {
        abort_unless($patient->role->value==='patient',404);
        return response()->json(['data'=>['patient'=>$patient->only(['id','name','email','phone']),'profile'=>$patient->patientProfile,'appointments'=>$patient->appointments()->with('service:id,name')->latest('starts_at')->limit(20)->get(),'documents'=>$patient->documents()->latest()->get()->makeHidden('storage_path'),'threads'=>$patient->messageThreads()->latest('last_message_at')->get()]]);
    }
}
