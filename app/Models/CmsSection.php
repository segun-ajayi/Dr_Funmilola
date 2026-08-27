<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class CmsSection extends Model{protected $guarded=[];protected function casts():array{return['content'=>'array','presentation'=>'array','is_visible'=>'boolean'];}}
