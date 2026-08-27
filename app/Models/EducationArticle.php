<?php
namespace App\Models;use Illuminate\Database\Eloquent\Model;class EducationArticle extends Model{protected $guarded=[];protected function casts():array{return['tags'=>'array','reviewed_at'=>'date','content_updated_at'=>'date','published_at'=>'datetime'];}}
