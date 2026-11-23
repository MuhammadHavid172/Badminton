@extends('layouts.admin')

@section('title', 'Daftar Booking - Admin - ' . config('gor.name'))

@section('content')
<div class="kontainer" style="max-width: 1000px; padding-top: var(--spasi-4);">

    {{-- Header Halaman --}}
    <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: var(--spasi-3); margin-bottom: var(--spasi-4);">
        <div>
            <p style="font-family: var(--font-mono); font-size: 0.75rem; text-transform: uppercase; color: var(--lime); letter-spacing: 0.05em; margin: 0 0 4px 0;">
                Data Transaksi &amp; Reservasi
            </p>
            <h1 style="font-size: clamp(1.5rem, 4vw, 2rem); margin: 0; line-height: 1.2;">
                Daftar Semua Booking
            </h1>
        </div>

        @php
            $bIndexCsv = ($modePantau ?? false) ? route('owner.laporan.ekspor-csv', request()->query()) : route('admin.booking.ekspor-csv', request()->query());
            $bIndexAction = ($modePantau ?? false) ? route('owner.booking.index') : route('admin.booking.index');
        @endphp
        <div style="display: flex; align-items: center; gap: var(--spasi-2);">
            <a href="{{ $bIndexCsv }}" class="tombol tombol-sekunder" style="min-height: 48px; padding: 0 var(--spasi-3);">
                Ekspor CSV (Sesuai Filter)
            </a>
            @if (!($modePantau ?? false))
                <a href="{{ route('admin.booking-langsung') }}" class="tombol tombol-primer" style="min-height: 48px; padding: 0 var(--spasi-3);">
                    + Booking Langsung
                </a>
            @endif
        </div>
    </div>

    {{-- Filter Bar --}}
    <div style="background-color: #171714; border: 1px solid var(--abu-garis); border-radius: var(--radius-md); padding: var(--spasi-4); margin-bottom: var(--spasi-4);">
        <form method="GET" action="{{ $bIndexAction }}" style="display: flex; flex-direction: column; gap: var(--spasi-3);">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: var(--spasi-2);">
                <div>
                    <label for="filter_dari" class="form-label" style="display: block; margin-bottom: 2px; font-size: 0.75rem;">
                        Dari Tanggal
                    </label>
                    <input type="date"
                           id="filter_dari"
                           name="dari"
                           value="{{ request('dari') }}"
                           class="form-input"
                           style="width: 100%; min-height: 44px; background-color: #1A1A17; font-size: 0.8125rem;">
                </div>

                <div>
                    <label for="filter_sampai" class="form-label" style="display: block; margin-bottom: 2px; font-size: 0.75rem;">
                        Sampai Tanggal
                    </label>
                    <input type="date"
                           id="filter_sampai"
                           name="sampai"
                           value="{{ request('sampai') }}"
                           class="form-input"
                           style="width: 100%; min-height: 44px; background-color: #1A1A17; font-size: 0.8125rem;">
                </div>

                <div>
                    <label for="filter_court" class="form-label" style="display: block; margin-bottom: 2px; font-size: 0.75rem;">
                        Lapangan
                    </label>
                    <select id="filter_court" name="court_id" class="form-input" style="width: 100%; min-height: 44px; background-color: #1A1A17; font-size: 0.8125rem;">
                        <option value="">Semua Lapangan</option>
                        @foreach ($courts as $c)
                            <option value="{{ $c->id }}" {{ request('court_id') == $c->id ? 'selected' : '' }}>
                                {{ $c->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="filter_status" class="form-label" style="display: block; margin-bottom: 2px; font-size: 0.75rem;">
                        Status
                    </label>
                    <select id="filter_status" name="status" class="form-input" style="width: 100%; min-height: 44px; background-color: #1A1A17; font-size: 0.8125rem;">
                        <option value="">Semua Status</option>
                        <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Lunas (PAID)</option>
                        <option value="pending_payment" {{ request('status') === 'pending_payment' ? 'selected' : '' }}>Menunggu Bayar</option>
                        <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Dibatalkan</option>
                        <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>Kedaluwarsa</option>
                    </select>
                </div>

                <div>
                    <label for="filter_type" class="form-label" style="display: block; margin-bottom: 2px; font-size: 0.75rem;">
                        Kategori Sewa
                    </label>
                    <select id="filter_type" name="type" class="form-input" style="width: 100%; min-height: 44px; background-color: #1A1A17; font-size: 0.8125rem;">
                        <option value="">Semua Kategori</option>
                        <option value="single" {{ request('type') === 'single' ? 'selected' : '' }}>Harian</option>
                        <option value="package" {{ request('type') === 'package' ? 'selected' : '' }}>Bulanan</option>
                    </select>
                </div>
            </div>

            <div style="display: flex; gap: var(--spasi-2); align-items: center; flex-wrap: wrap;">
                <div style="flex: 1; min-width: 200px;">
                    <input type="text"
                           name="q"
                           value="{{ request('q') }}"
                           placeholder="Cari kode booking, nama, tim, atau nomor HP..."
                           class="form-input"
                           style="width: 100%; min-height: 44px; background-color: #1A1A17; font-size: 0.875rem;">
                </div>

                <button type="submit" class="tombol tombol-primer" style="min-height: 44px; padding: 0 var(--spasi-3); font-size: 0.875rem;">
                    Terapkan Filter
                </button>

                @if (request()->hasAny(['dari', 'sampai', 'court_id', 'status', 'type', 'q']))
                    <a href="{{ route('admin.booking.index') }}" class="tombol tombol-sekunder" style="min-height: 44px; padding: 0 var(--spasi-3); font-size: 0.875rem; color: var(--teks-redup);">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    {{-- Tabel / Daftar Bertumpuk Booking --}}
    @if ($bookings->isEmpty())
        <div style="background-color: #171714; border: 1px solid var(--abu-garis); border-radius: var(--radius-md); padding: var(--spasi-6); text-align: center;">
            <p style="color: var(--teks-redup); margin: 0; font-size: 1rem;">
                Tidak ada data booking yang sesuai dengan kriteria pencarian atau filter.
            </p>
        </div>
    @else
        {{-- Versi Desktop: Tabel Rapat Garis Tipis --}}
        <div class="admin-table-desktop" style="background-color: #171714; border: 1px solid var(--abu-garis); border-radius: var(--radius-md); overflow: hidden; margin-bottom: var(--spasi-4);">
            <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.875rem;">
                <thead>
                    <tr style="border-bottom: 1px solid var(--abu-garis); background-color: #1C1C18; font-family: var(--font-mono); font-size: 0.6875rem; text-transform: uppercase; color: var(--teks-redup);">
                        <th style="padding: 12px 16px;">Kode</th>
                        <th style="padding: 12px 16px;">Pemesan</th>
                        <th style="padding: 12px 16px;">Kategori</th>
                        <th style="padding: 12px 16px;">Jadwal / Pertemuan</th>
                        <th style="padding: 12px 16px;">Total</th>
                        <th style="padding: 12px 16px;">Status</th>
                        <th style="padding: 12px 16px; text-align: right;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($bookings as $b)
                        @php
                            $rowDetailRoute = ($modePantau ?? false) ? route('owner.booking.detail', $b->code) : route('admin.booking.detail', $b->code);
                        @endphp
                        <tr style="border-bottom: 1px solid var(--abu-garis);">
                            <td style="padding: 12px 16px; font-family: var(--font-mono); font-weight: 700; color: var(--lime);">
                                <a href="{{ $rowDetailRoute }}" style="color: var(--lime); text-decoration: underline;">
                                    {{ $b->code }}
                                </a>
                            </td>
                            <td style="padding: 12px 16px;">
                                <div style="font-weight: 600; color: var(--putih);">
                                    {{ $b->user?->name ?? 'Tamu' }}
                                </div>
                                @if ($b->user?->team_name)
                                    <div style="font-size: 0.75rem; color: var(--teks-redup);">
                                        {{ $b->user->team_name }}
                                    </div>
                                @endif
                                <div style="font-family: var(--font-mono); font-size: 0.75rem; color: var(--teks-redup); margin-top: 2px;">
                                    <a href="tel:{{ $b->user?->phone }}" style="color: var(--teks-redup); text-decoration: underline;">
                                        {{ $b->user?->phone }}
                                    </a>
                                </div>
                            </td>
                            <td style="padding: 12px 16px;">
                                <span class="badge-kategori {{ $b->type->value === 'package' ? 'badge-kategori-bulanan' : 'badge-kategori-harian' }}">
                                    {{ $b->type->label() }}
                                </span>
                            </td>
                            <td style="padding: 12px 16px; font-family: var(--font-mono); font-size: 0.8125rem; color: var(--teks-redup);">
                                @php
                                    $firstSlot = $b->slots->sortBy('play_date')->first();
                                    $slotCount = $b->slots->count();
                                @endphp
                                @if ($firstSlot)
                                    <div style="color: var(--putih);">
                                        {{ $firstSlot->court->name }} &middot; {{ $firstSlot->session->formattedRange() }}
                                    </div>
                                    <div style="font-size: 0.75rem;">
                                        Mulai: {{ tanggal_indonesia($firstSlot->play_date->format('Y-m-d'), false, false) }}
                                        @if ($slotCount > 1)
                                            ({{ $slotCount }} pertemuan)
                                        @endif
                                    </div>
                                @else
                                    <span style="color: var(--merah);">Slot dilepas</span>
                                @endif
                            </td>
                            <td style="padding: 12px 16px; font-family: var(--font-mono); font-weight: 700; color: var(--putih);">
                                {{ rupiah($b->total_price) }}
                            </td>
                            <td style="padding: 12px 16px;">
                                <span style="font-family: var(--font-mono); font-size: 0.75rem; font-weight: 700;
                                    color: {{ $b->status->value === 'paid' ? 'var(--lime)' : ($b->status->value === 'cancelled' ? 'var(--merah)' : '#FFB020') }};">
                                    {{ $b->status->label() }}
                                </span>
                            </td>
                            <td style="padding: 12px 16px; text-align: right;">
                                <a href="{{ $rowDetailRoute }}" class="tombol tombol-sekunder" style="min-height: 38px; padding: 4px 12px; font-size: 0.8125rem;">
                                    Detail
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Versi Mobile: Daftar Bertumpuk (Label & Nilai, Tanpa Scroll Horizontal) --}}
        <div class="admin-list-mobile" style="display: flex; flex-direction: column; gap: var(--spasi-3); margin-bottom: var(--spasi-4);">
            @foreach ($bookings as $b)
                @php
                    $cardDetailRoute = ($modePantau ?? false) ? route('owner.booking.detail', $b->code) : route('admin.booking.detail', $b->code);
                @endphp
                <div style="background-color: #171714; border: 1px solid var(--abu-garis); border-radius: var(--radius-sm); padding: var(--spasi-3); display: flex; flex-direction: column; gap: var(--spasi-2);">
                    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--abu-garis); padding-bottom: var(--spasi-2);">
                        <div style="display: flex; align-items: center; gap: var(--spasi-2);">
                            <span style="font-family: var(--font-mono); font-weight: 700; color: var(--lime);">
                                {{ $b->code }}
                            </span>
                            <span class="badge-kategori {{ $b->type->value === 'package' ? 'badge-kategori-bulanan' : 'badge-kategori-harian' }}">
                                {{ $b->type->label() }}
                            </span>
                        </div>
                        <span style="font-family: var(--font-mono); font-size: 0.75rem; font-weight: 700;
                            color: {{ $b->status->value === 'paid' ? 'var(--lime)' : ($b->status->value === 'cancelled' ? 'var(--merah)' : '#FFB020') }};">
                            {{ $b->status->label() }}
                        </span>
                    </div>

                    <div style="display: flex; justify-content: space-between; font-size: 0.875rem;">
                        <span style="color: var(--teks-redup); font-family: var(--font-mono); font-size: 0.75rem; text-transform: uppercase;">Pemesan</span>
                        <div style="text-align: right;">
                            <strong style="color: var(--putih);">{{ $b->user?->name ?? 'Tamu' }}</strong>
                            @if ($b->user?->phone)
                                <div style="font-family: var(--font-mono); font-size: 0.75rem;">
                                    <a href="tel:{{ $b->user->phone }}" style="color: var(--lime); text-decoration: underline;">{{ $b->user->phone }}</a>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div style="display: flex; justify-content: space-between; font-size: 0.875rem;">
                        <span style="color: var(--teks-redup); font-family: var(--font-mono); font-size: 0.75rem; text-transform: uppercase;">Total</span>
                        <span style="font-family: var(--font-mono); font-weight: 700; color: var(--putih);">{{ rupiah($b->total_price) }}</span>
                    </div>

                    <div>
                        <a href="{{ $cardDetailRoute }}" class="tombol tombol-sekunder" style="width: 100%; min-height: 48px; font-size: 0.875rem;">
                            Buka Rincian Booking
                        </a>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Pagination --}}
        <div style="margin-top: var(--spasi-4);">
            {{ $bookings->links() }}
        </div>
    @endif

</div>
@endsection
