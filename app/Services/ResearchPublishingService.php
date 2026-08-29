<?php

namespace App\Services;

use App\Models\Achievement;
use App\Models\AuditLog;
use App\Models\CareerEntry;
use App\Models\Publication;
use App\Models\ResearchClaim;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ResearchPublishingService
{
    public function review(ResearchClaim $claim, User $actor, string $decision): ResearchClaim
    {
        return DB::transaction(function () use ($claim, $actor, $decision) {
            $claim = ResearchClaim::lockForUpdate()->findOrFail($claim->id);
            if ($claim->status !== 'pending_review') {
                throw ValidationException::withMessages(['status' => 'Only a pending claim can be reviewed. Published material requires the dedicated retraction workflow.']);
            }

            $claim->update(['status' => $decision, 'reviewed_by' => $actor->id, 'reviewed_at' => now()]);
            $this->audit($actor, $claim, 'research_claim.reviewed', ['from' => 'pending_review', 'to' => $decision]);

            return $claim;
        });
    }

    public function publish(ResearchClaim $claim, User $actor): array
    {
        return DB::transaction(function () use ($claim, $actor) {
            $claim = ResearchClaim::lockForUpdate()->findOrFail($claim->id);
            if ($claim->status !== 'verified') {
                throw ValidationException::withMessages(['status' => 'Only a verified claim can be published.']);
            }

            $record = $this->publishTarget($claim);
            $claim->update([
                'status' => 'published',
                'published_record_type' => $claim->target_type,
                'published_record_id' => $record->id,
                'retracted_at' => null,
                'retracted_by' => null,
                'retracted_reason' => null,
            ]);
            $this->audit($actor, $claim, 'research_claim.published', ['target_type' => $claim->target_type, 'record_id' => $record->id]);

            return ['claim' => $claim, 'record' => $record];
        });
    }

    public function retract(ResearchClaim $claim, User $actor, string $reason): ResearchClaim
    {
        return DB::transaction(function () use ($claim, $actor, $reason) {
            $claim = ResearchClaim::lockForUpdate()->findOrFail($claim->id);
            if ($claim->status !== 'published') {
                throw ValidationException::withMessages(['status' => 'Only published material can be retracted.']);
            }

            $record = $this->publishedRecord($claim);
            if ($record instanceof Publication) {
                $record->update(['verification_status' => 'retracted']);
            } elseif ($record) {
                $record->update(['verification_status' => 'retracted', 'is_published' => false]);
            }

            $claim->update(['status' => 'retracted', 'retracted_at' => now(), 'retracted_by' => $actor->id, 'retracted_reason' => $reason]);
            $this->audit($actor, $claim, 'research_claim.retracted', [
                'target_type' => $claim->published_record_type,
                'record_id' => $claim->published_record_id,
                'reason' => $reason,
            ]);

            return $claim;
        });
    }

    public function publicationIdentity(array $payload): string
    {
        $doi = $this->doi($payload['doi'] ?? null);
        if ($doi) {
            return 'doi:'.$doi;
        }

        $pmid = $this->pmid($payload['pmid'] ?? null);
        if ($pmid) {
            return 'pmid:'.$pmid;
        }

        $title = mb_strtolower(trim(preg_replace('/\s+/u', ' ', (string) ($payload['title'] ?? ''))));
        if ($title === '') {
            throw ValidationException::withMessages(['target_payload.title' => 'A publication title is required.']);
        }

        return 'title:'.hash('sha256', $title);
    }

    private function publishTarget(ResearchClaim $claim): Model
    {
        $payload = $claim->target_payload ?? [];

        return match ($claim->target_type) {
            'publication' => $this->publication($claim, $payload),
            'career' => $this->career($claim, $payload),
            'achievement' => $this->achievement($claim, $payload),
            default => throw ValidationException::withMessages(['target_type' => 'This claim has no supported publishing destination.']),
        };
    }

    private function publication(ResearchClaim $claim, array $payload): Publication
    {
        $payload = Validator::make($payload, [
            'title' => ['required', 'string', 'max:255'],
            'authors' => ['required', 'string', 'max:2000'],
            'journal' => ['required', 'string', 'max:255'],
            'published_at' => ['required', 'date'],
            'doi' => ['nullable', 'string', 'max:255'],
            'pmid' => ['nullable', 'string', 'max:40'],
            'category' => ['required', 'string', 'max:100'],
            'external_url' => ['required', 'url:http,https', 'max:2048'],
            'abstract' => ['nullable', 'string', 'max:10000'],
            'keywords' => ['nullable', 'array', 'max:30'],
            'keywords.*' => ['string', 'max:100'],
            'publication_type' => ['nullable', 'string', 'max:80'],
            'featured' => ['nullable', 'boolean'],
        ])->validate();
        $payload['doi'] = $this->doi($payload['doi'] ?? null);
        $payload['pmid'] = $this->pmid($payload['pmid'] ?? null);
        $identity = $this->publicationIdentity($payload);

        if ($this->otherClaim(Publication::where('identity_key', $identity), $claim)->exists()) {
            throw ValidationException::withMessages(['target_payload' => 'A different claim already owns this DOI, PMID or normalized title.']);
        }
        if ($payload['doi'] && $this->otherClaim(Publication::where('doi', $payload['doi']), $claim)->exists()) {
            throw ValidationException::withMessages(['target_payload.doi' => 'This DOI already belongs to another publication.']);
        }
        if ($payload['pmid'] && $this->otherClaim(Publication::where('pmid', $payload['pmid']), $claim)->exists()) {
            throw ValidationException::withMessages(['target_payload.pmid' => 'This PMID already belongs to another publication.']);
        }

        return Publication::updateOrCreate(
            ['source_claim_id' => $claim->id],
            array_merge($payload, ['identity_key' => $identity, 'verification_status' => 'published'])
        );
    }

    private function career(ResearchClaim $claim, array $payload): CareerEntry
    {
        $payload = Validator::make($payload, [
            'year_label' => ['required', 'string', 'max:50'],
            'institution' => ['required', 'string', 'max:255'],
            'position' => ['required', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'source_url' => ['required', 'url:http,https', 'max:2048'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
        ])->validate();

        return CareerEntry::updateOrCreate(
            ['source_claim_id' => $claim->id],
            array_merge($payload, ['verification_status' => 'published', 'is_published' => true])
        );
    }

    private function achievement(ResearchClaim $claim, array $payload): Achievement
    {
        $payload = Validator::make($payload, [
            'title' => ['required', 'string', 'max:255'],
            'year_label' => ['nullable', 'string', 'max:50'],
            'organization' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'category' => ['required', 'string', 'max:100'],
            'source_url' => ['required', 'url:http,https', 'max:2048'],
        ])->validate();

        return Achievement::updateOrCreate(
            ['source_claim_id' => $claim->id],
            array_merge($payload, ['verification_status' => 'published', 'is_published' => true])
        );
    }

    private function otherClaim(Builder $query, ResearchClaim $claim): Builder
    {
        return $query->where(function (Builder $ownership) use ($claim) {
            $ownership->whereNull('source_claim_id')->orWhere('source_claim_id', '!=', $claim->id);
        });
    }

    private function publishedRecord(ResearchClaim $claim): ?Model
    {
        return match ($claim->published_record_type) {
            'publication' => Publication::find($claim->published_record_id),
            'career' => CareerEntry::find($claim->published_record_id),
            'achievement' => Achievement::find($claim->published_record_id),
            default => null,
        };
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

    private function audit(User $actor, ResearchClaim $claim, string $action, array $metadata): void
    {
        AuditLog::create([
            'actor_id' => $actor->id,
            'action' => $action,
            'subject_type' => ResearchClaim::class,
            'subject_id' => $claim->id,
            'metadata' => $metadata,
        ]);
    }
}
