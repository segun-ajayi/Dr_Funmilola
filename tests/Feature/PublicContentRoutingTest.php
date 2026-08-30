<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\CmsPage;
use App\Models\CmsSetting;
use App\Models\EducationArticle;
use App\Models\Publication;
use App\Models\User;
use App\Services\CmsService;
use Database\Seeders\CoreCmsPageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicContentRoutingTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_and_known_routes_are_real_while_missing_paths_return_http_404(): void
    {
        foreach (['/', '/about', '/services', '/research', '/academic', '/education', '/contact', '/book', '/privacy', '/terms', '/accessibility', '/security', '/staff/accounts'] as $path) {
            $this->get($path)->assertOk();
        }

        $this->get('/contact-missing')->assertNotFound();
        $this->get('/definitely-not-home')->assertNotFound();
        $this->get('/p/missing-page')->assertNotFound();
        $this->get('/education/missing-article')->assertNotFound();
        $this->get('/academic/publications/999999')->assertNotFound();
    }

    public function test_dynamic_web_routes_exist_only_for_published_content(): void
    {
        $owner = User::factory()->create(['role' => UserRole::PowerAdmin]);
        $page = CmsPage::create(['title' => 'Patient guide', 'slug' => 'patient-guide', 'created_by' => $owner->id]);
        $article = $this->article('draft');
        $publication = Publication::create(['title' => 'Private study', 'verification_status' => 'verified']);

        $this->get('/p/patient-guide')->assertNotFound();
        $this->get('/education/'.$article->slug)->assertNotFound();
        $this->get('/academic/publications/'.$publication->id)->assertNotFound();

        $page->update(['published_snapshot' => app(CmsService::class)->snapshot($page), 'status' => 'published']);
        $article->update(['status' => 'published', 'published_at' => now()]);
        $publication->update(['verification_status' => 'published']);

        $this->get('/p/patient-guide')->assertOk();
        $this->get('/education/'.$article->slug)->assertOk();
        $this->get('/academic/publications/'.$publication->id)->assertOk();
    }

    public function test_sitemap_contains_only_indexable_published_content_and_robots_points_to_it(): void
    {
        $owner = User::factory()->create(['role' => UserRole::PowerAdmin]);
        $published = CmsPage::create(['title' => 'Public guide', 'slug' => 'public-guide', 'created_by' => $owner->id]);
        $published->update(['published_snapshot' => app(CmsService::class)->snapshot($published), 'status' => 'published']);
        CmsPage::create(['title' => 'Private guide', 'slug' => 'private-guide', 'created_by' => $owner->id]);
        $article = $this->article('published');
        $publication = Publication::create(['title' => 'Published study', 'verification_status' => 'published']);
        Publication::create(['title' => 'Retracted study', 'verification_status' => 'retracted']);

        $xml = $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8')->getContent();

        $this->assertStringContainsString(url('/contact'), $xml);
        $this->assertStringContainsString(url('/p/public-guide'), $xml);
        $this->assertStringContainsString(url('/education/'.$article->slug), $xml);
        $this->assertStringContainsString(url('/academic/publications/'.$publication->id), $xml);
        $this->assertStringNotContainsString('private-guide', $xml);
        $this->assertStringNotContainsString('Retracted', $xml);
        $this->assertStringNotContainsString(url('/privacy'), $xml);
        $this->get('/robots.txt')->assertOk()->assertSee('Sitemap: '.url('/sitemap.xml'), false);
    }

    public function test_server_html_has_route_specific_metadata_canonical_and_safe_noindex_rules(): void
    {
        $this->get('/contact')->assertOk()
            ->assertSee('<title>Contact the practice | Dr. Funmilola Olanike Wuraola</title>', false)
            ->assertSee('name="description" content="Request an appointment or contact the practice securely through the patient portal."', false)
            ->assertSee('rel="canonical" href="'.url('/contact').'"', false)
            ->assertSee('property="og:url" content="'.url('/contact').'"', false)
            ->assertSee('name="robots" content="index, follow"', false);
        $this->get('/privacy')->assertOk()->assertSee('name="robots" content="noindex, nofollow"', false)->assertDontSee('rel="canonical"', false);
        $this->get('/missing-metadata-page')->assertNotFound()->assertSee('name="robots" content="noindex, nofollow"', false)->assertDontSee('rel="canonical"', false);

        $article = $this->article('published', 'metadata-guide');
        $this->get('/education/'.$article->slug)->assertOk()
            ->assertSee('<title>'.$article->title.' | Dr. Funmilola Olanike Wuraola</title>', false)
            ->assertSee('property="og:type" content="article"', false)
            ->assertSee('rel="canonical" href="'.url('/education/'.$article->slug).'"', false);
    }

    public function test_core_seed_has_metadata_and_complete_public_navigation_without_overwriting_existing_pages(): void
    {
        User::factory()->create(['role' => UserRole::PowerAdmin]);
        $this->seed(CoreCmsPageSeeder::class);

        $home = CmsPage::where('slug', 'home')->firstOrFail();
        $this->assertNotEmpty($home->seo_title);
        $this->assertNotEmpty($home->seo_description);
        $this->assertSame($home->seo_title, $home->published_snapshot['seo']['title']);
        $navigation = CmsSetting::where('key', 'navigation')->firstOrFail()->published_value;
        $this->assertTrue(collect($navigation)->contains('path', '/contact'));
        $research = collect($navigation)->firstWhere('path', '/research');
        $this->assertTrue(collect($research['children'])->contains('path', '/academic'));
        $this->assertTrue(collect($research['children'])->contains('path', '/education'));
    }

    public function test_article_and_publication_detail_apis_never_expose_unpublished_records(): void
    {
        $draft = $this->article('draft');
        $published = $this->article('published', 'published-guide');
        $privatePublication = Publication::create(['title' => 'Private paper', 'verification_status' => 'verified']);
        $publicPublication = Publication::create(['title' => 'Public paper', 'verification_status' => 'published']);

        $this->getJson('/api/education/articles/'.$draft->slug)->assertNotFound();
        $this->getJson('/api/education/articles/'.$published->slug)->assertOk()->assertJsonPath('data.title', $published->title);
        $this->getJson('/api/academic/publications/'.$privatePublication->id)->assertNotFound();
        $this->getJson('/api/academic/publications/'.$publicPublication->id)->assertOk()->assertJsonPath('data.title', 'Public paper');
    }

    private function article(string $status, string $slug = 'patient-guide'): EducationArticle
    {
        return EducationArticle::create([
            'title' => 'Understanding a clinic visit',
            'slug' => $slug,
            'summary' => 'A medically reviewed appointment guide.',
            'content' => 'This guide explains what to expect during a clinic appointment.',
            'author' => 'Practice Education Team',
            'medical_reviewer' => 'Dr. Reviewer',
            'reviewed_at' => now()->toDateString(),
            'content_updated_at' => now()->toDateString(),
            'category' => 'Appointments',
            'medical_disclaimer' => 'This general information does not replace personal medical advice.',
            'status' => $status,
            'published_at' => $status === 'published' ? now() : null,
        ]);
    }
}
