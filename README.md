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
- [ ] **Fase 6**: Admin Operasional (Area `/admin`, Check-in, Jatah Habis, Booking Langsung)
- [ ] **Fase 7**: Admin Manajemen & Mode Pantau Owner (`/owner`)
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

## 7. Akun Demo & Data Awal (Lingkungan Lokal)

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

## 8. Rute Aplikasi

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
| `/admin` | GET | `auth`, `role:admin` | Kerangka Dashboard Admin (Siap untuk Fase 6) | 200 OK |
| `/owner` | GET | `auth`, `role:owner` | Kerangka Mode Pantau Owner (Siap untuk Fase 7) | 200 OK |
| `/dev/komponen` | GET | `web` | Katalog komponen visual (Hanya aktif di `APP_ENV=local`) | 200 OK |

---

## 9. Hasil Pengujian (PHPUnit)

Seluruh pengujian unit dan fitur berjalan otomatis:

```bash
php artisan test
```

**Ringkasan Hasil Uji**:
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

**Total**: **84 Tests Passed (372 assertions)**, 100% hijau.
