@extends('layouts.app')

@section('title', 'Masuk - ' . config('gor.name'))

@section('content')
<main id="konten-utama" class="auth-page">
    <div class="kontainer">
        <div class="auth-grid">
            {{-- Kolom Kiri: Judul Raksasa & Keterangan --}}
            <div class="auth-left">
                <div class="pola-titik" style="position: absolute; inset: -20px; opacity: 0.25; pointer-events: none;" aria-hidden="true"></div>
                <div style="position: relative; z-index: 2;">
                    <x-penanda teks="AKUN PEMAIN" style="margin-bottom: var(--spasi-3);" />
                    <h1 class="auth-title">
                        Masuk dulu, baru booking.
                    </h1>
                    <p class="auth-desc">
                        Gunakan nomor HP atau email yang sudah terdaftar untuk melihat riwayat booking dan mengelola jadwal mainmu.
                    </p>
                </div>
            </div>

            {{-- Kolom Kanan: Form Murni tanpa kartu/kaca --}}
            <div class="auth-right">
                <form action="{{ route('masuk.post') }}" method="POST" novalidate>
                    @csrf

                    <div class="form-group">
                        <label for="login" class="form-label">Nomor HP atau email</label>
                        <input
                            type="text"
                            id="login"
                            name="login"
                            value="{{ old('login') }}"
                            class="form-input {{ $errors->has('login') ? 'has-error' : '' }}"
                            placeholder="Contoh: 08123456789 atau email@kamu.com"
                            autocomplete="username"
                            required
                            autofocus
                        >
                        @error('login')
                            <span class="form-error" role="alert">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="password" class="form-label">Password</label>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-input {{ $errors->has('password') ? 'has-error' : '' }}"
                            placeholder="Masukkan password akunmu"
                            autocomplete="current-password"
                            required
                        >
                        @error('password')
                            <span class="form-error" role="alert">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-checkbox-row">
                        <input
                            type="checkbox"
                            id="remember"
                            name="remember"
                            class="form-checkbox"
                            {{ old('remember') ? 'checked' : '' }}
                        >
                        <label for="remember" class="form-checkbox-label">Ingat saya di peramban ini</label>
                    </div>

                    <button type="submit" class="tombol tombol-solid" style="width: 100%; justify-content: center;">
                        Masuk ke Akun
                    </button>

                    <div class="form-footer-switch">
                        Baru pertama kali di sini?
                        <a href="{{ route('daftar') }}" class="form-footer-link">Daftar sekarang</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>
@endsection
