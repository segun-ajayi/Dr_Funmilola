<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\SessionRevocationService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;

class ResetPasswordController extends Controller
{
    public function __invoke(Request $request, SessionRevocationService $sessions): JsonResponse
    {
        $data = $request->validate(['token' => ['required'], 'email' => ['required', 'email'], 'password' => ['required', 'confirmed', PasswordRule::min(10)->mixedCase()->numbers()->symbols()->uncompromised()]]);
        $status = Password::reset($data, function (User $user, string $password) use ($request, $sessions) {
            $user->forceFill(['password' => Hash::make($password), 'remember_token' => Str::random(60), 'account_claimed_at' => $user->account_claimed_at ?? now()])->save();
            $user->tokens()->delete();
            $sessions->revokeAll($user);
            AuditLog::create(['actor_id' => $user->id, 'action' => 'identity.password_reset', 'subject_type' => User::class, 'subject_id' => $user->id, 'ip_address' => $request->ip()]);
            event(new PasswordReset($user));
        });
        if ($status !== Password::PasswordReset) {
            throw ValidationException::withMessages(['email' => __($status)]);
        }

        return response()->json(['message' => 'Your password has been reset. You can now sign in.']);
    }
}
