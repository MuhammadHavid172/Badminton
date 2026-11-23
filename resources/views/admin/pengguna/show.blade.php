@extends('layouts.admin')

@section('title', 'Detail Pengguna: ' . $user->name . ' - ' . config('gor.name'))

@section('content')
<div class="kontainer" style="max-width: 900px; padding-top: var(--spasi-4);">

    {{-- Breadcrumb --}}
    <div style="margin-bottom: var(--spasi-4);">
        @php
            $backRoute = $modePantau ? route('owner.pengguna.index') : route('admin.pengguna.index');
        @endphp
        <a href="{{ $backRoute }}" class="tombol tombol-sekunder" style="min-height: 40px; font-size: 0.8125rem;">
            &larr; Kembali ke Daftar Pengguna
        </a>
    </div>

    {{-- Banner Status Notifikasi --}}
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

    {{-- Kartu Profil Pengguna --}}
    <div style="background-color: #171714; border: 1px solid var(--abu-garis); border-radius: var(--radius-md); padding: var(--spasi-4); margin-bottom: var(--spasi-5);">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: var(--spasi-3); border-bottom: 1px solid var(--abu-garis); padding-bottom: var(--spasi-3); margin-bottom: var(--spasi-3);">
            <div>
                <div style="display: flex; align-items: center; gap: var(--spasi-2); margin-bottom: 4px;">
                    <h1 style="font-size: clamp(1.25rem, 3vw, 1.75rem); margin: 0; line-height: 1.2;">
                        {{ $user->name }}
                    </h1>
                    @if ($user->id === auth()->id())
                        <span style="font-family: var(--font-mono); font-size: 0.75rem; color: var(--lime); font-weight: 700;">(Akun Kamu)</span>
                    @endif
                </div>
                <p style="font-family: var(--font-mono); font-size: 0.75rem; color: var(--teks-redup); margin: 0;">
                    ID Pengguna #{{ $user->id }} &middot; Terdaftar sejak {{ tanggal_indonesia($user->created_at->format('Y-m-d'), false, true) }}
                </p>
            </div>

            <div style="display: flex; align-items: center; gap: var(--spasi-2);">
                <span style="font-family: var(--font-mono); font-size: 0.8125rem; font-weight: 700; text-transform: uppercase; padding: 4px 10px; border-radius: 4px;
                    background-color: {{ $user->isAdmin() ? 'rgba(218, 255, 4, 0.15)' : ($user->isOwner() ? 'rgba(255, 176, 32, 0.15)' : '#2E2E28') }};
                    color: {{ $user->isAdmin() ? 'var(--lime)' : ($user->isOwner() ? '#FFB020' : 'var(--putih)') }};">
                    {{ $user->role->label() }}
                </span>
                <span style="font-family: var(--font-mono); font-size: 0.8125rem; font-weight: 700; padding: 4px 10px; border-radius: 4px;
                    background-color: {{ $user->is_active ? 'rgba(218, 255, 4, 0.1)' : 'rgba(255, 90, 77, 0.1)' }};
                    color: {{ $user->is_active ? 'var(--lime)' : 'var(--merah)' }};">
                    {{ $user->is_active ? 'AKTIF' : 'NONAKTIF' }}
                </span>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: var(--spasi-3);">
            <div>
                <span style="font-family: var(--font-mono); font-size: 0.6875rem; text-transform: uppercase; color: var(--teks-redup); display: block;">
                    Nomor Handphone
                </span>
                <span style="font-family: var(--font-mono); font-size: 0.9375rem;">
                    <a href="tel:{{ $user->phone }}" style="color: var(--lime); text-decoration: underline;">
                        {{ $user->phone }}
                    </a>
                </span>
            </div>

            <div>
                <span style="font-family: var(--font-mono); font-size: 0.6875rem; text-transform: uppercase; color: var(--teks-redup); display: block;">
                    Email
                </span>
                <span style="font-size: 0.9375rem; color: var(--putih);">
                    {{ $user->email ?: '-' }}
                </span>
            </div>

            <div>
                <span style="font-family: var(--font-mono); font-size: 0.6875rem; text-transform: uppercase; color: var(--teks-redup); display: block;">
                    Nama PB / Tim
                </span>
                <span style="font-size: 0.9375rem; color: var(--putih);">
                    {{ $user->team_name ?: '-' }}
                </span>
            </div>

            <div>
                <span style="font-family: var(--font-mono); font-size: 0.6875rem; text-transform: uppercase; color: var(--teks-redup); display: block;">
                    Total Riwayat Booking
                </span>
                <span style="font-family: var(--font-mono); font-size: 1rem; font-weight: 700; color: var(--putih);">
                    {{ $user->bookings_count }} Transaksi
                </span>
            </div>
        </div>
    </div>

    {{-- Aksi Sensitif Manajemen Pengguna (Khusus Admin, Dihilangkan pada Mode Pantau) --}}
    @if (!$modePantau)
        <div style="background-color: #171714; border: 1px solid var(--abu-garis); border-radius: var(--radius-md); padding: var(--spasi-4); margin-bottom: var(--spasi-5);">
            <h2 style="font-size: 1.125rem; margin: 0 0 var(--spasi-3) 0; border-bottom: 1px solid var(--abu-garis); padding-bottom: var(--spasi-2);">
                Tindakan Otoritas Akun
            </h2>

            <div style="display: flex; flex-direction: column; gap: var(--spasi-4);">

                {{-- 1. Ubah Peran --}}
                <form action="{{ route('admin.pengguna.peran', $user->id) }}" method="POST" style="background-color: #1C1C18; border: 1px solid var(--abu-garis); border-radius: var(--radius-sm); padding: var(--spasi-3);">
                    @csrf
                    @method('PUT')
                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: var(--spasi-2); margin-bottom: var(--spasi-2);">
                        <div>
                            <strong style="color: var(--putih); font-size: 0.9375rem;">Ubah Peran Pengguna</strong>
                            <p style="margin: 2px 0 0 0; font-size: 0.75rem; color: var(--teks-redup);">
                                Mengubah hak akses pengguna ke sistem (Customer, Admin, atau Owner).
                            </p>
                        </div>
                    </div>

                    <div style="display: flex; flex-wrap: wrap; gap: var(--spasi-2); align-items: center;">
                        <select name="role" required class="form-input" style="min-height: 48px; background-color: #151512; color: var(--putih); padding: 0 12px; font-size: 0.875rem;">
                            <option value="customer" {{ $user->role->value === 'customer' ? 'selected' : '' }}>Customer (Pelanggan)</option>
                            <option value="admin" {{ $user->role->value === 'admin' ? 'selected' : '' }}>Admin (Pengelola)</option>
                            <option value="owner" {{ $user->role->value === 'owner' ? 'selected' : '' }}>Owner (Pemilik)</option>
                        </select>

                        <input type="password"
                               name="password"
                               required
                               placeholder="Konfirmasi password admin kamu"
                               class="form-input"
                               style="min-height: 48px; background-color: #151512; font-size: 0.875rem; flex: 1; min-width: 200px;">

                        <button type="submit" class="tombol tombol-sekunder" style="min-height: 48px; padding: 0 20px;" onclick="return confirm('Simpan perubahan peran untuk pengguna ini?');">
                            Simpan Peran
                        </button>
                    </div>
                </form>

                {{-- 2. Toggle Status Aktif / Nonaktif --}}
                <form action="{{ route('admin.pengguna.toggle-status', $user->id) }}" method="POST" style="background-color: #1C1C18; border: 1px solid var(--abu-garis); border-radius: var(--radius-sm); padding: var(--spasi-3);">
                    @csrf
                    @method('PUT')
                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: var(--spasi-2); margin-bottom: var(--spasi-2);">
                        <div>
                            <strong style="color: {{ $user->is_active ? 'var(--merah)' : 'var(--lime)' }}; font-size: 0.9375rem;">
                                {{ $user->is_active ? 'Nonaktifkan Akun' : 'Aktifkan Kembali Akun' }}
                            </strong>
                            <p style="margin: 2px 0 0 0; font-size: 0.75rem; color: var(--teks-redup);">
                                {{ $user->is_active ? 'Pengguna yang dinonaktifkan tidak dapat login dan sesinya otomatis dikeluarkan.' : 'Aktifkan agar pengguna dapat login kembali.' }}
                            </p>
                        </div>
                    </div>

                    <div style="display: flex; flex-wrap: wrap; gap: var(--spasi-2); align-items: center;">
                        <input type="password"
                               name="password"
                               required
                               placeholder="Konfirmasi password admin kamu"
                               class="form-input"
                               style="min-height: 48px; background-color: #151512; font-size: 0.875rem; flex: 1; min-width: 200px;">

                        <button type="submit" class="tombol tombol-sekunder" style="min-height: 48px; padding: 0 20px; color: {{ $user->is_active ? 'var(--merah)' : 'var(--lime)' }}; border-color: {{ $user->is_active ? 'rgba(255, 90, 77, 0.4)' : 'rgba(218, 255, 4, 0.4)' }};" onclick="return confirm('Apakah kamu yakin ingin mengubah status aktif pengguna ini?');">
                            {{ $user->is_active ? 'Nonaktifkan Pengguna' : 'Aktifkan Pengguna' }}
                        </button>
                    </div>
                </form>

                {{-- 3. Atur Ulang Password --}}
                <form action="{{ route('admin.pengguna.reset-password', $user->id) }}" method="POST" style="background-color: #1C1C18; border: 1px solid var(--abu-garis); border-radius: var(--radius-sm); padding: var(--spasi-3);">
                    @csrf
                    <div style="margin-bottom: var(--spasi-2);">
                        <strong style="color: var(--putih); font-size: 0.9375rem;">Atur Ulang Password Pengguna</strong>
                        <p style="margin: 2px 0 0 0; font-size: 0.75rem; color: var(--teks-redup);">
                            Admin mereset password pengguna tanpa verifikasi email. Masukkan password baru atau kosongkan untuk dibuatkan password acak.
                        </p>
                    </div>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: var(--spasi-2); align-items: center;">
                        <input type="password"
                               name="password_admin"
                               required
                               placeholder="Konfirmasi password admin kamu *"
                               class="form-input"
                               style="min-height: 48px; background-color: #151512; font-size: 0.875rem;">

                        <input type="text"
                               name="password_baru"
                               placeholder="Password baru (opsional)"
                               class="form-input"
                               style="min-height: 48px; background-color: #151512; font-size: 0.875rem;">

                        <button type="submit" class="tombol tombol-sekunder" style="min-height: 48px; padding: 0 20px;" onclick="return confirm('Atur ulang password untuk akun pengguna ini?');">
                            Reset Password Sekarang
                        </button>
                    </div>
                </form>

            </div>
        </div>
    @endif

    {{-- Riwayat Booking Milik Pengguna Ini --}}
    <div style="background-color: #171714; border: 1px solid var(--abu-garis); border-radius: var(--radius-md); padding: var(--spasi-4);">
        <h2 style="font-size: 1.125rem; margin: 0 0 var(--spasi-3) 0; border-bottom: 1px solid var(--abu-garis); padding-bottom: var(--spasi-2);">
            Riwayat Booking ({{ $bookings->total() }})
        </h2>

        @if ($bookings->isEmpty())
            <p style="color: var(--teks-redup); margin: 0;">
                Pengguna ini belum pernah melakukan booking lapangan.
            </p>
        @else
            <div style="display: flex; flex-direction: column; gap: var(--spasi-3);">
                @foreach ($bookings as $b)
                    @php
                        $bDetailRoute = $modePantau ? route('owner.booking.detail', $b->code) : route('admin.booking.detail', $b->code);
                    @endphp
                    <div style="background-color: #1C1C18; border: 1px solid var(--abu-garis); border-radius: var(--radius-sm); padding: var(--spasi-3); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: var(--spasi-2);">
                        <div>
                            <div style="display: flex; align-items: center; gap: var(--spasi-2); margin-bottom: 2px;">
                                <a href="{{ $bDetailRoute }}" style="font-family: var(--font-mono); font-weight: 700; color: var(--lime); text-decoration: underline;">
                                    {{ $b->code }}
                                </a>
                                <span class="badge-kategori {{ $b->type->value === 'package' ? 'badge-kategori-bulanan' : 'badge-kategori-harian' }}">
                                    {{ $b->type->label() }}
                                </span>
                                <span style="font-family: var(--font-mono); font-size: 0.75rem; color: {{ $b->status->value === 'paid' ? 'var(--lime)' : ($b->status->value === 'cancelled' ? 'var(--merah)' : '#FFB020') }};">
                                    {{ $b->status->label() }}
                                </span>
                            </div>

                            <div style="font-family: var(--font-mono); font-size: 0.8125rem; color: var(--teks-redup);">
                                Dibuat: {{ $b->created_at->timezone('Asia/Jayapura')->format('d M Y, H:i') }} WIT &middot; {{ $b->slots->count() }} Pertemuan &middot; {{ rupiah($b->total_price) }}
                            </div>
                        </div>

                        <div>
                            <a href="{{ $bDetailRoute }}" class="tombol tombol-sekunder" style="min-height: 40px; padding: 0 16px; font-size: 0.8125rem;">
                                Lihat Booking &rarr;
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>

            <div style="margin-top: var(--spasi-4);">
                {{ $bookings->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
