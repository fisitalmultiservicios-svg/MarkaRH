<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class MfaTest extends TestCase
{
    use LazilyRefreshDatabase;

    private const PASSWORD = 'MfaTestingPassword42!';

    private const SECRET = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';

    private const RECOVERY = '00112233-44556677-8899aabb-ccddeeff';

    private function master(bool $configured = false): User
    {
        $factory = User::factory()->master();

        return ($configured ? $factory->withMfa() : $factory)->create(['password' => self::PASSWORD]);
    }

    private function passwordLogin(User $user): void
    {
        $this->post('/acceso', ['email' => $user->email, 'password' => self::PASSWORD])->assertRedirect($user->mfa_confirmed_at ? '/seguridad/verificar' : '/seguridad/configurar');
    }

    private function code(string $secret = self::SECRET, int $offset = 0): string
    {
        return app(Google2FA::class)->oathTotp($secret, intdiv(now()->timestamp, 30) + $offset);
    }

    private function wrongCode(string $secret = self::SECRET): string
    {
        $valid = [$this->code($secret, -1), $this->code($secret), $this->code($secret, 1)];
        foreach (['000000', '111111', '222222', '333333'] as $code) {
            if (! in_array($code, $valid, true)) {
                return $code;
            }
        }
        throw new \LogicException('A test must choose an invalid code.');
    }

    private function verify(string $code, string $method = 'totp'): TestResponse
    {
        return $this->post('/seguridad/verificar', ['method' => $method, $method === 'totp' ? 'totp_code' : 'recovery_code' => $code]);
    }

    public function test_password_only_master_cannot_read_or_write_master_resources(): void
    {
        $master = $this->master();
        $this->passwordLogin($master);
        $this->get('/maestro')->assertRedirect('/seguridad/configurar');
        $this->get('/maestro/consultas')->assertRedirect('/seguridad/configurar');
        $this->post('/maestro/empresas', ['name' => 'Must not be created'])->assertRedirect('/seguridad/configurar');
        $this->assertDatabaseCount('tenants', 0);
        $this->assertDatabaseMissing('audit_logs', ['action' => 'auth.login']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'auth.password_verified', 'user_id' => $master->id]);
    }

    public function test_setup_has_local_qr_and_does_not_enable_without_valid_code(): void
    {
        $this->freezeTime();
        $master = $this->master();
        $this->passwordLogin($master);
        $response = $this->get('/seguridad/configurar')->assertOk()->assertSee('data:image/svg+xml;base64,', false);
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $secret = session('mfa.enrollment_secret');
        $this->get('/seguridad/configurar')->assertOk();
        $this->assertSame($secret, session('mfa.enrollment_secret'));
        $this->post('/seguridad/configurar', ['totp_code' => $this->wrongCode($secret)])->assertSessionHasErrors('totp_code');
        $this->assertNull($master->fresh()->mfa_confirmed_at);
        $this->assertNull($master->fresh()->mfa_secret);
        $this->assertNull(session('_old_input.totp_code'));
        $this->get('/maestro')->assertRedirect('/seguridad/configurar');
    }

    public function test_enrollment_encrypts_secrets_and_shows_ten_recovery_codes_once(): void
    {
        $this->freezeTime();
        $master = $this->master();
        $this->passwordLogin($master);
        $this->get('/seguridad/configurar')->assertOk();
        $secret = session('mfa.enrollment_secret');
        $this->post('/seguridad/configurar', ['totp_code' => $this->code($secret)])->assertRedirect('/seguridad/respaldo');
        $codes = session('new_recovery_codes');
        $this->assertCount(10, $codes);
        $this->assertCount(10, array_unique($codes));
        $fresh = $master->fresh();
        $this->assertSame($secret, $fresh->mfa_secret);
        $this->assertNotNull($fresh->mfa_confirmed_at);
        $this->assertSame(1, $fresh->mfa_version);
        $raw = DB::table('users')->where('id', $master->id)->first();
        $this->assertNotSame($secret, $raw->mfa_secret);
        $this->assertStringNotContainsString($secret, $fresh->toJson());
        $this->assertStringNotContainsString('mfa_secret', $fresh->toJson());
        foreach ($codes as $code) {
            $this->assertMatchesRegularExpression('/^[0-9a-f]{8}(?:-[0-9a-f]{8}){3}$/', $code);
            $this->assertStringNotContainsString($code, $raw->mfa_recovery_codes);
            $this->assertContains(hash('sha256', str_replace('-', '', $code)), $fresh->mfa_recovery_codes);
        }
        $this->get('/seguridad/respaldo')->assertOk()->assertSee($codes[0]);
        $this->get('/seguridad/respaldo')->assertOk()->assertDontSee($codes[0]);
        $this->get('/maestro')->assertOk();
        $this->assertNull(session('mfa.enrollment_secret'));
        $this->assertDatabaseHas('audit_logs', ['action' => 'mfa.enabled', 'user_id' => $master->id]);
    }

    public function test_configured_master_requires_challenge_and_cannot_replay_totp(): void
    {
        $this->freezeTime();
        $master = $this->master(true);
        $this->passwordLogin($master);
        $this->get('/maestro')->assertRedirect('/seguridad/verificar');
        $code = $this->code();
        $this->verify($code)->assertRedirect('/maestro');
        $this->get('/maestro')->assertOk();
        $this->post('/salir')->assertRedirect('/acceso');
        $this->passwordLogin($master->fresh());
        $this->verify($code)->assertSessionHasErrors('totp_code');
        $this->get('/maestro')->assertRedirect('/seguridad/verificar');
        $this->travel(31)->seconds();
        $this->verify($this->code())->assertRedirect('/maestro');
    }

    public static function otpWindows(): array
    {
        return ['expired' => [-2, false], 'previous step' => [-1, true], 'current step' => [0, true], 'next step' => [1, true], 'too far ahead' => [2, false]];
    }

    #[DataProvider('otpWindows')]
    public function test_totp_clock_window_is_limited_to_one_step(int $offset, bool $accepted): void
    {
        $this->freezeTime();
        $master = $this->master(true);
        $this->passwordLogin($master);
        $response = $this->verify($this->code(self::SECRET, $offset));
        if ($accepted) {
            $response->assertRedirect('/maestro');
            $this->get('/maestro')->assertOk();
        } else {
            $response->assertSessionHasErrors('totp_code');
            $this->get('/maestro')->assertRedirect('/seguridad/verificar');
        }
    }

    public function test_rfc6238_reference_vector_is_accepted(): void
    {
        $this->travelTo(CarbonImmutable::createFromTimestampUTC(59));
        $master = $this->master(true);
        $this->passwordLogin($master);
        $this->verify('287082')->assertRedirect('/maestro');
        $this->assertSame(1, $master->fresh()->mfa_last_step);
    }

    public function test_initial_setup_code_cannot_be_reused_for_login(): void
    {
        $this->freezeTime();
        $master = $this->master();
        $this->passwordLogin($master);
        $this->get('/seguridad/configurar');
        $secret = session('mfa.enrollment_secret');
        $code = $this->code($secret);
        $this->post('/seguridad/configurar', ['totp_code' => $code])->assertRedirect('/seguridad/respaldo');
        $this->post('/salir');
        $this->passwordLogin($master->fresh());
        $this->verify($code)->assertSessionHasErrors('totp_code');
    }

    public function test_recovery_code_is_consumed_once_without_disabling_mfa(): void
    {
        $this->freezeTime();
        $master = $this->master(true);
        $this->passwordLogin($master);
        $this->verify(strtoupper(self::RECOVERY), 'recovery')->assertRedirect('/maestro');
        $this->get('/maestro')->assertOk();
        $this->assertCount(0, $master->fresh()->mfa_recovery_codes);
        $this->assertNotNull($master->fresh()->mfa_confirmed_at);
        $this->post('/salir');
        $this->passwordLogin($master->fresh());
        $this->verify(self::RECOVERY, 'recovery')->assertSessionHasErrors('recovery_code');
        $this->assertNull(session('_old_input.recovery_code'));
        $this->get('/maestro')->assertRedirect('/seguridad/verificar');
        $this->assertSame(1, AuditLog::where('action', 'mfa.recovery_used')->count());
    }

    public function test_combined_wrong_totp_and_recovery_attempts_are_limited_per_user(): void
    {
        $this->freezeTime();
        $master = $this->master(true);
        $this->passwordLogin($master);
        for ($i = 0; $i < 3; $i++) {
            $this->verify($this->wrongCode())->assertSessionHasErrors('totp_code');
        }
        for ($i = 0; $i < 2; $i++) {
            $this->verify(str_repeat('f', 32), 'recovery')->assertSessionHasErrors('recovery_code');
        }
        $this->verify($this->code())->assertSessionHasErrors(['totp_code' => 'Demasiados intentos. Espera cinco minutos antes de volver a intentarlo.']);
        $this->get('/maestro')->assertRedirect('/seguridad/verificar');
        $this->travel(301)->seconds();
        $this->verify($this->code())->assertRedirect('/maestro');
    }

    public function test_expired_first_factor_clears_pending_secret_and_requires_password_again(): void
    {
        $this->freezeTime();
        $master = $this->master();
        $this->passwordLogin($master);
        $this->get('/seguridad/configurar');
        $this->travel(601)->seconds();
        $this->get('/seguridad/configurar')->assertRedirect('/acceso');
        $this->assertGuest();
        $this->assertNull(session('mfa.enrollment_secret'));
        $this->assertNull($master->fresh()->mfa_secret);
    }

    public function test_authenticated_master_without_first_factor_state_cannot_enroll(): void
    {
        $master = $this->master();
        $this->actingAs($master)->get('/seguridad/configurar')->assertRedirect('/acceso');
        $this->assertGuest();
    }

    public function test_guests_and_company_admins_cannot_use_security_endpoints(): void
    {
        $this->get('/seguridad/configurar')->assertRedirect('/acceso');
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get('/seguridad/configurar')->assertForbidden();
        $this->get('/seguridad/verificar')->assertForbidden();
        $this->get('/seguridad')->assertForbidden();
        $this->post('/seguridad/configurar', ['totp_code' => '123456'])->assertForbidden();
        $this->post('/seguridad/respaldo/renovar', [])->assertForbidden();
    }

    public function test_regenerating_backup_requires_password_and_fresh_factor(): void
    {
        $this->freezeTime();
        $master = $this->master(true);
        $this->asVerifiedMaster($master);
        $oldCodes = $master->mfa_recovery_codes;
        $this->post('/seguridad/respaldo/renovar', ['current_password' => 'incorrect', 'totp_code' => $this->code()])->assertSessionHasErrors('current_password');
        $this->assertSame($oldCodes, $master->fresh()->mfa_recovery_codes);
        $this->assertNull(session('_old_input.current_password'));
        $this->post('/seguridad/respaldo/renovar', ['current_password' => self::PASSWORD, 'totp_code' => $this->wrongCode()])->assertSessionHasErrors('totp_code');
        $this->assertSame($oldCodes, $master->fresh()->mfa_recovery_codes);
        $this->post('/seguridad/respaldo/renovar', ['current_password' => self::PASSWORD, 'totp_code' => $this->code()])->assertRedirect('/seguridad/respaldo');
        $this->assertCount(10, session('new_recovery_codes'));
        $this->assertSame(2, $master->fresh()->mfa_version);
        $this->assertNotSame($oldCodes, $master->fresh()->mfa_recovery_codes);
        $this->get('/maestro')->assertOk();
        $this->post('/salir');
        $this->passwordLogin($master->fresh());
        $this->verify(self::RECOVERY, 'recovery')->assertSessionHasErrors('recovery_code');
    }

    public function test_security_version_and_password_changes_invalidate_existing_verifications(): void
    {
        $master = $this->master(true);
        $this->asVerifiedMaster($master);
        $oldState = session('mfa.verified');
        $master->forceFill(['mfa_version' => 2])->save();
        $this->actingAs($master->fresh())->withSession(['mfa.verified' => $oldState])->get('/maestro')->assertRedirect('/seguridad/verificar');
        $this->asVerifiedMaster($master->fresh());
        $master->forceFill(['password' => 'ChangedPassword42!'])->save();
        $this->actingAs($master->fresh())->get('/maestro')->assertRedirect('/seguridad/verificar');
    }

    public function test_verification_cannot_be_carried_to_another_account_or_beyond_eight_hours(): void
    {
        $this->freezeTime();
        $first = $this->master(true);
        $this->asVerifiedMaster($first);
        $other = $this->master(true);
        $this->actingAs($other)->get('/maestro')->assertRedirect('/seguridad/verificar');
        $this->asVerifiedMaster($first);
        $this->travel(28801)->seconds();
        $this->get('/maestro')->assertRedirect('/seguridad/verificar');
    }

    public function test_lost_authenticator_can_be_replaced_with_recent_recovery_and_password(): void
    {
        $this->freezeTime();
        $master = $this->master(true);
        $this->passwordLogin($master);
        $this->verify(self::RECOVERY, 'recovery')->assertRedirect('/maestro');
        $this->post('/seguridad/autenticador/reemplazar', ['current_password' => self::PASSWORD, 'understand' => '1'])->assertRedirect('/seguridad/configurar');
        $fresh = $master->fresh();
        $this->assertNull($fresh->mfa_secret);
        $this->assertNull($fresh->mfa_confirmed_at);
        $this->assertNull($fresh->mfa_recovery_codes);
        $this->get('/maestro')->assertRedirect('/seguridad/configurar');
        $this->get('/seguridad/configurar')->assertOk();
        $secret = session('mfa.enrollment_secret');
        $this->assertNotSame(self::SECRET, $secret);
        $this->post('/seguridad/configurar', ['totp_code' => $this->code($secret)])->assertRedirect('/seguridad/respaldo');
        $this->get('/maestro')->assertOk();
    }

    public function test_recent_recovery_grace_expires_and_cannot_be_extended_from_input(): void
    {
        $this->freezeTime();
        $master = $this->master(true);
        $this->passwordLogin($master);
        $this->verify(self::RECOVERY, 'recovery')->assertRedirect('/maestro');
        $this->travel(301)->seconds();
        $this->post('/seguridad/autenticador/reemplazar', ['current_password' => self::PASSWORD, 'understand' => '1', 'recentRecovery' => true, 'mfa.recovery_at' => now()->timestamp])->assertSessionHasErrors('totp_code');
        $this->assertSame(self::SECRET, $master->fresh()->mfa_secret);
    }

    public function test_pending_setup_cannot_overwrite_an_already_confirmed_authenticator(): void
    {
        $this->freezeTime();
        $master = $this->master();
        $this->passwordLogin($master);
        $this->get('/seguridad/configurar');
        $secret = session('mfa.enrollment_secret');
        $pending = session('mfa');
        $this->post('/seguridad/configurar', ['totp_code' => $this->code($secret)])->assertRedirect('/seguridad/respaldo');
        $this->actingAs($master->fresh())->withSession(['mfa' => $pending])->post('/seguridad/configurar', ['totp_code' => $this->code($secret)])->assertRedirect('/acceso');
        $this->assertSame($secret, $master->fresh()->mfa_secret);
        $this->assertSame(1, $master->fresh()->mfa_version);
    }

    public function test_regeneration_does_not_extend_recent_recovery_grace(): void
    {
        $this->freezeTime();
        $master = $this->master(true);
        $this->passwordLogin($master);
        $this->verify(self::RECOVERY, 'recovery')->assertRedirect('/maestro');
        $original = session('mfa.recovery_at');
        $this->travel(240)->seconds();
        $this->post('/seguridad/respaldo/renovar', ['current_password' => self::PASSWORD])->assertRedirect('/seguridad/respaldo');
        $this->assertSame($original, session('mfa.recovery_at'));
        $this->travel(61)->seconds();
        $this->post('/seguridad/autenticador/reemplazar', ['current_password' => self::PASSWORD, 'understand' => '1'])->assertSessionHasErrors('totp_code');
        $this->assertSame(self::SECRET, $master->fresh()->mfa_secret);
    }
}
