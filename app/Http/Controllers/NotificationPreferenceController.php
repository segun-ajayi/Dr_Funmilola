<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationPreferenceController extends Controller
{
    public function show(Request $request): JsonResponse { return response()->json(['data'=>$request->user()->notificationPreference()->firstOrCreate([])]); }
    public function update(Request $request): JsonResponse
    {
        $data=$request->validate(['in_app_reminders'=>['required','boolean'],'email_reminders'=>['required','boolean'],'push_reminders'=>['required','boolean']]);
        abort_if($data['push_reminders'],422,'Mobile push reminders will be available when the mobile apps launch.');
        $preference=$request->user()->notificationPreference()->updateOrCreate([],$data);return response()->json(['data'=>$preference,'message'=>'Reminder preferences saved.']);
    }
}
