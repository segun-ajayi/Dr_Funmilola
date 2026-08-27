<?php
namespace App\Http\Controllers;
use App\Models\AuditLog;use Illuminate\Http\JsonResponse;use Illuminate\Http\Request;
class DeviceController extends Controller{
 public function index(Request $request):JsonResponse{return response()->json(['data'=>$request->user()->tokens()->latest()->get(['id','name','abilities','last_used_at','expires_at','created_at'])]);}
 public function destroy(Request $request,int $token):JsonResponse{$record=$request->user()->tokens()->findOrFail($token);$name=$record->name;$record->delete();AuditLog::create(['actor_id'=>$request->user()->id,'action'=>'identity.mobile_token_revoked','subject_type'=>$request->user()::class,'subject_id'=>$request->user()->id,'metadata'=>['device_name'=>$name]]);return response()->json(['message'=>'Device access revoked.']);}
}
