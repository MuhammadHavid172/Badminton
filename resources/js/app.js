/**
 * Badminton Booking - Vanilla JavaScript
 * Ringan, cepat, tanpa framework atau pustaka animasi.
 * Menghormati preferensi prefers-reduced-motion.
 */

document.addEventListener('DOMContentLoaded', () => {
    const isReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    /* ======================================================================
       (a) Navbar Solid Setelah Scroll
       ====================================================================== */
    const navbar = document.getElementById('navbar');
    if (navbar) {
        const handleScroll = () => {
            if (window.scrollY > 40) {
                navbar.classList.add('is-scrolled');
            } else {
                navbar.classList.remove('is-scrolled');
            }
        };

        window.addEventListener('scroll', handleScroll, { passive: true });
        handleScroll();
    }

    /* ======================================================================
       (b) Menu Hamburger Layar Penuh di HP
       ====================================================================== */
    const hamburger = document.getElementById('nav-toggle');
    const mobileMenu = document.getElementById('mobile-menu');

    if (hamburger && mobileMenu) {
        const openMenu = () => {
            hamburger.classList.add('is-active');
            hamburger.setAttribute('aria-expanded', 'true');
            mobileMenu.classList.add('is-open');
            mobileMenu.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
            // Fokus ke link pertama di menu
            const firstLink = mobileMenu.querySelector('a');
            if (firstLink) firstLink.focus();
        };

        const closeMenu = () => {
            hamburger.classList.remove('is-active');
            hamburger.setAttribute('aria-expanded', 'false');
            mobileMenu.classList.remove('is-open');
            mobileMenu.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
            hamburger.focus();
        };

        hamburger.addEventListener('click', () => {
            const isOpen = hamburger.classList.contains('is-active');
            if (isOpen) {
                closeMenu();
            } else {
                openMenu();
            }
        });

        // Tutup menu dengan tombol Escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && hamburger.classList.contains('is-active')) {
                closeMenu();
            }
        });

        // Tutup menu saat pengguna memilih salah satu tautan
        mobileMenu.querySelectorAll('a').forEach((link) => {
            link.addEventListener('click', () => {
                closeMenu();
            });
        });
    }

    /* ======================================================================
       (c) Pemandu Scroll Horizontal Deretan Kartu Langkah
       ====================================================================== */
    const container = document.getElementById('langkah-deretan');
    const prevBtn = document.getElementById('langkah-prev');
    const nextBtn = document.getElementById('langkah-next');

    if (container && prevBtn && nextBtn) {
        const getScrollStep = () => {
            const firstCard = container.querySelector('.kartu-langkah');
            return firstCard ? firstCard.getBoundingClientRect().width + 24 : 340;
        };

        prevBtn.addEventListener('click', () => {
            container.scrollBy({
                left: -getScrollStep(),
                behavior: isReducedMotion ? 'auto' : 'smooth'
            });
        });

        nextBtn.addEventListener('click', () => {
            container.scrollBy({
                left: getScrollStep(),
                behavior: isReducedMotion ? 'auto' : 'smooth'
            });
        });
    }

    /* ======================================================================
       (d) Animasi Muncul Halus Saat Masuk Viewport (IntersectionObserver)
       ====================================================================== */
    const reveals = document.querySelectorAll('.reveal');
    if (reveals.length > 0) {
        if (isReducedMotion || !('IntersectionObserver' in window)) {
            reveals.forEach((el) => el.classList.add('is-visible'));
        } else {
            const observer = new IntersectionObserver((entries, obs) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-visible');
                        obs.unobserve(entry.target);
                    }
                });
            }, {
                threshold: 0.12,
                rootMargin: '0px 0px -40px 0px'
            });

            reveals.forEach((el) => observer.observe(el));
        }
    }

    /* ======================================================================
       (e) Dropdown Menu Pengguna (Aksesibel Keyboard & Escape)
       ====================================================================== */
    const userToggle = document.getElementById('user-menu-toggle');
    const userDropdown = document.getElementById('user-dropdown-menu');

    if (userToggle && userDropdown) {
        const toggleDropdown = () => {
            const isOpen = userDropdown.classList.contains('is-open');
            if (isOpen) {
                closeDropdown();
            } else {
                openDropdown();
            }
        };

        const openDropdown = () => {
            userDropdown.classList.add('is-open');
            userToggle.setAttribute('aria-expanded', 'true');
            const firstItem = userDropdown.querySelector('a, button');
            if (firstItem) firstItem.focus();
        };

        const closeDropdown = () => {
            userDropdown.classList.remove('is-open');
            userToggle.setAttribute('aria-expanded', 'false');
            userToggle.focus();
        };

        userToggle.addEventListener('click', (e) => {
            e.stopPropagation();
            toggleDropdown();
        });

        // Tutup saat klik di luar
        document.addEventListener('click', (e) => {
            if (!userToggle.contains(e.target) && !userDropdown.contains(e.target)) {
                userDropdown.classList.remove('is-open');
                userToggle.setAttribute('aria-expanded', 'false');
            }
        });

        // Tutup dengan Escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && userDropdown.classList.contains('is-open')) {
                closeDropdown();
            }
        });
    }
});
