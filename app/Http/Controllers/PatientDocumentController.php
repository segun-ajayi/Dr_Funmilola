<?php

namespace App\Http\Controllers;

use App\Contracts\FileScannerInterface;
use App\Models\AuditLog;
use App\Models\PatientDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class PatientDocumentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(['data' => $request->user()->documents()->latest()->get()->makeHidden(['storage_path', 'patient_id'])]);
    }

    public function store(Request $request, FileScannerInterface $scanner): JsonResponse
    {
        $data = $request->validate([
            'label' => ['required', 'string', 'max:120'],
            'document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ]);
        /** @var UploadedFile $file */
        $file = $data['document'];
        $originalName = $this->safeOriginalName($file);
        $publicId = (string) Str::uuid();
        $quarantinePath = $file->storeAs(config('upload-security.quarantine_directory'), $publicId.'.'.$file->extension(), 'local');

        if (! $quarantinePath) {
            $this->audit($request, 'document.scan_failed', ['reason' => 'quarantine_store_failed']);
            throw ValidationException::withMessages(['document' => 'The secure upload service is temporarily unavailable. Please try again later.']);
        }

        try {
            $scanner->assertSafe(Storage::disk('local')->path($quarantinePath));
        } catch (ValidationException $exception) {
            Storage::disk('local')->delete($quarantinePath);
            $this->audit($request, 'document.upload_rejected', $this->safeFileMetadata($file, 'unsafe'));
            throw $exception;
        } catch (Throwable) {
            Storage::disk('local')->delete($quarantinePath);
            $this->audit($request, 'document.scan_failed', $this->safeFileMetadata($file, 'unavailable_or_indeterminate'));
            throw ValidationException::withMessages(['document' => 'The security scan could not be completed. No file was retained; please try again later.']);
        }

        $releasedPath = config('upload-security.released_directory')."/{$request->user()->id}/{$publicId}.{$file->extension()}";
        if (! Storage::disk('local')->move($quarantinePath, $releasedPath)) {
            Storage::disk('local')->delete($quarantinePath);
            $this->audit($request, 'document.scan_failed', $this->safeFileMetadata($file, 'release_failed'));
            throw ValidationException::withMessages(['document' => 'The scanned file could not be secured. No file was retained; please try again later.']);
        }

        try {
            $document = PatientDocument::create([
                'public_id' => $publicId,
                'patient_id' => $request->user()->id,
                'label' => $data['label'],
                'original_name' => $originalName,
                'storage_path' => $releasedPath,
                'mime_type' => $file->getMimeType(),
                'size_bytes' => $file->getSize(),
            ]);
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($releasedPath);
            throw $exception;
        }

        $this->audit($request, 'document.uploaded', ['document_id' => $document->id, 'mime_type' => $document->mime_type, 'size_bytes' => $document->size_bytes]);

        return response()->json(['data' => $document->makeHidden(['storage_path', 'patient_id']), 'message' => 'Document scanned and stored securely.'], 201);
    }

    public function download(Request $request, PatientDocument $document): StreamedResponse
    {
        abort_unless($request->user()->isStaff() || $document->patient_id === $request->user()->id, 403);
        abort_unless(Storage::disk('local')->exists($document->storage_path), 404);
        $this->audit($request, 'document.downloaded', ['document_id' => $document->id, 'owner_id' => $document->patient_id]);

        return Storage::disk('local')->download($document->storage_path, $document->original_name, ['Content-Type' => $document->mime_type]);
    }

    private function safeOriginalName(UploadedFile $file): string
    {
        $name = $file->getClientOriginalName();
        $extension = strtolower($file->getClientOriginalExtension());
        $allowedName = preg_match('/^[\pL\pN][\pL\pN _-]{0,119}\.(pdf|jpe?g|png)$/iu', $name) === 1;

        if (basename($name) !== $name || substr_count($name, '.') !== 1 || ! $allowedName || ! in_array($extension, ['pdf', 'jpg', 'jpeg', 'png'], true)) {
            throw ValidationException::withMessages(['document' => 'Use a safe PDF, JPG, JPEG or PNG filename with a single extension.']);
        }

        return $name;
    }

    private function safeFileMetadata(UploadedFile $file, string $reason): array
    {
        return ['reason' => $reason, 'extension' => strtolower($file->getClientOriginalExtension()), 'size_bytes' => $file->getSize()];
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
