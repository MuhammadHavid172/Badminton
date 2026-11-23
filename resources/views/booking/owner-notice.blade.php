@extends('layouts.app')

@section('title', 'Akun Owner Hanya Untuk Memantau - ' . config('gor.name'))

@section('content')
<div class="kontainer" style="padding-top: var(--spasi-12); padding-bottom: var(--spasi-12);">
    <div style="max-width: 560px; margin: 0 auto; text-align: center;">
        <span class="badge-kategori badge-kategori-bulanan" style="margin-bottom: var(--spasi-3);">
            MODE PANTAU
        </span>

        <h1 style="font-family: var(--font-judul); font-size: 2rem; font-weight: 700; text-transform: uppercase; margin-bottom: var(--spasi-4); letter-spacing: var(--tracking-judul);">
            Akun Owner Hanya Untuk Memantau
        </h1>

        <p style="color: var(--teks-redup); margin-bottom: var(--spasi-6); line-height: 1.6;">
            Akun dengan peran Owner digunakan khusus untuk melihat laporan, okupansi, dan aktivitas operasional lapangan. Untuk membuat booking lapangan, silakan gunakan akun customer atau admin ya.
        </p>

        <div style="display: flex; gap: var(--spasi-3); justify-content: center; flex-wrap: wrap;">
            <a href="{{ route('jadwal') }}" class="tombol tombol-solid">
                Lihat Jadwal
            </a>
            <a href="{{ route('owner.dashboard') }}" class="tombol tombol-outline">
                Ke Dashboard Owner
            </a>
        </div>
    </div>
</div>
@endsection
