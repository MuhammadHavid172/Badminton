# Matriks Peran & Hak Akses Pengguna

Sistem Booking Lapangan Badminton menerapkan arsitektur keamanan tiga peran pengguna (*Role-Based Access Control*) yang terpisah secara tegas tanpa peran ambigu "penjaga".

---

## 1. Definisi Tiga Peran Pengguna

### 1. `customer` (Pelanggan / Pembooking)
- **Karakteristik**: Pengguna umum yang memesan lapangan untuk bermain badminton secara mandiri maupun atas nama perkumpulan bulutangkis (PB)/tim.
- **Tujuan**: Melihat jadwal terbuka, memilih slot, menyelesaikan pembayaran, mendapatkan bukti booking, menerima pengingat perpanjangan sewa, dan mengelola profil akun.
- **Pendaftaran**: Dapat mendaftar mandiri via `/daftar` atau langsung inline saat proses booking di `/booking/baru`.

### 2. `admin` (Admin Operasional GOR)
- **Karakteristik**: Menggabungkan seluruh fungsi operasional harian lapangan (penjaga lapangan/resepsionis) sekaligus manajer administratif GOR.
- **Tujuan**: Bertanggung jawab atas presensi kehadiran di lapangan, menerima pemesanan langsung (*walk-in booking*) di kasir, menutup/membuka lapangan untuk perawatan, membatalkan booking jika terjadi force majeure, mengelola akun pengguna, mengatur tarif sewa, serta melihat log audit aktivitas.
- **Akses Area**: Area `/admin/*`. Dilarang mengakses area `/owner/*`.

### 3. `owner` (Pemilik GOR - Mode Pantau Penuh)
- **Karakteristik**: Pemilik fasilitas GOR yang membutuhkan visibilitas eksekutif atas performa bisnis tanpa risiko mengubah operasional harian atau merusak data transaksi secara tidak sengaja.
- **Tujuan**: Memantau ringkasan pendapatan harian/bulanan, grafik tren okupansi lapangan, efektivitas perpanjangan Bulanan (*renewal rate*), daftar pelanggan, log aktivitas sistem, dan laporan keuangan tanpa hak modifikasi.
- **Akses Area**: Area `/owner/*`. Dilarang keras mengakses area `/admin/*` dan dilarang melakukan aksi mutasi apapun (HTTP 403 Forbidden).

---

## 2. Matriks Hak Akses Fitur & Endpoint

| Modul / Kemampuan | Publik (Tamu) | Customer | Admin | Owner (Pantau) |
| :--- | :---: | :---: | :---: | :---: |
| **Lihat Jadwal Terbuka (`/jadwal`)** | Ya | Ya | Ya | Ya |
| **Pesan Slot Harian / Bulanan Online** | Harus Daftar | Ya | Ya | Ditolak (403) |
| **Bayar Booking Mandiri (`/booking/{code}/bayar`)** | Tidak | Hanya Milik Sendiri | Tidak | Tidak |
| **Cetak Tiket & Bukti Bayar** | Tidak | Hanya Milik Sendiri | Semua | Semua |
| **Perpanjang Sewa Bulanan Mandiri** | Tidak | Hanya Milik Sendiri | Tidak | Tidak |
| **Notifikasi In-App Jadwal & Pembayaran** | Tidak | Ya | Tidak | Tidak |
| **Presensi Check-In Pemain di Lapangan** | Tidak | Tidak | **Ya** | Tidak (Read-Only) |
| **Booking Langsung Kasir (Tunai / Tahan)** | Tidak | Tidak | **Ya** | Tidak (403) |
| **Tutup / Buka Slot Lapangan Manual** | Tidak | Tidak | **Ya** | Tidak (403) |
| **Batalkan Booking Pelanggan** | Tidak | Hanya Pending Sendiri | **Ya (Wajib Alasan)** | Tidak (403) |
| **Perpanjang Bulanan atas Nama Pelanggan**| Tidak | Tidak | **Ya** | Tidak (403) |
| **Lihat Nomor HP Pelanggan Penuh** | Disamarkan | Disamarkan | **Ya** | **Ya** |
| **Tambah Pengguna Baru** | Tidak | Tidak | **Ya** | Tidak (403) |
| **Ubah Peran Pengguna (Role Management)** | Tidak | Tidak | **Ya (Perlu Password)**| Tidak (403) |
| **Aktifkan / Nonaktifkan Akun Pengguna** | Tidak | Tidak | **Ya (Perlu Password)**| Tidak (403) |
| **Reset Password Pengguna** | Tidak | Tidak | **Ya (Perlu Password)**| Tidak (403) |
| **Ubah Tarif & Waktu Tahan Booking** | Tidak | Tidak | **Ya (Perlu Password)**| Teks Statis (403) |
| **Ekspor CSV Laporan Transaksi Keuangan**| Tidak | Tidak | **Ya** | **Ya** |
| **Inspeksi Log Audit Aktivitas Lengkap** | Tidak | Tidak | **Ya** | **Ya** |

---

## 3. Penegakan Keamanan & Aturan Khusus

1. **Anti Self-Demotion**: Admin tidak dapat menurunkan peran akunnya sendiri dari admin untuk mencegah penguncian sistem.
2. **Anti Self-Deactivation**: Admin tidak dapat menonaktifkan status akunnya sendiri.
3. **Anti Zero-Admin**: Sistem memastikan harus selalu ada minimal 1 admin aktif dalam sistem. Aksi penurunan atau penonaktifan admin terakhir akan ditolak oleh sistem.
4. **Konfirmasi Password Sensitif**: Pembatalan booking berbayar, pengubahan peran pengguna, penonaktifan akun, reset password pengguna, dan pengubahan pengaturan tarif mewajibkan konfirmasi password admin yang sedang aktif.
5. **Enforce Idle Timeout**: Sesi admin dan owner yang tidak aktif selama lebih dari batas waktu konfigurasi (default 60 menit) secara otomatis dikeluarkan (*logout*) demi keamanan workstation di resepsionis.
6. **Mode Pantau Owner Bebas Form Mutasi**: View `/owner/*` dibangun dengan flag `$modePantau = true`. Seluruh tombol aksi, tombol presensi, form pembatalan, form booking langsung, dan form submit pengaturan tidak dirender di antarmuka HTML.
