<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('research_claims', function (Blueprint $table) {
            $table->string('published_record_type')->nullable()->after('target_payload');
            $table->unsignedBigInteger('published_record_id')->nullable()->after('published_record_type');
            $table->timestamp('retracted_at')->nullable()->after('published_record_id');
            $table->foreignId('retracted_by')->nullable()->after('retracted_at')->constrained('users')->nullOnDelete();
            $table->text('retracted_reason')->nullable()->after('retracted_by');
        });
        Schema::table('publications', function (Blueprint $table) {
            $table->string('identity_key', 80)->nullable()->after('id');
            $table->foreignId('source_claim_id')->nullable()->after('identity_key')->constrained('research_claims')->nullOnDelete();
        });
        Schema::table('career_entries', fn (Blueprint $table) => $table->foreignId('source_claim_id')->nullable()->constrained('research_claims')->nullOnDelete());
        Schema::table('achievements', fn (Blueprint $table) => $table->foreignId('source_claim_id')->nullable()->constrained('research_claims')->nullOnDelete());

        $this->removeIncompleteSeedRows();
        $this->reconcilePublications();
        $this->linkPublishedClaims();

        Schema::table('publications', function (Blueprint $table) {
            $table->unique('identity_key');
            $table->unique('source_claim_id');
            $table->unique('doi');
            $table->unique('pmid');
        });
        Schema::table('career_entries', fn (Blueprint $table) => $table->unique('source_claim_id'));
        Schema::table('achievements', fn (Blueprint $table) => $table->unique('source_claim_id'));
    }

    public function down(): void
    {
        Schema::table('achievements', function (Blueprint $table) {
            $table->dropUnique(['source_claim_id']);
            $table->dropConstrainedForeignId('source_claim_id');
        });
        Schema::table('career_entries', function (Blueprint $table) {
            $table->dropUnique(['source_claim_id']);
            $table->dropConstrainedForeignId('source_claim_id');
        });
        Schema::table('publications', function (Blueprint $table) {
            $table->dropUnique(['identity_key']);
            $table->dropUnique(['source_claim_id']);
            $table->dropUnique(['doi']);
            $table->dropUnique(['pmid']);
            $table->dropConstrainedForeignId('source_claim_id');
            $table->dropColumn('identity_key');
        });
        Schema::table('research_claims', function (Blueprint $table) {
            $table->dropConstrainedForeignId('retracted_by');
            $table->dropColumn(['published_record_type', 'published_record_id', 'retracted_at', 'retracted_reason']);
        });
    }

    private function removeIncompleteSeedRows(): void
    {
        DB::table('publications')->where('verification_status', 'pending_review')->whereNull('authors')->whereNull('journal')->whereNull('doi')->whereNull('pmid')->whereNull('external_url')->delete();
    }

    private function reconcilePublications(): void
    {
        $groups = DB::table('publications')->get()->groupBy(fn ($row) => $this->identity($row));
        foreach ($groups as $key => $rows) {
            $ordered = $rows->sortByDesc(fn ($row) => match ($row->verification_status) {
                'published' => 3,'verified' => 2,default => 1
            })->values();
            $keeper = $ordered->first();
            $merged = (array) $keeper;
            foreach ($ordered->slice(1) as $duplicate) {
                foreach (['authors', 'journal', 'published_at', 'doi', 'pmid', 'external_url', 'abstract', 'keywords'] as $field) {
                    if (empty($merged[$field]) && ! empty($duplicate->{$field})) {
                        $merged[$field] = $duplicate->{$field};
                    }
                }DB::table('publications')->where('id', $duplicate->id)->delete();
            }
            $merged['doi'] = $this->doi($merged['doi'] ?? null);
            $merged['pmid'] = $this->pmid($merged['pmid'] ?? null);
            $merged['identity_key'] = $key;
            DB::table('publications')->where('id', $keeper->id)->update(collect($merged)->only(['authors', 'journal', 'published_at', 'doi', 'pmid', 'external_url', 'abstract', 'keywords', 'identity_key'])->all());
        }
    }

    private function linkPublishedClaims(): void
    {
        foreach (DB::table('research_claims')->where('status', 'published')->where('target_type', 'publication')->get() as $claim) {
            $payload = json_decode($claim->target_payload ?: '[]', true) ?: [];
            $identity = $this->identity((object) $payload);
            $record = DB::table('publications')->where('identity_key', $identity)->first();
            if (! $record) {
                continue;
            }DB::table('publications')->where('id', $record->id)->whereNull('source_claim_id')->update(['source_claim_id' => $claim->id, 'verification_status' => 'published']);
            DB::table('research_claims')->where('id', $claim->id)->update(['published_record_type' => 'publication', 'published_record_id' => $record->id]);
        }
    }

    private function identity(object|array $record): string
    {
        $record = (object) $record;
        $doi = $this->doi($record->doi ?? null);
        if ($doi) {
            return 'doi:'.$doi;
        }$pmid = $this->pmid($record->pmid ?? null);
        if ($pmid) {
            return 'pmid:'.$pmid;
        }$title = mb_strtolower(trim(preg_replace('/\s+/u', ' ', (string) ($record->title ?? ''))));

        return 'title:'.hash('sha256', $title);
    }

    private function doi(?string $value): ?string
    {
        $value = mb_strtolower(trim((string) $value));
        $value = preg_replace('#^https?://(?:dx\.)?doi\.org/#', '', $value);

        return $value !== '' ? $value : null;
    }

    private function pmid(?string $value): ?string
    {
        $value = preg_replace('/\D/', '', (string) $value);

        return $value !== '' ? $value : null;
    }
};
