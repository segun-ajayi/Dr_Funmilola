<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class CmsSetting extends Model{protected $guarded=[];protected function casts():array{return['draft_value'=>'array','published_value'=>'array'];}}
