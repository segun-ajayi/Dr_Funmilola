<?php

namespace App\Http\Controllers;

use App\Models\MessageThread;
use App\Models\User;
use App\Notifications\PortalActivityNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class MessageThreadController extends Controller
{
    public function index(Request $request): JsonResponse { return response()->json(['data'=>$request->user()->messageThreads()->with(['messages'=>fn($q)=>$q->with('sender:id,name,role')->oldest()])->latest('last_message_at')->get()]); }
    public function store(Request $request): JsonResponse
    {
        $data=$request->validate(['subject'=>['required','string','max:150'],'body'=>['required','string','max:4000']]);
        $thread=DB::transaction(function() use($request,$data){$thread=MessageThread::create(['public_id'=>(string)Str::uuid(),'patient_id'=>$request->user()->id,'subject'=>$data['subject'],'last_message_at'=>now()]);$thread->messages()->create(['sender_id'=>$request->user()->id,'body'=>$data['body']]);return $thread;});
        Notification::send(User::query()->whereIn('role',['admin','moderator','power_admin'])->where('is_active',true)->get(),new PortalActivityNotification('New patient message',$data['subject'],'message'));
        return response()->json(['data'=>$thread->load('messages.sender:id,name,role'),'message'=>'Your message has been sent.'],201);
    }
    public function reply(Request $request, MessageThread $thread): JsonResponse
    {
        abort_unless($thread->patient_id===$request->user()->id,403); $data=$request->validate(['body'=>['required','string','max:4000']]);
        $message=$thread->messages()->create(['sender_id'=>$request->user()->id,'body'=>$data['body']]);$thread->update(['last_message_at'=>now()]);
        Notification::send(User::query()->whereIn('role',['admin','moderator','power_admin'])->where('is_active',true)->get(),new PortalActivityNotification('Patient replied',$thread->subject,'message'));
        return response()->json(['data'=>$message->load('sender:id,name,role')],201);
    }
}
