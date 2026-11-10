# AntreIn — Platform Manajemen Antrean Digital Terintegrasi untuk UMKM

AntreIn adalah aplikasi web terintegrasi untuk manajemen antrean bisnis UMKM (Usaha Mikro, Kecil, dan Menengah). AntreIn menyatukan antrean pelanggan daring (*online queue*) dan antrean langsung di lokasi (*walk-in queue*) ke dalam satu antrean terpadu dengan prinsip *First-In, First-Out* (FIFO) yang adil, transparan, dan dapat dipantau secara langsung (*real-time tracking*).

---

## 🚀 Arsitektur & Teknologi

- **Backend Framework:** Laravel 12 (PHP 8.4+)
- **Database:** MySQL 8.0+ (InnoDB, strict mode, transactional engine dengan locking `lockForUpdate`)
- **Frontend / UI:** Blade Templates, Tailwind CSS v4, Alpine.js / Livewire v4
- **Visualisasi & Analitik:** Chart.js
- **Autentikasi & Autorisasi:** Laravel Fortify/Breeze hybrid, Role-Based Access Control (RBAC: `admin`, `merchant`), Multi-Tenancy Scoping
- **Pola Arsitektur:** Monolith MVC, Service Layer (`QueueService`, `ReportService`, `AnalyticsService`), Polling Adaptif HTTP (5–15 detik)

---

## 📋 Prasyarat Sistem

1. **PHP:** Versi 8.4 atau lebih baru (dengan ekstensi `pdo_mysql`, `mbstring`, `openssl`, `bcmath`, `fileinfo`, `gd`/`imagick`, `curl`).
2. **Composer:** Versi 2.x.
3. **Node.js & npm:** Node.js v20+ dan npm v10+.
4. **Database:** MySQL 8.0+ atau MariaDB 10.5+.
5. **Web Server:** Laragon / Nginx / Apache / `php artisan serve`.

---

## 🛠️ Panduan Instalasi & Konfigurasi

### 1. Kloning Repositori
```bash
git clone https://github.com/aryadigunaa/antrein.git
cd antrein
```

### 2. Instalasi Dependensi Backend & Frontend
```bash
composer install
npm install
```

### 3. Konfigurasi Lingkungan (`.env`)
Salin berkas `.env.example` ke `.env`:
```bash
cp .env.example .env
php artisan key:generate
```

Sesuaikan parameter koneksi database di `.env`:
```ini
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=antrein
DB_USERNAME=root
DB_PASSWORD=

APP_TIMEZONE=Asia/Makassar
APP_DEFAULT_BUSINESS_TIMEZONE=Asia/Makassar
SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=database
```

### 4. Migrasi Database & Seeder
Jalankan migrasi struktur database beserta data awal (kategori bisnis, template hari operasional, dan data demo):
```bash
php artisan migrate --seed
```

### 5. Tautan Penyimpanan Publik (*Storage Link*)
Buat symbolic link dari folder storage ke direktori public untuk berkas logo UMKM dan aset:
```bash
php artisan storage:link
```

### 6. Kompilasi Aset Frontend
Untuk mode pengembangan:
```bash
npm run dev
```
Untuk produksi:
```bash
npm run build
```

---

## 👤 Bootstrap Akun Administrator

Untuk lingkungan produksi atau inisialisasi awal tanpa kata sandi default hardcoded, gunakan perintah Artisan khusus AntreIn:

### Mode Interaktif:
```bash
php artisan admin:create
```
Perintah akan meminta nama, email, dan kata sandi admin secara aman (kata sandi tidak ditampilkan pada layar).

### Mode Non-Interaktif / CI / Script:
```bash
php artisan admin:create --name="Admin AntreIn" --email="admin@antrein.id" --password="SuperSecretPassword123"
```

---

## 🧪 Menjalankan Pengujian Otomatis (*Test Suite*)

AntreIn dilengkapi dengan pengujian menyeluruh (Unit & Feature Tests) yang mencakup keamanan, isolasi multi-tenant, antrean terpadu FIFO, dan konkurensi:

```bash
php artisan test
```

Menjalankan pengujian spesifik keamanan:
```bash
php artisan test --filter=Security
```

---

## 🔒 Fitur Keamanan & Perlindungan Data

1. **Isolasi Multi-Tenant (*Cross-Tenant Isolation*):** Setiap merchant hanya dapat mengakses, memanggil, dan memodifikasi antrean, layanan, dan laporan milik bisnisnya sendiri (`user_id` / `business_id` scoping). Akses silang dicegah dengan HTTP 403 Forbidden.
2. **Perlindungan Enumerasi Akun:** Endpoint reset kata sandi (`/forgot-password`) memberikan pesan generik yang sama baik untuk email terdaftar maupun tidak terdaftar.
3. **Pembatasan Laju Akses (*Rate Limiting*):**
   - Login: 5 percobaan gagal per IP/Email.
   - Permintaan Reset Password: 5 permintaan per 15 menit per IP.
   - Pendaftaran Antrean Online: 30 pendaftaran per 15 menit per IP.
   - Polling Pelacakan Antrean: 60 permintaan per menit per IP (`Retry-After: 60`).
4. **Validasi Berkas Ketat (*SVG & File Upload Sanitization*):** Validasi tipe MIME, ukuran maksimum (2 MB), serta inspeksi regex terhadap berkas SVG untuk memblokir tag `<script>`, `<iframe>`, `<foreignObject>`, dan event handler `on*`.
5. **Token Pelacakan Kriptografis:** Token pelacakan antrean dibuat acak 64 karakter heksadesimal (`bin2hex(random_bytes(32))`) yang aman dan otomatis kedaluwarsa setelah tiket berstatus terminal (`completed`, `cancelled`, `skipped`) lewat dari 24 jam.
6. **Perlindungan Ekspor CSV & Formula Injection:** Seluruh nilai pada ekspor CSV yang diawali karakter trigger formula (`=`, `+`, `-`, `@`, `\t`, `\r`) diproteksi dengan sanitasi prefix petik tunggal (`'`) untuk memitigasi eksekusi formula jahat (*CSV Formula Injection*). Berkas CSV dilengkapi UTF-8 BOM untuk kompatibilitas otomatis Microsoft Excel.

---

## 📊 Ekspor Laporan Operasional (CSV & PDF)

Merchant dapat mengunduh rekaman kinerja operasional antrean dalam dua format:
- **Spreadsheet CSV:** Streaming download data terstruktur (metadata eksekutif, rekapitulasi status, breakdown layanan, dan rincian tiket antrean).
- **Dokumen PDF:** Layout siap cetak standar A4 Portrait berdesain AntreIn, memuat ringkasan KPI eksekutif, analisis jam sibuk (OQ-15), performa layanan, serta daftar tiket pada periode terpilih.

Tautan unduh dapat diakses langsung dari dashboard laporan merchant (`/umkm/reports`).

---

## 👥 Manajemen Multi-Staf & Otorisasi Peran (Multi-Staff & RBAC)

AntreIn mendukung kolaborasi tim operasional UMKM melalui sistem keanggotaan bisnis berjenjang:
1. **Pemilik Usaha (Owner):** Memiliki kontrol penuh atas profil usaha, jam operasional, manajemen layanan, pengaturan akun, unduh laporan & ekspor (CSV/PDF), serta mengundang atau mengelola akses staf. Akun pemilik utama diproteksi dari penonaktifan atau penghapusan mandiri.
2. **Staf Operasional (Operator):** Diberikan akses khusus ke konsol antrean untuk melayani pelanggan: mengambil nomor antrean walk-in, memanggil antrean berikutnya, memanggil ulang, memulai layanan, dan menyelesaikan tiket. Staf operator dilarang mengakses halaman pengaturan, laporan, ekspor, manajemen staf, atau membatalkan tiket antrean.
3. **Alur Undangan Kriptografis (Tokenized Invitation):** Pemilik dapat membuat tautan undangan staf berdurasi 72 jam melalui menu `/umkm/staff`. Calon staf dapat langsung bergabung atau mendaftar via `/invite/{token}`.
4. **Aturan Satu Akun Satu Usaha:** Setiap akun staf hanya dapat terhubung ke satu unit usaha pada lingkup rilis ini demi menjaga isolasi data operasional.

---

## 🏢 Manajemen Multi-Loket & Penugasan Antrean (Multi-Counter & Queue Assignment)

AntreIn mendukung pelayanan antrean multi-loket secara paralel untuk UMKM dengan banyak meja pelayanan (barbershop multi-kursi, klinik multi-dokter, konter pembayaran/layanan):
1. **Antrean Terpadu FIFO Global:** Seluruh tiket (online & walk-in) tetap masuk ke dalam satu antrean harian terpadu (`DailyQueue`). Antrean tidak dipecah per loket sehingga keadilan dan urutan kedatangan pelanggan tetap terjaga mutlak.
2. **Pelayanan Paralel Antar-Loket:** Konkurensi aktif berlaku per loket (`counter_id`). Banyak loket dapat memanggil dan melayani tiket secara bersamaan tanpa saling mengunci, tetapi satu loket hanya dapat memiliki maksimal satu tiket aktif (`dipanggil` atau `sedang_dilayani`).
3. **Manajemen Loket & Penugasan Operator:** Pemilik usaha dapat membuat, mengubah, mengaktifkan/menonaktifkan, dan menugaskan operator ke loket melalui menu `/umkm/counters`. Owner dapat mengoperasikan seluruh loket aktif, sedangkan operator hanya dapat mengoperasikan loket yang ditugaskan kepada mereka.
4. **Perlindungan Integritas Operasional:** Loket tidak dapat dinonaktifkan atau dihapus jika sedang melayani antrean aktif atau jika merupakan satu-satunya loket aktif di unit usaha.
5. **Transparansi Pemanggilan Pelanggan:** Halaman pelacakan publik pelanggan menampilkan loket tujuan pemanggilan (misal: "Loket 2") secara real-time tanpa mengekspos identifier database internal.

---

## ⚡ Pembaruan Real-Time dengan Laravel Reverb & WebSocket (P1-04)

AntreIn mengintegrasikan WebSocket server berkinerja tinggi menggunakan **Laravel Reverb** untuk pembaruan status antrean seketika:

1. **Sinkronisasi Instan:**
   - **Pelanggan:** Status tiket (`dipanggil`, `sedang_dilayani`, `selesai`, `dilewati`) dan penugasan loket terbarui seketika tanpa jeda polling.
   - **Meja Operasional & Dashboard UMKM:** Notifikasi toast real-time saat ada tiket baru mendaftar atau status loket berubah.
2. **Arsitektur Event Transaksional (`ShouldDispatchAfterCommit`):** Seluruh broadcast event hanya dikirim setelah transaksi database MySQL selesai di-commit (`ACID`), mencegah sinyal palsu akibat transaksi yang di-rollback.
3. **Keamanan & Privasi Data:**
   - Channel privat staf (`private-business.{businessId}` dan `private-counter.{counterId}`) diproteksi via otorisasi saluran Laravel.
   - Channel pelacakan tiket tamu (`private-ticket.{tokenHash}`) diotorisasi via endpoint khusus `POST /broadcasting/auth/ticket` menggunakan validasi hash token (SHA-256) dan rate-limiting ketat (60 req/menit/IP).
   - Payload broadcast bebas dari data sensitif pelanggan (nomor HP dan token rahasia tidak pernah disiarkan).
4. **Ketahanan Koneksi (*Resilience & Adaptive Fallback*):** Jika koneksi WebSocket terputus, sistem secara otomatis beralih ke *adaptive HTTP polling* (5–15 detik) sehingga pelanggan dan merchant tidak pernah kehilangan pembaruan antrean.

### Menjalankan Server WebSocket Reverb:
Untuk mode pengembangan atau lokal:
```bash
php artisan reverb:start
```
Secara default Reverb berjalan pada port `8080` (dapat dikonfigurasi melalui variabel `REVERB_PORT` di `.env`).

---

## 💾 Prosedur Backup & Restore Database

### 1. Pencadangan (*Backup*) Harian Database MySQL
Gunakan utilitas `mysqldump`:
```bash
# Backup struktur dan data
mysqldump -u [username] -p[password] --single-transaction --quick --routines antrein > antrein_backup_$(date +\%Y\%m\%d_\%H\%M\%S).sql

# Kompresi cadangan
gzip antrein_backup_*.sql
```

### 2. Pencadangan Berkas Media / Storage
```bash
tar -czvf storage_backup_$(date +\%Y\%m\%d).tar.gz storage/app/public
```

### 3. Pemulihan (*Restore*) Database
```bash
# Uncompress jika dalam bentuk gzip
gunzip antrein_backup_YYYYMMDD_HHMMSS.sql.gz

# Restore ke database baru atau database target
mysql -u [username] -p[password] antrein < antrein_backup_YYYYMMDD_HHMMSS.sql
```

---

## 📜 Lisensi & Hak Cipta

AntreIn dikembangkan untuk mendukung digitalisasi UMKM Indonesia di bawah lisensi [MIT License](LICENSE).
Hak Cipta © 2026 AntreIn Project.
