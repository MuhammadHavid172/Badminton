@extends('layouts.app')

@section('title', $judul . ' - ' . config('gor.name'))

@section('content')
<section style="min-height: 80vh; display: flex; align-items: center; justify-content: center; padding: 120px 0 var(--spasi-10); position: relative;" class="pola-titik">
    <div class="kontainer" style="text-align: left; max-width: 800px;">
        <x-penanda teks="TAHAP PENGEMBANGAN" style="margin-bottom: var(--spasi-4);" />
        
        <h1 style="font-size: clamp(2.5rem, 6vw, 4.5rem); margin-bottom: var(--spasi-3);">
            {{ $judul }}
        </h1>
        
        <p style="font-size: 1.125rem; max-width: 580px; margin-bottom: var(--spasi-6); color: var(--teks-redup);">
            {{ $keterangan }}
        </p>
        
        <div style="display: flex; gap: var(--spasi-3); flex-wrap: wrap;">
            <a href="{{ url('/') }}" class="tombol tombol-solid">
                Kembali ke Beranda
            </a>
            <x-cta href="{{ url('/#cara-booking') }}" teks="PELAJARI CARA BOOKING" />
        </div>
    </div>
</section>
@endsection
