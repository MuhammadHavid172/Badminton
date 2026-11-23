@extends('layouts.admin')

@section('title', 'Detail Booking ' . $booking->code . ' - Admin - ' . config('gor.name'))

@section('content')
<div class="kontainer" style="max-width: 900px; padding-top: var(--spasi-4);">

    {{-- Breadcrumb & Navigasi Kembali --}}
    <div style="margin-bottom: var(--spasi-4); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: var(--spasi-2);">
        @php
            $bDashRoute = ($modePantau ?? false) ? route('owner.dashboard') : route('admin.dashboard');
            $bListRoute = ($modePantau ?? false) ? route('owner.booking.index') : route('admin.booking.index');
        @endphp
        <a href="{{ $bDashRoute }}" class="tombol tombol-sekunder" style="min-height: 40px; font-size: 0.8125rem;">
            &larr; Kembali ke Hari Ini
        </a>
        <a href="{{ $bListRoute }}" class="tombol tombol-sekunder" style="min-height: 40px; font-size: 0.8125rem;">
            Lihat Daftar Booking
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

    {{-- Header Kartu Ringkasan Booking --}}
    <div style="background-color: #171714; border: 1px solid var(--abu-garis); border-radius: var(--radius-md); padding: var(--spasi-4); margin-bottom: var(--spasi-5);">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: var(--spasi-3); border-bottom: 1px solid var(--abu-garis); padding-bottom: var(--spasi-3); margin-bottom: var(--spasi-4);">
            <div>
                <div style="display: flex; align-items: center; gap: var(--spasi-2); margin-bottom: 4px;">
                    <h1 style="font-family: var(--font-mono); font-size: 1.25rem; font-weight: 700; color: var(--lime); margin: 0; line-height: 1.2;">
                        {{ $booking->code }}
                    </h1>
                    <span class="badge-kategori {{ $booking->type->value === 'package' ? 'badge-kategori-bulanan' : 'badge-kategori-harian' }}">
                        {{ $booking->type->label() }}
                    </span>
                </div>
                <p style="font-family: var(--font-mono); font-size: 0.8125rem; color: var(--teks-redup); margin: 0;">
                    Dibuat: {{ $booking->created_at->timezone('Asia/Jayapura')->format('d M Y, H:i') }} WIT
                    @if ($booking->createdBy)
                        &middot; Oleh Admin: {{ $booking->createdBy->name }}
                    @endif
                </p>
            </div>

            <div style="text-align: right;">
                <span style="font-family: var(--font-mono); font-size: 0.8125rem; padding: 4px 10px; border-radius: 4px; font-weight: 700; text-transform: uppercase;
                    background-color: {{ $booking->status->value === 'paid' ? 'rgba(218, 255, 4, 0.15)' : ($booking->status->value === 'cancelled' ? 'rgba(255, 90, 77, 0.15)' : '#2E2E28') }};
                    color: {{ $booking->status->value === 'paid' ? 'var(--lime)' : ($booking->status->value === 'cancelled' ? 'var(--merah)' : '#FFB020') }};">
                    {{ $booking->status->label() }}
                </span>
                <div style="font-family: var(--font-mono); font-size: 1.125rem; font-weight: 700; color: var(--putih); margin-top: 6px;">
                    {{ rupiah($booking->total_price) }}
                </div>
            </div>
        </div>

        {{-- Detail Pemesan --}}
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: var(--spasi-3);">
            <div>
                <span style="font-family: var(--font-mono); font-size: 0.6875rem; text-transform: uppercase; color: var(--teks-redup); display: block;">
                    Nama Pemesan
                </span>
                <span style="font-size: 1rem; font-weight: 600; color: var(--putih);">
                    {{ $booking->user?->name ?? 'Tamu' }}
                </span>
            </div>

            <div>
                <span style="font-family: var(--font-mono); font-size: 0.6875rem; text-transform: uppercase; color: var(--teks-redup); display: block;">
                    Nomor WhatsApp / HP
                </span>
                <span style="font-family: var(--font-mono); font-size: 0.9375rem;">
                    @if ($booking->user?->phone)
                        <a href="tel:{{ $booking->user->phone }}" style="color: var(--lime); text-decoration: underline;">
                            {{ $booking->user->phone }}
                        </a>
                    @else
                        -
                    @endif
                </span>
            </div>

            <div>
                <span style="font-family: var(--font-mono); font-size: 0.6875rem; text-transform: uppercase; color: var(--teks-redup); display: block;">
                    Nama PB / Tim
                </span>
                <span style="font-size: 0.9375rem; color: var(--putih);">
                    {{ $booking->user?->team_name ?: '-' }}
                </span>
            </div>

            <div>
                <span style="font-family: var(--font-mono); font-size: 0.6875rem; text-transform: uppercase; color: var(--teks-redup); display: block;">
                    Email Pemesan
                </span>
                <span style="font-size: 0.875rem; color: var(--putih);">
                    {{ $booking->user?->email ?: '-' }}
                </span>
            </div>
        </div>

        @if ($booking->notes)
            <div style="margin-top: var(--spasi-3); padding-top: var(--spasi-3); border-top: 1px dashed var(--abu-garis);">
                <span style="font-family: var(--font-mono); font-size: 0.6875rem; text-transform: uppercase; color: var(--teks-redup); display: block;">
                    Catatan Booking
                </span>
                <p style="margin: 2px 0 0 0; font-size: 0.875rem; color: var(--putih);">
                    {{ $booking->notes }}
                </p>
            </div>
        @endif

        {{-- Info Pembatalan Jika Dibatalkan --}}
        @if ($booking->status->value === 'cancelled')
            <div style="margin-top: var(--spasi-3); padding: var(--spasi-3); background-color: rgba(255, 90, 77, 0.08); border: 1px solid rgba(255, 90, 77, 0.3); border-radius: var(--radius-sm);">
                <div style="font-family: var(--font-mono); font-size: 0.8125rem; font-weight: 700; color: var(--merah); margin-bottom: 4px;">
                    DIBATALKAN PADA {{ $booking->cancelled_at?->timezone('Asia/Jayapura')->format('d M Y, H:i') }} WIT
                    @if ($booking->cancelledByUser)
                        OLEH {{ $booking->cancelledByUser->name }}
                    @endif
                </div>
                <div style="font-size: 0.875rem; color: var(--putih);">
                    <strong>Alasan:</strong> {{ $booking->cancel_reason }}
                </div>
                @if ($booking->refund_note)
                    <div style="font-size: 0.875rem; color: var(--teks-redup); margin-top: 2px;">
                        <strong>Catatan Refund:</strong> {{ $booking->refund_note }}
                    </div>
                @endif
            </div>
        @endif
    </div>

    {{-- Daftar Slot Pertemuan & Check-in Kehadiran --}}
    <div style="background-color: #171714; border: 1px solid var(--abu-garis); border-radius: var(--radius-md); padding: var(--spasi-4); margin-bottom: var(--spasi-5);">
        <h2 style="font-size: 1.125rem; margin: 0 0 var(--spasi-3) 0; border-bottom: 1px solid var(--abu-garis); padding-bottom: var(--spasi-2);">
            Jadwal Pertemuan &amp; Presensi Kehadiran
        </h2>

        @if ($booking->slots->isEmpty())
            <p style="color: var(--teks-redup); margin: 0;">
                Seluruh slot telah dibebaskan karena pembatalan atau kedaluwarsa.
            </p>
        @else
            <div style="display: flex; flex-direction: column; gap: var(--spasi-3);">
                @foreach ($booking->slots->sortBy('play_date') as $index => $slot)
                    @php
                        $isPastSlot = \Carbon\Carbon::parse("{$slot->play_date->format('Y-m-d')} {$slot->session->end_time}", 'Asia/Jayapura')->isPast();
                        $isTodaySlot = $slot->play_date->format('Y-m-d') === \Carbon\Carbon::now('Asia/Jayapura')->format('Y-m-d');
                    @endphp

                    <div style="background-color: #1C1C18; border: 1px solid {{ $isTodaySlot ? 'var(--lime)' : 'var(--abu-garis)' }}; border-radius: var(--radius-sm); padding: var(--spasi-3); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: var(--spasi-2);">
                        <div>
                            <div style="display: flex; align-items: center; gap: var(--spasi-2); margin-bottom: 2px;">
                                <span style="font-family: var(--font-mono); font-size: 0.75rem; color: var(--lime); font-weight: 700;">
                                    Pertemuan {{ $index + 1 }}
                                </span>
                                <span style="font-weight: 600; color: var(--putih);">
                                    {{ $slot->court->name }}
                                </span>
                                @if ($isTodaySlot)
                                    <span style="font-family: var(--font-mono); font-size: 0.6875rem; background-color: var(--lime); color: var(--hitam); padding: 1px 6px; border-radius: 3px; font-weight: 700;">
                                        HARI INI
                                    </span>
                                @endif
                            </div>

                            <div style="font-family: var(--font-mono); font-size: 0.875rem; color: var(--teks-redup);">
                                {{ tanggal_indonesia($slot->play_date->format('Y-m-d'), false, true) }} &middot; {{ $slot->session->formattedRange() }}
                            </div>

                            {{-- Keterangan Kehadiran --}}
                            @if ($slot->checked_in_at)
                                <div style="margin-top: 4px; font-size: 0.75rem; font-family: var(--font-mono); color: var(--lime);">
                                    Sudah Hadir ({{ \Carbon\Carbon::parse($slot->checked_in_at)->timezone('Asia/Jayapura')->format('H:i') }} WIT)
                                    @if ($slot->attendance_note)
                                        &middot; {{ $slot->attendance_note }}
                                    @endif
                                </div>
                            @elseif ($isPastSlot)
                                <div style="margin-top: 4px; font-size: 0.75rem; font-family: var(--font-mono); color: var(--merah);">
                                    Tidak tercatat hadir
                                </div>
                            @endif
                        </div>

                        {{-- Tombol Aksi Check-in (Hanya Admin) --}}
                        @if (!($modePantau ?? false))
                            <div style="display: flex; align-items: center; gap: var(--spasi-2);">
                                @if ($slot->checked_in_at)
                                    <form action="{{ route('admin.slot.batal-check-in', $slot->id) }}" method="POST" style="margin: 0;">
                                        @csrf
                                        <button type="submit" class="tombol tombol-sekunder" style="min-height: 48px; padding: 0 16px; font-size: 0.8125rem; color: var(--teks-redup);" onclick="return confirm('Batalkan presensi kehadiran slot ini?');">
                                            Batal Hadir
                                        </button>
                                    </form>
                                @else
                                    <form action="{{ route('admin.slot.check-in', $slot->id) }}" method="POST" style="margin: 0;">
                                        @csrf
                                        <button type="submit" class="tombol tombol-primer" style="min-height: 48px; padding: 0 20px; font-size: 0.875rem;">
                                            Sudah Hadir
                                        </button>
                                    </form>
                                @endif
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Riwayat Pembayaran --}}
    <div style="background-color: #171714; border: 1px solid var(--abu-garis); border-radius: var(--radius-md); padding: var(--spasi-4); margin-bottom: var(--spasi-5);">
        <h2 style="font-size: 1.125rem; margin: 0 0 var(--spasi-3) 0; border-bottom: 1px solid var(--abu-garis); padding-bottom: var(--spasi-2);">
            Riwayat Pembayaran
        </h2>

        @if ($booking->payments->isEmpty())
            <p style="color: var(--teks-redup); margin: 0;">
                Belum ada transaksi pembayaran untuk booking ini.
            </p>
        @else
            <div style="display: flex; flex-direction: column; gap: var(--spasi-2);">
                @foreach ($booking->payments as $payment)
                    <div style="background-color: #1C1C18; border: 1px solid var(--abu-garis); border-radius: var(--radius-sm); padding: var(--spasi-3); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: var(--spasi-2);">
                        <div>
                            <div style="font-family: var(--font-mono); font-size: 0.8125rem; color: var(--teks-redup);">
                                Metode: <strong style="color: var(--putih); text-transform: uppercase;">{{ $payment->method }}</strong> &middot; Provider: {{ $payment->provider }}
                            </div>
                            <div style="font-family: var(--font-mono); font-size: 0.75rem; color: var(--teks-redup); margin-top: 2px;">
                                @if ($payment->paid_at)
                                    Dibayar: {{ \Carbon\Carbon::parse($payment->paid_at)->timezone('Asia/Jayapura')->format('d M Y, H:i') }} WIT
                                @else
                                    Dibuat: {{ $payment->created_at->timezone('Asia/Jayapura')->format('d M Y, H:i') }} WIT
                                @endif
                                @if ($payment->recorder)
                                    &middot; Dicatat oleh: {{ $payment->recorder->name }}
                                @endif
                            </div>
                        </div>

                        <div style="text-align: right;">
                            <div style="font-family: var(--font-mono); font-weight: 700; color: var(--putih);">
                                {{ rupiah($payment->amount) }}
                            </div>
                            <span style="font-family: var(--font-mono); font-size: 0.75rem; font-weight: 700; color: {{ $payment->status->value === 'paid' ? 'var(--lime)' : ($payment->status->value === 'failed' ? 'var(--merah)' : '#FFB020') }};">
                                {{ $payment->status->label() }}
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Aksi Pembatalan Oleh Admin (Hanya Jika Bukan Mode Pantau) --}}
    @if (!($modePantau ?? false) && $booking->status->value !== 'cancelled' && $booking->status->value !== 'expired')
        <div style="background-color: #171714; border: 1px solid rgba(255, 90, 77, 0.4); border-radius: var(--radius-md); padding: var(--spasi-4);">
            <h2 style="font-size: 1.125rem; margin: 0 0 var(--spasi-2) 0; color: var(--merah);">
                Batalkan Booking Ini
            </h2>
            <p style="font-size: 0.875rem; color: var(--teks-redup); margin: 0 0 var(--spasi-3) 0;">
                Membatalkan booking akan membebaskan SEMUA slot masa depan sehingga dapat dipesan kembali oleh pengguna lain. Slot masa lalu yang telah lewat tetap dipertahankan sebagai riwayat presensi. Pemesan akan menerima notifikasi pembatalan.
            </p>

            <form action="{{ route('admin.booking.batal', $booking->code) }}" method="POST" style="display: flex; flex-direction: column; gap: var(--spasi-3);" onsubmit="return confirm('Apakah kamu yakin ingin membatalkan booking ini? Aksi ini tidak dapat diurungkan.');">
                @csrf

                <div>
                    <label for="admin_cancel_reason" class="form-label" style="display: block; margin-bottom: 4px;">
                        Alasan Pembatalan <span style="color: var(--merah);">*</span>
                    </label>
                    <input type="text"
                           id="admin_cancel_reason"
                           name="reason"
                           required
                           placeholder="Contoh: Perbaikan lapangan mendadak, permintaan langsung pemesan..."
                           class="form-input"
                           style="width: 100%; min-height: 48px; background-color: #1A1A17;"
                           value="{{ old('reason') }}">
                    @error('reason')
                        <span class="pesan-error">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label for="admin_refund_note" class="form-label" style="display: block; margin-bottom: 4px;">
                        Catatan Pengembalian Dana / Refund (Opsional)
                    </label>
                    <input type="text"
                           id="admin_refund_note"
                           name="refund_note"
                           placeholder="Contoh: Dana tunai Rp 200.000 sudah dikembalikan di tempat"
                           class="form-input"
                           style="width: 100%; min-height: 48px; background-color: #1A1A17;"
                           value="{{ old('refund_note') }}">
                    @error('refund_note')
                        <span class="pesan-error">{{ $message }}</span>
                    @enderror
                </div>

                {{-- Wajib Konfirmasi Password Jika Booking PAID --}}
                @if ($booking->status->value === 'paid')
                    <div style="background-color: #1F1F1B; border: 1px solid var(--abu-garis); border-radius: var(--radius-sm); padding: var(--spasi-3);">
                        <label for="admin_password_confirm" class="form-label" style="display: block; margin-bottom: 4px; color: var(--putih);">
                            Konfirmasi Password Admin <span style="color: var(--merah);">*</span>
                        </label>
                        <p style="font-size: 0.75rem; color: var(--teks-redup); margin: 0 0 6px 0;">
                            Booking ini telah berstatus lunas (PAID). Masukkan password akun admin kamu untuk otorisasi pembatalan.
                        </p>
                        <input type="password"
                               id="admin_password_confirm"
                               name="password"
                               required
                               placeholder="Masukkan password admin kamu"
                               class="form-input"
                               style="width: 100%; min-height: 48px; background-color: #1A1A17;">
                        @error('password')
                            <span class="pesan-error">{{ $message }}</span>
                        @enderror
                    </div>
                @endif

                <div>
                    <button type="submit"
                            class="tombol tombol-sekunder"
                            style="min-height: 48px; padding: 0 24px; color: var(--merah); border-color: var(--merah); font-weight: 700;">
                        Batalkan Booking Sekarang
                    </button>
                </div>
            </form>
        </div>
    @endif

</div>
@endsection
