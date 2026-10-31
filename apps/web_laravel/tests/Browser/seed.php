<?php

use App\Models\Branch;
use App\Models\Employee;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;

// Test-only fixture. Refuses every database except the dedicated test database.
require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (! app()->environment('testing') || config('database.connections.mysql.database') !== 'markarh_test' || config('database.default') !== 'mysql') {
    throw new RuntimeException('Browser fixtures require APP_ENV=testing and MySQL markarh_test.');
}
Artisan::call('migrate:fresh', ['--force' => true]);
$t = Tenant::firstOrCreate(['name' => 'Empresa de prueba del navegador']);
$b = Branch::firstOrCreate(['tenant_id' => $t->id, 'name' => 'San José']);
User::updateOrCreate(['email' => 'admin@browser.example'], ['tenant_id' => $t->id, 'name' => 'Administración de prueba', 'role' => 'admin', 'password' => 'BrowserTestPassword42!']);
User::factory()->master()->withMfa()->create(['email' => 'master@browser.example', 'name' => 'Maestro de prueba', 'password' => 'BrowserTestPassword42!']);
Employee::firstOrCreate(['tenant_id' => $t->id, 'internal_code' => 'BROWSER1'], ['branch_id' => $b->id, 'name' => 'Persona de prueba', 'job' => 'Operaciones']);
echo "Browser test fixtures prepared in markarh_test only.\n";
