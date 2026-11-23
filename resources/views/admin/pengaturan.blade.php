@extends('layouts.admin')

@section('title', ($modePantau ? 'Pengaturan Sistem (Mode Pantau)' : 'Pengaturan Operasional') . ' - ' . config('gor.name'))

@section('content')
<div class="kontainer" style="max-width: 800px; padding-top: var(--spasi-4);">

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
    <div style="margin-bottom: var(--spasi-5);">
        <p style="font-family: var(--font-mono); font-size: 0.75rem; text-transform: uppercase; color: var(--lime); letter-spacing: 0.05em; margin: 0 0 4px 0;">
            {{ $modePantau ? 'Parameter Sistem (Mode Pantau)' : 'Konfigurasi Tarif & Aturan Bisnis' }}
        </p>
        <h1 style="font-size: clamp(1.5rem, 4vw, 2rem); margin: 0 0 4px 0; line-height: 1.2;">
            Pengaturan Operasional
        </h1>
        <p style="color: var(--teks-redup); margin: 0; font-size: 0.875rem;">
            Perubahan harga dan batas penahanan slot hanya akan berlaku bagi booking baru yang dibuat setelah pengaturan disimpan.
        </p>
    </div>

    @if ($modePantau)
        {{-- Mode Pantau: Nilai Ditampilkan Sebagai Teks Saja --}}
        <div style="background-color: #171714; border: 1px solid var(--abu-garis); border-radius: var(--radius-md); padding: var(--spasi-5);">
            <div style="display: flex; flex-direction: column; gap: var(--spasi-4);">

                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--abu-garis); padding-bottom: var(--spasi-3);">
                    <div>
                        <strong style="color: var(--putih); font-size: 1rem;">Harga Sewa Harian</strong>
                        <p style="margin: 2px 0 0 0; font-size: 0.8125rem; color: var(--teks-redup);">Tarif sewa per 1 slot sesi bermain</p>
                    </div>
                    <div style="font-family: var(--font-mono); font-size: 1.25rem; font-weight: 700; color: var(--lime);">
                        {{ rupiah($settings['price_single']) }}
                    </div>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--abu-garis); padding-bottom: var(--spasi-3);">
                    <div>
                        <strong style="color: var(--putih); font-size: 1rem;">Harga Sewa Bulanan</strong>
                        <p style="margin: 2px 0 0 0; font-size: 0.8125rem; color: var(--teks-redup);">Tarif paket 4 pertemuan mingguan berturut-turut</p>
                    </div>
                    <div style="font-family: var(--font-mono); font-size: 1.25rem; font-weight: 700; color: var(--lime);">
                        {{ rupiah($settings['price_package']) }}
                    </div>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--abu-garis); padding-bottom: var(--spasi-3);">
                    <div>
                        <strong style="color: var(--putih); font-size: 1rem;">Waktu Tahan Bayar (*Hold Minutes*)</strong>
                        <p style="margin: 2px 0 0 0; font-size: 0.8125rem; color: var(--teks-redup);">Durasi slot ditahan sementara sebelum dilepas otomatis</p>
                    </div>
                    <div style="font-family: var(--font-mono); font-size: 1.125rem; font-weight: 700; color: var(--putih);">
                        {{ $settings['hold_minutes'] }} Menit
                    </div>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--abu-garis); padding-bottom: var(--spasi-3);">
                    <div>
                        <strong style="color: var(--putih); font-size: 1rem;">Maksimal Booking Menunggu Bayar</strong>
                        <p style="margin: 2px 0 0 0; font-size: 0.8125rem; color: var(--teks-redup);">Batas booking pending payment bersamaan per pengguna</p>
                    </div>
                    <div style="font-family: var(--font-mono); font-size: 1.125rem; font-weight: 700; color: var(--putih);">
                        {{ $settings['max_pending_bookings'] }} Transaksi
                    </div>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--abu-garis); padding-bottom: var(--spasi-3);">
                    <div>
                        <strong style="color: var(--putih); font-size: 1rem;">Ambang Peringatan Perpanjangan</strong>
                        <p style="margin: 2px 0 0 0; font-size: 0.8125rem; color: var(--teks-redup);">Sisa sesi pertemuan untuk mulai memunculkan peringatan</p>
                    </div>
                    <div style="font-family: var(--font-mono); font-size: 1.125rem; font-weight: 700; color: var(--putih);">
                        Sisa {{ $settings['renew_notice_remaining'] }} Pertemuan
                    </div>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <strong style="color: var(--putih); font-size: 1rem;">Jam Notifikasi Hari Terakhir</strong>
                        <p style="margin: 2px 0 0 0; font-size: 0.8125rem; color: var(--teks-redup);">Jam pengiriman notifikasi pengingat sesi terakhir (WIT)</p>
                    </div>
                    <div style="font-family: var(--font-mono); font-size: 1.125rem; font-weight: 700; color: var(--putih);">
                        Pukul {{ sprintf('%02d:00', $settings['last_day_notice_hour']) }} WIT
                    </div>
                </div>

            </div>
        </div>
    @else
        {{-- Mode Admin: Form Pengaturan dengan Konfirmasi Password --}}
        <div style="background-color: #171714; border: 1px solid var(--abu-garis); border-radius: var(--radius-md); padding: var(--spasi-5);">
            <form action="{{ route('admin.pengaturan.update') }}" method="POST" style="display: flex; flex-direction: column; gap: var(--spasi-4);">
                @csrf
                @method('PUT')

                {{-- Tarif Sewa --}}
                <div style="background-color: #1F1F1B; border: 1px solid var(--abu-garis); border-radius: var(--radius-sm); padding: var(--spasi-4);">
                    <h2 style="font-size: 1rem; margin: 0 0 var(--spasi-3) 0; color: var(--putih); border-bottom: 1px dashed var(--abu-garis); padding-bottom: var(--spasi-2);">
                        1. Tarif Sewa Lapangan
                    </h2>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: var(--spasi-3);">
                        <div>
                            <label for="set_price_single" class="form-label" style="display: block; margin-bottom: 4px;">
                                Harga Sewa Harian (Rp) <span style="color: var(--merah);">*</span>
                            </label>
                            <input type="number"
                                   id="set_price_single"
                                   name="price_single"
                                   required
                                   min="10000"
                                   step="1000"
                                   class="form-input"
                                   style="width: 100%; min-height: 48px; background-color: #151512; font-family: var(--font-mono); font-size: 1rem;"
                                   value="{{ old('price_single', $settings['price_single']) }}">
                            <span style="font-size: 0.75rem; color: var(--teks-redup); margin-top: 2px; display: block;">Per 1 slot sesi bermain</span>
                        </div>

                        <div>
                            <label for="set_price_package" class="form-label" style="display: block; margin-bottom: 4px;">
                                Harga Sewa Bulanan (Rp) <span style="color: var(--merah);">*</span>
                            </label>
                            <input type="number"
                                   id="set_price_package"
                                   name="price_package"
                                   required
                                   min="10000"
                                   step="1000"
                                   class="form-input"
                                   style="width: 100%; min-height: 48px; background-color: #151512; font-family: var(--font-mono); font-size: 1rem;"
                                   value="{{ old('price_package', $settings['price_package']) }}">
                            <span style="font-size: 0.75rem; color: var(--teks-redup); margin-top: 2px; display: block;">Per 4 pertemuan mingguan berturut-turut</span>
                        </div>
                    </div>
                </div>

                {{-- Waktu & Kuota Reservasi --}}
                <div style="background-color: #1F1F1B; border: 1px solid var(--abu-garis); border-radius: var(--radius-sm); padding: var(--spasi-4);">
                    <h2 style="font-size: 1rem; margin: 0 0 var(--spasi-3) 0; color: var(--putih); border-bottom: 1px dashed var(--abu-garis); padding-bottom: var(--spasi-2);">
                        2. Durasi Tahan &amp; Batasan Pemesanan
                    </h2>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: var(--spasi-3);">
                        <div>
                            <label for="set_hold_minutes" class="form-label" style="display: block; margin-bottom: 4px;">
                                Waktu Tahan Bayar (Menit) <span style="color: var(--merah);">*</span>
                            </label>
                            <input type="number"
                                   id="set_hold_minutes"
                                   name="hold_minutes"
                                   required
                                   min="5"
                                   max="120"
                                   class="form-input"
                                   style="width: 100%; min-height: 48px; background-color: #151512; font-family: var(--font-mono); font-size: 1rem;"
                                   value="{{ old('hold_minutes', $settings['hold_minutes']) }}">
                            <span style="font-size: 0.75rem; color: var(--teks-redup); margin-top: 2px; display: block;">Batas waktu transfer pembayaran</span>
                        </div>

                        <div>
                            <label for="set_max_pending_bookings" class="form-label" style="display: block; margin-bottom: 4px;">
                                Batas Maksimal Booking Menunggu Bayar <span style="color: var(--merah);">*</span>
                            </label>
                            <input type="number"
                                   id="set_max_pending_bookings"
                                   name="max_pending_bookings"
                                   required
                                   min="1"
                                   max="10"
                                   class="form-input"
                                   style="width: 100%; min-height: 48px; background-color: #151512; font-family: var(--font-mono); font-size: 1rem;"
                                   value="{{ old('max_pending_bookings', $settings['max_pending_bookings']) }}">
                            <span style="font-size: 0.75rem; color: var(--teks-redup); margin-top: 2px; display: block;">Mencegah penimbunan slot oleh 1 akun</span>
                        </div>
                    </div>
                </div>

                {{-- Kebijakan Siklus Bulanan --}}
                <div style="background-color: #1F1F1B; border: 1px solid var(--abu-garis); border-radius: var(--radius-sm); padding: var(--spasi-4);">
                    <h2 style="font-size: 1rem; margin: 0 0 var(--spasi-3) 0; color: var(--putih); border-bottom: 1px dashed var(--abu-garis); padding-bottom: var(--spasi-2);">
                        3. Notifikasi Siklus Bulanan
                    </h2>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: var(--spasi-3);">
                        <div>
                            <label for="set_renew_notice_remaining" class="form-label" style="display: block; margin-bottom: 4px;">
                                Ambang Peringatan Perpanjangan <span style="color: var(--merah);">*</span>
                            </label>
                            <input type="number"
                                   id="set_renew_notice_remaining"
                                   name="renew_notice_remaining"
                                   required
                                   min="1"
                                   max="3"
                                   class="form-input"
                                   style="width: 100%; min-height: 48px; background-color: #151512; font-family: var(--font-mono); font-size: 1rem;"
                                   value="{{ old('renew_notice_remaining', $settings['renew_notice_remaining']) }}">
                            <span style="font-size: 0.75rem; color: var(--teks-redup); margin-top: 2px; display: block;">Peringatan dikirim saat sisa sesi &le; nilai ini</span>
                        </div>

                        <div>
                            <label for="set_last_day_notice_hour" class="form-label" style="display: block; margin-bottom: 4px;">
                                Jam Pengiriman Hari Terakhir (WIT) <span style="color: var(--merah);">*</span>
                            </label>
                            <input type="number"
                                   id="set_last_day_notice_hour"
                                   name="last_day_notice_hour"
                                   required
                                   min="0"
                                   max="23"
                                   class="form-input"
                                   style="width: 100%; min-height: 48px; background-color: #151512; font-family: var(--font-mono); font-size: 1rem;"
                                   value="{{ old('last_day_notice_hour', $settings['last_day_notice_hour']) }}">
                            <span style="font-size: 0.75rem; color: var(--teks-redup); margin-top: 2px; display: block;">Format 0-23 (bawaan jam 6 pagi WIT)</span>
                        </div>
                    </div>
                </div>

                {{-- Konfirmasi Password Admin --}}
                <div style="background-color: #1F1F1B; border: 1px solid var(--abu-garis); border-radius: var(--radius-sm); padding: var(--spasi-4);">
                    <label for="admin_settings_password" class="form-label" style="display: block; margin-bottom: 4px; color: var(--putih); font-weight: 700;">
                        Konfirmasi Password Admin <span style="color: var(--merah);">*</span>
                    </label>
                    <p style="font-size: 0.75rem; color: var(--teks-redup); margin: 0 0 var(--spasi-2) 0;">
                        Masukkan password akun admin kamu untuk memvalidasi perubahan konfigurasi sistem.
                    </p>
                    <input type="password"
                           id="admin_settings_password"
                           name="password"
                           required
                           placeholder="Password akun admin kamu"
                           class="form-input"
                           style="width: 100%; min-height: 48px; background-color: #151512;">
                </div>

                <div>
                    <button type="submit" class="tombol tombol-primer" style="width: 100%; min-height: 52px; font-weight: 700; font-size: 1rem;">
                        Simpan Perubahan Pengaturan
                    </button>
                </div>
            </form>
        </div>
    @endif

</div>
@endsection
