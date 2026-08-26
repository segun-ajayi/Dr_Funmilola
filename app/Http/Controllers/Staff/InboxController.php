<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\AppointmentCancellationRequest;
use App\Models\AuditLog;
use App\Models\MessageThread;
use App\Models\User;
use App\Notifications\PortalActivityNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InboxController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'threads'=>MessageThread::with(['patient:id,name,email,phone','messages'=>fn($q)=>$q->with('sender:id,name,role')->oldest()])->latest('last_message_at')->limit(30)->get(),
            'cancellations'=>AppointmentCancellationRequest::with(['appointment.patient:id,name,email,phone','appointment.service:id,name'])->where('status','pending')->oldest()->get(),
        ]);
    }
    public function reply(Request $request, MessageThread $thread): JsonResponse
    {
        $data=$request->validate(['body'=>['required','string','max:4000']]);$message=$thread->messages()->create(['sender_id'=>$request->user()->id,'body'=>$data['body']]);$thread->update(['last_message_at'=>now()]);
        $thread->patient->notify(new PortalActivityNotification('Practice reply',$thread->subject,'message'));
        return response()->json(['data'=>$message->load('sender:id,name,role')],201);
    }
    public function reviewCancellation(Request $request, AppointmentCancellationRequest $cancellation): JsonResponse
    {
        $data=$request->validate(['decision'=>['required','in:approved,declined']]);
        $cancellation->update(['status'=>$data['decision'],'reviewed_by'=>$request->user()->id,'reviewed_at'=>now()]);
        if($data['decision']==='approved') app(\App\Services\AppointmentWorkflowService::class)->transition($cancellation->appointment,\App\Enums\AppointmentStatus::Cancelled,$request->user(),'Cancelled following patient request.');
        AuditLog::create(['actor_id'=>$request->user()->id,'action'=>'appointment.cancellation_reviewed','subject_type'=>$cancellation::class,'subject_id'=>$cancellation->id,'metadata'=>['decision'=>$data['decision']]]);
        $cancellation->appointment->patient->notify(new PortalActivityNotification('Cancellation request updated',"Your cancellation request was {$data['decision']}.",'cancellation'));
        return response()->json(['data'=>$cancellation->fresh()]);
    }
    public function patient(User $patient): JsonResponse
    {
        abort_unless($patient->role->value==='patient',404);
        return response()->json(['data'=>['patient'=>$patient->only(['id','name','email','phone']),'profile'=>$patient->patientProfile,'appointments'=>$patient->appointments()->with('service:id,name')->latest('starts_at')->limit(20)->get(),'documents'=>$patient->documents()->latest()->get()->makeHidden('storage_path'),'threads'=>$patient->messageThreads()->latest('last_message_at')->get()]]);
    }
}
