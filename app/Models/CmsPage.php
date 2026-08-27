<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class CmsPage extends Model{protected $guarded=[];protected function casts():array{return['published_snapshot'=>'array','published_at'=>'datetime'];}public function sections(){return $this->hasMany(CmsSection::class)->orderBy('sort_order');}public function versions(){return $this->hasMany(CmsVersion::class)->latest('version');}}
