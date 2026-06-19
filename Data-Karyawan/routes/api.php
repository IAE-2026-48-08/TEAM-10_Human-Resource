<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\SsoController;
use App\Models\Employee;
use App\Http\Controllers\Api\SoapController;
use App\Http\Controllers\Api\RabbitMqController;

Route::prefix('v1')->group(function () {
    Route::post('/auth/login', [SsoController::class, 'login']);
    Route::post('/auth/login-m2m', [SsoController::class, 'loginM2M']);
    Route::get('/auth/health', [SsoController::class, 'health']);

    Route::post('/audit/send', [SoapController::class, 'sendAudit']);
    Route::post('/messages/publish', [RabbitMqController::class, 'publish']);

    Route::middleware('api.key')->group(function () {
        Route::get('/employees', [EmployeeController::class, 'index']);
        Route::get('/employees/{id}', [EmployeeController::class, 'show']);
        Route::post('/employees', [EmployeeController::class, 'store']);
    });
});

Route::get('/test-insert', function () {
    Employee::create([
        'nip' => 'EMP001',
        'nama' => 'Budi Santoso',
        'jabatan' => 'HR Staff',
        'departemen' => 'Human Resource',
        'gaji_pokok' => 5000000,
        'email' => 'budi@gmail.com'
    ]);

    return 'Data berhasil ditambahkan';
});