<?php

namespace App\Http\Controllers;

use App\Models\PatientDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Contracts\FileScannerInterface;
use App\Models\AuditLog;
use Illuminate\Validation\ValidationException;

class PatientDocumentController extends Controller
{
    public function index(Request $request): JsonResponse { return response()->json(['data' => $request->user()->documents()->latest()->get()->makeHidden(['storage_path','patient_id'])]); }
    public function store(Request $request, FileScannerInterface $scanner): JsonResponse
    {
        $data = $request->validate(['label' => ['required','string','max:120'], 'document' => ['required','file','mimes:pdf,jpg,jpeg,png','max:10240']]);
        $file = $data['document'];
        try { $scanner->assertSafe($file); } catch (ValidationException $exception) {
            AuditLog::create(['actor_id'=>$request->user()->id,'action'=>'document.upload_rejected','subject_type'=>$request->user()::class,'subject_id'=>$request->user()->id,'metadata'=>['extension'=>$file->getClientOriginalExtension(),'size_bytes'=>$file->getSize()],'ip_address'=>$request->ip()]);
            throw $exception;
        }
        $publicId = (string) Str::uuid();
        $path = $file->store("patient-documents/{$request->user()->id}", 'local');
        $document = PatientDocument::create(['public_id'=>$publicId,'patient_id'=>$request->user()->id,'label'=>$data['label'],'original_name'=>$file->getClientOriginalName(),'storage_path'=>$path,'mime_type'=>$file->getMimeType(),'size_bytes'=>$file->getSize()]);
        return response()->json(['data'=>$document->makeHidden(['storage_path','patient_id']),'message'=>'Document uploaded securely.'], 201);
    }
    public function download(Request $request, PatientDocument $document): StreamedResponse
    {
        abort_unless($request->user()->isStaff() || $document->patient_id === $request->user()->id, 403);
        abort_unless(Storage::disk('local')->exists($document->storage_path), 404);
        return Storage::disk('local')->download($document->storage_path, $document->original_name, ['Content-Type'=>$document->mime_type]);
    }
}
