<?php

namespace Tests\Feature;

use App\Models\AttendanceEvent;
use App\Models\Branch;
use App\Models\Device;
use App\Models\Employee;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class IntegrityTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_mixed_batch_preserves_valid_event_and_reports_unauthorized_one(): void
    {
        $t = Tenant::factory()->create(['name' => 'Empresa']);
        $b = Branch::factory()->create(['tenant_id' => $t->id, 'name' => 'Central']);
        $e = Employee::factory()->create(['tenant_id' => $t->id, 'branch_id' => $b->id, 'name' => 'Persona', 'internal_code' => 'E1']);
        Device::factory()->create(['tenant_id' => $t->id, 'branch_id' => $b->id, 'name' => 'Terminal', 'token_hash' => hash('sha256', 'test-token-only')]);
        $event = ['id' => (string) Str::uuid(), 'sequence' => 1, 'employee_id' => $e->id, 'action' => 'IN', 'local_occurred_at' => '2026-10-31T08:00:00-06:00'];
        $bad = $event;
        $bad['id'] = (string) Str::uuid();
        $bad['sequence'] = 2;
        $bad['employee_id'] = 99999;
        $this->withToken('test-token-only')->postJson('/api/v1/device/events/batch', ['events' => [$event, $bad]])->assertOk()->assertJsonPath('results.0.status', 'accepted')->assertJsonPath('results.1.reason', 'employee_not_authorized');
        $this->assertDatabaseCount('attendance_events', 1);
        $this->assertEquals('2026-10-31 14:00:00', AttendanceEvent::first()->occurred_at->format('Y-m-d H:i:s'));
        $original = AttendanceEvent::first();
        try {
            $original->update(['action' => 'OUT']);
            $this->fail('Una evidencia original no debe poder modificarse.');
        } catch (\LogicException $ex) {
            $this->assertStringContainsString('no se modifican', $ex->getMessage());
        }
        $this->assertDatabaseHas('attendance_events', ['id' => $event['id'], 'action' => 'IN']);
    }

    public function test_master_command_creates_no_default_password_and_refuses_second_master(): void
    {
        $this->artisan('markarh:create-master')->expectsQuestion('Nombre', 'Maestro de prueba')->expectsQuestion('Correo electrónico', 'master@test.example')->expectsQuestion('Contraseña (mínimo 12 caracteres, mayúsculas, minúsculas y números)', 'OnlyTestPassword42')->expectsQuestion('Repite la contraseña', 'OnlyTestPassword42')->assertSuccessful();
        $this->assertDatabaseHas('users', ['email' => 'master@test.example', 'role' => 'master', 'tenant_id' => null]);
        $this->artisan('markarh:create-master')->assertFailed();
        $this->assertDatabaseCount('users', 1);
    }

    public function test_default_seeder_does_not_create_demo_accounts(): void
    {
        $this->seed();
        $this->assertDatabaseCount('users', 0);
    }
}
