@extends('layouts.app')

@section('title', 'Daftar Akun - ' . config('gor.name'))

@section('content')
<main id="konten-utama" class="auth-page">
    <div class="kontainer">
        <div class="auth-grid">
            {{-- Kolom Kiri: Judul Raksasa & Keterangan --}}
            <div class="auth-left">
                <div class="pola-titik" style="position: absolute; inset: -20px; opacity: 0.25; pointer-events: none;" aria-hidden="true"></div>
                <div style="position: relative; z-index: 2;">
                    <x-penanda teks="PENDAFTARAN CEPAT" style="margin-bottom: var(--spasi-3);" />
                    <h1 class="auth-title">
                        Baru pertama? Daftar sebentar.
                    </h1>
                    <p class="auth-desc">
                        Hanya butuh nama dan nomor HP aktif. Tanpa verifikasi ribet, kamu bisa langsung pilih lapangan dan booking slot.
                    </p>
                </div>
            </div>

            {{-- Kolom Kanan: Form Murni tanpa kartu/kaca --}}
            <div class="auth-right">
                <form action="{{ route('daftar.post') }}" method="POST" novalidate>
                    @csrf

                    <div class="form-group">
                        <label for="name" class="form-label">Nama lengkap</label>
                        <input
                            type="text"
                            id="name"
                            name="name"
                            value="{{ old('name') }}"
                            class="form-input {{ $errors->has('name') ? 'has-error' : '' }}"
                            placeholder="Masukkan nama lengkap"
                            autocomplete="name"
                            required
                            autofocus
                        >
                        @error('name')
                            <span class="form-error" role="alert">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="phone" class="form-label">Nomor HP</label>
                        <input
                            type="tel"
                            id="phone"
                            name="phone"
                            value="{{ old('phone') }}"
                            class="form-input {{ $errors->has('phone') ? 'has-error' : '' }}"
                            placeholder="08xxxxxxxxxx"
                            autocomplete="tel"
                            required
                        >
                        @error('phone')
                            <span class="form-error" role="alert">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="team_name" class="form-label">
                            Nama PB atau tim <span class="form-label-optional">(opsional)</span>
                        </label>
                        <input
                            type="text"
                            id="team_name"
                            name="team_name"
                            value="{{ old('team_name') }}"
                            class="form-input {{ $errors->has('team_name') ? 'has-error' : '' }}"
                            placeholder="Nama PB / tim"
                        >
                        @error('team_name')
                            <span class="form-error" role="alert">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="email" class="form-label">
                            Email <span class="form-label-optional">(opsional)</span>
                        </label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            value="{{ old('email') }}"
                            class="form-input {{ $errors->has('email') ? 'has-error' : '' }}"
                            placeholder="nama@email.com"
                            autocomplete="email"
                        >
                        @error('email')
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
                            placeholder="Minimal 8 karakter"
                            autocomplete="new-password"
                            required
                        >
                        @error('password')
                            <span class="form-error" role="alert">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="password_confirmation" class="form-label">Konfirmasi password</label>
                        <input
                            type="password"
                            id="password_confirmation"
                            name="password_confirmation"
                            class="form-input"
                            placeholder="Ulangi password"
                            autocomplete="new-password"
                            required
                        >
                    </div>

                    <button type="submit" class="tombol tombol-solid" style="width: 100%; justify-content: center; margin-top: var(--spasi-2);">
                        Buat Akun Sekarang
                    </button>

                    <div class="form-footer-switch">
                        Sudah punya akun?
                        <a href="{{ route('masuk') }}" class="form-footer-link">Masuk di sini</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>
@endsection
