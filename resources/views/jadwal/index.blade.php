@extends('layouts.app')

@section('title', 'Jadwal Lapangan - ' . config('gor.name'))

@section('content')
<main id="konten-utama" class="jadwal-page">
    <div class="kontainer">
        {{-- Header Jadwal --}}
        <header class="jadwal-header">
            <div class="jadwal-title-row">
                <div class="jadwal-title-group">
                    <span class="jadwal-subtitle">Jadwal Lapangan</span>
                    <h1 class="jadwal-main-date">
                        @if ($tampilan === 'hari')
                            {{ $dayData['date_formatted'] ?? tanggal_indonesia($targetDate, true, false) }}
                        @else
                            {{ $monthData['title'] ?? '' }}
                        @endif
                    </h1>
                </div>

                {{-- Toggle Tampilan: Per Hari | Kalender --}}
                <nav class="tampilan-toggle" aria-label="Pilihan Tampilan Jadwal">
                    <a
                        href="{{ route('jadwal', ['tampilan' => 'hari', 'tanggal' => $tanggal]) }}"
                        class="tampilan-toggle-item {{ $tampilan === 'hari' ? 'active' : '' }}"
                    >
                        Per hari
                    </a>
                    <a
                        href="{{ route('jadwal', ['tampilan' => 'kalender', 'tanggal' => $tanggal, 'bulan' => substr($tanggal, 0, 7)]) }}"
                        class="tampilan-toggle-item {{ $tampilan === 'kalender' ? 'active' : '' }}"
                    >
                        Kalender
                    </a>
                </nav>
            </div>

            {{-- Navigasi Tanggal (Hanya di Tampilan Hari) --}}
            @if ($tampilan === 'hari')
                <div class="jadwal-nav-controls">
                    <div class="nav-buttons-group">
                        <a
                            href="{{ $prevDateStr ? route('jadwal', ['tampilan' => 'hari', 'tanggal' => $prevDateStr]) : '#' }}"
                            class="nav-btn {{ !$prevDateStr ? 'disabled' : '' }}"
                            aria-label="Hari sebelumnya"
                        >
                            &larr; Sebelumnya
                        </a>

                        <a
                            href="{{ route('jadwal', ['tampilan' => 'hari', 'tanggal' => $todayStr]) }}"
                            class="nav-btn nav-btn-today {{ $tanggal === $todayStr ? 'active' : '' }}"
                        >
                            Hari ini
                        </a>

                        <a
                            href="{{ $nextDateStr ? route('jadwal', ['tampilan' => 'hari', 'tanggal' => $nextDateStr]) : '#' }}"
                            class="nav-btn {{ !$nextDateStr ? 'disabled' : '' }}"
                            aria-label="Hari berikutnya"
                        >
                            Berikutnya &rarr;
                        </a>

                        <form action="{{ route('jadwal') }}" method="GET" style="display: inline-flex; margin: 0;">
                            <input type="hidden" name="tampilan" value="hari">
                            <input
                                type="date"
                                name="tanggal"
                                value="{{ $tanggal }}"
                                min="{{ $minDateStr }}"
                                max="{{ $maxDateStr }}"
                                class="nav-date-input"
                                aria-label="Pilih tanggal jadwal"
                                onchange="this.form.submit()"
                            >
                        </form>
                    </div>

                    {{-- Strip 7 Hari Terdekat (Jalan Pintas) --}}
                    <div class="strip-hari-container" role="navigation" aria-label="Jalan pintas 7 hari">
                        @foreach ($stripHari as $item)
                            <a
                                href="{{ route('jadwal', ['tampilan' => 'hari', 'tanggal' => $item['date']]) }}"
                                class="strip-hari-btn {{ $item['is_active'] ? 'active' : '' }}"
                                aria-current="{{ $item['is_active'] ? 'date' : 'false' }}"
                            >
                                <span class="strip-hari-name">{{ $item['is_today'] ? 'Hari Ini' : $item['day_short'] }}</span>
                                <span class="strip-hari-num">{{ $item['day_num'] }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Navigasi Bulan (Hanya di Tampilan Kalender) --}}
            @if ($tampilan === 'kalender')
                <div class="kalender-month-nav">
                    <a
                        href="{{ $monthData['can_prev'] ? route('jadwal', ['tampilan' => 'kalender', 'bulan' => $monthData['prev_bulan']]) : '#' }}"
                        class="nav-btn {{ !$monthData['can_prev'] ? 'disabled' : '' }}"
                        aria-label="Bulan sebelumnya"
                    >
                        &larr; Bulan Sebelumnya
                    </a>

                    <a
                        href="{{ route('jadwal', ['tampilan' => 'kalender', 'bulan' => substr($todayStr, 0, 7)]) }}"
                        class="nav-btn nav-btn-today"
                    >
                        Bulan Ini
                    </a>

                    <a
                        href="{{ $monthData['can_next'] ? route('jadwal', ['tampilan' => 'kalender', 'bulan' => $monthData['next_bulan']]) : '#' }}"
                        class="nav-btn {{ !$monthData['can_next'] ? 'disabled' : '' }}"
                        aria-label="Bulan berikutnya"
                    >
                        Bulan Berikutnya &rarr;
                    </a>
                </div>
            @endif
        </header>

        {{-- KONTEN 1: TAMPILAN PER HARI --}}
        @if ($tampilan === 'hari' && $dayData)
            {{-- Papan Desktop Grid --}}
            <div class="papan-desktop-view papan-jadwal-wrapper" id="papan-jadwal-container">
                <table class="papan-table" aria-label="Tabel Jadwal Lapangan {{ $dayData['date_formatted'] }}">
                    <thead>
                        <tr>
                            <th scope="col" class="th-sesi-col">Sesi Waktu</th>
                            @foreach ($dayData['courts'] as $court)
                                <th scope="col">{{ $court['name'] }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($dayData['grid'] as $row)
                            <tr>
                                {{-- Kolom Info Sesi --}}
                                <td class="sesi-cell">
                                    <span class="sesi-label-badge">{{ $row['session']['label'] }}</span>
                                    <span class="sesi-time-range">{{ $row['session']['time_range'] }} WIT</span>

                                    @if ($row['session']['is_current'])
                                        <div class="badge-sedang-main" aria-label="Sesi sedang berlangsung">
                                            <span class="badge-sedang-main-dot" aria-hidden="true"></span>
                                            <span>Sedang Main</span>
                                        </div>
                                    @endif
                                </td>

                                {{-- Kolom Tiap Lapangan --}}
                                @foreach ($row['courts'] as $slot)
                                    <td>
                                        <x-slot-cell :slotItem="$slot" />
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Papan Mobile View (< 600px): Blok Sesi Bertumpuk --}}
            <div class="papan-mobile-view" id="papan-mobile-container">
                @foreach ($dayData['grid'] as $row)
                    <section class="mobile-session-card" aria-label="{{ $row['session']['label'] }}">
                        <div class="mobile-session-header">
                            <div>
                                <h2 class="mobile-session-title">{{ $row['session']['label'] }}</h2>
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
                    </section>
                @endforeach
            </div>
        @endif

        {{-- KONTEN 2: TAMPILAN KALENDER --}}
        @if ($tampilan === 'kalender' && $monthData)
            <div class="kalender-container" aria-label="Kalender Jadwal {{ $monthData['title'] }}">
                {{-- Header Hari Kalender (Mulai Senin) --}}
                <div class="kalender-grid-header" role="row">
                    <div class="kalender-header-cell" role="columnheader">Sen</div>
                    <div class="kalender-header-cell" role="columnheader">Sel</div>
                    <div class="kalender-header-cell" role="columnheader">Rab</div>
                    <div class="kalender-header-cell" role="columnheader">Kam</div>
                    <div class="kalender-header-cell" role="columnheader">Jum</div>
                    <div class="kalender-header-cell" role="columnheader">Sab</div>
                    <div class="kalender-header-cell" role="columnheader">Min</div>
                </div>

                {{-- Badan Grid Kalender --}}
                <div class="kalender-grid-body">
                    {{-- Kotak Kosong Padding Awal Bulan --}}
                    @for ($p = 0; $p < $monthData['start_padding']; $p++)
                        <div class="kalender-day-cell kalender-day-empty" aria-hidden="true"></div>
                    @endfor

                    {{-- Hari dalam Bulan --}}
                    @foreach ($monthData['days'] as $d)
                        <a
                            href="{{ route('jadwal', ['tampilan' => 'hari', 'tanggal' => $d['date']]) }}"
                            class="kalender-day-cell {{ $d['is_today'] ? 'is-today' : '' }} {{ $d['is_past'] ? 'is-past' : '' }}"
                            aria-label="{{ $d['day'] }} {{ $monthData['month_name'] }}: {{ $d['empty_count'] }} slot kosong"
                        >
                            <div class="kalender-day-header">
                                <span class="kalender-day-number">{{ $d['day'] }}</span>
                                <span class="kalender-empty-count">{{ $d['empty_count'] }} kosong</span>
                            </div>

                            {{-- Mini Matrix (Disembunyikan di HP) --}}
                            <div class="kalender-mini-matrix" aria-hidden="true">
                                @foreach ($d['matrix'] as $mRow)
                                    <div class="mini-matrix-row">
                                        @foreach ($mRow as $mCell)
                                            <span
                                                class="mini-matrix-dot {{ $mCell['status'] }} {{ $mCell['is_package'] ? 'has-package' : '' }}"
                                                title="Sesi Lapangan"
                                            ></span>
                                        @endforeach
                                    </div>
                                @endforeach
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Legenda Status Slot --}}
        <footer class="jadwal-legenda" aria-label="Legenda Status Slot">
            <div class="legenda-item">
                <span class="legenda-swatch swatch-kosong" aria-hidden="true"></span>
                <span>Kosong (Dapat dipesan)</span>
            </div>
            <div class="legenda-item">
                <span class="legenda-swatch swatch-menunggu" aria-hidden="true"></span>
                <span>Menunggu Bayar (Ditahan 15 menit)</span>
            </div>
            <div class="legenda-item">
                <span class="legenda-swatch swatch-terisi" aria-hidden="true"></span>
                <span>Terisi (Sudah dibayar)</span>
            </div>
            <div class="legenda-item">
                <span class="legenda-swatch swatch-ditutup" aria-hidden="true"></span>
                <span>Ditutup (Perawatan / Acara)</span>
            </div>
            <div class="legenda-item">
                <span class="legenda-swatch swatch-lewat" aria-hidden="true"></span>
                <span>Lewat (Sesi berakhir)</span>
            </div>
        </footer>
    </div>
</main>

{{-- Script Pembaruan Otomatis 60 Detik Tanpa Reload (Jeda saat tab tidak aktif) --}}
@if ($tampilan === 'hari')
<script>
    (function () {
        const tanggalTarget = '{{ $tanggal }}';
        let refreshTimer = null;

        function refreshScheduleData() {
            if (document.hidden) {
                return; // Jeda jika tab browser sedang tidak aktif
            }

            fetch('/api/jadwal/hari?tanggal=' + encodeURIComponent(tanggalTarget))
                .then(res => res.json())
                .then(result => {
                    if (result && result.status === 'success' && result.data && result.data.grid) {
                        updateDomGrid(result.data.grid);
                    }
                })
                .catch(err => {
                    console.warn('Gagal memuat pembaruan jadwal otomatis:', err);
                });
        }

        function updateDomGrid(gridData) {
            gridData.forEach(sessionRow => {
                sessionRow.courts.forEach(courtSlot => {
                    const selector = `[data-court-id="${courtSlot.court_id}"][data-session-id="${courtSlot.session_id}"]`;
                    const cells = document.querySelectorAll(selector);

                    cells.forEach(cell => {
                        const currentStatus = cell.getAttribute('data-status');
                        if (currentStatus !== courtSlot.status) {
                            // Update kelas status
                            cell.className = 'slot-cell slot-cell-' + courtSlot.status.replace('_', '-');
                            cell.setAttribute('data-status', courtSlot.status);

                            // Update konten teks ringkas
                            let html = '<div class="slot-content">';
                            if (courtSlot.status === 'kosong') {
                                html += '<span class="slot-status-text">Kosong</span><span class="slot-hint-text">Siap dipesan</span>';
                            } else if (courtSlot.status === 'menunggu_bayar') {
                                html += '<span class="slot-status-text">Menunggu Bayar</span><span class="slot-hint-text">Ditahan sementara</span>';
                            } else if (courtSlot.status === 'terisi') {
                                html += '<div class="slot-header-tags">';
                                if (courtSlot.category) {
                                    const catType = courtSlot.category.toLowerCase() === 'bulanan' ? 'bulanan' : 'harian';
                                    html += `<span class="badge-kategori ${catType}">${courtSlot.category.toUpperCase()}</span>`;
                                }
                                if (courtSlot.is_remaining_one) {
                                    html += '<span class="badge-sisa-satu">Sisa 1x</span>';
                                }
                                html += `</div><span class="slot-pemesan-nama">${courtSlot.label || 'Terisi'}</span>`;
                            } else if (courtSlot.status === 'ditutup') {
                                html += `<span class="slot-status-text">Ditutup</span><span class="slot-alasan-text">${courtSlot.label || ''}</span>`;
                            } else if (courtSlot.status === 'lewat') {
                                html += '<span class="slot-status-text slot-text-dim">Lewat</span><span class="slot-hint-text slot-text-dim">Sesi selesai</span>';
                            }
                            html += '</div>';
                            cell.innerHTML = html;
                        }
                    });
                });
            });
        }

        // Interval 60 detik
        refreshTimer = setInterval(refreshScheduleData, 60000);

        // Resume langsung saat tab kembali aktif
        document.addEventListener('visibilitychange', function () {
            if (!document.hidden) {
                refreshScheduleData();
            }
        });
    })();
</script>
@endif
@endsection
