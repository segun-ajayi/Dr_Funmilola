<?php
namespace App\Http\Controllers;
use App\Models\CmsPage;
use App\Models\CmsPreviewToken;
use App\Models\CmsSetting;
use Illuminate\Http\JsonResponse;
class PublicCmsController extends Controller
{
 public function show(string $slug):JsonResponse{$page=CmsPage::where('slug',$slug)->whereNotNull('published_snapshot')->firstOrFail();return response()->json(['data'=>$page->published_snapshot]);}
 public function preview(string $token):JsonResponse{$record=CmsPreviewToken::where('token_hash',hash('sha256',$token))->where('expires_at','>',now())->firstOrFail();$page=CmsPage::findOrFail($record->cms_page_id);return response()->json(['data'=>$record->snapshot?:app(\App\Services\CmsService::class)->snapshot($page),'preview'=>true]);}
 public function settings():JsonResponse{return response()->json(['data'=>CmsSetting::all()->filter(fn($setting)=>$setting->published_value!==null)->mapWithKeys(fn($setting)=>[$setting->key=>$setting->published_value])->all()]);}
}
