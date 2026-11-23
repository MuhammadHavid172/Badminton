@extends('layouts.app')

@section('title', 'Dashboard Admin - ' . config('gor.name'))

@section('content')
<main id="konten-utama" style="padding-top: calc(80px + var(--spasi-8)); padding-bottom: var(--spasi-16); min-height: 80vh;">
    <div class="kontainer">
        <x-penanda teks="AREA ADMIN" style="margin-bottom: var(--spasi-3);" />

        <h1 style="font-family: var(--font-judul); font-size: clamp(2.5rem, 5vw, 4rem); font-weight: 600; margin: 0 0 var(--spasi-3);">
            Dashboard Admin
        </h1>

        <p style="color: var(--teks-redup); max-width: 600px; font-size: 1.125rem; line-height: 1.5; margin-bottom: var(--spasi-8);">
            Pusat operasional lapangan, check-in kehadiran pemain, penutupan slot pemeliharaan, dan manajemen booking harian maupun bulanan.
        </p>

        <div style="border: 1px dashed var(--garis); border-radius: 20px; padding: var(--spasi-8); background-color: var(--hitam-2); text-align: center;">
            <span style="display: inline-block; padding: 4px 12px; border-radius: var(--radius-pil); background-color: rgba(218, 255, 4, 0.15); border: 1px solid var(--lime); color: var(--lime); font-size: 0.8125rem; font-weight: 700; text-transform: uppercase; margin-bottom: var(--spasi-2);">
                Segera Hadir di Fase 6
            </span>
            <h2 style="font-family: var(--font-judul); font-size: 1.5rem; color: var(--putih); margin: 0 0 var(--spasi-2);">
                Operasional Lapangan & Admin
            </h2>
            <p style="color: var(--teks-redup); max-width: 480px; margin: 0 auto var(--spasi-4); font-size: 0.9375rem;">
                Modul check-in kehadiran, pembatalan booking berbayar, dan kontrol slot sedang dalam antrean pengerjaan fase berikutnya.
            </p>
            <a href="{{ route('jadwal') }}" class="tombol tombol-solid">
                Lihat Jadwal Lapangan
            </a>
        </div>
    </div>
</main>
@endsection
