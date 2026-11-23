@extends('layouts.app')

@section('title', 'Akses Dibatasi (403) - ' . config('gor.name'))

@section('content')
<main id="konten-utama" style="padding-top: calc(80px + var(--spasi-12)); padding-bottom: var(--spasi-16); min-height: 80vh; display: flex; align-items: center;">
    <div class="kontainer" style="text-align: center; max-width: 640px;">
        <span style="display: inline-block; font-family: var(--font-judul); font-size: clamp(3rem, 8vw, 6rem); font-weight: 700; color: var(--lime); line-height: 1; margin-bottom: var(--spasi-2);">
            403
        </span>

        <h1 style="font-family: var(--font-judul); font-size: clamp(2rem, 4vw, 3rem); font-weight: 600; margin: 0 0 var(--spasi-3);">
            Akses Dibatasi
        </h1>

        <p style="color: var(--teks-redup); font-size: 1.125rem; line-height: 1.5; margin-bottom: var(--spasi-6);">
            Kamu tidak memiliki izin untuk membuka halaman ini. Pastikan kamu masuk dengan akun yang memiliki hak akses yang sesuai.
        </p>

        <div style="display: flex; justify-content: center; gap: var(--spasi-3); flex-wrap: wrap;">
            <a href="{{ route('beranda') }}" class="tombol tombol-solid">
                Kembali ke Beranda
            </a>
            <a href="{{ route('jadwal') }}" class="tombol tombol-outline">
                Lihat Jadwal
            </a>
        </div>
    </div>
</main>
@endsection
