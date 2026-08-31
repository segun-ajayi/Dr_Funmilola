<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\CmsPage;
use App\Models\CmsPreviewToken;
use App\Models\CmsSection;
use App\Models\CmsVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CmsTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_power_admin_can_access_editor_and_protected_slugs_are_rejected(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::Admin]));
        $this->getJson('/api/cms/pages')->assertForbidden();
        Sanctum::actingAs($this->power());
        $this->postJson('/api/cms/pages', ['title' => 'Portal', 'slug' => 'portal', 'template' => 'standard'])->assertUnprocessable();
    }

    public function test_public_slug_and_protected_numeric_page_routes_are_unambiguous(): void
    {
        $page = $this->page();
        $this->section($page, 'Editable heading');
        $this->getJson('/api/content/pages/'.$page->slug)->assertNotFound();
        $this->getJson('/api/cms/pages/'.$page->id)->assertOk()->assertJsonPath('data.id', $page->id)->assertJsonPath('data.sections.0.content.heading', 'Editable heading');
        foreach ([UserRole::Patient, UserRole::Moderator, UserRole::Admin] as $role) {
            Sanctum::actingAs(User::factory()->create(['role' => $role]));
            $this->getJson('/api/cms/pages/'.$page->id)->assertForbidden();
        }
        $this->power();
        $this->getJson('/api/cms/pages/999999')->assertNotFound();
        $this->getJson('/api/content/pages/missing-page')->assertNotFound();
    }

    public function test_real_backend_editor_journey_selects_edits_previews_publishes_and_reads_public_page(): void
    {
        $this->power();
        $pageId = $this->postJson('/api/cms/pages', ['title' => 'Route proof', 'slug' => 'route-proof', 'template' => 'standard'])->assertCreated()->json('data.id');
        $this->getJson('/api/cms/pages')->assertOk()->assertJsonFragment(['id' => $pageId, 'slug' => 'route-proof']);
        $sectionId = $this->postJson("/api/cms/pages/{$pageId}/sections", ['type' => 'text', 'content' => ['heading' => 'Initial heading', 'body' => 'Initial body'], 'presentation' => ['background' => 'white', 'alignment' => 'left', 'spacing' => 'normal'], 'is_visible' => true])->assertCreated()->json('data.id');
        $this->getJson("/api/cms/pages/{$pageId}")->assertOk()->assertJsonPath('data.sections.0.content.heading', 'Initial heading');
        $this->putJson("/api/cms/pages/{$pageId}/sections/{$sectionId}", ['type' => 'text', 'content' => ['heading' => 'Edited draft heading', 'body' => 'Edited draft body'], 'presentation' => ['background' => 'white', 'alignment' => 'left', 'spacing' => 'normal'], 'is_visible' => true])->assertOk();
        $previewUrl = $this->postJson("/api/cms/pages/{$pageId}/preview")->assertOk()->json('preview_url');
        $previewToken = basename(parse_url($previewUrl, PHP_URL_PATH));
        $this->getJson('/api/cms/preview/'.$previewToken)->assertOk()->assertJsonPath('data.sections.0.content.heading', 'Edited draft heading');
        $this->getJson('/api/content/pages/route-proof')->assertNotFound();
        $this->postJson("/api/cms/pages/{$pageId}/publish")->assertOk();
        $this->getJson('/api/content/pages/route-proof')->assertOk()->assertJsonPath('data.sections.0.content.heading', 'Edited draft heading');
    }

    public function test_structured_sections_reject_html_and_unsupported_fields(): void
    {
        $page = $this->page();
        $payload = ['type' => 'hero', 'content' => ['heading' => 'Safe heading', 'text' => 'Helpful introduction'], 'presentation' => ['background' => 'ivory', 'alignment' => 'left'], 'is_visible' => true];
        $this->postJson("/api/cms/pages/{$page->id}/sections", $payload)->assertCreated();
        $payload['content']['heading'] = '<script>alert(1)</script>';
        $this->postJson("/api/cms/pages/{$page->id}/sections", $payload)->assertUnprocessable();
        $payload['content'] = ['heading' => 'Safe', 'raw_html' => '<b>no</b>'];
        $this->postJson("/api/cms/pages/{$page->id}/sections", $payload)->assertUnprocessable();
        $payload = ['type' => 'script', 'content' => ['heading' => 'Unsafe component'], 'presentation' => [], 'is_visible' => true];
        $this->postJson("/api/cms/pages/{$page->id}/sections", $payload)->assertUnprocessable();
        $payload = ['type' => 'cards', 'content' => ['heading' => 'Cards', 'items' => [['heading' => 'Card', 'text' => 'Safe text', 'style' => 'position:fixed']]], 'presentation' => [], 'is_visible' => true];
        $this->postJson("/api/cms/pages/{$page->id}/sections", $payload)->assertUnprocessable();
        $payload['content']['items'][0] = ['heading' => 'Card', 'text' => 'Safe text', 'url' => 'javascript:alert(1)'];
        $this->postJson("/api/cms/pages/{$page->id}/sections", $payload)->assertUnprocessable();
        $payload['content']['items'] = array_fill(0, 13, ['heading' => 'Card', 'text' => 'Safe text']);
        $this->postJson("/api/cms/pages/{$page->id}/sections", $payload)->assertUnprocessable();
        $key = (string) \Illuminate\Support\Str::uuid();
        $payload['content']['items'] = [['key' => $key, 'heading' => 'One', 'text' => 'Safe', 'is_visible' => true], ['key' => $key, 'heading' => 'Two', 'text' => 'Safe', 'is_visible' => true]];
        $this->postJson("/api/cms/pages/{$page->id}/sections", $payload)->assertUnprocessable();
        $payload['content']['items'] = [['key' => (string) \Illuminate\Support\Str::uuid(), 'heading' => 'One', 'text' => 'Safe', 'is_visible' => 'yes']];
        $this->postJson("/api/cms/pages/{$page->id}/sections", $payload)->assertUnprocessable();
    }

    public function test_draft_is_private_preview_is_time_bounded_and_publish_is_explicit(): void
    {
        $page = $this->page();
        $this->section($page, 'Original heading');
        $this->getJson('/api/content/pages/'.$page->slug)->assertNotFound();
        $preview = $this->postJson("/api/cms/pages/{$page->id}/preview")->assertOk()->json('preview_url');
        $token = basename(parse_url($preview, PHP_URL_PATH));
        $this->getJson('/api/cms/preview/'.$token)->assertOk()->assertJsonPath('data.sections.0.content.heading', 'Original heading');
        CmsPreviewToken::query()->update(['expires_at' => now()->subMinute()]);
        $this->getJson('/api/cms/preview/'.$token)->assertNotFound();
        $this->postJson("/api/cms/pages/{$page->id}/publish")->assertOk();
        $this->getJson('/api/content/pages/'.$page->slug)->assertOk()->assertJsonPath('data.sections.0.content.heading', 'Original heading');
    }

    public function test_visual_document_save_is_atomic_versioned_and_conflict_protected(): void
    {
        $page = $this->page();
        $section = $this->section($page, 'Original');
        $document = $this->getJson("/api/cms/pages/{$page->id}")->assertOk()->json('data');
        $document['sections'][0]['content']['heading'] = 'Atomic draft';
        $document['sections'][] = [
            'section_key' => (string) \Illuminate\Support\Str::uuid(),
            'type' => 'text',
            'sort_order' => 99,
            'is_visible' => true,
            'content' => ['heading' => 'Second section', 'body' => 'Stored as one document.'],
            'presentation' => ['background' => 'white'],
        ];

        $saved = $this->putJson("/api/cms/pages/{$page->id}/visual-draft", [
            'lock_version' => $document['lock_version'],
            'sections' => $document['sections'],
        ])->assertOk()->assertJsonPath('data.schema_version', 3)->assertJsonPath('data.sections.0.content.heading', 'Atomic draft')->assertJsonCount(2, 'data.sections')->json('data');

        $this->assertGreaterThan($document['lock_version'], $saved['lock_version']);
        $this->getJson('/api/content/pages/'.$page->slug)->assertNotFound();
        $this->putJson("/api/cms/pages/{$page->id}/visual-draft", [
            'lock_version' => $document['lock_version'],
            'sections' => [$document['sections'][0]],
        ])->assertConflict();
        $this->assertSame('Atomic draft', $section->fresh()->content['heading']);
        $this->assertCount(2, $page->fresh()->sections);
    }

    public function test_exact_unsaved_preview_publish_previous_version_and_direct_rollback_share_one_snapshot(): void
    {
        $page = $this->page();
        $this->section($page, 'Published first');
        $this->postJson("/api/cms/pages/{$page->id}/publish")->assertOk();
        $document = $this->getJson("/api/cms/pages/{$page->id}")->assertOk()->json('data');
        $document['sections'][0]['content']['heading'] = 'Current unsaved visual edit';

        $previewUrl = $this->postJson("/api/cms/pages/{$page->id}/preview", [
            'lock_version' => $document['lock_version'],
            'sections' => $document['sections'],
        ])->assertOk()->json('preview_url');
        $this->getJson('/api/cms/preview/'.basename(parse_url($previewUrl, PHP_URL_PATH)))->assertOk()->assertJsonPath('data.sections.0.content.heading', 'Current unsaved visual edit');
        $this->getJson('/api/content/pages/'.$page->slug)->assertJsonPath('data.sections.0.content.heading', 'Published first');

        $published = $this->postJson("/api/cms/pages/{$page->id}/publish", [
            'lock_version' => $document['lock_version'],
            'sections' => $document['sections'],
        ])->assertOk()->assertJsonPath('data.sections.0.content.heading', 'Current unsaved visual edit')->json('data');
        $previous = CmsVersion::where('cms_page_id', $page->id)->where('reason', 'Previous published version')->latest('version')->firstOrFail();
        $this->assertSame('Published first', $previous->snapshot['sections'][0]['content']['heading']);

        $this->postJson("/api/cms/pages/{$page->id}/versions/{$previous->id}/rollback", ['lock_version' => $published['lock_version']])
            ->assertOk()->assertJsonPath('data.sections.0.content.heading', 'Published first');
        $this->getJson('/api/content/pages/'.$page->slug)->assertOk()->assertJsonPath('data.sections.0.content.heading', 'Published first');
    }

    public function test_visual_document_preview_publish_and_rollback_are_power_admin_only(): void
    {
        $page = $this->page();
        $this->section($page, 'Authorization proof');
        $document = $this->getJson("/api/cms/pages/{$page->id}")->assertOk()->json('data');
        $this->postJson("/api/cms/pages/{$page->id}/publish")->assertOk();
        $version = CmsVersion::where('cms_page_id', $page->id)->latest('version')->firstOrFail();

        foreach ([UserRole::Patient, UserRole::Moderator, UserRole::Admin] as $role) {
            Sanctum::actingAs(User::factory()->create(['role' => $role]));
            $payload = ['lock_version' => $page->fresh()->lock_version, 'sections' => $document['sections']];
            $this->putJson("/api/cms/pages/{$page->id}/visual-draft", $payload)->assertForbidden();
            $this->postJson("/api/cms/pages/{$page->id}/preview", $payload)->assertForbidden();
            $this->postJson("/api/cms/pages/{$page->id}/publish", $payload)->assertForbidden();
            $this->postJson("/api/cms/pages/{$page->id}/versions/{$version->id}/rollback", ['lock_version' => $payload['lock_version']])->assertForbidden();
        }

        $this->assertSame('Authorization proof', $page->fresh()->sections()->firstOrFail()->content['heading']);
    }

    public function test_version_restore_creates_new_draft_without_changing_published_snapshot(): void
    {
        $page = $this->page();
        $section = $this->section($page, 'Version one');
        $firstVersion = $page->versions()->latest('version')->first();
        $this->postJson("/api/cms/pages/{$page->id}/publish");
        $this->putJson("/api/cms/pages/{$page->id}/sections/{$section->id}", ['type' => 'hero', 'content' => ['heading' => 'Version two'], 'presentation' => [], 'is_visible' => true])->assertOk();
        $this->postJson("/api/cms/pages/{$page->id}/versions/{$firstVersion->id}/restore")->assertOk()->assertJsonPath('data.sections.0.content.heading', 'Version one');
        $this->assertSame('Version one', CmsPage::find($page->id)->published_snapshot['sections'][0]['content']['heading']);
        $this->assertGreaterThan(3, $page->versions()->count());
    }

    public function test_navigation_and_theme_settings_are_allowlisted(): void
    {
        $this->power();
        $this->putJson('/api/cms/settings/navigation', ['value' => [['label' => 'Care', 'path' => '/services']]])->assertOk();
        $this->getJson('/api/cms/public-settings')->assertOk()->assertJsonMissingPath('data.navigation');
        $this->getJson('/api/cms/settings')->assertOk()->assertJsonPath('data.navigation.draft_value.0.label', 'Care');
        $this->putJson('/api/cms/settings/navigation', ['value' => [['label' => 'Bad', 'path' => 'javascript:alert(1)']]])->assertUnprocessable();
        $this->putJson('/api/cms/settings/theme', ['value' => ['palette' => 'wine', 'density' => 'comfortable', 'heading_style' => 'editorial']])->assertOk();
        $this->postJson('/api/cms/settings/navigation/publish')->assertOk();
        $this->postJson('/api/cms/settings/theme/publish')->assertOk();
        $this->getJson('/api/cms/public-settings')->assertOk()->assertJsonPath('data.navigation.0.label', 'Care')->assertJsonPath('data.theme.palette', 'wine');

        $this->putJson('/api/cms/settings/navigation', ['value' => [['label' => 'Private draft', 'path' => '/about']]])->assertOk();
        $this->putJson('/api/cms/settings/theme', ['value' => ['palette' => 'plum', 'density' => 'compact', 'heading_style' => 'modern']])->assertOk();
        $this->getJson('/api/cms/public-settings')->assertJsonPath('data.navigation.0.label', 'Care')->assertJsonPath('data.theme.palette', 'wine');
    }

    public function test_every_structured_section_and_presentation_control_round_trips(): void
    {
        $page = $this->page();
        $itemKey = fn () => (string) \Illuminate\Support\Str::uuid();
        $sections = [
            'hero' => ['eyebrow' => 'Care', 'heading' => 'Hero', 'text' => 'Intro', 'primary_label' => 'Book', 'primary_url' => '/book', 'secondary_label' => 'Learn', 'secondary_url' => 'https://example.test/learn'],
            'text' => ['eyebrow' => 'Profile', 'heading' => 'Text', 'body' => "First paragraph\nSecond paragraph"],
            'image' => ['heading' => 'Image', 'image_url' => '/media/photo.jpg', 'image_alt' => 'Clinician speaking with a patient', 'caption' => 'Patient-centred care', 'image_link' => '/about'],
            'image_text' => ['eyebrow' => 'Team', 'heading' => 'Image and text', 'text' => 'Approved photograph', 'image_url' => 'https://example.test/photo.jpg', 'image_alt' => 'Clinician speaking with a patient'],
            'cards' => ['eyebrow' => 'Explore', 'heading' => 'Cards', 'text' => 'Resources', 'items' => [['key' => $itemKey(), 'heading' => 'Guide', 'text' => 'Helpful guide', 'url' => '/guide', 'is_visible' => true]]],
            'services' => ['eyebrow' => 'Care', 'heading' => 'Services', 'text' => 'Care options', 'items' => [['key' => $itemKey(), 'heading' => 'Consultation', 'text' => 'Specialist review', 'url' => '/book', 'is_visible' => true]]],
            'cta' => ['eyebrow' => 'Next', 'heading' => 'CTA', 'text' => 'Act now', 'button_label' => 'Contact', 'button_url' => '/contact'],
            'publications' => ['eyebrow' => 'Research', 'heading' => 'Publications', 'text' => 'Selected work', 'items' => [['key' => $itemKey(), 'title' => 'Paper title', 'meta' => 'Journal · 2026', 'url' => 'https://example.test/paper', 'is_visible' => true]]],
            'career_timeline' => ['eyebrow' => 'Career', 'heading' => 'Timeline', 'text' => 'Professional journey', 'items' => [['key' => $itemKey(), 'year' => '2026', 'heading' => 'Professor', 'text' => 'Career milestone', 'is_visible' => true]]],
            'achievements' => ['eyebrow' => 'Impact', 'heading' => 'Achievements', 'text' => 'Selected impact', 'items' => [['key' => $itemKey(), 'value' => '25+', 'heading' => 'Years', 'text' => 'Specialist service', 'is_visible' => true]]],
            'faq' => ['eyebrow' => 'Questions', 'heading' => 'FAQ', 'text' => 'Common questions', 'items' => [['key' => $itemKey(), 'question' => 'What happens next?', 'answer' => 'The team will guide you.', 'is_visible' => true]]],
            'gallery' => ['eyebrow' => 'Gallery', 'heading' => 'Gallery', 'text' => 'Approved images', 'items' => [['key' => $itemKey(), 'image_url' => '/media/gallery.jpg', 'image_alt' => 'Clinical team', 'caption' => 'The care team', 'is_visible' => true]]],
            'stats' => ['eyebrow' => 'Impact', 'heading' => 'Stats', 'text' => 'Selected measures', 'items' => [['key' => $itemKey(), 'value' => '25+', 'label' => 'Years', 'is_visible' => true]]],
            'contact' => ['eyebrow' => 'Contact', 'heading' => 'Contact us', 'text' => 'Choose a channel', 'email' => 'care@example.org', 'telephone' => '+234 800 000 0000', 'address' => 'Ile-Ife, Nigeria'],
            'appointment' => ['eyebrow' => 'Appointments', 'heading' => 'Book care', 'text' => 'Choose a time', 'button_label' => 'Book', 'button_url' => '/book', 'button_action' => 'internal'],
            'video' => ['eyebrow' => 'Watch', 'heading' => 'Video', 'text' => 'Introduction', 'video_url' => 'https://example.test/video.mp4', 'poster_url' => '/media/poster.jpg', 'caption' => 'Patient education video'],
            'divider' => ['heading' => 'Divider', 'label' => 'Continue'],
            'spacer' => ['heading' => 'Spacer'],
        ];
        foreach ($sections as $type => $content) {
            $this->postJson("/api/cms/pages/{$page->id}/sections", [
                'type' => $type, 'content' => $content,
                'presentation' => ['background' => 'blush', 'alignment' => 'center', 'width' => 'wide', 'spacing' => 'generous'],
                'is_visible' => false,
            ])->assertCreated()->assertJsonPath('data.presentation.width', 'wide')->assertJsonPath('data.is_visible', false);
        }
        $this->getJson("/api/cms/pages/{$page->id}")->assertOk()->assertJsonCount(18, 'data.sections')->assertJsonPath('data.sections.12.content.items.0.label', 'Years')->assertJsonPath('data.sections.17.type', 'spacer');
    }

    public function test_page_and_section_lifecycle_supports_duplicate_unpublish_seo_and_conflict_detection(): void
    {
        $page = $this->page();
        $section = $this->section($page, 'Lifecycle source');
        $version = $this->getJson("/api/cms/pages/{$page->id}")->assertOk()->json('data.lock_version');
        $this->putJson("/api/cms/pages/{$page->id}", [
            'title' => 'Living well guide', 'slug' => 'living-well', 'template' => 'resource',
            'seo_title' => 'Living well after breast treatment', 'seo_description' => 'A clear guide to follow-up care.', 'lock_version' => $version,
        ])->assertOk()->assertJsonPath('data.seo_title', 'Living well after breast treatment');
        $this->putJson("/api/cms/pages/{$page->id}", [
            'title' => 'Stale editor', 'slug' => 'living-well', 'template' => 'resource', 'lock_version' => $version,
        ])->assertConflict();

        $this->postJson("/api/cms/pages/{$page->id}/sections/{$section->id}/duplicate")->assertCreated();
        $this->assertCount(2, $page->fresh()->sections);
        $this->postJson("/api/cms/pages/{$page->id}/publish")->assertOk();
        $this->getJson('/api/content/pages/living-well')->assertOk()->assertJsonPath('data.seo.title', 'Living well after breast treatment');

        $copyId = $this->postJson("/api/cms/pages/{$page->id}/duplicate", ['title' => 'Living well copy', 'slug' => 'living-well-copy'])
            ->assertCreated()->assertJsonPath('data.status', 'draft')->json('data.id');
        $this->getJson("/api/cms/pages/{$copyId}")->assertJsonCount(2, 'data.sections');
        $this->getJson('/api/content/pages/living-well-copy')->assertNotFound();

        $this->postJson("/api/cms/pages/{$page->id}/unpublish")->assertOk();
        $this->getJson('/api/content/pages/living-well')->assertNotFound();
    }

    public function test_advanced_button_image_and_typography_options_are_allowlisted(): void
    {
        $page = $this->page();
        $payload = [
            'type' => 'hero',
            'content' => ['heading' => 'Options', 'heading_marks' => [['type' => 'bold', 'start' => 0, 'end' => 7]], 'primary_label' => 'Book', 'primary_url' => '/book', 'primary_action' => 'internal', 'primary_style' => 'outline', 'primary_icon' => 'calendar', 'primary_icon_position' => 'left', 'primary_target' => '_self', 'primary_visibility' => 'show'],
            'presentation' => ['background' => 'white', 'alignment' => 'center', 'width' => 'wide', 'spacing' => 'compact', 'font_family' => 'modern', 'font_size' => 'large', 'font_weight' => 'bold', 'emphasis' => 'italic_underline', 'text_color' => 'wine', 'line_height' => 'relaxed', 'text_styles' => ['heading' => ['font_family' => 'editorial', 'font_size' => '4xl', 'font_weight' => '700', 'bold' => true, 'italic' => false, 'underline' => false, 'color' => 'wine', 'alignment' => 'center', 'line_height' => '1.2', 'letter_spacing' => '-0.02em', 'text_decoration' => 'none']], 'button_styles' => ['primary' => ['alignment' => 'center', 'size' => 'large', 'font_family' => 'modern', 'font_size' => 'lg', 'font_weight' => '800', 'background_color' => 'wine', 'text_color' => 'white', 'border_style' => 'solid', 'border_width' => '2', 'border_color' => 'wine', 'border_radius' => '24', 'padding_x' => '24', 'padding_y' => '12', 'margin' => '8']]],
            'is_visible' => true,
        ];
        $payload['presentation']['background_image'] = '/media/approved-hero.jpg';
        $payload['presentation']['responsive'] = ['desktop' => ['layout' => 'grid', 'columns' => '2', 'background_gradient' => 'rose_glow'], 'tablet' => ['columns' => '1', 'spacing' => 'compact'], 'mobile' => ['background' => 'wine', 'width' => 'full', 'min_height' => 'short', 'border_style' => 'solid', 'border_width' => '2', 'border_color' => 'rose', 'radius' => 'soft', 'shadow' => 'strong']];
        $this->postJson("/api/cms/pages/{$page->id}/sections", $payload)->assertCreated()->assertJsonPath('data.content.primary_icon', 'calendar')->assertJsonPath('data.content.primary_icon_position', 'left')->assertJsonPath('data.content.heading_marks.0.type', 'bold')->assertJsonPath('data.presentation.emphasis', 'italic_underline')->assertJsonPath('data.presentation.text_styles.heading.font_size', '4xl')->assertJsonPath('data.presentation.button_styles.primary.border_radius', '24')->assertJsonPath('data.presentation.responsive.mobile.background', 'wine')->assertJsonPath('data.presentation.responsive.desktop.columns', '2');
        $payload['content']['primary_style'] = 'javascript';
        $this->postJson("/api/cms/pages/{$page->id}/sections", $payload)->assertUnprocessable();
        $payload['content']['primary_style'] = 'primary';
        $payload['content']['primary_action'] = 'script';
        $this->postJson("/api/cms/pages/{$page->id}/sections", $payload)->assertUnprocessable();
        $payload['content']['primary_action'] = 'internal';
        $payload['presentation']['button_styles']['primary']['position'] = 'fixed';
        $this->postJson("/api/cms/pages/{$page->id}/sections", $payload)->assertUnprocessable();
        unset($payload['presentation']['button_styles']['primary']['position']);
        $payload['presentation']['font_family'] = 'url(evil)';
        $this->postJson("/api/cms/pages/{$page->id}/sections", $payload)->assertUnprocessable();
        $payload['presentation']['font_family'] = 'site';
        $payload['presentation']['text_styles']['heading']['position'] = 'fixed';
        $this->postJson("/api/cms/pages/{$page->id}/sections", $payload)->assertUnprocessable();
        unset($payload['presentation']['text_styles']['heading']['position']);
        $payload['presentation']['responsive']['mobile']['position'] = 'fixed';
        $this->postJson("/api/cms/pages/{$page->id}/sections", $payload)->assertUnprocessable();
        unset($payload['presentation']['responsive']['mobile']['position']);
        $payload['presentation']['background_image'] = '/image.jpg\");position:fixed';
        $this->postJson("/api/cms/pages/{$page->id}/sections", $payload)->assertUnprocessable();
        $payload['presentation']['background_image'] = '/media/approved-hero.jpg';
        $payload['content']['heading_marks'] = [['type' => 'link', 'start' => 0, 'end' => 7, 'url' => 'mailto:care@example.org', 'target' => '_self', 'action' => 'email']];
        $this->postJson("/api/cms/pages/{$page->id}/sections", $payload)->assertCreated()->assertJsonPath('data.content.heading_marks.0.action', 'email');
        $payload['content']['heading_marks'][0]['action'] = 'internal';
        $this->postJson("/api/cms/pages/{$page->id}/sections", $payload)->assertUnprocessable();
        $payload['content']['heading_marks'] = [['type' => 'link', 'start' => 0, 'end' => 7, 'url' => 'javascript:alert(1)']];
        $this->postJson("/api/cms/pages/{$page->id}/sections", $payload)->assertUnprocessable();
    }

    public function test_navigation_supports_visibility_reordering_and_one_safe_submenu_level(): void
    {
        $this->power();
        $navigation = [
            ['label' => 'Care', 'path' => '/services', 'is_visible' => true, 'children' => [
                ['label' => 'Second opinion', 'path' => '/book', 'is_visible' => true],
                ['label' => 'Hidden guide', 'path' => '/p/guide', 'is_visible' => false],
            ]],
            ['label' => 'Private', 'path' => '/p/private', 'is_visible' => false],
        ];
        $this->putJson('/api/cms/settings/navigation', ['value' => $navigation])->assertOk()
            ->assertJsonPath('data.draft_value.0.children.0.label', 'Second opinion')
            ->assertJsonPath('data.draft_value.1.is_visible', false);
        $this->postJson('/api/cms/settings/navigation/publish')->assertOk();
        $this->getJson('/api/cms/public-settings')->assertJsonPath('data.navigation.0.children.1.is_visible', false);

        $navigation[0]['children'][0]['children'] = [['label' => 'Too deep', 'path' => '/about']];
        $this->putJson('/api/cms/settings/navigation', ['value' => $navigation])->assertUnprocessable();
    }

    private function power(): User
    {
        $user = User::factory()->create(['role' => UserRole::PowerAdmin]);
        Sanctum::actingAs($user);

        return $user;
    }

    private function page(): CmsPage
    {
        $user = $this->power();

        return CmsPage::create(['title' => 'Living well', 'slug' => 'living-well', 'template' => 'standard', 'created_by' => $user->id]);
    }

    private function section(CmsPage $page, string $heading)
    {
        $response = $this->postJson("/api/cms/pages/{$page->id}/sections", ['type' => 'hero', 'content' => ['heading' => $heading], 'presentation' => [], 'is_visible' => true])->assertCreated();

        return CmsSection::findOrFail($response->json('data.id'));
    }
}
