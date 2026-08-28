<?php
namespace App\Http\Controllers\Cms;
use App\Http\Controllers\Controller;
use App\Models\CmsPage;
use App\Models\CmsSection;
use App\Services\CmsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SectionController extends Controller
{
 public function store(Request $request,CmsPage $page,CmsService $cms):JsonResponse{$data=$this->data($request,$cms);$section=$page->sections()->create($data+['section_key'=>(string)Str::uuid(),'sort_order'=>((int)$page->sections()->max('sort_order'))+1]);$this->draft($page);$cms->version($page,$request->user(),'Section added');return response()->json(['data'=>$section],201);}
 public function update(Request $request,CmsPage $page,CmsSection $section,CmsService $cms):JsonResponse{$this->belongs($page,$section);$this->assertVersion($request,$page);$section->update($this->data($request,$cms));$this->draft($page);$cms->version($page,$request->user(),'Section updated');return response()->json(['data'=>$section]);}
 public function destroy(Request $request,CmsPage $page,CmsSection $section,CmsService $cms):JsonResponse{$this->belongs($page,$section);$section->delete();$this->draft($page);$cms->version($page,$request->user(),'Section removed');return response()->json(['message'=>'Section removed.']);}
 public function duplicate(Request $request,CmsPage $page,CmsSection $section,CmsService $cms):JsonResponse{$this->belongs($page,$section);$copy=$page->sections()->create(['section_key'=>(string)Str::uuid(),'type'=>$section->type,'sort_order'=>((int)$page->sections()->max('sort_order'))+1,'is_visible'=>$section->is_visible,'content'=>$section->content,'presentation'=>$section->presentation]);$this->draft($page);$cms->version($page,$request->user(),'Section duplicated');return response()->json(['data'=>$copy],201);}
 public function reorder(Request $request,CmsPage $page,CmsService $cms):JsonResponse{$data=$request->validate(['section_ids'=>['required','array'],'section_ids.*'=>['integer']]);$existing=$page->sections()->pluck('id')->sort()->values()->all();$submitted=collect($data['section_ids'])->unique()->sort()->values()->all();abort_unless($existing===$submitted,422,'The section order must include every page section exactly once.');foreach($data['section_ids'] as $order=>$id)$page->sections()->whereKey($id)->update(['sort_order'=>$order]);$this->draft($page);$cms->version($page,$request->user(),'Sections reordered');return response()->json(['data'=>$cms->snapshot($page)]);}
 private function data(Request $request,CmsService $cms):array{$base=$request->validate(['type'=>['required','string'],'content'=>['required','array'],'presentation'=>['nullable','array'],'is_visible'=>['required','boolean']]);return['type'=>$base['type'],'content'=>$cms->validateContent($base['type'],$base['content']),'presentation'=>$cms->validatePresentation($base['presentation']??[]),'is_visible'=>$base['is_visible']];}
 private function draft(CmsPage $page):void{$page->update(['status'=>'draft','lock_version'=>$page->lock_version+1]);}
 private function assertVersion(Request $request,CmsPage $page):void{if($request->has('page_version')&&(int)$request->input('page_version')!==$page->lock_version)abort(409,'This page changed in another editing session. Reload it before saving.');}
 private function belongs(CmsPage $page,CmsSection $section):void{abort_unless($section->cms_page_id===$page->id,404);}
}
