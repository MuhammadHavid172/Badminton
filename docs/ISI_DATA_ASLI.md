# Panduan Mengisi Data Asli Operasional GOR

Dokumen ini menjelaskan langkah-langkah praktis untuk mengganti data demonstrasi bawaan (*placeholder*) dengan data riil GOR sebelum sistem dibuka untuk publik secara resmi.

---

## 1. Menyesuaikan Identitas GOR & Kanal Kontak

Buka berkas `config/gor.php` atau atur via `.env`:

```env
GOR_NAME="GOR Cendrawasih Jayapura"
GOR_CITY="Jayapura"
GOR_TIMEZONE=Asia/Jayapura
```

Pada `config/gor.php`:
```php
'name' => env('GOR_NAME', 'GOR Cendrawasih Jayapura'),
'city' => env('GOR_CITY', 'Jayapura'),
'address' => 'Jl. Pemuda No. 45, Abepura, Kota Jayapura, Papua',
'phone' => '081234567890',
'whatsapp' => '081234567890',
'instagram' => 'gorcendrawasih.official',
```

> [!NOTE]
> Sistem memegang prinsip integritas UI (*Anti AI Slop*): jika kanal kontak kosong (`null` atau string kosong), ikon dan teks yang bergantung padanya otomatis **TIDAK DIRENDER** di antarmuka web, sehingga tidak akan memicu tautan mati (*dead link*).

---

## 2. Menyesuaikan Data Lapangan (`courts`)

Jumlah dan nama lapangan di sistem bersifat **dinamis** dan bersumber dari tabel database `courts`.

1. Sesuaikan daftar lapangan pada `config/gor.php`:
   ```php
   'courts' => [
       ['name' => 'Lapangan 1 (Vinyl Karpet A)', 'sort_order' => 1],
       ['name' => 'Lapangan 2 (Vinyl Karpet B)', 'sort_order' => 2],
       ['name' => 'Lapangan 3 (Parket Kayu)', 'sort_order' => 3],
       ['name' => 'Lapangan VIP', 'sort_order' => 4],
   ],
   ```
2. Jalankan migrasi dan seeder awal pada database produksi:
   ```bash
   php artisan migrate --force
   php artisan db:seed --class=CourtSeeder --force
   ```

---

## 3. Menyesuaikan Sesi Bermain (`play_sessions`)

Jadwal sesi bermain tetap harian didefinisikan dalam `config/gor.php`:
```php
'sessions' => [
    ['label' => 'Sesi 1 (Pagi)', 'start_time' => '08:00:00', 'end_time' => '11:00:00', 'sort_order' => 1],
    ['label' => 'Sesi 2 (Siang)', 'start_time' => '11:00:00', 'end_time' => '15:00:00', 'sort_order' => 2],
    ['label' => 'Sesi 3 (Sore)', 'start_time' => '15:00:00', 'end_time' => '19:00:00', 'sort_order' => 3],
    ['label' => 'Sesi 4 (Malam)', 'start_time' => '19:00:00', 'end_time' => '23:00:00', 'sort_order' => 4],
],
```

Jalankan seeder sesi untuk memasukkan data:
```bash
php artisan db:seed --class=PlaySessionSeeder --force
```

---

## 4. Menentukan Tarif Sewa & Parameter Operasional

Tarif dan aturan penahanan waktu disimpan dalam tabel `settings` dan dapat diubah kapan saja melalui halaman web **/admin/pengaturan**:

- **Harga Harian (`price_single`)**: Tarif sewa 1 slot per pertemuan (misal: Rp150.000).
- **Harga Bulanan (`price_package`)**: Tarif paket 4 pertemuan mingguan (misal: Rp500.000).
- **Masa Tahan Bayar (`hold_minutes`)**: Waktu toleransi pembayaran sebelum slot dilepas (bawaan: 15 menit).
- **Batas Maksimal Pending Booking (`max_pending_bookings`)**: Batas booking menunggu bayar per pengguna agar tidak ada akun yang memborong slot (bawaan: 2).
- **Ambang Pengingat Perpanjangan (`renew_notice_remaining`)**: Dikirim saat sisa pertemuan Bulanan tinggal 1 kali.
- **Jam Notifikasi Hari Terakhir (`last_day_notice_hour`)**: Pukul pengiriman notifikasi in-app pada hari pertemuan terakhir (bawaan: jam 6 pagi WIT).

---

## 5. Mengunggah Foto Lapangan Riil

Letakkan foto lapangan riil beresolusi tinggi di direktori `resources/images/` atau `storage/app/public/courts/`.

Untuk memproses dan mengompresi gambar secara otomatis menjadi format WebP modern:
```bash
npm run foto
```
Jika foto belum diunggah, antarmuka web secara otomatis menggunakan komponen fallback visual bermotif dot-matrix dan palet navy sinematik tanpa merusak tata letak.

---

## 6. Mengganti Gateway Pembayaran Simulasi ke Gateway Nyata

Saat ini sistem menggunakan gateway pembayaran simulasi (`SimulatedGateway`) yang mengimplementasikan antarmuka `App\Contracts\PaymentGateway`.

Untuk beralih ke penyedia gateway nyata (misalnya Midtrans, Xendit, atau DOKU):
1. Buat class baru, misalnya `App\Services\Payment\MidtransGateway` yang mengimplementasikan `App\Contracts\PaymentGateway`.
2. Implementasikan metode:
   - `createCharge(Booking $booking): array`
   - `verifyPayment(string $reference): bool`
   - `cancelCharge(string $reference): bool`
3. Daftarkan binding class tersebut di `app/Providers/AppServiceProvider.php` berdasarkan konfigurasi `config('gor.payment_driver')`.
4. Atur `GOR_PAYMENT_DRIVER=midtrans` pada berkas `.env` server produksi.

---

## 7. Membuat Akun Admin & Owner Asli

Gunakan command interaktif Artisan yang aman dari command-line server:

```bash
# Membuat akun Admin operasional
php artisan gor:buat-akun --peran=admin

# Membuat akun Owner pemantau
php artisan gor:buat-akun --peran=owner
```

Command akan menanyakan:
- Nama Lengkap
- Nomor Handphone (otomatis dinormalisasi ke format `08xxxxxxxxxx`)
- Email (opsional)
- Password (minimal 8 karakter)

---

## 8. Menghapus Data Akun & Transaksi Demo

Di lingkungan produksi (`APP_ENV=production`), seeder bawaan **TIDAK AKAN** membuat akun demo maupun booking demo.

Jika sebelumnya kamu sempat menjalankan seeder lokal pada database produksi, bersihkan akun dan transaksi demo melalui tinker:

```bash
php artisan tinker --execute="
\App\Models\BookingSlot::truncate();
\App\Models\Payment::truncate();
\App\Models\Booking::truncate();
\App\Models\SlotBlock::truncate();
\App\Models\User::whereIn('phone', ['081100000001', '081100000003', '081100000004', '081100000005'])->delete();
"
```
Sistem kini bersih dan siap melayani pemesanan publik secara penuh.
