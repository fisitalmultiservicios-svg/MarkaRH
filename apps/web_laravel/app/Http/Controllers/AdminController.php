<?php

namespace App\Http\Controllers;

use App\Models\AttendanceEvent;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Device;
use App\Models\Employee;
use App\Models\Tenant;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    private function tenantId(Request $request): int
    {
        return $request->user()->tenant_id;
    }

    public function dashboard(Request $request): View
    {
        $tenantId = $this->tenantId($request);

        return view('admin.dashboard', ['tenant' => Tenant::findOrFail($tenantId), 'employeeCount' => Employee::where('tenant_id', $tenantId)->where('active', true)->count(), 'deviceCount' => Device::where('tenant_id', $tenantId)->whereNull('revoked_at')->count(), 'eventCount' => AttendanceEvent::where('tenant_id', $tenantId)->count(), 'events' => AttendanceEvent::where('tenant_id', $tenantId)->with(['employee', 'device'])->latest('received_at')->limit(10)->get()]);
    }

    public function employees(Request $request): View
    {
        return view('admin.employees', ['employees' => Employee::where('tenant_id', $this->tenantId($request))->when($request->filled('q'), fn ($q) => $q->where('name', 'like', '%'.mb_substr($request->string('q'), 0, 100).'%'))->with('branch')->orderBy('name')->paginate(20)->withQueryString(), 'branches' => Branch::where('tenant_id', $this->tenantId($request))->get()]);
    }

    public function storeEmployee(Request $request): RedirectResponse
    {
        $tenantId = $this->tenantId($request);
        $data = $this->employeeData($request);
        DB::transaction(function () use ($request, $tenantId, $data) {
            $employee = Employee::create($data + ['tenant_id' => $tenantId, 'active' => true]);
            AuditLog::record($request->user(), 'employee.created', $employee->id);
        });

        return back()->with('status', 'Empleado creado. El aprovisionamiento de PIN llegará con el cliente nativo.');
    }

    public function editEmployee(Request $request, int $employee): View
    {
        return view('admin.employee-edit', ['employee' => Employee::where('tenant_id', $this->tenantId($request))->findOrFail($employee), 'branches' => Branch::where('tenant_id', $this->tenantId($request))->get()]);
    }

    public function updateEmployee(Request $request, int $employee): RedirectResponse
    {
        $employee = Employee::where('tenant_id', $this->tenantId($request))->findOrFail($employee);
        $data = $this->employeeData($request, $employee->id);
        $request->validate(['active' => 'required|boolean']);
        DB::transaction(function () use ($request, $employee, $data) {
            $employee->update($data + ['active' => $request->boolean('active')]);
            AuditLog::record($request->user(), 'employee.updated', $employee->id);
        });

        return redirect()->route('admin.employees')->with('status', 'Empleado actualizado; los eventos originales se conservan.');
    }

    private function employeeData(Request $request, ?int $id = null): array
    {
        $tenantId = $this->tenantId($request);

        return $request->validate(['name' => 'required|string|max:120', 'internal_code' => ['required', 'string', 'max:40', Rule::unique('employees')->where('tenant_id', $tenantId)->ignore($id)], 'job' => 'nullable|string|max:120', 'branch_id' => ['required', 'integer', Rule::exists('branches', 'id')->where('tenant_id', $tenantId)]]);
    }

    public function branches(Request $request): View
    {
        return view('admin.branches', ['branches' => Branch::where('tenant_id', $this->tenantId($request))->get()]);
    }

    public function storeBranch(Request $request): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:100', Rule::unique('branches')->where('tenant_id', $this->tenantId($request))]]);
        DB::transaction(function () use ($request, $data) {
            $b = Branch::create($data + ['tenant_id' => $this->tenantId($request), 'timezone' => 'America/Costa_Rica']);
            AuditLog::record($request->user(), 'branch.created', $b->id);
        });

        return back()->with('status', 'Sucursal creada.');
    }

    public function devices(Request $request): View
    {
        return view('admin.devices', ['devices' => Device::where('tenant_id', $this->tenantId($request))->with('branch')->get(), 'branches' => Branch::where('tenant_id', $this->tenantId($request))->get()]);
    }

    public function revoke(Request $request, int $device): RedirectResponse
    {
        $device = Device::where('tenant_id', $this->tenantId($request))->findOrFail($device);
        DB::transaction(function () use ($request, $device) {
            $device->update(['revoked_at' => now()]);
            AuditLog::record($request->user(), 'device.revoked', $device->id);
        });

        return back()->with('status', 'Terminal revocada para futuras conexiones. Sus eventos se conservan.');
    }

    public function events(Request $request): View
    {
        return view('admin.events', ['events' => AttendanceEvent::where('tenant_id', $this->tenantId($request))->with(['employee', 'device'])->latest('received_at')->paginate(30)]);
    }

    public function audit(Request $request): View
    {
        return view('admin.audit', ['logs' => AuditLog::where('tenant_id', $this->tenantId($request))->latest('id')->paginate(30)]);
    }
}
