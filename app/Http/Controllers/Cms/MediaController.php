<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Models\CmsMedia;
use App\Services\CmsMediaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class MediaController extends Controller
{
    public function index(Request $request, CmsMediaService $media): JsonResponse
    {
        $data = $request->validate(['q' => ['nullable', 'string', 'max:100'], 'page' => ['nullable', 'integer', 'min:1']]);
        $query = CmsMedia::query()->where('is_archived', false)->latest();
        if ($search = trim((string) ($data['q'] ?? ''))) {
            $escaped = addcslashes($search, '%_\\');
            $query->where(function ($builder) use ($escaped) {
                $builder->where('title', 'like', '%'.$escaped.'%')
                    ->orWhere('original_name', 'like', '%'.$escaped.'%')
                    ->orWhere('alt_text', 'like', '%'.$escaped.'%')
                    ->orWhere('caption', 'like', '%'.$escaped.'%');
            });
        }
        $assets = $query->paginate(24);

        return response()->json([
            'data' => collect($assets->items())->map(fn (CmsMedia $asset) => $media->resource($asset))->all(),
            'meta' => ['current_page' => $assets->currentPage(), 'last_page' => $assets->lastPage(), 'per_page' => $assets->perPage(), 'total' => $assets->total()],
        ]);
    }

    public function store(Request $request, CmsMediaService $media): JsonResponse
    {
        $data = $this->validated($request, true);
        $asset = $media->store($data['image'], $request->user(), $data, $request->ip());

        return response()->json(['data' => $media->resource($asset)], 201);
    }

    public function update(Request $request, CmsMedia $mediaAsset, CmsMediaService $media): JsonResponse
    {
        $data = $this->validated($request, false);
        $mediaAsset->update([
            'title' => $data['title'],
            'alt_text' => $data['alt_text'] ?: null,
            'caption' => $data['caption'] ?: null,
            'is_decorative' => $data['is_decorative'],
        ]);
        $media->auditMetadataUpdate($request->user(), $mediaAsset, $request->ip());

        return response()->json(['data' => $media->resource($mediaAsset->fresh())]);
    }

    public function destroy(Request $request, CmsMedia $mediaAsset, CmsMediaService $media): JsonResponse
    {
        if (! $mediaAsset->is_archived) {
            $references = $media->references($mediaAsset);
            if (array_sum($references) > 0) {
                throw ValidationException::withMessages(['media' => 'This asset is still used by a page or retained version and cannot be archived.']);
            }
            $mediaAsset->update(['is_archived' => true]);
            $media->auditArchive($request->user(), $mediaAsset, $request->ip());
        }

        return response()->json(['data' => $media->resource($mediaAsset->fresh())]);
    }

    public function content(Request $request, CmsMedia $mediaAsset, CmsMediaService $media)
    {
        $user = $request->user();
        $canEdit = $user && $user->is_active && $user->hasVerifiedEmail() && $user->role->value === 'power_admin';
        abort_unless($canEdit || $media->isPublishedReference($mediaAsset), 404);
        abort_unless(Storage::disk('local')->exists($mediaAsset->storage_path), 404);

        return Storage::disk('local')->response($mediaAsset->storage_path, $mediaAsset->original_name, [
            'Content-Type' => $mediaAsset->mime_type,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
            'Content-Disposition' => 'inline; filename="'.addslashes($mediaAsset->original_name).'"',
        ]);
    }

    private function validated(Request $request, bool $withImage): array
    {
        $rules = [
            'title' => ['required', 'string', 'max:150'],
            'alt_text' => ['nullable', 'string', 'max:500'],
            'caption' => ['nullable', 'string', 'max:500'],
            'is_decorative' => ['required', 'boolean'],
        ];
        if ($withImage) {
            $rules['image'] = ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:10240'];
        }
        $data = $request->validate($rules);
        foreach (['title', 'alt_text', 'caption'] as $field) {
            $data[$field] = trim((string) ($data[$field] ?? ''));
            if ($data[$field] !== strip_tags($data[$field])) {
                throw ValidationException::withMessages([$field => 'Use plain text only.']);
            }
        }
        if ($data['is_decorative'] && $data['alt_text'] !== '') {
            throw ValidationException::withMessages(['alt_text' => 'Decorative images must use empty alternative text.']);
        }
        if (! $data['is_decorative'] && $data['alt_text'] === '') {
            throw ValidationException::withMessages(['alt_text' => 'Add alternative text or explicitly mark the image as decorative.']);
        }

        return $data;
    }
}
