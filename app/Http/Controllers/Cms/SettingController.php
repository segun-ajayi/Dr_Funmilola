<?php
namespace App\Http\Controllers\Cms;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\CmsSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SettingController extends Controller
{
 public function show():JsonResponse{return response()->json(['data'=>CmsSetting::all()->keyBy('key')]);}
 public function update(Request $request,string $key):JsonResponse{$value=$request->validate(['value'=>['required','array']])['value'];$clean=match($key){'navigation'=>$this->navigation($value),'theme'=>$this->theme($value),default=>throw ValidationException::withMessages(['key'=>'This setting cannot be edited.'])};$setting=CmsSetting::updateOrCreate(['key'=>$key],['draft_value'=>$clean,'updated_by'=>$request->user()->id]);AuditLog::create(['actor_id'=>$request->user()->id,'action'=>'cms.setting_updated','subject_type'=>CmsSetting::class,'subject_id'=>$setting->id,'metadata'=>['key'=>$key]]);return response()->json(['data'=>$setting]);}
 public function publish(Request $request,string $key):JsonResponse{$setting=CmsSetting::where('key',$key)->firstOrFail();$setting->update(['published_value'=>$setting->draft_value,'updated_by'=>$request->user()->id]);AuditLog::create(['actor_id'=>$request->user()->id,'action'=>'cms.setting_published','subject_type'=>CmsSetting::class,'subject_id'=>$setting->id,'metadata'=>['key'=>$key]]);return response()->json(['data'=>$setting]);}
 private function navigation(array $value):array{if(count($value)>8)throw ValidationException::withMessages(['value'=>'Navigation supports up to eight links.']);$keys=[];$clean=[];foreach($value as$item)$clean[]=$this->navigationItem($item,true,$keys);return$clean;}
 private function navigationItem(mixed $item,bool $allowChildren,array &$keys):array
 {
  $allowed=['key','label','type','path','target','is_visible','children'];
  if(!is_array($item)||array_diff(array_keys($item),$allowed))throw ValidationException::withMessages(['value'=>'Navigation contains an unsupported property.']);
  $label=trim((string)($item['label']??''));$path=trim((string)($item['path']??''));$type=(string)($item['type']??(preg_match('#^https?://#i',$path)?'external':'internal'));$target=(string)($item['target']??'_self');$key=(string)($item['key']??Str::uuid());
  if($label===''||mb_strlen($label)>40||$label!==strip_tags($label))throw ValidationException::withMessages(['value'=>'Each navigation item needs a plain-text label of at most 40 characters.']);
  if(!Str::isUuid($key)||in_array($key,$keys,true))throw ValidationException::withMessages(['value'=>'Navigation item keys must be unique UUIDs.']);
  if(!in_array($type,['internal','external'],true)||!in_array($target,['_self','_blank'],true)||!$this->safeNavigationPath($type,$path))throw ValidationException::withMessages(['value'=>'Choose a safe matching internal path or external website URL.']);
  if(array_key_exists('is_visible',$item)&&!is_bool($item['is_visible']))throw ValidationException::withMessages(['value'=>'Navigation visibility must be true or false.']);
  $children=$item['children']??[];
  if(!is_array($children)||count($children)>6||(!$allowChildren&&count($children)))throw ValidationException::withMessages(['value'=>'Navigation supports one submenu level with up to six links.']);
  $keys[]=$key;
  $cleanChildren=[];foreach($children as$child)$cleanChildren[]=$this->navigationItem($child,false,$keys);
  return['key'=>$key,'label'=>$label,'type'=>$type,'path'=>$path,'target'=>$target,'is_visible'=>$item['is_visible']??true,'children'=>$cleanChildren];
 }
 private function safeNavigationPath(string $type,string $path):bool
 {
  if($type==='internal')return $path==='/'||preg_match('#^/[a-z0-9]+(?:-[a-z0-9]+)*(?:/[a-z0-9]+(?:-[a-z0-9]+)*)*/?$#',$path)===1;
  if(mb_strlen($path)>2048||filter_var($path,FILTER_VALIDATE_URL)===false)return false;
  $parts=parse_url($path);
  return in_array(strtolower((string)($parts['scheme']??'')),['http','https'],true)&&!empty($parts['host'])&&!isset($parts['user'])&&!isset($parts['pass']);
 }
 private function theme(array $value):array{$allowed=['palette'=>['wine','plum','rose'],'density'=>['comfortable','compact'],'heading_style'=>['editorial','modern']];if(array_diff(array_keys($value),array_keys($allowed)))throw ValidationException::withMessages(['value'=>'Unsupported theme option.']);foreach($value as $key=>$choice)if(!in_array($choice,$allowed[$key],true))throw ValidationException::withMessages(["value.{$key}"=>'Choose a supported theme option.']);return $value;}
}
