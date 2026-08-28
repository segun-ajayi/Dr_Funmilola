<?php
namespace App\Http\Controllers\Cms;
use App\Http\Controllers\Controller;
use App\Models\CmsPage;
use App\Models\CmsPreviewToken;
use App\Models\CmsVersion;
use App\Services\CmsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PageController extends Controller
{
 private const PROTECTED=['api','staff','portal','sign-in','register','forgot-password','reset-password','sanctum','up'];
 public function index():JsonResponse{return response()->json(['data'=>CmsPage::withCount('sections')->latest()->get()]);}
 public function show(CmsPage $page,CmsService $cms):JsonResponse{return response()->json(['data'=>$cms->snapshot($page)+['id'=>$page->id,'status'=>$page->status,'published_at'=>$page->published_at,'lock_version'=>$page->lock_version]]);}
 public function store(Request $request,CmsService $cms):JsonResponse{$data=$request->validate(['title'=>['required','string','max:150'],'slug'=>['required','alpha_dash','max:100','unique:cms_pages,slug',Rule::notIn(self::PROTECTED)],'template'=>['required',Rule::in(['standard','landing','resource'])]]);$page=CmsPage::create($data+['created_by'=>$request->user()->id]);$cms->version($page,$request->user(),'Page created');return response()->json(['data'=>$page],201);}
 public function update(Request $request,CmsPage $page,CmsService $cms):JsonResponse{$this->assertVersion($request,$page);$data=$request->validate(['title'=>['required','string','max:150'],'slug'=>['required','alpha_dash','max:100',Rule::unique('cms_pages')->ignore($page),Rule::notIn(self::PROTECTED)],'template'=>['required',Rule::in(['standard','landing','resource'])],'seo_title'=>['nullable','string','max:70'],'seo_description'=>['nullable','string','max:170'],'lock_version'=>['nullable','integer','min:0']]);unset($data['lock_version']);$page->update($data+['status'=>'draft','lock_version'=>$page->lock_version+1]);$cms->version($page,$request->user(),'Page details updated');return response()->json(['data'=>$page]);}
 public function preview(Request $request,CmsPage $page,CmsService $cms):JsonResponse{$token=Str::random(64);CmsPreviewToken::create(['cms_page_id'=>$page->id,'token_hash'=>hash('sha256',$token),'expires_at'=>now()->addHour(),'created_by'=>$request->user()->id]);$cms->audit($request->user(),'cms.preview_created',$page);return response()->json(['preview_url'=>url('/preview/'.$token),'expires_at'=>now()->addHour()->toIso8601String()]);}
 public function publish(Request $request,CmsPage $page,CmsService $cms):JsonResponse{$cms->version($page,$request->user(),'Before publish');$page->update(['published_snapshot'=>$cms->snapshot($page),'status'=>'published','published_at'=>now(),'published_by'=>$request->user()->id]);$cms->audit($request->user(),'cms.page_published',$page);return response()->json(['data'=>$page]);}
 public function unpublish(Request $request,CmsPage $page,CmsService $cms):JsonResponse{$cms->version($page,$request->user(),'Before unpublish');$page->update(['published_snapshot'=>null,'status'=>'draft','published_at'=>null,'published_by'=>null,'lock_version'=>$page->lock_version+1]);$cms->audit($request->user(),'cms.page_unpublished',$page);return response()->json(['data'=>$page]);}
 public function duplicate(Request $request,CmsPage $page,CmsService $cms):JsonResponse{$data=$request->validate(['title'=>['required','string','max:150'],'slug'=>['required','alpha_dash','max:100','unique:cms_pages,slug',Rule::notIn(self::PROTECTED)]]);$copy=DB::transaction(function()use($data,$page,$request,$cms){$copy=CmsPage::create($data+['template'=>$page->template,'seo_title'=>$page->seo_title,'seo_description'=>$page->seo_description,'status'=>'draft','created_by'=>$request->user()->id]);foreach($page->sections as$section)$copy->sections()->create(['section_key'=>(string)Str::uuid(),'type'=>$section->type,'sort_order'=>$section->sort_order,'is_visible'=>$section->is_visible,'content'=>$section->content,'presentation'=>$section->presentation]);$cms->version($copy,$request->user(),'Page duplicated from '.$page->id);$cms->audit($request->user(),'cms.page_duplicated',$copy,['source_page_id'=>$page->id]);return$copy;});return response()->json(['data'=>$copy],201);}
 public function versions(CmsPage $page):JsonResponse{return response()->json(['data'=>$page->versions()->get(['id','version','reason','created_by','created_at'])]);}
 public function restore(Request $request,CmsPage $page,CmsVersion $version,CmsService $cms):JsonResponse{abort_unless($version->cms_page_id===$page->id,404);$cms->version($page,$request->user(),'Before restore');$snapshot=$version->snapshot;DB::transaction(function()use($page,$snapshot){$page->update(['title'=>$snapshot['title'],'slug'=>$snapshot['slug'],'template'=>$snapshot['template'],'seo_title'=>$snapshot['seo']['title']??null,'seo_description'=>$snapshot['seo']['description']??null,'status'=>'draft','lock_version'=>$page->lock_version+1]);$page->sections()->delete();foreach($snapshot['sections'] as $section)$page->sections()->create(\Illuminate\Support\Arr::except($section,'id'));});$cms->version($page,$request->user(),'Restored version '.$version->version);return response()->json(['data'=>$cms->snapshot($page)]);}
 private function assertVersion(Request $request,CmsPage $page):void{if($request->has('lock_version')&&(int)$request->input('lock_version')!==$page->lock_version)abort(409,'This page changed in another editing session. Reload it before saving.');}
}
