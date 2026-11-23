@extends('layouts.app')

@section('title', 'Booking Saya - ' . config('gor.name'))

@section('content')
<div class="kontainer" style="padding-top: var(--spasi-8); padding-bottom: var(--spasi-16);">
    {{-- Header Halaman --}}
    <div style="margin-bottom: var(--spasi-8); display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: var(--spasi-4);">
        <div>
            <h1 style="font-family: var(--font-judul); font-size: 2.25rem; font-weight: 700; text-transform: uppercase; letter-spacing: var(--tracking-judul);">
                Booking Saya
            </h1>
            <p style="color: var(--teks-redup); margin-top: var(--spasi-1);">
                Kelola jadwal bermain, pantau status pembayaran, dan lihat riwayat sewa lapanganmu.
            </p>
        </div>

        <a href="{{ route('jadwal') }}" class="tombol tombol-solid">
            Pesan Lapangan Baru
        </a>
    </div>

    @php
        $nowJayapura = \Carbon\Carbon::now('Asia/Jayapura');

        // Cari apakah ada sewa bulanan yang butuh perpanjangan mendesak untuk banner tipis
        $urgentNoticeBooking = null;
        $urgentNoticeType = null;
        foreach ($akanDatang as $b) {
            if ($b->type === \App\Enums\BookingType::PACKAGE) {
                $lc = new \App\Services\PackageLifecycle($b);
                $st = $lc->state($nowJayapura);
                $cs = $lc->continuationStatus($nowJayapura);
                if ($cs === 'belum_lanjut' && in_array($st, ['hampir_habis', 'terakhir_hari_ini'])) {
                    $urgentNoticeBooking = $b;
                    $urgentNoticeType = $st;
                    break;
                }
            }
        }
    @endphp

    {{-- Banner Tipis (Dapat Ditutup) untuk Pemilik Bulanan yang Hampir Habis atau Terakhir Hari Ini --}}
    @if ($urgentNoticeBooking)
        <div id="dismissable-renewal-banner" class="alert-box alert-box-warning" style="margin-bottom: var(--spasi-6); display: flex; justify-content: space-between; align-items: center; gap: var(--spasi-3); flex-wrap: wrap;" role="alert">
            <div>
                <strong>
                    {{ $urgentNoticeType === 'terakhir_hari_ini' ? 'Hari Ini Pertemuan Terakhir!' : 'Jatah Main Bulanan Hampir Habis!' }}
                </strong>
                Booking {{ $urgentNoticeBooking->code }} tersisa {{ (new \App\Services\PackageLifecycle($urgentNoticeBooking))->remainingMeetings($nowJayapura) }} pertemuan.
                Perpanjang sekarang sebelum slot diambil orang lain ya.
            </div>
            <div style="display: flex; align-items: center; gap: var(--spasi-2); flex-shrink: 0;">
                <form action="{{ route('booking.perpanjang', $urgentNoticeBooking->code) }}" method="POST" style="margin: 0;">
                    @csrf
                    <button type="submit" class="tombol tombol-solid" style="min-height: 36px; padding: 4px 14px; font-size: 0.8125rem;">
                        Perpanjang &rarr;
                    </button>
                </form>
                <button type="button" onclick="document.getElementById('dismissable-renewal-banner').style.display='none'" class="tombol tombol-sekunder" style="min-height: 36px; padding: 4px 10px; font-size: 0.8125rem;" aria-label="Tutup pemberitahuan">
                    &times;
                </button>
            </div>
        </div>
    @endif

    {{-- BAGIAN 1: MENUNGGU BAYAR --}}
    <section class="booking-section-group" aria-labelledby="heading-menunggu-bayar">
        <div class="booking-section-header">
            <h2 id="heading-menunggu-bayar" class="booking-section-title">
                Menunggu Bayar
            </h2>
            <span class="booking-section-count">{{ $menungguBayar->count() }}</span>
        </div>

        @if ($menungguBayar->isEmpty())
            <div class="booking-empty-box">
                Tidak ada booking yang sedang menunggu pembayaran.
            </div>
        @else
            <div class="booking-rows-list">
                @foreach ($menungguBayar as $booking)
                    <div class="booking-row-item is-pending">
                        <div class="booking-row-main">
                            <div class="booking-row-header">
                                <span class="badge-kategori {{ $booking->type === \App\Enums\BookingType::PACKAGE ? 'badge-kategori-bulanan' : 'badge-kategori-harian' }}">
                                    {{ $booking->type->label() }}
                                </span>
                                <span class="booking-code-pill">{{ $booking->code }}</span>
                                <span class="slip-status-badge slip-status-pending_payment">Menunggu Bayar</span>
                            </div>

                            <div class="booking-row-details">
                                <div class="booking-row-detail-item">
                                    <span class="detail-label">Lapangan</span>
                                    <span class="detail-val">{{ $booking->slots->first()?->court->name ?? 'Lapangan' }}</span>
                                </div>

                                <div class="booking-row-detail-item">
                                    <span class="detail-label">Jam Main</span>
                                    <span class="detail-val">
                                        {{ $booking->slots->first()?->session->label ?? 'Sesi' }} ({{ $booking->slots->first()?->session->formattedRange() ?? '' }} WIT)
                                    </span>
                                </div>

                                <div class="booking-row-detail-item" style="grid-column: span 2;">
                                    <span class="detail-label">Jadwal Pertemuan</span>
                                    <div class="detail-val-dates">
                                        @foreach ($booking->slots as $slot)
                                            <span class="meeting-date-badge">
                                                {{ tanggal_indonesia($slot->play_date, false, false) }}
                                            </span>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="booking-row-side">
                            <div class="booking-row-price">
                                <span class="detail-label">Total Tagihan</span>
                                <span class="price-val">{{ rupiah($booking->total_price) }}</span>
                            </div>

                            <a href="{{ route('booking.bayar', $booking->code) }}" class="tombol tombol-solid" style="min-height: 40px; font-size: 0.875rem;">
                                Bayar Sekarang &rarr;
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </section>

    {{-- BAGIAN 2: AKAN DATANG --}}
    <section class="booking-section-group" aria-labelledby="heading-akan-datang" style="margin-top: var(--spasi-10);">
        <div class="booking-section-header">
            <h2 id="heading-akan-datang" class="booking-section-title">
                Akan Datang
            </h2>
            <span class="booking-section-count">{{ $akanDatang->count() }}</span>
        </div>

        @if ($akanDatang->isEmpty())
            <div class="booking-empty-box">
                Belum ada jadwal main mendatang. Silakan booking slot di jadwal lapangan.
            </div>
        @else
            <div class="booking-rows-list">
                @foreach ($akanDatang as $booking)
                    @php
                        $isBulanan = ($booking->type === \App\Enums\BookingType::PACKAGE);
                        $lc = $isBulanan ? new \App\Services\PackageLifecycle($booking) : null;
                        $remaining = $lc ? $lc->remainingMeetings($nowJayapura) : 0;
                        $total = $lc ? $lc->totalMeetings() : 0;
                        $completed = max(0, $total - $remaining);
                        $state = $lc ? $lc->state($nowJayapura) : '';
                        $contStatus = $lc ? $lc->continuationStatus($nowJayapura) : '';
                    @endphp
                    <div class="booking-row-item">
                        <div class="booking-row-main">
                            <div class="booking-row-header">
                                <span class="badge-kategori {{ $booking->type === \App\Enums\BookingType::PACKAGE ? 'badge-kategori-bulanan' : 'badge-kategori-harian' }}">
                                    {{ $booking->type->label() }}
                                </span>
                                <span class="booking-code-pill">{{ $booking->code }}</span>
                                <span class="slip-status-badge slip-status-paid">Lunas</span>

                                {{-- Chip State untuk Bulanan --}}
                                @if ($isBulanan && $lc)
                                    @if ($contStatus === 'sudah_lanjut')
                                        <span class="chip-state chip-state-success">Sudah Lanjut</span>
                                    @elseif ($contStatus === 'perpanjangan_menunggu_bayar')
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
                                @endif
                            </div>

                            {{-- Siklus Bulanan: Pertemuan X dari 4 & Batang Kemajuan Datar --}}
                            @if ($isBulanan && $lc)
                                <div style="margin-bottom: var(--spasi-3);">
                                    <div style="font-family: var(--font-mono); font-size: 0.75rem; text-transform: uppercase; color: var(--teks-redup); margin-bottom: 4px;">
                                        Pertemuan {{ min($completed + 1, $total) }} dari {{ $total }}
                                    </div>
                                    <div class="package-progress-bar-small">
                                        @for ($i = 1; $i <= $total; $i++)
                                            <div class="progress-box-small {{ $i <= $completed ? 'is-completed' : ($i === $completed + 1 ? 'is-current' : '') }}"></div>
                                        @endfor
                                    </div>
                                </div>
                            @endif

                            <div class="booking-row-details">
                                <div class="booking-row-detail-item">
                                    <span class="detail-label">Lapangan</span>
                                    <span class="detail-val">{{ $booking->slots->first()?->court->name ?? 'Lapangan' }}</span>
                                </div>

                                <div class="booking-row-detail-item">
                                    <span class="detail-label">Jam Main</span>
                                    <span class="detail-val">
                                        {{ $booking->slots->first()?->session->label ?? 'Sesi' }} ({{ $booking->slots->first()?->session->formattedRange() ?? '' }} WIT)
                                    </span>
                                </div>

                                <div class="booking-row-detail-item" style="grid-column: span 2;">
                                    <span class="detail-label">Jadwal Pertemuan</span>
                                    <div class="detail-val-dates">
                                        @foreach ($booking->slots as $slot)
                                            @php
                                                $sessionEnd = \Carbon\Carbon::parse("{$slot->play_date} {$slot->session->end_time}", 'Asia/Jayapura');
                                                $isPassed = $nowJayapura->greaterThanOrEqualTo($sessionEnd);
                                            @endphp
                                            <span class="meeting-date-badge {{ $isPassed ? 'is-passed' : 'is-upcoming' }}">
                                                {{ tanggal_indonesia($slot->play_date, false, false) }}
                                                @if ($isPassed)
                                                    <span style="font-size: 0.6875rem; opacity: 0.7;">(selesai)</span>
                                                @endif
                                            </span>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="booking-row-side">
                            <div class="booking-row-price">
                                <span class="detail-label">Biaya Sewa</span>
                                <span class="price-val">{{ rupiah($booking->total_price) }}</span>
                            </div>

                            @if ($isBulanan && $remaining <= \App\Models\Setting::getInt('renew_notice_remaining', 1) && $remaining >= 1 && $contStatus === 'belum_lanjut')
                                <form action="{{ route('booking.perpanjang', $booking->code) }}" method="POST" style="margin: 0; width: 100%;">
                                    @csrf
                                    <button type="submit" class="tombol tombol-solid" style="min-height: 40px; font-size: 0.8125rem; width: 100%;">
                                        Lanjutkan Sewa &rarr;
                                    </button>
                                </form>
                            @endif

                            <a href="{{ route('booking.detail', $booking->code) }}" class="tombol tombol-outline" style="min-height: 40px; font-size: 0.875rem;">
                                Lihat Tiket &rarr;
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </section>

    {{-- BAGIAN 3: RIWAYAT --}}
    <section class="booking-section-group" aria-labelledby="heading-riwayat" style="margin-top: var(--spasi-10);">
        <div class="booking-section-header">
            <h2 id="heading-riwayat" class="booking-section-title">
                Riwayat Booking
            </h2>
            <span class="booking-section-count">{{ $riwayat->count() }}</span>
        </div>

        @if ($riwayat->isEmpty())
            <div class="booking-empty-box">
                Belum ada riwayat booking sebelumnya.
            </div>
        @else
            <div class="booking-rows-list">
                @foreach ($riwayat as $booking)
                    <div class="booking-row-item is-history">
                        <div class="booking-row-main">
                            <div class="booking-row-header">
                                <span class="badge-kategori {{ $booking->type === \App\Enums\BookingType::PACKAGE ? 'badge-kategori-bulanan' : 'badge-kategori-harian' }}">
                                    {{ $booking->type->label() }}
                                </span>
                                <span class="booking-code-pill">{{ $booking->code }}</span>
                                <span class="slip-status-badge slip-status-{{ $booking->status->value }}">
                                    {{ $booking->status->label() }}
                                </span>
                            </div>

                            <div class="booking-row-details">
                                <div class="booking-row-detail-item">
                                    <span class="detail-label">Lapangan</span>
                                    <span class="detail-val">{{ $booking->slots->first()?->court->name ?? 'Lapangan' }}</span>
                                </div>

                                <div class="booking-row-detail-item">
                                    <span class="detail-label">Jam Main</span>
                                    <span class="detail-val">
                                        {{ $booking->slots->first()?->session->label ?? 'Sesi' }} ({{ $booking->slots->first()?->session->formattedRange() ?? '' }} WIT)
                                    </span>
                                </div>

                                <div class="booking-row-detail-item" style="grid-column: span 2;">
                                    <span class="detail-label">Tanggal</span>
                                    <div class="detail-val-dates">
                                        @if ($booking->slots->isNotEmpty())
                                            @foreach ($booking->slots as $slot)
                                                <span class="meeting-date-badge is-passed">
                                                    {{ tanggal_indonesia($slot->play_date, false, false) }}
                                                </span>
                                            @endforeach
                                        @else
                                            <span style="font-size: 0.8125rem; color: var(--teks-redup); font-family: var(--font-mono);">
                                                Slot dibebaskan
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="booking-row-side">
                            <div class="booking-row-price">
                                <span class="detail-label">Total</span>
                                <span class="price-val" style="color: var(--teks-redup);">{{ rupiah($booking->total_price) }}</span>
                            </div>

                            <a href="{{ route('booking.detail', $booking->code) }}" class="tombol tombol-sekunder" style="min-height: 40px; font-size: 0.875rem;">
                                Detail &rarr;
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </section>
</div>
@endsection
