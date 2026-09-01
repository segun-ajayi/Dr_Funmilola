<?php

namespace App\Services;

use App\Contracts\FileScannerInterface;
use App\Models\AuditLog;
use App\Models\CmsMedia;
use App\Models\CmsPage;
use App\Models\CmsSection;
use App\Models\CmsVersion;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class CmsMediaService
{
    private const MIME_EXTENSIONS = [
        'image/jpeg' => ['jpg', 'jpeg'],
        'image/png' => ['png'],
        'image/webp' => ['webp'],
    ];

    public function __construct(private readonly FileScannerInterface $scanner) {}

    public function store(UploadedFile $file, User $actor, array $metadata, ?string $ipAddress = null): CmsMedia
    {
        $originalName = $this->safeOriginalName($file);
        $publicId = (string) Str::uuid();
        $extension = strtolower($file->getClientOriginalExtension());
        $quarantinePath = $file->storeAs(
            config('upload-security.quarantine_directory'),
            $publicId.'.'.$extension,
            'local',
        );

        if (! $quarantinePath) {
            $this->audit($actor, 'cms.media_scan_failed', null, ['reason' => 'quarantine_store_failed'], $ipAddress);
            throw ValidationException::withMessages(['image' => 'The secure media service is temporarily unavailable. Please try again later.']);
        }

        try {
            $absolutePath = Storage::disk('local')->path($quarantinePath);
            $this->scanner->assertSafe($absolutePath);
            $details = @getimagesize($absolutePath);
            if (! is_array($details) || ! isset($details[0], $details[1], $details['mime'])) {
                throw ValidationException::withMessages(['image' => 'The uploaded file is not a valid supported image.']);
            }
            $mimeType = strtolower((string) $details['mime']);
            if (! isset(self::MIME_EXTENSIONS[$mimeType]) || ! in_array($extension, self::MIME_EXTENSIONS[$mimeType], true)) {
                throw ValidationException::withMessages(['image' => 'The image content does not match its filename and permitted type.']);
            }
            $width = (int) $details[0];
            $height = (int) $details[1];
            if ($width < 1 || $height < 1 || $width > 12000 || $height > 12000 || ($width * $height) > 40000000) {
                throw ValidationException::withMessages(['image' => 'Use an image no larger than 12,000 pixels on either side or 40 megapixels.']);
            }
            $checksum = hash_file('sha256', $absolutePath);
            if (! is_string($checksum)) {
                throw new \RuntimeException('The media checksum could not be calculated.');
            }
        } catch (ValidationException $exception) {
            Storage::disk('local')->delete($quarantinePath);
            $this->audit($actor, 'cms.media_upload_rejected', null, ['reason' => 'unsafe_or_invalid', 'extension' => $extension, 'size_bytes' => $file->getSize()], $ipAddress);
            $message = collect($exception->errors())->flatten()->first() ?? 'This image failed validation or security scanning.';
            throw ValidationException::withMessages(['image' => $message]);
        } catch (Throwable) {
            Storage::disk('local')->delete($quarantinePath);
            $this->audit($actor, 'cms.media_scan_failed', null, ['reason' => 'unavailable_or_indeterminate', 'extension' => $extension, 'size_bytes' => $file->getSize()], $ipAddress);
            throw ValidationException::withMessages(['image' => 'The security scan could not be completed. No file was retained; please try again later.']);
        }

        $releasedPath = config('upload-security.cms_media_directory').'/'.$publicId.'.'.$extension;
        if (! Storage::disk('local')->move($quarantinePath, $releasedPath)) {
            Storage::disk('local')->delete($quarantinePath);
            $this->audit($actor, 'cms.media_scan_failed', null, ['reason' => 'release_failed'], $ipAddress);
            throw ValidationException::withMessages(['image' => 'The scanned image could not be secured. No file was retained; please try again later.']);
        }

        try {
            $asset = CmsMedia::create([
                'public_id' => $publicId,
                'title' => $metadata['title'],
                'original_name' => $originalName,
                'storage_path' => $releasedPath,
                'mime_type' => $mimeType,
                'extension' => $extension,
                'size_bytes' => $file->getSize(),
                'width_pixels' => $width,
                'height_pixels' => $height,
                'checksum_sha256' => $checksum,
                'alt_text' => $metadata['alt_text'] ?: null,
                'caption' => $metadata['caption'] ?: null,
                'is_decorative' => $metadata['is_decorative'],
                'created_by' => $actor->id,
            ]);
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($releasedPath);
            throw $exception;
        }

        $this->audit($actor, 'cms.media_uploaded', $asset, [
            'mime_type' => $asset->mime_type,
            'size_bytes' => $asset->size_bytes,
            'width_pixels' => $asset->width_pixels,
            'height_pixels' => $asset->height_pixels,
        ], $ipAddress);

        return $asset;
    }

    public function assertUsableReference(string $publicId, string $field = 'media'): CmsMedia
    {
        if (! Str::isUuid($publicId)) {
            throw ValidationException::withMessages([$field => 'Choose a valid media-library asset.']);
        }
        $asset = CmsMedia::query()->where('public_id', $publicId)->first();
        if (! $asset || $asset->is_archived || ! Storage::disk('local')->exists($asset->storage_path)) {
            throw ValidationException::withMessages([$field => 'This media asset is unavailable and cannot be saved or published.']);
        }

        return $asset;
    }

    public function isPublishedReference(CmsMedia $asset): bool
    {
        foreach (CmsPage::query()->whereNotNull('published_snapshot')->cursor() as $page) {
            if ($this->containsReference($page->published_snapshot, $asset->public_id)) {
                return true;
            }
        }

        return false;
    }

    public function references(CmsMedia $asset): array
    {
        $draft = 0;
        foreach (CmsSection::query()->cursor() as $section) {
            if ($this->containsReference($section->content, $asset->public_id) || $this->containsReference($section->presentation, $asset->public_id)) {
                $draft++;
            }
        }
        $published = CmsPage::query()->whereNotNull('published_snapshot')->cursor()
            ->filter(fn (CmsPage $page) => $this->containsReference($page->published_snapshot, $asset->public_id))->count();
        $versions = CmsVersion::query()->cursor()
            ->filter(fn (CmsVersion $version) => $this->containsReference($version->snapshot, $asset->public_id))->count();

        return compact('draft', 'published', 'versions');
    }

    public function resource(CmsMedia $asset): array
    {
        return [
            'id' => $asset->public_id,
            'title' => $asset->title,
            'original_name' => $asset->original_name,
            'mime_type' => $asset->mime_type,
            'size_bytes' => $asset->size_bytes,
            'width_pixels' => $asset->width_pixels,
            'height_pixels' => $asset->height_pixels,
            'alt_text' => $asset->alt_text,
            'caption' => $asset->caption,
            'is_decorative' => $asset->is_decorative,
            'is_archived' => $asset->is_archived,
            'url' => url('/media/'.$asset->public_id),
            'references' => $this->references($asset),
            'created_at' => $asset->created_at,
            'updated_at' => $asset->updated_at,
        ];
    }

    public function auditMetadataUpdate(User $actor, CmsMedia $asset, ?string $ipAddress = null): void
    {
        $this->audit($actor, 'cms.media_metadata_updated', $asset, ['is_decorative' => $asset->is_decorative], $ipAddress);
    }

    public function auditArchive(User $actor, CmsMedia $asset, ?string $ipAddress = null): void
    {
        $this->audit($actor, 'cms.media_archived', $asset, ['references' => $this->references($asset)], $ipAddress);
    }

    private function safeOriginalName(UploadedFile $file): string
    {
        $name = $file->getClientOriginalName();
        $extension = strtolower($file->getClientOriginalExtension());
        $valid = preg_match('/^[\pL\pN][\pL\pN _-]{0,129}\.(jpe?g|png|webp)$/iu', $name) === 1;
        if (basename($name) !== $name || substr_count($name, '.') !== 1 || ! $valid || ! in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            throw ValidationException::withMessages(['image' => 'Use a safe JPG, JPEG, PNG or WebP filename with a single extension.']);
        }

        return $name;
    }

    private function containsReference(mixed $value, string $publicId): bool
    {
        if (! is_array($value)) {
            return false;
        }
        foreach ($value as $key => $child) {
            if (in_array($key, ['image_media_id', 'background_media_id', 'poster_media_id'], true) && $child === $publicId) {
                return true;
            }
            if (is_array($child) && $this->containsReference($child, $publicId)) {
                return true;
            }
        }

        return false;
    }

    private function audit(User $actor, string $action, ?CmsMedia $asset, array $metadata, ?string $ipAddress): void
    {
        AuditLog::create([
            'actor_id' => $actor->id,
            'action' => $action,
            'subject_type' => CmsMedia::class,
            'subject_id' => $asset?->id,
            'metadata' => $metadata,
            'ip_address' => $ipAddress,
        ]);
    }
}
