@extends('layouts.admin')

@section('title', ($modePantau ? 'Daftar Pengguna (Mode Pantau)' : 'Manajemen Pengguna') . ' - ' . config('gor.name'))

@section('content')
<div class="kontainer" style="max-width: 1000px; padding-top: var(--spasi-4);">

    {{-- Banner Notifikasi --}}
    @if (session('status'))
        <div class="alert-box alert-box-sukses" style="margin-bottom: var(--spasi-4);" role="status">
            <span>{{ session('status') }}</span>
        </div>
    @endif
    @if (session('error'))
        <div class="alert-box alert-box-error" style="margin-bottom: var(--spasi-4);" role="alert">
            <span>{{ session('error') }}</span>
        </div>
    @endif
    @if ($errors->any())
        <div class="alert-box alert-box-error" style="margin-bottom: var(--spasi-4);" role="alert">
            <ul style="margin: 0; padding-left: 20px;">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Header Halaman --}}
    <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: var(--spasi-3); margin-bottom: var(--spasi-4);">
        <div>
            <p style="font-family: var(--font-mono); font-size: 0.75rem; text-transform: uppercase; color: var(--lime); letter-spacing: 0.05em; margin: 0 0 4px 0;">
                {{ $modePantau ? 'Data Pengguna Sistem' : 'Otoritas & Akses Pengguna' }}
            </p>
            <h1 style="font-size: clamp(1.5rem, 4vw, 2rem); margin: 0; line-height: 1.2;">
                Daftar Pengguna &amp; Akun
            </h1>
        </div>

        @if (!$modePantau)
            <div>
                <button type="button" class="tombol tombol-primer" style="min-height: 48px; padding: 0 var(--spasi-3);" onclick="document.getElementById('form-tambah-pengguna').scrollIntoView({behavior: 'smooth'});">
                    + Tambah Pengguna Baru
                </button>
            </div>
        @endif
    </div>

    {{-- Filter Bar --}}
    <div style="background-color: #171714; border: 1px solid var(--abu-garis); border-radius: var(--radius-md); padding: var(--spasi-4); margin-bottom: var(--spasi-4);">
        @php
            $formAction = $modePantau ? route('owner.pengguna.index') : route('admin.pengguna.index');
        @endphp
        <form method="GET" action="{{ $formAction }}" style="display: flex; flex-direction: column; gap: var(--spasi-3);">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: var(--spasi-2);">
                <div>
                    <label for="filter_role" class="form-label" style="display: block; margin-bottom: 2px; font-size: 0.75rem;">
                        Peran Akun
                    </label>
                    <select id="filter_role" name="role" class="form-input" style="width: 100%; min-height: 44px; background-color: #1A1A17; font-size: 0.8125rem;">
                        <option value="">Semua Peran</option>
                        <option value="customer" {{ request('role') === 'customer' ? 'selected' : '' }}>Customer (Pelanggan)</option>
                        <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Admin (Pengelola)</option>
                        <option value="owner" {{ request('role') === 'owner' ? 'selected' : '' }}>Owner (Pemilik)</option>
                    </select>
                </div>

                <div>
                    <label for="filter_status" class="form-label" style="display: block; margin-bottom: 2px; font-size: 0.75rem;">
                        Status Akun
                    </label>
                    <select id="filter_status" name="status" class="form-input" style="width: 100%; min-height: 44px; background-color: #1A1A17; font-size: 0.8125rem;">
                        <option value="">Semua Status</option>
                        <option value="aktif" {{ request('status') === 'aktif' ? 'selected' : '' }}>Aktif</option>
                        <option value="nonaktif" {{ request('status') === 'nonaktif' ? 'selected' : '' }}>Nonaktif</option>
                    </select>
                </div>

                <div style="grid-column: span 2;">
                    <label for="filter_q" class="form-label" style="display: block; margin-bottom: 2px; font-size: 0.75rem;">
                        Cari Kata Kunci
                    </label>
                    <div style="display: flex; gap: var(--spasi-2);">
                        <input type="text"
                               id="filter_q"
                               name="q"
                               value="{{ request('q') }}"
                               placeholder="Cari nama, nomor HP, email, atau nama tim..."
                               class="form-input"
                               style="flex: 1; min-height: 44px; background-color: #1A1A17; font-size: 0.875rem;">
                        <button type="submit" class="tombol tombol-primer" style="min-height: 44px; padding: 0 var(--spasi-3);">
                            Cari
                        </button>
                        @if (request()->hasAny(['role', 'status', 'q']))
                            <a href="{{ $formAction }}" class="tombol tombol-sekunder" style="min-height: 44px; padding: 0 var(--spasi-3); color: var(--teks-redup);">
                                Reset
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </form>
    </div>

    {{-- Tabel / Daftar Bertumpuk Pengguna --}}
    @if ($users->isEmpty())
        <div style="background-color: #171714; border: 1px solid var(--abu-garis); border-radius: var(--radius-md); padding: var(--spasi-6); text-align: center; margin-bottom: var(--spasi-5);">
            <p style="color: var(--teks-redup); margin: 0; font-size: 1rem;">
                Tidak ada data pengguna yang sesuai dengan filter atau kata kunci pencarian.
            </p>
        </div>
    @else
        {{-- Versi Desktop: Tabel Rapat Garis Tipis --}}
        <div class="admin-table-desktop" style="background-color: #171714; border: 1px solid var(--abu-garis); border-radius: var(--radius-md); overflow: hidden; margin-bottom: var(--spasi-4);">
            <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.875rem;">
                <thead>
                    <tr style="border-bottom: 1px solid var(--abu-garis); background-color: #1C1C18; font-family: var(--font-mono); font-size: 0.6875rem; text-transform: uppercase; color: var(--teks-redup);">
                        <th style="padding: 12px 16px;">Nama / Identitas</th>
                        <th style="padding: 12px 16px;">Kontak &amp; Tim</th>
                        <th style="padding: 12px 16px;">Peran</th>
                        <th style="padding: 12px 16px;">Status</th>
                        <th style="padding: 12px 16px;">Booking</th>
                        <th style="padding: 12px 16px; text-align: right;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $u)
                        @php
                            $detailRoute = $modePantau ? route('owner.pengguna.show', $u->id) : route('admin.pengguna.show', $u->id);
                        @endphp
                        <tr style="border-bottom: 1px solid var(--abu-garis);">
                            <td style="padding: 12px 16px;">
                                <a href="{{ $detailRoute }}" style="font-weight: 700; color: var(--putih); text-decoration: underline;">
                                    {{ $u->name }}
                                </a>
                                @if ($u->id === auth()->id())
                                    <span style="font-family: var(--font-mono); font-size: 0.6875rem; color: var(--lime); margin-left: 4px;">(Kamu)</span>
                                @endif
                                <div style="font-family: var(--font-mono); font-size: 0.6875rem; color: var(--teks-redup); margin-top: 2px;">
                                    ID #{{ $u->id }} &middot; Gabung {{ $u->created_at->format('d/m/Y') }}
                                </div>
                            </td>
                            <td style="padding: 12px 16px;">
                                <div style="font-family: var(--font-mono); font-size: 0.8125rem;">
                                    <a href="tel:{{ $u->phone }}" style="color: var(--lime); text-decoration: underline;">
                                        {{ $u->phone }}
                                    </a>
                                </div>
                                @if ($u->email)
                                    <div style="font-size: 0.75rem; color: var(--teks-redup);">
                                        {{ $u->email }}
                                    </div>
                                @endif
                                @if ($u->team_name)
                                    <div style="font-size: 0.75rem; color: var(--teks-redup);">
                                        Tim: {{ $u->team_name }}
                                    </div>
                                @endif
                            </td>
                            <td style="padding: 12px 16px;">
                                <span style="font-family: var(--font-mono); font-size: 0.75rem; font-weight: 700; text-transform: uppercase; padding: 2px 8px; border-radius: 3px;
                                    background-color: {{ $u->isAdmin() ? 'rgba(218, 255, 4, 0.15)' : ($u->isOwner() ? 'rgba(255, 176, 32, 0.15)' : '#2E2E28') }};
                                    color: {{ $u->isAdmin() ? 'var(--lime)' : ($u->isOwner() ? '#FFB020' : 'var(--putih)') }};">
                                    {{ $u->role->label() }}
                                </span>
                            </td>
                            <td style="padding: 12px 16px;">
                                @if ($u->is_active)
                                    <span style="font-family: var(--font-mono); font-size: 0.75rem; color: var(--lime);">
                                        Aktif
                                    </span>
                                @else
                                    <span style="font-family: var(--font-mono); font-size: 0.75rem; color: var(--merah);">
                                        Nonaktif
                                    </span>
                                @endif
                            </td>
                            <td style="padding: 12px 16px; font-family: var(--font-mono); font-size: 0.8125rem; color: var(--teks-redup);">
                                {{ $u->bookings_count }}x
                            </td>
                            <td style="padding: 12px 16px; text-align: right;">
                                <a href="{{ $detailRoute }}" class="tombol tombol-sekunder" style="min-height: 38px; padding: 4px 12px; font-size: 0.8125rem;">
                                    Detail
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Versi Mobile: Daftar Bertumpuk --}}
        <div class="admin-list-mobile" style="display: flex; flex-direction: column; gap: var(--spasi-3); margin-bottom: var(--spasi-4);">
            @foreach ($users as $u)
                @php
                    $detailRoute = $modePantau ? route('owner.pengguna.show', $u->id) : route('admin.pengguna.show', $u->id);
                @endphp
                <div style="background-color: #171714; border: 1px solid var(--abu-garis); border-radius: var(--radius-sm); padding: var(--spasi-3); display: flex; flex-direction: column; gap: var(--spasi-2);">
                    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--abu-garis); padding-bottom: var(--spasi-2);">
                        <div>
                            <a href="{{ $detailRoute }}" style="font-weight: 700; color: var(--putih); text-decoration: underline;">
                                {{ $u->name }}
                            </a>
                            @if ($u->id === auth()->id())
                                <span style="font-family: var(--font-mono); font-size: 0.6875rem; color: var(--lime);">(Kamu)</span>
                            @endif
                        </div>
                        <span style="font-family: var(--font-mono); font-size: 0.75rem; font-weight: 700; color: {{ $u->isAdmin() ? 'var(--lime)' : ($u->isOwner() ? '#FFB020' : 'var(--putih)') }};">
                            {{ $u->role->label() }}
                        </span>
                    </div>

                    <div style="display: flex; justify-content: space-between; font-size: 0.875rem;">
                        <span style="color: var(--teks-redup); font-family: var(--font-mono); font-size: 0.75rem;">HP / TIM</span>
                        <div style="text-align: right;">
                            <div><a href="tel:{{ $u->phone }}" style="color: var(--lime); font-family: var(--font-mono); font-size: 0.8125rem;">{{ $u->phone }}</a></div>
                            @if ($u->team_name)
                                <div style="font-size: 0.75rem; color: var(--teks-redup);">{{ $u->team_name }}</div>
                            @endif
                        </div>
                    </div>

                    <div style="display: flex; justify-content: space-between; font-size: 0.875rem;">
                        <span style="color: var(--teks-redup); font-family: var(--font-mono); font-size: 0.75rem;">STATUS / RIWAYAT</span>
                        <span style="font-family: var(--font-mono); font-size: 0.8125rem; color: {{ $u->is_active ? 'var(--lime)' : 'var(--merah)' }};">
                            {{ $u->is_active ? 'Aktif' : 'Nonaktif' }} &middot; {{ $u->bookings_count }} booking
                        </span>
                    </div>

                    <div>
                        <a href="{{ $detailRoute }}" class="tombol tombol-sekunder" style="width: 100%; min-height: 48px; font-size: 0.875rem;">
                            Buka Detail &amp; Riwayat
                        </a>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Paginasi --}}
        <div style="margin-bottom: var(--spasi-6);">
            {{ $users->links() }}
        </div>
    @endif

    {{-- Form Tambah Pengguna Baru (Khusus Admin, Tidak Dirender di Mode Pantau) --}}
    @if (!$modePantau)
        <div id="form-tambah-pengguna" style="background-color: #171714; border: 1px solid var(--abu-garis); border-radius: var(--radius-md); padding: var(--spasi-4); margin-bottom: var(--spasi-5);">
            <div style="border-bottom: 1px solid var(--abu-garis); padding-bottom: var(--spasi-2); margin-bottom: var(--spasi-3);">
                <h2 style="font-size: 1.125rem; margin: 0 0 2px 0;">
                    Tambah Pengguna Baru
                </h2>
                <p style="font-size: 0.8125rem; color: var(--teks-redup); margin: 0;">
                    Buat akun baru untuk peran Admin, Owner, atau Customer secara manual.
                </p>
            </div>

            <form action="{{ route('admin.pengguna.store') }}" method="POST" style="display: flex; flex-direction: column; gap: var(--spasi-3);">
                @csrf

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: var(--spasi-3);">
                    <div>
                        <label for="new_name" class="form-label" style="display: block; margin-bottom: 4px;">
                            Nama Lengkap <span style="color: var(--merah);">*</span>
                        </label>
                        <input type="text"
                               id="new_name"
                               name="name"
                               required
                               placeholder="Masukkan nama lengkap"
                               class="form-input"
                               style="width: 100%; min-height: 48px; background-color: #1A1A17;"
                               value="{{ old('name') }}">
                    </div>

                    <div>
                        <label for="new_phone" class="form-label" style="display: block; margin-bottom: 4px;">
                            Nomor HP / WhatsApp <span style="color: var(--merah);">*</span>
                        </label>
                        <input type="text"
                               id="new_phone"
                               name="phone"
                               required
                               placeholder="08xxxxxxxxxx"
                               class="form-input"
                               style="width: 100%; min-height: 48px; background-color: #1A1A17;"
                               value="{{ old('phone') }}">
                    </div>

                    <div>
                        <label for="new_email" class="form-label" style="display: block; margin-bottom: 4px;">
                            Email (Opsional)
                        </label>
                        <input type="email"
                               id="new_email"
                               name="email"
                               placeholder="alamat@email.com (opsional)"
                               class="form-input"
                               style="width: 100%; min-height: 48px; background-color: #1A1A17;"
                               value="{{ old('email') }}">
                    </div>

                    <div>
                        <label for="new_team_name" class="form-label" style="display: block; margin-bottom: 4px;">
                            Nama PB / Tim (Opsional)
                        </label>
                        <input type="text"
                               id="new_team_name"
                               name="team_name"
                               placeholder="Nama PB atau tim (opsional)"
                               class="form-input"
                               style="width: 100%; min-height: 48px; background-color: #1A1A17;"
                               value="{{ old('team_name') }}">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: var(--spasi-3);">
                    <div>
                        <label for="new_role" class="form-label" style="display: block; margin-bottom: 4px;">
                            Pilih Peran Akun <span style="color: var(--merah);">*</span>
                        </label>
                        <select id="new_role" name="role" required class="form-input" style="width: 100%; min-height: 48px; background-color: #1A1A17;">
                            <option value="customer" {{ old('role') === 'customer' ? 'selected' : '' }}>Customer (Pelanggan)</option>
                            <option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }}>Admin (Pengelola)</option>
                            <option value="owner" {{ old('role') === 'owner' ? 'selected' : '' }}>Owner (Pemilik)</option>
                        </select>
                    </div>

                    <div>
                        <label for="new_password" class="form-label" style="display: block; margin-bottom: 4px;">
                            Password Baru (Min. 8 Karakter) <span style="color: var(--merah);">*</span>
                        </label>
                        <input type="password"
                               id="new_password"
                               name="password"
                               required
                               class="form-input"
                               style="width: 100%; min-height: 48px; background-color: #1A1A17;">
                    </div>

                    <div>
                        <label for="new_password_confirmation" class="form-label" style="display: block; margin-bottom: 4px;">
                            Konfirmasi Password <span style="color: var(--merah);">*</span>
                        </label>
                        <input type="password"
                               id="new_password_confirmation"
                               name="password_confirmation"
                               required
                               class="form-input"
                               style="width: 100%; min-height: 48px; background-color: #1A1A17;">
                    </div>
                </div>

                <div style="margin-top: var(--spasi-2);">
                    <button type="submit" class="tombol tombol-primer" style="min-height: 48px; padding: 0 24px; font-weight: 700;">
                        Simpan Pengguna Baru
                    </button>
                </div>
            </form>
        </div>
    @endif

</div>
@endsection
