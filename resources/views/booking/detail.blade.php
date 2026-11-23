@extends('layouts.app')

@section('title', 'Detail Booking ' . $booking->code . ' - ' . config('gor.name'))

@section('content')
<div class="kontainer" style="padding-top: var(--spasi-8); padding-bottom: var(--spasi-16);">
    {{-- Navigasi Atas --}}
    <div style="margin-bottom: var(--spasi-6); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: var(--spasi-3);">
        <a href="{{ route('booking.saya') }}" class="link-kembali" style="display: inline-flex; align-items: center; gap: var(--spasi-2); font-family: var(--font-mono); font-size: 0.8125rem; text-transform: uppercase; color: var(--teks-redup); text-decoration: none;">
            &larr; Ke Booking Saya
        </a>

        @if (auth()->user()->isAdmin())
            <span class="badge-kategori badge-kategori-bulanan">TAMPILAN ADMIN</span>
        @elseif (auth()->user()->isOwner())
            <span class="badge-kategori badge-kategori-bulanan">MODE PANTAU</span>
        @endif
    </div>

    @if (session('status'))
        <div class="alert-box alert-box-success" style="margin-bottom: var(--spasi-6);" role="alert">
            {{ session('status') }}
        </div>
    @endif

    <div style="max-width: 720px; margin: 0 auto;">
        {{-- Hitung Mundur Waktu Tahan (Jika Pending Payment) --}}
        @if ($booking->status === \App\Enums\BookingStatus::PENDING_PAYMENT && $booking->expires_at)
            <div class="countdown-card" id="countdown-wrapper" data-expires="{{ $booking->expires_at->toIso8601String() }}">
                <div class="countdown-header">
                    <span class="countdown-title">WAKTU PEMBAYARAN TERSISA</span>
                    <span class="countdown-sub">Selesaikan pembayaran sebelum slot dibebaskan otomatis</span>
                </div>
                <div class="countdown-timer-display" id="countdown-display">
                    --:--
                </div>
            </div>
        @endif

        {{-- Slip Tiket Datar Terang: Satu-satunya Elemen Terang di Halaman Gelap --}}
        <div class="slip-tiket" aria-label="Tiket Booking Lapangan">
            <div class="slip-tiket-header">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: var(--spasi-2);">
                    <div>
                        <div class="slip-tiket-sub">{{ config('gor.name') }}</div>
                        <div class="slip-tiket-kode">{{ $booking->code }}</div>
                    </div>
                    <div style="text-align: right;">
                        <span class="badge-kategori {{ $booking->type === \App\Enums\BookingType::PACKAGE ? 'badge-kategori-bulanan' : 'badge-kategori-harian' }}" style="display: inline-block; margin-bottom: 4px;">
                            {{ $booking->type->label() }}
                        </span>
                        <div>
                            <span class="slip-status-badge slip-status-{{ $booking->status->value }}">
                                {{ $booking->status->label() }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="slip-tiket-body">
                <div class="slip-tiket-row">
                    <span class="slip-tiket-label">Pemesan</span>
                    <span class="slip-tiket-val">
                        {{ $booking->user->name }}
                        @if ($booking->user->team_name)
                            ({{ $booking->user->team_name }})
                        @endif
                        @if (auth()->user()->isAdmin() || auth()->user()->isOwner())
                            <span style="display: block; font-family: var(--font-mono); font-size: 0.8125rem; color: #555;">
                                {{ $booking->user->phone }}
                            </span>
                        @endif
                    </span>
                </div>

                <div class="slip-tiket-row">
                    <span class="slip-tiket-label">Waktu Dibuat</span>
                    <span class="slip-tiket-val">
                        {{ tanggal_indonesia($booking->created_at, true, true) }} WIT
                    </span>
                </div>

                @if ($booking->notes)
                    <div class="slip-tiket-row">
                        <span class="slip-tiket-label">Catatan</span>
                        <span class="slip-tiket-val">{{ $booking->notes }}</span>
                    </div>
                @endif

                {{-- Daftar Pertemuan --}}
                <div style="margin-top: var(--spasi-3); border-top: 1px dashed #C8C8C0; padding-top: var(--spasi-3);">
                    <span class="slip-tiket-label" style="display: block; margin-bottom: var(--spasi-2);">
                        Jadwal Pertemuan ({{ $booking->slots->count() }} Sesi)
                    </span>

                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        @foreach ($booking->slots as $index => $slot)
                            <div class="slip-meeting-item">
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <span class="slip-meeting-num">{{ $index + 1 }}</span>
                                    <div>
                                        <div style="font-weight: 700; color: #1A1A18;">
                                            {{ tanggal_indonesia($slot->play_date, true, false) }}
                                        </div>
                                        <div style="font-size: 0.8125rem; color: #555;">
                                            {{ $slot->court->name }} &middot; {{ $slot->session->label }} ({{ $slot->session->formattedRange() }} WIT)
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="slip-tiket-footer">
                <span class="slip-tiket-label">Total Biaya Sewa</span>
                <span class="slip-tiket-val slip-tiket-val-large">
                    {{ rupiah($booking->total_price) }}
                </span>
            </div>
        </div>

        {{-- Aksi Tambahan di Bawah Tiket --}}
        <div style="margin-top: var(--spasi-6); display: flex; flex-direction: column; gap: var(--spasi-3);">
            {{-- Tombol Batalkan Booking (Hanya untuk pemilik dan pending payment) --}}
            @if ($booking->status === \App\Enums\BookingStatus::PENDING_PAYMENT && $booking->user_id === auth()->id())
                <form action="{{ route('booking.batal', $booking->code) }}" method="POST" onsubmit="return confirm('Apakah kamu yakin ingin membatalkan booking ini? Slot akan langsung dibebaskan.');">
                    @csrf
                    <button type="submit" class="tombol tombol-sekunder" style="width: 100%; min-height: 48px; color: var(--merah); border-color: rgba(255, 90, 77, 0.4);">
                        Batalkan Booking
                    </button>
                </form>
            @endif

            {{-- Tombol Simulasi Lunas Sementara (Khusus Local Environment - Fase 4) --}}
            @if (app()->environment('local') && $booking->status === \App\Enums\BookingStatus::PENDING_PAYMENT)
                <div style="background-color: var(--abu-gelap); border: 1px dashed var(--garis); padding: var(--spasi-4); border-radius: var(--radius-sm); text-align: center;">
                    <div style="font-family: var(--font-mono); font-size: 0.75rem; text-transform: uppercase; color: var(--lime); margin-bottom: var(--spasi-2);">
                        Mode Pengujian Lokal (Fase 4)
                    </div>
                    <form action="{{ route('booking.simulasi-lunas', $booking->code) }}" method="POST">
                        @csrf
                        <button type="submit" class="tombol tombol-outline" style="width: 100%; min-height: 44px;">
                            Simulasi Lunas (Tes)
                        </button>
                    </form>
                </div>
            @endif

            <div style="display: flex; gap: var(--spasi-3); justify-content: center; margin-top: var(--spasi-2);">
                <a href="{{ route('jadwal') }}" class="tombol tombol-outline" style="flex: 1;">
                    Cek Jadwal Lapangan
                </a>
                <a href="{{ route('booking.saya') }}" class="tombol tombol-sekunder" style="flex: 1;">
                    Booking Saya
                </a>
            </div>
        </div>
    </div>
</div>

{{-- Script Hitung Mundur Tanpa Aria-Live Berisik --}}
@if ($booking->status === \App\Enums\BookingStatus::PENDING_PAYMENT && $booking->expires_at)
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
            // Muat ulang otomatis saat waktu tahan habis
            setTimeout(function () {
                window.location.reload();
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
@endif
@endsection
