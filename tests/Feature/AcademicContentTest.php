<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Publication;
use App\Models\ResearchClaim;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AcademicContentTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_published_records_appear_in_both_public_feeds(): void
    {
        foreach (['pending_review', 'verified', 'rejected', 'retracted'] as $status) {
            Publication::create(['title' => "{$status} study", 'category' => 'Breast Cancer', 'verification_status' => $status]);
        }
        Publication::create([
            'title' => 'Published survivor study',
            'authors' => 'F. Wuraola',
            'category' => 'Survivorship',
            'verification_status' => 'published',
            'published_at' => '2023-01-01',
        ]);

        $this->getJson('/api/academic/publications')->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.title', 'Published survivor study');
        $this->getJson('/api/public')->assertOk()
            ->assertJsonCount(1, 'publications')
            ->assertJsonPath('publications.0.title', 'Published survivor study');
    }

    public function test_power_admin_can_review_publish_and_retract_as_one_consistent_lifecycle(): void
    {
        $claim = $this->claim();
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::Admin]));
        $this->getJson('/api/cms/verification-queue')->assertForbidden();

        $powerAdmin = User::factory()->create(['role' => UserRole::PowerAdmin]);
        Sanctum::actingAs($powerAdmin);
        $this->patchJson("/api/cms/verification-queue/{$claim->id}", ['decision' => 'verified'])->assertOk();
        $this->postJson("/api/cms/verification-queue/{$claim->id}/publish")->assertOk();

        $claim->refresh();
        $this->assertSame('published', $claim->status);
        $this->assertSame('publication', $claim->published_record_type);
        $this->assertNotNull($claim->published_record_id);
        $this->assertDatabaseHas('publications', [
            'id' => $claim->published_record_id,
            'source_claim_id' => $claim->id,
            'doi' => '10.1000/safe',
            'verification_status' => 'published',
        ]);
        $this->getJson('/api/academic/publications?q=Verified')->assertJsonPath('total', 1);
        $this->getJson('/api/public')->assertJsonCount(1, 'publications');

        $this->patchJson("/api/cms/verification-queue/{$claim->id}", ['decision' => 'rejected'])->assertUnprocessable();
        $this->postJson("/api/cms/verification-queue/{$claim->id}/retract", ['reason' => 'The source record was withdrawn.'])->assertOk();

        $this->assertDatabaseHas('research_claims', [
            'id' => $claim->id,
            'status' => 'retracted',
            'retracted_by' => $powerAdmin->id,
            'retracted_reason' => 'The source record was withdrawn.',
        ]);
        $this->assertDatabaseHas('publications', ['id' => $claim->published_record_id, 'verification_status' => 'retracted']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'research_claim.published', 'subject_id' => $claim->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'research_claim.retracted', 'subject_id' => $claim->id]);
        $this->getJson('/api/academic/publications')->assertJsonPath('total', 0);
        $this->getJson('/api/public')->assertJsonCount(0, 'publications');
    }

    public function test_invalid_transitions_and_short_retraction_reasons_are_rejected(): void
    {
        $claim = $this->claim();
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::PowerAdmin]));

        $this->postJson("/api/cms/verification-queue/{$claim->id}/publish")->assertUnprocessable();
        $this->postJson("/api/cms/verification-queue/{$claim->id}/retract", ['reason' => 'Too short'])->assertUnprocessable();
        $this->patchJson("/api/cms/verification-queue/{$claim->id}", ['decision' => 'rejected'])->assertOk();
        $this->patchJson("/api/cms/verification-queue/{$claim->id}", ['decision' => 'verified'])->assertUnprocessable();
        $this->postJson("/api/cms/verification-queue/{$claim->id}/publish")->assertUnprocessable();

        $this->assertDatabaseCount('publications', 0);
        $this->assertDatabaseHas('research_claims', ['id' => $claim->id, 'status' => 'rejected']);
    }

    public function test_publications_without_external_identifiers_use_title_identity_and_never_overwrite_each_other(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::PowerAdmin]));
        $first = $this->claim(['title' => 'First study', 'doi' => null, 'pmid' => null], ['claim' => 'First sourced study']);
        $second = $this->claim(['title' => 'Second study', 'doi' => null, 'pmid' => null], ['claim' => 'Second sourced study']);

        foreach ([$first, $second] as $claim) {
            $this->patchJson("/api/cms/verification-queue/{$claim->id}", ['decision' => 'verified'])->assertOk();
            $this->postJson("/api/cms/verification-queue/{$claim->id}/publish")->assertOk();
        }

        $this->assertDatabaseCount('publications', 2);
        $this->assertDatabaseHas('publications', ['source_claim_id' => $first->id, 'title' => 'First study']);
        $this->assertDatabaseHas('publications', ['source_claim_id' => $second->id, 'title' => 'Second study']);
    }

    public function test_duplicate_doi_pmid_or_normalized_title_cannot_be_published_by_another_claim(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::PowerAdmin]));
        $original = $this->claim();
        $this->patchJson("/api/cms/verification-queue/{$original->id}", ['decision' => 'verified']);
        $this->postJson("/api/cms/verification-queue/{$original->id}/publish")->assertOk();

        $duplicate = $this->claim([
            'title' => 'Different title',
            'doi' => 'https://doi.org/10.1000/SAFE',
            'pmid' => 'PMID 123456',
        ], ['claim' => 'A duplicate identifier claim']);
        $this->patchJson("/api/cms/verification-queue/{$duplicate->id}", ['decision' => 'verified'])->assertOk();
        $this->postJson("/api/cms/verification-queue/{$duplicate->id}/publish")->assertUnprocessable();

        $titleOriginal = $this->claim(['title' => 'Title Identity Study', 'doi' => null, 'pmid' => null], ['claim' => 'Title identity original']);
        $this->patchJson("/api/cms/verification-queue/{$titleOriginal->id}", ['decision' => 'verified']);
        $this->postJson("/api/cms/verification-queue/{$titleOriginal->id}/publish")->assertOk();
        $titleDuplicate = $this->claim(['title' => '  TITLE   identity study ', 'doi' => null, 'pmid' => null], ['claim' => 'Title identity duplicate']);
        $this->patchJson("/api/cms/verification-queue/{$titleDuplicate->id}", ['decision' => 'verified']);
        $this->postJson("/api/cms/verification-queue/{$titleDuplicate->id}/publish")->assertUnprocessable();

        $this->assertDatabaseCount('publications', 2);
    }

    public function test_review_payload_cannot_override_system_owned_publication_fields(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::PowerAdmin]));
        $claim = $this->claim([
            'source_claim_id' => 9999,
            'identity_key' => 'attacker-controlled',
            'verification_status' => 'rejected',
        ]);
        $this->patchJson("/api/cms/verification-queue/{$claim->id}", ['decision' => 'verified']);
        $this->postJson("/api/cms/verification-queue/{$claim->id}/publish")->assertOk();

        $this->assertDatabaseHas('publications', [
            'source_claim_id' => $claim->id,
            'identity_key' => 'doi:10.1000/safe',
            'verification_status' => 'published',
        ]);
    }

    public function test_fresh_seed_creates_only_sourced_review_claims_and_is_idempotent(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('publications', 0);
        $this->assertSame(6, ResearchClaim::where('target_type', 'publication')->count());
        ResearchClaim::where('target_type', 'publication')->get()->each(function (ResearchClaim $claim) {
            $this->assertSame('pending_review', $claim->status);
            $this->assertStringStartsWith('https://pubmed.ncbi.nlm.nih.gov/', $claim->source_url);
            $this->assertNotEmpty($claim->source_title);
            $this->assertNotEmpty($claim->target_payload['doi']);
            $this->assertNotEmpty($claim->target_payload['pmid']);
        });

        $reviewed = ResearchClaim::where('target_type', 'publication')->firstOrFail();
        $reviewed->update(['status' => 'verified']);
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(6, ResearchClaim::where('target_type', 'publication')->count());
        $this->assertSame('verified', $reviewed->fresh()->status);
        $this->assertDatabaseCount('publications', 0);
    }

    public function test_upgrade_migration_removes_incomplete_seed_rows_and_reconciles_legacy_duplicates(): void
    {
        $migration = require database_path('migrations/2026_08_28_000004_unify_research_publication_lifecycle.php');
        $migration->down();

        DB::table('publications')->insert([
            [
                'title' => 'Legacy duplicate study',
                'authors' => 'First Author',
                'journal' => null,
                'published_at' => null,
                'doi' => 'https://doi.org/10.1000/DUPLICATE',
                'pmid' => null,
                'category' => 'Breast Cancer',
                'external_url' => null,
                'featured' => false,
                'verification_status' => 'verified',
                'publication_type' => 'journal_article',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Legacy duplicate study',
                'authors' => null,
                'journal' => 'Journal of Safe Migration',
                'published_at' => '2025-01-01',
                'doi' => '10.1000/duplicate',
                'pmid' => '123456',
                'category' => 'Breast Cancer',
                'external_url' => 'https://example.test/legacy',
                'featured' => false,
                'verification_status' => 'published',
                'publication_type' => 'journal_article',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Old title-only seed',
                'authors' => null,
                'journal' => null,
                'published_at' => null,
                'doi' => null,
                'pmid' => null,
                'category' => 'Breast Cancer',
                'external_url' => null,
                'featured' => false,
                'verification_status' => 'pending_review',
                'publication_type' => 'journal_article',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $migration->up();

        $this->assertDatabaseMissing('publications', ['title' => 'Old title-only seed']);
        $this->assertDatabaseCount('publications', 1);
        $this->assertDatabaseHas('publications', [
            'title' => 'Legacy duplicate study',
            'authors' => 'First Author',
            'journal' => 'Journal of Safe Migration',
            'doi' => '10.1000/duplicate',
            'pmid' => '123456',
            'identity_key' => 'doi:10.1000/duplicate',
            'verification_status' => 'published',
        ]);
    }

    public function test_education_requires_review_metadata_and_plain_text_before_publication(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::PowerAdmin]));
        $payload = [
            'title' => 'Understanding breast clinic visits',
            'slug' => 'understanding-breast-clinic-visits',
            'summary' => 'A clear guide to preparing for a specialist breast clinic visit.',
            'content' => 'This educational guide explains what patients may expect during an appointment.',
            'author' => 'Practice Education Team',
            'medical_reviewer' => 'Dr. Reviewer',
            'reviewed_at' => now()->toDateString(),
            'content_updated_at' => now()->toDateString(),
            'category' => 'Breast awareness',
            'tags' => ['appointments'],
            'medical_disclaimer' => 'This general educational information does not replace personal medical advice or emergency care.',
        ];
        $article = $this->postJson('/api/cms/education', $payload)->assertCreated()->json('data.id');
        $this->getJson('/api/education/articles')->assertJsonPath('total', 0);
        $this->postJson("/api/cms/education/{$article}/publish")->assertOk();
        $this->getJson('/api/education/articles/understanding-breast-clinic-visits')->assertOk()->assertJsonPath('data.medical_reviewer', 'Dr. Reviewer');
        $payload['content'] = '<script>unsafe</script>';
        $this->postJson('/api/cms/education', $payload + ['slug' => 'unsafe'])->assertUnprocessable();
    }

    private function claim(array $payloadOverrides = [], array $claimOverrides = []): ResearchClaim
    {
        $payload = array_merge([
            'title' => 'Verified publication',
            'authors' => 'F. Wuraola',
            'journal' => 'Journal',
            'published_at' => '2025-01-01',
            'doi' => '10.1000/safe',
            'pmid' => null,
            'category' => 'Breast Cancer',
            'external_url' => 'https://example.test/paper',
            'publication_type' => 'journal_article',
        ], $payloadOverrides);

        return ResearchClaim::create(array_merge([
            'category' => 'Publication',
            'claim' => 'Verified publication record',
            'source_title' => 'Journal record',
            'source_url' => 'https://example.test/source',
            'confidence' => 'high',
            'status' => 'pending_review',
            'target_type' => 'publication',
            'target_payload' => $payload,
        ], $claimOverrides));
    }
}
