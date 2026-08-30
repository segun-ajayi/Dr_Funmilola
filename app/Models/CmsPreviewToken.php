<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class CmsPreviewToken extends Model{protected $guarded=[];protected function casts():array{return['snapshot'=>'array','expires_at'=>'datetime'];}}
