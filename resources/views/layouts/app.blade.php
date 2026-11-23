<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0D0D0B">
    <meta name="description" content="Website booking lapangan badminton {{ config('gor.name') }}. Cek jadwal kosong, booking slot harian atau bulanan langsung dari HP tanpa ribet.">

    <title>@yield('title', config('gor.name') . ' - Booking Lapangan Badminton')</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <!-- Aksesibilitas: Tautan Lompat ke Konten -->
    <a href="#konten-utama" class="skip-link">Lompat ke konten utama</a>

    <!-- Navigasi Atas -->
    <header>
        <nav id="navbar" class="navbar" aria-label="Navigasi Utama">
            <div class="kontainer navbar-inner">
                <!-- Menu Kiri (Desktop) -->
                <ul class="navbar-nav" role="list">
                    <li><a href="{{ route('jadwal') }}" class="navbar-link">Jadwal</a></li>
                    <li><a href="{{ url('/#harga') }}" class="navbar-link">Harga</a></li>
                    <li><a href="{{ url('/#cara-booking') }}" class="navbar-link">Cara booking</a></li>
                    <li><a href="{{ url('/#faq') }}" class="navbar-link">FAQ</a></li>
                </ul>

                <!-- Logo di Tengah (Desktop) / Kiri (Mobile) -->
                <a href="{{ url('/') }}" aria-label="{{ config('gor.name') }} - Beranda">
                    <x-logo />
                </a>

                <!-- Aksi Kanan (Desktop) -->
                <div class="navbar-actions">
                    @guest
                        <a href="{{ route('masuk') }}" class="link-masuk">Masuk</a>
                        <a href="{{ route('daftar') }}" class="tombol tombol-solid">Daftar</a>
                    @else
                        <div class="user-menu-wrapper" id="user-menu-container">
                            <button
                                type="button"
                                class="user-menu-btn"
                                id="user-menu-toggle"
                                aria-expanded="false"
                                aria-haspopup="true"
                                aria-controls="user-dropdown-menu"
                            >
                                <span>{{ Str::words(auth()->user()->name, 1, '') }}</span>
                                <span aria-hidden="true" style="font-size: 0.6875rem;">▼</span>
                            </button>

                            <div
                                class="user-dropdown"
                                id="user-dropdown-menu"
                                role="menu"
                                aria-labelledby="user-menu-toggle"
                            >
                                <a href="{{ route('booking.saya') }}" class="user-dropdown-item" role="menuitem">
                                    Booking saya
                                </a>

                                <a href="{{ route('akun') }}" class="user-dropdown-item" role="menuitem">
                                    Akun saya
                                </a>

                                @if (auth()->user()->isAdmin())
                                    <a href="{{ route('admin.dashboard') }}" class="user-dropdown-item" role="menuitem">
                                        Dashboard Admin
                                    </a>
                                @elseif (auth()->user()->isOwner())
                                    <a href="{{ route('owner.dashboard') }}" class="user-dropdown-item" role="menuitem">
                                        Mode Pantau
                                    </a>
                                @endif

                                <div class="user-dropdown-separator" role="separator" aria-hidden="true"></div>

                                <form action="{{ route('keluar') }}" method="POST" style="margin: 0;">
                                    @csrf
                                    <button type="submit" class="user-dropdown-item" role="menuitem" style="color: var(--merah);">
                                        Keluar
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endguest
                </div>

                <!-- Tombol Hamburger (Mobile) -->
                <button
                    id="nav-toggle"
                    class="navbar-hamburger"
                    type="button"
                    aria-expanded="false"
                    aria-controls="mobile-menu"
                    aria-label="Buka menu navigasi"
                >
                    <span class="hamburger-line" aria-hidden="true"></span>
                    <span class="hamburger-line" aria-hidden="true"></span>
                    <span class="hamburger-line" aria-hidden="true"></span>
                </button>
            </div>
        </nav>

        <!-- Menu Layar Penuh Mobile -->
        <div id="mobile-menu" class="mobile-menu" aria-hidden="true" role="dialog" aria-label="Menu navigasi mobile">
            <div class="mobile-menu-inner">
                <ul class="mobile-menu-links" role="list">
                    <li><a href="{{ route('jadwal') }}" class="mobile-menu-link">Jadwal</a></li>
                    <li><a href="{{ url('/#harga') }}" class="mobile-menu-link">Harga</a></li>
                    <li><a href="{{ url('/#cara-booking') }}" class="mobile-menu-link">Cara booking</a></li>
                    <li><a href="{{ url('/#faq') }}" class="mobile-menu-link">FAQ</a></li>
                </ul>
            </div>
            <div class="mobile-menu-actions">
                @guest
                    <a href="{{ route('masuk') }}" class="tombol tombol-sekunder" style="width: 100%;">Masuk</a>
                    <a href="{{ route('daftar') }}" class="tombol tombol-solid" style="width: 100%;">Daftar</a>
                @else
                    <a href="{{ route('booking.saya') }}" class="tombol tombol-solid" style="width: 100%;">
                        Booking Saya
                    </a>
                    <a href="{{ route('akun') }}" class="tombol tombol-sekunder" style="width: 100%;">
                        Akun Saya ({{ Str::words(auth()->user()->name, 1, '') }})
                    </a>
                    @if (auth()->user()->isAdmin())
                        <a href="{{ route('admin.dashboard') }}" class="tombol tombol-outline" style="width: 100%;">
                            Dashboard Admin
                        </a>
                    @elseif (auth()->user()->isOwner())
                        <a href="{{ route('owner.dashboard') }}" class="tombol tombol-outline" style="width: 100%;">
                            Mode Pantau
                        </a>
                    @endif
                    <form action="{{ route('keluar') }}" method="POST" style="width: 100%; margin-top: var(--spasi-2);">
                        @csrf
                        <button type="submit" class="tombol tombol-sekunder" style="width: 100%; color: var(--merah); border-color: rgba(255, 90, 77, 0.4);">
                            Keluar
                        </button>
                    </form>
                @endguest
            </div>
        </div>
    </header>

    <!-- Konten Halaman -->
    <main id="konten-utama">
        @yield('content')
    </main>

    <!-- Footer Situs -->
    <footer class="site-footer">
        <div class="kontainer">
            <div class="footer-grid">
                <div>
                    <a href="{{ url('/') }}" aria-label="{{ config('gor.name') }}">
                        <x-logo />
                    </a>
                    <p style="margin-top: var(--spasi-3); max-width: 380px;">
                        Booking lapangan badminton mudah dan transparan. Cek jadwal terbuka, pilih sesi, dan langsung main.
                    </p>
                    <p style="margin-top: var(--spasi-2); font-size: 0.875rem; color: var(--teks-redup);">
                        Jam Buka: {{ config('gor.jam_buka') }}
                    </p>
                    @if (config('gor.alamat'))
                        <p style="margin-top: var(--spasi-1); font-size: 0.875rem; color: var(--teks-redup);">
                            {{ config('gor.alamat') }}{{ config('gor.city') ? ', ' . config('gor.city') : '' }}
                        </p>
                    @elseif (config('gor.city'))
                        <p style="margin-top: var(--spasi-1); font-size: 0.875rem; color: var(--teks-redup);">
                            Kota {{ config('gor.city') }}
                        </p>
                    @endif
                </div>

                <div>
                    <h3 style="font-size: 0.875rem; text-transform: uppercase; letter-spacing: var(--tracking-label); margin-bottom: var(--spasi-3);">
                        Navigasi
                    </h3>
                    <ul class="footer-nav" role="list">
                        <li><a href="{{ route('jadwal') }}" class="footer-link">Jadwal Lapangan</a></li>
                        <li><a href="{{ url('/#harga') }}" class="footer-link">Pilihan Harga</a></li>
                        <li><a href="{{ url('/#cara-booking') }}" class="footer-link">Cara Booking</a></li>
                        <li><a href="{{ url('/#faq') }}" class="footer-link">Tanya Jawab (FAQ)</a></li>
                    </ul>
                </div>

                <div>
                    <h3 style="font-size: 0.875rem; text-transform: uppercase; letter-spacing: var(--tracking-label); margin-bottom: var(--spasi-3);">
                        Akses Akun
                    </h3>
                    <ul class="footer-nav" role="list">
                        <li><a href="{{ route('masuk') }}" class="footer-link">Masuk ke Akun</a></li>
                        <li><a href="{{ route('daftar') }}" class="footer-link">Daftar Akun Baru</a></li>
                    </ul>

                    @if (config('gor.kontak.whatsapp') || config('gor.kontak.instagram'))
                        <h3 style="font-size: 0.875rem; text-transform: uppercase; letter-spacing: var(--tracking-label); margin-top: var(--spasi-4); margin-bottom: var(--spasi-2);">
                            Kontak
                        </h3>
                        <ul class="footer-nav" role="list">
                            @if (config('gor.kontak.whatsapp'))
                                <li>
                                    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', config('gor.kontak.whatsapp')) }}" class="footer-link" target="_blank" rel="noopener noreferrer">
                                        WhatsApp: {{ config('gor.kontak.whatsapp') }}
                                    </a>
                                </li>
                            @endif
                            @if (config('gor.kontak.instagram'))
                                <li>
                                    <a href="https://instagram.com/{{ ltrim(config('gor.kontak.instagram'), '@') }}" class="footer-link" target="_blank" rel="noopener noreferrer">
                                        Instagram: {{ config('gor.kontak.instagram') }}
                                    </a>
                                </li>
                            @endif
                        </ul>
                    @endif
                </div>
            </div>

            <div class="footer-bottom">
                <p>&copy; {{ date('Y') }} {{ config('gor.name') }}. Seluruh hak cipta dilindungi.</p>
                <p>Tahap 1: Sistem Desain &amp; Antarmuka Pengguna.</p>
            </div>
        </div>
    </footer>
</body>
</html>
