@extends('layouts.admin')

@section('title', 'Admin Operasional - Hari Ini - ' . config('gor.name'))

@section('content')
<div class="kontainer" style="max-width: 900px; padding-top: var(--spasi-4);">

    {{-- Banner Notifikasi Status --}}
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

    {{-- Header Halaman & Kotak Pencarian --}}
    <div style="display: flex; flex-direction: column; gap: var(--spasi-3); margin-bottom: var(--spasi-5);">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: var(--spasi-2);">
            <div>
                <p style="font-family: var(--font-mono); font-size: 0.75rem; text-transform: uppercase; color: var(--lime); letter-spacing: 0.05em; margin: 0 0 4px 0;">
                    Operasional Lapangan &middot; {{ $nowJayapura->isoFormat('dddd') }}
                </p>
                <h1 style="font-size: clamp(1.5rem, 4vw, 2rem); margin: 0; line-height: 1.2;">
                    {{ tanggal_indonesia($todayStr, false, true) }}
                </h1>
            </div>

            @if (!($modePantau ?? false))
                <div style="display: flex; align-items: center; gap: var(--spasi-2);">
                    <a href="{{ route('admin.booking-langsung') }}" class="tombol tombol-primer" style="min-height: 48px; padding: 0 var(--spasi-4);">
                        + Booking Langsung
                    </a>
                </div>
            @endif
        </div>

        {{-- Form Pencarian Cepat --}}
        <form method="GET" action="{{ ($modePantau ?? false) ? route('owner.hari-ini') : route('admin.dashboard') }}" style="display: flex; gap: var(--spasi-2); width: 100%;">
            <div style="flex: 1; position: relative;">
                <input type="text"
                       name="q"
                       value="{{ $searchQuery }}"
                       placeholder="Cari kode booking, nama, tim, atau nomor HP..."
                       class="form-input"
                       style="width: 100%; min-height: 48px; font-size: 1rem; padding: 10px 16px; background-color: #1A1A17; border-color: var(--abu-garis);"
                       aria-label="Cari booking operasional">
            </div>
            <button type="submit" class="tombol tombol-sekunder" style="min-height: 48px; padding: 0 var(--spasi-3); min-width: 80px;">
                Cari
            </button>
            @if (!empty($searchQuery))
                <a href="{{ ($modePantau ?? false) ? route('owner.hari-ini') : route('admin.dashboard') }}" class="tombol tombol-sekunder" style="min-height: 48px; padding: 0 var(--spasi-3); color: var(--teks-redup);">
                    Reset
                </a>
            @endif
        </form>
    </div>

    {{-- Hasil Pencarian Jika Ada Query --}}
    @if ($searchResults !== null)
        <div style="margin-bottom: var(--spasi-6); background-color: #171714; border: 1px solid var(--abu-garis); border-radius: var(--radius-md); padding: var(--spasi-4);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--spasi-3); border-bottom: 1px solid var(--abu-garis); padding-bottom: var(--spasi-2);">
                <h2 style="font-size: 1.125rem; margin: 0;">
                    Hasil Pencarian: "{{ $searchQuery }}"
                </h2>
                <span style="font-family: var(--font-mono); font-size: 0.8125rem; color: var(--teks-redup);">
                    {{ $searchResults->count() }} booking ditemukan
                </span>
            </div>

            @if ($searchResults->isEmpty())
                <p style="color: var(--teks-redup); margin: var(--spasi-3) 0;">
                    Tidak ada booking yang cocok dengan kata kunci pencarian.
                </p>
            @else
                <div style="display: flex; flex-direction: column; gap: var(--spasi-3);">
                    @foreach ($searchResults as $item)
                        <div style="background-color: #1F1F1B; border: 1px solid var(--abu-garis); border-radius: var(--radius-sm); padding: var(--spasi-3); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: var(--spasi-2);">
                            <div>
                                <div style="display: flex; align-items: center; gap: var(--spasi-2); margin-bottom: 4px;">
                                    <span style="font-family: var(--font-mono); font-weight: 700; color: var(--lime);">
                                        {{ $item->code }}
                                    </span>
                                    <span class="badge-kategori {{ $item->type->value === 'package' ? 'badge-kategori-bulanan' : 'badge-kategori-harian' }}">
                                        {{ $item->type->label() }}
                                    </span>
                                    <span style="font-family: var(--font-mono); font-size: 0.75rem; color: var(--teks-redup);">
                                        {{ $item->status->label() }}
                                    </span>
                                </div>
                                <div style="font-weight: 600; color: var(--putih);">
                                    {{ $item->user?->team_name ? $item->user->team_name . ' (' . $item->user->name . ')' : ($item->user?->name ?? 'Tamu') }}
                                </div>
                                <div style="font-family: var(--font-mono); font-size: 0.8125rem; color: var(--teks-redup); margin-top: 2px;">
                                    HP: <a href="tel:{{ $item->user?->phone }}" style="color: var(--putih); text-decoration: underline;">{{ $item->user?->phone }}</a>
                                </div>
                            </div>
                            <div>
                                @php
                                    $searchDetailRoute = ($modePantau ?? false) ? route('owner.booking.detail', $item->code) : route('admin.booking.detail', $item->code);
                                @endphp
                                <a href="{{ $searchDetailRoute }}" class="tombol tombol-sekunder" style="min-height: 48px; padding: 0 16px;">
                                    Buka Detail Booking
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endif

    {{-- Daftar Sesi Operasional Hari Ini --}}
    <div style="display: flex; flex-direction: column; gap: var(--spasi-5);">
        @foreach ($orderedSessions as $sessionData)
            @php
                $session = $sessionData['session'];
                $isCurrent = $sessionData['is_current'];
                $isPast = $sessionData['is_past'];
                $isUpcoming = $sessionData['is_upcoming'];
                $courtItems = $sessionData['court_items'];
            @endphp

            <section class="admin-session-section {{ $isCurrent ? 'is-current' : ($isPast ? 'is-past' : '') }}"
                     style="background-color: #171714; border: 1px solid {{ $isCurrent ? 'var(--lime)' : 'var(--abu-garis)' }}; border-radius: var(--radius-md); overflow: hidden; {{ $isPast ? 'opacity: 0.72;' : '' }}">

                {{-- Header Sesi --}}
                <div style="padding: var(--spasi-3) var(--spasi-4); background-color: {{ $isCurrent ? 'rgba(218, 255, 4, 0.08)' : '#1C1C18' }}; border-bottom: 1px solid {{ $isCurrent ? 'rgba(218, 255, 4, 0.2)' : 'var(--abu-garis)' }}; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: var(--spasi-2);">
                    <div style="display: flex; align-items: center; gap: var(--spasi-3);">
                        <h2 style="font-size: 1.125rem; margin: 0; display: flex; align-items: center; gap: var(--spasi-2);">
                            <span>{{ $session->label }}</span>
                            <span style="font-family: var(--font-mono); font-size: 0.875rem; color: var(--teks-redup); font-weight: 400;">
                                ({{ $session->formattedRange() }})
                            </span>
                        </h2>

                        @if ($isCurrent)
                            <span style="background-color: var(--lime); color: var(--hitam); font-family: var(--font-mono); font-size: 0.6875rem; font-weight: 700; padding: 2px 8px; border-radius: 9999px; text-transform: uppercase;">
                                Sedang Berlangsung
                            </span>
                        @elseif ($isPast)
                            <span style="font-family: var(--font-mono); font-size: 0.6875rem; color: var(--teks-redup); text-transform: uppercase;">
                                Selesai
                            </span>
                        @else
                            <span style="font-family: var(--font-mono); font-size: 0.6875rem; color: var(--teks-redup); text-transform: uppercase;">
                                Akan Datang
                            </span>
                        @endif
                    </div>
                </div>

                {{-- Daftar Lapangan di Dalam Sesi Ini --}}
                <div style="display: flex; flex-direction: column; divide-y: 1px solid var(--abu-garis);">
                    @foreach ($courtItems as $item)
                        @php
                            $court = $item['court'];
                            $slot = $item['slot'];
                            $block = $item['block'];
                        @endphp

                        <div style="padding: var(--spasi-3) var(--spasi-4); border-bottom: 1px solid var(--abu-garis); display: flex; flex-direction: column; gap: var(--spasi-2);">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: var(--spasi-2);">

                                {{-- Identitas Lapangan & Status --}}
                                <div style="flex: 1; min-width: 240px;">
                                    <div style="display: flex; align-items: center; gap: var(--spasi-2); margin-bottom: 4px;">
                                        <span style="font-family: var(--font-mono); font-weight: 700; font-size: 0.9375rem; color: var(--putih);">
                                            {{ $court->name }}
                                        </span>

                                        @if ($block)
                                            <span style="font-family: var(--font-mono); font-size: 0.6875rem; background-color: #5A5A52; color: #FFF; padding: 1px 6px; border-radius: 3px; text-transform: uppercase;">
                                                DITUTUP
                                            </span>
                                        @elseif ($slot && $slot->booking)
                                            <span class="badge-kategori {{ $slot->booking->type->value === 'package' ? 'badge-kategori-bulanan' : 'badge-kategori-harian' }}">
                                                {{ $slot->booking->type->label() }}
                                            </span>

                                            {{-- Chip Bulanan: Pertemuan X dari 4 / Terakhir Hari Ini --}}
                                            @if ($slot->booking->type->value === 'package')
                                                @php
                                                    $lc = new \App\Services\PackageLifecycle($slot->booking);
                                                    $meetingNum = $lc->meetingNumberForSlot($slot);
                                                    $isLastToday = $lc->isLastMeetingToday($nowJayapura);
                                                @endphp

                                                @if ($isLastToday)
                                                    <span style="font-family: var(--font-mono); font-size: 0.6875rem; background-color: #FFB020; color: #000; font-weight: 700; padding: 1px 6px; border-radius: 3px; text-transform: uppercase;">
                                                        TERAKHIR HARI INI
                                                    </span>
                                                @elseif ($meetingNum)
                                                    <span style="font-family: var(--font-mono); font-size: 0.6875rem; background-color: #2E2E28; color: var(--putih); padding: 1px 6px; border-radius: 3px;">
                                                        Pertemuan {{ $meetingNum }} dari 4
                                                    </span>
                                                @endif
                                            @endif
                                        @else
                                            <span style="font-family: var(--font-mono); font-size: 0.6875rem; color: var(--lime); background-color: rgba(218, 255, 4, 0.1); padding: 1px 6px; border-radius: 3px; text-transform: uppercase;">
                                                KOSONG
                                            </span>
                                        @endif
                                    </div>

                                    {{-- Informasi Pemesan / Kondisi Slot --}}
                                    @if ($block)
                                        <p style="margin: 0; color: var(--teks-redup); font-size: 0.875rem;">
                                            Alasan tutup: {{ $block->reason }}
                                        </p>
                                    @elseif ($slot && $slot->booking)
                                        @php
                                            $b = $slot->booking;
                                            $u = $b->user;
                                            $displayName = $u ? ($u->team_name ? $u->team_name . ' (' . $u->name . ')' : $u->name) : 'Pelanggan';
                                        @endphp
                                        <div style="font-size: 1rem; font-weight: 600; color: var(--putih); margin-bottom: 2px;">
                                            {{ $displayName }}
                                        </div>
                                        <div style="display: flex; flex-wrap: wrap; align-items: center; gap: var(--spasi-3); font-family: var(--font-mono); font-size: 0.8125rem;">
                                            @if ($u && $u->phone)
                                                <span>
                                                    HP: <a href="tel:{{ $u->phone }}" style="color: var(--lime); text-decoration: underline;">{{ $u->phone }}</a>
                                                </span>
                                            @endif
                                            <span style="color: var(--teks-redup);">
                                                @php
                                                    $itemDetailRoute = ($modePantau ?? false) ? route('owner.booking.detail', $b->code) : route('admin.booking.detail', $b->code);
                                                @endphp
                                                Kode: <a href="{{ $itemDetailRoute }}" style="color: var(--putih); text-decoration: underline;">{{ $b->code }}</a>
                                            </span>
                                            <span style="color: {{ $b->status->value === 'paid' ? 'var(--lime)' : '#FFB020' }};">
                                                Status: {{ $b->status->label() }}
                                            </span>
                                        </div>

                                        {{-- Catatan Kehadiran Jika Sudah Check-in --}}
                                        @if ($slot->checked_in_at)
                                            <div style="margin-top: 4px; font-size: 0.75rem; font-family: var(--font-mono); color: var(--lime);">
                                                Hadir: {{ \Carbon\Carbon::parse($slot->checked_in_at)->timezone('Asia/Jayapura')->format('H:i') }} WIT
                                                @if ($slot->attendance_note)
                                                    &middot; "{{ $slot->attendance_note }}"
                                                @endif
                                            </div>
                                        @elseif ($isPast)
                                            <div style="margin-top: 4px; font-size: 0.75rem; font-family: var(--font-mono); color: var(--merah);">
                                                Tidak tercatat hadir
                                            </div>
                                        @endif
                                    @else
                                        <p style="margin: 0; color: var(--teks-redup); font-size: 0.875rem;">
                                            Slot belum dipesan.
                                            @if (!($modePantau ?? false))
                                                <a href="{{ route('admin.booking-langsung', ['lapangan' => $court->id, 'sesi' => $session->id, 'tanggal' => $todayStr]) }}" style="color: var(--lime); text-decoration: underline; margin-left: 4px;">
                                                    Isi booking langsung
                                                </a>
                                            @endif
                                        </p>
                                    @endif
                                </div>

                                {{-- Aksi Check-In / Status Kehadiran (Min-height 48px Touch Target) --}}
                                @if ($slot && $slot->booking)
                                    @php
                                        $slotDetailRoute = ($modePantau ?? false) ? route('owner.booking.detail', $slot->booking->code) : route('admin.booking.detail', $slot->booking->code);
                                    @endphp
                                    <div style="display: flex; align-items: center; gap: var(--spasi-2);">
                                        @if (!($modePantau ?? false))
                                            @if ($slot->checked_in_at)
                                                <form action="{{ route('admin.slot.batal-check-in', $slot->id) }}" method="POST" style="margin: 0;">
                                                    @csrf
                                                    <button type="submit"
                                                            class="tombol tombol-sekunder"
                                                            style="min-height: 48px; padding: 0 var(--spasi-3); font-size: 0.875rem; color: var(--teks-redup); border-color: var(--abu-garis);"
                                                            onclick="return confirm('Batalkan pencatatan kehadiran untuk slot ini?');">
                                                        Hadir (Batal)
                                                    </button>
                                                </form>
                                            @else
                                                <form action="{{ route('admin.slot.check-in', $slot->id) }}" method="POST" style="margin: 0; display: flex; gap: var(--spasi-1);">
                                                    @csrf
                                                    <button type="submit"
                                                            class="tombol tombol-primer"
                                                            style="min-height: 48px; padding: 0 var(--spasi-4); font-size: 0.9375rem; font-weight: 700;">
                                                        Sudah Hadir
                                                    </button>
                                                </form>
                                            @endif
                                        @endif

                                        <a href="{{ $slotDetailRoute }}" class="tombol tombol-sekunder" style="min-height: 48px; padding: 0 var(--spasi-3);" title="Rincian Booking">
                                            Rincian
                                        </a>
                                    </div>
                                @elseif ($block)
                                    @if (!($modePantau ?? false))
                                        <div>
                                            <form action="{{ route('admin.tutup-slot.destroy', $block->id) }}" method="POST" style="margin: 0;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="tombol tombol-sekunder" style="min-height: 48px; padding: 0 var(--spasi-3); font-size: 0.8125rem;" onclick="return confirm('Buka kembali slot ini?');">
                                                    Buka Slot
                                                </button>
                                            </form>
                                        </div>
                                    @endif
                                @endif

                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        @endforeach
    </div>

</div>
@endsection
