<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PayrollController;

Route::middleware('check.api.key')->prefix('v1')->group(function () {
    Route::get('/payrolls', [PayrollController::class, 'index']);
    Route::get('/payrolls/{id}', [PayrollController::class, 'show']);
    Route::post('/payrolls/process', [PayrollController::class, 'process']);
});