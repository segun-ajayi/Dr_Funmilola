<?php
namespace App\Models;use Illuminate\Database\Eloquent\Model;class CareerEntry extends Model{protected $guarded=[];protected function casts():array{return['is_published'=>'boolean'];}}
