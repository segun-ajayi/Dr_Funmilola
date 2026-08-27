<?php
namespace App\Services;
use App\Models\AuditLog;
use App\Models\CmsPage;
use App\Models\CmsVersion;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class CmsService
{
 public const TYPES=['hero','text','cta','stats','image_text'];
 private const CONTENT_KEYS=['hero'=>['eyebrow','heading','text','primary_label','primary_url','secondary_label','secondary_url'],'text'=>['eyebrow','heading','body'],'cta'=>['eyebrow','heading','text','button_label','button_url'],'stats'=>['eyebrow','heading','text','items'],'image_text'=>['eyebrow','heading','text','image_url','image_alt']];
 public function validateContent(string $type,array $content):array{
  if(!in_array($type,self::TYPES,true))throw ValidationException::withMessages(['type'=>'Choose a supported section type.']);$unknown=array_diff(array_keys($content),self::CONTENT_KEYS[$type]);if($unknown)throw ValidationException::withMessages(['content'=>'This section contains unsupported fields.']);
  foreach($content as $key=>$value){if($key==='items'){if(!is_array($value)||count($value)>8)throw ValidationException::withMessages(['content.items'=>'Statistics must contain no more than eight items.']);foreach($value as $item){if(!is_array($item)||array_diff(array_keys($item),['value','label'])||!isset($item['value'],$item['label']))throw ValidationException::withMessages(['content.items'=>'Each statistic needs a value and label.']);$this->plain($item['value'],40);$this->plain($item['label'],80);}continue;}if(!is_string($value))throw ValidationException::withMessages(["content.{$key}"=>'Content must be text.']);if(str_ends_with($key,'_url'))$this->safeUrl($value);else $this->plain($value,$key==='body'||$key==='text'?4000:200);}
  if(empty($content['heading']))throw ValidationException::withMessages(['content.heading'=>'A heading is required.']);return $content;
 }
 public function validatePresentation(array $data):array{$allowed=['background'=>['ivory','white','wine','blush'],'alignment'=>['left','center'],'width'=>['normal','narrow','wide'],'spacing'=>['compact','normal','generous']];if(array_diff(array_keys($data),array_keys($allowed)))throw ValidationException::withMessages(['presentation'=>'Unsupported presentation setting.']);foreach($data as $key=>$value)if(!in_array($value,$allowed[$key],true))throw ValidationException::withMessages(["presentation.{$key}"=>'Choose a supported option.']);return $data;}
 public function snapshot(CmsPage $page):array{return['title'=>$page->title,'slug'=>$page->slug,'template'=>$page->template,'sections'=>$page->sections()->get()->map(fn($s)=>$s->only(['id','section_key','type','sort_order','is_visible','content','presentation']))->all()];}
 public function version(CmsPage $page,User $actor,string $reason):CmsVersion{$next=((int)$page->versions()->max('version'))+1;$version=$page->versions()->create(['version'=>$next,'reason'=>$reason,'snapshot'=>$this->snapshot($page),'created_by'=>$actor->id]);$this->audit($actor,'cms.version_created',$page,['version'=>$next,'reason'=>$reason]);return $version;}
 public function audit(User $actor,string $action,CmsPage $page,array $metadata=[]):void{AuditLog::create(['actor_id'=>$actor->id,'action'=>$action,'subject_type'=>CmsPage::class,'subject_id'=>$page->id,'metadata'=>$metadata]);}
 private function plain(string $value,int $max):void{if(mb_strlen($value)>$max||$value!==strip_tags($value))throw ValidationException::withMessages(['content'=>'Content must be plain text within the allowed length.']);}
 private function safeUrl(string $value):void{if($value===''||str_starts_with($value,'/'))return;if(!filter_var($value,FILTER_VALIDATE_URL)||!in_array(parse_url($value,PHP_URL_SCHEME),['https','http'],true))throw ValidationException::withMessages(['content'=>'Links must be internal paths or safe HTTP(S) URLs.']);}
}
