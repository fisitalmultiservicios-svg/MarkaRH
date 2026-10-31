<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Device;
use App\Models\DeviceEnrollment;
use App\Models\Employee;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class MarkaRhTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function company(string $name = 'Empresa A'): array
    {
        $t = Tenant::factory()->create(['name' => $name]);
        $b = Branch::factory()->create(['tenant_id' => $t->id, 'name' => 'Central']);
        $u = User::factory()->create(['tenant_id' => $t->id, 'role' => 'admin']);
        $e = Employee::factory()->create(['tenant_id' => $t->id, 'branch_id' => $b->id, 'name' => $name.' Persona', 'internal_code' => 'E01']);

        return [$t, $b, $u, $e];
    }

    private function terminal(Tenant $t, Branch $b): array
    {
        $token = Str::random(80);

        return [Device::factory()->create(['tenant_id' => $t->id, 'branch_id' => $b->id, 'name' => 'Terminal test', 'token_hash' => hash('sha256', $token)]), $token];
    }

    private function event(Employee $e, int $seq = 1): array
    {
        return ['id' => (string) Str::uuid(), 'sequence' => $seq, 'employee_id' => $e->id, 'action' => 'IN', 'local_occurred_at' => '2026-10-31T08:00:00-06:00'];
    }

    public function test_public_pages_and_login_are_available_without_a_marker(): void
    {
        $this->get('/')->assertOk()->assertSee('Tu equipo marca.')->assertDontSee('123456');
        $this->get('/acceso')->assertOk()->assertDontSee('data-role', false);
        $this->get('/privacidad')->assertOk();
        $this->get('/admin')->assertRedirect('/acceso');
        $this->post('/admin/marcaciones', [])->assertNotFound();
        $this->get('/marcador')->assertNotFound();
    }

    public function test_session_login_and_logout_use_real_hashed_passwords(): void
    {
        [, , $u] = $this->company();
        $u->update(['password' => 'SecureTestPassword42']);
        $this->assertTrue(Hash::check('SecureTestPassword42', $u->fresh()->password));
        $this->post('/acceso', ['email' => $u->email, 'password' => 'wrong'])->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->post('/acceso', ['email' => $u->email, 'password' => 'SecureTestPassword42'])->assertRedirect('/admin');
        $this->assertAuthenticatedAs($u);
        $this->post('/salir')->assertRedirect('/acceso');
        $this->assertGuest();
    }

    public function test_login_limits_repeated_attempts(): void
    {
        [, , $u] = $this->company();
        for ($i = 0; $i < 6; $i++) {
            $response = $this->post('/acceso', ['email' => $u->email, 'password' => 'wrong']);
        }
        $response->assertSessionHasErrors(['email' => 'Demasiados intentos. Espera un minuto.']);
        $this->assertGuest();
    }

    public function test_company_admin_cannot_access_master_or_authorize_devices(): void
    {
        [$t, $b, $u] = $this->company();
        $this->actingAs($u)->get('/maestro')->assertForbidden();
        $this->post('/maestro/empresas/'.$t->id.'/instalaciones', ['branch_id' => $b->id])->assertForbidden();
        $this->assertDatabaseCount('device_enrollments', 0);
    }

    public function test_master_creates_company_admin_with_hash_and_branch_atomically(): void
    {
        $master = User::factory()->create(['role' => 'master', 'tenant_id' => null]);
        $this->actingAs($master)->post('/maestro/empresas', ['name' => 'Nueva empresa', 'branch' => 'San José', 'admin_name' => 'Admin', 'email' => 'admin@test.example', 'password' => 'SecureInitial42!', 'password_confirmation' => 'SecureInitial42!'])->assertRedirect();
        $admin = User::where('email', 'admin@test.example')->firstOrFail();
        $this->assertEquals('admin', $admin->role);
        $this->assertTrue(Hash::check('SecureInitial42!', $admin->password));
        $this->assertDatabaseHas('branches', ['tenant_id' => $admin->tenant_id, 'name' => 'San José']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'tenant.created', 'user_id' => $master->id]);
        $this->get('/admin')->assertForbidden();
    }

    public function test_employee_lists_are_scoped_and_foreign_identifiers_return_404(): void
    {
        [, , $a] = $this->company('Visible A');
        [, , , $eb] = $this->company('Privada B');
        $this->actingAs($a)->get('/admin/empleados')->assertOk()->assertSee('Visible A Persona')->assertDontSee('Privada B Persona');
        $this->get('/admin/empleados/'.$eb->id.'/editar')->assertNotFound();
        $this->patch('/admin/empleados/'.$eb->id, [])->assertNotFound();
    }

    public function test_employee_cannot_be_assigned_to_another_company_branch(): void
    {
        [$ta, , $ua] = $this->company();
        [, $bb] = $this->company('Empresa B');
        $this->actingAs($ua)->post('/admin/empleados', ['name' => 'No permitido', 'internal_code' => 'E02', 'branch_id' => $bb->id, 'tenant_id' => $bb->tenant_id])->assertSessionHasErrors('branch_id');
        $this->assertDatabaseMissing('employees', ['name' => 'No permitido']);
    }

    public function test_create_edit_and_deactivate_employee_preserve_company(): void
    {
        [$t, $b, $u, $e] = $this->company();
        $this->actingAs($u)->post('/admin/empleados', ['name' => 'Persona Nueva', 'internal_code' => 'E02', 'branch_id' => $b->id, 'tenant_id' => 999])->assertRedirect();
        $this->assertDatabaseHas('employees', ['name' => 'Persona Nueva', 'tenant_id' => $t->id]);
        $this->patch('/admin/empleados/'.$e->id, ['name' => 'Actualizada', 'internal_code' => 'E01', 'branch_id' => $b->id, 'active' => false])->assertRedirect('/admin/empleados');
        $this->assertDatabaseHas('employees', ['id' => $e->id, 'name' => 'Actualizada', 'active' => false]);
    }

    public function test_master_enrollment_is_single_use_and_token_is_only_stored_hashed(): void
    {
        [$t, $b] = $this->company();
        $m = User::factory()->create(['role' => 'master', 'tenant_id' => null]);
        $r = $this->actingAs($m)->post('/maestro/empresas/'.$t->id.'/instalaciones', ['branch_id' => $b->id])->assertRedirect();
        $code = session('enrollment_code');
        $this->assertNotEmpty($code);
        $this->assertDatabaseHas('device_enrollments', ['code_hash' => hash('sha256', $code)]);
        $r = $this->postJson('/api/v1/device/enroll', ['enrollment_code' => $code, 'name' => 'Windows'])->assertCreated();
        $token = $r->json('token');
        $this->assertNotEmpty($token);
        $this->assertDatabaseHas('devices', ['token_hash' => hash('sha256', $token)]);
        $this->postJson('/api/v1/device/enroll', ['enrollment_code' => $code, 'name' => 'Clon'])->assertUnprocessable();
        $this->assertDatabaseCount('devices', 1);
        $this->assertStringNotContainsString($token, Device::first()->toJson());
    }

    public function test_expired_enrollment_and_foreign_branch_are_rejected(): void
    {
        [$t, $b] = $this->company();
        [, $foreign] = $this->company('Otra');
        $m = User::factory()->create(['role' => 'master', 'tenant_id' => null]);
        $this->actingAs($m)->post('/maestro/empresas/'.$t->id.'/instalaciones', ['branch_id' => $foreign->id])->assertSessionHasErrors('branch_id');
        DeviceEnrollment::create(['tenant_id' => $t->id, 'branch_id' => $b->id, 'code_hash' => hash('sha256', 'expired'), 'expires_at' => now()->subMinute()]);
        $this->postJson('/api/v1/device/enroll', ['enrollment_code' => 'expired', 'name' => 'No válido'])->assertUnprocessable();
        $this->assertDatabaseCount('devices', 0);
    }

    public function test_admin_session_does_not_authenticate_device_api(): void
    {
        [, , $u, $e] = $this->company();
        $this->actingAs($u)->postJson('/api/v1/device/events/batch', ['events' => [$this->event($e)]])->assertUnauthorized();
        $this->assertDatabaseCount('attendance_events', 0);
    }

    public function test_batch_is_idempotent_and_visible_only_in_its_company(): void
    {
        [$t, $b, $u, $e] = $this->company();
        [$d, $token] = $this->terminal($t, $b);
        [, , $other] = $this->company('Otra empresa');
        $event = $this->event($e);
        $this->withToken($token)->postJson('/api/v1/device/events/batch', ['events' => [$event]])->assertOk()->assertJsonPath('results.0.status', 'accepted');
        for ($i = 0; $i < 3; $i++) {
            $this->withToken($token)->postJson('/api/v1/device/events/batch', ['events' => [$event]])->assertJsonPath('results.0.status', 'duplicate');
        }
        $this->assertDatabaseCount('attendance_events', 1);
        $this->actingAs($u)->get('/admin/registros')->assertOk()->assertSee($e->name)->assertSee('2026-10-31T08:00:00-06:00');
        $this->actingAs($other)->get('/admin/registros')->assertOk()->assertDontSee($e->name);
    }

    public function test_event_uuid_and_sequence_conflicts_never_overwrite_evidence(): void
    {
        [$t, $b, , $e] = $this->company();
        [, $token] = $this->terminal($t, $b);
        $event = $this->event($e);
        $this->withToken($token)->postJson('/api/v1/device/events/batch', ['events' => [$event]])->assertJsonPath('results.0.status', 'accepted');
        $changed = $event;
        $changed['action'] = 'OUT';
        $this->postJson('/api/v1/device/events/batch', ['events' => [$changed]])->assertJsonPath('results.0.reason', 'identity_conflict');
        $this->postJson('/api/v1/device/events/batch', ['events' => [$this->event($e)]])->assertJsonPath('results.0.reason', 'sequence_conflict');
        $this->assertDatabaseHas('attendance_events', ['id' => $event['id'], 'action' => 'IN']);
        $this->assertDatabaseCount('attendance_events', 1);
    }

    public function test_terminal_cannot_ingest_foreign_or_other_branch_employee(): void
    {
        [$t, $b, , $e] = $this->company();
        [, $token] = $this->terminal($t, $b);
        [, , , $other] = $this->company('Otra');
        $this->withToken($token)->postJson('/api/v1/device/events/batch', ['events' => [$this->event($other)]])->assertJsonPath('results.0.reason', 'employee_not_authorized');
        $newBranch = Branch::factory()->create(['tenant_id' => $t->id, 'name' => 'Norte']);
        $e->update(['branch_id' => $newBranch->id]);
        $this->postJson('/api/v1/device/events/batch', ['events' => [$this->event($e)]])->assertJsonPath('results.0.reason', 'employee_not_authorized');
        $this->assertDatabaseCount('attendance_events', 0);
    }

    public function test_revocation_blocks_connections_and_preserves_received_events(): void
    {
        [$t, $b, $u, $e] = $this->company();
        [$d, $token] = $this->terminal($t, $b);
        $this->withToken($token)->postJson('/api/v1/device/events/batch', ['events' => [$this->event($e)]])->assertOk();
        $this->actingAs($u)->post('/admin/dispositivos/'.$d->id.'/revocar')->assertRedirect();
        $this->withToken($token)->getJson('/api/v1/device/config')->assertUnauthorized();
        $this->postJson('/api/v1/device/events/batch', ['events' => [$this->event($e, 2)]])->assertUnauthorized();
        $this->assertDatabaseCount('attendance_events', 1);
    }

    public function test_suspending_company_blocks_existing_sessions_and_tokens(): void
    {
        [$t, $b, $u] = $this->company();
        [, $token] = $this->terminal($t, $b);
        $t->update(['status' => 'suspended']);
        $this->actingAs($u)->get('/admin')->assertForbidden();
        $this->withToken($token)->getJson('/api/v1/device/config')->assertUnauthorized();
    }

    public function test_foreign_device_revocation_and_audit_access_are_isolated(): void
    {
        [$t, $b, $u] = $this->company();
        [$otherT, $otherB] = $this->company('Otra');
        [$d] = $this->terminal($otherT, $otherB);
        $this->actingAs($u)->post('/admin/dispositivos/'.$d->id.'/revocar')->assertNotFound();
        $this->assertNull($d->fresh()->revoked_at);
        $this->get('/admin/bitacora')->assertOk();
    }

    public function test_invalid_time_and_tenant_override_are_rejected(): void
    {
        [$t, $b, , $e] = $this->company();
        [, $token] = $this->terminal($t, $b);
        $event = $this->event($e);
        $event['local_occurred_at'] = '2026-10-31 08:00:00';
        $this->withToken($token)->postJson('/api/v1/device/events/batch', ['events' => [$event]])->assertUnprocessable();
        $this->postJson('/api/v1/device/events/batch', ['events' => [$this->event($e)], 'tenant_id' => 999])->assertUnprocessable();
        $this->assertDatabaseCount('attendance_events', 0);
    }

    public function test_oversize_batch_is_rejected_without_partial_ingestion(): void
    {
        [$t, $b, , $e] = $this->company();
        [, $token] = $this->terminal($t, $b);
        $this->withToken($token)->postJson('/api/v1/device/events/batch', ['events' => array_map(fn ($i) => $this->event($e, $i), range(1, 101))])->assertUnprocessable();
        $this->assertDatabaseCount('attendance_events', 0);
    }

    public function test_contact_validates_consent_and_persists_without_email_delivery(): void
    {
        $data = ['name' => 'Contacto', 'email' => 'contacto@test.example', 'company' => 'Empresa', 'message' => 'Consulta de implementación'];
        $this->post('/contacto', $data)->assertSessionHasErrors('consent');
        $this->assertDatabaseCount('inquiries', 0);
        $this->post('/contacto', $data + ['consent' => '1'])->assertRedirect('/#consulta');
        $this->assertDatabaseCount('inquiries', 1);
    }

    public function test_employee_text_is_escaped_in_administrative_views(): void
    {
        [, , $u, $e] = $this->company();
        $payload = '<script>alert("xss")</script>';
        $e->update(['name' => $payload]);
        $this->actingAs($u)->get('/admin/empleados')->assertOk()->assertSee($payload)->assertDontSee($payload, false);
    }

    public function test_contact_content_is_escaped_and_only_master_can_read_it(): void
    {
        [, , $u] = $this->company();
        $payload = '<img src=x onerror=alert(1)>';
        $this->post('/contacto', ['name' => 'Contacto', 'email' => 'contacto@test.example', 'company' => 'Empresa', 'message' => $payload, 'consent' => '1'])->assertRedirect('/#consulta');
        $this->actingAs($u)->get('/maestro/consultas')->assertForbidden();
        $m = User::factory()->create(['role' => 'master', 'tenant_id' => null]);
        $this->actingAs($m)->get('/maestro/consultas')->assertOk()->assertSee($payload)->assertDontSee($payload, false);
    }

    public function test_api_rate_limit_is_per_authenticated_terminal(): void
    {
        $this->freezeTime();
        [$t, $b] = $this->company();
        [, $first] = $this->terminal($t, $b);
        [, $second] = $this->terminal($t, $b);
        for ($i = 0; $i < 60; $i++) {
            $this->withToken($first)->getJson('/api/v1/device/config')->assertOk();
        }
        $this->withToken($first)->getJson('/api/v1/device/config')->assertStatus(429);
        $this->withToken($second)->getJson('/api/v1/device/config')->assertOk();
    }

    public function test_uuid_case_does_not_create_a_second_event(): void
    {
        [$t, $b, , $e] = $this->company();
        [, $token] = $this->terminal($t, $b);
        $event = $this->event($e);
        $this->withToken($token)->postJson('/api/v1/device/events/batch', ['events' => [$event]])->assertJsonPath('results.0.status', 'accepted');
        $event['id'] = strtoupper($event['id']);
        $this->postJson('/api/v1/device/events/batch', ['events' => [$event]])->assertJsonPath('results.0.status', 'duplicate');
        $this->assertDatabaseCount('attendance_events', 1);
    }
}
