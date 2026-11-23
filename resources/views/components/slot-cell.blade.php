@props([
    'slotItem' => [],
    'href' => null,
    'compact' => false,
])

@php
    $status = $slotItem['status'] ?? 'kosong';
    $label = $slotItem['label'] ?? null;
    $category = $slotItem['category'] ?? null;
    $isRemainingOne = $slotItem['is_remaining_one'] ?? false;
    $isMine = $slotItem['is_mine'] ?? false;
    $reason = $slotItem['reason'] ?? null;
    $courtName = $slotItem['court_name'] ?? '';
    $sessionLabel = $slotItem['session_label'] ?? '';
    $bookingUrl = $href ?? ($slotItem['booking_url'] ?? null);

    // Kelas pembungkus berdasarkan status
    $statusClass = match ($status) {
        'kosong' => 'slot-cell-kosong',
        'menunggu_bayar' => 'slot-cell-menunggu-bayar',
        'terisi' => 'slot-cell-terisi',
        'ditutup' => 'slot-cell-ditutup',
        'lewat' => 'slot-cell-lewat',
        default => 'slot-cell-kosong',
    };

    $isClickable = ($status === 'kosong' && !empty($bookingUrl));
@endphp

@if ($isClickable)
    <a
        href="{{ $bookingUrl }}"
        {{ $attributes->merge(['class' => "slot-cell {$statusClass} " . ($compact ? 'slot-cell-compact' : '')]) }}
        data-court-id="{{ $slotItem['court_id'] ?? '' }}"
        data-session-id="{{ $slotItem['session_id'] ?? '' }}"
        data-status="{{ $status }}"
        role="link"
        aria-label="Pesan {{ $courtName }} {{ $sessionLabel }}"
    >
        <div class="slot-content">
            <span class="slot-status-text">Kosong</span>
            <span class="slot-hint-text">Pilih slot &rarr;</span>
        </div>
    </a>
@else
    <div
        {{ $attributes->merge(['class' => "slot-cell {$statusClass} " . ($compact ? 'slot-cell-compact' : '')]) }}
        data-court-id="{{ $slotItem['court_id'] ?? '' }}"
        data-session-id="{{ $slotItem['session_id'] ?? '' }}"
        data-status="{{ $status }}"
        tabindex="0"
        role="region"
        aria-label="{{ $courtName }} {{ $sessionLabel }}: {{ $slotItem['status_label'] ?? ucfirst($status) }}{{ $label ? ' (' . $label . ')' : '' }}"
    >
        {{-- 1. Status: Kosong --}}
        @if ($status === 'kosong')
            <div class="slot-content">
                <span class="slot-status-text">Kosong</span>
                <span class="slot-hint-text">Siap dipesan</span>
            </div>

        {{-- 2. Status: Menunggu Bayar --}}
        @elseif ($status === 'menunggu_bayar')
            <div class="slot-content">
                <span class="slot-status-text">Menunggu Bayar</span>
                <span class="slot-hint-text">Ditahan sementara</span>
            </div>

        {{-- 3. Status: Terisi --}}
        @elseif ($status === 'terisi')
            <div class="slot-content">
                <div class="slot-header-tags">
                    @if ($category)
                        <x-badge-kategori :tipe="$category" />
                    @endif

                    @if ($isRemainingOne)
                        <span class="badge-sisa-satu" title="Tersisa 1 pertemuan sewa Bulanan">
                            Sisa 1x
                        </span>
                    @endif

                    @if ($isMine)
                        <span class="badge-punyamu" title="Booking milik kamu">
                            Punyamu
                        </span>
                    @endif
                </div>

                <span class="slot-pemesan-nama" title="{{ $label }}">
                    {{ $label ?? 'Terisi' }}
                </span>
            </div>

        {{-- 4. Status: Ditutup --}}
        @elseif ($status === 'ditutup')
            <div class="slot-content">
                <span class="slot-status-text">Ditutup</span>
                @if ($reason)
                    <span class="slot-alasan-text" title="{{ $reason }}">
                        {{ Str::limit($reason, 24) }}
                    </span>
                @endif
            </div>

        {{-- 5. Status: Lewat --}}
        @elseif ($status === 'lewat')
            <div class="slot-content">
                <span class="slot-status-text slot-text-dim">Lewat</span>
                <span class="slot-hint-text slot-text-dim">Sesi selesai</span>
            </div>
        @endif
    </div>
@endif
