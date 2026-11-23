# Panduan Deploy Produksi Sistem Booking Lapangan Badminton

Dokumen ini menjelaskan prosedur instalasi, konfigurasi server, backup database, dan pemeliharaan aplikasi booking lapangan badminton untuk lingkungan produksi.

---

## 1. Persyaratan Server

- **PHP**: Versi 8.2 atau lebih baru
- **Ekstensi PHP Wajib**: `BCMath`, `Ctype`, `cURL`, `DOM`, `Fileinfo`, `JSON`, `Mbstring`, `OpenSSL`, `PCRE`, `PDO`, `PDO_MySQL`, `Tokenizer`, `XML`
- **Database**: MySQL 8.0+ atau MariaDB 10.5+ (dengan dukungan JSON dan foreign key constraints)
- **Web Server**: Nginx atau Apache (dengan modul `mod_rewrite`)
- **Composer**: Versi 2.5+
- **Node.js & NPM**: Node 18+ (untuk kompilasi aset Vite)

---

## 2. Opsi A: Deployment pada Shared Hosting (cPanel)

### Langkah 1: Persiapan Berkas
1. Kompilasi aset frontend di lokal sebelum diunggah:
   ```bash
   npm run build
   ```
2. Kompresi seluruh folder proyek ke dalam arsip ZIP (kecualikan folder `.git`, `node_modules`, dan berkas `.env` lokal).

### Langkah 2: Struktur Direktori di cPanel
Untuk keamanan maksimal, pisahkan kode inti aplikasi dari direktori yang dapat diakses publik (`public_html`):
- Ekstrak kode aplikasi ke: `/home/username/badminton_app/`
- Pindahkan seluruh isi folder `/home/username/badminton_app/public/` ke: `/home/username/public_html/`
- Edit berkas `/home/username/public_html/index.php`:
  ```php
  // Ganti path autoloader dan bootstrap ke lokasi direktori aplikasi
  require __DIR__.'/../badminton_app/vendor/autoload.php';
  $app = require_once __DIR__.'/../badminton_app/bootstrap/app.php';
  ```

### Langkah 3: Konfigurasi Database & Environment
1. Buat database dan user MySQL melalui menu **MySQL Databases** di cPanel.
2. Salin `.env.production.example` menjadi `.env` di `/home/username/badminton_app/`.
3. Sesuaikan parameter koneksi database:
   ```env
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://domainanda.com
   DB_DATABASE=username_badminton
   DB_USERNAME=username_dbuser
   DB_PASSWORD=password_kuat
   SESSION_SECURE_COOKIE=true
   ```
4. Jalankan migrasi database via terminal cPanel (SSH):
   ```bash
   cd /home/username/badminton_app
   php artisan key:generate --force
   php artisan migrate --force --seed
   ```

### Langkah 4: Pengaturan Cron Job Laravel Scheduler (WAJIB)
> [!IMPORTANT]
> Sistem booking lapangan SANGAT BERGANTUNG pada scheduler Laravel untuk menjalankan tugas berkala:
> 1. `bookings:expire`: Melepaskan slot yang ditahan dan menandai booking pending_payment yang kedaluwarsa menjadi `expired`.
> 2. `bookings:notify-renewal`: Mengirimkan notifikasi in-app kepada pemilik sewa Bulanan yang jatah pertemuannya hampir habis atau terakhir hari ini.
>
> **Jika cron job tidak aktif, slot yang belum dibayar tidak akan pernah dibebaskan kembali ke jadwal publik, dan notifikasi perpanjangan tidak akan terkirim!**

Di menu **Cron Jobs** cPanel, tambahkan tugas baru yang dieksekusi **setiap menit**:
```bash
* * * * * cd /home/username/badminton_app && php artisan schedule:run >> /dev/null 2>&1
```

---

## 3. Opsi B: Deployment pada VPS (Nginx + PHP-FPM)

### Langkah 1: Kloning Repositori & Instalasi Dependensi
```bash
cd /var/www
git clone https://github.com/organisasi/badminton.git badminton
cd /var/www/badminton

# Instal dependensi PHP tanpa paket pengembangan
composer install --no-dev --optimize-autoloader

# Instal dan kompilasi aset frontend
npm ci
npm run build

# Atur hak akses direktori storage dan cache
chown -R www-data:www-data /var/www/badminton
chmod -R 775 /var/www/badminton/storage /var/www/badminton/bootstrap/cache
```

### Langkah 2: Konfigurasi Virtual Host Nginx
Buat berkas konfigurasi `/etc/nginx/sites-available/badminton.conf`:

```nginx
server {
    listen 80;
    server_name namagor.com www.namagor.com;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    server_name namagor.com www.namagor.com;
    root /var/www/badminton/public;

    index index.php index.html;
    charset utf-8;

    # Konfigurasi Sertifikat SSL (Let's Encrypt / Certbot)
    ssl_certificate /etc/letsencrypt/live/namagor.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/namagor.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;

    # Header Keamanan
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
    add_header Permissions-Policy "camera=(), microphone=(), geolocation=()" always;

    # Caching untuk Aset Statis (Vite)
    location ~* \.(ico|css|js|gif|jpeg|jpg|png|woff|woff2|ttf|svg|eot)$ {
        expires 1y;
        add_header Cache-Control "public, max-age=31536000, immutable";
        access_log off;
    }

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Aktifkan konfigurasi dan muat ulang Nginx:
```bash
ln -s /etc/nginx/sites-available/badminton.conf /etc/nginx/sites-enabled/
nginx -t && systemctl reload nginx
```

### Langkah 3: Konfigurasi Crontab Scheduler
Buka crontab user web server (`www-data`):
```bash
crontab -e -u www-data
```
Tambahkan baris scheduler berikut:
```bash
* * * * * php /var/www/badminton/artisan schedule:run >> /dev/null 2>&1
```

### Langkah 4: Optimasi Performa Produksi
Jalankan perintah cache konfigurasi, rute, dan blade template:
```bash
cd /var/www/badminton
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 4. Prosedur Backup Database

Buat skrip backup berkala `/usr/local/bin/backup-badminton.sh`:
```bash
#!/bin/bash
BACKUP_DIR="/var/backups/badminton"
DATE=$(date +'%Y%m%d_%H%M%S')
DB_NAME="db_badminton_prod"
DB_USER="badminton_user"
DB_PASS="PasswordDatabaseAnda"

mkdir -p $BACKUP_DIR
mysqldump -u $DB_USER -p$DB_PASS --single-transaction --routines --triggers $DB_NAME | gzip > "$BACKUP_DIR/backup_${DB_NAME}_$DATE.sql.gz"

# Hapus backup yang berumur lebih dari 30 hari
find $BACKUP_DIR -type f -name "*.sql.gz" -mtime +30 -delete
```

Jadwalkan eksekusi backup setiap hari pukul 02.00 WIT melalui cron:
```bash
0 2 * * * /usr/local/bin/backup-badminton.sh > /dev/null 2>&1
```

---

## 5. Prosedur Pembaruan Aplikasi (Update Deployment)

Ketika memperbarui kode aplikasi di server produksi:

```bash
cd /var/www/badminton

# 1. Aktifkan mode pemeliharaan
php artisan down --render="errors.500"

# 2. Tarik kode terbaru
git pull origin main

# 3. Instal dependensi PHP dan kompilasi aset
composer install --no-dev --optimize-autoloader
npm ci
npm run build

# 4. Jalankan migrasi database jika ada skema baru
php artisan migrate --force

# 5. Segarkan seluruh cache aplikasi
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 6. Nonaktifkan mode pemeliharaan
php artisan up
```
