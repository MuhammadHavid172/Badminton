@extends('layouts.app')

@section('title', 'Mode Pantau Owner - ' . config('gor.name'))

@section('content')
<main id="konten-utama" style="padding-top: calc(80px + var(--spasi-8)); padding-bottom: var(--spasi-16); min-height: 80vh;">
    <div class="kontainer">
        <div style="display: flex; align-items: center; gap: var(--spasi-2); margin-bottom: var(--spasi-3);">
            <x-penanda teks="PEMILIK GOR" />
            <span style="display: inline-block; padding: 4px 10px; border-radius: var(--radius-pil); background-color: #2A2A25; border: 1px solid var(--garis); font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: var(--putih);">
                Mode Pantau (Hanya Lihat)
            </span>
        </div>

        <h1 style="font-family: var(--font-judul); font-size: clamp(2.5rem, 5vw, 4rem); font-weight: 600; margin: 0 0 var(--spasi-3);">
            Ringkasan Pemilik
        </h1>

        <p style="color: var(--teks-redup); max-width: 600px; font-size: 1.125rem; line-height: 1.5; margin-bottom: var(--spasi-8);">
            Pemantauan langsung pendapatan, okupansi slot lapangan, data pengguna, dan laporan operasional tanpa akses pengubahan data.
        </p>

        <div style="border: 1px dashed var(--garis); border-radius: 20px; padding: var(--spasi-8); background-color: var(--hitam-2); text-align: center;">
            <span style="display: inline-block; padding: 4px 12px; border-radius: var(--radius-pil); background-color: rgba(255, 255, 255, 0.1); border: 1px solid var(--garis); color: var(--putih); font-size: 0.8125rem; font-weight: 700; text-transform: uppercase; margin-bottom: var(--spasi-2);">
                Segera Hadir di Fase 7
            </span>
            <h2 style="font-family: var(--font-judul); font-size: 1.5rem; color: var(--putih); margin: 0 0 var(--spasi-2);">
                Dasbor Pemantauan Pemilik
            </h2>
            <p style="color: var(--teks-redup); max-width: 480px; margin: 0 auto var(--spasi-4); font-size: 0.9375rem;">
                Grafik batang SVG okupansi dan statistik pendapatan zona Jayapura akan disiapkan secara terisolasi pada Fase 7.
            </p>
            <a href="{{ route('jadwal') }}" class="tombol tombol-solid">
                Lihat Jadwal Lapangan
            </a>
        </div>
    </div>
</main>
@endsection
