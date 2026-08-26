<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LogoutController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($request->bearerToken()) {
            $request->user()?->currentAccessToken()?->delete();
        }
        if ($request->hasSession()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }
        AuditLog::create(['actor_id' => $user?->id, 'action' => 'identity.signed_out', 'subject_type' => $user ? $user::class : null, 'subject_id' => $user?->id, 'ip_address' => $request->ip()]);

        return response()->json(['message' => 'Signed out successfully.']);
    }
}
