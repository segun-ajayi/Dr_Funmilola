<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\AvailabilityException;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AvailabilityExceptionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $data=$request->validate(['from'=>['nullable','date'],'to'=>['nullable','date','after_or_equal:from']]);
        $query=AvailabilityException::orderBy('starts_at');if(isset($data['from']))$query->where('ends_at','>=',CarbonImmutable::parse($data['from'],'Africa/Lagos')->startOfDay()->utc());if(isset($data['to']))$query->where('starts_at','<=',CarbonImmutable::parse($data['to'],'Africa/Lagos')->endOfDay()->utc());
        return response()->json(['data'=>$query->limit(200)->get()]);
    }
    public function store(Request $request): JsonResponse
    {
        $data=$request->validate(['kind'=>['required','in:closed,additional'],'label'=>['required','string','max:120'],'starts_at'=>['required','date'],'ends_at'=>['required','date','after:starts_at'],'consultation_method'=>['required','in:both,online,in_person']]);
        $start=CarbonImmutable::parse($data['starts_at']);$end=CarbonImmutable::parse($data['ends_at']);abort_if($start->diffInDays($end)>31,422,'An exception cannot exceed 31 days.');
        $item=AvailabilityException::create($data+['starts_at'=>$start->utc(),'ends_at'=>$end->utc(),'created_by'=>$request->user()->id]);$this->audit($request,$item,'availability_exception.created');
        return response()->json(['data'=>$item],201);
    }
    public function destroy(Request $request,AvailabilityException $availabilityException): JsonResponse
    {
        $this->audit($request,$availabilityException,'availability_exception.deleted');$availabilityException->delete();return response()->json(['message'=>'Schedule exception removed.']);
    }
    private function audit(Request $request,AvailabilityException $item,string $action): void { AuditLog::create(['actor_id'=>$request->user()->id,'action'=>$action,'subject_type'=>$item::class,'subject_id'=>$item->id,'metadata'=>$item->only(['kind','label','starts_at','ends_at'])]); }
}
