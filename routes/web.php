<?php

use App\Http\Controllers\BackupController;
use App\Http\Controllers\ChangePasswordController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\ForecastController;
use App\Http\Controllers\ModeController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\Api\SensorDataController;
use Illuminate\Support\Facades\Route;

// Redirect /register ke /login (registrasi dimatikan)
Route::redirect('/register', '/login');

// Guest: tampilkan landing page custom
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return view('welcome');
});

// Route yang butuh login
Route::middleware('auth')->group(function () {
    // Mode selector
    Route::get('/select-mode', [ModeController::class, 'showSelect'])->name('mode.select');
    Route::post('/select-mode', [ModeController::class, 'setMode'])->name('mode.set');
    Route::post('/switch-mode', [ModeController::class, 'switchMode'])->name('mode.switch');

    // Semua user (admin + petani) bisa akses
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/forecast', [ForecastController::class, 'index'])->name('forecast');
    Route::get('/profil-kebun', function () { return view('profil-kebun'); })->name('profil.kebun');

    // Ubah password (semua user)
    Route::get('/change-password', [ChangePasswordController::class, 'edit'])->name('password.edit');
    Route::put('/change-password', [ChangePasswordController::class, 'update'])->name('password.update');

    // Semua user bisa export PDF prediksi
    Route::get('/export-forecast-pdf', [\App\Http\Controllers\ForecastPdfController::class, 'export'])->name('export.forecast.pdf');

    // Generate data dummy
    Route::post('/sensor-data/generate-dummy', [App\Http\Controllers\Api\SensorDataController::class, 'generateDummy'])->name('sensor.generateDummy');
    Route::post('/sensor-data/simulate', [App\Http\Controllers\Api\SensorDataController::class, 'simulate'])->name('sensor.simulate');

    // Hanya admin
    Route::middleware('admin')->group(function () {
        Route::get('/export-sensor-data', [ExportController::class, 'export'])->name('export.sensor');
        Route::post('/sensor-data/clear', [SensorDataController::class, 'clear'])->name('sensor.clear');
        Route::post('/sensor-data/delete-selected', [SensorDataController::class, 'destroySelected'])->name('sensor.deleteSelected');

        // Backup & Restore data real
        Route::get('/backup/download', [BackupController::class, 'download'])->name('backup.download');
        Route::post('/backup/restore', [BackupController::class, 'restore'])->name('backup.restore');
        Route::get('/backup/info', [BackupController::class, 'info'])->name('backup.info');

        // User management
        Route::get('/users', [UserManagementController::class, 'index'])->name('users.index');
        Route::post('/users', [UserManagementController::class, 'store'])->name('users.store');
        Route::delete('/users/{user}', [UserManagementController::class, 'destroy'])->name('users.destroy');
        Route::post('/users/{user}/reset-password', [UserManagementController::class, 'resetPassword'])->name('users.resetPassword');
    });
});

require __DIR__ . '/auth.php';
