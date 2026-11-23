@extends('layouts.app')

@section('title', 'Akun Saya - ' . config('gor.name'))

@section('content')
<main id="konten-utama" class="akun-page">
    <div class="kontainer">
        <header class="akun-header">
            <x-penanda teks="PROFIL PENGGUNA" style="margin-bottom: var(--spasi-2);" />
            <div style="display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: var(--spasi-2);">
                <div>
                    <h1 style="font-family: var(--font-judul); font-size: clamp(2rem, 4vw, 3rem); font-weight: 600; margin: 0 0 6px;">
                        {{ $user->name }}
                    </h1>
                    <span style="font-size: 0.9375rem; color: var(--teks-redup);">
                        Terdaftar sejak {{ tanggal_indonesia($user->created_at, false, false) }}
                    </span>
                </div>

                <div>
                    <span style="display: inline-block; padding: 6px 14px; border-radius: var(--radius-pil); background-color: var(--hitam-2); border: 1px solid var(--garis); font-size: 0.8125rem; font-weight: 600; text-transform: uppercase; color: var(--lime);">
                        Peran: {{ $user->role->label() }}
                    </span>
                </div>
            </div>
        </header>

        <div class="akun-grid">
            {{-- Bagian 1: Ubah Data Profil --}}
            <section class="akun-card-section" aria-labelledby="judul-profil">
                <h2 id="judul-profil" style="font-family: var(--font-judul); font-size: 1.5rem; font-weight: 600; margin: 0 0 var(--spasi-4);">
                    Informasi Profil
                </h2>

                @if (session('status_profile'))
                    <div class="alert-success" role="status">
                        {{ session('status_profile') }}
                    </div>
                @endif

                <form action="{{ route('akun.profil') }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="form-group">
                        <label for="name" class="form-label">Nama lengkap</label>
                        <input
                            type="text"
                            id="name"
                            name="name"
                            value="{{ old('name', $user->name) }}"
                            class="form-input {{ $errors->has('name') ? 'has-error' : '' }}"
                            required
                        >
                        @error('name')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="phone_display" class="form-label">Nomor HP</label>
                        <input
                            type="text"
                            id="phone_display"
                            value="{{ $user->phone }}"
                            class="form-input"
                            style="opacity: 0.65; cursor: not-allowed;"
                            readonly
                            aria-describedby="phone-hint"
                        >
                        <span id="phone-hint" style="font-size: 0.75rem; color: var(--teks-redup); margin-top: 4px;">
                            Nomor HP merupakan identitas utama akun. Hubungi admin jika ingin mengubahnya.
                        </span>
                    </div>

                    <div class="form-group">
                        <label for="team_name" class="form-label">
                            Nama PB atau tim <span class="form-label-optional">(opsional)</span>
                        </label>
                        <input
                            type="text"
                            id="team_name"
                            name="team_name"
                            value="{{ old('team_name', $user->team_name) }}"
                            class="form-input {{ $errors->has('team_name') ? 'has-error' : '' }}"
                            placeholder="Contoh: PB Rajawali"
                        >
                        @error('team_name')
                            <span class="form-error">{{ $message }}</span>
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
                            value="{{ old('email', $user->email) }}"
                            class="form-input {{ $errors->has('email') ? 'has-error' : '' }}"
                            placeholder="email@kamu.com"
                        >
                        @error('email')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <button type="submit" class="tombol tombol-solid" style="margin-top: var(--spasi-2);">
                        Simpan Perubahan
                    </button>
                </form>
            </section>

            {{-- Bagian 2: Ubah Password --}}
            <section class="akun-card-section" aria-labelledby="judul-password">
                <h2 id="judul-password" style="font-family: var(--font-judul); font-size: 1.5rem; font-weight: 600; margin: 0 0 var(--spasi-4);">
                    Ubah Password
                </h2>

                @if (session('status_password'))
                    <div class="alert-success" role="status">
                        {{ session('status_password') }}
                    </div>
                @endif

                <form action="{{ route('akun.password') }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="form-group">
                        <label for="current_password" class="form-label">Password saat ini</label>
                        <input
                            type="password"
                            id="current_password"
                            name="current_password"
                            class="form-input {{ $errors->has('current_password') ? 'has-error' : '' }}"
                            placeholder="Masukkan password lama"
                            required
                        >
                        @error('current_password')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="password" class="form-label">Password baru</label>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-input {{ $errors->has('password') ? 'has-error' : '' }}"
                            placeholder="Minimal 8 karakter"
                            required
                        >
                        @error('password')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="password_confirmation" class="form-label">Konfirmasi password baru</label>
                        <input
                            type="password"
                            id="password_confirmation"
                            name="password_confirmation"
                            class="form-input"
                            placeholder="Ulangi password baru"
                            required
                        >
                    </div>

                    <button type="submit" class="tombol tombol-solid" style="margin-top: var(--spasi-2);">
                        Perbarui Password
                    </button>
                </form>
            </section>
        </div>
    </div>
</main>
@endsection
