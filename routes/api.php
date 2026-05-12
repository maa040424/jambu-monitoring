<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SensorDataController;
use App\Http\Controllers\Api\ForecastController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| POST /api/sensor-data  → dilindungi API key (untuk Arduino/ESP)
| GET  /api/sensor-data  → dilindungi session auth (untuk dashboard)
| GET  /api/forecast     → dilindungi session auth (untuk dashboard)
|
*/

// Arduino/ESP kirim data — butuh API key di header X-API-KEY
Route::post('/sensor-data', [SensorDataController::class, 'store'])
    ->middleware('api.key');

// Dashboard ambil data — hanya user yang sudah login
Route::middleware('web', 'auth')->group(function () {
    Route::get('/sensor-data', [SensorDataController::class, 'index']);
    Route::get('/forecast', [ForecastController::class, 'index']);
});