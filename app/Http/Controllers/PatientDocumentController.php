<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\PatientDocument;
use App\Services\Security\SecureDocumentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PatientDocumentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(['data' => $request->user()->documents()->latest()->get()->makeHidden(['storage_path', 'patient_id'])]);
    }

    public function store(Request $request, SecureDocumentService $documents): JsonResponse
    {
        $data = $request->validate([
            'label' => ['required', 'string', 'max:120'],
            'document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ]);
        $document = $documents->store(
            $data['document'],
            $request->user(),
            $request->user(),
            $data['label'],
            ipAddress: $request->ip(),
        );

        return response()->json(['data' => $document->makeHidden(['storage_path', 'patient_id']), 'message' => 'Document scanned and stored securely.'], 201);
    }

    public function download(Request $request, PatientDocument $document): StreamedResponse
    {
        abort_unless($request->user()->isStaff() || $document->patient_id === $request->user()->id, 403);
        abort_unless(Storage::disk('local')->exists($document->storage_path), 404);
        $this->audit($request, 'document.downloaded', ['document_id' => $document->id, 'owner_id' => $document->patient_id]);

        return Storage::disk('local')->download($document->storage_path, $document->original_name, ['Content-Type' => $document->mime_type]);
    }

    private function audit(Request $request, string $action, array $metadata): void
    {
        AuditLog::create([
            'actor_id' => $request->user()->id,
            'action' => $action,
            'subject_type' => $request->user()::class,
            'subject_id' => $request->user()->id,
            'metadata' => $metadata,
            'ip_address' => $request->ip(),
        ]);
    }
}
