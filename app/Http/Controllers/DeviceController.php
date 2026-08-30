<?php

namespace App\Http\Controllers;

use App\Services\AuditService;
use App\Services\SessionRevocationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $currentId = $request->user()->currentAccessToken()?->id;
        $tokens = $request->user()->tokens()->latest()->get(['id', 'name', 'abilities', 'last_used_at', 'expires_at', 'created_at'])
            ->each(fn ($token) => $token->setAttribute('current', $token->id === $currentId));

        return response()->json(['data' => $tokens]);
    }

    public function destroy(Request $request, int $token, AuditService $audit): JsonResponse
    {
        $record = $request->user()->tokens()->findOrFail($token);
        abort_if($record->id === $request->user()->currentAccessToken()?->id, 422, 'Use sign out to revoke access for this device.');
        $name = $record->name;
        $record->delete();
        $audit->record($request->user(), 'identity.mobile_token_revoked', $request->user(), ['device_name' => $name], $request);

        return response()->json(['message' => 'Device access revoked.']);
    }

    public function sessions(Request $request, SessionRevocationService $sessions): JsonResponse
    {
        $current = $request->hasSession() ? $request->session()->getId() : null;
        $data = $sessions->sessions($request->user())->map(fn ($session) => [
            'reference' => hash('sha256', $session->id),
            'current' => $current && hash_equals($current, $session->id),
            'device' => $this->deviceLabel($session->user_agent),
            'ip_address' => $this->maskedIp($session->ip_address),
            'last_active_at' => now()->setTimestamp($session->last_activity)->toIso8601String(),
        ])->values();

        return response()->json(['data' => $data, 'session_store' => config('session.driver')]);
    }

    public function destroySession(Request $request, string $reference, SessionRevocationService $sessions, AuditService $audit): JsonResponse
    {
        abort_unless((bool) preg_match('/^[a-f0-9]{64}$/', $reference), 404);
        $session = $sessions->findOwnedByReference($request->user(), $reference);
        abort_unless($session, 404);
        abort_if($request->hasSession() && hash_equals($request->session()->getId(), $session->id), 422, 'Use sign out to close your current browser session.');
        $sessions->revoke($session->id);
        $audit->record($request->user(), 'identity.web_session_revoked', $request->user(), ['session_reference' => substr($reference, 0, 12)], $request);

        return response()->json(['message' => 'Browser session revoked.']);
    }

    private function deviceLabel(?string $userAgent): string
    {
        if (! $userAgent) {
            return 'Unknown browser';
        }
        foreach (['Edg' => 'Microsoft Edge', 'Firefox' => 'Firefox', 'Chrome' => 'Chrome', 'Safari' => 'Safari'] as $needle => $label) {
            if (str_contains($userAgent, $needle)) {
                return $label;
            }
        }

        return 'Web browser';
    }

    private function maskedIp(?string $ip): ?string
    {
        if (! $ip) {
            return null;
        }
        if (str_contains($ip, ':')) {
            return implode(':', array_slice(explode(':', $ip), 0, 3)).':…';
        }
        $parts = explode('.', $ip);

        return count($parts) === 4 ? "{$parts[0]}.{$parts[1]}.{$parts[2]}.x" : null;
    }
}
