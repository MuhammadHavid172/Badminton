@props([
    'code' => 'DEMO',
    'size' => 200,
])

@php
    $seed = crc32($code);
    mt_srand($seed);
    $gridSize = 17;
    $cellSize = 10;
    $totalWidth = $gridSize * $cellSize;
@endphp

<div class="qr-simulasi-container" style="display: inline-flex; flex-direction: column; align-items: center; background: #FFFFFF; padding: 16px; border-radius: 8px; border: 1px solid #E0E0DA;">
    <svg
        width="{{ $size }}"
        height="{{ $size }}"
        viewBox="0 0 {{ $totalWidth }} {{ $totalWidth }}"
        xmlns="http://www.w3.org/2000/svg"
        role="img"
        aria-label="QR Pembayaran Simulasi untuk kode {{ $code }}"
    >
        <title>QR Pembayaran Simulasi: {{ $code }}</title>
        <!-- Latar Belakang Putih -->
        <rect width="100%" height="100%" fill="#FFFFFF" />

        <!-- 3 Kotak Sudut Marker QR (Kiri Atas, Kanan Atas, Kiri Bawah) -->
        <!-- Kiri Atas -->
        <rect x="0" y="0" width="50" height="50" fill="#1A1A18" />
        <rect x="10" y="10" width="30" height="30" fill="#FFFFFF" />
        <rect x="15" y="15" width="20" height="20" fill="#1A1A18" />

        <!-- Kanan Atas -->
        <rect x="120" y="0" width="50" height="50" fill="#1A1A18" />
        <rect x="130" y="10" width="30" height="30" fill="#FFFFFF" />
        <rect x="135" y="15" width="20" height="20" fill="#1A1A18" />

        <!-- Kiri Bawah -->
        <rect x="0" y="120" width="50" height="50" fill="#1A1A18" />
        <rect x="10" y="130" width="30" height="30" fill="#FFFFFF" />
        <rect x="15" y="135" width="20" height="20" fill="#1A1A18" />

        <!-- Titik Acak Terdeterminasi berdasarkan Kode Booking -->
        @for ($r = 0; $r < $gridSize; $r++)
            @for ($c = 0; $c < $gridSize; $c++)
                @php
                    // Lewati area marker sudut
                    $inTopLeft = ($r < 6 && $c < 6);
                    $inTopRight = ($r < 6 && $c > 10);
                    $inBottomLeft = ($r > 10 && $c < 6);
                    $inCenter = ($r >= 6 && $r <= 10 && $c >= 4 && $c <= 12);
                    $fill = mt_rand(0, 100) > 52;
                @endphp

                @if (!$inTopLeft && !$inTopRight && !$inBottomLeft && !$inCenter && $fill)
                    <rect
                        x="{{ $c * $cellSize }}"
                        y="{{ $r * $cellSize }}"
                        width="{{ $cellSize }}"
                        height="{{ $cellSize }}"
                        fill="#1A1A18"
                    />
                @endif
            @endfor
        @endfor

        <!-- Label SIMULASI di Tengah -->
        <rect x="42" y="68" width="86" height="34" rx="4" fill="#FFB020" stroke="#1A1A18" stroke-width="2" />
        <text
            x="85"
            y="90"
            font-family="monospace, sans-serif"
            font-size="12"
            font-weight="900"
            fill="#1A1A18"
            text-anchor="middle"
            letter-spacing="1"
        >
            SIMULASI
        </text>
    </svg>

    <span style="font-family: var(--font-mono); font-size: 0.75rem; font-weight: 700; color: #666660; margin-top: 8px; letter-spacing: 0.05em;">
        {{ $code }} &middot; BUKAN QR ASLI
    </span>
</div>
