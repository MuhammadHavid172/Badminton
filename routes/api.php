<?php

use App\Http\Controllers\ScheduleController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::middleware('throttle:60,1')->group(function () {
    // API Jadwal Publik (Tanpa data pribadi, cache 10 detik)
    Route::get('/jadwal/hari', [ScheduleController::class, 'apiDay'])->name('api.jadwal.hari');
    Route::get('/jadwal/bulan', [ScheduleController::class, 'apiMonth'])->name('api.jadwal.bulan');
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
