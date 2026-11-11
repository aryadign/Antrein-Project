# AntreIn — Platform Manajemen Antrean Digital Terintegrasi untuk UMKM

AntreIn adalah aplikasi web terintegrasi untuk manajemen antrean bisnis UMKM (Usaha Mikro, Kecil, dan Menengah). AntreIn menyatukan antrean pelanggan daring (*online queue*) dan antrean langsung di lokasi (*walk-in queue*) ke dalam satu antrean terpadu dengan prinsip *First-In, First-Out* (FIFO) yang adil, transparan, dan dapat dipantau secara langsung (*real-time tracking*).

---

## 🚀 Arsitektur & Teknologi

- **Backend Framework:** Laravel 13 (PHP 8.3+)
- **Database:** MySQL 8.0+ (InnoDB, strict mode, transactional engine dengan locking `lockForUpdate`)
- **Frontend / UI:** Blade Templates, Tailwind CSS v4, Alpine.js / Livewire v4
- **Visualisasi & Analitik:** Chart.js
- **Autentikasi & Autorisasi:** Laravel Fortify/Breeze hybrid, Role-Based Access Control (RBAC: `admin`, `merchant`), Multi-Tenancy Scoping
- **Pola Arsitektur:** Monolith MVC, Service Layer (`QueueService`, `ReportService`, `AnalyticsService`), Polling Adaptif HTTP (5–15 detik)

---

## 📋 Prasyarat Sistem

1. **PHP:** Versi 8.3 atau lebih baru (dengan ekstensi `pdo_mysql`, `mbstring`, `openssl`, `bcmath`, `fileinfo`, `gd`/`imagick`, `curl`).
2. **Composer:** Versi 2.x.
3. **Node.js & npm:** Node.js v20+ dan npm v10+.
4. **Database:** MySQL 8.0+ atau MariaDB 10.5+.
5. **Web Server:** Laragon / Nginx / Apache / `php artisan serve`.

---

## 🛠️ Panduan Instalasi & Konfigurasi

### 1. Kloning Repositori
```bash
git clone https://github.com/aryadign/Antrein-Project.git
cd Antrein-Project
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
Untuk mode pengembangan (terminal terpisah):
```bash
npm run dev
```
Untuk produksi:
```bash
npm run build
```

### 7. Menjalankan Server Pengembangan Laravel
Jalankan server aplikasi lokal (default `http://127.0.0.1:8000`):
```bash
php artisan serve
```
Atau jalankan skrip pengembangan terpadu via Composer:
```bash
composer run dev
```

### 8. Akun Demo Development (`local` / `testing`)
Ketika `php artisan migrate --seed` dijalankan di luar environment `production`, `AdminUserSeeder` dan `DevelopmentMerchantSeeder` menyiapkan akun demo berikut (yang juga dapat diakses instan melalui panel **Akses Cepat Pengujian** di `/login` dan *role switcher* pada mode lokal):
- **Admin AntreIn:** `admin@antrein.id` / `AdminAntreIn#2026` (dapat dikustomisasi lewat `INITIAL_ADMIN_EMAIL` / `INITIAL_ADMIN_PASSWORD` di `.env`)
- **Owner UMKM (Barbershop Garasi):** `owner@garasi.id` / `merchant12345`
- **Operator Loket 1:** `operator@garasi.id` / `operator12345`
- **Pelanggan (Tamu):** Akses publik tanpa login melalui `/q/barbershop-garasi-demo`

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

> **Catatan — Pengujian Integrasi MySQL (Opt-in):** Tes integrasi MySQL (konkurensi InnoDB dan integritas referensial) hanya berjalan ketika variabel `DB_TEST_HOST` / `DB_TEST_PORT` / `DB_TEST_DATABASE` / `DB_TEST_USERNAME` / `DB_TEST_PASSWORD` dikonfigurasi pada environment ke database khusus pengujian (nama database wajib mengandung kata `test`). Tanpa konfigurasi tersebut, tes ini dilewati otomatis dengan alasan eksplisit dan tidak pernah secara implisit diarahkan ke database aplikasi maupun produksi:
> ```bash
> # Contoh: menjalankan suite integrasi MySQL secara eksplisit
> DB_TEST_DATABASE=antrein_test php artisan test tests/Feature/Database
> ```

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
7. **Anonimisasi PII Pelanggan (ADR-13):** Tiket antrean berstatus terminal yang lebih tua dari jendela retensi `QUEUE_PII_RETENTION_DAYS` (default 30 hari) dianonimisasi secara otomatis setiap hari: nama pelanggan diganti placeholder (`Pelanggan #A-012`), nomor telepon dimaskirifikasi irreversibel (`0812****0000`), dan catatan operator dihapus permanen (`NULL`). Nomor tiket, stempel waktu, relasi, dan metrik analitik tetap dipertahankan untuk pelaporan. Operasi bersifat idempoten, di-chunk, dan transaksional. Dapat dijalankan manual melalui `php artisan queues:anonymize-pii` (tersedia opsi `--dry-run`).

### 🕐 Penjadwalan Tugas Anonimisasi PII

Tugas `queues:anonymize-pii` terdaftar pada scheduler Laravel (berjalan sekali per hari dengan proteksi `withoutOverlapping()`). Deployment produksi memerlukan *scheduler runner* aktif — misalnya entri cron per menit yang menjalankan `php artisan schedule:run`, atau proses panjang `php artisan schedule:work` — agar jadwal dieksekusi. Tanpa *scheduler runner*, anonimisasi tidak berjalan otomatis dan harus dipicu manual.

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
Secara default Reverb berjalan pada port `8080` (dapat dikonfigurasi melalui variabel `REVERB_PORT` di `.env`). Origin WebSocket yang diizinkan dikendalikan oleh variabel `REVERB_ALLOWED_ORIGINS` (daftar dipisah koma): lingkungan pengembangan lokal dapat memakai `*`, sedangkan produksi wajib mendefinisikan origin eksplisit — wildcard ditolak secara fail-fast saat bootstrap aplikasi.

---

## 🔔 Notifikasi Email & Browser Web Push (P1-05)

AntreIn mendukung kanal notifikasi multi-tier yang menjamin pelanggan dan staf UMKM tetap mendapatkan pembaruan antrean meskipun peramban atau layar HP diminimalkan/terkunci:

1. **Hierarki Kanal Notifikasi Terpadu:**
   - **WebSocket (Reverb):** Akselerator real-time instan saat antarmuka aktif di latar depan (*foreground*).
   - **Web Push Notifications (W3C Push API / Service Worker):** Peringatan visual dan nada dering langsung di layar komputer atau HP saat tab peramban diminimalkan atau ditutup.
   - **Email Transaksional:** Rekaman status permanen dan kanal konfirmasi terpercaya bagi pelanggan serta staf usaha.
2. **Persetujuan Eksplisit & Privasi Pelanggan (GDPR / UU PDP):**
   - Penerimaan email pelanggan bersifat opsional (*opt-in* eksplisit). Pelanggan harus mencentang persetujuan penerimaan notifikasi saat mendaftar tiket online (`notify_email = 1`).
   - Alamat email pelanggan tidak pernah ditampilkan di publik, tidak dibocorkan di API pelacakan tiket, dan tidak diekspor ke laporan umum.
3. **Standar Kriptografi VAPID (RFC 8292):**
   - Menggunakan pasangan kunci kurva eliptik ECDSA P-256 terstandarisasi.
   - Kredensial push browser (`public_key` dan `auth_token`) disimpan dalam database dengan enkripsi native Laravel (`encrypted` casts).
   - *Auto-pruning:* Endpoint langganan browser yang kedaluwarsa atau dinonaktifkan (HTTP 404/410) otomatis dibersihkan dari basis data. Langganan tiket tamu otomatis dihapus saat tiket mencapai status terminal (`selesai`, `dilewati`, `dibatalkan`).
4. **Preferensi Staf & Isolasi Tenant:**
   - Pemilik dan operator UMKM dapat mengatur preferensi notifikasi secara mandiri melalui menu **Pengaturan Usaha → Notifikasi** (`/umkm/settings?tab=notifications`).
   - Notifikasi staf diproteksi isolasi multi-tenant; staf hanya menerima notifikasi tiket dari unit usahanya sendiri.
   - Tiket *walk-in* tidak memicu notifikasi staf untuk mencegah banjir notifikasi di kasir.

### Pembuatan Kunci VAPID:
Untuk menginisialisasi atau memperbarui pasangan kunci VAPID:
```bash
php artisan webpush:vapid
```
Perintah mencetak pasangan kunci baru; salin nilainya ke variabel `VAPID_PUBLIC_KEY` dan `VAPID_PRIVATE_KEY` pada berkas `.env` Anda. Tidak ada kunci fallback yang di-hardcode pada source — tanpa kunci yang terkonfigurasi, fitur notifikasi peramban dinonaktifkan secara aman (halaman dan notifikasi email tetap berfungsi normal), sedangkan environment produksi menolak pengiriman push secara fail-fast sampai kunci diatur.

---

## 📺 Layar Antrean Publik / TV Queue Monitor (P1-08)

AntreIn menyediakan antarmuka khusus layar monitor TV ruang tunggu yang dapat dipasang pada Smart TV, monitor dinding, atau layar pelanggan tanpa memerlukan proses login:

1. **Desain Layar Lebar & Anti-Silau (TV Optimized):**
   - Tampilan kontras tinggi bertema gelap (`bg-slate-950`) dengan tipografi ekstra besar (`text-8xl tabular-nums`) yang mudah dibaca dari jarak jauh.
   - Dilengkapi jam digital presisi sesuai zona waktu unit usaha (WITA/WIB/WIT), branding usaha, dan status koneksi live.
   - Mendukung **Mode Layar Penuh (Fullscreen API)** dengan satu klik tombol interaktif.
2. **Tampilan Multi-Loket Simultan:**
   - Menampilkan seluruh loket aktif secara berdampingan dalam grid adaptif (1, 2, 3, hingga 4+ loket).
   - Memperlihatkan nomor antrean yang sedang **Dipanggil** (dengan animasi badge amber berpendar) atau **Sedang Dilayani** (badge emerald).
   - Loket tanpa antrean aktif menampilkan status *Standby* bersih tanpa merusak tata letak loket lain.
3. **Audio Chime Synthesizer Bawaan (Web Audio API):**
   - Menghasilkan nada dering lonceng dua nada ganda (C5 - G5) secara sintesis native via peramban (`AudioContext`).
   - Berfungsi 100% offline tanpa membutuhkan berkas audio MP3 eksternal, bebas masalah CORS atau kegagalan jaringan.
   - Berbunyi otomatis saat ada panggilan nomor baru di loket, dilengkapi kontrol mute/unmute.
4. **Pembaruan Real-Time & Ketahanan Jaringan:**
   - Terhubung seketika via Laravel Reverb pada channel publik berbasis hash token (`display.{tokenHash}`).
   - *Fallback Polling:* Memperbarui data otomatis setiap 10 detik apabila koneksi WebSocket terputus.
5. **Kredensial Kriptografis & Keamanan Privasi (Zero PII):**
   - Tautan publik diproteksi token acak 48 karakter berentropi tinggi (`display_token_hash` SHA-256).
   - Pemilik usaha (*Owner*) dapat mengaktifkan, menyalin, melihat pratinjau live data, merotasi (*regenerate*), atau mencabut (*revoke*) tautan display kapan saja melalui menu **Layar TV** (`/umkm/display`).
   - Endpoint read-only (`GET /display/{token}/data`) diproteksi `Cache-Control: no-store` dan menjamin **Zero PII** (nama, nomor telepon, email, dan token privat pelanggan tidak pernah dikirimkan).

---

## ⭐ Ulasan & Masukan Pelanggan / Customer Reviews (P1-09)

AntreIn menyediakan modul umpan balik dan penilaian kepuasan pelanggan yang terintegrasi langsung pada siklus hidup antrean:

1. **Pengambilan Ulasan Setelah Layanan Selesai:**
   - Formulir ulasan muncul secara otomatis pada halaman pelacakan pelanggan (`/q/track/{token}`) segera setelah tiket berstatus **Selesai** (`STATUS_SELESAI`).
   - Pelanggan yang sedang menunggu, dipanggil, dilayani, dilewati, atau dibatalkan dilarang mengirimkan ulasan (validasi HTTP 422).
2. **Penilaian Bintang & Masukan Teks Interaktif:**
   - Selektor interaktif 1 hingga 5 bintang dengan animasi sentuh/klik berbasis Alpine.js.
   - Kolom masukan teks (hingga 1.000 karakter) dengan penghitung karakter *real-time*.
   - Proteksi keamanan: Teks komentar dibersihkan secara otomatis dari tag HTML berbahaya (*Anti-XSS Sanitization* via `strip_tags`).
3. **Idempotensi & Proteksi Duplikasi:**
   - Satu tiket antrean hanya dapat diulas satu kali (`unique` constraint pada basis data).
   - Setelah ulasan tersimpan, halaman pelacakan menampilkan rincian bintang, komentar yang telah dikirim, dan pesan apresiasi terima kasih.
4. **Analisis & Pemantauan Ulasan Merchant:**
   - Dasbor ulasan terisolasi multi-tenant pada menu **Ulasan** (`/umkm/reviews`), dapat diakses oleh Pemilik (*Owner*) dan Operator.
   - Ringkasan metrik analitik: Rata-rata Rating, Total Ulasan, Tiket Selesai, dan Tingkat Respon Ulasan.
   - Grafik distribusi bintang (5, 4, 3, 2, 1 bintang) dengan persentase dan jumlah ulasan.
   - Filter interaktif berdasarkan penilaian bintang, jenis layanan, serta pencarian nama pelanggan, nomor tiket, atau isi komentar.

---

## ⏱️ Estimasi Waktu Tunggu Historis / Historical Waiting Time Estimation (P1-10)

AntreIn menghitung perkiraan waktu tunggu pelanggan dari data historis pelayanan nyata, menggantikan estimasi statis berbasis durasi layanan konfigurasi:

1. **Basis Data Historis per Unit Usaha:**
   - Sampel diambil dari durasi layanan aktual terukur (`service_started_at → completed_at`) tiket **Selesai** milik unit usaha yang sama dalam jendela 30 hari terakhir, dibatasi pada timezone lokal usaha.
   - Statistik **median** (bukan rata-rata) dipilih agar tahan terhadap pencilan — satu pelayanan yang tidak normal tidak akan mendistorsi perkiraan.
   - Minimum 5 sampel valid wajib terpenuhi sebelum angka dipublikasikan; tiket yang telah teranonimisasi (ADR-13) tetap valid sebagai sampel karena hanya timestamps yang dibaca.
2. **Sadar Multi-Loket:**
   - Rumus: `perkiraan = ceil(antrean efektif × median durasi historis ÷ jumlah loket aktif)` dengan penyesuaian loket kosong yang dapat memanggil antrean terdepan hampir seketika.
   - Semantik loket sibuk mengikuti engine pemanggilan (`callNext`); loket nonaktif tidak pernah mempercepat perkiraan.
3. **Fallback Jujur Tanpa Angka Karangan:**
   - Ketika riwayat belum mencukupi (`insufficient_history`), belum ada loket aktif (`no_active_counters`), atau tiket sudah lewat fase menunggu (`not_waiting`), respons mengembalikan `null` dengan alasan eksplisit — **tidak pernah** mensubstitusi angka nol yang menyesatkan (Spesifikasi 7.6).
4. **Tampilan pada Halaman Pelacakan:**
   - Estimasi tampil sebagai "**± X menit**" di dalam kartu status **Menunggu** disertai disclaimer eksplisit bahwa angka tersebut *bukan janji waktu pelayanan*, beserta penjelasan basis perhitungannya (riwayat historis + jumlah loket aktif).
   - Posisi antrean tetap menjadi informasi utama; estimasi diperbarui melalui mekanisme *real-time* yang sudah ada (WebSocket Reverb + *adaptive polling*) dan tidak pernah tampil basi pada status terminal.
5. **Keamanan & Privasi:**
   - Kueri terisolasi per tenant, hanya membaca kolom timestamps (bebas PII), respons hanya berisi agregat, dan service bersifat *read-only* — tidak menyentuh FIFO, siklus hidup tiket, kuota, maupun mekanisme multi-loket.

---

## 🏬 Manajemen Bisnis Multi-Cabang & Organisasi (Multi-Branch Management — P2-01)

AntreIn mendukung struktur hierarki entitas grup usaha/brand yang menaungi beberapa cabang operasional secara fleksibel dan terisolasi:

1. **Entitas Induk Organisasi (*BusinessOrganization*):**
   - Brand atau payung usaha diwakili oleh model `BusinessOrganization` yang menaungi satu atau lebih cabang operasional (`businesses`).
   - Pemilik organisasi dapat mengelola profil grup/brand, menambah cabang baru, mengubah identitas cabang, serta mengaktifkan/menonaktifkan cabang melalui `/umkm/branches` dan `/umkm/organization`.
2. **Isolasi Penuh Antrean Per Cabang:**
   - Setiap cabang memiliki nomor antrean harian mandiri (FIFO global unit yang selalu mulai dari `A-001`), kuota terpisah, dan status operasional masing-masing (`buka`, `tutup`, `jeda`).
   - Loket dan display antrean terikat secara independen pada cabang bersangkutan; tidak ada percampuran antrean antar-cabang.
3. **Pengalihan Cabang Aktif (*Active Branch Switching*):**
   - Pengguna dengan hak akses beberapa cabang dapat berpindah konteks operasional kapan saja melalui *Branch Switcher Dropdown* di topbar atau menu `/umkm/branches`.
   - Konteks sesi cabang (`active_business_id`) divalidasi ketat di sisi server via middleware `ResolveActiveBranch` untuk mencegah manipulasi sesi atau kebocoran data.
4. **Analitik & Laporan Agregat Organisasi:**
   - Halaman `/umkm/organization/reports` menyajikan metrik agregat performa seluruh cabang dalam grup (total tiket, tingkat penyelesaian, waktu tunggu rata-rata tertimbang, dan perbandingan antar cabang).
5. **Kompatibilitas Penuh Data Historis:**
   - Seluruh data bisnis tunggal lama secara otomatis terhubung ke organisasi default melalui migrasi backfill, menjaga stabilitas tautan slug publik (`/q/{slug}`), token pelacakan tiket, dan ulasan pelanggan.

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
