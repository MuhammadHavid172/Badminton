@extends('layouts.app')

@section('title', 'Pilihan Perpanjangan Sewa Bulanan - ' . config('gor.name'))

@section('content')
<div class="kontainer" style="padding-top: var(--spasi-8); padding-bottom: var(--spasi-16);">
    <div style="margin-bottom: var(--spasi-6);">
        <a href="{{ route('booking.detail', $booking->code) }}" class="link-kembali" style="display: inline-flex; align-items: center; gap: var(--spasi-2); font-family: var(--font-mono); font-size: 0.8125rem; text-transform: uppercase; color: var(--teks-redup); text-decoration: none;">
            &larr; Kembali ke Detail Booking
        </a>
    </div>

    <div style="max-width: 680px; margin: 0 auto;">
        <div style="margin-bottom: var(--spasi-6);">
            <span class="badge-kategori badge-kategori-bulanan" style="margin-bottom: var(--spasi-2); display: inline-block;">
                PERPANJANGAN BULANAN
            </span>
            <h1 style="font-family: var(--font-judul); font-size: 2rem; font-weight: 700; text-transform: uppercase; letter-spacing: var(--tracking-judul);">
                Slot Semula Sudah Tidak Tersedia
            </h1>
            <p style="color: var(--teks-redup); margin-top: var(--spasi-1);">
                Sesuai aturan sewa bulanan, sistem menerapkan <em>siapa cepat dia dapat</em> tanpa pencadangan slot otomatis.
            </p>
        </div>

        {{-- Tanggal yang Bentrok --}}
        <div class="booking-section-box" style="margin-bottom: var(--spasi-6); border-left: 3px solid var(--merah);">
            <div style="font-family: var(--font-mono); font-size: 0.8125rem; font-weight: 700; color: var(--merah); text-transform: uppercase; margin-bottom: var(--spasi-2);">
                Jadwal yang Bentrok pada {{ $alternatives['court']->name }} &middot; {{ $alternatives['session']->label }}
            </div>
            <ul style="margin: 0; padding-left: var(--spasi-4); font-size: 0.9375rem; color: var(--putih); line-height: 1.6;">
                @foreach ($alternatives['conflicts'] as $cDate => $cMsg)
                    <li>{{ $cMsg }}</li>
                @endforeach
            </ul>
        </div>

        {{-- Pilihan Alternatif --}}
        @if ($alternatives['has_alternatives'])
            <form action="{{ route('booking.perpanjang', $booking->code) }}" method="POST">
                @csrf
                <div class="booking-section-box" style="margin-bottom: var(--spasi-6);">
                    <h2 style="font-family: var(--font-judul); font-size: 1.125rem; font-weight: 700; text-transform: uppercase; margin-bottom: var(--spasi-3);">
                        Pilihan Alternatif yang Tersedia (Kosong 4 Minggu)
                    </h2>
                    <p style="font-size: 0.875rem; color: var(--teks-redup); margin-bottom: var(--spasi-4);">
                        Kami menemukan opsi slot yang kosong untuk seluruh 4 tanggal pertemuan lanjutanmu:
                    </p>

                    <div class="booking-radio-group">
                        {{-- Opsi Alternatif Lapangan Lain --}}
                        @foreach ($alternatives['alternative_courts'] as $idx => $alt)
                            <label class="booking-radio-row">
                                <input
                                    type="radio"
                                    name="alternative_court_id"
                                    value="{{ $alt['court']->id }}"
                                    class="booking-radio-input"
                                    {{ $loop->first ? 'checked' : '' }}
                                >
                                <div class="booking-radio-content">
                                    <div class="booking-radio-title">
                                        {{ $alt['court']->name }}
                                    </div>
                                    <div class="booking-radio-desc">
                                        Sesi tetap sama: {{ $alt['session']->label }} ({{ $alt['session']->formattedRange() }} WIT)
                                    </div>
                                </div>
                            </label>
                        @endforeach

                        {{-- Opsi Alternatif Sesi Lain --}}
                        @foreach ($alternatives['alternative_sessions'] as $idx => $alt)
                            <label class="booking-radio-row">
                                <input
                                    type="radio"
                                    name="alternative_session_id"
                                    value="{{ $alt['session']->id }}"
                                    class="booking-radio-input"
                                    {{ empty($alternatives['alternative_courts']) && $loop->first ? 'checked' : '' }}
                                >
                                <div class="booking-radio-content">
                                    <div class="booking-radio-title">
                                        {{ $alt['session']->label }} ({{ $alt['session']->formattedRange() }} WIT)
                                    </div>
                                    <div class="booking-radio-desc">
                                        Lapangan tetap sama: {{ $alt['court']->name }}
                                    </div>
                                </div>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div style="display: flex; gap: var(--spasi-3); flex-wrap: wrap;">
                    <button type="submit" class="tombol tombol-solid" style="flex: 1; min-height: 48px;">
                        Lanjutkan dengan Pilihan Ini &rarr;
                    </button>
                    <a href="{{ route('jadwal') }}" class="tombol tombol-outline" style="min-height: 48px;">
                        Pilih Manual di Jadwal
                    </a>
                </div>
            </form>
        @else
            <div class="booking-section-box" style="text-align: center; padding: var(--spasi-8);">
                <p style="font-size: 1rem; color: var(--putih); margin-bottom: var(--spasi-4); line-height: 1.6;">
                    Mohon maaf, seluruh slot alternatif di lapangan dan sesi lain untuk keempat tanggal tersebut juga telah terisi atau ditutup.
                </p>
                <p style="font-size: 0.875rem; color: var(--teks-redup); margin-bottom: var(--spasi-6);">
                    Silakan periksa jadwal keseluruhan untuk memilih hari atau sesi lain secara manual.
                </p>
                <a href="{{ route('jadwal') }}" class="tombol tombol-solid">
                    Buka Jadwal Lapangan
                </a>
            </div>
        @endif
    </div>
</div>
@endsection
