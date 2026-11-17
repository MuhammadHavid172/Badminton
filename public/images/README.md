# Panduan Aset Foto Lapangan Badminton

Semua foto bersifat **opsional**. Jika file gambar belum tersedia, website secara otomatis menampilkan tampilan *fallback* elegan berbasis CSS: gradien sinematik `--navy` ke `--hitam` dipadukan dengan pola titik halus (*subtle dot pattern*).

## Struktur Direktori

- `public/images/asli/` : Tempat meletakkan foto resolusi asli (JPG/PNG).
- `public/images/` : Tempat file gambar hasil proses atau gambar yang langsung digunakan oleh aplikasi.

## Daftar File, Ukuran, dan Arah Foto

| Nama File | Dimensi Rekomendasi | Arah / Karakteristik Visual | Penempatan di Halaman |
| :--- | :--- | :--- | :--- |
| `hero.jpg` | 2400 x 1400 px | Subjek (lapangan/kok/pemain) di tengah dengan tepi gelap (*vignette*/bayangan alami) agar teks kontras | Latar belakang penuh section Hero |
| `pernyataan.jpg` | 2400 x 1200 px | Suasana lapangan, tekstur lantai vinyl, atau pencahayaan dramatis | Latar belakang section Pernyataan |
| `lapangan-besar.jpg` | 2400 x 1400 px | Foto sudut lebar (*wide angle*) lapangan badminton bersih | Latar belakang section Pilih Lapangan |
| `lapangan-1.jpg` dst. | 800 x 600 px | Cuplikan sudut lapangan nomor 1, 2, dst. | Kartu pratinjau kecil di section Pilih Lapangan |
| `langkah-1.jpg` s/d `langkah-5.jpg` | 900 x 1200 px | Foto vertikal suasana booking, jadwal, pembayaran, atau lapangan | Kartu geser horizontal section Cara Booking |
| `cta.jpg` | 2400 x 1200 px | Suasana akhir pertandingan / pencahayaan lapangan badminton | Latar belakang section Penutup Besar |

## Skrip Optimasi Gambar (`npm run foto`)

Untuk menghasilkan varian WebP dan AVIF dalam berbagai ukuran responsif (800w, 1600w, 2400w):
1. Letakkan gambar mentah di direktori `public/images/asli/` (misal `hero.jpg`).
2. Jalankan perintah di terminal:
   ```bash
   npm run foto
   ```
3. Skrip akan memproses setiap gambar di folder `asli/` dan menghasilkan file `.webp` dan `.avif` dengan penamaan responsif (contoh `hero-800.webp`, `hero-1600.webp`, `hero-2400.webp`, dst.) serta mempertahankan file dasar di `public/images/`.
