@extends('layouts.app')

@section('title', 'Pembayaran Booking ' . $booking->code . ' - ' . config('gor.name'))

@section('content')
<div class="kontainer" style="padding-top: var(--spasi-8); padding-bottom: var(--spasi-16);">
    {{-- Banner Permanen Mode Uji jika Production dengan Driver Simulated --}}
    @if (app()->environment('production') && config('gor.payment_driver') === 'simulated')
        <div class="alert-box alert-box-warning" style="margin-bottom: var(--spasi-6); font-family: var(--font-mono); font-weight: 700; text-align: center;" role="alert">
            MODE UJI &middot; GATEWAY PEMBAYARAN SIMULASI AKTIF DI PRODUKSI
        </div>
    @endif

    {{-- Navigasi Kembali --}}
    <div style="margin-bottom: var(--spasi-6);">
        <a href="{{ route('booking.detail', $booking->code) }}" class="link-kembali" style="display: inline-flex; align-items: center; gap: var(--spasi-2); font-family: var(--font-mono); font-size: 0.8125rem; text-transform: uppercase; color: var(--teks-redup); text-decoration: none;">
            &larr; Kembali ke Detail Tiket
        </a>
    </div>

    @if (session('error'))
        <div class="alert-box alert-box-error" style="margin-bottom: var(--spasi-6);" role="alert">
            {{ session('error') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert-box alert-box-error" style="margin-bottom: var(--spasi-6);" role="alert">
            <ul style="margin: 0; padding-left: var(--spasi-4);">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div style="max-width: 680px; margin: 0 auto;">
        {{-- Hitung Mundur Waktu Tahan --}}
        @if ($booking->expires_at)
            <div class="countdown-card" id="countdown-wrapper" data-expires="{{ $booking->expires_at->toIso8601String() }}">
                <div class="countdown-header">
                    <span class="countdown-title">BATAS WAKTU PEMBAYARAN</span>
                    <span class="countdown-sub">Slot otomatis dibebaskan jika pembayaran belum selesai</span>
                </div>
                <div class="countdown-timer-display" id="countdown-display">
                    --:--
                </div>
            </div>
        @endif

        {{-- Ringkasan Tagihan & Total Besar --}}
        <div class="booking-section-box" style="margin-bottom: var(--spasi-6);">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: var(--spasi-4); padding-bottom: var(--spasi-4); border-bottom: 1px solid var(--garis); flex-wrap: wrap; gap: var(--spasi-2);">
                <div>
                    <span style="font-family: var(--font-mono); font-size: 0.75rem; text-transform: uppercase; color: var(--teks-redup);">
                        Kode Booking
                    </span>
                    <div style="font-family: var(--font-mono); font-size: 1.5rem; font-weight: 700; color: var(--putih);">
                        {{ $booking->code }}
                    </div>
                </div>

                <div style="text-align: right;">
                    <span class="badge-kategori {{ $booking->type === \App\Enums\BookingType::PACKAGE ? 'badge-kategori-bulanan' : 'badge-kategori-harian' }}">
                        {{ $booking->type->label() }}
                    </span>
                    <div style="font-size: 0.8125rem; color: var(--teks-redup); margin-top: 4px;">
                        {{ $booking->slots->count() }} Pertemuan
                    </div>
                </div>
            </div>

            <div style="display: flex; flex-direction: column; gap: var(--spasi-2); margin-bottom: var(--spasi-4);">
                <div style="display: flex; justify-content: space-between; font-size: 0.9375rem;">
                    <span style="color: var(--teks-redup);">Lapangan:</span>
                    <span style="font-weight: 600; color: var(--putih);">
                        {{ $booking->slots->first()?->court->name ?? 'Lapangan' }}
                    </span>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 0.9375rem;">
                    <span style="color: var(--teks-redup);">Sesi Waktu:</span>
                    <span style="font-weight: 600; color: var(--putih);">
                        {{ $booking->slots->first()?->session->label ?? 'Sesi' }} ({{ $booking->slots->first()?->session->formattedRange() ?? '' }} WIT)
                    </span>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 0.9375rem;">
                    <span style="color: var(--teks-redup);">Mulai Main:</span>
                    <span style="font-weight: 600; color: var(--putih);">
                        {{ tanggal_indonesia($booking->slots->first()?->play_date, true, false) }}
                    </span>
                </div>
            </div>

            <div style="padding-top: var(--spasi-4); border-top: 1px dashed var(--garis); display: flex; justify-content: space-between; align-items: baseline;">
                <span style="font-family: var(--font-mono); font-size: 0.875rem; text-transform: uppercase; color: var(--teks-redup);">
                    Total Pembayaran
                </span>
                <span style="font-family: var(--font-mono); font-size: 2rem; font-weight: 700; color: var(--lime);">
                    {{ rupiah($booking->total_price) }}
                </span>
            </div>
        </div>

        {{-- Blok Pembayaran Simulasi --}}
        <div class="booking-section-box" style="text-align: center;">
            {{-- Banner Jelas Mode Simulasi --}}
            <div style="background-color: #26200A; border: 1px solid #FFB020; border-radius: var(--radius-sm); padding: var(--spasi-3) var(--spasi-4); margin-bottom: var(--spasi-6);">
                <span style="font-family: var(--font-mono); font-size: 0.8125rem; font-weight: 700; color: #FFB020; letter-spacing: 0.05em; display: block;">
                    MODE SIMULASI: TIDAK ADA UANG YANG DIPINDAHKAN
                </span>
                <span style="font-size: 0.8125rem; color: #D8BD80; margin-top: 2px; display: block;">
                    Sistem saat ini menggunakan gateway pembayaran simulasi untuk pengujian alur sewa.
                </span>
            </div>

            {{-- QR Palsu Berlabel SIMULASI --}}
            <div style="margin-bottom: var(--spasi-6);">
                <x-qr-simulasi :code="$booking->code" :size="200" />
            </div>

            {{-- Tombol Aksi Simulasi --}}
            <div style="display: flex; flex-direction: column; gap: var(--spasi-3);">
                <form action="{{ route('booking.bayar.proses', $booking->code) }}" method="POST">
                    @csrf
                    <button type="submit" class="tombol tombol-solid" style="width: 100%; min-height: 52px; font-size: 1rem;">
                        Simulasikan Pembayaran Berhasil
                    </button>
                </form>

                <form action="{{ route('booking.bayar.gagal', $booking->code) }}" method="POST">
                    @csrf
                    <button type="submit" class="tombol tombol-sekunder" style="width: 100%; min-height: 44px; color: var(--merah); border-color: rgba(255, 90, 77, 0.4);">
                        Simulasikan Gagal
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- Script Hitung Mundur --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
    const wrapper = document.getElementById('countdown-wrapper');
    const display = document.getElementById('countdown-display');
    if (!wrapper || !display) return;

    const expiresTime = new Date(wrapper.getAttribute('data-expires')).getTime();

    function updateCountdown() {
        const now = new Date().getTime();
        const diff = expiresTime - now;

        if (diff <= 0) {
            display.textContent = '00:00';
            display.classList.add('is-expired');
            setTimeout(function () {
                window.location.href = "{{ route('booking.detail', $booking->code) }}";
            }, 1000);
            return;
        }

        const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((diff % (1000 * 60)) / 1000);

        const mm = String(minutes).padStart(2, '0');
        const ss = String(seconds).padStart(2, '0');

        display.textContent = mm + ':' + ss;
    }

    updateCountdown();
    setInterval(updateCountdown, 1000);
});
</script>
@endsection
