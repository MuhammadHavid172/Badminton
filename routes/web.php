<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - Website Booking Lapangan Badminton (Tahap 1: Tampilan)
|--------------------------------------------------------------------------
| Seluruh tautan terhubung ke rute yang valid. Halaman fungsional masa depan
| menampilkan halaman "Segera hadir" dengan gaya sistem desain.
*/

// Beranda & Landing Page Lengkap
Route::get('/', function () {
    return view('landing');
})->name('beranda');

// Halaman Sementara: Jadwal
Route::get('/jadwal', function () {
    return view('pages.segera-hadir', [
        'judul' => 'Jadwal Lapangan',
        'keterangan' => 'Jadwal interaktif dan ketersediaan slot lapangan sedang disiapkan untuk tahap berikutnya.',
    ]);
})->name('jadwal');

// Halaman Sementara: Masuk
Route::get('/masuk', function () {
    return view('pages.segera-hadir', [
        'judul' => 'Masuk ke Akun',
        'keterangan' => 'Layanan login dan autentikasi akun pemain sedang disiapkan untuk tahap berikutnya.',
    ]);
})->name('masuk');

// Halaman Sementara: Daftar
Route::get('/daftar', function () {
    return view('pages.segera-hadir', [
        'judul' => 'Daftar Akun',
        'keterangan' => 'Pendaftaran akun member dan pemain sedang disiapkan untuk tahap berikutnya.',
    ]);
})->name('daftar');

// Halaman Pengembang: Katalog Komponen (Hanya Lokal, 404 di Produksi)
Route::get('/dev/komponen', function () {
    abort_unless(app()->environment('local'), 404);

    return view('pages.dev-komponen');
})->name('dev.komponen');
