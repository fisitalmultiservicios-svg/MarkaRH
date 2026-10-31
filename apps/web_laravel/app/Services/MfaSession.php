<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;

class MfaSession
{
    public static function begin(Request $request, User $user): void
    {
        $request->session()->forget('mfa');
        $request->session()->regenerate();
        $request->session()->put('mfa.pending', self::identity($user) + ['at' => now()->timestamp]);
    }

    public static function pendingIsFresh(Request $request, User $user): bool
    {
        return self::matches($request->session()->get('mfa.pending', []), $user, 600);
    }

    public static function verified(Request $request, User $user): bool
    {
        return $user->mfa_confirmed_at !== null && $user->mfa_secret !== null
            && self::matches($request->session()->get('mfa.verified', []), $user, 28800);
    }

    public static function recentRecovery(Request $request, User $user): bool
    {
        $at = $request->session()->get('mfa.recovery_at');

        return self::verified($request, $user) && is_int($at) && self::fresh($at, 300);
    }

    public static function complete(Request $request, User $user, ?int $recoveryAt = null): void
    {
        $request->session()->forget('mfa');
        $request->session()->regenerate();
        $request->session()->put('mfa.verified', self::identity($user) + ['at' => now()->timestamp]);
        if ($recoveryAt !== null) {
            $request->session()->put('mfa.recovery_at', $recoveryAt);
        }
    }

    /** @return array{user:int, version:int, password:string} */
    private static function identity(User $user): array
    {
        return ['user' => $user->id, 'version' => $user->mfa_version, 'password' => hash('sha256', $user->getAuthPassword())];
    }

    private static function matches(mixed $state, User $user, int $seconds): bool
    {
        if (! is_array($state)) {
            return false;
        }

        return ($state['user'] ?? null) === $user->id
            && ($state['version'] ?? null) === $user->mfa_version
            && is_string($state['password'] ?? null)
            && hash_equals(hash('sha256', $user->getAuthPassword()), $state['password'])
            && is_int($state['at'] ?? null) && self::fresh($state['at'], $seconds);
    }

    private static function fresh(int $at, int $seconds): bool
    {
        $age = now()->timestamp - $at;

        return $age >= 0 && $age <= $seconds;
    }
}
