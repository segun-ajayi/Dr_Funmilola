<?php
namespace App\Http\Controllers\Cms;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\CmsSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SettingController extends Controller
{
 public function show():JsonResponse{return response()->json(['data'=>CmsSetting::all()->keyBy('key')]);}
 public function update(Request $request,string $key):JsonResponse{$value=$request->validate(['value'=>['required','array']])['value'];$clean=match($key){'navigation'=>$this->navigation($value),'theme'=>$this->theme($value),default=>throw ValidationException::withMessages(['key'=>'This setting cannot be edited.'])};$setting=CmsSetting::updateOrCreate(['key'=>$key],['draft_value'=>$clean,'updated_by'=>$request->user()->id]);AuditLog::create(['actor_id'=>$request->user()->id,'action'=>'cms.setting_updated','subject_type'=>CmsSetting::class,'subject_id'=>$setting->id,'metadata'=>['key'=>$key]]);return response()->json(['data'=>$setting]);}
 public function publish(Request $request,string $key):JsonResponse{$setting=CmsSetting::where('key',$key)->firstOrFail();$setting->update(['published_value'=>$setting->draft_value,'updated_by'=>$request->user()->id]);AuditLog::create(['actor_id'=>$request->user()->id,'action'=>'cms.setting_published','subject_type'=>CmsSetting::class,'subject_id'=>$setting->id,'metadata'=>['key'=>$key]]);return response()->json(['data'=>$setting]);}
 private function navigation(array $value):array{if(count($value)>8)throw ValidationException::withMessages(['value'=>'Navigation supports up to eight links.']);return collect($value)->map(fn($item)=>$this->navigationItem($item,true))->all();}
 private function navigationItem(mixed $item,bool $allowChildren):array{if(!is_array($item)||array_diff(array_keys($item),['label','path','is_visible','children'])||empty($item['label'])||empty($item['path'])||mb_strlen($item['label'])>40||!preg_match('#^/[a-z0-9/_-]*$#',$item['path']))throw ValidationException::withMessages(['value'=>'Each navigation item needs a short label and safe internal path.']);$children=$item['children']??[];if(!is_array($children)||count($children)>6||(!$allowChildren&&count($children)))throw ValidationException::withMessages(['value'=>'Navigation supports one submenu level with up to six links.']);return['label'=>strip_tags($item['label']),'path'=>$item['path'],'is_visible'=>(bool)($item['is_visible']??true),'children'=>collect($children)->map(fn($child)=>$this->navigationItem($child,false))->all()];}
 private function theme(array $value):array{$allowed=['palette'=>['wine','plum','rose'],'density'=>['comfortable','compact'],'heading_style'=>['editorial','modern']];if(array_diff(array_keys($value),array_keys($allowed)))throw ValidationException::withMessages(['value'=>'Unsupported theme option.']);foreach($value as $key=>$choice)if(!in_array($choice,$allowed[$key],true))throw ValidationException::withMessages(["value.{$key}"=>'Choose a supported theme option.']);return $value;}
}
