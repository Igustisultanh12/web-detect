# WebGuard Investigasi & Reporting Platform
### Platform Terpusat Investigasi Siber, Analisis Website Pasif & Evidence Management

![WebGuard Shield](https://img.shields.io/badge/Security-SSRF%20Protected-blue?style=for-the-badge&logo=shield)
![Laravel](https://img.shields.io/badge/Laravel-11.x-red?style=for-the-badge&logo=laravel)
![Vue 3](https://img.shields.io/badge/Vue.js-3.5%20%2B%20TypeScript-emerald?style=for-the-badge&logo=vuedotjs)
![Tailwind](https://img.shields.io/badge/Tailwind-CSS%20v4-sky?style=for-the-badge&logo=tailwindcss)
![License](https://img.shields.io/badge/License-Proprietary%20Dinas-darkblue?style=for-the-badge)

---

## 1. Ikhtisar Platform (Overview)

**WebGuard Investigasi** adalah platform web dinas profesional berstandar institusi untuk melakukan analisis teknis **pasif, non-destruktif, dan berorientasi forensik** terhadap website atau domain yang diduga terlibat dalam aktivitas ilegal (phishing, penipuan, malware distribution, judi online, atau pelanggaran siber lainnya).

### Prinsip Utama Operasional:
1. **Strictly Passive Analysis**: Tidak pernah menjalankan serangan aktif, penetrasi, SQL injection, bypass WAF/CAPTCHA, DoS/DDoS, atau eksploitasi kerentanan.
2. **Defensive & Forensic Readiness**: Seluruh barang bukti teknis diberi timestamp kriptografis, hash **SHA-256 mutlak**, dan rantai pemeliharaan (*Chain of Custody*).
3. **SSRF Hardened**: Dilengkapi pertahanan perimeter berlapis untuk memblokir perutean ke jaringan internal, loopback (`127.0.0.1`, `localhost`), link-local cloud metadata (`169.254.169.254`), dan pemalsuan resolusi DNS.
4. **Desain Antarmuka Terintegrasi**: Mengadaptasi standar UI kepegawaian profesional (Sisfopers KC) dengan palet latar slate (`#F8FAFC`), kartu putih bersudut halus (`rounded-2xl`), aksen biru dinas (`#2563EB`), serta tata letak navigasi yang ergonomis.

---

## 2. Fitur Unggulan (Core Modules)

### A. Modul Passive Website Analysis (15 Tab Komprehensif)
* **Ikhtisar Kasus**: Status analisis real-time, tingkat risiko agregat, timeline investigasi, dan tombol aksi cepat.
* **Domain & WHOIS/RDAP**: Analisis registrant, registrar, nama server (NS), status domain EPP, dan masa berlaku domain.
* **DNS Intelligence**: Resolusi pasif record A, AAAA, MX, TXT (SPF/DMARC), CNAME, SOA, CAA, dan NS.
* **IP Server & Geolocation**: Identifikasi IP publik, reverse PTR, estimasi lokasi server dengan **peta Leaflet interaktif**, dan disclaimer ketidakakuratan geolokasi IP.
* **ASN & ISP**: Nama Autonomous System, nomor AS, organisasi ISP, rute BGP, dan estimasi tipe infrastruktur.
* **CDN & Proxy Detection**: Deteksi Cloudflare, Akamai, Cloudfront, Fastly dengan disclaimer alamat IP edge proxy.
* **SSL / TLS Certificate**: Analisis Subject, Issuer (CA), validitas waktu, SAN, serial number, cipher suite, dan fingerprint SHA-256.
* **HTTP/HTTPS Headers & Security Posture**: Evaluasi HSTS, CSP, X-Frame-Options, X-Content-Type-Options, Referrer-Policy, dan server header.
* **Technology Detection**: Analisis fingerprint CMS (WordPress, Joomla), Web Server (Nginx, Apache), reverse proxy, dan library front-end.
* **Passive Subdomain Discovery**: Penemuan subdomain pasif dari Certificate Transparency Logs (crt.sh) dan catatan DNS publik tanpa bruteforce.
* **Reputation & Threat Intel**: Agregasi status blacklist, skor ancaman, dan riwayat pelaporan malicious domain.
* **Automated Screenshot**: Tangkapan layar pasif halaman web target untuk dokumentasi visual barang bukti.
* **Evidence Vault**: Penyimpanan arsip bukti digital dengan validasi integritas SHA-256 instan dan log revisi catatan.
* **Investigation Timeline**: Rekaman sekuensial seluruh tahapan analisis dari inisiasi hingga kesimpulan kasus.
* **Report Generator**: Penerbitan laporan resmi otomatis (PDF, CSV, JSON) dengan format penulisan siap sidang penegak hukum.

### B. Modul Manajemen Personel & Keamanan (RBAC)
* **Struktur Organisasi**: Hirarki Pangkat, Korps, Satuan Kerja, dan Jabatan Kedinasan.
* **Privasi Identitas**: Penyimpanan KTP dan KTA di private disk terisolasi dengan watermark dinamis otomatis pengakses.
* **Autentikasi Multi-Faktor (2FA)**: Standar TOTP (Google Authenticator / Aegis) dengan 10 emergency recovery codes.
* **Audit Trail**: Pencatatan riwayat setiap aksi pengguna, login, unduh dokumen, dan perubahan data.

---

## 3. Arsitektur Teknologi

* **Backend**: Laravel 11.x (PHP 8.2 / 8.3)
* **Frontend**: Vue 3.5 (Composition API) + TypeScript + Tailwind CSS v4 + Pinia + Vue Router
* **Build Tool**: Vite 8.x + Rollup
* **Queue & Job Management**: Laravel Horizon + Redis
* **Database**: MySQL 8.0 / MariaDB 10.11
* **Caching & Session**: Redis 7.x
* **PDF Generator**: DomPDF (Barryvdh Laravel DomPDF)
* **Web Server**: Nginx (Reverse Proxy & Static Asset Caching)
* **Target Deployment**: Linux (Ubuntu 22.04/24.04 LTS atau Debian 12) & aaPanel

---

## 4. Panduan Deployment Produksi di aaPanel (26 Langkah Lengkap)

Ikuti 26 panduan langkah demi langkah berikut untuk mendistribusikan WebGuard Investigasi secara production-ready pada server Ubuntu/Debian yang menggunakan aaPanel:

### Tahap I: Persiapan Server & Instalasi aaPanel
1. **Update & Upgrade Server OS**:
   ```bash
   sudo apt update && sudo apt upgrade -y
   sudo apt install -y curl wget git unzip zip htop ufw fail2ban
   ```
2. **Instalasi aaPanel Standar**:
   Jalankan script instalasi resmi aaPanel:
   ```bash
   URL=https://www.aapanel.com/script/install_7.0_en.sh && if [ -f /usr/bin/curl ];then curl -ksSO "$URL" ;else wget --no-check-certificate -O install_7.0_en.sh "$URL" ;fi;bash install_7.0_en.sh aapanel
   ```
3. **Buka Dashboard aaPanel**:
   Akses URL admin panel yang tertera di terminal, login, dan pilih instalasi **LNMP Stack**:
   * Nginx 1.24+
   * MySQL 8.0 atau MariaDB 10.11
   * PHP 8.2 atau PHP 8.3
   * Pure-FTPd (Opsional)
   * phpMyAdmin 5.2+

### Tahap II: Konfigurasi PHP & Redis
4. **Instalasi Redis Server**:
   Masuk ke menu **App Store** di aaPanel, cari **Redis**, dan klik **Install**.
5. **Instalasi Ekstensi PHP Wajib**:
   Masuk ke **App Store** -> **PHP 8.3** -> **Install Extensions**:
   * `fileinfo` (Wajib untuk deteksi MIME file KTP/KTA)
   * `redis` (Wajib untuk queue & caching cepat)
   * `opcache` (Optimasi performa PHP bytecode)
   * `exif` (Ekstraksi metadata gambar/screenshot)
   * `intl` (Internasionalisasi dan format tanggal)
6. **Konfigurasi `php.ini`**:
   Buka tab **Configuration** pada PHP 8.3 di aaPanel:
   ```ini
   max_execution_time = 300
   max_input_time = 300
   memory_limit = 512M
   post_max_size = 64M
   upload_max_filesize = 64M
   date.timezone = Asia/Jakarta
   ```
7. **Pengaturan Fungsi PHP (Disabled Functions)**:
   Pada tab **Disabled functions**, izinkan fungsi `pcntl_signal`, `pcntl_alarm`, `pcntl_fork`, `posix_kill` jika Horizon dijalankan langsung melalui CLI PHP.

### Tahap III: Pembuatan Situs & Konfigurasi Nginx
8. **Tambah Situs di aaPanel**:
   Buka menu **Website** -> **Add site**:
   * Domain: `webguard.domainanda.com`
   * Root directory: `/www/wwwroot/web-detect`
   * FTP: No
   * Database: MySQL (buat database sekaligus, misal: `webguard_db`)
   * PHP Version: PHP-83
9. **Pasang Sertifikat SSL (Let's Encrypt)**:
   Klik domain Anda -> menu **SSL** -> pilih **Let's Encrypt** -> Centang domain -> Klik **Apply**. Aktifkan **Force HTTPS**.
10. **Atur Site Directory / Running Directory**:
    Klik domain -> menu **Site directory** -> ganti **Running directory** dari `/` menjadi `/public` -> Klik **Save**.
11. **Konfigurasi URL Rewrite (SPA & Laravel Routing)**:
    Klik menu **URL rewrite**, pilih template **Laravel 5**, atau tempel konfigurasi berikut:
    ```nginx
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    ```
12. **Konfigurasi Security Headers di Nginx Config**:
    Buka tab **Config** pada Nginx situs Anda dan tambahkan parameter keamanan berikut di dalam blok `server { ... }`:
    ```nginx
    add_header X-Frame-Options "DENY" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
    
    # Blokir akses ke file sensitif (.env, .git)
    location ~ /\.(?!well-known).* {
        deny all;
    }
    ```

### Tahap IV: Deployment Source Code & Dependensi
13. **Clone Repository GitHub**:
    Buka terminal server via SSH atau web terminal aaPanel:
    ```bash
    cd /www/wwwroot
    git clone https://github.com/Igustisultanh12/web-detect.git
    cd /www/wwwroot/web-detect
    ```
14. **Atur Hak Akses & Ownership File**:
    ```bash
    chown -R www:www /www/wwwroot/web-detect
    chmod -R 775 /www/wwwroot/web-detect/storage
    chmod -R 775 /www/wwwroot/web-detect/bootstrap/cache
    ```
15. **Konfigurasi Environment File (`.env`)**:
    Salin file contoh konfigurasi:
    ```bash
    cp .env.example .env
    nano .env
    ```
    Sesuaikan kredensial MySQL, Redis, dan URL aplikasi Anda:
    ```env
    APP_NAME="WebGuard Investigasi"
    APP_ENV=production
    APP_DEBUG=false
    APP_URL=https://webguard.domainanda.com

    DB_CONNECTION=mysql
    DB_HOST=127.0.0.1
    DB_PORT=3306
    DB_DATABASE=webguard_db
    DB_USERNAME=webguard_user
    DB_PASSWORD=password_database_anda

    CACHE_STORE=redis
    QUEUE_CONNECTION=redis
    SESSION_DRIVER=redis
    ```
16. **Instalasi Dependensi PHP (Composer)**:
    ```bash
    composer install --no-dev --optimize-autoloader
    ```
17. **Generate Application Key**:
    ```bash
    php artisan key:generate
    ```
18. **Migrasi Database & Master Seeding**:
    ```bash
    php artisan migrate --force --seed
    ```
19. **Build Frontend Assets (Vue 3 + Tailwind + Vite)**:
    Jika Node.js sudah terpasang di server:
    ```bash
    npm ci
    npm run build
    ```
20. **Membuat Symlink Storage**:
    ```bash
    php artisan storage:link
    ```

### Tahap V: Automasi Antrean, Cron Job & Supervisor
21. **Instalasi Supervisor Manager di aaPanel**:
    Masuk ke **App Store** -> cari **Supervisor** -> Klik **Install**.
22. **Tambah Daemon Horizon di Supervisor**:
    Buka **Supervisor Manager** -> Klik **Add Daemon**:
    * Name: `webguard-horizon`
    * Run User: `www`
    * Run Directory: `/www/wwwroot/web-detect`
    * Command: `/usr/bin/php /www/wwwroot/web-detect/artisan horizon`
    * Number of processes: `1`
    Klik **Confirm** dan pastikan status service menunjukkan **Running**.
23. **Setup Laravel Scheduler di Cron Job aaPanel**:
    Buka menu **Cron** di aaPanel -> **Add Cron Job**:
    * Type of Task: `Shell Script`
    * Name of Task: `Laravel Scheduler - WebGuard`
    * Period: `N Minutes` -> `1 Minute`
    * Script content:
      ```bash
      cd /www/wwwroot/web-detect && php artisan schedule:run >> /dev/null 2>&1
      ```
    Klik **Add Task**.

### Tahap VI: Pengujian Akun & Operasional Dinas
24. **Akun Akses Bawaan (Default Seeded Credentials)**:
    Setelah `DatabaseSeeder` berjalan, gunakan akun superadmin bawaan:
    * **Email**: `superadmin@webguard.mil.id`
    * **Password**: `Investigator#2026!`
    * **Peran**: `Super Administrator (Panglima / Kepala Siber)`
    * *Catatan Penting*: Segera ubah password dan aktifkan 2FA setelah login pertama!
25. **Perintah Artisan Khusus Administrasi**:
    * Membuat Super Admin baru secara interaktif:
      ```bash
      php artisan webguard:create-admin
      ```
    * Audit postur keamanan sistem secara berkala:
      ```bash
      php artisan webguard:security-check
      ```
26. **Prosedur Backup Rutin**:
    * Di menu **Cron** aaPanel, tambahkan backup berkala untuk **Database MySQL** (`webguard_db`) setiap hari pukul 02:00 WIB.
    * Tambahkan backup folder privat `/www/wwwroot/web-detect/storage/app/private` untuk mengamankan berkas KTP/KTA dan bukti digital forensik.

---

## 5. Menjalankan Melalui Docker Compose (Alternatif)

Bagi lingkungan server yang mengutamakan kontainerisasi:
```bash
# 1. Salin environment
cp .env.example .env

# 2. Jalankan container
docker compose up -d --build

# 3. Jalankan migrasi dan seeder di dalam container
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --force --seed
docker compose exec app php artisan storage:link
```
Akses platform di `http://localhost:8080`.

---

## 6. Verifikasi & Pengujian Otomatis

WebGuard Investigasi memiliki suite pengujian komprehensif:
```bash
php artisan test
```
* **SsrfProtectionTest**: Verifikasi blokade IP loopback, jaringan privat RFC 1918, endpoint AWS/GCP cloud metadata (`169.254.169.254`), skema URL ilegal, dan mitigasi representasi IP heksadesimal/desimal.
* **PersonnelDocumentSecurityTest**: Verifikasi isolasi disk privat untuk dokumen KTP/KTA, otorisasi RBAC (403 Forbidden bagi non-otoritas), dan validasi integritas hash SHA-256.
* **InvestigationWorkflowTest**: Verifikasi format otomatis kode investigasi (`WG-YYYY-XXXXXX`), antrean queue pasif, dan hashing kriptografis bukti digital forensik.

---

## 7. Catatan Legalitas & Etika Penggunaan

Aplikasi ini ditujukan secara eksklusif untuk aparat penegak hukum, analis keamanan siber resmi, dan auditor sistem informasi dalam kerangka investigasi pasif legal. Platform ini tidak menyediakan sarana ofensif, tidak melakukan intrusi jaringan, dan senantiasa mencantumkan disclaimer forensik pada setiap estimasi geolokasi maupun identitas edge server.
