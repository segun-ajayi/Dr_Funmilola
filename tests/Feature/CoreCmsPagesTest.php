<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\CmsPage;
use App\Models\User;
use Database\Seeders\CoreCmsPageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CoreCmsPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_core_page_seed_is_idempotent_and_preserves_existing_records(): void
    {
        $owner = User::factory()->create(['role' => UserRole::PowerAdmin]);
        CmsPage::create(['title' => 'Custom About', 'slug' => 'about', 'template' => 'standard', 'created_by' => $owner->id]);

        $this->seed(CoreCmsPageSeeder::class);
        $this->seed(CoreCmsPageSeeder::class);

        $this->assertDatabaseCount('cms_pages', 4);
        $this->assertDatabaseHas('cms_pages', ['slug' => 'about', 'title' => 'Custom About']);
        $this->assertDatabaseHas('cms_pages', ['slug' => 'home', 'status' => 'published']);
        $this->assertSame(3, CmsPage::where('status', 'published')->count());
        $this->assertDatabaseCount('cms_settings', 2);
        $this->getJson('/api/cms/public-settings')->assertOk()
            ->assertJsonPath('data.navigation.0.label', 'Home')
            ->assertJsonPath('data.theme.palette', 'wine');
    }

    public function test_seeded_core_page_is_public_at_cms_contract_and_draft_changes_stay_private(): void
    {
        User::factory()->create(['role' => UserRole::PowerAdmin]);
        $this->seed(CoreCmsPageSeeder::class);
        $page = CmsPage::where('slug', 'home')->firstOrFail();
        $publishedHeading = $page->published_snapshot['sections'][0]['content']['heading'];

        $this->getJson('/api/content/pages/home')->assertOk()->assertJsonPath('data.slug', 'home')->assertJsonPath('data.sections.0.content.heading', $publishedHeading);

        $page->sections()->first()->update(['content' => ['heading' => 'Private draft heading']]);
        $page->update(['status' => 'draft']);

        $this->getJson('/api/content/pages/home')->assertJsonPath('data.sections.0.content.heading', $publishedHeading);
    }
}
