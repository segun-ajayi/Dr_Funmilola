<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $credentials = $request->validate(['email' => ['required', 'email'], 'password' => ['required', 'string']]);
        $credentials['email'] = mb_strtolower($credentials['email']);
        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages(['email' => 'The provided credentials are incorrect.']);
        }
        if (! $request->user()->is_active || ! $request->user()->account_claimed_at) {
            Auth::logout();
            throw ValidationException::withMessages(['email' => 'This account is unavailable. Please contact the practice.']);
        }
        $request->session()->regenerate();
        $request->user()->forceFill(['last_login_at' => now()])->save();
        AuditLog::create(['actor_id' => $request->user()->id, 'action' => 'identity.signed_in', 'subject_type' => User::class, 'subject_id' => $request->user()->id, 'ip_address' => $request->ip()]);

        return response()->json(['message' => 'Signed in successfully.', 'user' => $request->user()->only('id', 'name', 'email', 'phone', 'role')]);
    }
}
