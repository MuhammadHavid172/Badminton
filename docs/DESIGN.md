# Sistem Desain Website Booking Lapangan Badminton

Dokumen ini mendefinisikan sistem desain visual, token, komponen, serta aturan ketat integritas UI (Anti AI Slop) untuk website booking lapangan badminton tahap 1.

---

## 1. Arah Visual & Filosofi

- **Gelap & Sinematik**: Latar belakang hitam pekat dan navy dalam menghasilkan atmosfer arena pertandingan profesional di malam hari.
- **Tipografi Raksasa & Rapat**: Menggunakan *Inter Tight* dengan ukuran besar, *negative letter-spacing*, dan *line-height* rapat (`0.95`) untuk menegaskan kesan berani (*bold*) dan modern.
- **Satu Aksen Limau (`#DAFF04`)**: Warna aksen tunggal yang digunakan secara selektif untuk memandu mata pengguna ke tindakan penting (CTA, kata aksen di hero, status).
- **Banyak Ruang Kosong (*Generous Whitespace*)**: Jarak vertikal yang lapang memberi ruang napas bagi setiap section sehingga hirarki terbaca dengan tenang.
- **Garis Tipis & Foto Besar**: Pemisah visual berupa garis horizontal 1px dengan opasitas rendah (`rgba(255,255,255,0.14)`).

---

## 2. Palet Warna

| Token CSS | Kode Warna / Nilai | Peruntukan / Kegunaan |
| :--- | :--- | :--- |
| `--hitam` | `#0D0D0B` | Latar belakang utama seluruh halaman |
| `--hitam-2` | `#161613` | Permukaan kartu, panel, atau elemen terangkat |
| `--navy` | `#122B41` | Latar hero, overlay, dan gradien fallback foto |
| `--biru-redup` | `#34475B` | Elemen struktur redup & aksen penyeimbang |
| `--lime` | `#DAFF04` | **Aksen tunggal**: CTA, titik penanda, kata kunci hero, harga |
| `--lime-hover` | `#C5E602` | State hover tombol solid (menggelap 1px, tanpa glow) |
| `--putih` | `#FFFFFF` | Teks judul utama, badge Bulanan |
| `--teks-redup` | `rgba(255, 255, 255, 0.62)` | Paragraf isi, keterangan kecil, deskripsi |
| `--garis` | `rgba(255, 255, 255, 0.14)` | Garis pemisah tipis horizontal dan border kartu |
| `--merah` | `#FF5A4D` | Indikator kesalahan atau peringatan kritis |

---

## 3. Tipografi

Sistem tipografi di-host mandiri (*self-hosted*) melalui `@fontsource-variable`, tanpa permintaan eksternal ke Google Fonts (`font-display: swap`).

- **Judul**: `Inter Tight Variable`
  - Bobot: `500` s/d `600`
  - Letter-spacing: `-0.045em`
  - Line-height: `0.95`
  - Ukuran responsif dengan `clamp()`, contoh hero: `clamp(3rem, 11vw, 11rem)`
- **Isi**: `Inter Variable`
  - Bobot: `400` dan `500`
  - Line-height: `1.5`
- **Label & CTA**: `Inter Tight Variable`
  - Huruf kapital (*uppercase*), bobot `600`, letter-spacing: `-0.01em`

---

## 4. Komponen Antarmuka

### CTA Landing Page
Teks limau kapital bergaris bawah dengan simbol panah serong (`↗`). Saat hover, garis bawah berganti menjadi putih tegas dan panah bergeser sedikit ke kanan atas (`translate(3px, -3px)`). Tanpa bayangan atau pendaran cahaya (*no glow*).

### Tombol Aksi
- **Solid**: Bentuk pil penuh berwarna limau (`--lime`) dengan teks hitam. State hover: bergeser 1px ke atas (`translateY(-1px)`) dan sedikit menggelap tanpa pendaran.
- **Sekunder**: Bentuk pil outline garis tipis (`--garis`) dengan teks putih.
- **Ghost**: Teks saja tanpa latar belakang, untuk aksi navigasi ringan.

### Penanda Section (*Section Marker*)
Pil outline tipis memuat teks kapital kecil dan satu titik berwarna limau di bagian depan (contoh: `PILIH LAPANGANMU ●`).

### Kartu (*Cards*)
Border-radius `24px`, warna latar `--hitam-2`, garis tepi 1px `--garis`. Tidak menggunakan bayangan kartu (*no box-shadow*).

### Badge Kategori
- **BULANAN**: Bentuk pil penuh putih dengan teks hitam tebal.
- **HARIAN**: Bentuk pil outline putih dengan teks putih.

### Logo
Tanda titik-titik (*dot matrix*) geometris buatan sendiri berbentuk kok badminton (*shuttlecock*) berbahan SVG berwarna limau, bersanding dengan teks nama GOR dari konfigurasi.

### Pola Cadangan (*Fallback Pattern*)
Pola titik halus (`radial-gradient` titik putih dengan opasitas rendah) yang dipadukan dengan gradien `--navy` ke `--hitam`. Menjamin tampilan tetap estetis saat foto belum diunggah.

---

## 5. Aturan Anti AI Slop (Kepatuhan Wajib)

Seluruh komponen dan teks dalam proyek ini wajib mematuhi aturan berikut:

1. **Tanpa Glow & Tanpa Bayangan Berwarna**: Tidak ada `box-shadow` berpendar limau, biru, atau neon.
2. **Tanpa Teks Gradient**: Dilarang menggunakan `background-clip: text` atau `-webkit-background-clip: text`.
3. **Tanpa Emoji**: Dilarang memasukkan karakter emoji pada seluruh antarmuka dan teks copy.
4. **Tanpa Ikon dalam Kotak Berwarna**: Dilarang membuat kotak persegi berwarna-warni yang hanya berisi ikon kecil generik.
5. **Blur HANYA untuk Navbar**: Atribut `backdrop-filter: blur(...)` hanya diizinkan untuk navigasi atas saat digulir (*scrolled*).
6. **Satu Elemen Dominan per Section**: Setiap bagian memiliki hierarki ukuran yang tajam; hindari membuat deretan grid kartu kembar identik sebagai pola default.
7. **Copy Santai Menggunakan "kamu"**: Dilarang menggunakan kata formal kaku "Anda".
8. **Tanpa Klaim Kosong**: Dilarang memakai frasa hampa seperti "terpercaya", "terbaik", "solusi", "transparan tanpa", "instan", "standar turnamen".
9. **Tanpa Angka atau Testimoni Palsu**: Dilarang menampilkan bintang rating 4.9/5 palsu, jumlah pengguna fiktif ("10,000+ pengguna puas"), atau testimoni rekayasa.
10. **Data Nyata & Transparan**: Setiap klaim bersumber dari konfigurasi (`config/gor.php`) atau mekanisme booking nyata (contoh waktu tahan slot `hold_minutes`). Konten contoh wajib diberi label "Contoh".
11. **Teks Rata Kiri Default**: Mayoritas teks rata kiri untuk keterbacaan tinggi.
12. **Skala Spasi Basis 8px**: Konsistensi jarak menggunakan kelipatan 8px (8, 16, 24, 32, 40, 48, 64, 80, 96, 128, 160px).
