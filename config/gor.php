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
    | Durasi Penahanan Slot (Menit)
    |--------------------------------------------------------------------------
    | Slot ditahan selama hold_minutes sebelum otomatis dilepas jika belum dibayar.
    */
    'hold_minutes' => (int) env('GOR_HOLD_MINUTES', 15),

    /*
    |--------------------------------------------------------------------------
    | Harga Sewa Lapangan (PLACEHOLDER)
    |--------------------------------------------------------------------------
    | single: 200000 (PLACEHOLDER)
    | package: 600000 (PLACEHOLDER, 4 pertemuan mingguan)
    | is_placeholder: flag untuk menandai harga masih berupa placeholder contoh.
    */
    'harga' => [
        'single' => (int) env('GOR_HARGA_SINGLE', 200000), // PLACEHOLDER
        'package' => (int) env('GOR_HARGA_PACKAGE', 600000), // PLACEHOLDER
        'is_placeholder' => true, // PLACEHOLDER
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
    | Daftar Lapangan
    |--------------------------------------------------------------------------
    | Hanya untuk teks, array nama lapangan, bawaan KOSONG ([]).
    | Jika array kosong, bagian UI yang memakainya disembunyikan.
    */
    'daftar_lapangan' => array_filter(
        array_map('trim', explode(',', (string) env('GOR_DAFTAR_LAPANGAN', '')))
    ),
];
