<?php
namespace App\Http\Controllers;
use Illuminate\Http\JsonResponse;use Illuminate\Support\Facades\DB;use Illuminate\Support\Facades\Storage;use Throwable;
class ReadinessController extends Controller{public function __invoke():JsonResponse{$checks=['database'=>false,'private_storage'=>false];try{DB::select('select 1');$checks['database']=true;}catch(Throwable){}try{$checks['private_storage']=Storage::disk('local')->exists('')||is_writable(storage_path('app'));}catch(Throwable){}$ready=!in_array(false,$checks,true);return response()->json(['status'=>$ready?'ready':'not_ready','checks'=>$checks],$ready?200:503);}}
