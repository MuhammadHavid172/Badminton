<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'Admin Operasional - ' . config('gor.name'))</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="theme-gelap">
    <a href="#konten-admin" class="sr-only sr-only-focusable">
        Lompat ke konten admin
    </a>

    @php
        $isOwnerMode = ($modePantau ?? false) || (auth()->check() && auth()->user()->isOwner());
        $dashboardRoute = $isOwnerMode ? route('owner.dashboard') : route('admin.dashboard');
    @endphp

    <!-- Header Navbar Admin / Owner -->
    <header class="site-header">
        <nav class="navbar" aria-label="Navigasi Pengelola GOR">
            <div class="kontainer navbar-inner">
                <div style="display: flex; align-items: center; gap: var(--spasi-3);">
                    <a href="{{ $dashboardRoute }}" aria-label="{{ config('gor.name') }}">
                        <x-logo />
                    </a>
                    @if ($isOwnerMode)
                        <span class="badge-kategori badge-kategori-bulanan" style="font-size: 0.6875rem; background-color: #2E2E28; color: var(--lime); border-color: var(--lime);">
                            OWNER &middot; MODE PANTAU
                        </span>
                    @else
                        <span class="badge-kategori badge-kategori-bulanan" style="font-size: 0.6875rem;">
                            AREA ADMIN
                        </span>
                    @endif
                </div>

                <div class="navbar-actions">
                    <span style="font-size: 0.875rem; color: var(--teks-redup); display: none; @media(min-width: 600px){ display: inline; }">
                        {{ auth()->user()->name }}
                    </span>

                    <a href="{{ route('beranda') }}" class="tombol tombol-sekunder" style="font-size: 0.8125rem; min-height: 38px; padding: 4px 12px;">
                        Ke Beranda Web
                    </a>

                    <form action="{{ route('keluar') }}" method="POST" style="margin: 0;">
                        @csrf
                        <button type="submit" class="tombol tombol-sekunder" style="color: var(--merah); border-color: rgba(255, 90, 77, 0.4); font-size: 0.8125rem; min-height: 38px; padding: 4px 12px;">
                            Keluar
                        </button>
                    </form>
                </div>
            </div>
        </nav>

        <!-- Sub-Navigasi Desktop Pengelola -->
        <div class="admin-subnav-desktop">
            <div class="kontainer admin-subnav-inner">
                @if ($isOwnerMode)
                    <a href="{{ route('owner.ringkasan') }}" class="admin-nav-link {{ request()->routeIs('owner.dashboard') || request()->routeIs('owner.ringkasan') ? 'is-active' : '' }}">
                        Ringkasan
                    </a>
                    <a href="{{ route('owner.hari-ini') }}" class="admin-nav-link {{ request()->routeIs('owner.hari-ini') ? 'is-active' : '' }}">
                        Hari Ini
                    </a>
                    <a href="{{ route('owner.jatah-habis') }}" class="admin-nav-link {{ request()->routeIs('owner.jatah-habis') ? 'is-active' : '' }}">
                        Jatah Habis
                        @if (isset($jatahHabisCount) && $jatahHabisCount > 0)
                            <span class="admin-nav-badge">{{ $jatahHabisCount }}</span>
                        @endif
                    </a>
                    <a href="{{ route('owner.booking.index') }}" class="admin-nav-link {{ request()->routeIs('owner.booking.index') || request()->routeIs('owner.booking.detail') ? 'is-active' : '' }}">
                        Daftar Booking
                    </a>
                    <a href="{{ route('owner.pengguna.index') }}" class="admin-nav-link {{ request()->routeIs('owner.pengguna.*') ? 'is-active' : '' }}">
                        Pengguna
                    </a>
                    <a href="{{ route('owner.aktivitas') }}" class="admin-nav-link {{ request()->routeIs('owner.aktivitas') ? 'is-active' : '' }}">
                        Aktivitas
                    </a>
                    <a href="{{ route('owner.laporan') }}" class="admin-nav-link {{ request()->routeIs('owner.laporan') ? 'is-active' : '' }}">
                        Laporan
                    </a>
                    <a href="{{ route('owner.pengaturan') }}" class="admin-nav-link {{ request()->routeIs('owner.pengaturan') ? 'is-active' : '' }}">
                        Pengaturan
                    </a>
                @else
                    <a href="{{ route('admin.dashboard') }}" class="admin-nav-link {{ request()->routeIs('admin.dashboard') ? 'is-active' : '' }}">
                        Hari Ini
                    </a>
                    <a href="{{ route('admin.ringkasan') }}" class="admin-nav-link {{ request()->routeIs('admin.ringkasan') ? 'is-active' : '' }}">
                        Ringkasan
                    </a>
                    <a href="{{ route('admin.jatah-habis') }}" class="admin-nav-link {{ request()->routeIs('admin.jatah-habis') ? 'is-active' : '' }}">
                        Jatah Habis
                        @if (isset($jatahHabisCount) && $jatahHabisCount > 0)
                            <span class="admin-nav-badge">{{ $jatahHabisCount }}</span>
                        @endif
                    </a>
                    <a href="{{ route('admin.booking.index') }}" class="admin-nav-link {{ request()->routeIs('admin.booking.index') || request()->routeIs('admin.booking.detail') ? 'is-active' : '' }}">
                        Daftar Booking
                    </a>
                    <a href="{{ route('admin.booking-langsung') }}" class="admin-nav-link {{ request()->routeIs('admin.booking-langsung') ? 'is-active' : '' }}">
                        Booking Langsung
                    </a>
                    <a href="{{ route('admin.tutup-slot') }}" class="admin-nav-link {{ request()->routeIs('admin.tutup-slot') ? 'is-active' : '' }}">
                        Tutup / Buka Slot
                    </a>
                    <a href="{{ route('admin.pengguna.index') }}" class="admin-nav-link {{ request()->routeIs('admin.pengguna.*') ? 'is-active' : '' }}">
                        Pengguna
                    </a>
                    <a href="{{ route('admin.aktivitas') }}" class="admin-nav-link {{ request()->routeIs('admin.aktivitas') ? 'is-active' : '' }}">
                        Aktivitas
                    </a>
                    <a href="{{ route('admin.laporan') }}" class="admin-nav-link {{ request()->routeIs('admin.laporan') ? 'is-active' : '' }}">
                        Laporan
                    </a>
                    <a href="{{ route('admin.pengaturan') }}" class="admin-nav-link {{ request()->routeIs('admin.pengaturan') ? 'is-active' : '' }}">
                        Pengaturan
                    </a>
                @endif
            </div>
        </div>
    </header>

    <!-- Konten Utama Admin -->
    <main id="konten-admin" style="padding-bottom: 96px;">
        @yield('content')
    </main>

    <!-- Bilah Bawah Tetap Mobile (< 768px, Touch Target 48px) -->
    <nav class="admin-mobile-bottom-bar" aria-label="Menu Cepat Mobile">
        @if ($isOwnerMode)
            <a href="{{ route('owner.ringkasan') }}" class="admin-bar-item {{ request()->routeIs('owner.dashboard') || request()->routeIs('owner.ringkasan') ? 'is-active' : '' }}">
                <svg class="admin-bar-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <line x1="18" y1="20" x2="18" y2="10"></line>
                    <line x1="12" y1="20" x2="12" y2="4"></line>
                    <line x1="6" y1="20" x2="6" y2="14"></line>
                </svg>
                <span class="admin-bar-label">Ringkasan</span>
            </a>
            <a href="{{ route('owner.hari-ini') }}" class="admin-bar-item {{ request()->routeIs('owner.hari-ini') ? 'is-active' : '' }}">
                <svg class="admin-bar-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                </svg>
                <span class="admin-bar-label">Hari Ini</span>
            </a>
            <a href="{{ route('owner.booking.index') }}" class="admin-bar-item {{ request()->routeIs('owner.booking.index') ? 'is-active' : '' }}">
                <svg class="admin-bar-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <line x1="8" y1="6" x2="21" y2="6"></line>
                    <line x1="8" y1="12" x2="21" y2="12"></line>
                    <line x1="8" y1="18" x2="21" y2="18"></line>
                    <line x1="3" y1="6" x2="3.01" y2="6"></line>
                    <line x1="3" y1="12" x2="3.01" y2="12"></line>
                    <line x1="3" y1="18" x2="3.01" y2="18"></line>
                </svg>
                <span class="admin-bar-label">Booking</span>
            </a>
            <a href="{{ route('owner.jatah-habis') }}" class="admin-bar-item {{ request()->routeIs('owner.jatah-habis') ? 'is-active' : '' }}">
                <svg class="admin-bar-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                    <line x1="12" y1="9" x2="12" y2="13"></line>
                    <line x1="12" y1="17" x2="12.01" y2="17"></line>
                </svg>
                <span class="admin-bar-label">
                    Jatah
                    @if (isset($jatahHabisCount) && $jatahHabisCount > 0)
                        <span class="admin-nav-badge-dot"></span>
                    @endif
                </span>
            </a>
            <a href="{{ route('owner.laporan') }}" class="admin-bar-item {{ request()->routeIs('owner.laporan') ? 'is-active' : '' }}">
                <svg class="admin-bar-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="16" y1="13" x2="8" y2="13"></line>
                    <line x1="16" y1="17" x2="8" y2="17"></line>
                    <polyline points="10 9 9 9 8 9"></polyline>
                </svg>
                <span class="admin-bar-label">Laporan</span>
            </a>
        @else
            <a href="{{ route('admin.dashboard') }}" class="admin-bar-item {{ request()->routeIs('admin.dashboard') ? 'is-active' : '' }}">
                <svg class="admin-bar-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                </svg>
                <span class="admin-bar-label">Hari Ini</span>
            </a>
            <a href="{{ route('admin.ringkasan') }}" class="admin-bar-item {{ request()->routeIs('admin.ringkasan') ? 'is-active' : '' }}">
                <svg class="admin-bar-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <line x1="18" y1="20" x2="18" y2="10"></line>
                    <line x1="12" y1="20" x2="12" y2="4"></line>
                    <line x1="6" y1="20" x2="6" y2="14"></line>
                </svg>
                <span class="admin-bar-label">Ringkasan</span>
            </a>
            <a href="{{ route('admin.booking.index') }}" class="admin-bar-item {{ request()->routeIs('admin.booking.index') ? 'is-active' : '' }}">
                <svg class="admin-bar-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <line x1="8" y1="6" x2="21" y2="6"></line>
                    <line x1="8" y1="12" x2="21" y2="12"></line>
                    <line x1="8" y1="18" x2="21" y2="18"></line>
                    <line x1="3" y1="6" x2="3.01" y2="6"></line>
                    <line x1="3" y1="12" x2="3.01" y2="12"></line>
                    <line x1="3" y1="18" x2="3.01" y2="18"></line>
                </svg>
                <span class="admin-bar-label">Booking</span>
            </a>
            <a href="{{ route('admin.booking-langsung') }}" class="admin-bar-item {{ request()->routeIs('admin.booking-langsung') ? 'is-active' : '' }}">
                <svg class="admin-bar-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="16"></line>
                    <line x1="8" y1="12" x2="16" y2="12"></line>
                </svg>
                <span class="admin-bar-label">Langsung</span>
            </a>
            <a href="{{ route('admin.jatah-habis') }}" class="admin-bar-item {{ request()->routeIs('admin.jatah-habis') ? 'is-active' : '' }}">
                <svg class="admin-bar-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                    <line x1="12" y1="9" x2="12" y2="13"></line>
                    <line x1="12" y1="17" x2="12.01" y2="17"></line>
                </svg>
                <span class="admin-bar-label">
                    Jatah
                    @if (isset($jatahHabisCount) && $jatahHabisCount > 0)
                        <span class="admin-nav-badge-dot"></span>
                    @endif
                </span>
            </a>
        @endif
    </nav>
</body>
</html>