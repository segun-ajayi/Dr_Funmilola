<?php

namespace Tests\Feature;

use App\Contracts\FileScannerInterface;
use App\Enums\UserRole;
use App\Models\CmsMedia;
use App\Models\CmsPage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Tests\TestCase;

class CmsMediaTest extends TestCase
{
    use RefreshDatabase;

    public function test_power_admin_can_upload_browse_search_update_and_archive_persistent_media(): void
    {
        Storage::fake('local');
        $this->power();

        $response = $this->upload('care-team.jpg', 'Care team', 'The specialist care team')
            ->assertCreated()
            ->assertJsonPath('data.title', 'Care team')
            ->assertJsonPath('data.width_pixels', 120)
            ->assertJsonPath('data.height_pixels', 80)
            ->assertJsonPath('data.is_decorative', false)
            ->assertJsonMissingPath('data.storage_path')
            ->assertJsonMissingPath('data.checksum_sha256');
        $id = $response->json('data.id');
        $asset = CmsMedia::where('public_id', $id)->firstOrFail();
        Storage::disk('local')->assertExists($asset->storage_path);
        $this->assertSame([], Storage::disk('local')->allFiles(config('upload-security.quarantine_directory')));
        $this->assertDatabaseHas('audit_logs', ['action' => 'cms.media_uploaded', 'subject_id' => $asset->id]);

        $this->getJson('/api/cms/media?q=specialist')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $id);
        $this->putJson('/api/cms/media/'.$id, [
            'title' => 'Updated care team',
            'alt_text' => 'Clinicians discussing breast care',
            'caption' => 'Approved practice photograph',
            'is_decorative' => false,
        ])->assertOk()->assertJsonPath('data.caption', 'Approved practice photograph');
        $this->deleteJson('/api/cms/media/'.$id)->assertOk()->assertJsonPath('data.is_archived', true);
        $this->getJson('/api/cms/media')->assertOk()->assertJsonCount(0, 'data');
        Storage::disk('local')->assertExists($asset->storage_path);
        $this->assertDatabaseHas('audit_logs', ['action' => 'cms.media_archived', 'subject_id' => $asset->id]);
    }

    public function test_media_library_and_mutations_are_power_admin_only(): void
    {
        Storage::fake('local');
        $this->getJson('/api/cms/media')->assertUnauthorized();

        foreach ([UserRole::Patient, UserRole::Moderator, UserRole::Admin] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]));
            $this->getJson('/api/cms/media')->assertForbidden();
            $this->upload('denied-'.$role->value.'.jpg', 'Denied', 'Denied image')->assertForbidden();
        }
        $this->actingAs(User::factory()->unverified()->create(['role' => UserRole::PowerAdmin]));
        $this->getJson('/api/cms/media')->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => UserRole::PowerAdmin, 'is_active' => false]));
        $this->getJson('/api/cms/media')->assertForbidden();

        $this->assertDatabaseCount('cms_media_assets', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_media_metadata_requires_plain_text_alt_or_explicit_decorative_state(): void
    {
        Storage::fake('local');
        $this->power();

        $this->upload('missing-alt.jpg', 'Missing alt', '', false)->assertUnprocessable()->assertJsonValidationErrors('alt_text');
        $this->upload('decorative-with-alt.jpg', 'Decorative', 'This should be empty', true)->assertUnprocessable()->assertJsonValidationErrors('alt_text');
        $this->upload('unsafe-title.jpg', '<script>Unsafe</script>', '', true)->assertUnprocessable()->assertJsonValidationErrors('title');
        $this->upload('decorative.jpg', 'Decorative flourish', '', true)->assertCreated()->assertJsonPath('data.is_decorative', true)->assertJsonPath('data.alt_text', null);
    }

    public function test_unsafe_or_unavailable_scanner_fails_closed_without_media_or_files(): void
    {
        Storage::fake('local');
        $this->power();
        $this->app->instance(FileScannerInterface::class, new class implements FileScannerInterface
        {
            public function assertSafe(string $absolutePath): void
            {
                throw ValidationException::withMessages(['image' => 'Unsafe fixture.']);
            }
        });
        $this->upload('unsafe.jpg', 'Unsafe', 'Unsafe image')->assertUnprocessable()->assertJsonValidationErrors('image');
        $this->assertDatabaseCount('cms_media_assets', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());

        $this->app->instance(FileScannerInterface::class, new class implements FileScannerInterface
        {
            public function assertSafe(string $absolutePath): void
            {
                throw new RuntimeException('Scanner unavailable');
            }
        });
        $this->upload('unavailable.jpg', 'Unavailable', 'Unavailable image')->assertUnprocessable()->assertJsonValidationErrors('image');
        $this->assertDatabaseCount('cms_media_assets', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
        $this->assertDatabaseHas('audit_logs', ['action' => 'cms.media_scan_failed']);
    }

    public function test_malformed_oversized_and_unsafe_filename_images_are_rejected(): void
    {
        Storage::fake('local');
        $this->power();

        $headers = ['Accept' => 'application/json'];
        $this->post('/api/cms/media', $this->payload(UploadedFile::fake()->createWithContent('malformed.png', "\x89PNG\r\n\x1A\nnot-an-image")), $headers)->assertUnprocessable();
        $this->post('/api/cms/media', $this->payload(UploadedFile::fake()->create('oversized.jpg', 10241, 'image/jpeg')), $headers)->assertUnprocessable();
        $this->post('/api/cms/media', $this->payload(UploadedFile::fake()->image('double.name.jpg')), $headers)->assertUnprocessable();
        $this->post('/api/cms/media', $this->payload(UploadedFile::fake()->create('vector.svg', 10, 'image/svg+xml')), $headers)->assertUnprocessable();
        $this->assertDatabaseCount('cms_media_assets', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_draft_media_is_private_then_public_only_while_referenced_by_published_snapshot(): void
    {
        Storage::fake('local');
        $power = $this->power();
        $id = $this->upload('private-draft.jpg', 'Private draft', 'A private draft image')->assertCreated()->json('data.id');
        $page = $this->createImagePage($id);

        $this->get('/media/'.$id)->assertOk();
        $this->actingAsGuest('web')->actingAsGuest('sanctum');
        $this->get('/media/'.$id)->assertNotFound();

        $this->actingAs($power);
        $this->postJson('/api/cms/pages/'.$page->id.'/publish')->assertOk();
        $this->actingAsGuest('web')->actingAsGuest('sanctum');
        $this->get('/media/'.$id)->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->getJson('/api/content/pages/media-test')->assertOk()->assertJsonPath('data.sections.0.content.image_media_id', $id)->assertJsonPath('data.sections.0.content.image_alt', 'A private draft image');
    }

    public function test_archived_media_remains_safe_for_existing_public_pages_but_cannot_be_saved_again(): void
    {
        Storage::fake('local');
        $power = $this->power();
        $id = $this->upload('published.jpg', 'Published', 'Published image')->assertCreated()->json('data.id');
        $page = $this->createImagePage($id);
        $this->postJson('/api/cms/pages/'.$page->id.'/publish')->assertOk();
        $this->deleteJson('/api/cms/media/'.$id)->assertUnprocessable()->assertJsonValidationErrors('media');
        $this->assertDatabaseHas('cms_media_assets', ['public_id' => $id, 'is_archived' => false]);

        $this->actingAsGuest('web')->actingAsGuest('sanctum');
        $this->get('/media/'.$id)->assertOk();
        $this->actingAs($power);
        $archivedId = $this->upload('archived.jpg', 'Archived', 'Archived image')->assertCreated()->json('data.id');
        $this->deleteJson('/api/cms/media/'.$archivedId)->assertOk()->assertJsonPath('data.is_archived', true);
        $newPage = $this->postJson('/api/cms/pages', ['title' => 'Archived media target', 'start_mode' => 'blank'])->assertCreated()->json('data');
        $this->putJson('/api/cms/pages/'.$newPage['id'].'/visual-draft', [
            'lock_version' => $newPage['lock_version'],
            'sections' => [$this->imageSection($archivedId)],
        ])->assertUnprocessable()->assertJsonValidationErrors('content.image_media_id');
        $this->assertDatabaseHas('cms_pages', ['id' => $newPage['id'], 'lock_version' => 0]);
    }

    public function test_preview_and_publish_fail_closed_when_a_referenced_media_file_is_missing(): void
    {
        Storage::fake('local');
        $this->power();
        $id = $this->upload('missing-file.jpg', 'Missing file', 'Missing file image')->assertCreated()->json('data.id');
        $page = $this->createImagePage($id);
        $asset = CmsMedia::where('public_id', $id)->firstOrFail();
        Storage::disk('local')->delete($asset->storage_path);

        $this->postJson('/api/cms/pages/'.$page->id.'/preview')->assertUnprocessable()->assertJsonValidationErrors('content.image_media_id');
        $this->postJson('/api/cms/pages/'.$page->id.'/publish')->assertUnprocessable()->assertJsonValidationErrors('content.image_media_id');
        $this->assertDatabaseHas('cms_pages', ['id' => $page->id, 'published_snapshot' => null]);
    }

    private function power(): User
    {
        $user = User::factory()->create(['role' => UserRole::PowerAdmin]);
        $this->actingAs($user);

        return $user;
    }

    private function upload(string $filename, string $title, string $alt, bool $decorative = false)
    {
        return $this->post('/api/cms/media', $this->payload(UploadedFile::fake()->image($filename, 120, 80), $title, $alt, $decorative), ['Accept' => 'application/json']);
    }

    private function payload(UploadedFile $file, string $title = 'Test image', string $alt = 'Test image alternative text', bool $decorative = false): array
    {
        return ['image' => $file, 'title' => $title, 'alt_text' => $alt, 'caption' => '', 'is_decorative' => $decorative];
    }

    private function createImagePage(string $mediaId): CmsPage
    {
        $page = $this->postJson('/api/cms/pages', ['title' => 'Media Test', 'start_mode' => 'blank'])->assertCreated()->json('data');
        $this->putJson('/api/cms/pages/'.$page['id'].'/visual-draft', [
            'lock_version' => $page['lock_version'],
            'sections' => [$this->imageSection($mediaId)],
        ])->assertOk();

        return CmsPage::findOrFail($page['id']);
    }

    private function imageSection(string $mediaId): array
    {
        return [
            'id' => null,
            'section_key' => (string) Str::uuid(),
            'type' => 'image',
            'sort_order' => 0,
            'is_visible' => true,
            'content' => [
                'heading' => 'Media test image',
                'image_media_id' => $mediaId,
                'image_alt' => 'A private draft image',
                'image_is_decorative' => false,
                'caption' => 'A safe caption',
                'image_link' => '/about',
            ],
            'presentation' => [
                'image_width' => 'large',
                'image_height' => 'medium',
                'image_alignment' => 'center',
                'image_fit' => 'cover',
                'image_radius' => 'soft',
                'crop_position' => 'center',
                'image_overlay_color' => 'none',
                'image_overlay_opacity' => '0',
                'image_opacity' => '100',
            ],
        ];
    }
}
