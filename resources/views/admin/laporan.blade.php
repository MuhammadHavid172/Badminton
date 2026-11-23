@extends('layouts.admin')

@section('title', 'Laporan Operasional & Keuangan - ' . config('gor.name'))

@section('content')
<div class="kontainer" style="max-width: 1000px; padding-top: var(--spasi-4);">

    {{-- Header Halaman --}}
    <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: var(--spasi-3); margin-bottom: var(--spasi-4);">
        <div>
            <p style="font-family: var(--font-mono); font-size: 0.75rem; text-transform: uppercase; color: var(--lime); letter-spacing: 0.05em; margin: 0 0 4px 0;">
                Akuntansi &amp; Analitik Operasional
            </p>
            <h1 style="font-size: clamp(1.5rem, 4vw, 2rem); margin: 0; line-height: 1.2;">
                Laporan Pendapatan &amp; Transaksi
            </h1>
        </div>

        @php
            $csvRoute = $modePantau ? route('owner.laporan.ekspor-csv', request()->query()) : route('admin.laporan.ekspor-csv', request()->query());
        @endphp
        <div>
            <a href="{{ $csvRoute }}" class="tombol tombol-sekunder" style="min-height: 48px; padding: 0 var(--spasi-3);">
                Unduh CSV Transaksi
            </a>
        </div>
    </div>

    {{-- Filter Rentang Tanggal --}}
    <div style="background-color: #171714; border: 1px solid var(--abu-garis); border-radius: var(--radius-md); padding: var(--spasi-4); margin-bottom: var(--spasi-5);">
        @php
            $filterRoute = $modePantau ? route('owner.laporan') : route('admin.laporan');
        @endphp
        <form method="GET" action="{{ $filterRoute }}" style="display: flex; flex-wrap: wrap; gap: var(--spasi-3); align-items: flex-end;">
            <div style="flex: 1; min-width: 180px;">
                <label for="lap_dari" class="form-label" style="display: block; margin-bottom: 4px; font-size: 0.75rem;">
                    Dari Tanggal
                </label>
                <input type="date"
                       id="lap_dari"
                       name="dari"
                       value="{{ $reportData['dari'] }}"
                       class="form-input"
                       style="width: 100%; min-height: 44px; background-color: #1A1A17; font-size: 0.875rem;">
            </div>

            <div style="flex: 1; min-width: 180px;">
                <label for="lap_sampai" class="form-label" style="display: block; margin-bottom: 4px; font-size: 0.75rem;">
                    Sampai Tanggal
                </label>
                <input type="date"
                       id="lap_sampai"
                       name="sampai"
                       value="{{ $reportData['sampai'] }}"
                       class="form-input"
                       style="width: 100%; min-height: 44px; background-color: #1A1A17; font-size: 0.875rem;">
            </div>

            <div style="display: flex; gap: var(--spasi-2);">
                <button type="submit" class="tombol tombol-primer" style="min-height: 44px; padding: 0 var(--spasi-4); font-size: 0.875rem;">
                    Terapkan Rentang
                </button>
                <a href="{{ $filterRoute }}" class="tombol tombol-sekunder" style="min-height: 44px; padding: 0 var(--spasi-3); font-size: 0.875rem; color: var(--teks-redup);">
                    Reset
                </a>
            </div>
        </form>
    </div>

    {{-- Baris Tipografi Angka Besar Laporan --}}
    <div style="border-top: 1px solid var(--abu-garis); border-bottom: 1px solid var(--abu-garis); padding: var(--spasi-4) 0; margin-bottom: var(--spasi-6); display: flex; flex-wrap: wrap; gap: var(--spasi-4);">
        <div style="flex: 1; min-width: 200px; border-right: 1px solid var(--abu-garis); padding-right: var(--spasi-3);">
            <span style="font-family: var(--font-mono); font-size: 0.6875rem; text-transform: uppercase; color: var(--teks-redup); display: block; margin-bottom: 4px;">
                Total Pendapatan Terverifikasi
            </span>
            <div style="font-family: var(--font-mono); font-size: clamp(1.5rem, 3vw, 2.25rem); font-weight: 700; color: var(--lime); line-height: 1.1;">
                {{ rupiah($reportData['total_pendapatan']) }}
            </div>
            <span style="font-family: var(--font-mono); font-size: 0.6875rem; color: var(--teks-redup);">
                Rentang: {{ tanggal_indonesia($reportData['dari'], false, false) }} &ndash; {{ tanggal_indonesia($reportData['sampai'], false, false) }}
            </span>
        </div>

        <div style="flex: 1; min-width: 180px; border-right: 1px solid var(--abu-garis); padding-right: var(--spasi-3);">
            <span style="font-family: var(--font-mono); font-size: 0.6875rem; text-transform: uppercase; color: var(--teks-redup); display: block; margin-bottom: 4px;">
                Total Transaksi Booking
            </span>
            <div style="font-family: var(--font-mono); font-size: clamp(1.5rem, 3vw, 2.25rem); font-weight: 700; color: var(--putih); line-height: 1.1;">
                {{ $reportData['total_booking'] }}
            </div>
            <span style="font-family: var(--font-mono); font-size: 0.6875rem; color: var(--teks-redup);">
                {{ $reportData['count_harian'] }} Harian &middot; {{ $reportData['count_bulanan'] }} Bulanan
            </span>
        </div>

        <div style="flex: 1; min-width: 200px;">
            <span style="font-family: var(--font-mono); font-size: 0.6875rem; text-transform: uppercase; color: var(--teks-redup); display: block; margin-bottom: 4px;">
                Tingkat Perpanjangan Bulanan
            </span>
            <div style="font-family: var(--font-mono); font-size: clamp(1.5rem, 3vw, 2.25rem); font-weight: 700; color: {{ $reportData['renewal_rate_percent'] >= 50 ? 'var(--lime)' : '#FFB020' }}; line-height: 1.1;">
                {{ $reportData['renewal_rate_percent'] }}%
            </div>
            <span style="font-family: var(--font-mono); font-size: 0.6875rem; color: var(--teks-redup);">
                {{ $reportData['total_renewed_bulanan'] }} dari {{ $reportData['total_ended_bulanan'] }} sewa Bulanan berakhir
            </span>
        </div>
    </div>

    {{-- Dua Bagian: Rincian Metode Bayar & Evaluasi Siklus Bulanan --}}
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: var(--spasi-4); margin-bottom: var(--spasi-6);">

        {{-- Tabel Rincian Metode Bayar --}}
        <div style="background-color: #171714; border: 1px solid var(--abu-garis); border-radius: var(--radius-md); padding: var(--spasi-4);">
            <h2 style="font-size: 1rem; margin: 0 0 var(--spasi-3) 0; border-bottom: 1px solid var(--abu-garis); padding-bottom: var(--spasi-2);">
                Rincian Metode Pembayaran
            </h2>

            @if ($reportData['payment_methods']->isEmpty())
                <p style="color: var(--teks-redup); margin: 0; font-size: 0.875rem;">
                    Belum ada pembayaran yang tercatat dalam rentang tanggal ini.
                </p>
            @else
                <table style="width: 100%; border-collapse: collapse; font-size: 0.875rem;">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--abu-garis); color: var(--teks-redup); font-family: var(--font-mono); font-size: 0.6875rem; text-transform: uppercase;">
                            <th style="padding: 8px 0; text-align: left;">Metode</th>
                            <th style="padding: 8px 0; text-align: center;">Jumlah</th>
                            <th style="padding: 8px 0; text-align: right;">Total Nominal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($reportData['payment_methods'] as $pm)
                            <tr style="border-bottom: 1px solid var(--abu-garis);">
                                <td style="padding: 10px 0; font-family: var(--font-mono); font-weight: 700; color: var(--putih); text-transform: uppercase;">
                                    {{ $pm->method }}
                                </td>
                                <td style="padding: 10px 0; text-align: center; font-family: var(--font-mono); color: var(--teks-redup);">
                                    {{ $pm->count }}x
                                </td>
                                <td style="padding: 10px 0; text-align: right; font-family: var(--font-mono); font-weight: 700; color: var(--lime);">
                                    {{ rupiah($pm->total_amount) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        {{-- Evaluasi Siklus Bulanan --}}
        <div style="background-color: #171714; border: 1px solid var(--abu-garis); border-radius: var(--radius-md); padding: var(--spasi-4);">
            <h2 style="font-size: 1rem; margin: 0 0 var(--spasi-3) 0; border-bottom: 1px solid var(--abu-garis); padding-bottom: var(--spasi-2);">
                Evaluasi Retensi Sewa Bulanan
            </h2>

            <div style="font-family: var(--font-mono); font-size: 0.8125rem; color: var(--teks-redup); margin-bottom: var(--spasi-3); display: flex; flex-direction: column; gap: 4px;">
                <div>Sewa Bulanan Berakhir: <strong style="color: var(--putih);">{{ $reportData['total_ended_bulanan'] }}</strong></div>
                <div>Sewa Berhasil Diperpanjang: <strong style="color: var(--lime);">{{ $reportData['total_renewed_bulanan'] }}</strong></div>
                <div>Tingkat Retensi: <strong style="color: var(--putih);">{{ $reportData['renewal_rate_percent'] }}%</strong></div>
            </div>

            <h3 style="font-size: 0.875rem; margin: var(--spasi-3) 0 var(--spasi-2) 0; color: #FFB020;">
                Sewa Bulanan yang Tidak Diperpanjang:
            </h3>

            @if ($reportData['unrenewed_packages']->isEmpty())
                <p style="color: var(--teks-redup); margin: 0; font-size: 0.8125rem;">
                    Seluruh sewa Bulanan yang berakhir pada periode ini telah berhasil diperpanjang.
                </p>
            @else
                <div style="display: flex; flex-direction: column; gap: var(--spasi-2); max-height: 220px; overflow-y: auto;">
                    @foreach ($reportData['unrenewed_packages'] as $unrenewed)
                        <div style="background-color: #1C1C18; border: 1px solid var(--abu-garis); border-radius: var(--radius-sm); padding: var(--spasi-2) var(--spasi-3); display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <strong style="color: var(--putih); font-size: 0.8125rem;">
                                    {{ $unrenewed['user']?->team_name ?: ($unrenewed['user']?->name ?? 'Pelanggan') }}
                                </strong>
                                <div style="font-family: var(--font-mono); font-size: 0.6875rem; color: var(--teks-redup);">
                                    {{ $unrenewed['court']?->name }} &middot; Berakhir: {{ tanggal_indonesia($unrenewed['last_date'], false, false) }}
                                </div>
                            </div>
                            <div>
                                <span style="font-family: var(--font-mono); font-size: 0.6875rem; color: var(--merah);">
                                    Tidak Lanjut
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

    </div>

</div>
@endsection
