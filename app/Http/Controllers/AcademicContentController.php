<?php
namespace App\Http\Controllers;
use App\Models\Achievement;use App\Models\CareerEntry;use App\Models\EducationArticle;use App\Models\PracticeContact;use App\Models\Publication;use Illuminate\Http\JsonResponse;use Illuminate\Http\Request;
class AcademicContentController extends Controller{
 public function profile():JsonResponse{return response()->json(['career'=>CareerEntry::where('verification_status','verified')->where('is_published',true)->orderBy('sort_order')->get(),'achievements'=>Achievement::where('verification_status','verified')->where('is_published',true)->latest()->get(),'contacts'=>PracticeContact::where('is_public',true)->orderBy('sort_order')->get()]);}
 public function publications(Request $request):JsonResponse{$data=$request->validate(['q'=>['nullable','string','max:100'],'category'=>['nullable','string','max:80'],'sort'=>['nullable','in:newest,oldest,title']]);$query=Publication::where('verification_status','verified')->when($data['q']??null,fn($q,$term)=>$q->where(fn($x)=>$x->where('title','like',"%{$term}%")->orWhere('authors','like',"%{$term}%")))->when($data['category']??null,fn($q,$category)=>$q->where('category',$category));match($data['sort']??'newest'){'oldest'=>$query->oldest('published_at'),'title'=>$query->orderBy('title'),default=>$query->latest('published_at')};return response()->json($query->paginate(15));}
 public function publication(Publication $publication):JsonResponse{abort_unless($publication->verification_status==='verified',404);return response()->json(['data'=>$publication]);}
 public function articles():JsonResponse{return response()->json(EducationArticle::where('status','published')->latest('published_at')->paginate(12));}
 public function article(string $slug):JsonResponse{return response()->json(['data'=>EducationArticle::where('slug',$slug)->where('status','published')->firstOrFail()]);}
}
