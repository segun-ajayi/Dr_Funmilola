<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SessionRevocationService
{
    public function sessions(User $user): Collection
    {
        return DB::table(config('session.table', 'sessions'))
            ->where('user_id', $user->id)
            ->orderByDesc('last_activity')
            ->get();
    }

    public function revokeAll(User $user, ?string $exceptSessionId = null): int
    {
        $query = DB::table(config('session.table', 'sessions'))->where('user_id', $user->id);
        if ($exceptSessionId) {
            $query->where('id', '!=', $exceptSessionId);
        }

        return $query->delete();
    }

    public function findOwnedByReference(User $user, string $reference): ?object
    {
        return $this->sessions($user)->first(fn ($session) => hash_equals(hash('sha256', $session->id), $reference));
    }

    public function revoke(string $sessionId): void
    {
        DB::table(config('session.table', 'sessions'))->where('id', $sessionId)->delete();
    }
}
