# Website Booking Lapangan Badminton (Tahap 1: Sistem Desain & Landing Page)

Sistem landing page dan fondasi desain untuk pemesanan lapangan badminton modern dengan estetika gelap sinematik (*dark cinematic*), tipografi raksasa rapat, satu aksen limau (`#DAFF04`), banyak ruang kosong, serta aturan ketat *Anti AI Slop*.

Tahap 1 ini berfokus secara eksklusif pada **sistem desain, arsitektur UI Blade, dan landing page lengkap tanpa dependensi database**.

---

## 1. Fitur Utama Tahap 1

- **Arsitektur Tanpa Database**: Menggunakan `SESSION_DRIVER=file` dan `CACHE_STORE=file` sehingga aplikasi langsung berjalan tanpa konfigurasi database.
- **Tipografi Mandiri (*Self-Hosted*)**: Memanfaatkan `@fontsource-variable/inter` dan `@fontsource-variable/inter-tight` lokal, tanpa permintaan ke Google Fonts, dengan `font-display: swap`.
- **CSS Murni Tanpa Tailwind**: Token desain modular (`tokens.css`, `base.css`, `components.css`) dengan CSS variables, hierarki jelas, dan skala spasi 8px.
- **JavaScript Vanilla Ringan**:
  - Navbar adaptif (transparan di hero, solid gelap dengan *blur* setelah digulir).
  - Menu hamburger layar penuh di mobile (aksesibel via keyboard, tutup dengan `Escape`, kunci *scroll* body).
  - Pemandu *scroll* horizontal untuk kartu langkah booking (*smooth scroll snap* + tombol navigasi).
  - Animasi masuk *viewport* halus via `IntersectionObserver` (mati otomatis pada `prefers-reduced-motion`).
- **Komponen Blade Modular**:
  - `<x-foto>`: Mendukung gambar lokal, WebP/AVIF responsif (*srcset*), dan *fallback* dinamis (gradien navy ke hitam + pola titik).
  - `<x-cta>`: Teks limau kapital bergaris bawah dengan panah serong (`↗`).
  - `<x-penanda>`: Pil *outline* tipis dengan titik limau (`PILIH LAPANGANMU ●`).
  - `<x-badge-kategori>`: Badge `BULANAN` (pil penuh putih) dan `HARIAN` (pil outline putih).
  - `<x-kartu>`: Radius 24px, latar `--hitam-2`, border tipis, tanpa bayangan berwarna.
  - `<x-logo>`: Desain geometris kok badminton (*dot matrix* SVG) bersanding dengan nama GOR.
- **Kepatuhan Anti AI Slop**:
  - Tanpa efek *glow* atau bayangan berwarna.
  - Tanpa teks gradien (*no background-clip: text*).
  - Tanpa emoji di seluruh tampilan dan *copy*.
  - Tanpa kotak ikon berwarna-warni.
  - *Backdrop-filter blur* hanya untuk navbar.
  - *Copy* santai menggunakan kata "kamu" (tanpa kata kaku "Anda").
  - Tanpa klaim kosong atau angka testimoni palsu.

---

## 2. Persyaratan Sistem

- **PHP**: ^8.1
- **Composer**: ^2.0
- **Node.js**: ^18.0 (atau Node v22 LTS)
- **NPM**: ^9.0

---

## 3. Instalasi & Setup Cepat

1. **Clone atau Buka Direktori Proyek**:
   ```bash
   cd d:\Badminton
   ```

2. **Pasang Dependensi PHP & Node.js**:
   ```bash
   composer install
   npm install
   ```

3. **Konfigurasi Environment**:
   Salin file konfigurasi jika belum ada:
   ```bash
   copy .env.example .env
   php artisan key:generate
   ```

4. **Kompilasi Aset Frontend**:
   ```bash
   npm run build
   ```

5. **Jalankan Server Lokal**:
   ```bash
   php artisan serve --port=8000
   ```
   Buka di peramban: `http://127.0.0.1:8000/`

---

## 4. Konfigurasi GOR (`config/gor.php`)

Seluruh informasi GOR diatur melalui variabel lingkungan di file `.env`:

```env
GOR_NAME="Nama GOR"
GOR_CITY=
GOR_JAM_BUKA="08.00-23.00"
GOR_ALAMAT=
GOR_HOLD_MINUTES=15
GOR_HARGA_SINGLE=200000
GOR_HARGA_PACKAGE=600000
GOR_WHATSAPP=
GOR_INSTAGRAM=
GOR_DAFTAR_LAPANGAN=
```

- Jika `GOR_CITY` atau `GOR_ALAMAT` kosong, bagian tersebut tidak ditampilkan di antarmuka.
- Jika `GOR_DAFTAR_LAPANGAN` kosong, pil daftar lapangan di section *Pilih Lapangan* disembunyikan otomatis.
- Jika `GOR_WHATSAPP` dan `GOR_INSTAGRAM` kosong, elemen kontak di footer tidak dirender.

---

## 5. Panduan Foto & Aset Gambar

Semua foto bersifat **opsional**. Jika foto belum ditaruh, website menampilkan latar estetis gradien `--navy` ke `--hitam` dipadu pola titik halus.

| File | Resolusi Rekomendasi | Keterangan |
| :--- | :--- | :--- |
| `public/images/asli/hero.jpg` | 2400 x 1400 | Latar hero (subjek tengah, tepi gelap) |
| `public/images/asli/pernyataan.jpg` | 2400 x 1200 | Latar section pernyataan |
| `public/images/asli/lapangan-besar.jpg` | 2400 x 1400 | Latar section pilih lapangan |
| `public/images/asli/langkah-1.jpg` s/d `langkah-5.jpg` | 900 x 1200 | Foto vertikal pada kartu langkah booking |
| `public/images/asli/lapangan-1.jpg` dst. | 800 x 600 | Cuplikan pratinjau sudut lapangan |
| `public/images/asli/cta.jpg` | 2400 x 1200 | Latar section penutup besar |

### Mengoptimasi Gambar Otomatis
Letakkan gambar mentah di direktori `public/images/asli/`, lalu jalankan:
```bash
npm run foto
```
Skrip ini (menggunakan `sharp`) akan menghasilkan format modern **WebP** dan **AVIF** dalam ukuran responsif (800w, 1600w, 2400w) ke dalam direktori `public/images/`.

---

## 6. Rute Halaman

| Rute | Metode | Deskripsi | Status |
| :--- | :--- | :--- | :--- |
| `/` | GET | Landing Page lengkap dengan 8 section sinematik | 200 OK |
| `/jadwal` | GET | Halaman sementara "Segera Hadir" untuk Jadwal | 200 OK |
| `/masuk` | GET | Halaman sementara "Segera Hadir" untuk Login | 200 OK |
| `/daftar` | GET | Halaman sementara "Segera Hadir" untuk Registrasi | 200 OK |
| `/dev/komponen` | GET | Katalog komponen visual (Hanya aktif di `APP_ENV=local`, 404 di produksi) | 200 OK (Local) |

---

## 7. Hasil Pengujian & Kualitas

### A. Tes Otomatis Laravel (PHPUnit)
Jalankan seluruh tes dengan perintah:
```bash
php artisan test
```
**Hasil:**
- `Tests\Feature\LandingPageTest`: 9 assertions lolos (Status 200, tiga kata hero, halaman sementara, fallback `<x-foto>`, helper rupiah).
- `Tests\Feature\DesignRulesTest`: 6 assertions lolos (Memastikan tidak ada kata "Anda", tidak ada klaim kosong, tidak ada emoji, tidak ada teks gradient, tidak ada box-shadow berwarna/glow, dan backdrop-filter hanya pada navbar).
- **Status:** **17 Passed (31 assertions)**, durasi ~1.2 detik.

### B. Audit Aksesibilitas Axe-Core
Dijalankan melalui Playwright pada resolusi desktop (1440px) dan mobile (390px):
- **Pelanggaran Critical / Serious**: **0 (Nol)**.
- Kontras warna teks memenuhi standar WCAG AA.
- Indikator fokus `:focus-visible` warna limau terang di seluruh kontrol interaktif.
- Tautan lompat ke konten (*Skip to main content*) tersedia.

### C. Skor Audit Google Lighthouse Mobile
Dijalankan pada perangkat simulasi seluler (*Moto G4 / Mobile emulation*):
- **Performance**: **100**
- **Accessibility**: **100**
- **Best Practices**: **100**
- **SEO**: **100**
- **Cumulative Layout Shift (CLS)**: **0.000**

### D. Tangkapan Layar Responsif
Dokumentasi visual tersimpan di direktori `docs/screenshots/landing/`:
- `landing-1920.png` (Desktop Layar Lebar)
- `landing-1440.png` (Desktop Standar)
- `landing-1366.png` (Laptop)
- `landing-1024.png` (Tablet Landscape)
- `landing-768.png` (Tablet Portrait)
- `landing-390.png` (Mobile Modern / iPhone)
- `mobile-menu-open.png` (Tampilan Menu Layar Penuh Mobile)
