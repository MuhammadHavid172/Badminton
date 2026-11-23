# Lembar Uji Kualitas Sistem (QA Checklist)

Dokumen ini memuat panduan pengujian fungsional manual, pengujian lintas perangkat, dan verifikasi kepatuhan sistem booking lapangan badminton.

---

## 1. Pengujian Responsif & Antarmuka Lintas Resolusi Layar

| No | Target Resolusi | Perangkat Acuan | Kriteria Kelulusan | Status |
| :-: | :--- | :--- | :--- | :-: |
| 1.1 | **390px** (Mobile Portret) | iPhone 12/13/14, Pixel 7 | Tanpa scroll horizontal. Area sentuh tombol minimal 44px (admin 48px). Font input minimal 16px (mencegah auto-zoom iOS). Navigasi bawah admin menempel dengan aman (`env(safe-area-inset-bottom)`). Tabel berubah menjadi kartu bertumpuk label-nilai. | Lolos |
| 1.2 | **768px** (Tablet Portret) | iPad Mini, Galaxy Tab | Grid jadwal 2 kolom seimbang, slip tiket booking membungkus dengan rapi, menu sub-navigasi beralih ke desktop. | Lolos |
| 1.3 | **1024px** (Tablet Lanskap / Laptop Kecil)| iPad Pro, Laptop 13" | Tata letak booking dua kolom (kiri slip tiket, kanan pilihan kategori), tabel admin ditampilkan penuh tanpa scroll horizontal tersembunyi. | Lolos |
| 1.4 | **1366px** (Desktop Standar) | Monitor HD Standar | Seluruh kontainer berada dalam `--max-lebar: 1380px`, baris tipografi ringkasan dashboard proporsional, grafik SVG batang 29 hari sejajar penuh. | Lolos |
| 1.5 | **1920px** (Layar Lebar Full HD) | Monitor Desktop / iMac | Konten tetap terpusat di tengah, latar belakang gelap sinematik merata, tanpa distorsi visual. | Lolos |

---

## 2. Pengujian Skenario Pelanggan (Customer)

| No | Skenario Uji | Prosedur Uji | Ekspektasi Hasil | Status |
| :-: | :--- | :--- | :--- | :-: |
| 2.1 | **Jadwal Publik** | Buka `/jadwal`, beralih antara tampilan "Per hari" dan "Kalender". | Tampilan harian menunjukkan status slot (kosong, menunggu bayar, terisi nama PB, ditutup, lewat). Kalender menampilkan matriks mini dan titik bulanan. | Lolos |
| 2.2 | **Pendaftaran Tamu Inline** | Pilih slot kosong tanpa login, isi nama, nomor HP, dan password di formulir pendaftaran inline. | Akun customer langsung tercipta, sesi login otomatis aktif, dan booking langsung berlanjut ke pemilihan kategori sewa. | Lolos |
| 2.3 | **Booking Harian** | Pilih slot Harian, submit booking. | Booking `pending_payment` dibuat (1 slot), harga snapshot tercatat, hitung mundur masa tahan 15 menit berjalan. | Lolos |
| 2.4 | **Booking Bulanan** | Pilih kategori Bulanan. | Sistem memeriksa ketersediaan 4 tanggal berturut-turut (+7, +14, +21 hari). Jika bentrok, pilihan disabled dan tanggal bentrok disebutkan. Jika kosong, 4 slot terkunci sekaligus. | Lolos |
| 2.5 | **Simulasi Pembayaran** | Buka halaman bayar, klik "Simulasikan pembayaran berhasil". | Status booking dan pembayaran menjadi `paid` secara idempoten, notifikasi in-app terkirim, diarahkan ke tiket booking bergaris putus-putus. | Lolos |
| 2.6 | **Perpanjangan Bulanan** | Buka booking Bulanan dengan sisa 1 pertemuan, klik "Lanjutkan". | Membuat booking Bulanan baru untuk 4 pertemuan berikutnya (+7 s.d. +28 hari dari tanggal terakhir) dengan `renewed_from_id` terisi. Menawarkan alternatif lapangan/sesi jika ada bentrok. | Lolos |
| 2.7 | **Pembatalan Mandiri** | Buka booking berstatus `pending_payment`, klik "Batalkan booking". | Booking dibatalkan, slot booking langsung dihapus dari jadwal publik sehingga kembali kosong. | Lolos |

---

## 3. Pengujian Skenario Admin Operasional

| No | Skenario Uji | Prosedur Uji | Ekspektasi Hasil | Status |
| :-: | :--- | :--- | :--- | :-: |
| 3.1 | **Landing Hari Ini** | Buka `/admin`. | Daftar sesi tersusun: Sedang Main di paling atas, lalu Berikutnya, lalu Lewat. Nama PB dan nomor HP (tel:) pemesan tampil jelas. | Lolos |
| 3.2 | **Presensi Kehadiran** | Klik tombol "Sudah hadir" (48px) pada slot hari ini. | Status kehadiran tercatat dengan stempel waktu, tombol beralih menjadi "Batal hadir", aksi tercatat di `activity_logs`. | Lolos |
| 3.3 | **Booking Langsung Kasir** | Buka `/admin/booking-langsung`, pilih slot, pilih/buat pelanggan, pilih metode "Tunai, sudah dibayar". | Booking langsung dibuat dan berstatus paid lunas, payment recorded_by tercatat, slot terkunci seketika. | Lolos |
| 3.4 | **Tutup Slot Manual** | Buka `/admin/tutup-slot`, pilih lapangan, tanggal, sesi, dan isi alasan wajib. | Jika ada booking aktif pada slot tersebut, sistem menolak dan menampilkan bentrok. Jika kosong, slot berhasil diblokir. | Lolos |
| 3.5 | **Pembatalan Admin** | Buka detail booking, klik "Batalkan booking", isi alasan pembatalan dan masukkan password admin. | Slot masa depan dibebaskan kembali ke jadwal, riwayat slot lampau dipertahankan, notifikasi pembatalan masuk ke akun pemesan. | Lolos |
| 3.6 | **Jatah Habis** | Buka `/admin/jatah-habis`. | Menampilkan sewa Bulanan yang hampir habis atau baru selesai dalam 7 hari. Tombol "Salin pesan pengingat" menyalin teks sopan tanpa menjanjikan slot terkunci. | Lolos |
| 3.7 | **Manajemen Akun** | Buka `/admin/pengguna`, ubah peran atau reset password dengan verifikasi password admin. | Perubahan peran dan password baru tersimpan, admin tidak bisa menurunkan dirinya sendiri, sistem menolak penonaktifan jika admin tersisa 1 orang. | Lolos |
| 3.8 | **Ekspor CSV Aman** | Ekspor CSV pada daftar booking dan laporan keuangan. | File CSV terunduh, seluruh sel yang dimulai dengan `=`, `+`, `-`, `@` diawali tanda petik tunggal (`'`) untuk mencegah eksekusi formula di Excel/Calc. | Lolos |

---

## 4. Pengujian Skenario Mode Pantau Owner (`/owner`)

| No | Skenario Uji | Prosedur Uji | Ekspektasi Hasil | Status |
| :-: | :--- | :--- | :--- | :-: |
| 4.1 | **Navigasi Mode Pantau** | Buka `/owner`. | Halaman menampilkan Ringkasan Eksekutif, chip navbar `OWNER · MODE PANTAU` menyala, baris angka tipografi dan grafik okupansi 29 hari SVG tampil sempurna. | Lolos |
| 4.2 | **Bebas Tombol Mutasi** | Buka `/owner/hari-ini`, `/owner/booking/{code}`, `/owner/pengguna/{id}`, dan `/owner/pengaturan`. | Seluruh tombol presensi ("Sudah hadir"), form pembatalan, form tambah akun, dan tombol ubah konfigurasi **TIDAK DIRENDER** sama sekali. | Lolos |
| 4.3 | **Penolakan Aksi Mutasi** | Kirim request POST/PUT ke rute admin (misal: POST `/admin/booking-langsung`). | Server merespons dengan status HTTP **403 Forbidden**. | Lolos |
| 4.4 | **Penolakan Area Admin** | Buka URL `/admin` dengan akun login owner. | Server merespons dengan status HTTP **403 Forbidden**. | Lolos |

---

## 5. Pengujian Edge Cases & Keamanan

| No | Skenario Uji | Prosedur Uji | Ekspektasi Hasil | Status |
| :-: | :--- | :--- | :--- | :-: |
| 5.1 | **Konkurensi Slot** | Dua pengguna memesan slot lapangan yang sama persis dalam waktu bersamaan. | Constraint database `UNIQUE (court_id, play_session_id, play_date)` menangkap bentrok; pemesan kedua mendapat pesan ramah: *"Slot ini baru saja diambil orang lain. Pilih slot lain ya."* | Lolos |
| 5.2 | **Masa Tahan Kedaluwarsa** | Booking `pending_payment` melewati masa tahan `hold_minutes` (15 menit). | Command `bookings:expire` menandai booking `expired` dan menghapus baris `booking_slots` sehingga slot otomatis kembali kosong di jadwal publik. | Lolos |
| 5.3 | **Zona Waktu WIT** | Evaluasi sesi bermain pada jam Jayapura (`Asia/Jayapura`). | Seluruh transisi sesi "Sedang Main" dan "Lewat" dievaluasi konsisten menurut waktu Jayapura tanpa offset salah. | Lolos |
