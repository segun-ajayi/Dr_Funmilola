<?php

namespace App\Http\Controllers\Staff;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditService;
use App\Services\SessionRevocationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AccountController extends Controller
{
    private const AUDIT_FIELDS = ['name', 'email', 'phone', 'role', 'is_active', 'email_verified_at', 'account_claimed_at'];

    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'role' => ['nullable', Rule::enum(UserRole::class)],
            'active' => ['nullable', 'boolean'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);
        $actor = $request->user();
        $query = User::query()->select(self::AUDIT_FIELDS)->addSelect(['id', 'created_at', 'last_login_at']);
        if ($actor->role !== UserRole::PowerAdmin) {
            $query->where('role', UserRole::Patient);
        }
        $query->when($data['q'] ?? null, function ($query, $term) {
            $term = trim($term);
            $query->where(fn ($inner) => $inner->where('name', 'like', "%{$term}%")->orWhere('email', 'like', "%{$term}%")->orWhere('phone', 'like', "%{$term}%"));
        })->when($data['role'] ?? null, fn ($query, $role) => $query->where('role', $role))
            ->when(array_key_exists('active', $data), fn ($query) => $query->where('is_active', $data['active']))
            ->latest('id');

        return response()->json($query->paginate(25));
    }

    public function store(Request $request, AuditService $audit): JsonResponse
    {
        $actor = $request->user();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'role' => ['required', Rule::enum(UserRole::class)],
            'current_password' => ['nullable', 'string'],
        ]);
        $role = UserRole::from($data['role']);
        abort_if($actor->role !== UserRole::PowerAdmin && $role !== UserRole::Patient, 403, 'Only a Power Admin can invite staff accounts.');
        if ($role !== UserRole::Patient) {
            $this->requirePassword($actor, $data['current_password'] ?? null);
        }

        $user = DB::transaction(function () use ($data, $role, $actor, $audit, $request) {
            $user = User::create([
                'name' => trim($data['name']),
                'email' => mb_strtolower($data['email']),
                'phone' => $data['phone'] ?: null,
                'password' => Str::password(40),
                'role' => $role,
                'is_active' => true,
                'account_claimed_at' => null,
                'email_verified_at' => null,
            ]);
            $audit->record($actor, 'identity.account_invited', $user, ['after' => $this->snapshot($user)], $request);

            return $user;
        });
        Password::sendResetLink(['email' => $user->email]);

        return response()->json(['message' => 'Account invitation created and a secure claim link was requested.', 'data' => $this->snapshot($user) + ['id' => $user->id]], 201);
    }

    public function update(Request $request, User $account, AuditService $audit, SessionRevocationService $sessions): JsonResponse
    {
        $actor = $request->user();
        $this->authorizeTarget($actor, $account);
        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:120'],
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($account)],
            'phone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'role' => ['sometimes', Rule::enum(UserRole::class)],
            'is_active' => ['sometimes', 'boolean'],
            'current_password' => ['nullable', 'string'],
        ]);
        $newRole = isset($data['role']) ? UserRole::from($data['role']) : $account->role;
        $newActive = $data['is_active'] ?? $account->is_active;
        $sensitive = $newRole !== $account->role || $newActive !== $account->is_active;
        if ($sensitive) {
            $this->requirePassword($actor, $data['current_password'] ?? null);
        }
        abort_if($actor->role !== UserRole::PowerAdmin && $newRole !== UserRole::Patient, 403, 'Only a Power Admin can change account roles.');
        abort_if($account->is($actor) && ($newRole !== $account->role || ! $newActive), 422, 'You cannot demote or deactivate your own account.');
        if ($account->role === UserRole::PowerAdmin && ($newRole !== UserRole::PowerAdmin || ! $newActive)) {
            $otherPowerAdmins = User::query()->where('role', UserRole::PowerAdmin)->where('is_active', true)->whereKeyNot($account->id)->exists();
            abort_unless($otherPowerAdmins, 422, 'The last active Power Admin cannot be demoted or deactivated.');
        }

        $before = $this->snapshot($account);
        DB::transaction(function () use ($data, $newRole, $newActive, $account, $actor, $before, $audit, $sessions, $request) {
            $updates = array_intersect_key($data, array_flip(['name', 'email', 'phone']));
            if (array_key_exists('email', $updates)) {
                $updates['email'] = mb_strtolower($updates['email']);
                if ($updates['email'] !== $account->email) {
                    $updates['email_verified_at'] = null;
                }
            }
            $updates['role'] = $newRole;
            $updates['is_active'] = $newActive;
            $account->update($updates);
            if (! $newActive) {
                $account->tokens()->delete();
                $sessions->revokeAll($account);
            }
            $account->refresh();
            $audit->record($actor, 'identity.account_updated', $account, $audit->changes($before, $this->snapshot($account), self::AUDIT_FIELDS), $request);
        });

        return response()->json(['message' => 'Account updated.', 'data' => $this->snapshot($account) + ['id' => $account->id]]);
    }

    private function authorizeTarget(User $actor, User $account): void
    {
        abort_if($actor->role !== UserRole::PowerAdmin && $account->role !== UserRole::Patient, 403, 'Only a Power Admin can manage staff accounts.');
    }

    private function requirePassword(User $actor, ?string $password): void
    {
        if (! $password || ! Hash::check($password, $actor->password)) {
            throw ValidationException::withMessages(['current_password' => ['Enter your current password to confirm this sensitive change.']]);
        }
    }

    private function snapshot(User $user): array
    {
        return [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'role' => $user->role->value,
            'is_active' => $user->is_active,
            'email_verified_at' => $user->email_verified_at?->toIso8601String(),
            'account_claimed_at' => $user->account_claimed_at?->toIso8601String(),
        ];
    }
}
