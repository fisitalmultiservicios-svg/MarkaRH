<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\DeviceEnrollment;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class MasterController extends Controller
{
    public function index(): View
    {
        return view('master.index', ['tenants' => Tenant::with('branches')->latest()->paginate(20)]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['name' => 'required|string|max:120', 'branch' => 'required|string|max:100', 'admin_name' => 'required|string|max:120', 'email' => 'required|email|max:200|lowercase|unique:users,email', 'password' => ['required', 'confirmed', Password::min(12)->mixedCase()->numbers()]]);
        DB::transaction(function () use ($request, $data) {
            $tenant = Tenant::create(['name' => $data['name'], 'status' => 'active']);
            Branch::create(['tenant_id' => $tenant->id, 'name' => $data['branch'], 'timezone' => 'America/Costa_Rica']);
            User::create(['name' => $data['admin_name'], 'email' => $data['email'], 'password' => $data['password'], 'role' => 'admin', 'tenant_id' => $tenant->id]);
            AuditLog::record($request->user(), 'tenant.created', $tenant->id, $tenant->id);
        });

        return back()->with('status', 'Empresa y cuenta administrativa creadas. Comunica las credenciales por un canal seguro.');
    }

    public function update(Request $request, int $tenant): RedirectResponse
    {
        $data = $request->validate(['status' => 'required|in:active,suspended']);
        DB::transaction(function () use ($request, $tenant, $data) {
            Tenant::findOrFail($tenant)->update($data);
            AuditLog::record($request->user(), 'tenant.'.$data['status'], $tenant, $tenant);
        });

        return back()->with('status', 'Estado actualizado. Los eventos existentes se conservan.');
    }

    public function enrollment(Request $request, int $tenant): RedirectResponse
    {
        Tenant::where('status', 'active')->findOrFail($tenant);
        $request->validate(['branch_id' => ['required', 'integer', Rule::exists('branches', 'id')->where('tenant_id', $tenant)]]);
        $code = Str::random(48);
        DB::transaction(function () use ($request, $tenant, $code) {
            $e = DeviceEnrollment::create(['tenant_id' => $tenant, 'branch_id' => $request->integer('branch_id'), 'code_hash' => hash('sha256', $code), 'expires_at' => now()->addMinutes(15)]);
            AuditLog::record($request->user(), 'device.enrollment_issued', $e->id, $tenant);
        });

        return back()->with('enrollment_code', $code)->with('status', 'Credencial creada. Vence en 15 minutos y solo permite un uso.');
    }

    public function inquiries(Request $request): View
    {
        AuditLog::record($request->user(), 'inquiry.list_viewed');

        return view('master.inquiries', ['inquiries' => DB::table('inquiries')->latest()->paginate(20)]);
    }
}
