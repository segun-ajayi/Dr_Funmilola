<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class MobileTokenController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate(['email' => ['required', 'email'], 'password' => ['required'], 'device_name' => ['required', 'string', 'max:100']]);
        $user = User::where('email', mb_strtolower($data['email']))->first();
        if (! $user || ! $user->is_active || ! $user->account_claimed_at || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages(['email' => 'The provided credentials are incorrect.']);
        }
        $abilities = $user->isStaff() ? ['profile:read', 'appointments:manage', 'patients:read'] : ['profile:read', 'profile:update', 'appointments:self'];
        $token = $user->createToken($data['device_name'], $abilities, now()->addDays(30));
        AuditLog::create(['actor_id' => $user->id, 'action' => 'identity.mobile_token_created', 'subject_type' => User::class, 'subject_id' => $user->id, 'metadata' => ['device_name' => $data['device_name']], 'ip_address' => $request->ip()]);

        return response()->json(['token' => $token->plainTextToken, 'token_type' => 'Bearer', 'expires_at' => now()->addDays(30)->toIso8601String(), 'abilities' => $abilities]);
    }
}
