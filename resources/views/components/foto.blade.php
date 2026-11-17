@props([
    'nama' => '',
    'alt' => '',
    'width' => 1200,
    'height' => 800,
    'isHero' => false,
    'lazy' => true,
    'class' => '',
])

@php
    $relPath = 'images/' . ltrim($nama, '/');
    $fullPath = public_path($relPath);
    $adaFoto = !empty($nama) && file_exists($fullPath);

    // Cari varian webp / avif responsif jika ada
    $info = pathinfo($nama);
    $basename = $info['filename'] ?? '';
    $ext = $info['extension'] ?? '';

    $adaWebp = $basename && file_exists(public_path("images/{$basename}.webp"));
    $adaAvif = $basename && file_exists(public_path("images/{$basename}.avif"));

    // Varian ukuran 800, 1600, 2400
    $widths = [800, 1600, 2400];
    $webpSrcset = [];
    $avifSrcset = [];

    foreach ($widths as $w) {
        if (file_exists(public_path("images/{$basename}-{$w}.webp"))) {
            $webpSrcset[] = asset("images/{$basename}-{$w}.webp") . " {$w}w";
        }
        if (file_exists(public_path("images/{$basename}-{$w}.avif"))) {
            $avifSrcset[] = asset("images/{$basename}-{$w}.avif") . " {$w}w";
        }
    }
@endphp

@if ($adaFoto)
    <picture class="foto-wadah {{ $class }}">
        @if (!empty($avifSrcset))
            <source type="image/avif" srcset="{{ implode(', ', $avifSrcset) }}">
        @elseif ($adaAvif)
            <source type="image/avif" srcset="{{ asset("images/{$basename}.avif") }}">
        @endif

        @if (!empty($webpSrcset))
            <source type="image/webp" srcset="{{ implode(', ', $webpSrcset) }}">
        @elseif ($adaWebp)
            <source type="image/webp" srcset="{{ asset("images/{$basename}.webp") }}">
        @endif

        <img
            src="{{ asset($relPath) }}"
            alt="{{ $alt }}"
            width="{{ $width }}"
            height="{{ $height }}"
            class="foto-elemen"
            @if ($isHero)
                fetchpriority="high"
            @elseif ($lazy)
                loading="lazy"
                decoding="async"
            @else
                decoding="async"
            @endif
        >
    </picture>
@else
    <div
        class="foto-fallback {{ $class }}"
        role="img"
        aria-label="{{ $alt ?: 'Tekstur latar belakang lapangan badminton' }}"
        style="aspect-ratio: {{ $width }} / {{ $height }};"
    >
        {{ $slot }}
    </div>
@endif
