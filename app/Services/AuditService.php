<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AuditService
{
    private const SENSITIVE_KEYS = ['password', 'password_confirmation', 'remember_token', 'token', 'secret', 'body', 'content', 'payload'];

    public function record(?User $actor, string $action, ?Model $subject = null, array $metadata = [], ?Request $request = null): AuditLog
    {
        return AuditLog::create([
            'actor_id' => $actor?->id,
            'action' => $action,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'metadata' => $this->safe($metadata),
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);
    }

    public function changes(array $before, array $after, array $allowed): array
    {
        $old = array_intersect_key($before, array_flip($allowed));
        $new = array_intersect_key($after, array_flip($allowed));
        $changed = array_keys(array_filter($new, fn ($value, $key) => ($old[$key] ?? null) !== $value, ARRAY_FILTER_USE_BOTH));

        return [
            'fields' => $changed,
            'before' => array_intersect_key($old, array_flip($changed)),
            'after' => array_intersect_key($new, array_flip($changed)),
        ];
    }

    public function safe(array $metadata): array
    {
        return collect($metadata)
            ->reject(fn ($value, $key) => in_array(mb_strtolower((string) $key), self::SENSITIVE_KEYS, true) || preg_match('/password|token|secret|body|content|payload/i', (string) $key))
            ->map(fn ($value) => is_array($value) ? $this->safe($value) : $value)
            ->all();
    }
}
