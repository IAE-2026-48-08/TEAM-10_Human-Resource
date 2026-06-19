<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AbsensiController;

/*
|--------------------------------------------------------------------------
| API Routes - Service Absensi
|--------------------------------------------------------------------------
|
| Semua endpoint dilindungi oleh middleware ApiKeyMiddleware (X-IAE-KEY).
| Base URL: /api/v1/absensi
|
| Endpoints:
| GET    /api/v1/absensi       - Menampilkan seluruh rekap absensi
| GET    /api/v1/absensi/{id}  - Menampilkan rekap absensi per karyawan
| POST   /api/v1/absensi       - Menambah/generate rekap absensi bulanan
|
*/

Route::prefix('v1')->middleware(\App\Http\Middleware\ApiKeyMiddleware::class)->group(function () {
    // GET /api/v1/absensi - Menampilkan seluruh rekap absensi karyawan
    Route::get('/absensi', [AbsensiController::class, 'index']);

    // GET /api/v1/absensi/{id} - Menampilkan rekap absensi berdasarkan karyawan (perorangan)
    Route::get('/absensi/{id}', [AbsensiController::class, 'show']);

    // POST /api/v1/absensi - Menambahkan atau generate rekap absensi bulanan
    Route::post('/absensi', [AbsensiController::class, 'store']);
});
