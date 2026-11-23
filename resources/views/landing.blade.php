@extends('layouts.app')

@section('title', config('gor.name') . ' - Booking Lapangan Badminton')

@section('content')
{{-- ==========================================================================
     SECTION A: HERO (100svh)
     ========================================================================== --}}
<section class="hero-section" aria-label="Pembuka">
    <x-foto nama="hero.jpg" alt="Suasana Lapangan Badminton {{ config('gor.name') }}" :width="2400" :height="1400" :isHero="true" class="hero-bg-media" />
    <div class="scrim-hero" aria-hidden="true"></div>
    <div class="pola-titik" style="position: absolute; inset: 0; opacity: 0.35; pointer-events: none;" aria-hidden="true"></div>

    <div class="kontainer hero-content">
        <div class="hero-main-block">
            {{-- Satu h1 tunggal di halaman --}}
            <h1 class="hero-judul">
                <span>Saatnya</span>
                <span class="teks-lime">Turun</span>
                <span>Lapangan</span>
            </h1>
        </div>

        <div class="hero-bottom-bar">
            <div class="hero-jam-kartu">
                <span class="hero-jam-label">BUKA SETIAP HARI</span>
                <span class="hero-jam-nilai">{{ config('gor.jam_buka') }}</span>
            </div>

            <span class="hero-scroll-hint">Mulai dari sini</span>

            <x-cta href="{{ route('jadwal') }}" teks="LIHAT JADWAL" />
        </div>
    </div>
</section>

{{-- ==========================================================================
     SECTION B: MARQUEE
     ========================================================================== --}}
<div class="marquee-wrapper" aria-hidden="true">
    <div class="marquee-track">
        <div class="marquee-content">
            @for ($i = 0; $i < 6; $i++)
                <span class="marquee-item">{{ config('gor.name') }}</span>
                <span class="marquee-separator">●</span>
            @endfor
        </div>
        <div class="marquee-content">
            @for ($i = 0; $i < 6; $i++)
                <span class="marquee-item">{{ config('gor.name') }}</span>
                <span class="marquee-separator">●</span>
            @endfor
        </div>
    </div>
</div>

{{-- ==========================================================================
     SECTION C: PERNYATAAN
     ========================================================================== --}}
<section class="pernyataan-section reveal" aria-labelledby="judul-pernyataan">
    <x-foto nama="pernyataan.jpg" alt="Tekstur Lantai Lapangan Badminton" :width="2400" :height="1200" class="hero-bg-media" />
    <div class="scrim-penuh" aria-hidden="true"></div>
    <div class="pola-titik" style="position: absolute; inset: 0; opacity: 0.35; pointer-events: none;" aria-hidden="true"></div>

    <div class="kontainer" style="position: relative; z-index: 2; text-align: center;">
        <x-penanda teks="KEMUDAHAN BOOKING" style="margin: 0 auto var(--spasi-3);" />

        <h2 id="judul-pernyataan" class="pernyataan-judul">
            Booking lapangan, tanpa ribet.
        </h2>

        <p class="pernyataan-teks">
            Lihat lapangan, hari, dan sesi yang kosong, lalu booking dari HP. Tidak perlu bertanya jadwal satu per satu.
        </p>

        <div style="display: flex; justify-content: center;">
            <x-cta href="{{ route('jadwal') }}" teks="BOOKING LAPANGAN" />
        </div>
    </div>
</section>

{{-- ==========================================================================
     SECTION D: CARA BOOKING
     ========================================================================== --}}
<section id="cara-booking" class="cara-booking-section reveal" aria-labelledby="judul-cara-booking">
    <div class="kontainer">
        <div class="cara-booking-header">
            <h2 id="judul-cara-booking" style="font-size: clamp(1.75rem, 3.5vw, 2.75rem); text-transform: uppercase;">
                CARA BOOKING
            </h2>

            <div class="cara-booking-nav">
                <span style="font-size: 0.8125rem; color: var(--teks-redup); text-transform: uppercase; letter-spacing: 0.04em;">
                    Geser untuk lihat semua
                </span>
                <button id="langkah-prev" class="langkah-nav-btn" type="button" aria-label="Geser ke langkah sebelumnya">
                    <span aria-hidden="true">&larr;</span>
                </button>
                <button id="langkah-next" class="langkah-nav-btn" type="button" aria-label="Geser ke langkah berikutnya">
                    <span aria-hidden="true">&rarr;</span>
                </button>
            </div>
        </div>

        <div class="scroll-kartu-pembungkus">
            <div id="langkah-deretan" class="scroll-kartu-deretan" tabindex="0" aria-label="Daftar lima langkah booking">
                {{-- 01 Lihat Jadwal --}}
                <x-kartu class="kartu-langkah">
                    <x-foto nama="langkah-1.jpg" alt="Langkah 1 Lihat Jadwal Lapangan" :width="900" :height="1200" :lazy="false" class="langkah-foto" />
                    <div class="langkah-angka">01</div>
                    <h3 class="langkah-judul">Lihat jadwal</h3>
                    <p>Pilih hari, lalu lihat lapangan dan sesi yang masih kosong.</p>
                </x-kartu>

                {{-- 02 Pilih Harian atau Bulanan --}}
                <x-kartu class="kartu-langkah">
                    <x-foto nama="langkah-2.jpg" alt="Langkah 2 Pilih Paket Harian atau Bulanan" :width="900" :height="1200" :lazy="false" class="langkah-foto" />
                    <div class="langkah-angka">02</div>
                    <h3 class="langkah-judul">Pilih Harian atau Bulanan</h3>
                    <p>Harian untuk satu kali main. Bulanan untuk 4 pertemuan mingguan di lapangan, sesi, dan hari yang sama.</p>
                </x-kartu>

                {{-- 03 Bayar --}}
                <x-kartu class="kartu-langkah">
                    <x-foto nama="langkah-3.jpg" alt="Langkah 3 Konfirmasi dan Bayar" :width="900" :height="1200" :lazy="false" class="langkah-foto" />
                    <div class="langkah-angka">03</div>
                    <h3 class="langkah-judul">Bayar</h3>
                    <p>Slot ditahan selama {{ config('gor.hold_minutes') }} menit. Lewat dari itu, otomatis dilepas.</p>
                </x-kartu>

                {{-- 04 Main --}}
                <x-kartu class="kartu-langkah">
                    <x-foto nama="langkah-4.jpg" alt="Langkah 4 Tunjukkan Kode dan Main" :width="900" :height="1200" :lazy="false" class="langkah-foto" />
                    <div class="langkah-angka">04</div>
                    <h3 class="langkah-judul">Main</h3>
                    <p>Tunjukkan kode booking ke admin di lapangan.</p>
                </x-kartu>

                {{-- 05 Lanjutkan --}}
                <x-kartu class="kartu-langkah">
                    <x-foto nama="langkah-5.jpg" alt="Langkah 5 Perpanjang Jadwal Bulanan" :width="900" :height="1200" :lazy="false" class="langkah-foto" />
                    <div class="langkah-angka">05</div>
                    <h3 class="langkah-judul">Lanjutkan</h3>
                    <p>Jatah Bulanan tinggal 1 pertemuan? Perpanjang sebelum slotnya diambil orang lain. Siapa cepat dia dapat.</p>
                </x-kartu>
            </div>
        </div>
    </div>
</section>

{{-- ==========================================================================
     SECTION E: PILIH LAPANGAN
     ========================================================================== --}}
<section class="pilih-lapangan-section reveal" aria-labelledby="judul-pilih-lapangan">
    <x-foto nama="lapangan-besar.jpg" alt="Pilihan Lapangan Badminton {{ config('gor.name') }}" :width="2400" :height="1400" :lazy="false" class="hero-bg-media" />
    <div class="scrim-penuh" aria-hidden="true"></div>
    <div class="pola-titik" style="position: absolute; inset: 0; opacity: 0.35; pointer-events: none;" aria-hidden="true"></div>

    <div class="kontainer" style="position: relative; z-index: 3;">
        <x-penanda teks="PILIH LAPANGANMU" />

        <h2 id="judul-pilih-lapangan" class="pilih-lapangan-judul">
            <span>Pilih</span>
            <span>Slot</span>
            <span>Mainmu</span>
        </h2>

        {{-- Daftar nama lapangan: hanya dirender jika config terisi --}}
        @if (!empty(config('gor.daftar_lapangan')))
            <div class="lapangan-pil-daftar">
                @foreach (config('gor.daftar_lapangan') as $namaLapangan)
                    <span class="lapangan-pil-item">{{ $namaLapangan }}</span>
                @endforeach
            </div>
        @endif

        <div class="pilih-lapangan-bawah">
            <x-cta href="{{ route('jadwal') }}" teks="LIHAT JADWAL" />
        </div>
    </div>
</section>

{{-- ==========================================================================
     SECTION HARI INI (PAPAN RINGKAS JADWAL)
     ========================================================================== --}}
@if (isset($todaySchedule) && !empty($todaySchedule['grid']))
<section class="jadwal-hari-ini-section reveal" aria-labelledby="judul-jadwal-hari-ini">
    <div class="kontainer">
        <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: var(--spasi-4); flex-wrap: wrap; gap: var(--spasi-2);">
            <div>
                <x-penanda teks="STATUS HARI INI" style="margin-bottom: var(--spasi-2);" />
                <h2 id="judul-jadwal-hari-ini" style="font-size: clamp(2rem, 4vw, 3rem); font-family: var(--font-judul); font-weight: 600; text-transform: uppercase;">
                    JADWAL HARI INI
                </h2>
                <span style="font-size: 0.9375rem; color: var(--teks-redup);">
                    {{ $todaySchedule['date_formatted'] }} (Zona WIT)
                </span>
            </div>

            <x-cta href="{{ route('jadwal') }}" teks="LIHAT SEMUA JADWAL" />
        </div>

        {{-- Desktop Grid --}}
        <div class="papan-desktop-view papan-jadwal-wrapper">
            <table class="papan-table" aria-label="Tabel Jadwal Lapangan Hari Ini">
                <thead>
                    <tr>
                        <th scope="col" class="th-sesi-col">Sesi Waktu</th>
                        @foreach ($todaySchedule['courts'] as $court)
                            <th scope="col">{{ $court['name'] }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($todaySchedule['grid'] as $row)
                        <tr>
                            <td class="sesi-cell">
                                <span class="sesi-label-badge">{{ $row['session']['label'] }}</span>
                                <span class="sesi-time-range">{{ $row['session']['time_range'] }} WIT</span>
                                @if ($row['session']['is_current'])
                                    <div class="badge-sedang-main">
                                        <span class="badge-sedang-main-dot" aria-hidden="true"></span>
                                        <span>Sedang Main</span>
                                    </div>
                                @endif
                            </td>
                            @foreach ($row['courts'] as $slot)
                                <td>
                                    <x-slot-cell :slotItem="$slot" :compact="true" />
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Mobile Grid (< 640px) --}}
        <div class="papan-mobile-view">
            @foreach ($todaySchedule['grid'] as $row)
                <div class="mobile-session-card">
                    <div class="mobile-session-header">
                        <div>
                            <span class="mobile-session-title">{{ $row['session']['label'] }}</span>
                            <span class="sesi-time-range">{{ $row['session']['time_range'] }} WIT</span>
                        </div>
                        @if ($row['session']['is_current'])
                            <div class="badge-sedang-main">
                                <span class="badge-sedang-main-dot" aria-hidden="true"></span>
                                <span>Sedang Main</span>
                            </div>
                        @endif
                    </div>
                    <div class="mobile-courts-grid">
                        @foreach ($row['courts'] as $slot)
                            <div class="mobile-court-item">
                                <span class="mobile-court-label">{{ $slot['court_name'] }}</span>
                                <x-slot-cell :slotItem="$slot" :compact="true" />
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- ==========================================================================
     SECTION F: HARGA
     ========================================================================== --}}
<section id="harga" class="harga-section reveal" aria-labelledby="judul-harga">
    <div class="kontainer">
        <x-penanda teks="TRANSPARAN" style="margin-bottom: var(--spasi-3);" />

        <h2 id="judul-harga" style="font-size: clamp(2rem, 4vw, 3.5rem); text-transform: uppercase;">
            PILIHAN SEWA
        </h2>

        <div class="harga-grid">
            {{-- Kolom Harian --}}
            <div class="harga-col">
                <div>
                    <x-badge-kategori tipe="harian" />

                    <div class="harga-angka-grup" style="margin-top: var(--spasi-3);">
                        <span class="harga-angka">{{ rupiah(config('gor.harga.single')) }}</span>
                        <span class="harga-periode">/ sesi</span>
                        @if (config('gor.harga.is_placeholder') && app()->isLocal())
                            <span class="tag-contoh">Contoh harga</span>
                        @endif
                    </div>

                    <p style="color: var(--putih); font-weight: 500; margin-top: var(--spasi-2);">
                        1 sesi, di lapangan dan jam pilihanmu.
                    </p>

                    <ul class="harga-fitur-list" role="list">
                        <li class="harga-fitur-item">
                            <span class="harga-fitur-titik" aria-hidden="true"></span>
                            <span>Slot ditahan {{ config('gor.hold_minutes') }} menit sampai kamu bayar.</span>
                        </li>
                        <li class="harga-fitur-item">
                            <span class="harga-fitur-titik" aria-hidden="true"></span>
                            <span>Pilihan fleksibel untuk main santai kapan saja.</span>
                        </li>
                    </ul>
                </div>

                <div class="harga-cta-wrap">
                    <x-cta href="{{ route('jadwal') }}" teks="PILIH HARIAN" />
                </div>
            </div>

            {{-- Kolom Bulanan --}}
            <div class="harga-col">
                <div>
                    <x-badge-kategori tipe="bulanan" />

                    <div class="harga-angka-grup" style="margin-top: var(--spasi-3);">
                        <span class="harga-angka">{{ rupiah(config('gor.harga.package')) }}</span>
                        <span class="harga-periode">/ 4 sesi</span>
                        @if (config('gor.harga.is_placeholder') && app()->isLocal())
                            <span class="tag-contoh">Contoh harga</span>
                        @endif
                    </div>

                    <p style="color: var(--putih); font-weight: 500; margin-top: var(--spasi-2);">
                        4 pertemuan mingguan di lapangan, sesi, dan hari yang sama.
                    </p>

                    <ul class="harga-fitur-list" role="list">
                        <li class="harga-fitur-item">
                            <span class="harga-fitur-titik" aria-hidden="true"></span>
                            <span>Keempat tanggal langsung terkunci atas namamu.</span>
                        </li>
                        <li class="harga-fitur-item">
                            <span class="harga-fitur-titik" aria-hidden="true"></span>
                            <span>Penanda 'Sisa 1x' muncul di jadwal saat jatahmu hampir habis.</span>
                        </li>
                        @php
                            $hemat = (4 * config('gor.harga.single')) - config('gor.harga.package');
                        @endphp
                        @if ($hemat > 0)
                            <li class="harga-fitur-item" style="color: var(--lime);">
                                <span class="harga-fitur-titik" aria-hidden="true"></span>
                                <span>Hemat {{ rupiah($hemat) }} dibanding 4 kali booking harian.</span>
                            </li>
                        @endif
                    </ul>
                </div>

                <div class="harga-cta-wrap">
                    <x-cta href="{{ route('jadwal') }}" teks="PILIH BULANAN" />
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ==========================================================================
     SECTION G: FAQ
     ========================================================================== --}}
<section id="faq" class="faq-section reveal" aria-labelledby="judul-faq">
    <div class="kontainer">
        <x-penanda teks="TANYA JAWAB" style="margin-bottom: var(--spasi-3);" />

        <h2 id="judul-faq" style="font-size: clamp(2rem, 4vw, 3.5rem); text-transform: uppercase; margin-bottom: var(--spasi-6);">
            PERTANYAAN UMUM
        </h2>

        <div class="faq-list">
            <details class="faq-item">
                <summary class="faq-summary">
                    <span>Apa beda Harian dan Bulanan?</span>
                    <span class="faq-icon" aria-hidden="true">+</span>
                </summary>
                <div class="faq-body">
                    Harian untuk booking 1 kali main pada tanggal dan jam pilihanmu. Bulanan untuk 4 pertemuan mingguan di hari, jam, dan lapangan yang sama secara otomatis terkunci.
                </div>
            </details>

            <details class="faq-item">
                <summary class="faq-summary">
                    <span>Bagaimana kalau aku belum sempat bayar?</span>
                    <span class="faq-icon" aria-hidden="true">+</span>
                </summary>
                <div class="faq-body">
                    Slot yang kamu pilih akan ditahan selama {{ config('gor.hold_minutes') }} menit. Jika pembayaran belum diselesaikan dalam batas waktu tersebut, slot otomatis dilepas kembali ke jadwal agar bisa dipilih orang lain.
                </div>
            </details>

            <details class="faq-item">
                <summary class="faq-summary">
                    <span>Bagaimana kalau jatah Bulanan habis?</span>
                    <span class="faq-icon" aria-hidden="true">+</span>
                </summary>
                <div class="faq-body">
                    Saat jatahmu tersisa 1 pertemuan terakhir, penanda 'Sisa 1x' muncul di jadwalmu. Kamu bisa segera melakukan perpanjangan sebelum slotnya diambil pemain lain. Berlaku sistem siapa cepat dia dapat.
                </div>
            </details>

            <details class="faq-item">
                <summary class="faq-summary">
                    <span>Kapan aku bisa booking?</span>
                    <span class="faq-icon" aria-hidden="true">+</span>
                </summary>
                <div class="faq-body">
                    Kamu bisa melihat jadwal dan melakukan booking setiap hari selama jam operasional kami, yaitu {{ config('gor.jam_buka') }}.
                </div>
            </details>
        </div>
    </div>
</section>

{{-- ==========================================================================
     SECTION H: PENUTUP BESAR
     ========================================================================== --}}
<section class="penutup-section reveal" aria-labelledby="judul-penutup">
    <x-foto nama="cta.jpg" alt="Suasana Lapangan Badminton {{ config('gor.name') }}" :width="2400" :height="1200" :lazy="false" class="hero-bg-media" />
    <div class="scrim-penuh" aria-hidden="true"></div>
    <div class="pola-titik" style="position: absolute; inset: 0; opacity: 0.35; pointer-events: none;" aria-hidden="true"></div>

    <div class="kontainer" style="position: relative; z-index: 3; text-align: center;">
        <h2 id="judul-penutup" class="penutup-judul">
            Siap main?
        </h2>

        <div class="penutup-aksi">
            <x-cta href="{{ route('jadwal') }}" teks="LIHAT JADWAL" />
            <a href="{{ route('daftar') }}" class="tombol tombol-solid">Daftar</a>
        </div>
    </div>
</section>
@endsection
