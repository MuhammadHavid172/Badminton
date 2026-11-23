<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\OwnerController;
use App\Http\Controllers\ScheduleController;
use App\Services\ScheduleService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - Website Booking Lapangan Badminton
|--------------------------------------------------------------------------
*/

// Beranda & Landing Page Lengkap dengan Jadwal Hari Ini
Route::get('/', function (ScheduleService $scheduleService) {
    $nowJayapura = Carbon::now('Asia/Jayapura');
    $todaySchedule = $scheduleService->dayGrid($nowJayapura);

    return view('landing', compact('todaySchedule'));
})->name('beranda');

// Halaman Publik: Jadwal Lapangan
Route::get('/jadwal', [ScheduleController::class, 'index'])->name('jadwal');

// Alur Booking (Fase 4)
Route::get('/booking/baru', [BookingController::class, 'create'])->name('booking.baru');
Route::post('/booking', [BookingController::class, 'store'])->middleware('throttle:10,1')->name('booking.store');

Route::middleware('auth')->group(function () {
    Route::get('/booking', [BookingController::class, 'myBookings'])->name('booking.saya');
    Route::get('/booking/{code}', [BookingController::class, 'show'])->name('booking.detail');
    Route::post('/booking/{code}/batal', [BookingController::class, 'cancel'])->name('booking.batal');
    Route::post('/booking/{code}/simulasi-lunas', [BookingController::class, 'simulasiLunas'])->name('booking.simulasi-lunas');
});

// Rute Tamu (Guest Only)
Route::middleware('guest')->group(function () {
    Route::get('/masuk', [AuthController::class, 'showLogin'])->name('masuk');
    Route::post('/masuk', [AuthController::class, 'login'])->name('masuk.post');

    Route::get('/daftar', [AuthController::class, 'showRegister'])->name('daftar');
    Route::post('/daftar', [AuthController::class, 'register'])->name('daftar.post');
});

// Keluar (Bisa diakses pengguna terotentikasi)
Route::post('/keluar', [AuthController::class, 'logout'])->name('keluar');

// Rute Pengguna Terautentikasi (/akun)
Route::middleware('auth')->group(function () {
    Route::get('/akun', [AccountController::class, 'index'])->name('akun');
    Route::put('/akun/profil', [AccountController::class, 'updateProfile'])->name('akun.profil');
    Route::put('/akun/password', [AccountController::class, 'updatePassword'])->name('akun.password');
});

// Area Admin (/admin) - Hanya untuk role:admin
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('dashboard');
});

// Area Owner (/owner) - Hanya untuk role:owner
Route::middleware(['auth', 'role:owner'])->prefix('owner')->name('owner.')->group(function () {
    Route::get('/', [OwnerController::class, 'index'])->name('dashboard');
});

// Halaman Pengembang: Katalog Komponen (Hanya Lokal, 404 di Produksi)
Route::get('/dev/komponen', function () {
    abort_unless(app()->environment('local'), 404);

    return view('pages.dev-komponen');
})->name('dev.komponen');
