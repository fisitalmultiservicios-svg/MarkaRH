<?php

namespace App\Http\Controllers;

use App\Models\AttendanceEvent;
use App\Models\AuditLog;
use App\Models\Device;
use App\Models\DeviceEnrollment;
use App\Models\Employee;
use App\Models\Tenant;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DeviceApiController extends Controller
{
    public function enroll(Request $request): JsonResponse
    {
        $data = $request->validate(['enrollment_code' => 'required|string|max:100', 'name' => 'required|string|max:100']);

        return DB::transaction(function () use ($data) {
            $enrollment = DeviceEnrollment::where('code_hash', hash('sha256', $data['enrollment_code']))->lockForUpdate()->first();
            if (! $enrollment || $enrollment->consumed_at || $enrollment->expires_at->isPast() || ! Tenant::whereKey($enrollment->tenant_id)->where('status', 'active')->exists()) {
                throw ValidationException::withMessages(['enrollment_code' => 'Credencial inválida o no disponible.']);
            }
            $token = Str::random(80);
            $device = Device::create(['tenant_id' => $enrollment->tenant_id, 'branch_id' => $enrollment->branch_id, 'name' => $data['name'], 'token_hash' => hash('sha256', $token)]);
            $enrollment->update(['consumed_at' => now()]);
            AuditLog::record(null, 'device.enrolled', $device->id, $device->tenant_id);

            return response()->json(['device_id' => $device->id, 'token' => $token, 'token_type' => 'Bearer'], 201)->header('Cache-Control', 'no-store');
        });
    }

    public function config(Request $request): JsonResponse
    {
        $device = $request->attributes->get('device');

        return response()->json(['device_id' => $device->id, 'tenant_id' => $device->tenant_id, 'branch_id' => $device->branch_id, 'timezone' => 'America/Costa_Rica', 'protocol_version' => 1, 'security_stage' => 'initial_backend_not_production'])->header('Cache-Control', 'no-store');
    }

    public function batch(Request $request): JsonResponse
    {
        $data = $request->validate(['events' => 'required|array|min:1|max:100', 'events.*' => 'required|array:id,sequence,employee_id,action,local_occurred_at', 'events.*.id' => 'required|uuid', 'events.*.sequence' => 'required|integer|min:1|max:2147483647', 'events.*.employee_id' => 'required|integer|min:1', 'events.*.action' => 'required|in:IN,OUT,BREAK_START,BREAK_END,AUTO_PUNCH', 'events.*.local_occurred_at' => ['required', 'string', 'max:40', 'date', 'after_or_equal:2000-01-01T00:00:00Z', 'before:2100-01-01T00:00:00Z', 'regex:/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d{1,6})?(?:Z|[+-]\d{2}:\d{2})$/']]);
        if (array_diff(array_keys($request->all()), ['events'])) {
            throw ValidationException::withMessages(['events' => 'No se admiten campos de empresa ni campos adicionales.']);
        }
        $device = $request->attributes->get('device');
        $results = [];
        foreach ($data['events'] as $event) {
            $event['id'] = strtolower($event['id']);
            try {
                $results[] = $this->receive($device, $event);
            } catch (QueryException $e) {
                if (! in_array((string) $e->getCode(), ['23000', '23505'], true)) {
                    throw $e;
                }
                $results[] = ['id' => $event['id'], 'status' => 'rejected_with_reason', 'reason' => 'identity_conflict'];
            }
        }

        return response()->json(['results' => $results])->header('Cache-Control', 'no-store');
    }

    private function receive(Device $device, array $event): array
    {
        return DB::transaction(function () use ($device, $event) {
            $locked = Device::whereKey($device->id)->lockForUpdate()->firstOrFail();
            $result = ['id' => $event['id']];
            if ($locked->revoked_at || ! Tenant::whereKey($locked->tenant_id)->where('status', 'active')->exists()) {
                return $result + ['status' => 'rejected_with_reason', 'reason' => 'device_not_authorized'];
            }
            $payload = ['sequence' => (int) $event['sequence'], 'employee_id' => (int) $event['employee_id'], 'action' => $event['action'], 'local_occurred_at' => $event['local_occurred_at']];
            $hash = hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR));
            $old = AttendanceEvent::find($event['id']);
            if ($old) {
                if ($old->device_id === $device->id && hash_equals($old->payload_hash, $hash)) {
                    return $result + ['status' => 'duplicate'];
                }

                return $result + ['status' => 'rejected_with_reason', 'reason' => 'identity_conflict'];
            }
            if (AttendanceEvent::where('device_id', $device->id)->where('sequence', $event['sequence'])->exists()) {
                return $result + ['status' => 'rejected_with_reason', 'reason' => 'sequence_conflict'];
            }
            if (! Employee::whereKey($event['employee_id'])->where('tenant_id', $device->tenant_id)->where('branch_id', $device->branch_id)->where('active', true)->exists()) {
                return $result + ['status' => 'rejected_with_reason', 'reason' => 'employee_not_authorized'];
            }
            AttendanceEvent::create($payload + ['id' => $event['id'], 'tenant_id' => $device->tenant_id, 'branch_id' => $device->branch_id, 'device_id' => $device->id, 'occurred_at' => CarbonImmutable::parse($event['local_occurred_at'])->utc(), 'received_at' => now(), 'payload_hash' => $hash]);

            return $result + ['status' => 'accepted'];
        });
    }
}
