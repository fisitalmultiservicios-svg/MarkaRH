<?php

use App\Http\Controllers\DeviceApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/device')->group(function () {
    Route::post('/enroll', [DeviceApiController::class, 'enroll'])->middleware('throttle:5,1');
    Route::middleware(['device', 'throttle:device-api'])->group(function () {
        Route::get('/config', [DeviceApiController::class, 'config']);
        Route::post('/events/batch', [DeviceApiController::class, 'batch']);
    });
});
