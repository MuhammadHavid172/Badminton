@extends('layouts.admin')

@section('title', 'Jatah Bulanan Habis & Perpanjangan - Admin - ' . config('gor.name'))

@section('content')
<div class="kontainer" style="max-width: 900px; padding-top: var(--spasi-4);">

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

    {{-- Header Halaman --}}
    <div style="margin-bottom: var(--spasi-5);">
        <p style="font-family: var(--font-mono); font-size: 0.75rem; text-transform: uppercase; color: var(--lime); letter-spacing: 0.05em; margin: 0 0 4px 0;">
            Manajemen Pelanggan Bulanan
        </p>
        <h1 style="font-size: clamp(1.5rem, 4vw, 2rem); margin: 0 0 var(--spasi-2) 0; line-height: 1.2;">
            Jatah Bulanan Perlu Ditindaklanjuti
        </h1>
        <p style="color: var(--teks-redup); margin: 0; font-size: 0.9375rem;">
            Daftar sewa Bulanan yang tersisa 1 pertemuan atau akan berakhir dalam 14 hari ke depan, serta yang baru berakhir dalam 7 hari terakhir dan belum diperpanjang.
        </p>
    </div>

    {{-- Ringkasan Jumlah --}}
    <div style="margin-bottom: var(--spasi-4); display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--abu-garis); padding-bottom: var(--spasi-3);">
        <span style="font-family: var(--font-mono); font-size: 0.875rem; color: var(--teks-redup);">
            TOTAL PERLU PERHATIAN: <strong style="color: {{ $jatahHabisCount > 0 ? '#FFB020' : 'var(--lime)' }};">{{ $jatahHabisCount }}</strong> BOOKING
        </span>
    </div>

    @if ($actionList->isEmpty())
        <div style="background-color: #171714; border: 1px solid var(--abu-garis); border-radius: var(--radius-md); padding: var(--spasi-6); text-align: center;">
            <p style="color: var(--teks-redup); margin: 0; font-size: 1rem;">
                Tidak ada sewa Bulanan yang perlu ditindaklanjuti saat ini. Semua pelanggan masih aktif atau sudah melakukan perpanjangan.
            </p>
        </div>
    @else
        <div style="display: flex; flex-direction: column; gap: var(--spasi-4);">
            @foreach ($actionList as $item)
                @php
                    $b = $item['booking'];
                    $u = $item['user'];
                    $court = $item['court'];
                    $session = $item['session'];
                    $lastDate = $item['last_date'];
                    $remaining = $item['remaining'];
                    $contStatus = $item['continuation_status'];
                    $reminderText = $item['reminder_text'];
                @endphp

                <div style="background-color: #171714; border: 1px solid var(--abu-garis); border-radius: var(--radius-md); padding: var(--spasi-4); display: flex; flex-direction: column; gap: var(--spasi-3);">
                    {{-- Baris Atas: Pemesan & Status Sisa --}}
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: var(--spasi-2); border-bottom: 1px solid var(--abu-garis); padding-bottom: var(--spasi-3);">
                        <div>
                            <div style="display: flex; align-items: center; gap: var(--spasi-2); margin-bottom: 4px;">
                                <span style="font-family: var(--font-mono); font-size: 0.8125rem; font-weight: 700; color: var(--lime);">
                                    {{ $b->code }}
                                </span>
                                <span class="badge-kategori badge-kategori-bulanan">
                                    BULANAN
                                </span>
                                @if ($contStatus === 'perpanjangan_menunggu_bayar')
                                    <span style="font-family: var(--font-mono); font-size: 0.6875rem; background-color: #FFB020; color: #000; padding: 1px 6px; border-radius: 3px; font-weight: 700;">
                                        MENUNGGU BAYAR LANJUTAN
                                    </span>
                                @endif
                            </div>

                            <div style="font-size: 1.125rem; font-weight: 700; color: var(--putih);">
                                {{ $u ? ($u->team_name ? $u->team_name . ' (' . $u->name . ')' : $u->name) : 'Pelanggan' }}
                            </div>

                            <div style="font-family: var(--font-mono); font-size: 0.8125rem; margin-top: 4px;">
                                HP: <a href="tel:{{ $u?->phone }}" style="color: var(--lime); text-decoration: underline;">{{ $u?->phone }}</a>
                            </div>
                        </div>

                        <div style="text-align: right;">
                            <span style="display: inline-block; font-family: var(--font-mono); font-size: 0.8125rem; font-weight: 700; padding: 4px 10px; border-radius: 4px;
                                background-color: {{ $remaining === 0 ? 'rgba(255, 90, 77, 0.15)' : '#2E2E28' }};
                                color: {{ $remaining === 0 ? 'var(--merah)' : '#FFB020' }};">
                                @if ($remaining === 0)
                                    SUDAH BERAKHIR
                                @elseif ($remaining === 1)
                                    SISA 1 PERTEMUAN
                                @else
                                    SISA {{ $remaining }} PERTEMUAN
                                @endif
                            </span>
                            <div style="font-family: var(--font-mono); font-size: 0.75rem; color: var(--teks-redup); margin-top: 6px;">
                                Terakhir: {{ $lastDate ? tanggal_indonesia($lastDate, false, false) : '-' }}
                            </div>
                        </div>
                    </div>

                    {{-- Baris Tengah: Info Jadwal Rutin --}}
                    <div style="font-family: var(--font-mono); font-size: 0.875rem; color: var(--teks-redup);">
                        Jadwal: <strong style="color: var(--putih);">{{ $court?->name ?? 'Lapangan' }}</strong> &middot;
                        {{ $session?->formattedRange() ?? '' }} &middot;
                        Hari {{ $lastDate ? \Carbon\Carbon::parse($lastDate)->locale('id')->isoFormat('dddd') : '' }}
                    </div>

                    {{-- Baris Bawah: Aksi Pengelola (Min-height 48px) --}}
                    <div style="display: flex; flex-direction: column; gap: var(--spasi-3); background-color: #1F1F1B; border: 1px solid var(--abu-garis); border-radius: var(--radius-sm); padding: var(--spasi-3);">
                        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: var(--spasi-2);">
                            <span style="font-family: var(--font-mono); font-size: 0.75rem; color: var(--teks-redup); text-transform: uppercase;">
                                Teks Pengingat Pelanggan:
                            </span>

                            <button type="button"
                                    class="tombol tombol-sekunder btn-salin-pengingat"
                                    style="min-height: 48px; padding: 0 16px; font-size: 0.8125rem;"
                                    data-text="{{ $reminderText }}">
                                Salin Pesan Pengingat
                            </button>
                        </div>

                        <div style="font-size: 0.8125rem; color: var(--teks-redup); background-color: #151512; padding: var(--spasi-2) var(--spasi-3); border-radius: 4px; font-style: italic;">
                            "{{ $reminderText }}"
                        </div>

                        @php
                            $jatahDetailRoute = ($modePantau ?? false) ? route('owner.booking.detail', $b->code) : route('admin.booking.detail', $b->code);
                        @endphp

                        @if (!($modePantau ?? false))
                            {{-- Form Perpanjang Atas Nama (Khusus Admin) --}}
                            <form action="{{ route('admin.booking.perpanjang-atas-nama', $b->code) }}" method="POST" style="margin-top: var(--spasi-2); display: flex; flex-wrap: wrap; align-items: center; gap: var(--spasi-2);">
                                @csrf
                                <span style="font-family: var(--font-mono); font-size: 0.8125rem; color: var(--putih); margin-right: 4px;">
                                    Perpanjang atas nama:
                                </span>

                                <select name="metode" class="form-input" style="min-height: 48px; background-color: #151512; border-color: var(--abu-garis); color: var(--putih); font-size: 0.875rem; padding: 0 12px;" required>
                                    <option value="tunai">Tunai, sudah dibayar (Lunas)</option>
                                    <option value="tahan">Tahan dulu (Masa Tahan Biasa)</option>
                                </select>

                                <button type="submit" class="tombol tombol-primer" style="min-height: 48px; padding: 0 20px; font-size: 0.875rem;" onclick="return confirm('Proses perpanjangan sewa Bulanan 4 minggu berikutnya untuk pemesan ini?');">
                                    Proses Perpanjangan
                                </button>

                                <a href="{{ $jatahDetailRoute }}" class="tombol tombol-sekunder" style="min-height: 48px; padding: 0 16px; font-size: 0.8125rem; margin-left: auto;">
                                    Lihat Booking
                                </a>
                            </form>
                        @else
                            <div style="margin-top: var(--spasi-2); display: flex; justify-content: flex-end;">
                                <a href="{{ $jatahDetailRoute }}" class="tombol tombol-sekunder" style="min-height: 48px; padding: 0 16px; font-size: 0.8125rem;">
                                    Lihat Booking &rarr;
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const copyButtons = document.querySelectorAll('.btn-salin-pengingat');
    copyButtons.forEach(btn => {
        btn.addEventListener('click', function () {
            const textToCopy = this.getAttribute('data-text');
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(textToCopy).then(() => {
                    const originalText = this.innerText;
                    this.innerText = 'Tersalin!';
                    this.style.color = 'var(--lime)';
                    setTimeout(() => {
                        this.innerText = originalText;
                        this.style.color = '';
                    }, 2500);
                });
            } else {
                // Fallback textarea
                const textArea = document.createElement('textarea');
                textArea.value = textToCopy;
                textArea.style.position = 'fixed';
                textArea.style.left = '-999999px';
                document.body.appendChild(textArea);
                textArea.focus();
                textArea.select();
                try {
                    document.execCommand('copy');
                    const originalText = this.innerText;
                    this.innerText = 'Tersalin!';
                    this.style.color = 'var(--lime)';
                    setTimeout(() => {
                        this.innerText = originalText;
                        this.style.color = '';
                    }, 2500);
                } catch (err) {
                    // silent
                }
                textArea.remove();
            }
        });
    });
});
</script>
@endsection
