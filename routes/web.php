<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\NotificationController;
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

// Alur Booking & Pembayaran (Fase 4 & 5)
Route::get('/booking/baru', [BookingController::class, 'create'])->name('booking.baru');
Route::post('/booking', [BookingController::class, 'store'])->middleware('throttle:10,1')->name('booking.store');

Route::middleware('auth')->group(function () {
    Route::get('/booking', [BookingController::class, 'myBookings'])->name('booking.saya');
    Route::get('/booking/{code}', [BookingController::class, 'show'])->name('booking.detail');
    Route::get('/booking/{code}/bayar', [BookingController::class, 'pay'])->name('booking.bayar');
    Route::post('/booking/{code}/bayar/proses', [BookingController::class, 'processPayment'])->name('booking.bayar.proses');
    Route::post('/booking/{code}/bayar/gagal', [BookingController::class, 'failPayment'])->name('booking.bayar.gagal');
    Route::post('/booking/{code}/perpanjang', [BookingController::class, 'perpanjang'])->name('booking.perpanjang');
    Route::post('/booking/{code}/batal', [BookingController::class, 'cancel'])->name('booking.batal');
    Route::post('/booking/{code}/simulasi-lunas', [BookingController::class, 'simulasiLunas'])->name('booking.simulasi-lunas');

    // Notifikasi In-App (Fase 5)
    Route::get('/notifikasi', [NotificationController::class, 'index'])->name('notifikasi.index');
    Route::post('/notifikasi/baca-semua', [NotificationController::class, 'markAllAsRead'])->name('notifikasi.baca-semua');
    Route::get('/notifikasi/{id}', [NotificationController::class, 'markAsRead'])->name('notifikasi.baca');
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
    Route::get('/ringkasan', [AdminController::class, 'ringkasan'])->name('ringkasan');
    Route::post('/slot/{id}/check-in', [AdminController::class, 'checkIn'])->name('slot.check-in');
    Route::post('/slot/{id}/batal-check-in', [AdminController::class, 'cancelCheckIn'])->name('slot.batal-check-in');

    Route::get('/jatah-habis', [AdminController::class, 'jatahHabis'])->name('jatah-habis');
    Route::get('/booking-langsung', [AdminController::class, 'bookingLangsungForm'])->name('booking-langsung');
    Route::post('/booking-langsung', [AdminController::class, 'bookingLangsungStore'])->name('booking-langsung.store');

    Route::get('/tutup-slot', [AdminController::class, 'tutupSlotForm'])->name('tutup-slot');
    Route::post('/tutup-slot', [AdminController::class, 'tutupSlotStore'])->name('tutup-slot.store');
    Route::delete('/tutup-slot/{id}', [AdminController::class, 'bukaSlot'])->name('tutup-slot.destroy');

    Route::get('/booking', [AdminController::class, 'daftarBooking'])->name('booking.index');
    Route::get('/booking/ekspor-csv', [AdminController::class, 'eksporCsv'])->name('booking.ekspor-csv');
    Route::get('/booking/{code}', [AdminController::class, 'showBooking'])->name('booking.detail');
    Route::post('/booking/{code}/batal', [AdminController::class, 'cancelBooking'])->name('booking.batal');
    Route::post('/booking/{code}/perpanjang-atas-nama', [AdminController::class, 'perpanjangAtasNama'])->name('booking.perpanjang-atas-nama');

    // Manajemen Pengguna (Fase 7)
    Route::get('/pengguna', [AdminController::class, 'pengguna'])->name('pengguna.index');
    Route::post('/pengguna', [AdminController::class, 'tambahPengguna'])->name('pengguna.store');
    Route::get('/pengguna/{id}', [AdminController::class, 'showPengguna'])->name('pengguna.show');
    Route::put('/pengguna/{id}/peran', [AdminController::class, 'ubahPeranPengguna'])->name('pengguna.peran');
    Route::put('/pengguna/{id}/toggle-status', [AdminController::class, 'toggleStatusPengguna'])->name('pengguna.toggle-status');
    Route::post('/pengguna/{id}/reset-password', [AdminController::class, 'resetPasswordPengguna'])->name('pengguna.reset-password');

    // Log Aktivitas (Fase 7)
    Route::get('/aktivitas', [AdminController::class, 'aktivitas'])->name('aktivitas');

    // Laporan & Ekspor Transaksi (Fase 7)
    Route::get('/laporan', [AdminController::class, 'laporan'])->name('laporan');
    Route::get('/laporan/ekspor-csv', [AdminController::class, 'laporanEksporCsv'])->name('laporan.ekspor-csv');

    // Pengaturan Operasional (Fase 7)
    Route::get('/pengaturan', [AdminController::class, 'pengaturan'])->name('pengaturan');
    Route::put('/pengaturan', [AdminController::class, 'updatePengaturan'])->name('pengaturan.update');
});

// Area Owner (/owner) - Mode Pantau (Hanya Lihat) - Hanya untuk role:owner (Fase 7)
Route::middleware(['auth', 'role:owner'])->prefix('owner')->name('owner.')->group(function () {
    Route::get('/', [OwnerController::class, 'index'])->name('dashboard');
    Route::get('/ringkasan', [OwnerController::class, 'ringkasan'])->name('ringkasan');
    Route::get('/hari-ini', [OwnerController::class, 'hariIni'])->name('hari-ini');
    Route::get('/jatah-habis', [OwnerController::class, 'jatahHabis'])->name('jatah-habis');
    Route::get('/booking', [OwnerController::class, 'booking'])->name('booking.index');
    Route::get('/booking/{code}', [OwnerController::class, 'showBooking'])->name('booking.detail');
    Route::get('/pengguna', [OwnerController::class, 'pengguna'])->name('pengguna.index');
    Route::get('/pengguna/{id}', [OwnerController::class, 'showPengguna'])->name('pengguna.show');
    Route::get('/aktivitas', [OwnerController::class, 'aktivitas'])->name('aktivitas');
    Route::get('/laporan', [OwnerController::class, 'laporan'])->name('laporan');
    Route::get('/laporan/ekspor-csv', [OwnerController::class, 'laporanEksporCsv'])->name('laporan.ekspor-csv');
    Route::get('/pengaturan', [OwnerController::class, 'pengaturan'])->name('pengaturan');
});

// Halaman Pengembang: Katalog Komponen (Hanya Lokal, 404 di Produksi)
Route::get('/dev/komponen', function () {
    abort_unless(app()->environment('local'), 404);

    return view('pages.dev-komponen');
})->name('dev.komponen');
