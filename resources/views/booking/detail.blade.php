@extends('layouts.app')

@section('title', 'Detail Booking ' . $booking->code . ' - ' . config('gor.name'))

@section('content')
<div class="kontainer" style="padding-top: var(--spasi-8); padding-bottom: var(--spasi-16);">
    {{-- Navigasi Atas --}}
    <div style="margin-bottom: var(--spasi-6); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: var(--spasi-3);" class="no-print">
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
        <div class="alert-box alert-box-success no-print" style="margin-bottom: var(--spasi-6);" role="alert">
            {{ session('status') }}
        </div>
    @endif

    @if (session('error'))
        <div class="alert-box alert-box-error no-print" style="margin-bottom: var(--spasi-6);" role="alert">
            {{ session('error') }}
        </div>
    @endif

    <div style="max-width: 720px; margin: 0 auto;">
        {{-- Hitung Mundur Waktu Tahan (Jika Pending Payment) --}}
        @if ($booking->status === \App\Enums\BookingStatus::PENDING_PAYMENT && $booking->expires_at)
            <div class="countdown-card no-print" id="countdown-wrapper" data-expires="{{ $booking->expires_at->toIso8601String() }}">
                <div class="countdown-header">
                    <span class="countdown-title">WAKTU PEMBAYARAN TERSISA</span>
                    <span class="countdown-sub">Selesaikan pembayaran sebelum slot dibebaskan otomatis</span>
                </div>
                <div class="countdown-timer-display" id="countdown-display">
                    --:--
                </div>
            </div>
        @endif

        @php
            $isPackagePaid = ($booking->type === \App\Enums\BookingType::PACKAGE && $booking->status === \App\Enums\BookingStatus::PAID);
            $lifecycle = $isPackagePaid ? new \App\Services\PackageLifecycle($booking) : null;
        @endphp

        {{-- Siklus Sewa Bulanan di UI --}}
        @if ($lifecycle)
            @php
                $totalMeetings = $lifecycle->totalMeetings();
                $remainingMeetings = $lifecycle->remainingMeetings();
                $completedMeetings = max(0, $totalMeetings - $remainingMeetings);
                $state = $lifecycle->state();
                $continuationStatus = $lifecycle->continuationStatus();
            @endphp
            <div class="booking-section-box no-print" style="margin-bottom: var(--spasi-6);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--spasi-3); flex-wrap: wrap; gap: var(--spasi-2);">
                    <div style="font-family: var(--font-judul); font-size: 1.125rem; font-weight: 700; text-transform: uppercase;">
                        Pertemuan {{ min($completedMeetings + 1, $totalMeetings) }} dari {{ $totalMeetings }}
                    </div>

                    {{-- Chip State --}}
                    <div>
                        @if ($continuationStatus === 'sudah_lanjut')
                            <span class="chip-state chip-state-success">Sudah Lanjut</span>
                        @elseif ($continuationStatus === 'perpanjangan_menunggu_bayar')
                            <span class="chip-state chip-state-warning">Perpanjangan Menunggu Bayar</span>
                        @elseif ($state === 'terakhir_hari_ini')
                            <span class="chip-state chip-state-urgent">Terakhir Hari Ini</span>
                        @elseif ($state === 'hampir_habis')
                            <span class="chip-state chip-state-urgent">Hampir Habis</span>
                        @elseif ($state === 'selesai')
                            <span class="chip-state chip-state-dim">Selesai</span>
                        @else
                            <span class="chip-state chip-state-normal">Aktif</span>
                        @endif
                    </div>
                </div>

                {{-- Batang Kemajuan 4 Kotak Datar --}}
                <div class="package-progress-bar" role="progressbar" aria-valuenow="{{ $completedMeetings }}" aria-valuemin="0" aria-valuemax="{{ $totalMeetings }}" aria-label="Kemajuan Pertemuan Bulanan">
                    @for ($i = 1; $i <= $totalMeetings; $i++)
                        <div class="progress-box {{ $i <= $completedMeetings ? 'is-completed' : ($i === $completedMeetings + 1 ? 'is-current' : '') }}">
                            <span class="progress-box-num">{{ $i }}</span>
                            <span class="progress-box-label">
                                {{ $i <= $completedMeetings ? 'Selesai' : ($i === $completedMeetings + 1 ? 'Berikutnya' : 'Mendatang') }}
                            </span>
                        </div>
                    @endfor
                </div>

                {{-- Tombol Perpanjangan (Jika Tersisa <= 1 sesi dan belum diperpanjang) --}}
                @if ($remainingMeetings <= \App\Models\Setting::getInt('renew_notice_remaining', 1) && $remainingMeetings >= 1 && $continuationStatus === 'belum_lanjut' && $booking->user_id === auth()->id())
                    <div style="margin-top: var(--spasi-4); padding-top: var(--spasi-4); border-top: 1px dashed var(--garis); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: var(--spasi-3);">
                        <div>
                            <div style="font-weight: 700; color: var(--putih); font-size: 0.9375rem;">
                                Ingin lanjut main untuk 4 minggu ke depan?
                            </div>
                            <div style="font-size: 0.8125rem; color: var(--teks-redup);">
                                Siapa cepat dia dapat. Slot lanjutan tidak dikunci otomatis.
                            </div>
                        </div>

                        <form action="{{ route('booking.perpanjang', $booking->code) }}" method="POST">
                            @csrf
                            <button type="submit" class="tombol tombol-solid">
                                Lanjutkan Sewa Bulanan &rarr;
                            </button>
                        </form>
                    </div>
                @endif
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

                {{-- Waktu Pembayaran (Jika Sudah Lunas) --}}
                @php
                    $paidPayment = $booking->payments->where('status', \App\Enums\PaymentStatus::PAID)->first();
                @endphp
                @if ($paidPayment && $paidPayment->paid_at)
                    <div class="slip-tiket-row">
                        <span class="slip-tiket-label">Waktu Bayar</span>
                        <span class="slip-tiket-val">
                            {{ tanggal_indonesia($paidPayment->paid_at, true, true) }} WIT
                        </span>
                    </div>
                @endif

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

                {{-- Riwayat Percobaan Pembayaran --}}
                @if ($booking->payments->isNotEmpty())
                    <div style="margin-top: var(--spasi-3); border-top: 1px dashed #C8C8C0; padding-top: var(--spasi-3);">
                        <span class="slip-tiket-label" style="display: block; margin-bottom: var(--spasi-2);">
                            Riwayat Transaksi Pembayaran
                        </span>

                        <div style="display: flex; flex-direction: column; gap: 6px; font-family: var(--font-mono); font-size: 0.8125rem;">
                            @foreach ($booking->payments as $p)
                                <div style="display: flex; justify-content: space-between; align-items: baseline; color: #333;">
                                    <span>
                                        {{ $p->reference }} &middot;
                                        <strong style="text-transform: uppercase;">{{ $p->status->label() }}</strong>
                                    </span>
                                    <span>{{ rupiah($p->amount) }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <div class="slip-tiket-footer">
                <span class="slip-tiket-label">Total Biaya Sewa</span>
                <span class="slip-tiket-val slip-tiket-val-large">
                    {{ rupiah($booking->total_price) }}
                </span>
            </div>
        </div>

        {{-- Aksi Tambahan di Bawah Tiket --}}
        <div class="no-print" style="margin-top: var(--spasi-6); display: flex; flex-direction: column; gap: var(--spasi-3);">
            {{-- Tombol Bayar Sekarang (Jika Pending Payment dan pemilik) --}}
            @if ($booking->status === \App\Enums\BookingStatus::PENDING_PAYMENT && $booking->user_id === auth()->id())
                <a href="{{ route('booking.bayar', $booking->code) }}" class="tombol tombol-solid" style="width: 100%; min-height: 52px; font-size: 1rem; text-align: center;">
                    Lanjut ke Pembayaran &rarr;
                </a>

                <form action="{{ route('booking.batal', $booking->code) }}" method="POST" onsubmit="return confirm('Apakah kamu yakin ingin membatalkan booking ini? Slot akan langsung dibebaskan.');">
                    @csrf
                    <button type="submit" class="tombol tombol-sekunder" style="width: 100%; min-height: 44px; color: var(--merah); border-color: rgba(255, 90, 77, 0.4);">
                        Batalkan Booking
                    </button>
                </form>
            @endif

            {{-- Tombol Cetak Tiket (Jika Paid) --}}
            @if ($booking->status === \App\Enums\BookingStatus::PAID)
                <button type="button" onclick="window.print()" class="tombol tombol-solid" style="width: 100%; min-height: 48px;">
                    Cetak Tiket / Simpan PDF
                </button>
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
