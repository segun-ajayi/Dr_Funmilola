<?php

namespace App\Services\Security;

use App\Contracts\FileScannerInterface;
use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\PatientDocument;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class SecureDocumentService
{
    public function __construct(private readonly FileScannerInterface $scanner) {}

    public function store(
        UploadedFile $file,
        User $owner,
        User $actor,
        string $label,
        ?Appointment $appointment = null,
        ?string $ipAddress = null,
        string $validationField = 'document',
    ): PatientDocument {
        $originalName = $this->safeOriginalName($file, $validationField);
        $publicId = (string) Str::uuid();
        $extension = strtolower($file->getClientOriginalExtension());
        $quarantinePath = $file->storeAs(
            config('upload-security.quarantine_directory'),
            $publicId.'.'.$extension,
            'local',
        );

        if (! $quarantinePath) {
            $this->audit($actor, $owner, 'document.scan_failed', ['reason' => 'quarantine_store_failed'], $ipAddress);
            throw ValidationException::withMessages([$validationField => 'The secure upload service is temporarily unavailable. Please try again later.']);
        }

        try {
            $this->scanner->assertSafe(Storage::disk('local')->path($quarantinePath));
        } catch (ValidationException $exception) {
            Storage::disk('local')->delete($quarantinePath);
            $this->audit($actor, $owner, 'document.upload_rejected', $this->safeFileMetadata($file, 'unsafe'), $ipAddress);
            $message = collect($exception->errors())->flatten()->first() ?? 'This file failed the security scan and was not uploaded.';
            throw ValidationException::withMessages([$validationField => $message]);
        } catch (Throwable) {
            Storage::disk('local')->delete($quarantinePath);
            $this->audit($actor, $owner, 'document.scan_failed', $this->safeFileMetadata($file, 'unavailable_or_indeterminate'), $ipAddress);
            throw ValidationException::withMessages([$validationField => 'The security scan could not be completed. No file was retained; please try again later.']);
        }

        $releasedPath = config('upload-security.released_directory')."/{$owner->id}/{$publicId}.{$extension}";
        if (! Storage::disk('local')->move($quarantinePath, $releasedPath)) {
            Storage::disk('local')->delete($quarantinePath);
            $this->audit($actor, $owner, 'document.scan_failed', $this->safeFileMetadata($file, 'release_failed'), $ipAddress);
            throw ValidationException::withMessages([$validationField => 'The scanned file could not be secured. No file was retained; please try again later.']);
        }

        try {
            $document = PatientDocument::create([
                'public_id' => $publicId,
                'patient_id' => $owner->id,
                'appointment_id' => $appointment?->id,
                'label' => $label,
                'original_name' => $originalName,
                'storage_path' => $releasedPath,
                'mime_type' => $file->getMimeType(),
                'size_bytes' => $file->getSize(),
            ]);
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($releasedPath);
            throw $exception;
        }

        $this->audit($actor, $owner, 'document.uploaded', [
            'document_id' => $document->id,
            'appointment_id' => $appointment?->id,
            'mime_type' => $document->mime_type,
            'size_bytes' => $document->size_bytes,
        ], $ipAddress);

        return $document;
    }

    public function discard(PatientDocument $document): void
    {
        Storage::disk('local')->delete($document->storage_path);
        $document->delete();
    }

    private function safeOriginalName(UploadedFile $file, string $validationField): string
    {
        $name = $file->getClientOriginalName();
        $extension = strtolower($file->getClientOriginalExtension());
        $allowedName = preg_match('/^[\pL\pN][\pL\pN _-]{0,119}\.(pdf|jpe?g|png)$/iu', $name) === 1;

        if (basename($name) !== $name || substr_count($name, '.') !== 1 || ! $allowedName || ! in_array($extension, ['pdf', 'jpg', 'jpeg', 'png'], true)) {
            throw ValidationException::withMessages([$validationField => 'Use a safe PDF, JPG, JPEG or PNG filename with a single extension.']);
        }

        return $name;
    }

    private function safeFileMetadata(UploadedFile $file, string $reason): array
    {
        return ['reason' => $reason, 'extension' => strtolower($file->getClientOriginalExtension()), 'size_bytes' => $file->getSize()];
    }

    private function audit(User $actor, User $owner, string $action, array $metadata, ?string $ipAddress): void
    {
        AuditLog::create([
            'actor_id' => $actor->id,
            'action' => $action,
            'subject_type' => User::class,
            'subject_id' => $owner->id,
            'metadata' => $metadata,
            'ip_address' => $ipAddress,
        ]);
    }
}
