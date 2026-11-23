@extends('layouts.app')

@section('title', 'Notifikasi - ' . config('gor.name'))

@section('content')
<div class="kontainer" style="padding-top: var(--spasi-8); padding-bottom: var(--spasi-16);">
    <div style="max-width: 760px; margin: 0 auto;">
        {{-- Header Halaman --}}
        <div style="margin-bottom: var(--spasi-6); display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: var(--spasi-3);">
            <div>
                <h1 style="font-family: var(--font-judul); font-size: 2.25rem; font-weight: 700; text-transform: uppercase; letter-spacing: var(--tracking-judul);">
                    Notifikasi
                </h1>
                <p style="color: var(--teks-redup); margin-top: var(--spasi-1);">
                    Pemberitahuan penting tentang status pembayaran dan jadwal bermainmu.
                </p>
            </div>

            @if ($notifications->contains(fn($n) => $n->unread()))
                <form action="{{ route('notifikasi.baca-semua') }}" method="POST">
                    @csrf
                    <button type="submit" class="tombol tombol-sekunder" style="min-height: 40px; font-size: 0.8125rem;">
                        Tandai Semua Dibaca
                    </button>
                </form>
            @endif
        </div>

        @if (session('status'))
            <div class="alert-box alert-box-success" style="margin-bottom: var(--spasi-6);" role="alert">
                {{ session('status') }}
            </div>
        @endif

        {{-- Daftar Notifikasi --}}
        @if ($notifications->isEmpty())
            <div class="booking-section-box" style="text-align: center; padding: var(--spasi-10);">
                <p style="font-size: 1rem; color: var(--teks-redup); margin: 0;">
                    Belum ada notifikasi untukmu saat ini.
                </p>
            </div>
        @else
            <div class="notifikasi-list">
                @foreach ($notifications as $notif)
                    @php
                        $data = $notif->data;
                        $isUnread = is_null($notif->read_at);
                        $bookingCode = $data['booking_code'] ?? null;
                    @endphp
                    <div class="notifikasi-item {{ $isUnread ? 'is-unread' : '' }}">
                        <div class="notifikasi-content">
                            <div class="notifikasi-header">
                                <span class="notifikasi-title">{{ $data['title'] ?? 'Pemberitahuan' }}</span>
                                <span class="notifikasi-time">{{ tanggal_indonesia($notif->created_at, true, true) }} WIT</span>
                            </div>

                            <p class="notifikasi-message">
                                {{ $data['message'] ?? '' }}
                            </p>

                            @if ($bookingCode)
                                <div style="margin-top: var(--spasi-2);">
                                    <a href="{{ route('notifikasi.baca', $notif->id) }}" class="notifikasi-link">
                                        Lihat Tiket {{ $bookingCode }} &rarr;
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Pagination --}}
            <div style="margin-top: var(--spasi-6);">
                {{ $notifications->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
