@props([
    'class' => '',
    'hanyaIkon' => false,
])

<div {{ $attributes->merge(['class' => 'navbar-brand ' . $class]) }}>
    <svg class="logo-icon" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <!-- Titik Bulu Atas Melebar -->
        <circle cx="7" cy="5" r="1.5" fill="#DAFF04" />
        <circle cx="11.5" cy="4" r="1.5" fill="#DAFF04" />
        <circle cx="16" cy="3.5" r="1.5" fill="#DAFF04" />
        <circle cx="20.5" cy="4" r="1.5" fill="#DAFF04" />
        <circle cx="25" cy="5" r="1.5" fill="#DAFF04" />

        <!-- Titik Bulu Baris 2 -->
        <circle cx="8.5" cy="9" r="1.5" fill="#DAFF04" />
        <circle cx="12.2" cy="8.5" r="1.5" fill="#DAFF04" />
        <circle cx="16" cy="8.2" r="1.5" fill="#DAFF04" />
        <circle cx="19.8" cy="8.5" r="1.5" fill="#DAFF04" />
        <circle cx="23.5" cy="9" r="1.5" fill="#DAFF04" />

        <!-- Titik Bulu Baris 3 -->
        <circle cx="10" cy="13" r="1.5" fill="#DAFF04" />
        <circle cx="13" cy="12.7" r="1.5" fill="#DAFF04" />
        <circle cx="16" cy="12.5" r="1.5" fill="#DAFF04" />
        <circle cx="19" cy="12.7" r="1.5" fill="#DAFF04" />
        <circle cx="22" cy="13" r="1.5" fill="#DAFF04" />

        <!-- Titik Pita Kerangka Tengah -->
        <circle cx="11.5" cy="17" r="1.4" fill="#DAFF04" />
        <circle cx="13.8" cy="17" r="1.4" fill="#DAFF04" />
        <circle cx="16" cy="17" r="1.4" fill="#DAFF04" />
        <circle cx="18.2" cy="17" r="1.4" fill="#DAFF04" />
        <circle cx="20.5" cy="17" r="1.4" fill="#DAFF04" />

        <!-- Titik Kerucut Bawah -->
        <circle cx="13" cy="21" r="1.4" fill="#DAFF04" />
        <circle cx="15" cy="21" r="1.4" fill="#DAFF04" />
        <circle cx="17" cy="21" r="1.4" fill="#DAFF04" />
        <circle cx="19" cy="21" r="1.4" fill="#DAFF04" />

        <!-- Gabus Dasar Bulat (Cork) -->
        <circle cx="14" cy="25" r="1.6" fill="#DAFF04" />
        <circle cx="16" cy="25.2" r="1.7" fill="#DAFF04" />
        <circle cx="18" cy="25" r="1.6" fill="#DAFF04" />
        <circle cx="16" cy="28.5" r="1.8" fill="#DAFF04" />
    </svg>

    @if (!$hanyaIkon)
        <span class="navbar-brand-name">{{ config('gor.name') }}</span>
    @endif
</div>
