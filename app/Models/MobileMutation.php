<?php
namespace App\Models;use Illuminate\Database\Eloquent\Model;class MobileMutation extends Model{protected $guarded=[];protected function casts():array{return['response_body'=>'array'];}}
