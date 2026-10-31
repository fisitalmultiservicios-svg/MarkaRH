<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MasterController;
use App\Http\Controllers\MfaController;
use App\Http\Controllers\PublicController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'public.home')->name('home');
Route::post('/contacto', [PublicController::class, 'contact'])->middleware('throttle:3,1')->name('contact');
Route::view('/terminos', 'public.terms')->name('terms');
Route::view('/privacidad', 'public.privacy')->name('privacy');
Route::middleware('guest')->group(function () {
    Route::get('/acceso', [AuthController::class, 'create'])->name('login');
    Route::post('/acceso', [AuthController::class, 'store'])->middleware('throttle:20,1')->name('login.store');
});
Route::post('/salir', [AuthController::class, 'destroy'])->middleware('auth')->name('logout');
Route::middleware(['auth', 'role:master', 'mfa'])->prefix('maestro')->name('master.')->group(function () {
    Route::get('/', [MasterController::class, 'index'])->name('index');
    Route::get('/consultas', [MasterController::class, 'inquiries'])->name('inquiries');
    Route::post('/empresas', [MasterController::class, 'store'])->name('tenants.store');
    Route::patch('/empresas/{tenant}', [MasterController::class, 'update'])->name('tenants.update');
    Route::post('/empresas/{tenant}/instalaciones', [MasterController::class, 'enrollment'])->name('enrollment');
});
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/empleados', [AdminController::class, 'employees'])->name('employees');
    Route::post('/empleados', [AdminController::class, 'storeEmployee'])->name('employees.store');
    Route::get('/empleados/{employee}/editar', [AdminController::class, 'editEmployee'])->name('employees.edit');
    Route::patch('/empleados/{employee}', [AdminController::class, 'updateEmployee'])->name('employees.update');
    Route::get('/sucursales', [AdminController::class, 'branches'])->name('branches');
    Route::post('/sucursales', [AdminController::class, 'storeBranch'])->name('branches.store');
    Route::get('/dispositivos', [AdminController::class, 'devices'])->name('devices');
    Route::post('/dispositivos/{device}/revocar', [AdminController::class, 'revoke'])->name('devices.revoke');
    Route::get('/registros', [AdminController::class, 'events'])->name('events');
    Route::get('/bitacora', [AdminController::class, 'audit'])->name('audit');
});

Route::middleware(['auth', 'role:master'])->prefix('seguridad')->name('security.')->group(function () {
    Route::get('/configurar', [MfaController::class, 'setup'])->name('setup');
    Route::post('/configurar', [MfaController::class, 'enable'])->middleware('throttle:20,1')->name('enable');
    Route::get('/verificar', [MfaController::class, 'challenge'])->name('challenge');
    Route::post('/verificar', [MfaController::class, 'verify'])->middleware('throttle:20,1')->name('verify');
    Route::middleware('mfa')->group(function () {
        Route::get('/', [MfaController::class, 'index'])->name('index');
        Route::get('/respaldo', [MfaController::class, 'recovery'])->name('recovery');
        Route::post('/respaldo/renovar', [MfaController::class, 'regenerate'])->middleware('throttle:10,1')->name('regenerate');
        Route::post('/autenticador/reemplazar', [MfaController::class, 'reset'])->middleware('throttle:10,1')->name('reset');
    });
});
