@extends('layouts.admin')

@section('title', ($modePantau ? 'Ringkasan Pemantauan Owner' : 'Ringkasan Operasional') . ' - ' . config('gor.name'))

@section('content')
<div class="kontainer" style="max-width: 1000px; padding-top: var(--spasi-4);">

    {{-- Header Halaman --}}
    <div style="margin-bottom: var(--spasi-5);">
        <p style="font-family: var(--font-mono); font-size: 0.75rem; text-transform: uppercase; color: var(--lime); letter-spacing: 0.05em; margin: 0 0 4px 0;">
            {{ $modePantau ? 'Laporan Eksekutif Pemilik' : 'Pusat Kendali Operasional' }}
        </p>
        <h1 style="font-size: clamp(1.75rem, 4vw, 2.5rem); margin: 0 0 6px 0; line-height: 1.1;">
            Ringkasan Statistik &amp; Okupansi
        </h1>
        <p style="color: var(--teks-redup); margin: 0; font-size: 0.9375rem;">
            Zona Waktu: <strong>Asia/Jayapura (WIT)</strong> &middot; Data diperbarui langsung dari sistem pembayaran &amp; reservasi.
        </p>
    </div>

    {{-- Baris Angka Besar Tipografis Dipisah Garis Tipis (Tanpa Kartu KPI Berikon) --}}
    <div class="tipografi-kpi-baris" style="border-top: 1px solid var(--abu-garis); border-bottom: 1px solid var(--abu-garis); padding: var(--spasi-4) 0; margin-bottom: var(--spasi-6); display: flex; flex-wrap: wrap; gap: var(--spasi-4);">
        <div style="flex: 1; min-width: 170px; border-right: 1px solid var(--abu-garis); padding-right: var(--spasi-3);">
            <span style="font-family: var(--font-mono); font-size: 0.6875rem; text-transform: uppercase; color: var(--teks-redup); display: block; margin-bottom: 4px;">
                Pendapatan Hari Ini
            </span>
            <div style="font-family: var(--font-mono); font-size: clamp(1.5rem, 3vw, 2.25rem); font-weight: 700; color: var(--lime); line-height: 1.1;">
                {{ rupiah($stats['pendapatan_hari_ini']) }}
            </div>
        </div>

        <div style="flex: 1; min-width: 170px; border-right: 1px solid var(--abu-garis); padding-right: var(--spasi-3);">
            <span style="font-family: var(--font-mono); font-size: 0.6875rem; text-transform: uppercase; color: var(--teks-redup); display: block; margin-bottom: 4px;">
                Pendapatan Bulan Ini
            </span>
            <div style="font-family: var(--font-mono); font-size: clamp(1.5rem, 3vw, 2.25rem); font-weight: 700; color: var(--putih); line-height: 1.1;">
                {{ rupiah($stats['pendapatan_bulan_ini']) }}
            </div>
        </div>

        <div style="flex: 1; min-width: 130px; border-right: 1px solid var(--abu-garis); padding-right: var(--spasi-3);">
            <span style="font-family: var(--font-mono); font-size: 0.6875rem; text-transform: uppercase; color: var(--teks-redup); display: block; margin-bottom: 4px;">
                Booking Hari Ini
            </span>
            <div style="font-family: var(--font-mono); font-size: clamp(1.5rem, 3vw, 2.25rem); font-weight: 700; color: var(--putih); line-height: 1.1;">
                {{ $stats['booking_hari_ini'] }}
            </div>
        </div>

        <div style="flex: 1; min-width: 130px; border-right: 1px solid var(--abu-garis); padding-right: var(--spasi-3);">
            <span style="font-family: var(--font-mono); font-size: 0.6875rem; text-transform: uppercase; color: var(--teks-redup); display: block; margin-bottom: 4px;">
                Okupansi Hari Ini
            </span>
            <div style="font-family: var(--font-mono); font-size: clamp(1.5rem, 3vw, 2.25rem); font-weight: 700; color: {{ $stats['okupansi_hari_ini_persen'] > 70 ? 'var(--lime)' : ($stats['okupansi_hari_ini_persen'] > 30 ? '#FFB020' : 'var(--putih)') }}; line-height: 1.1;">
                {{ $stats['okupansi_hari_ini_persen'] }}%
            </div>
            <span style="font-family: var(--font-mono); font-size: 0.6875rem; color: var(--teks-redup);">
                {{ $stats['slot_terisi_hari_ini'] }} / {{ $stats['total_kapasitas_hari_ini'] }} slot
            </span>
        </div>

        <div style="flex: 1; min-width: 140px;">
            <span style="font-family: var(--font-mono); font-size: 0.6875rem; text-transform: uppercase; color: var(--teks-redup); display: block; margin-bottom: 4px;">
                Menunggu Bayar
            </span>
            <div style="font-family: var(--font-mono); font-size: clamp(1.5rem, 3vw, 2.25rem); font-weight: 700; color: {{ $stats['booking_menunggu_bayar'] > 0 ? '#FFB020' : 'var(--teks-redup)' }}; line-height: 1.1;">
                {{ $stats['booking_menunggu_bayar'] }}
            </div>
        </div>
    </div>

    {{-- Grafik Batang Okupansi 14 Hari ke Belakang & 14 Hari ke Depan (SVG Buatan Sendiri) --}}
    <div style="background-color: #171714; border: 1px solid var(--abu-garis); border-radius: var(--radius-md); padding: var(--spasi-4); margin-bottom: var(--spasi-6);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--spasi-3); border-bottom: 1px solid var(--abu-garis); padding-bottom: var(--spasi-2);">
            <div>
                <h2 style="font-size: 1.125rem; margin: 0;">
                    Grafik Okupansi Lapangan (29 Hari)
                </h2>
                <p style="margin: 2px 0 0 0; font-family: var(--font-mono); font-size: 0.75rem; color: var(--teks-redup);">
                    Rentang: 14 hari ke belakang s.d. 14 hari ke depan &middot; Warna aksen limau adalah Hari Ini
                </p>
            </div>
            <div style="display: flex; gap: var(--spasi-3); font-family: var(--font-mono); font-size: 0.6875rem;">
                <span style="display: inline-flex; align-items: center; gap: 4px; color: var(--teks-redup);">
                    <span style="width: 10px; height: 10px; background-color: #3A3A33; display: inline-block;"></span> Lampau
                </span>
                <span style="display: inline-flex; align-items: center; gap: 4px; color: var(--lime);">
                    <span style="width: 10px; height: 10px; background-color: var(--lime); display: inline-block;"></span> Hari Ini
                </span>
                <span style="display: inline-flex; align-items: center; gap: 4px; color: #8C8C7E;">
                    <span style="width: 10px; height: 10px; background-color: #5A5A4E; display: inline-block;"></span> Mendatang
                </span>
            </div>
        </div>

        {{-- SVG Bar Chart --}}
        <div style="width: 100%; overflow-x: auto; padding-top: 10px;">
            <svg viewBox="0 0 870 160" width="100%" height="160" style="display: block; font-family: var(--font-mono);" aria-label="Grafik okupansi 29 hari">
                {{-- Garis Panduan Horizontal --}}
                <line x1="0" y1="120" x2="870" y2="120" stroke="var(--abu-garis)" stroke-width="1" />
                <line x1="0" y1="70" x2="870" y2="70" stroke="rgba(255, 255, 255, 0.05)" stroke-dasharray="4,4" stroke-width="1" />
                <line x1="0" y1="20" x2="870" y2="20" stroke="rgba(255, 255, 255, 0.05)" stroke-dasharray="4,4" stroke-width="1" />

                <text x="5" y="16" fill="var(--teks-redup)" font-size="10">100%</text>
                <text x="5" y="66" fill="var(--teks-redup)" font-size="10">50%</text>
                <text x="5" y="116" fill="var(--teks-redup)" font-size="10">0%</text>

                @php
                    $barWidth = 18;
                    $slotWidth = 870 / count($chartDays);
                @endphp

                @foreach ($chartDays as $idx => $d)
                    @php
                        $x = ($idx * $slotWidth) + 30;
                        $pct = $d['percentage'];
                        $barHeight = max(2, (int) round(($pct / 100) * 100));
                        $y = 120 - $barHeight;

                        if ($d['is_today']) {
                            $fillColor = '#DAFF04'; // var(--lime)
                            $textColor = '#DAFF04';
                        } elseif ($d['is_past']) {
                            $fillColor = '#3A3A33';
                            $textColor = '#6E6E64';
                        } else {
                            $fillColor = '#5A5A4E';
                            $textColor = '#A8A89A';
                        }
                    @endphp

                    <g>
                        <rect x="{{ $x }}" y="{{ $y }}" width="{{ $barWidth }}" height="{{ $barHeight }}" rx="1" fill="{{ $fillColor }}">
                            <title>{{ $d['date'] }}: {{ $pct }}% terisi ({{ $d['occupied'] }}/{{ $d['capacity'] }} slot)</title>
                        </rect>
                        <text x="{{ $x + ($barWidth / 2) }}" y="136" text-anchor="middle" fill="{{ $textColor }}" font-size="9">
                            {{ $d['label_day'] }}
                        </text>
                        @if ($d['is_today'])
                            <circle cx="{{ $x + ($barWidth / 2) }}" cy="148" r="2.5" fill="var(--lime)" />
                        @endif
                    </g>
                @endforeach
            </svg>
        </div>
    </div>

    {{-- Dua Kolom: Jatah Kritis Bulanan & Penutupan Slot Mendatang --}}
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: var(--spasi-4);">

        {{-- Kolom Kiri: Jatah Bulanan Hampir Habis --}}
        <div style="background-color: #171714; border: 1px solid var(--abu-garis); border-radius: var(--radius-md); padding: var(--spasi-4);">
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--abu-garis); padding-bottom: var(--spasi-2); margin-bottom: var(--spasi-3);">
                <h3 style="font-size: 1rem; margin: 0;">
                    Jatah Bulanan Hampir Habis
                </h3>
                @if ($modePantau)
                    <a href="{{ route('owner.jatah-habis') }}" style="font-family: var(--font-mono); font-size: 0.75rem; color: var(--lime); text-decoration: underline;">
                        Lihat Semua &rarr;
                    </a>
                @else
                    <a href="{{ route('admin.jatah-habis') }}" style="font-family: var(--font-mono); font-size: 0.75rem; color: var(--lime); text-decoration: underline;">
                        Lihat Semua &rarr;
                    </a>
                @endif
            </div>

            @if ($criticalBulanan->isEmpty())
                <p style="color: var(--teks-redup); margin: var(--spasi-2) 0; font-size: 0.875rem;">
                    Tidak ada sewa Bulanan yang hampir habis saat ini.
                </p>
            @else
                <div style="display: flex; flex-direction: column; gap: var(--spasi-2);">
                    @foreach ($criticalBulanan as $crit)
                        <div style="background-color: #1C1C18; border: 1px solid var(--abu-garis); border-radius: var(--radius-sm); padding: var(--spasi-2) var(--spasi-3); display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <div style="font-weight: 600; color: var(--putih); font-size: 0.875rem;">
                                    {{ $crit['user']?->team_name ?: ($crit['user']?->name ?? 'Pelanggan') }}
                                </div>
                                <div style="font-family: var(--font-mono); font-size: 0.75rem; color: var(--teks-redup);">
                                    {{ $crit['court']?->name }} &middot; {{ $crit['session']?->formattedRange() }}
                                </div>
                            </div>
                            <div style="text-align: right;">
                                <span style="font-family: var(--font-mono); font-size: 0.75rem; font-weight: 700; color: {{ $crit['remaining'] === 0 ? 'var(--merah)' : '#FFB020' }};">
                                    {{ $crit['remaining'] === 0 ? 'Habis' : 'Sisa ' . $crit['remaining'] . 'x' }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Kolom Kanan: Penutupan Slot Mendatang --}}
        <div style="background-color: #171714; border: 1px solid var(--abu-garis); border-radius: var(--radius-md); padding: var(--spasi-4);">
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--abu-garis); padding-bottom: var(--spasi-2); margin-bottom: var(--spasi-3);">
                <h3 style="font-size: 1rem; margin: 0;">
                    Penutupan Slot Mendatang
                </h3>
                @if (!$modePantau)
                    <a href="{{ route('admin.tutup-slot') }}" style="font-family: var(--font-mono); font-size: 0.75rem; color: var(--lime); text-decoration: underline;">
                        Kelola Slot &rarr;
                    </a>
                @endif
            </div>

            @if ($upcomingClosures->isEmpty())
                <p style="color: var(--teks-redup); margin: var(--spasi-2) 0; font-size: 0.875rem;">
                    Tidak ada jadwal pemeliharaan atau penutupan slot lapangan mendatang.
                </p>
            @else
                <div style="display: flex; flex-direction: column; gap: var(--spasi-2);">
                    @foreach ($upcomingClosures as $closure)
                        <div style="background-color: #1C1C18; border: 1px solid var(--abu-garis); border-radius: var(--radius-sm); padding: var(--spasi-2) var(--spasi-3);">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <strong style="color: var(--putih); font-size: 0.875rem;">{{ $closure->court->name }}</strong>
                                <span style="font-family: var(--font-mono); font-size: 0.75rem; color: var(--lime);">
                                    {{ tanggal_indonesia($closure->play_date, false, true) }}
                                </span>
                            </div>
                            <div style="font-size: 0.75rem; color: var(--teks-redup); margin-top: 2px;">
                                {{ $closure->session ? $closure->session->label . ' (' . $closure->session->formattedRange() . ')' : 'Semua Sesi' }} &middot; "{{ $closure->reason }}"
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

    </div>

</div>
@endsection
