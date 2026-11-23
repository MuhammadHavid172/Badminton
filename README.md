# Sistem Booking Lapangan Badminton

Sistem informasi dan pemesanan lapangan badminton modern dengan estetika gelap sinematik (*dark cinematic*), tipografi raksasa rapat (*Inter Tight*), aksen tunggal limau (`#DAFF04`), banyak ruang kosong, serta aturan ketat integritas UI (*Anti AI Slop*).

Sistem dikembangkan bertahap sesuai acuan dokumen spesifikasi [docs/SPEK.md](docs/SPEK.md) dan panduan desain [docs/DESIGN.md](docs/DESIGN.md).

---

## 1. Status Progres Pengembangan

- [x] **Fase 0**: Sistem Desain, Token CSS, Arsitektur Blade, & Landing Page Responsif
- [x] **Fase 1**: Fondasi Data (Skema Lengkap DB, Enum, Model Relasi, Helper, Seeder Dinamis & Data Demo)
- [x] **Fase 2**: Jadwal Publik (`/jadwal`)
- [x] **Fase 3**: Otentikasi & Akun (`/masuk`, `/daftar`, Profil)
- [x] **Fase 4**: Alur Booking Pengguna (Harian & Bulanan)
- [x] **Fase 5**: Pembayaran, Perpanjang Bulanan, & Peringatan
- [x] **Fase 6**: Admin Operasional (Area `/admin`, Check-in, Jatah Habis, Booking Langsung)
- [x] **Fase 7**: Admin Manajemen & Mode Pantau Owner (`/owner`)
- [ ] **Fase 8**: Poles, Keamanan, & Deploy

---

## 2. Fondasi Data (Fase 1)

### A. Keputusan Inti Data & Bisnis
- **Zona Waktu**: `Asia/Jayapura` (WIT) untuk seluruh perhitungan tanggal, waktu sesi, dan pencatatan riwayat.
- **Bahasa & Tone**: Bahasa Indonesia dengan kata ganti santai "kamu" di seluruh antarmuka pengguna.
- **Lapangan adalah Data Dinamis**: Jumlah dan nama lapangan bukan angka hardcoded, melainkan bersumber dari `config/gor.php` (bawaan 3 placeholder) dan disimpan dalam tabel `courts`.
- **Sesi Bermain**: 4 sesi operasional tetap per hari (`play_sessions`):
  - Sesi 1: `08.00-11.00`
  - Sesi 2: `11.00-15.00`
  - Sesi 3: `15.00-19.00`
  - Sesi 4: `19.00-23.00`
- **Kategori Sewa**:
  - **Harian** (`single`): 1 slot (lapangan + tanggal + sesi).
  - **Bulanan** (`package`): 4 pertemuan mingguan berturut-turut pada hari, lapangan, dan sesi yang sama (+7, +14, +21 hari).
  - *Catatan Kepatuhan*: Di UI WAJIB selalu berlabel "Harian" dan "Bulanan" (dilarang memakai "Sekali main" atau "Paket bulanan").
- **Kunci Pencegahan Double Booking**: Constraint `UNIQUE (court_id, play_session_id, play_date)` pada tabel `booking_slots`.

### B. Skema Database Lengkap
1. `users`: `id`, `name`, `role` (enum: `customer`, `admin`, `owner`), `phone` (unik), `email` (nullable, unik), `team_name` (nullable), `is_active`, `password`, `timestamps`.
2. `courts`: `id`, `name`, `sort_order`, `is_active`, `timestamps`.
3. `play_sessions`: `id`, `label`, `start_time`, `end_time`, `sort_order`, `is_active`, `timestamps`.
4. `bookings`: `id`, `code` (unik, acak 8 karakter alphanumeric), `user_id`, `type` (`single`, `package`), `status` (`pending_payment`, `paid`, `cancelled`, `expired`), `total_price`, `expires_at`, `notes`, `cancelled_at`, `cancelled_by`, `cancel_reason`, `refund_note`, `renewed_from_id`, `renewal_notified_at`, `last_day_notified_at`, `ended_notified_at`, `created_by`, `timestamps`.
5. `booking_slots`: `id`, `booking_id` (cascade), `court_id`, `play_session_id`, `play_date`, `checked_in_at`, `checked_in_by`, `attendance_note`, `timestamps`. `UNIQUE (court_id, play_session_id, play_date)`.
6. `slot_blocks`: `id`, `court_id`, `play_date`, `play_session_id` (nullable = seluruh sesi), `reason`, `created_by`, `timestamps`.
7. `payments`: `id`, `booking_id`, `method`, `provider` (bawaan `simulated`), `amount`, `status` (`pending`, `paid`, `failed`), `reference`, `payload` (JSON), `recorded_by`, `paid_at`, `timestamps`.
8. `settings`: `id`, `key` (unik), `value`, `timestamps` (didukung helper `Setting::get()` dan `Setting::set()` dengan auto-cache).
9. `activity_logs`: `id`, `user_id` (nullable), `role`, `action`, `subject_type`, `subject_id`, `description`, `meta` (JSON), `created_at`.
10. `notifications`: Tabel notifikasi database standar Laravel.

---

## 3. Jadwal Publik & Siklus Paket (Fase 2)

### A. Layanan Inti
- **`App\Services\PackageLifecycle`**:
  - Mengelola logika siklus booking Bulanan: menghitung total pertemuan, tanggal pertemuan terakhir, dan sisa sesi yang belum selesai menurut zona waktu WIT (`Asia/Jayapura`).
  - Menentukan state siklus: `aktif`, `hampir_habis` (sisa <= 1 sesi), `terakhir_hari_ini`, dan `selesai`.
  - Melacak status lanjutan perpanjangan (`belum_lanjut`, `perpanjangan_menunggu_bayar`, `sudah_lanjut`).
  - Menampilkan penanda `Sisa 1x` pada jadwal publik jika sisa pertemuan = 1 dan belum diperpanjang; penanda otomatis hilang begitu perpanjangan berstatus dibayar.
- **`App\Services\ScheduleService`**:
  - Menghasilkan struktur `dayGrid()` harian dan `monthSummary()` bulanan bebas N+1.
  - Resolusi status slot terpadu:
    1. `ditutup`: Penutupan lapangan (slot_blocks) mengalahkan status lain; menampilkan alasan penutupan.
    2. `terisi`: Booking dibayar; menampilkan nama PB / nama pemesan dan badge `HARIAN` atau `BULANAN` (nomor HP tidak diekspos ke publik).
    3. `menunggu_bayar`: Booking pending yang belum kedaluwarsa (ditahan sementara); tidak menampilkan nama pemesan. Hold kedaluwarsa otomatis dianggap kosong.
    4. `lewat`: Slot di masa lalu yang sesinya telah berakhir.
    5. `kosong`: Slot aktif siap dipesan.

### B. Antarmuka Jadwal Publik (`/jadwal`)
- **Tampilan Per Hari**:
  - Tanggal terpilih sebagai elemen paling dominan dengan tipografi raksasa.
  - Navigasi tanggal lengkap: Tombol Sebelumnya, Hari Ini, Berikutnya, input Date Picker, dan Strip 7 Hari Terdekat sebagai jalan pintas cepat.
  - Papan datar bergaris tipis dengan kolom dinamis mengikuti data lapangan dalam database.
  - Penanda `Sedang Main` pada sesi yang aktif berlangsung hari ini.
  - Komponen `<x-slot-cell>` dengan aksesibilitas tinggi (teks eksplisit + pola tekstur visual).
  - Tampilan mobile responsif (< 640px): berubah menjadi blok sesi bertumpuk dengan grid lapangan 2 kolom.
  - Pembaruan otomatis setiap 60 detik tanpa reload penuh (menjeda otomatis saat tab browser tidak aktif).
- **Tampilan Kalender**:
  - Kalender bulanan dimulai dari hari Senin dengan judul bulan besar dan navigasi antar bulan.
  - Tiap sel tanggal memuat angka tanggal, jumlah slot kosong, dan matriks mini kotak status sesi x lapangan (dengan titik putih penanda sewa Bulanan).
  - Klik pada tanggal langsung membuka rute tampilan per hari pada tanggal tersebut.
- **Section Hari Ini di Beranda (`/`)**:
  - Papan ringkas jadwal hari ini disematkan pada landing page utama dan seluruh CTA mengarah ke `/jadwal` nyata.

---

## 4. Otentikasi, Akun, & Sistem Peran (Fase 3)

### A. Fitur & Keamanan
- **Tanpa Starter Kit**: Seluruh controller, view, dan middleware dibangun mandiri tanpa dependensi Breeze, Jetstream, atau Fortify.
- **Normalisasi Nomor HP**: Helper `normalisasi_nomor_hp()` mengubah berbagai format (+62, 62, spasi, tanda hubung) menjadi format baku `08xxxxxxxxxx` (10-14 digit).
- **Fleksibilitas Login**: Satu field input mendukung nomor HP maupun alamat email secara otomatis.
- **Proteksi Brute-Force**: Rate limiting 5 percobaan gagal per menit per identitas + IP dengan pesan bahasa Indonesia yang menyebut sisa waktu tunggu detik.
- **Proteksi Akun Nonaktif**:
  - Pengguna dengan `is_active = false` ditolak saat mencoba login dengan pesan jelas.
  - Middleware `EnsureAccountIsActive` pada grup `web` secara otomatis mengeluarkan (*logout*) sesi pengguna yang sedang aktif jika status akunnya dinonaktifkan oleh admin.
- **Pengalihan Berbasis Peran & Intended URL**:
  - Admin otomatis dialihkan ke `/admin`.
  - Owner otomatis dialihkan ke `/owner`.
  - Customer otomatis dialihkan ke `/akun` (atau ke halaman tujuan semula via `redirect()->intended()`).
- **Otorisasi Hak Akses**:
  - Middleware `CheckRole` membatasi akses ke dashboard spesifik peran; akses terlarang menampilkan halaman error 403 kustom bergaya desain dengan kata santai "kamu".

### B. Antarmuka Autentikasi
- **Layout Dua Kolom**:
  - Halaman `/masuk` dan `/daftar` dirancang dengan layout 2 kolom di desktop (kiri: judul tipografi raksasa dengan pola titik halus; kanan: form murni langsung di atas latar belakang hitam tanpa kartu kaca).
  - Form responsif 1 kolom di HP (< 768px).
- **Navbar Adaptif & Dropdown Pengguna**:
  - Tamu (*guest*): Tombol "Masuk" dan tombol solid "Daftar".
  - Pengguna masuk (*auth*): Nama panggilan dengan menu dropdown interaktif (Akun saya, Dashboard Admin/Owner, Keluar). Dapat dioperasikan penuh melalui keyboard, dan tombol `Escape` otomatis menutup menu.

### C. CLI Artisan Command
Perintah CLI mandiri untuk membuat akun pengelola GOR di lingkungan produksi/lokal:
```bash
# Membuat akun Admin atau Owner secara interaktif
php artisan gor:buat-akun

# Opsi langsung
php artisan gor:buat-akun --nama="Nama Admin" --hp="08123456789" --password="password123" --peran=admin

# Alias cepat pembuatan Admin
php artisan gor:buat-admin --nama="Nama Admin" --hp="08123456789" --password="password123"
```

---

## 5. Alur Booking Pengguna & Tiket (Fase 4)

### A. Fitur & Mekanisme Inti
- **Integrasi Papan Jadwal ke Booking**: Seluruh sel berstatus `kosong` pada halaman `/jadwal` berfungsi langsung sebagai tautan interaktif menuju `GET /booking/baru?lapangan=&sesi=&tanggal=`.
- **Pendaftaran Ringkas Inline (*Guest Checkout*)**: Pengunjung tamu yang belum login langsung disajikan form pendaftaran ringkas (Nama, Nomor HP, Password, Tim & Email opsional) di halaman `/booking/baru`. Setelah klik "Booking sekarang", akun otomatis dibuat, sesi langsung login, dan transaksi booking langsung tercipta tanpa kehilangan pilihan slot.
- **Kategori Sewa Harian vs Bulanan**:
  - **Harian**: 1 pertemuan pada tanggal & sesi terpilih. Harga snapshot `settings.price_single` (Rp 200.000).
  - **Bulanan**: 4 pertemuan mingguan terkunci berturut-turut pada hari, lapangan, dan sesi yang sama (+0, +7, +14, +21 hari). Harga snapshot `settings.price_package` (Rp 600.000). Perhitungan hemat ditampilkan otomatis (`Hemat Rp 200.000`), beserta kalimat penegas: *"Slot lanjutan tidak dikunci. Siapa cepat dia dapat."*.
  - *Pengecekan Bentrok*: Jika ada satu tanggal dari 4 pertemuan yang bentrok (terisi atau ditutup), radio Bulanan otomatis dinonaktifkan (*disabled*) dan menyebutkan tanggal yang bentrok.
- **Pengamanan Konkurensi Database**:
  - Constraint `UNIQUE (court_id, play_session_id, play_date)` pada database adalah penjaga utama konkurensi.
  - Jika dua pengguna memesan slot yang sama dalam waktu berdekatan, transaksi kedua gagal secara ramah dengan pesan: *"Slot ini baru saja diambil orang lain. Pilih slot lain ya."*.
- **Waktu Tahan (*Hold Minutes*) & Hitung Mundur**:
  - Setiap booking baru berstatus `pending_payment` dengan `expires_at = now + hold_minutes` (bawaan 15 menit).
  - Halaman detail tiket (`/booking/{code}`) menyajikan hitung mundur waktu tahan (format `mm:ss`, muat ulang otomatis saat habis, tanpa atribut `aria-live` yang berisik bagi pembaca layar).
- **Pembatalan oleh Pemesan**:
  - Pemesan dapat membatalkan booking berstatus `pending_payment` melalui tombol "Batalkan booking"; slot langsung dibebaskan dan dihapus dari `booking_slots`.
- **Halaman "Booking Saya" (`/booking`)**:
  - Terbagi menjadi 3 bagian: **Menunggu Bayar**, **Akan Datang**, dan **Riwayat**.
  - Menampilkan kartu baris lengkap dengan kategori, jam, lapangan, daftar tanggal pertemuan (menandai pertemuan yang sudah lewat), total harga, status badge, dan tombol aksi.
- **Penanda "Punyamu" pada Jadwal**:
  - Sel jadwal terisi milik pengguna yang sedang login diberi badge khusus `"Punyamu"`.
- **Otorisasi & Kebijakan Hak Akses (`BookingPolicy`)**:
  - Detail booking hanya dapat diakses oleh pemilik booking, admin, dan owner.
  - Pembatalan mandiri hanya dapat dilakukan oleh pemilik booking.
  - Akun dengan peran `owner` dilarang membuat booking dan disajikan halaman penjelas *"Akun owner hanya untuk memantau"*.
- **Pembersihan Otomatis (*Scheduler*)**:
  - Command `php artisan bookings:expire` membersihkan booking `pending_payment` yang melewati `expires_at` dan menghapus slotnya. Booking `paid` aman dan tidak disentuh.
  - Dijadwalkan berjalan setiap menit di `app/Console/Kernel.php`.

### B. Menjalankan Task Scheduler
Untuk menjalankan cron/scheduler pembersihan slot otomatis di lingkungan lokal:
```bash
php artisan schedule:work
```
Pada server produksi, tambahkan satu baris cron pada crontab:
```bash
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

### C. Desain "Slip Tiket" Datar
Mengikuti aturan ketat [docs/DESIGN.md](docs/DESIGN.md):
- Permukaan terang datar bertekstur kertas halus (`#F4F4F0`, teks gelap `#1A1A18`) dengan garis putus-putus (*dashed border*) dan kode booking besar monospace. Ini adalah **satu-satunya elemen terang** di tengah halaman tema gelap.
- Pada layar ponsel (< 768px), panel ringkasan menempel (*sticky bottom bar*) di bagian bawah layar dengan tombol "Booking sekarang" yang otomatis dinonaktifkan saat submit.

---

## 6. Pembayaran, Perpanjang Bulanan, & Peringatan (Fase 5)

### A. Gateway Pembayaran & Halaman Bayar
- **Interface & Driver `App\Contracts\PaymentGateway`**:
  - Diimplementasikan oleh `App\Services\Payment\SimulatedGateway` yang dipilih melalui `config('gor.payment_driver')`.
  - Berhasil membayar: transaksi database atomik dengan pesimistik lock (`lockForUpdate`), idempoten, mengupdate `payments` dan `bookings` menjadi `paid`.
  - Mengabaikan/menolak booking yang sudah kedaluwarsa atau dibatalkan.
  - Tombol uji sementara Fase 4 ("Simulasi lunas") telah dihapus dari seluruh tampilan.
- **Halaman Pembayaran `/booking/{code}/bayar`**:
  - Menampilkan ringkasan sewa, total tagihan besar, dan hitung mundur waktu tahan.
  - Banner jelas: `"MODE SIMULASI: tidak ada uang yang dipindahkan"`.
  - **QR Pembayaran Simulasi**: Komponen `<x-qr-simulasi>` murni SVG yang digenerate terdeterminasi dari kode booking dengan label tegas `SIMULASI` di tengah matriks (bukan QR sungguhan dan tanpa pustaka pihak ketiga).
  - Aksi: Tombol "Simulasikan pembayaran berhasil" dan "Simulasikan gagal" (mencatat percobaan gagal namun booking tetap pending untuk dicoba lagi).
- **Perlindungan Lingkungan Produksi**:
  - Jika `APP_ENV=production` dan `payment_driver=simulated`, sistem memicu log peringatan saat boot aplikasi (`AppServiceProvider`) serta menampilkan banner permanen `MODE UJI` di halaman pembayaran dan footer situs.

### B. Bukti Booking & Cetak PDF
- Tiket lunas pada `/booking/{code}` menyajikan rincian lengkap pertemuan, kode booking besar monospace, waktu pembayaran terverifikasi WIT, serta riwayat percobaan transaksi.
- Dilengkapi stylesheet `@media print` sehingga pengguna dapat mencetak slip tiket atau menyimpannya langsung sebagai file PDF dari browser melalui tombol "Cetak Tiket / Simpan PDF".

### C. Siklus Bulanan & Perpanjangan (Siapa Cepat Dia Dapat)
- **Kemajuan Visual di UI**:
  - Menampilkan teks progres "Pertemuan X dari 4" dan indikator visual batang kemajuan 4 kotak datar.
  - Chip state siklus: `Aktif`, `Hampir Habis` (sisa <= 1 pertemuan), `Terakhir Hari Ini`, `Sudah Lanjut`, `Perpanjangan Menunggu Bayar`, dan `Selesai`.
  - Banner tipis (*dismissable*) pada halaman `/booking` untuk mengingatkan pemilik sewa Bulanan yang hampir habis atau terakhir hari ini.
- **Mekanisme Perpanjangan**:
  - Tombol "Lanjutkan Sewa Bulanan" muncul saat sisa pertemuan <= `settings.renew_notice_remaining` (1) dan >= 1.
  - Aturan: **Siapa cepat dia dapat** (tanpa masa prioritas atau pencadangan slot).
  - Menghasilkan booking Bulanan baru dengan 4 tanggal lanjutan (+7, +14, +21, +28 hari dari tanggal terakhir), kolom `renewed_from_id` terisi, dan snapshot harga terbaru.
- **Layanan Alternatif `RenewalAlternatives`**:
  - Jika keempat tanggal lanjutan bentrok (terisi atau diblokir), sistem menampilkan tanggal bentrok dan mencarikan alternatif:
    1. **Alternatif (a)**: Lapangan lain di sesi dan hari yang sama yang kosong 4 minggu berturut-turut.
    2. **Alternatif (b)**: Sesi lain di lapangan dan hari yang sama yang kosong 4 minggu berturut-turut.
  - Pemesan dapat memilih opsi alternatif untuk membuat booking lanjutan, atau disajikan pesan jujur dan tautan jadwal jika tidak ada alternatif yang tersedia.

### D. Sistem Notifikasi Dalam Aplikasi (*In-App Database Notifications*)
- Notifikasi idempoten tersimpan di database untuk 5 kejadian penting:
  1. **Pembayaran Berhasil**: Diterima langsung saat konfirmasi pembayaran.
  2. **Jatah Hampir Habis**: Dikirim sekali per booking saat sisa pertemuan <= 1 (dikunci via `renewal_notified_at`).
  3. **Terakhir Hari Ini**: Dikirim sekali per booking sejak jam `settings.last_day_notice_hour` (jam 6 pagi WIT) (dikunci via `last_day_notified_at`).
  4. **Masa Sewa Berakhir**: Dikirim saat seluruh sesi telah selesai dengan pesan adaptif apakah slot minggu berikutnya masih kosong atau telah diambil orang lain (dikunci via `ended_notified_at`).
  5. **Waktu Pembayaran Habis**: Dikirim saat booking pending payment kedaluwarsa.
- **Command Scheduler `bookings:notify-renewal`**:
  - Berjalan setiap jam melalui scheduler Laravel (`php artisan schedule:work`). Tidak mengirim notifikasi untuk booking yang sudah diperpanjang (`sudah_lanjut`) atau dibatalkan.
- **Antarmuka Notifikasi**:
  - Navbar: Tautan "Notifikasi" dengan angka belum dibaca berupa teks monospace DM Mono kecil (bukan lencana bulat merah).
  - Halaman `/notifikasi`: Daftar baris notifikasi (item belum dibaca bergaris tebal limau di kiri) dengan tombol "Tandai Semua Dibaca".

---

## 7. Admin Operasional (Fase 6)

### A. Antarmuka Mobile-First & Touch Target 48px
Admin menggabungkan fungsi penjaga lapangan dan administrator operasional:
- Seluruh antarmuka dirancang *mobile-first* dengan tombol aksi dan item interaktif berukuran minimal **48px** untuk kenyamanan jempol saat mengelola presensi di lapangan.
- **Navigasi Ganda**:
  - Desktop (>= 768px): Sub-navigasi horizontal di bawah header (`Hari Ini`, `Jatah Habis`, `Daftar Booking`, `Booking Langsung`, `Tutup / Buka Slot`).
  - Ponsel (< 768px): Bilah bawah tetap (*fixed bottom bar*) 5 item: **Hari Ini**, **Booking**, **Langsung**, **Jatah** (dengan dot badge indikator), dan **Tutup**.

### B. Landing Admin "Hari Ini" (`/admin`)
- Daftar per sesi (bukan tabel lebar yang memerlukan scroll horizontal):
  - Sesi yang sedang berlangsung (*Sedang Berlangsung*) otomatis ditempatkan paling atas dengan highlight garis limau.
  - Sesi berikutnya ditempatkan di tengah.
  - Sesi yang sudah lewat ditempatkan di bawah dalam kondisi diredupkan (*dimmed*).
- Setiap item lapangan menyajikan nama PB/pemesan, tautan telepon langsung (`tel:`), badge kategori (`HARIAN` / `BULANAN`), chip siklus ("Pertemuan X dari 4" atau "TERAKHIR HARI INI"), status pembayaran, dan tombol besar **"Sudah Hadir"** (check-in kehadiran, dapat dibatalkan jika salah klik).
- Slot kosong menyajikan tautan cepat menuju form Booking Langsung. Slot lewat tanpa presensi diberi penanda teks *"Tidak tercatat hadir"*.
- Dilengkapi kotak pencarian instan (`q`) berdasarkan kode booking, nama pemesan, tim, atau nomor HP.

### C. Detail Booking Operasional Admin (`/admin/booking/{code}`)
- Rincian lengkap pemesan (nama, nomor HP, email, PB/tim, waktu booking dibuat, pembuat atas nama jika ada).
- Presensi per slot: Catatan waktu kehadiran (zona Jayapura WIT) dan petugas admin yang mencatat, dilengkapi form check-in / batal check-in per sesi.
- Riwayat transaksi pembayaran terintegrasi.
- **Pembatalan oleh Admin**:
  - Membutuhkan alasan pembatalan wajib dan catatan pengembalian dana (*refund note*) opsional.
  - **Aturan Pembebasan Slot**: Membebaskan SEMUA slot masa depan sehingga bisa dipesan kembali; slot masa lalu yang telah lewat tetap dipertahankan sebagai riwayat kehadiran.
  - Notifikasi pembatalan (`BookingCancelledNotification`) otomatis dikirimkan ke pemesan.
  - **Pengaman Konfirmasi Password**: Jika booking berstatus lunas (`PAID`), admin wajib memasukkan konfirmasi password akun untuk otorisasi pembatalan.

### D. Penanganan Jatah Bulanan Habis (`/admin/jatah-habis`)
- Mendeteksi sewa Bulanan berbayar yang:
  1. Tersisa 1 pertemuan atau akan berakhir dalam 14 hari ke depan.
  2. Baru berakhir dalam 7 hari terakhir dan belum diperpanjang (`continuationStatus !== 'sudah_lanjut'`).
- Nomor HP pemesan dapat dihubungi langsung (`tel:`).
- **Salin Pesan Pengingat**: Satu tombol salin yang otomatis menyalin teks template ramah dari `lang/id/admin.php` ke clipboard untuk dikirimkan ke pelanggan via chat/telepon tanpa menjanjikan slot diamankan.
- **Perpanjang Atas Nama**: Admin dapat memproses sewa 4 minggu lanjutan langsung untuk pelanggan:
  - Opsi *"Tunai, sudah dibayar"*: Langsung lunas (`PAID`), mencatat payment tunai dan log.
  - Opsi *"Tahan dulu"*: Menahan slot dengan status `PENDING_PAYMENT`.

### E. Booking Langsung di Tempat (`/admin/booking-langsung`)
- Memfasilitasi pendaftaran pelanggan di kasir atau via telepon.
- Pilihan slot (lapangan, sesi, tanggal) dan kategori sewa (Harian / Bulanan).
- Fleksibilitas pelanggan: Pilih dari daftar pelanggan yang sudah ada atau buat akun cepat pelanggan baru (nama, nomor HP, nama tim opsional, password acak aman yang bisa direset nanti).
- Metode bayar: *"Tunai, sudah dibayar"* (lunas langsung) atau *"Tahan dulu"*.
- Memakai ulang `BookingService` Fase 4 untuk integritas transaksi.

### F. Tutup & Buka Slot (`/admin/tutup-slot`)
- Fasilitas pemeliharaan lapangan (ganti lampu, perbaikan karpet) atau acara turnamen internal.
- Menutup lapangan pada tanggal tertentu untuk satu sesi spesifik atau semua sesi sepanjang hari.
- **Validasi Anti Bentrok**: Sistem menolak penutupan slot jika sudah terdapat booking aktif (paid atau pending yang masih berlaku) pada jadwal tersebut, dan menampilkan informasi bentrok secara ramah.
- Daftar slot yang sedang ditutup (aktif & mendatang) dapat dibuka kembali (*Buka Kembali Slot*) dengan sekali klik.

### G. Daftar Semua Booking & Ekspor CSV Aman (`/admin/booking`)
- Filter lengkap: Rentang tanggal (dari - sampai), lapangan, status booking, kategori sewa, dan pencarian teks.
- Tampilan responsif: Tabel rapat garis tipis di desktop, berubah menjadi daftar kartu bertumpuk (*stacked list*) di ponsel tanpa scroll horizontal.
- **Ekspor CSV Kebal CSV Injection**:
  - Tombol "Ekspor CSV (Sesuai Filter)" mengekspor seluruh transaksi sesuai filter aktif.
  - **Pengaman Formula / CSV Injection**: Seluruh sel yang diawali karakter berbahaya (`=`, `+`, `-`, `@`) otomatis diawali tanda petik tunggal (`'`) sebelum ditulis ke file stream CSV.
  - Seluruh aktivitas ekspor CSV dicatat ke `activity_logs`.

### H. Pengaman Sistem & Log Aktivitas
- **Idle Timeout**: Middleware `EnforceIdleTimeout` otomatis mengeluarkan (*logout*) sesi admin atau owner yang tidak aktif selama lebih dari `config('gor.idle_timeout_minutes')` (default 60 menit) dan mengarahkan kembali ke halaman login.
- **Audit Log Terintegrasi**: Seluruh mutasi operasional (check-in, batal check-in, booking langsung, perpanjang atas nama, tutup slot, buka slot, pembatalan booking, ekspor CSV) tercatat secara detail di tabel `activity_logs` beserta pelaku, peran, dan metadata.
- **Perlindungan Privasi**: Nomor HP pelanggan hanya dapat dilihat oleh admin dan owner; endpoint publik (`/jadwal`, `/api/jadwal/*`) dijamin tidak pernah mengekspos nomor HP pelanggan.

---

## 8. Admin Manajemen & Mode Pantau Owner (Fase 7)

### A. Dashboard Ringkasan & Grafik Okupansi (`/admin/ringkasan`)
- **Baris Tipografis Raksasa (Tanpa Grid Kartu KPI Berikon)**:
  - Angka metrik ditampilkan dalam satu baris tipografi horizontal besar yang dipisahkan oleh garis vertikal tipis:
    - **Pendapatan Hari Ini & Bulan Ini**: Dihitung dari transaksi pembayaran berstatus `paid` berdasarkan stempel waktu `paid_at` di zona waktu Asia/Jayapura (WIT).
    - **Jumlah Booking Hari Ini**: Total booking baru dibuat hari ini di zona Jayapura.
    - **Persentase Okupansi Hari Ini**: Dihitung dari slot aktif terisi (paid, pending valid, dan slot ditutup) dibagi total kapasitas harian (`total lapangan x total sesi`).
    - **Booking Menunggu Bayar**: Jumlah booking `pending_payment` yang masih dalam masa penahanan aktif.
  - Menerapkan prinsip *Honest Empty State* tanpa kartu KPI berglow atau angka palsu.
- **Grafik Batang Okupansi 29 Hari (SVG Murni Responsif)**:
  - Rentang 29 hari: 14 hari ke belakang, hari ini, dan 14 hari ke depan.
  - Dibangun dengan markup `<svg>` murni tanpa dependensi pustaka grafik pihak ketiga (Chart.js / ApexCharts).
  - Batang hari ini diberi aksen limau khas (`#DAFF04`), hari lampau berlatar abu redup (`#2A2A25`), dan hari mendatang berbingkai abu garis tipis (`#3A3A35`).
- **Daftar Kritis & Penutupan Slot**:
  - Daftar jatah Bulanan hampir habis (sisa <= 1 pertemuan) untuk segera ditindaklanjuti.
  - Daftar penutupan slot pemeliharaan yang akan datang.

### B. Manajemen Pengguna (`/admin/pengguna`)
- **Daftar & Filter Pengguna**:
  - Tabel ringkas responsif (berubah menjadi kartu bertumpuk label-nilai di mobile) dengan pencarian teks (nama, nomor HP, email, PB/tim) dan filter peran (`admin`, `owner`, `customer`) serta status (`aktif`, `nonaktif`).
- **Pendaftaran Pengguna Baru oleh Admin**:
  - Form tambah pengguna untuk membuat akun admin, owner, atau customer baru secara cepat.
  - Normalisasi otomatis nomor HP dan pencatatan audit log `user.created`.
- **Detail Pengguna & Riwayat Booking (`/admin/pengguna/{id}`)**:
  - Menampilkan profil lengkap, status akun, dan seluruh riwayat transaksi booking beserta status pertemuan.
- **Proteksi Tingkat Tinggi Manajemen Akun**:
  - **Anti Self-Demotion**: Admin tidak dapat menurunkan perannya sendiri dari admin.
  - **Anti Self-Deactivation**: Admin tidak dapat menonaktifkan akunnya sendiri.
  - **Anti Zero-Admin**: Sistem menolak deaktivasi atau penurunan peran jika hanya tersisa 1 admin aktif di sistem (owner tidak dihitung).
  - **Konfirmasi Password Admin**: Seluruh tindakan sensitif (ubah peran, aktifkan/nonaktifkan, dan reset password) mewajibkan verifikasi password admin yang sedang login.
  - **Atur Ulang Password**: Admin dapat mereset password pengguna dengan password baru kustom atau otomatis acak, dengan pemberitahuan password sementara via flash message dan audit log `user.password_reset`.

### C. Log Aktivitas Sistem (`/admin/aktivitas`)
- **Pencatatan Riwayat Komprehensif**:
  - Tabel riwayat log aktivitas terbaru di posisi paling atas dengan stempel waktu zona Asia/Jayapura (WIT).
  - Filter pencarian jenis aksi (`action`), peran pelaku (`role`), dan rentang tanggal (`dari` s.d. `sampai`).
  - Modal/toggle inspeksi metadata JSON untuk melihat detail teknis tiap aksi audit.

### D. Laporan Keuangan & Operasional (`/admin/laporan`)
- **Metrik Laporan Terpadu**:
  - Pemfilteran rentang tanggal kustom (bawaan 30 hari terakhir).
  - Total pendapatan riil dari pembayaran `paid` dalam rentang tanggal terpilih.
  - Jumlah booking per kategori (`Harian` vs `Bulanan`).
  - Rincian penerimaan per metode bayar (`simulated`, `tunai`, dll).
  - **Tingkat Perpanjangan Bulanan (Renewal Rate)**: Menghitung jumlah sewa Bulanan yang berakhir dalam rentang waktu, jumlah yang telah diperpanjang (berdasarkan `renewed_from_id`), persentase tingkat perpanjangan, serta daftar transparan pelanggan Bulanan yang tidak memperpanjang.
- **Ekspor CSV Laporan Transaksi Aman**:
  - Ekspor stream CSV laporan transaksi keuangan (`/admin/laporan/ekspor-csv`).
  - Seluruh nilai sel berpotensi formula injection (`=`, `+`, `-`, `@`) otomatis diawali tanda petik tunggal (`'`).
  - Ekspor tercatat secara transparan di `activity_logs` (`laporan.csv_export`).

### E. Pengaturan Operasional (`/admin/pengaturan`)
- Pengaturan parameter dinamis sistem via antarmuka admin:
  - Harga Harian (`price_single`) & Harga Bulanan (`price_package`).
  - Waktu tahan booking menunggu bayar (`hold_minutes`).
  - Batas maksimal booking pending per pengguna (`max_pending_bookings`).
  - Ambang batas pengingat sisa pertemuan Bulanan (`renew_notice_remaining`).
  - Jam pengiriman notifikasi hari terakhir Bulanan (`last_day_notice_hour`).
- **Keamanan & Konsistensi**:
  - Mewajibkan konfirmasi password admin untuk menyimpan perubahan.
  - Perubahan harga dan masa tahan hanya berlaku untuk booking baru ke depan (snapshot harga lama tetap aman).
  - Mencatat nilai sebelum (*old*) dan sesudah (*new*) ke dalam audit log `setting.updated`.

### F. Mode Pantau Owner (`/owner`)
- **Prinsip Read-Only Penuh**:
  - Area `/owner` mencerminkan seluruh halaman admin untuk operasi lihat/pantau saja (GET).
  - Memakai ulang view admin dengan flag `$modePantau = true`: seluruh tombol aksi, tombol presensi check-in, form pembatalan, form booking langsung, form penutupan slot, dan form ubah pengaturan **TIDAK DIRENDER**.
  - Badge navbar menampilkan chip tegas: `OWNER · MODE PANTAU`.
- **Penegakan Hak Akses Server-Side**:
  - Owner dilarang keras mengakses area `/admin/*` (HTTP 403 Forbidden).
  - Owner dilarang keras melakukan aksi mutasi apapun (HTTP 403 Forbidden).
  - Customer dilarang keras mengakses `/admin/*` maupun `/owner/*` (HTTP 403 Forbidden).
  - Halaman pengaturan owner menampilkan parameter sistem dalam format kartu teks statis yang informatif tanpa form input.

---

## 9. Akun Demo & Data Awal (Lingkungan Lokal)

Saat menjalankan `php artisan migrate:fresh --seed` pada `APP_ENV=local`:

| Peran | No. Handphone | Password | Nama / Keterangan |
| :--- | :--- | :--- | :--- |
| **Admin** | `081100000001` | `password` | Admin GOR (Contoh) |
| **Customer** | `081100000003` | `password` | Budi Santoso (PB Rajawali) |
| **Owner** | `081100000004` | `password` | Pemilik GOR (Mode Pantau) |
| **Customer 2** | `081100000005` | `password` | Ahmad Kurnia (PB Garuda) |

Data transaksi demo mencakup:
1. **Harian Paid**: Lapangan 1, Sesi 1 (PB Rajawali) & Lapangan 2, Sesi 2 (PB Garuda).
2. **Bulanan Paid (Sisa 1x Pertemuan)**: Lapangan 1, Sesi 4 (4 pertemuan berturut-turut, 3 lampau + 1 mendatang).
3. **Bulanan Paid (Terakhir Hari Ini)**: Lapangan 2, Sesi 4 (pertemuan ke-4 jatuh pada hari ini).
4. **Bulanan Paid (Baru Berakhir)**: Lapangan 3, Sesi 2 (seluruh 4 sesi lampau, selesai 2 hari lalu).
5. **Pending Payment Aktif**: Lapangan 2, Sesi 3 (berlaku hingga 12 menit ke depan).
6. **Expired Booking**: Transaksi kedaluwarsa dengan slot yang telah dilepas.
7. **Slot Block**: Lapangan 3, Sesi 1 ditutup untuk perawatan rutin karpet & lampu.

---

## 10. Rute Aplikasi

| Rute | Metode | Middleware | Deskripsi | Status |
| :--- | :--- | :--- | :--- | :--- |
| `/` | GET | `web` | Landing Page lengkap dengan ringkasan jadwal hari ini | 200 OK |
| `/jadwal` | GET | `web` | Halaman publik jadwal lapangan (Per Hari & Kalender) | 200 OK |
| `/api/jadwal/hari` | GET | `throttle:60,1` | API JSON jadwal harian (Tanpa info privat, cache 10s) | 200 OK |
| `/api/jadwal/bulan` | GET | `throttle:60,1` | API JSON kalender bulanan (Tanpa info privat, cache 10s) | 200 OK |
| `/booking/baru` | GET | `web` | Halaman form booking (Slip tiket draf, pilihan Harian/Bulanan, pendaftaran inline tamu) | 200 OK |
| `/booking` | POST | `throttle:10,1` | Proses transaksi pembuatan booking baru (DB transaction + UNIQUE guard) | 302 Redirect |
| `/booking` | GET | `auth` | Halaman "Booking Saya" (Menunggu Bayar, Akan Datang, Riwayat) | 200 OK |
| `/booking/{code}` | GET | `auth` | Halaman detail tiket booking dengan bukti lunas & cetak PDF | 200 OK |
| `/booking/{code}/bayar` | GET | `auth` | Halaman pembayaran simulasi dengan QR SVG SIMULASI & hitung mundur | 200 OK |
| `/booking/{code}/bayar/proses` | POST | `auth` | Konfirmasi pembayaran berhasil secara aman & idempoten | 302 Redirect |
| `/booking/{code}/bayar/gagal` | POST | `auth` | Simulasi pembayaran gagal (dapat dicoba ulang) | 302 Redirect |
| `/booking/{code}/perpanjang` | POST | `auth` | Perpanjangan sewa Bulanan (+7 sd +28 hari) & alternatif bentrok | 302 / 200 |
| `/booking/{code}/batal` | POST | `auth` | Pembatalan booking pending payment oleh pemesan | 302 Redirect |
| `/notifikasi` | GET | `auth` | Halaman daftar notifikasi in-app pengguna | 200 OK |
| `/notifikasi/baca-semua` | POST | `auth` | Tandai seluruh notifikasi telah dibaca | 302 Redirect |
| `/notifikasi/{id}` | GET | `auth` | Tandai notifikasi spesifik dibaca dan buka detailnya | 302 Redirect |
| `/masuk` | GET, POST | `guest` | Halaman & proses login (HP/Email + password) | 200 OK |
| `/daftar` | GET, POST | `guest` | Halaman & proses pendaftaran customer baru | 200 OK |
| `/keluar` | POST | `auth` | Proses logout pengguna & invalidasi sesi | 302 Redirect |
| `/akun` | GET | `auth` | Halaman profil dan ubah password pengguna | 200 OK |
| `/akun/profil` | PUT | `auth` | Proses pembaruan nama, email, dan nama tim | 302 Redirect |
| `/akun/password` | PUT | `auth` | Proses ubah password dengan verifikasi password lama | 302 Redirect |
| `/admin` | GET | `auth`, `role:admin` | Landing Admin "Hari Ini" (daftar sesi, presensi 48px, search) | 200 OK |
| `/admin/ringkasan` | GET | `auth`, `role:admin` | Ringkasan angka besar tipografis & grafik okupansi 29 hari SVG | 200 OK |
| `/admin/slot/{id}/check-in` | POST | `auth`, `role:admin` | Pencatatan presensi kehadiran pelanggan per slot | 302 Redirect |
| `/admin/slot/{id}/batal-check-in` | POST | `auth`, `role:admin` | Pembatalan pencatatan kehadiran slot | 302 Redirect |
| `/admin/booking/{code}` | GET | `auth`, `role:admin` | Detail booking operasional, riwayat pembayaran, & presensi | 200 OK |
| `/admin/booking/{code}/batal` | POST | `auth`, `role:admin` | Pembatalan booking oleh admin (alasan wajib, konfirmasi password) | 302 Redirect |
| `/admin/jatah-habis` | GET | `auth`, `role:admin` | Daftar sewa Bulanan kritis/berakhir, salin pengingat | 200 OK |
| `/admin/booking/{code}/perpanjang-atas-nama` | POST | `auth`, `role:admin` | Perpanjangan sewa Bulanan atas nama pemesan (tunai/tahan) | 302 Redirect |
| `/admin/booking-langsung` | GET | `auth`, `role:admin` | Form pembuatan booking langsung di kasir | 200 OK |
| `/admin/booking-langsung` | POST | `auth`, `role:admin` | Proses simpan booking langsung (tunai lunas / tahan) | 302 Redirect |
| `/admin/tutup-slot` | GET | `auth`, `role:admin` | Form tutup slot & daftar slot yang sedang ditutup | 200 OK |
| `/admin/tutup-slot` | POST | `auth`, `role:admin` | Simpan penutupan slot (pengecekan anti booking aktif) | 302 Redirect |
| `/admin/tutup-slot/{id}` | DELETE | `auth`, `role:admin` | Buka kembali slot yang ditutup | 302 Redirect |
| `/admin/booking` | GET | `auth`, `role:admin` | Daftar semua booking dengan filter lengkap & responsif mobile | 200 OK |
| `/admin/booking/ekspor-csv` | GET | `auth`, `role:admin` | Ekspor CSV aman dari CSV Injection sesuai filter aktif | 200 Stream |
| `/admin/pengguna` | GET | `auth`, `role:admin` | Manajemen akun: daftar pengguna, filter peran, & tambah akun | 200 OK |
| `/admin/pengguna` | POST | `auth`, `role:admin` | Simpan akun pengguna baru (admin, owner, customer) | 302 Redirect |
| `/admin/pengguna/{id}` | GET | `auth`, `role:admin` | Detail pengguna, riwayat transaksi booking, & panel kelola akun | 200 OK |
| `/admin/pengguna/{id}/peran` | PUT | `auth`, `role:admin` | Ubah peran pengguna (wajib konfirmasi password admin) | 302 Redirect |
| `/admin/pengguna/{id}/toggle-status` | PUT | `auth`, `role:admin` | Aktifkan/nonaktifkan akun (wajib konfirmasi password admin) | 302 Redirect |
| `/admin/pengguna/{id}/reset-password` | POST | `auth`, `role:admin` | Reset password akun pengguna (wajib konfirmasi password admin) | 302 Redirect |
| `/admin/aktivitas` | GET | `auth`, `role:admin` | Log audit aktivitas sistem lengkap (filter aksi, peran, tanggal) | 200 OK |
| `/admin/laporan` | GET | `auth`, `role:admin` | Laporan pendapatan, kategori, metode bayar, & renewal rate | 200 OK |
| `/admin/laporan/ekspor-csv` | GET | `auth`, `role:admin` | Ekspor CSV transaksi laporan aman dari formula injection | 200 Stream |
| `/admin/pengaturan` | GET | `auth`, `role:admin` | Pengaturan harga, masa tahan bayar, batas booking, notifikasi | 200 OK |
| `/admin/pengaturan` | PUT | `auth`, `role:admin` | Simpan perubahan parameter sistem (wajib konfirmasi password) | 302 Redirect |
| `/owner` | GET | `auth`, `role:owner` | Landing Owner = Ringkasan Eksekutif Mode Pantau | 200 OK |
| `/owner/ringkasan` | GET | `auth`, `role:owner` | Ringkasan angka besar tipografis & grafik okupansi 29 hari SVG | 200 OK |
| `/owner/hari-ini` | GET | `auth`, `role:owner` | Mode pantau jadwal hari ini (tanpa tombol check-in/mutasi) | 200 OK |
| `/owner/jatah-habis` | GET | `auth`, `role:owner` | Mode pantau sewa Bulanan kritis (tanpa tombol perpanjang) | 200 OK |
| `/owner/booking` | GET | `auth`, `role:owner` | Mode pantau daftar booking seluruh pelanggan | 200 OK |
| `/owner/booking/{code}` | GET | `auth`, `role:owner` | Mode pantau detail booking (tanpa form pembatalan) | 200 OK |
| `/owner/pengguna` | GET | `auth`, `role:owner` | Mode pantau daftar akun (tanpa form tambah akun) | 200 OK |
| `/owner/pengguna/{id}` | GET | `auth`, `role:owner` | Mode pantau riwayat akun (tanpa tombol kelola/reset) | 200 OK |
| `/owner/aktivitas` | GET | `auth`, `role:owner` | Mode pantau log aktivitas sistem | 200 OK |
| `/owner/laporan` | GET | `auth`, `role:owner` | Mode pantau laporan operasional, keuangan, & renewal rate | 200 OK |
| `/owner/laporan/ekspor-csv` | GET | `auth`, `role:owner` | Ekspor CSV laporan transaksi keuangan oleh owner | 200 Stream |
| `/owner/pengaturan` | GET | `auth`, `role:owner` | Mode pantau parameter pengaturan sistem (teks statis) | 200 OK |
| `/dev/komponen` | GET | `web` | Katalog komponen visual (Hanya aktif di `APP_ENV=local`) | 200 OK |

---

## 11. Hasil Pengujian (PHPUnit)

Seluruh pengujian unit dan fitur berjalan otomatis:

```bash
php artisan test
```

**Ringkasan Hasil Uji**:
- `Tests\Feature\AdminManagementAndOwnerTest`: 10 pengujian lolos (matriks hak akses 3 peran dan audit rute lengkap, larangan 403 bagi owner untuk semua aksi mutasi dan larangan akses area `/admin/*`, larangan 403 bagi customer untuk area admin dan owner, penegakan mode pantau view bebas tombol dan form mutasi, chip `OWNER · MODE PANTAU` di navbar, proteksi anti-self demotion dan anti-self deactivation admin, proteksi anti zero-admin aktif, penambahan pengguna baru dengan normalisasi nomor HP dan audit log, pengubahan peran pengguna dan reset password dengan verifikasi password admin, perhitungan ringkasan pendapatan zona Jayapura dan grafik batang okupansi 29 hari SVG murni, laporan rasio perpanjangan bulanan terhitung akurat, ekspor stream CSV kebal formula injection dengan prefix tanda petik tunggal dan audit log, update pengaturan operasional dengan verifikasi password admin dan audit log old vs new).
- `Tests\Feature\AdminTest`: 11 pengujian lolos (kontrol akses admin murni & larangan bagi customer/owner, presensi check-in dan pembatalan check-in idempoten dengan catatan kehadiran & activity log, pencarian multi-kriteria kode/nama/tim/HP, daftar jatah habis Bulanan kritis dengan akses nomor HP aman dan proteksi endpoint publik bebas HP, perpanjangan sewa Bulanan atas nama metode tunai lunas & tahan dulu, booking langsung di tempat dengan registrasi otomatis pelanggan baru & lunas kasir, penutupan slot pemeliharaan & penolakan anti-bentrok jika ada booking aktif, pembatalan booking admin dengan pelepasan slot masa depan & penyimpanan riwayat slot lampau serta notifikasi, konfirmasi password admin untuk pembatalan booking berbayar, ekspor stream CSV kebal formula injection dengan prefix petik tunggal & pencatatan log, pemaksaan logout EnforceIdleTimeout untuk sesi admin/owner yang tidak aktif melebihi batas konfigurasi).
- `Tests\Feature\PaymentAndRenewalTest`: 11 pengujian lolos (pembayaran simulasi berhasil & notifikasi database, pembayaran idempoten, penolakan pembayaran booking expired/cancelled, pembayaran gagal lalu coba lagi, otorisasi hanya pemilik yang boleh bayar, visualisasi PackageLifecycle & chip state di UI, perpanjangan Bulanan sukses 4 tanggal & snapshot harga baru, penanganan bentrok perpanjangan & pemilihan alternatif lapangan/sesi, command scheduler `bookings:notify-renewal` idempoten tanpa duplikasi, banner permanen MODE UJI di environment production, halaman notifikasi & tandai semua dibaca).
- `Tests\Feature\BookingTest`: 14 pengujian lolos (Harian 1 slot & snapshot harga, Bulanan 4 slot +7 hari, Bulanan ditolak jika ada tanggal bentrok beserta rincian tanggal, slot ditutup & sesi lewat ditolak, proteksi konkurensi database gagal ramah, pendaftaran ringkas tamu inline langsung booking, batas maksimal pending booking per user, command scheduler `bookings:expire` membersihkan hold tanpa menyentuh paid, pembatalan booking oleh pemesan membebaskan slot, otorisasi BookingPolicy, snapshot harga kebal perubahan setting, peran owner dilarang booking, tiga bagian Booking Saya, penanda "Punyamu" pada sel jadwal).
- `Tests\Feature\AuthTest`: 13 pengujian lolos (normalisasi HP, registrasi, login HP & email, akun nonaktif, rate limit, redirect peran, intended URL, otorisasi 403, logout, artisan commands, ubah profil & password).
- `Tests\Unit\PackageLifecycleTest`: 4 pengujian lolos (total pertemuan, sisa pertemuan zona Jayapura, penanda Sisa 1x & perpanjangan, state siklus).
- `Tests\Unit\EnumTest`: 5 pengujian lolos (nilai, integritas label "Harian" & "Bulanan", status).
- `Tests\Unit\HelperTest`: 2 pengujian lolos (rupiah, tanggal Indonesia WIT).
- `Tests\Feature\ScheduleServiceTest`: 6 pengujian lolos (prioritas ditutup, label PB/nama, hold kedaluwarsa = kosong, sesi lewat, query bounded bebas N+1).
- `Tests\Feature\ScheduleControllerTest`: 6 pengujian lolos (tampilan hari 200, kalender 200, redirect tanggal tidak valid & di luar batas, API JSON bebas info privat).
- `Tests\Feature\DatabaseSchemaTest`: 5 pengujian lolos (skema user & role, relasi court/session, UNIQUE constraint double-booking, setting cache get/set, activity logger).
- `Tests\Feature\SeederTest`: 1 pengujian lolos (kepatuhan seeder terhadap config dinamis & data demo lengkap).
- `Tests\Feature\DesignRulesTest`: 6 pengujian lolos (bebas AI slop: anti kata "Anda", anti frasa "Sekali main" / "Paket bulanan" / "penjaga", anti klaim kosong, anti emoji, anti background-clip text, anti glow/bayangan berwarna, backdrop-filter terbatas pada navbar).
- `Tests\Feature\LandingPageTest`: 9 pengujian lolos.
- `Tests\Unit\ExampleTest` & `Tests\Feature\ExampleTest`: 2 pengujian lolos.

**Total**: **105 Tests Passed (568 assertions)**, 100% hijau.
