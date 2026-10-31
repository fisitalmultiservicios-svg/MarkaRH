<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use PragmaRX\Google2FA\Google2FA;

class MfaService
{
    public function __construct(private Google2FA $totp) {}

    public function secret(): string
    {
        return $this->totp->generateSecretKey(32);
    }

    public function qr(User $user, #[\SensitiveParameter] string $secret): string
    {
        $uri = $this->totp->getQRCodeUrl('MarkaRH', $user->email, $secret);
        $writer = new Writer(new ImageRenderer(new RendererStyle(240, 4), new SvgImageBackEnd));

        return 'data:image/svg+xml;base64,'.base64_encode($writer->writeString($uri));
    }

    /** @return array{user:User, codes:list<string>} */
    public function enable(User $identity, #[\SensitiveParameter] string $secret, #[\SensitiveParameter] string $code): array
    {
        return DB::transaction(function () use ($identity, $secret, $code) {
            $user = $this->locked($identity);
            if ($user->mfa_confirmed_at !== null) {
                throw ValidationException::withMessages(['totp_code' => 'MFA ya está configurado. Vuelve a ingresar.']);
            }
            $step = $this->step($secret, $code, 0);
            $codes = $this->newCodes();
            $user->forceFill(['mfa_secret' => $secret, 'mfa_recovery_codes' => $this->hashes($codes), 'mfa_confirmed_at' => now(), 'mfa_last_step' => $step, 'mfa_version' => $user->mfa_version + 1])->save();
            AuditLog::record($user, 'mfa.enabled', $user->id);
            AuditLog::record($user, 'auth.login');

            return ['user' => $user, 'codes' => $codes];
        });
    }

    public function challenge(User $identity, string $method, #[\SensitiveParameter] string $code): User
    {
        return DB::transaction(function () use ($identity, $method, $code) {
            $user = $this->locked($identity);
            if ($user->mfa_confirmed_at === null || $user->mfa_secret === null) {
                $this->invalid('totp_code');
            }
            if ($method === 'recovery') {
                $candidate = hash('sha256', strtolower(str_replace('-', '', $code)));
                $codes = $user->mfa_recovery_codes ?? [];
                $found = null;
                foreach ($codes as $index => $hash) {
                    if (hash_equals($hash, $candidate)) {
                        $found = $index;
                    }
                }
                if ($found === null) {
                    $this->invalid('recovery_code');
                }
                unset($codes[$found]);
                $user->forceFill(['mfa_recovery_codes' => array_values($codes)])->save();
                AuditLog::record($user, 'mfa.recovery_used', $user->id);
            } else {
                $user->forceFill(['mfa_last_step' => $this->step($user->mfa_secret, $code, $user->mfa_last_step ?? 0)])->save();
                AuditLog::record($user, 'mfa.challenge_passed', $user->id);
            }
            AuditLog::record($user, 'auth.login');

            return $user;
        });
    }

    /** @return array{user:User, codes:list<string>} */
    public function regenerate(User $identity, #[\SensitiveParameter] string $password, #[\SensitiveParameter] string $code, bool $recentRecovery): array
    {
        return DB::transaction(function () use ($identity, $password, $code, $recentRecovery) {
            $user = $this->locked($identity);
            $this->confirmAction($user, $password, $code, $recentRecovery);
            $codes = $this->newCodes();
            $user->forceFill(['mfa_recovery_codes' => $this->hashes($codes), 'mfa_version' => $user->mfa_version + 1])->save();
            AuditLog::record($user, 'mfa.recovery_rotated', $user->id);

            return ['user' => $user, 'codes' => $codes];
        });
    }

    public function reset(User $identity, #[\SensitiveParameter] string $password, #[\SensitiveParameter] string $code, bool $recentRecovery): User
    {
        return DB::transaction(function () use ($identity, $password, $code, $recentRecovery) {
            $user = $this->locked($identity);
            $this->confirmAction($user, $password, $code, $recentRecovery);
            $user->forceFill(['mfa_secret' => null, 'mfa_recovery_codes' => null, 'mfa_confirmed_at' => null, 'mfa_last_step' => null, 'mfa_version' => $user->mfa_version + 1])->save();
            AuditLog::record($user, 'mfa.authenticator_reset', $user->id);

            return $user;
        });
    }

    private function locked(User $identity): User
    {
        $user = User::whereKey($identity->id)->lockForUpdate()->firstOrFail();
        abort_unless($user->role === 'master', 403);
        if ($user->mfa_version !== $identity->mfa_version || ! hash_equals($user->getAuthPassword(), $identity->getAuthPassword())) {
            throw ValidationException::withMessages(['totp_code' => 'Tu sesión de seguridad cambió. Vuelve a ingresar.']);
        }

        return $user;
    }

    private function confirmAction(User $user, #[\SensitiveParameter] string $password, #[\SensitiveParameter] string $code, bool $recentRecovery): void
    {
        if (! Hash::check($password, $user->getAuthPassword())) {
            throw ValidationException::withMessages(['current_password' => 'No pudimos confirmar tu contraseña.']);
        }
        if ($user->mfa_confirmed_at === null || $user->mfa_secret === null) {
            $this->invalid('totp_code');
        }
        if (! $recentRecovery) {
            $user->forceFill(['mfa_last_step' => $this->step($user->mfa_secret, $code, $user->mfa_last_step ?? 0)])->save();
        }
    }

    private function step(#[\SensitiveParameter] string $secret, #[\SensitiveParameter] string $code, int $lastStep): int
    {
        $step = $this->totp->verifyKeyNewer($secret, $code, $lastStep, 1, intdiv(now()->timestamp, 30));
        if ($step === false) {
            $this->invalid('totp_code');
        }

        return (int) $step;
    }

    /** @return list<string> */
    private function newCodes(): array
    {
        $codes = [];
        for ($i = 0; $i < 10; $i++) {
            $codes[] = implode('-', str_split(bin2hex(random_bytes(16)), 8));
        }

        return $codes;
    }

    /** @param list<string> $codes
     * @return list<string>
     */
    private function hashes(#[\SensitiveParameter] array $codes): array
    {
        return array_map(fn (string $code): string => hash('sha256', str_replace('-', '', $code)), $codes);
    }

    private function invalid(string $field): never
    {
        throw ValidationException::withMessages([$field => 'El código no es válido, ya se usó o venció.']);
    }
}
