<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class RegisterController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'password' => ['required', 'confirmed', Password::min(10)->mixedCase()->numbers()->symbols()->uncompromised()],
        ]);

        $data['email'] = mb_strtolower($data['email']);
        $existing = User::where('email', $data['email'])->first();
        if ($existing?->account_claimed_at) {
            throw ValidationException::withMessages(['email' => 'An account already exists for this email address.']);
        }
        $user = $existing ?? new User(['email' => $data['email'], 'role' => UserRole::Patient]);
        $user->fill(['name' => $data['name'], 'phone' => $data['phone'], 'password' => $data['password'], 'account_claimed_at' => now()])->save();
        event(new Registered($user));
        Auth::login($user);
        $request->session()->regenerate();
        AuditLog::create(['actor_id' => $user->id, 'action' => 'identity.registered', 'subject_type' => User::class, 'subject_id' => $user->id, 'ip_address' => $request->ip()]);

        return response()->json(['message' => 'Account created. Please check your email to verify your address.', 'user' => $user->only('id', 'name', 'email', 'phone')], 201);
    }
}
