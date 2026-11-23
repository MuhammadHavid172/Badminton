<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Nama GOR
    |--------------------------------------------------------------------------
    | Nilai default "Nama GOR" jika GOR_NAME di file .env belum diisi.
    */
    'name' => env('GOR_NAME', 'Nama GOR'),

    /*
    |--------------------------------------------------------------------------
    | Kota Lokasi GOR
    |--------------------------------------------------------------------------
    | Boleh kosong. Jika bernilai null / string kosong, tidak akan ditampilkan di UI.
    */
    'city' => env('GOR_CITY') ?: null,

    /*
    |--------------------------------------------------------------------------
    | Jam Buka Operasional
    |--------------------------------------------------------------------------
    | Format teks jam operasional, bawaan "08.00-23.00".
    */
    'jam_buka' => env('GOR_JAM_BUKA', '08.00-23.00'),

    /*
    |--------------------------------------------------------------------------
    | Alamat Lengkap
    |--------------------------------------------------------------------------
    | Boleh kosong. Jika bernilai null / string kosong, tidak akan ditampilkan di UI.
    */
    'alamat' => env('GOR_ALAMAT') ?: null,

    /*
    |--------------------------------------------------------------------------
    | DAFTAR LAPANGAN (DATA AWAL / SEEDER)
    |--------------------------------------------------------------------------
    | PERINGATAN PENTING:
    | Lapangan adalah DATA dinamis dalam database, BUKAN angka tetap.
    | Jumlah dan nama lapangan diambil dari array di bawah ini sebagai placeholder.
    | SESUAIKAN DENGAN KONDISI LAPANGAN SEBENARNYA SEBELUM MENJALANKAN:
    |   php artisan migrate:fresh --seed
    | Jangan pernah mengasumsikan jumlah lapangan di kode mana pun!
    |--------------------------------------------------------------------------
    */
    'courts' => [
        ['name' => 'Lapangan 1', 'sort_order' => 1, 'is_active' => true],
        ['name' => 'Lapangan 2', 'sort_order' => 2, 'is_active' => true],
        ['name' => 'Lapangan 3', 'sort_order' => 3, 'is_active' => true],
    ],

    /*
    |--------------------------------------------------------------------------
    | SESI WAKTU BERMAIN PER HARI (DATA AWAL / SEEDER)
    |--------------------------------------------------------------------------
    | 4 Sesi bawaan operasional GOR. Satu "slot" = lapangan + tanggal + sesi.
    |--------------------------------------------------------------------------
    */
    'sessions' => [
        ['label' => 'Sesi 1', 'start_time' => '08:00', 'end_time' => '11:00', 'sort_order' => 1, 'is_active' => true],
        ['label' => 'Sesi 2', 'start_time' => '11:00', 'end_time' => '15:00', 'sort_order' => 2, 'is_active' => true],
        ['label' => 'Sesi 3', 'start_time' => '15:00', 'end_time' => '19:00', 'sort_order' => 3, 'is_active' => true],
        ['label' => 'Sesi 4', 'start_time' => '19:00', 'end_time' => '23:00', 'sort_order' => 4, 'is_active' => true],
    ],

    /*
    |--------------------------------------------------------------------------
    | Durasi Penahanan Slot (Menit)
    |--------------------------------------------------------------------------
    | Slot ditahan selama hold_minutes sebelum otomatis dilepas jika belum dibayar.
    */
    'hold_minutes' => (int) env('GOR_HOLD_MINUTES', 15),

    /*
    |--------------------------------------------------------------------------
    | Jumlah Pertemuan Paket Bulanan
    |--------------------------------------------------------------------------
    | Bulanan = 4 pertemuan mingguan berturut-turut pada hari, lapangan, dan sesi sama.
    */
    'package_meetings' => (int) env('GOR_PACKAGE_MEETINGS', 4),

    /*
    |--------------------------------------------------------------------------
    | Timeout Idle Sesi Pengguna (Menit)
    |--------------------------------------------------------------------------
    */
    'idle_timeout_minutes' => (int) env('GOR_IDLE_TIMEOUT_MINUTES', 60),

    /*
    |--------------------------------------------------------------------------
    | Driver Pembayaran
    |--------------------------------------------------------------------------
    | Bawaan: 'simulated' (simulasi arsitektur gateway yang bisa diganti nanti).
    */
    'payment_driver' => env('PAYMENT_DRIVER', 'simulated'),

    /*
    |--------------------------------------------------------------------------
    | Harga Sewa Lapangan (PLACEHOLDER DATA)
    |--------------------------------------------------------------------------
    | single: 200000 (PLACEHOLDER Harian)
    | package: 600000 (PLACEHOLDER Bulanan, 4 pertemuan mingguan)
    | is_placeholder: flag penanda harga contoh.
    */
    'harga' => [
        'single' => (int) env('GOR_HARGA_SINGLE', 200000),
        'package' => (int) env('GOR_HARGA_PACKAGE', 600000),
        'is_placeholder' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Informasi Kontak
    |--------------------------------------------------------------------------
    | whatsapp dan instagram bawaan KOSONG (null).
    | Elemen yang bergantung pada kontak hanya dirender jika nilai terisi.
    */
    'kontak' => [
        'whatsapp' => env('GOR_WHATSAPP') ?: null,
        'instagram' => env('GOR_INSTAGRAM') ?: null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Fitur Sistem
    |--------------------------------------------------------------------------
    */
    'fitur' => [
        'online_booking' => true,
        'simulated_payment' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Daftar Lapangan (Helper Teks Legacy)
    |--------------------------------------------------------------------------
    | Untuk kompatibilitas string sederhana jika ada kode lama yang memanggil.
    */
    'daftar_lapangan' => array_filter(
        array_map('trim', explode(',', (string) env('GOR_DAFTAR_LAPANGAN', '')))
    ),
];
