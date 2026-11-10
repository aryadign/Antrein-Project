# AntreIn — Development Tasklist untuk AI Coding Agent

> **Dokumen kerja:** checklist implementasi website AntreIn berdasarkan PRD v1.0 dan `AntreIn-design.md`  
> **Versi:** 1.0  
> **Status awal:** Belum dikerjakan  
> **Jenis produk:** Responsive Web Application  
> **Stack rencana:** Laravel, MySQL, Blade, Livewire, Tailwind CSS, Alpine.js, Chart.js  
> **Area aplikasi:** Admin AntreIn, Pemilik UMKM, Pelanggan

---

## 0. Tujuan dan cara menggunakan tasklist

Dokumen ini adalah backlog implementasi yang dapat diberikan kepada AI coding agent atau developer. Task diurutkan berdasarkan dependensi teknis dan prioritas produk, bukan sekadar urutan menu.

Gunakan dokumen ini bersama:

1. `AntreIn-PRD.md` — sumber kebutuhan produk, functional requirements, acceptance criteria, aturan bisnis, dan batasan MVP.
2. `AntreIn-design.md` — sumber token visual, komponen, layout, dan aturan responsivitas.
3. `AntreIn — Spesifikasi Fitur UI_UX Aplikasi UMKM.md` — referensi spesifikasi awal fitur UMKM.

**Urutan otoritas jika ada perbedaan:** keputusan produk terbaru yang telah disetujui → PRD terbaru → design system untuk keputusan visual → spesifikasi awal. Jangan mengubah aturan produk hanya berdasarkan preferensi teknis agent.

### Status checklist

- `[ ]` Belum dikerjakan.
- `[-]` Sedang dikerjakan.
- `[x]` Selesai dan sudah diverifikasi.
- `[!]` Terblokir atau membutuhkan keputusan.

Agent harus memperbarui checklist setelah menyelesaikan task. Jangan mencentang task hanya karena file atau UI sudah dibuat; task baru selesai setelah kriteria penerimaannya diverifikasi.

### Prioritas

| Prioritas | Arti |
|---|---|
| P0 | Wajib untuk MVP. |
| P1 | Penyempurnaan setelah alur inti MVP stabil. |
| P2 | Pengembangan lanjutan, di luar MVP. |

### Aturan eksekusi untuk agent

1. Baca PRD dan `AntreIn-design.md` sebelum mengubah kode.
2. Audit repository terlebih dahulu. Jika proyek Laravel sudah ada, pertahankan struktur dan konfigurasi yang masih valid; jangan membuat ulang proyek tanpa alasan.
3. Kerjakan satu milestone pada satu waktu dan ikuti dependensinya.
4. Jangan langsung mengimplementasikan semua fitur sekaligus. Selesaikan fondasi, uji, lalu lanjutkan.
5. Jangan mengarang keputusan untuk item yang tercantum di bagian **Open Questions**. Catat sebagai blocker dan minta keputusan pemilik produk jika keputusan tersebut memengaruhi data, aturan antrean, keamanan, atau scope.
6. Jangan menambahkan fitur P1/P2 ke MVP tanpa persetujuan.
7. Jangan mengganti stack yang ditetapkan tanpa alasan teknis yang kuat dan persetujuan pemilik produk.
8. Semua data operasional harus di-scope berdasarkan `business_id`. Menyembunyikan menu di frontend bukan pengamanan yang cukup.
9. Validasi aturan bisnis di backend. Frontend hanya membantu pengalaman pengguna dan bukan sumber kebenaran.
10. Gunakan transaksi database dan mekanisme concurrency control untuk pembuatan nomor tiket serta aksi pemanggilan antrean.
11. Gunakan komponen reusable dan token dari design system. Hindari hardcode warna, spacing, radius, dan shadow berulang di halaman.
12. Buat tampilan sebagai website responsif, bukan aplikasi mobile native. Admin dan UMKM desktop-first; pelanggan berupa public web interface yang nyaman di desktop maupun mobile.
13. Jangan membuat data contoh seolah-olah data produksi. Seed/demo data harus jelas sebagai data pengembangan.
14. Setiap fitur harus memiliki loading, empty, error, success, dan disabled state yang relevan.
15. Tambahkan atau perbarui test untuk setiap perilaku penting. Jangan menyatakan selesai jika test belum dijalankan.
16. Jangan menyimpan secret, password, API key, atau kredensial di repository.
17. Setelah setiap milestone, berikan ringkasan file yang berubah, test yang dijalankan, hasilnya, serta blocker yang masih ada.

---

# 1. Ringkasan ruang lingkup MVP

## 1.1 Admin AntreIn

- [ ] Login Admin.
- [ ] Dashboard platform.
- [ ] Manajemen UMKM: daftar, cari, filter, detail, aktif/nonaktif.
- [ ] Manajemen pengguna: daftar, cari, filter, detail, aktif/nonaktif.
- [ ] Monitoring antrean lintas UMKM dalam mode baca-saja.
- [ ] Manajemen kategori UMKM.
- [ ] Pengaturan identitas dan konfigurasi dasar platform.

## 1.2 Pemilik UMKM

- [ ] Registrasi, login, logout, dan reset password.
- [ ] Onboarding usaha.
- [ ] Dashboard UMKM.
- [ ] Profil usaha.
- [ ] Jam operasional dan status Buka/Tutup/Jeda.
- [ ] CRUD layanan.
- [ ] Antrean online dan walk-in dalam satu antrean FIFO.
- [ ] Tambah antrean manual.
- [ ] Now Serving dan aksi status antrean.
- [ ] Detail dan riwayat antrean.
- [ ] Laporan operasional dasar.
- [ ] Tautan dan QR antrean.
- [ ] Pengaturan akun dasar.

## 1.3 Pelanggan

- [ ] Halaman publik UMKM.
- [ ] Daftar layanan aktif dan ketersediaannya.
- [ ] Form pengambilan antrean tanpa login.
- [ ] Pembuatan tiket dan nomor otomatis.
- [ ] Halaman konfirmasi.
- [ ] Halaman pemantauan tiket dengan token unik.
- [ ] Pembaruan status melalui polling.

## 1.4 Out of scope MVP

Jangan implementasikan tanpa persetujuan: multi-staf, multi-loket, multi-cabang, WhatsApp API, POS, pembayaran, marketplace/direktori, printer tiket, paket langganan, prediksi AI/ML, dan fitur P1/P2 lainnya.

---

# 2. Gate 0 — Audit proyek dan keputusan produk

**Tujuan:** memahami kondisi proyek dan mengunci keputusan yang memengaruhi skema data serta perilaku sistem.

## 2.1 Audit repository

- [x] `G0-01` Periksa struktur repository, branch aktif, status Git, dan file yang sudah ada.
- [x] `G0-02` Periksa versi PHP, Composer, Node.js, NPM, Laravel, MySQL, dan package frontend yang tersedia.
- [x] `G0-03` Periksa apakah autentikasi, layout, komponen UI, migration, seeder, atau modul awal sudah pernah dibuat.
- [x] `G0-04` Identifikasi bagian yang dapat digunakan kembali dan bagian yang perlu diperbaiki.
- [x] `G0-05` Pastikan `.env`, kredensial, dan file sensitif tidak akan masuk commit.
- [x] `G0-06` Buat catatan audit singkat: kondisi proyek, versi teknologi, gap terhadap PRD, dan risiko awal.

**Kriteria selesai:** agent memahami kondisi repository dan tidak melakukan setup ulang yang merusak pekerjaan yang sudah ada.

## 2.2 Keputusan produk yang harus dikunci

Item berikut bersumber dari Open Questions PRD. Jangan mengasumsikan jawaban final.

- [x] `G0-07 / OQ-01` Konfirmasi apakah satu akun pemilik hanya mengelola satu UMKM pada MVP.
- [x] `G0-08 / OQ-02` Tetapkan timezone usaha, definisi hari antrean, dan kapan nomor direset.
- [x] `G0-09 / OQ-03` Putuskan apakah pembatalan tiket oleh pelanggan masuk MVP atau ditunda.
- [x] `G0-10 / OQ-04` Putuskan apakah tiket walk-in memperoleh tautan publik untuk pemantauan pelanggan.
- [x] `G0-11 / OQ-05` Tetapkan aturan prefix nomor tiket: default sistem atau dapat diatur pemilik.
- [x] `G0-12 / OQ-06` Putuskan apakah UMKM baru langsung aktif atau menunggu persetujuan Admin.
- [x] `G0-13 / OQ-07` Tetapkan dampak penonaktifan UMKM terhadap tiket yang sudah aktif.
- [x] `G0-14 / OQ-08` Tetapkan penanganan tiket Dipanggil yang tidak direspons pelanggan; jangan menambah timeout otomatis tanpa persetujuan.
- [x] `G0-15 / OQ-09` Konfirmasi cara kuota dihitung, termasuk perlakuan tiket yang dibatalkan.
- [x] `G0-16 / OQ-10` Putuskan apakah pelanggan boleh mengambil lebih dari satu tiket per hari.
- [x] `G0-17 / OQ-11` Tetapkan tujuan pengumpulan nomor telepon, masa simpan, dan apakah nomor hanya disimpan atau digunakan untuk notifikasi.
- [x] `G0-18 / OQ-12` Tetapkan interval polling berdasarkan uji performa.
- [x] `G0-19 / OQ-13` Tetapkan masa akses token tiket dan kebijakan retensi data.
- [x] `G0-20 / OQ-14` Putuskan apakah nomor telepon pelanggan terlihat pada monitoring Admin.
- [x] `G0-21 / OQ-15` Putuskan apakah ringkasan jam/hari ramai masuk laporan MVP atau hanya jika data memadai.

**Catatan blocker:** G0-08 memengaruhi daily queue, reset nomor, kuota, dan laporan; G0-13 memengaruhi tiket aktif; G0-15 memengaruhi kapasitas layanan; G0-17 dan G0-19 memengaruhi privasi; item tersebut harus dikunci sebelum implementasi perilaku terkait.

## 2.3 Rencana implementasi

- [x] `G0-22` Buat mapping PRD Functional Requirement (FR) ke task implementasi dan test.
- [x] `G0-23` Buat daftar milestone, dependency, serta urutan pull request.
- [x] `G0-24` Catat keputusan teknis dan arsitektur dalam `.docs/AntreIn-Architecture-Decisions.md` (ADR).
- [x] `G0-25` Pastikan scope P0/P1/P2 dipahami sebelum coding.

**Exit Gate 0:** audit selesai, keputusan kritis tercatat, serta rencana implementasi disetujui.

---

# 3. Milestone 1 — Product design, arsitektur, dan data model

**Dependensi:** Gate 0.

## 3.1 Desain sistem

- [x] `M1-01` Susun Use Case Diagram untuk Admin, Pemilik UMKM, dan Pelanggan.
- [x] `M1-02` Susun Activity Diagram onboarding UMKM.
- [x] `M1-03` Susun Activity Diagram pengambilan antrean online.
- [x] `M1-04` Susun Activity Diagram penambahan antrean walk-in.
- [x] `M1-05` Susun Activity Diagram lifecycle pelayanan antrean.
- [x] `M1-06` Susun Activity Diagram monitoring tiket pelanggan.
- [x] `M1-07` Dokumentasikan state machine antrean dan semua transisi valid/invalid.
- [x] `M1-08` Dokumentasikan role/permission matrix dan data ownership.

## 3.2 ERD dan data dictionary

- [x] `M1-09` Buat ERD konseptual dan fisik berdasarkan PRD serta keputusan Gate 0.
- [x] `M1-10` Buat Data Dictionary berisi nama kolom, tipe data, nullability, default, index, unique constraint, dan relasi.
- [x] `M1-11` Finalisasi entitas `users` dan role Admin/Pemilik UMKM.
- [x] `M1-12` Finalisasi entitas `businesses` (termasuk field `timezone` per-UMKM) dan `business_categories`.
- [x] `M1-13` Finalisasi entitas `business_hours` dan `services`.
- [x] `M1-14` Finalisasi entitas `daily_queues` (dengan `current_number` sebagai single source of truth; `queue_sequences` dihapus).
- [x] `M1-15` Finalisasi entitas `queue_tickets` (seluruh timestamp, token, reason) dan `queue_status_histories` (termasuk audit riwayat panggil ulang dan event_type).
- [x] `M1-16` Finalisasi entitas `activity_logs`.
- [x] `M1-17` Tentukan apakah tabel notifications dibutuhkan di MVP atau cukup disiapkan untuk tahap berikutnya.
- [x] `M1-18` Tentukan strategi foreign key (composite FK, ON DELETE RESTRICT), index, unique key, soft delete, dan retensi data.
- [x] `M1-19` Pastikan histori tiket tetap konsisten saat layanan atau UMKM dinonaktifkan.
- [x] `M1-20` Review skema terhadap skenario pembuatan tiket bersamaan dan nomor harian.

## 3.3 Arsitektur Laravel

- [x] `M1-21` Tetapkan struktur route untuk Admin, UMKM, dan halaman publik pelanggan.
- [x] `M1-22` Tetapkan strategi authentication dan role separation.
- [x] `M1-23` Tetapkan strategi authorization dengan middleware, Policies/Gates, dan business ownership scope.
- [x] `M1-24` Tetapkan pola pemisahan UI Livewire, action/service layer, model, dan validation request.
- [x] `M1-25` Tetapkan strategi exception handling dan response feedback.
- [x] `M1-26` Dokumentasikan arsitektur dan keputusan teknis utama.

**Exit Gate 1:** diagram proses, state machine, ERD, data dictionary, dan arsitektur dasar sudah direview sebelum migration inti dibuat.

**Exit Gate 1:** diagram proses, state machine, ERD, data dictionary, dan arsitektur dasar sudah direview sebelum migration inti dibuat.

---

# 4. Milestone 2 — Fondasi proyek Laravel dan design system

**Dependensi:** Gate 0 dan M1.

## 4.1 Bootstrap proyek

- [x] `M2-01` Siapkan atau rapikan proyek Laravel sesuai versi yang disepakati dan kompatibel dengan runtime.
- [x] `M2-02` Konfigurasikan koneksi MySQL melalui environment variable.
- [x] `M2-03` Siapkan Blade dan Livewire.
- [x] `M2-04` Siapkan Tailwind CSS dan Alpine.js sesuai struktur proyek.
- [x] `M2-05` Siapkan Chart.js untuk visualisasi laporan yang diperlukan.
- [x] `M2-06` Siapkan konfigurasi timezone aplikasi dan format tanggal sesuai keputusan Gate 0.
- [x] `M2-07` Pastikan konfigurasi `.env.example` lengkap tanpa secret.
- [x] `M2-08` Siapkan halaman error dasar untuk 403, 404, 419, 422, dan 500.
- [x] `M2-09` Siapkan health check atau pemeriksaan dasar kesiapan aplikasi.

## 4.2 Implementasi design tokens

Sumber visual: `AntreIn-design.md`. Jangan membuat palette atau skala baru tanpa memperbarui design system.

- [x] `M2-10` Implementasikan font Inter.
- [x] `M2-11` Implementasikan brand colors: `#1E40AF`, `#2563EB`, `#3B82F6`, `#60A5FA`, `#DBEAFE`, `#EFF6FF`.
- [x] `M2-12` Implementasikan neutral colors, semantic colors, dan queue status colors.
- [x] `M2-13` Implementasikan typography scale dan tabular numbers.
- [x] `M2-14` Implementasikan spacing scale berbasis kelipatan 4 px.
- [x] `M2-15` Implementasikan radius, border, focus ring, dan shadow tokens.
- [x] `M2-16` Implementasikan breakpoint `sm 640`, `md 768`, `lg 1024`, `xl 1280`, `2xl 1536`.
- [x] `M2-17` Gunakan CSS variables atau konfigurasi Tailwind terpusat untuk token.
- [x] `M2-18` Verifikasi tidak ada nilai visual hardcoded berulang yang bertentangan dengan token.

## 4.3 Component library

- [x] `M2-19` Buat Button variants: primary, secondary, outline, ghost, danger, dan link.
- [x] `M2-20` Buat Input, Textarea, Select, Checkbox, Switch, dan Form Field.
- [x] `M2-21` Buat Card, Stat Card, Section Header, dan Page Header.
- [x] `M2-22` Buat Status Badge untuk status antrean dan status operasional UMKM.
- [x] `M2-23` Buat Table, pagination, search field, filter controls, dan empty table state.
- [x] `M2-24` Buat Modal/Confirmation Dialog, Drawer, Dropdown, Toast/Alert.
- [x] `M2-25` Buat Loading Skeleton, Empty State, Error State, dan No Results State.
- [x] `M2-26` Buat responsive navigation/sidebar untuk Admin dan UMKM.
- [x] `M2-27` Buat public header/footer untuk Customer Interface.
- [x] `M2-28` Buat komponen queue ticket, Now Serving, source badge, dan queue action group.
- [x] `M2-29` Pastikan komponen memiliki default, hover, focus, pressed, disabled, loading, success, dan error state yang relevan.

## 4.4 Layout aplikasi

- [x] `M2-30` Buat layout Admin: navy sidebar, white topbar, neutral content background.
- [x] `M2-31` Buat layout UMKM: light sidebar, white topbar, neutral content background.
- [x] `M2-32` Buat layout Customer: website publik tanpa sidebar dashboard.
- [x] `M2-33` Buat layout auth untuk login, register, dan reset password.
- [x] `M2-34` Pastikan Admin dan UMKM desktop-first tetapi berfungsi di tablet/mobile.
- [x] `M2-35` Pastikan Customer Interface responsif untuk desktop, tablet, dan mobile.

**Exit Gate 2:** aplikasi dapat dijalankan, layout ketiga area tersedia, token dan komponen dasar terpakai, dan tidak ada halaman inti yang overflow pada ukuran layar standar.

---

# 5. Milestone 3 — Database, authentication, authorization, dan onboarding

**Dependensi:** M1 dan M2.

## 5.1 Database implementation

- [x] `M3-01` Buat migration sesuai ERD yang telah disetujui.
- [x] `M3-02` Terapkan foreign key, index, unique constraint, dan aturan nullability.
- [x] `M3-03` Buat model Eloquent dan relasi antarentitas.
- [x] `M3-04` Buat factory dan seeder untuk kategori awal serta akun Admin development.
- [x] `M3-05` Pastikan akun Admin tidak dapat dibuat melalui registrasi publik.
- [x] `M3-06` Siapkan seed data development yang jelas bukan data produksi.
- [x] `M3-07` Uji migrate, rollback, dan migrate:fresh pada database development.

## 5.2 Authentication

- [x] `M3-08` Implementasikan registrasi UMKM (FR-AUTH-01).
- [x] `M3-09` Implementasikan login/logout UMKM (FR-AUTH-02).
- [x] `M3-10` Implementasikan login Admin (FR-AUTH-03).
- [x] `M3-11` Implementasikan forgot/reset password UMKM (FR-AUTH-04) setelah mail configuration tersedia.
- [x] `M3-12` Terapkan rate limiting pada endpoint autentikasi.
- [x] `M3-13` Pastikan session regeneration, logout invalidation, password hashing, dan error message aman.
- [x] `M3-14` Uji akses guest, pemilik, Admin, akun aktif, dan akun nonaktif.

## 5.3 Authorization dan tenant isolation

- [x] `M3-15` Implementasikan role Admin dan Pemilik UMKM.
- [x] `M3-16` Implementasikan middleware pembatas area Admin dan UMKM.
- [x] `M3-17` Implementasikan Policy/Gate untuk resource usaha.
- [x] `M3-18` Pastikan query dan Livewire actions selalu memeriksa ownership `business_id`.
- [x] `M3-19` Pastikan manipulasi ID melalui URL/request tidak dapat membaca atau mengubah data UMKM lain.
- [x] `M3-20` Pastikan monitoring Admin hanya memiliki aksi baca untuk status operasional tiket.
- [x] `M3-21` Buat automated tests untuk akses lintas role dan lintas UMKM.

## 5.4 Onboarding dan profil usaha awal

- [x] `M3-22` Implementasikan proses pembuatan profil UMKM setelah registrasi.
- [x] `M3-23` Implementasikan pilihan kategori aktif.
- [x] `M3-24` Implementasikan checklist onboarding.
- [x] `M3-25` Hitung status konfigurasi minimum agar antrean online hanya dibuka ketika syarat terpenuhi.
- [x] `M3-26` Buat tampilan onboarding incomplete dan complete.

**Exit Gate 3:** Admin dan Pemilik dapat login pada area masing-masing; ownership isolation diuji; Pemilik dapat memulai dan menyelesaikan onboarding dasar.

---

# 6. Milestone 4 — Data UMKM, jam operasional, dan layanan

**Dependensi:** M3.

## 6.1 Profil UMKM dan kategori

- [x] `M4-01` Implementasikan CRUD kategori untuk Admin (FR-ADM-07).
- [x] `M4-02` Implementasikan daftar, pencarian, filter, dan pagination kategori jika diperlukan.
- [x] `M4-03` Implementasikan profil usaha: nama, logo, kategori, deskripsi, alamat, kontak (FR-SET-01).
- [x] `M4-04` Implementasikan upload logo dengan validasi tipe, ukuran, dan penyimpanan aman.
- [x] `M4-05` Implementasikan status kategori aktif/nonaktif dan aturan kategori yang telah digunakan.
- [x] `M4-06` Implementasikan slug publik unik untuk setiap UMKM.

## 6.2 Jam operasional

- [x] `M4-07` Implementasikan jadwal operasional untuk setiap hari.
- [x] `M4-08` Implementasikan jam buka, jam tutup, dan hari libur.
- [x] `M4-09` Implementasikan status manual Buka, Tutup, dan Jeda.
- [x] `M4-10` Pisahkan jam operasional terjadwal dari status operasional manual.
- [x] `M4-11` Validasi waktu dan penanganan jadwal sesuai keputusan timezone.
- [x] `M4-12` Pastikan status Tutup/Jeda mencegah tiket online baru tetapi tidak menghapus tiket aktif.
- [x] `M4-13` Tampilkan feedback ketika perubahan status memengaruhi kemampuan menerima antrean baru.

## 6.3 Layanan

- [x] `M4-14` Implementasikan daftar layanan UMKM (FR-SVC-01).
- [x] `M4-15` Implementasikan tambah dan edit layanan (FR-SVC-02).
- [x] `M4-16` Implementasikan nama, deskripsi, estimasi durasi, harga opsional, kuota opsional, dan status.
- [x] `M4-17` Terapkan validasi durasi positif, harga non-negatif, dan kuota valid.
- [x] `M4-18` Implementasikan aktivasi/nonaktif layanan (FR-SVC-03).
- [x] `M4-19` Pastikan layanan nonaktif tidak muncul untuk tiket baru.
- [x] `M4-20` Pertahankan referensi layanan historis pada tiket lama.
- [x] `M4-21` Pastikan kuota, jika digunakan, terhubung dengan periode antrean harian yang benar.
- [x] `M4-22` Buat empty state saat UMKM belum mempunyai layanan aktif.

**Exit Gate 4:** UMKM dapat menyiapkan profil, jam operasional, kategori, dan minimal satu layanan aktif; halaman publik dapat mengidentifikasi usaha melalui slug.

---

# 7. Milestone 5 — Core Queue Engine (inti sistem AntreIn)

**Dependensi:** M1, M3, M4, dan keputusan bisnis antrean yang relevan. Prioritas implementasi tertinggi.

## 7.1 Daily queue dan sequence

- [x] `M5-01` Implementasikan pembuatan race-safe daily queue (`firstOrCreate` dengan exception handling) berdasarkan UMKM dan tanggal operasional lokal.
- [x] `M5-02` Implementasikan sequence nomor antrean langsung dari `daily_queues.current_number` dengan row locking `SELECT ... FOR UPDATE`.
- [x] `M5-03` Terapkan transaksi database dan row locking/sequence locking yang sesuai saat menerbitkan nomor.
- [x] `M5-04` Terapkan unique constraint untuk mencegah nomor urut duplikat dalam periode yang sama.
- [x] `M5-05` Pastikan reset nomor harian konsisten dengan timezone dan definisi hari yang disetujui.
- [x] `M5-06` Buat automated concurrency test untuk beberapa request tiket pada waktu bersamaan.

## 7.2 Pembuatan tiket online

- [x] `M5-07` Buat domain/service action untuk validasi kelayakan antrean online.
- [x] `M5-08` Validasi UMKM aktif, status operasional, onboarding minimum, dan layanan aktif.
- [x] `M5-09` Validasi kuota layanan sebelum pembuatan tiket (menghitung status Menunggu, Dipanggil, Sedang dilayani, Selesai, dan Dilewati).
- [x] `M5-10` Buat tiket dengan sumber `ONLINE`.
- [x] `M5-11` Simpan snapshot/detail layanan yang diperlukan agar histori tidak berubah jika layanan diedit kemudian.
- [x] `M5-12` Buat token publik unik dan sulit ditebak untuk akses tiket.
- [x] `M5-13` Pastikan form double-submit tidak membuat tiket ganda; gunakan idempotency/request protection yang sesuai.
- [x] `M5-14` Pastikan nomor baru hanya ditampilkan setelah transaksi penyimpanan berhasil.

## 7.3 Pembuatan tiket walk-in

- [x] `M5-15` Buat action tambah antrean manual dengan sumber `WALK_IN`.
- [x] `M5-16` Validasi layanan dan kuota yang dipilih.
- [x] `M5-17` Gunakan sequence yang sama dengan antrean online.
- [x] `M5-18` Pastikan tiket walk-in muncul di daftar yang sama dengan tiket online.
- [x] `M5-19` Terapkan aturan tautan/tiket publik walk-in sesuai keputusan OQ-04.

## 7.4 FIFO dan operator tunggal

- [x] `M5-20` Implementasikan query tiket Menunggu berdasarkan urutan pencatatan yang konsisten.
- [x] `M5-21` Implementasikan aturan satu operator aktif: tidak boleh ada tiket Dipanggil/Sedang dilayani lain ketika panggil berikutnya dilakukan.
- [x] `M5-22` Pastikan sumber ONLINE dan WALK_IN tidak mengubah prioritas.
- [x] `M5-23` Tangani kondisi tidak ada tiket menunggu.
- [x] `M5-24` Gunakan locking/validasi status untuk mencegah dua aksi panggil berikutnya memproses tiket yang sama.

## 7.5 Status lifecycle dan history

- [x] `M5-25` Implementasikan enum/status antrean: Menunggu, Dipanggil, Sedang dilayani, Selesai, Dilewati, Dibatalkan.
- [x] `M5-26` Implementasikan transisi valid Menunggu → Dipanggil.
- [x] `M5-27` Implementasikan transisi valid Dipanggil → Sedang dilayani.
- [x] `M5-28` Implementasikan transisi valid Sedang dilayani → Selesai.
- [x] `M5-29` Implementasikan transisi valid Menunggu → Dilewati.
- [x] `M5-30` Implementasikan transisi valid Dipanggil → Dilewati.
- [x] `M5-31` Implementasikan pembatalan Menunggu → Dibatalkan hanya jika keputusan pembatalan sudah disetujui.
- [x] `M5-32` Tolak seluruh transisi yang tidak diizinkan oleh state machine MVP.
- [x] `M5-33` Catat timestamp untuk dibuat, dipanggil, panggil ulang, mulai dilayani, selesai, dilewati, dan dibatalkan sesuai kebutuhan.
- [x] `M5-34` Simpan setiap perubahan dan operational event pada `queue_status_histories` dengan `event_type` terstruktur (`status_changed`, `ticket_recalled`, `ticket_skipped`, `ticket_cancelled`).
- [x] `M5-35` Pastikan panggil ulang tidak membuat tiket baru, memperbarui `recalled_at`, dan menambahkan event `ticket_recalled` ke `queue_status_histories`.
- [x] `M5-36` Pastikan status Dilewati dan Dibatalkan tidak dihitung sebagai Selesai.
- [x] `M5-37` Buat automated tests untuk semua transisi valid dan invalid.

## 7.6 Posisi antrean dan kuota

- [x] `M5-38` Implementasikan jumlah antrean di depan berdasarkan tiket Menunggu yang lebih awal pada antrean yang sama.
- [x] `M5-39` Tentukan perilaku posisi ketika tiket Dipanggil/Sedang dilayani/Selesai/Dilewati/Dibatalkan.
- [x] `M5-40` Implementasikan pemakaian kuota sesuai definisi yang disetujui.
- [x] `M5-41` Pastikan pembatalan mengubah kapasitas hanya sesuai aturan yang telah disepakati.
- [x] `M5-42` Uji kondisi dua request bersamaan ketika kuota hampir habis.

**Exit Gate 5:** engine antrean memiliki nomor unik, FIFO, validasi kuota, state transition, timestamp, history, dan concurrency test yang lulus.

---

# 8. Milestone 6 — Customer Interface

**Dependensi:** M4 dan M5. Customer Interface adalah public website, bukan aplikasi mobile native.

## 8.1 Halaman publik UMKM

- [x] `M6-01` Implementasikan route `/q/{slug}`.
- [x] `M6-02` Tampilkan logo, nama, kategori, deskripsi, alamat, dan kontak yang memang publik.
- [x] `M6-03` Tampilkan status Buka/Tutup/Jeda dengan label yang jelas.
- [x] `M6-04` Tampilkan daftar layanan aktif, durasi, harga jika tersedia, dan ketersediaan.
- [x] `M6-05` Nonaktifkan CTA ambil antrean ketika usaha tutup/jeda, layanan tidak tersedia, atau kuota penuh.
- [x] `M6-06` Buat halaman usaha tidak ditemukan dan belum memiliki layanan aktif.

## 8.2 Form pengambilan antrean

- [x] `M6-07` Implementasikan route GET `/q/{slug}/take-queue` untuk form antrean dan POST `/q/{slug}/take-queue` untuk submit tiket (PRG pattern).
- [x] `M6-08` Implementasikan pilihan layanan dan field nama/label wajib.
- [x] `M6-09` Tambahkan nomor telepon hanya jika keputusan privasi/penggunaan data telah ditetapkan.
- [x] `M6-10` Tampilkan ringkasan layanan sebelum konfirmasi.
- [x] `M6-11` Tampilkan validasi field dan pesan kegagalan yang mudah dipahami.
- [x] `M6-12` Hubungkan submit dengan queue engine tanpa menduplikasi aturan bisnis di frontend.
- [x] `M6-13` Tangani kuota habis atau usaha berubah menjadi Tutup/Jeda sebelum submit selesai.

## 8.3 Konfirmasi dan tiket

- [x] `M6-14` Buat halaman konfirmasi setelah tiket berhasil dibuat.
- [x] `M6-15` Tampilkan nomor tiket, nama UMKM, nama pelanggan, layanan, waktu ambil, dan status.
- [x] `M6-16` Sediakan tautan pemantauan dan tombol salin tautan.
- [x] `M6-17` Pastikan token tidak terekspos dalam log atau informasi yang tidak diperlukan.

## 8.4 Pemantauan tiket

- [x] `M6-18` Implementasikan route `/ticket/{token}` (berlaku selama non-terminal dan hingga 24 jam post-terminal; token kadaluarsa tidak membocorkan PII).
- [x] `M6-19` Validasi token dan pastikan hanya tiket yang tepat dapat diakses.
- [x] `M6-20` Tampilkan status, nomor yang sedang dilayani, posisi di depan, dan waktu pengambilan.
- [x] `M6-21` Tampilkan estimasi waktu hanya jika data memadai; jangan tampilkan angka 0 sebagai pengganti data kosong.
- [x] `M6-22` Implementasikan adaptive polling (5s Menunggu/Dipanggil, 15s Dilayani) dan hentikan polling segera pada status terminal.
- [x] `M6-23` Tangani token invalid, tiket tidak ditemukan, dan koneksi gagal.
- [x] `M6-24` Tampilkan instruksi yang sesuai untuk tiap status.
- [x] `M6-25` Terapkan pembatalan pelanggan hanya jika disetujui dan hanya untuk status yang diizinkan.

## 8.5 Responsive customer web

- [x] `M6-26` Buat layout desktop dengan header website dan konten terstruktur.
- [x] `M6-27` Buat layout tablet satu/dua kolom sesuai ruang.
- [x] `M6-28` Buat layout mobile satu kolom dengan CTA mudah dijangkau.
- [x] `M6-29` Pastikan nomor tiket tetap dominan pada desktop dan mobile.
- [x] `M6-30` Pastikan tidak ada horizontal overflow pada halaman pelanggan.

**Exit Gate 6:** pelanggan dapat membuka halaman UMKM, mengambil antrean tanpa login, memperoleh tiket, dan memantau status dengan token yang valid.

---

# 9. Milestone 7 — Dashboard dan operasional UMKM

**Dependensi:** M3, M4, dan M5. Customer Interface dapat dikerjakan paralel dengan bagian UI setelah queue engine tersedia.

## 9.1 Dashboard UMKM

- [x] `M7-01` Implementasikan halaman `/umkm/dashboard` (FR-MER-01).
- [x] `M7-02` Tampilkan nama usaha, tanggal operasional, dan status Buka/Tutup/Jeda.
- [x] `M7-03` Tampilkan statistik total, Menunggu, Dipanggil, Sedang dilayani, Selesai, Dilewati, dan Dibatalkan.
- [x] `M7-04` Tampilkan beberapa tiket aktif paling relevan, bukan seluruh riwayat.
- [x] `M7-05` Tampilkan aksi cepat: Kelola Antrean, Tambah Manual, Tambah Layanan, Jam Operasional.
- [x] `M7-06` Tampilkan checklist onboarding jika setup belum lengkap.
- [x] `M7-07` Implementasikan empty, loading, error, dan usaha tutup/jeda states.

## 9.2 Daftar antrean

- [x] `M7-08` Implementasikan halaman `/umkm/queues` (FR-MER-02).
- [x] `M7-09` Tampilkan ringkasan jumlah per status.
- [x] `M7-10` Tampilkan nomor tiket, nama/label, layanan, sumber, waktu dibuat, status, dan aksi.
- [x] `M7-11` Implementasikan pencarian nomor tiket/nama.
- [x] `M7-12` Implementasikan filter status, layanan, sumber, dan periode riwayat.
- [x] `M7-13` Implementasikan pagination atau load more.
- [x] `M7-14` Pada mobile, ubah tabel menjadi kartu atau tampilan ringkas tanpa horizontal scrolling berlebihan.

## 9.3 Now Serving dan aksi operasional

- [x] `M7-15` Implementasikan komponen Now Serving (FR-MER-03).
- [x] `M7-16` Implementasikan Panggil Berikutnya (FR-MER-04).
- [x] `M7-17` Implementasikan Panggil Ulang (FR-MER-05).
- [x] `M7-18` Implementasikan Mulai Melayani (FR-MER-06).
- [x] `M7-19` Implementasikan Selesaikan Antrean (FR-MER-07).
- [x] `M7-20` Implementasikan Lewati Antrean (FR-MER-08).
- [x] `M7-21` Sesuaikan tombol dengan status saat ini; jangan tampilkan aksi yang tidak valid.
- [x] `M7-22` Berikan feedback dan proteksi double click pada setiap aksi.
- [x] `M7-23` Pastikan perubahan UI hanya menunjukkan sukses setelah server menyimpan perubahan.

## 9.4 Antrean manual dan detail

- [x] `M7-24` Implementasikan form tambah walk-in (FR-MER-09).
- [x] `M7-25` Tampilkan nomor tiket setelah penyimpanan berhasil.
- [x] `M7-26` Implementasikan panel/halaman detail tiket (FR-MER-10).
- [x] `M7-27` Tampilkan timestamp dan riwayat status.
- [x] `M7-28` Implementasikan riwayat antrean baca-saja dengan pencarian dan filter.

## 9.5 Layanan dan pengaturan UMKM

- [x] `M7-29` Hubungkan halaman CRUD layanan ke backend (FR-SVC-01 sampai FR-SVC-03).
- [x] `M7-30` Hubungkan profil usaha (FR-SET-01).
- [x] `M7-31` Hubungkan jam operasional (FR-SET-02).
- [x] `M7-32` Implementasikan halaman URL/QR antrean (FR-SET-03).
- [x] `M7-33` Implementasikan salin tautan dan unduh QR jika generator QR tersedia.
- [x] `M7-34` Implementasikan pengaturan akun pemilik (FR-SET-04).
- [x] `M7-35` Tampilkan preferensi notifikasi hanya untuk kanal yang benar-benar didukung (FR-SET-05).

## 9.6 Responsive UMKM web

- [x] `M7-36` Verifikasi layout desktop pada lebar 1280 px dan 1440 px.
- [x] `M7-37` Verifikasi layout tablet pada rentang 768–1023 px.
- [x] `M7-38` Verifikasi layout mobile di bawah 768 px.
- [x] `M7-39` Pastikan sidebar menjadi drawer/icon rail sesuai design system.
- [x] `M7-40` Pastikan aksi antrean utama tetap mudah ditemukan pada layar sempit.

**Exit Gate 7:** Pemilik dapat menjalankan operasional dari dashboard, termasuk antrean online dan walk-in, sampai tiket selesai atau dilewati.

---

# 10. Milestone 8 — Dashboard Admin dan monitoring platform

**Dependensi:** M3, M4, dan M5.

## 10.1 Dashboard Admin

- [x] `M8-01` Implementasikan `/admin/dashboard` (FR-ADM-01).
- [x] `M8-02` Tampilkan total UMKM, UMKM aktif/nonaktif, total pengguna, total antrean, dan antrean selesai.
- [x] `M8-03` Implementasikan filter periode untuk metrik yang memiliki dimensi waktu.
- [x] `M8-04` Implementasikan grafik tren pendaftaran UMKM dan antrean.
- [x] `M8-05` Implementasikan distribusi antrean berdasarkan status.
- [x] `M8-06` Implementasikan aktivitas terbaru dari sumber yang tercatat.
- [x] `M8-07` Pastikan empty state dan definisi periode konsisten.

## 10.2 Manajemen UMKM

- [x] `M8-08` Implementasikan daftar UMKM dengan search, filter, dan pagination (FR-ADM-02).
- [x] `M8-09` Tampilkan nama usaha, pemilik, kategori, alamat, tanggal daftar, jumlah layanan, jumlah antrean, dan status.
- [x] `M8-10` Implementasikan halaman detail UMKM (FR-ADM-03).
- [x] `M8-11` Implementasikan aktivasi/penonaktifan dengan konfirmasi (FR-ADM-04).
- [x] `M8-12` Pertahankan data historis saat UMKM dinonaktifkan.
- [x] `M8-13` Terapkan aturan tiket aktif sesuai keputusan OQ-07.
- [x] `M8-14` Catat perubahan status dalam activity log.

## 10.3 Manajemen pengguna

- [x] `M8-15` Implementasikan daftar pengguna (FR-ADM-05).
- [x] `M8-16` Implementasikan pencarian nama/email dan filter role.
- [x] `M8-17` Tampilkan UMKM terkait apabila akun adalah Pemilik UMKM.
- [x] `M8-18` Implementasikan detail dan aktivasi/penonaktifan akun.
- [x] `M8-19` Pastikan Admin tidak dapat menonaktifkan akun sendiri hingga menyebabkan platform tidak memiliki administrator aktif tanpa mekanisme pencegahan.

## 10.4 Monitoring antrean

- [x] `M8-20` Implementasikan monitoring antrean lintas UMKM (FR-ADM-06).
- [x] `M8-21` Tampilkan nomor tiket, UMKM, layanan, label pelanggan, sumber, waktu dibuat, dan status.
- [x] `M8-22` Implementasikan pencarian nomor tiket/nama UMKM.
- [x] `M8-23` Implementasikan filter status, sumber, dan tanggal.
- [x] `M8-24` Implementasikan halaman detail tiket read-only.
- [x] `M8-25` Pastikan tidak ada tombol perubahan status operasional di area Admin.
- [x] `M8-26` Batasi data pelanggan yang sensitif sesuai keputusan OQ-14 (masking nomor telepon pada monitoring Admin).

## 10.5 Pengaturan platform

- [x] `M8-27` Implementasikan pengaturan identitas platform (FR-ADM-08).
- [x] `M8-28` Implementasikan validasi logo dan informasi kontak.
- [x] `M8-29` Pastikan konfigurasi hanya dapat diubah oleh Admin berwenang.

## 10.6 Responsive Admin web

- [x] `M8-30` Verifikasi desktop-first layout dan tabel pada desktop.
- [x] `M8-31` Verifikasi sidebar drawer/icon rail pada tablet.
- [x] `M8-32` Verifikasi ringkasan statistik dan daftar data pada mobile.
- [x] `M8-33` Pastikan tabel tidak memaksa horizontal scrolling pada halaman penting; gunakan kartu atau kolom prioritas.

**Exit Gate 8:** Admin dapat mengelola platform dan memonitor antrean tanpa mengubah status pelayanan UMKM.

---

# 11. Milestone 9 — Laporan, analitik, dan audit log

**Dependensi:** M5, M7, dan data timestamp yang konsisten.

## 11.1 Laporan UMKM

- [x] `M9-01` Implementasikan filter Hari Ini, 7 Hari, 30 Hari, dan rentang tanggal khusus (FR-REP-01).
- [x] `M9-02` Implementasikan total antrean berdasarkan tiket dibuat pada periode.
- [x] `M9-03` Implementasikan jumlah tiket Selesai, Dilewati, dan Dibatalkan secara terpisah.
- [x] `M9-04` Implementasikan rata-rata waktu tunggu dari dibuat ke mulai dilayani, hanya untuk data lengkap.
- [x] `M9-05` Implementasikan rata-rata durasi pelayanan dari mulai dilayani ke selesai, hanya untuk data lengkap.
- [x] `M9-06` Implementasikan grafik tren antrean.
- [x] `M9-07` Implementasikan distribusi status.
- [x] `M9-08` Implementasikan jumlah antrean berdasarkan layanan.
- [x] `M9-09` Implementasikan analisis jam ramai berdasarkan `created_at` (demand kedatangan) dengan threshold minimal 50 tiket pada periode; tampilkan empty state informatif jika kurang.
- [x] `M9-10` Tampilkan periode laporan secara eksplisit.
- [x] `M9-11` Tampilkan no-data state untuk metrik yang tidak dapat dihitung; jangan mengubah data kosong menjadi nol.
- [x] `M9-12` Validasi hasil agregasi menggunakan dataset uji yang diketahui hasil hitungnya.

## 11.2 Statistik Admin

- [x] `M9-13` Validasi definisi total antrean platform dan periode penghitungan.
- [x] `M9-14` Pastikan statistik agregat tidak menggandakan tiket.
- [x] `M9-15` Pastikan filter periode diterapkan konsisten ke seluruh visualisasi terkait.

## 11.3 Activity log

- [x] `M9-16` Catat perubahan status UMKM.
- [x] `M9-17` Catat perubahan status akun pengguna.
- [x] `M9-18` Catat perubahan kategori.
- [x] `M9-19` Catat perubahan konfigurasi usaha yang penting.
- [x] `M9-20` Catat perubahan status tiket.
- [x] `M9-21` Pastikan log menyimpan actor, jenis aksi, resource, waktu, dan metadata minimum yang disetujui.
- [x] `M9-22` Pastikan data sensitif tidak disalin berlebihan ke log.

**Exit Gate 9:** laporan dan statistik dapat direkonsiliasi dengan data tiket; aktivitas penting tercatat.

---

# 12. Milestone 10 — Security, QA, responsive testing, dan hardening

**Dependensi:** seluruh fitur P0 telah terintegrasi.

## 12.1 Security review

- [x] `M10-01` Audit autentikasi, session handling, password reset, dan rate limiting.
- [x] `M10-02` Audit seluruh route yang memerlukan autentikasi.
- [x] `M10-03` Audit authorization pada Controller/Livewire action dan query data.
- [x] `M10-04` Jalankan pengujian akses lintas UMKM menggunakan minimal dua UMKM uji.
- [x] `M10-05` Pastikan token tiket cukup acak, tidak mudah ditebak, dan tidak membocorkan ID berurutan.
- [x] `M10-06` Audit validasi upload file.
- [x] `M10-07` Audit CSRF, XSS, SQL injection risk, mass assignment, dan output escaping.
- [x] `M10-08` Terapkan rate limit untuk endpoint publik pengambilan tiket dan polling sesuai hasil uji.
- [x] `M10-09` Periksa log agar tidak mencatat password, token sensitif, atau data pribadi tanpa kebutuhan.
- [x] `M10-10` Pastikan environment production tidak menggunakan debug mode dan secret tidak berada di source control.

## 12.2 Functional QA

- [x] `M10-11` Test registrasi, login, logout, dan reset password.
- [x] `M10-12` Test onboarding dan kelengkapan konfigurasi usaha.
- [x] `M10-13` Test CRUD kategori dan layanan.
- [x] `M10-14` Test jam operasional dan status Buka/Tutup/Jeda.
- [x] `M10-15` Test pengambilan antrean online.
- [x] `M10-16` Test antrean walk-in.
- [x] `M10-17` Test FIFO online dan walk-in.
- [x] `M10-18` Test seluruh transisi status valid dan invalid.
- [x] `M10-19` Test kuota penuh dan kondisi request bersamaan.
- [x] `M10-20` Test token valid, invalid, dan tiket status akhir.
- [x] `M10-21` Test polling dan perubahan status dari dashboard UMKM ke halaman pelanggan.
- [x] `M10-22` Test dashboard dan laporan terhadap dataset uji.
- [x] `M10-23` Test Admin monitoring read-only.

## 12.3 Responsive and browser QA

- [x] `M10-24` Uji Admin pada desktop minimal 1280 px dan 1440 px.
- [x] `M10-25` Uji UMKM pada desktop minimal 1280 px dan 1440 px.
- [x] `M10-26` Uji Admin dan UMKM pada tablet sekitar 768–1023 px.
- [x] `M10-27` Uji Admin dan UMKM pada mobile di bawah 768 px.
- [x] `M10-28` Uji Customer Interface pada desktop, tablet, dan mobile.
- [x] `M10-29` Periksa overflow horizontal, modal/drawer, tabel/kartu, form, dan tombol aksi.
- [x] `M10-30` Uji browser modern yang disepakati.
- [x] `M10-31` Uji keyboard navigation, visible focus, label form, dan kontras minimum yang relevan.

## 12.4 Reliability and performance

- [x] `M10-32` Periksa query N+1 pada dashboard, daftar antrean, monitoring, dan laporan.
- [x] `M10-33` Pastikan pagination digunakan pada daftar yang berpotensi panjang.
- [x] `M10-34` Uji concurrent ticket creation.
- [x] `M10-35` Uji concurrent call-next.
- [x] `M10-36` Uji database rollback saat proses pembuatan tiket gagal.
- [x] `M10-37` Pastikan polling berhenti atau diperlambat ketika tiket terminal/tidak aktif sesuai kebijakan.
- [x] `M10-38` Pastikan error koneksi dan server ditangani tanpa menampilkan data palsu.

## 12.5 Release checklist

- [x] `M10-39` Pastikan migration production dapat dijalankan secara aman.
- [x] `M10-40` Siapkan seeder/bootstrap Admin secara aman tanpa kredensial hardcoded.
- [x] `M10-41` Siapkan konfigurasi storage untuk logo dan QR.
- [x] `M10-42` Siapkan konfigurasi mail jika reset password menggunakan email.
- [x] `M10-43` Siapkan scheduler/queue worker hanya jika fitur yang dirilis membutuhkannya.
- [x] `M10-44` Siapkan backup database dan prosedur restore.
- [x] `M10-45` Siapkan `.env.example`, README setup lokal, dan instruksi deployment.
- [x] `M10-46` Jalankan test suite final pada environment mendekati production.
- [x] `M10-47` Catat known issues dan pastikan tidak ada blocker kritis.

**Exit Gate 10:** seluruh acceptance criteria P0 telah diuji; tidak ada isu kritis keamanan, akses data, duplikasi nomor, atau integritas status yang diketahui.

---

# 13. Milestone 11 — Report Export: CSV & PDF

**Dependensi:** M9 (Laporan dan Analitik), M10 (Hardening & Quality Assurance).

## 13.1 CSV Report Export (P1-06)

- [x] `M11-01` Implementasikan endpoint streaming download CSV pada route `/umkm/reports/export/csv`.
- [x] `M11-02` Terapkan filter tanggal (Hari Ini, 7 Hari, 30 Hari, dan Rentang Kustom) secara konsisten dengan dashboard laporan.
- [x] `M11-03` Gunakan zona waktu operasional UMKM untuk konversi batas tanggal dan formatting waktu tiket.
- [x] `M11-04` Ekspor metadata laporan: Nama UMKM, Kategori, Periode, Rentang Tanggal, Timezone, Waktu Pengunduhan.
- [x] `M11-05` Ekspor ringkasan metrik operasional: Total Tiket, Selesai, Menunggu, Dipanggil, Dilayani, Dilewati, Dibatalkan, Waktu Tunggu, Durasi Layanan, Jam Sibuk.
- [x] `M11-06` Ekspor rekapitulasi performa berdasarkan layanan (nama, status, total, selesai, dilewati, dibatalkan, rata-rata durasi).
- [x] `M11-07` Ekspor rincian tiket antrean periode lengkap dengan nomor tiket, nama pelanggan, layanan, sumber, status, dan timestamp.
- [x] `M11-08` Terapkan mitigasi CSV Formula Injection (escape karakter `=`, `+`, `-`, `@`, `\t`, `\r` dengan prefix petik tunggal).
- [x] `M11-09` Gunakan encoding UTF-8 dengan BOM (`\xEF\xBB\xBF`) untuk kompatibilitas penuh Microsoft Excel.
- [x] `M11-10` Pastikan token sensitif (`access_token`, `idempotency_key`) tidak diekspor ke file CSV.

## 13.2 PDF Report Export (P1-07)

- [x] `M11-11` Integrasikan library PDF (`barryvdh/laravel-dompdf` v3.1) yang kompatibel dengan Laravel 13 dan PHP 8.4.
- [x] `M11-12` Implementasikan endpoint download PDF pada route `/umkm/reports/export/pdf`.
- [x] `M11-13` Susun layout PDF profesional A4 Portrait berstandar AntreIn Design System (Primary `#1E40AF`, font Inter/DejaVu Sans).
- [x] `M11-14` Tampilkan header resmi: Identitas UMKM, kategori, kontak, judul laporan, periode laporan, dan waktu cetak.
- [x] `M11-15` Sajikan ringkasan KPI eksekutif dalam bentuk kartu metrik terstruktur.
- [x] `M11-16` Sajikan kotak analisis jam sibuk operasional sesuai aturan ambang batas OQ-15 (minimal 50 tiket/jam).
- [x] `M11-17` Sajikan tabel rekapitulasi kinerja layanan dengan badge status aktif/nonaktif dan rata-rata durasi.
- [x] `M11-18` Sajikan tabel rincian tiket antrean dengan penanganan page break (`page-break-inside: avoid`).
- [x] `M11-19` Tangani dataset kosong (*empty state*) tanpa memicu exception atau error kompilasi PDF.
- [x] `M11-20` Tampilkan footer resmi AntreIn pada setiap halaman dokumen.

## 13.3 UI Integration & Security Controls

- [x] `M11-21` Tambahkan tombol aksi "Ekspor CSV" dan "Ekspor PDF" pada halaman laporan merchant (`/umkm/reports`).
- [x] `M11-22` Pastikan parameter filter query (`period`, `start_date`, `end_date`) otomatis diteruskan ke tautan ekspor.
- [x] `M11-23` Terapkan proteksi autentikasi (`auth`), otorisasi role (`role:merchant`), dan status onboarding (`onboarded`).
- [x] `M11-24` Terapkan isolasi multi-tenant ketat: data laporan selalu di-scope ke `$request->user()->business`.
- [x] `M11-25` Cegah akses Admin terhadap ekspor laporan merchant (Admin dibatasi read-only monitoring).
- [x] `M11-26` Terapkan rate limiting pada endpoint ekspor untuk melindungi server dari scraping/abuse.
- [x] `M11-27` Tangani input rentang tanggal tidak valid dengan redirect informatif dan pesan validasi error.

**Exit Gate 11:** Seluruh fitur ekspor CSV dan PDF berfungsi, konsisten dengan data dashboard, aman dari formula injection & kebocoran tenant, serta lulus 100% automated tests.

---

# 14. Milestone 12 — Multi-Staf Management & Role-Based Access Control (P1-01 & P1-02)

**Dependensi:** M5 (Core Queue Engine), M7 (Dashboard & Operasional), M9 (Audit Log), M10 (Hardening), M11 (Report Export).

## 14.1 Relasi Multi-Staf & Database Memberships (P1-01)

- [x] `M12-01` Buat migrasi tabel `business_memberships` dengan relasi `user_id` unik (aturan satu akun pengguna hanya dapat terhubung ke satu unit usaha pada lingkup rilis ini).
- [x] `M12-02` Tetapkan role keanggotaan bisnis: `owner` (pemilik usaha dengan otoritas penuh) dan `operator` (staf operasional antrean).
- [x] `M12-03` Buat migrasi data otomatis untuk menyalin seluruh pemilik usaha aktif saat ini ke dalam `business_memberships` dengan role `owner`.
- [x] `M12-04` Buat migrasi tabel `staff_invitations` dengan token SHA-256 yang aman, timestamp kadaluarsa (default 72 jam), status diterima (`accepted_at`), dan status dicabut (`revoked_at`).
- [x] `M12-05` Definisikan model Eloquent `BusinessMembership` dengan relasi `business()`, `user()`, konstanta role, dan helper methods (`isOwner()`, `isOperator()`).
- [x] `M12-06` Definisikan model Eloquent `StaffInvitation` dengan helper method `generateSecureToken()`, `isValid()`, `isExpired()`, `isAccepted()`, `isRevoked()`.
- [x] `M12-07` Tambahkan relasi `memberships()`, `members()`, `staffMemberships()`, `invitations()`, `pendingInvitations()` pada model `Business`.
- [x] `M12-08` Tambahkan relasi dan helper pada model `User`: `business()`, `ownedBusiness()`, `businessMembership()`, `isOwner()`, `isOperator()`, `isBusinessMemberActive()`, `hasCompletedOnboarding()`.
- [x] `M12-09` Pasang event lifecycle model pada `Business` (`booted()`) untuk memastikan pembentukan otomatis keanggotaan `owner` saat unit usaha baru dibuat.

## 14.2 Role-Based Access Control & Middleware (P1-02)

- [x] `M12-10` Buat middleware `EnsureBusinessRole` (`business.role`) untuk memvalidasi keberadaan membership, status aktif, dan batasan peran bisnis.
- [x] `M12-11` Daftarkan middleware alias `'business.role'` di `bootstrap/app.php`.
- [x] `M12-12` Lindungi rute administratif pemilik (`business.role:owner`): Laporan & ekspor (CSV/PDF), pengaturan profil/jam buka/status, CRUD layanan, dan manajemen staf.
- [x] `M12-13` Izinkan rute konsol operasional untuk owner dan operator (`business.role:owner,operator`): Dashboard operasional, daftar antrean, ambil antrean walk-in, panggil antrean, panggil ulang, mulai layanan, dan selesaikan tiket.
- [x] `M12-14` Batasi aksi pembatalan tiket antrean (`cancel`) secara khusus hanya untuk role `owner`; tolak aksi pembatalan oleh operator dengan response 403 Forbidden.
- [x] `M12-15` Lindungi primary owner: cegah penonaktifan atau penghapusan keanggotaan pemilik utama unit usaha.
- [x] `M12-16` Sesuaikan `OnboardingController` agar staf operator yang baru bergabung langsung diarahkan ke konsol operasional tanpa harus mengisi formulir wizard onboarding bisnis.

## 14.3 Staff Management Interface & Undangan (P1-01 & P1-02)

- [x] `M12-17` Implementasikan `StaffManagementController` pada route `/umkm/staff` dengan fitur daftar staf aktif/nonaktif, statistik tim, dan daftar undangan tertunda.
- [x] `M12-18` Implementasikan pembuatan undangan staf dengan form input email, validasi duplikasi, pencegahan undang akun admin/owner/anggota UMKM lain, dan penghasilan tautan instan.
- [x] `M12-19` Sediakan flash banner dengan tombol salin tautan undangan instan ke clipboard untuk pemilik usaha.
- [x] `M12-20` Implementasikan pencabutan undangan tertunda (`revokeInvitation`) oleh pemilik usaha.
- [x] `M12-21` Implementasikan pengalihan status aktif staf (`toggleStatus`) dan penghapusan staf (`destroy`) oleh pemilik usaha.
- [x] `M12-22` Bangun antarmuka publik/auth penerimaan undangan pada route `GET /invite/{token}` dan `POST /invite/{token}/accept` yang mendukung pendaftaran akun baru staf atau login akun terdaftar.
- [x] `M12-23` Perbarui navigasi sidebar dan user dropdown merchant agar secara dinamis menyesuaikan menu berdasarkan peran (`owner` vs `operator`).
- [x] `M12-24` Catat audit log menyeluruh via `ActivityLog` untuk pembuatan undangan, pencabutan undangan, penerimaan undangan, pengubahan status staf, dan penghapusan staf.
- [x] `M12-25` Pertahankan integritas Core Queue Engine: aturan single active ticket per bisnis tetap berlaku penuh across multiple operators tanpa merusak FIFO terpadu.

**Exit Gate 12:** Seluruh fitur multi-staf dan RBAC berfungsi, terisolasi per unit usaha, aturan single-business per staf terpenuhi, proteksi pemilik utama aktif, dan seluruh automated test lulus 100%.

---

# 15. Milestone 13 — Multi-Loket Management & Queue Assignment (P1-03)

**Dependensi:** M5 (Core Queue Engine), M7 (Dashboard & Operasional UMKM), M12 (Multi-Staff & RBAC).

## 15.1 Arsitektur Multi-Loket & Skema Database (P1-03)

- [x] `M13-01` Buat migrasi tabel `business_counters` (`id`, `business_id`, `name`, `code`, `description`, `is_active`, timestamps; unique `[business_id, code]`).
- [x] `M13-02` Buat migrasi tabel `counter_operator_assignments` (`id`, `business_counter_id`, `business_membership_id`, timestamps; unique `[business_counter_id, business_membership_id]`).
- [x] `M13-03` Tambahkan kolom foreign key `counter_id` pada tabel `queue_tickets` (`nullable`, `constrained('business_counters')->nullOnDelete()`, index gabungan `[business_id, counter_id, status]`).
- [x] `M13-04` Buat migrasi data otomatis untuk backfill `Loket 1` (`L-01`) pada seluruh unit usaha eksisting, asosiasi tiket aktif, dan penugasan seluruh membership aktif.
- [x] `M13-05` Definisikan model Eloquent `BusinessCounter` dengan relasi `business()`, `assignedMemberships()`, `tickets()`, `activeTicket()`, scope `active()`, dan helper methods `hasActiveTicket()`, `canBeOperatedBy(User $user)`.
- [x] `M13-06` Tambahkan relasi `counters()` dan `activeCounters()` pada model `Business`.
- [x] `M13-07` Tambahkan relasi `assignedCounters()` pada model `BusinessMembership`.
- [x] `M13-08` Tambahkan relasi dan helper pada model `User`: `assignedCounters()`, `canOperateCounter(BusinessCounter $counter)`.
- [x] `M13-09` Pasang event lifecycle model pada `Business` (`booted()`) untuk auto-provision default `Loket 1` saat UMKM baru dibuat.
- [x] `M13-10` Pasang event lifecycle model pada `BusinessMembership` (`booted()`) untuk auto-assign operator baru ke loket aktif default.

## 15.2 Core Queue Engine Multi-Counter & Konkurensi (P1-03)

- [x] `M13-11` Perbarui `QueueService::callNext` dengan parameter `?BusinessCounter $counter = null`, validasi otorisasi, dan penegakan aturan Single Active Ticket per Counter (bukan per bisnis).
- [x] `M13-12` Pertahankan Single Source of Truth Global FIFO (`DailyQueue` per bisnis per tanggal operasional); seluruh tiket online & walk-in mengantre dalam sequence terpadu tanpa memecah antrean per loket.
- [x] `M13-13` Perbarui `QueueService::recallTicket`, `startService`, `completeTicket`, `skipTicket` dengan validasi kepemilikan dan integritas loket.
- [x] `M13-14` Implementasikan `QueueService::getActiveCountersStatus(Business $business)` dan perbarui `getCurrentServingTicket(?BusinessCounter $counter = null)`.

## 15.3 Antarmuka Manajemen Loket & Pengalaman Operasional (P1-03)

- [x] `M13-15` Implementasikan `CounterManagementController` pada route `/umkm/counters`: CRUD loket, toggle status aktif/nonaktif, penugasan staf operator, dan pencatatan audit log `ActivityLog`.
- [x] `M13-16` Pasang guards pada penonaktifan dan penghapusan loket: tidak dapat menonaktifkan/menghapus loket terakhir yang aktif, dan tidak dapat menonaktifkan/menghapus loket yang masih memiliki tiket aktif.
- [x] `M13-17` Batasi seluruh rute manajemen loket `/umkm/counters` khusus untuk role `owner` via middleware `business.role:owner`.
- [x] `M13-18` Implementasikan pemilihan loket aktif via endpoint `POST /umkm/queues/select-counter` dengan persistensi session `merchant_active_counter_id`.
- [x] `M13-19` Perbarui dashboard operasional `/umkm/dashboard` dengan loket selector, badge loket pada kartu Now Serving, dan monitoring status seluruh loket aktif.
- [x] `M13-20` Perbarui meja operasional `/umkm/queues` dengan loket selector, filter berdasarkan loket, dan indikator badge loket pada tabel serta kartu antrean.
- [x] `M13-21` Tambahkan item menu navigasi 'Loket' dengan ikon loket pada sidebar UMKM untuk role `owner`.
- [x] `M13-22` Perbarui endpoint pelacakan pelanggan `GET /queue/{token}/track` untuk menampilkan nama loket pemanggil aman (`counter_name`, `counter_code`) tanpa mengekspos ID internal database.
- [x] `M13-23` Perbarui tampilan pelacakan antrean pelanggan `customer/track.blade.php` dengan badge loket tujuan saat tiket berstatus `dipanggil` dan `sedang_dilayani`.
- [x] `M13-24` Implementasikan automated feature test suite komprehensif `CounterManagementTest.php` mencakup 17 skenario: provisioning, CRUD, guards, multi-operator, paralel calling, single active ticket per loket, global FIFO, session, dan tenant isolation.
- [x] `M13-25` Seluruh test suite (302 automated tests) lulus 100% tanpa regresi dan aset frontend terkompilasi optimal via Vite.

**Exit Gate 13:** Fitur multi-loket dan penugasan operator berjalan optimal, antrean dapat dilayani paralel di banyak loket dengan tetap menjaga FIFO global, customer dapat melihat loket tujuan, dan 302 automated tests lulus 100%.

---

# 16. Milestone 14 — Real-time Queue Updates with Laravel Reverb & WebSocket (P1-04)

Fokus utama: Implementasi pembaruan antrean real-time berbasis WebSocket menggunakan Laravel Reverb (`P1-04`), saluran broadcast terenkripsi dan terotorisasi, event broadcasting transaksional (`ShouldDispatchAfterCommit`), integrasi frontend Laravel Echo & Pusher-js, dan fallback adaptif.

## 16.1 Arsitektur Broadcasting & Konfigurasi Server (P1-04)

- [x] `M14-01` Instal dan konfigurasikan `laravel/reverb` v1.12 dan `pusher/pusher-php-server` pada backend PHP.
- [x] `M14-02` Instal pustaka klien WebSocket frontend `laravel-echo` dan `pusher-js` melalui NPM.
- [x] `M14-03` Terbitkan dan sesuaikan file konfigurasi `config/reverb.php` dan perbarui driver `config/broadcasting.php` dengan koneksi `reverb`.
- [x] `M14-04` Konfigurasikan variabel environment Reverb (`REVERB_APP_KEY`, `REVERB_APP_SECRET`, `REVERB_APP_ID`, `REVERB_HOST`, `REVERB_PORT`, `REVERB_SCHEME`) pada `.env` dan `.env.example`.
- [x] `M14-05` Pastikan `phpunit.xml` menggunakan `BROADCAST_CONNECTION=null` agar unit dan feature tests terisolasi tanpa memerlukan daemon Reverb aktif.

## 16.2 Event Broadcasting Transaksional & Payload Sanitization (P1-04)

- [x] `M14-06` Implementasikan event `QueueTicketCreated` dengan kontrak `ShouldBroadcastNow` dan `ShouldDispatchAfterCommit` pada private channel `business.{businessId}`.
- [x] `M14-07` Implementasikan event `QueueTicketStatusChanged` pada private channel `business.{businessId}` dan customer ticket channel `ticket.{tokenHash}`.
- [x] `M14-08` Implementasikan event `QueueTicketCounterAssigned` pada private channel `business.{businessId}`, `counter.{counterId}`, dan `ticket.{tokenHash}`.
- [x] `M14-09` Implementasikan event `CounterStatusChanged` pada private channel `business.{businessId}` dan `counter.{counterId}`.
- [x] `M14-10` Jamin payload data event tersanitasi: tidak ada nomor telepon pelanggan terbuka, tidak ada data PII mentah, dan tidak ada access token mentah dalam payload broadcast.

## 16.3 Otorisasi Saluran & Endpoint Autentikasi Pelanggan (P1-04)

- [x] `M14-11` Daftarkan otorisasi channel `business.{businessId}` pada `routes/channels.php` khusus untuk admin platform serta merchant owner/operator aktif dari unit usaha tersebut.
- [x] `M14-12` Daftarkan otorisasi channel `counter.{counterId}` khusus untuk staf/owner yang memiliki izin operasional loket bersangkutan.
- [x] `M14-13` Implementasikan controller otorisasi broadcast tiket tamu `TicketBroadcastAuthController` pada rute `POST /broadcasting/auth/ticket` dengan rate limiting (60 req/min/IP), validasi kesesuaian SHA-256 token hash, dan penandatanganan payload HMAC/Pusher.

## 16.4 Integrasi Domain Queue & Frontend Echo (P1-04)

- [x] `M14-14` Pasang event dispatching pada seluruh siklus antrean di `QueueService`: `generateOnlineTicket`, `generateWalkInTicket`, `callNext`, `recallTicket`, `startService`, `completeTicket`, `skipTicket`, dan `cancelTicket`.
- [x] `M14-15` Pasang event dispatching `CounterStatusChanged` pada perubahan status loket di `CounterManagementController::toggleStatus`.
- [x] `M14-16` Konfigurasikan klien `window.Echo` pada `resources/js/echo.js` dengan authorizer pintar yang membedakan saluran privat staf (`/broadcasting/auth`) dan saluran tiket tamu (`/broadcasting/auth/ticket`).
- [x] `M14-17` Perbarui halaman pelacakan pelanggan `customer/track.blade.php`: langganan real-time via Echo ke `ticket.{tokenHash}`, pembaruan status dan loket instan, dengan tetap mempertahankan adaptive polling sebagai fallback andal saat offline/diskoneksi.
- [x] `M14-18` Perbarui dashboard UMKM `merchant/dashboard.blade.php` dan meja antrean `merchant/queues/index.blade.php` dengan notifikasi real-time toast dan pemantauan event loket.

## 16.5 Pengujian, Verifikasi, & Aset (P1-04)

- [x] `M14-19` Buat test suite komprehensif `tests/Feature/Broadcasting/RealtimeQueueUpdatesTest.php` mencakup 13 pengujian spesifik untuk konfigurasi, kontrak, payload, sanitasi, otorisasi token hash tamu, otorisasi saluran staf, dan integrasi siklus hidup antrean.
- [x] `M14-20` Jalankan seluruh regression test suite (315 automated tests) dengan tingkat kelulusan 100% tanpa regresi.
- [x] `M14-21` Kompilasi aset frontend via `npm run build` berhasil tanpa warning/error.

**Exit Gate 14:** Arsitektur real-time broadcasting Laravel Reverb terpasang sempurna, seluruh event tersanitasi dan aman terhadap transaksi database, saluran tamu dan staf terotorisasi dengan presisi, halaman customer dan merchant merespons pembaruan status antrean secara instan, dan seluruh 315 tests lulus 100%.

---

# 17. Milestone 15 — Email & Browser Notifications (P1-05)

## 17.1 Fondasi Notifikasi, Konfigurasi VAPID & Service Worker (P1-05)

- [x] `M15-01` Pasang dependensi `minishlink/web-push` via Composer dan buat konfigurasi `config/webpush.php`.
- [x] `M15-02` Perbarui `.env.example` dan `.env` dengan kredensial VAPID (`VAPID_PUBLIC_KEY`, `VAPID_PRIVATE_KEY`, `VAPID_SUBJECT`, `VITE_VAPID_PUBLIC_KEY`).
- [x] `M15-03` Buat artisan command `GenerateVapidKeysCommand` (`php artisan webpush:vapid`) untuk auto-generasi pasangan kunci VAPID P-256 terstandarisasi.
- [x] `M15-04` Buat service worker `public/sw.js` dengan listener event `push` dan `notificationclick` yang membuka tautan pelacakan tiket atau console UMKM.
- [x] `M15-05` Implementasikan `WebPushService` dengan deteksi konfigurasi OpenSSL otomatis pada Windows, enkripsi pesan VAPID, serta auto-pruning endpoint kedaluwarsa (HTTP 404/410).

## 17.2 Skema Database & Model (P1-05)

- [x] `M15-06` Buat migrasi penambahan kolom intake email opsional pada `queue_tickets`: `customer_email`, `customer_email_consented_at`, dan `last_notified_status` (idempotency key).
- [x] `M15-07` Buat migrasi dan model `UserNotificationPreference` (`user_id`, `email_on_new_online_ticket`, `browser_on_new_online_ticket`).
- [x] `M15-08` Buat migrasi dan model `PushSubscription` (`user_id`, `queue_ticket_id`, `endpoint`, `endpoint_hash`, `public_key`, `auth_token`, `content_encoding`) dengan cast terenkripsi.
- [x] `M15-09` Perbarui model `User` dan `QueueTicket` dengan relasi notifikasi, preferensi, serta helper status penerimaan email/push.

## 17.3 Notifikasi Mailable & Listener Event Antrean (P1-05)

- [x] `M15-10` Buat mailable queued notification `CustomerTicketStatusNotification` untuk pembaruan status antrean pelanggan (dipanggil, sedang dilayani, selesai, dilewati, dibatalkan) dengan tautan pelacakan aman, nama loket, dan zona waktu usaha.
- [x] `M15-11` Buat mailable queued notification `MerchantNewOnlineTicketNotification` untuk staf usaha saat pelanggan mendaftar tiket online baru.
- [x] `M15-12` Buat queued event listener `SendQueueNotificationListener` yang terhubung ke `QueueTicketCreated` dan `QueueTicketStatusChanged` dengan proteksi idempotensi status dan deduplikasi penerima staf.
- [x] `M15-13` Daftarkan listener pada `AppServiceProvider`.

## 17.4 Endpoint, Controller, & Antarmuka Frontend (P1-05)

- [x] `M15-14` Perbarui form pendaftaran online pelanggan `customer/form.blade.php` dan `QueueTicketController::store` dengan checkbox persetujuan eksplisit (`notify_email`) dan validasi format email opsional.
- [x] `M15-15` Implementasikan `CustomerPushSubscriptionController` (`POST /push-subscriptions/customer`, `DELETE /push-subscriptions/customer`) dengan validasi access_token tiket aktif.
- [x] `M15-16` Implementasikan `NotificationPreferenceController` (`PUT /umkm/settings/notifications`, `POST /umkm/settings/push-subscription`, `DELETE /umkm/settings/push-subscription`) untuk merchant owner dan operator.
- [x] `M15-17` Tambahkan antarmuka Web Push Notification pada halaman pelacakan pelanggan `customer/track.blade.php`.
- [x] `M15-18` Tambahkan tab "Notifikasi" pada halaman pengaturan usaha `merchant/settings/index.blade.php` untuk konfigurasi preferensi email/push dan registrasi peramban aktif.

## 17.5 Pengujian Komprehensif & Verifikasi (P1-05)

- [x] `M15-19` Buat test suite komprehensif `tests/Feature/Notifications/EmailAndBrowserNotificationsTest.php` (20 automated tests, 43 assertions) mencakup intake email, persetujuan pelanggan, idempotensi status, preferensi staf, isolasi tenant, langganan Web Push pelanggan dan merchant, serta pembersihan push subscription saat status terminal.
- [x] `M15-20` Jalankan seluruh regression test suite proyek (335 automated tests, 2,260 assertions) dengan kelulusan 100%.
- [x] `M15-21` Kompilasi aset Vite frontend via `npm run build` berhasil.

**Exit Gate 15:** Saluran notifikasi email dan browser Web Push terintegrasi penuh pada alur antrean digital pelanggan dan staf UMKM, persetujuan email pelanggan terjaga opsional dan privat, token dan kredensial terenkripsi aman, dan seluruh 335 tests lulus 100%.

---

# 18. Milestone 16 — Public Queue Display (P1-08)

## 18.1 Skema Database & Model Kredensial Display (P1-08)

- [x] `M16-01` Buat migrasi penambahan pengaturan display pada tabel `businesses`: `is_display_enabled`, `display_token`, `display_token_hash` (unik SHA-256), dan `display_token_created_at`.
- [x] `M16-02` Perbarui model `Business` dengan fillable, casting, serta method helper `isDisplayActive()`, `generateDisplayToken()`, `revokeDisplayAccess()`, dan `getDisplayUrl()`.

## 18.2 Display Service & Backend Read-Only API (P1-08)

- [x] `M16-03` Buat `DisplayService` untuk menyelesaikan unit usaha via `display_token_hash` dan memformat data publik tersanitasi bebas PII (tanpa nama pelanggan, tanpa nomor telepon, tanpa email, tanpa token privat).
- [x] `M16-04` Implementasikan eager loading relasi `activeCounters.activeTicket.service` guna mengeliminasi problem N+1 queries.
- [x] `M16-05` Implementasikan `PublicQueueDisplayController` dengan route `GET /display/{token}` (HTML TV mode) dan `GET /display/{token}/data` (JSON API) dengan proteksi `Cache-Control: no-store, no-cache, must-revalidate, max-age=0`.

## 18.3 Integrasi Real-Time Reverb & Broadcasting (P1-08)

- [x] `M16-06` Buat public broadcast event `PublicQueueDisplayUpdated` (`display.{tokenHash}`) yang mengimplementasikan `ShouldBroadcastNow` dan `ShouldDispatchAfterCommit` dengan payload minimal bebas PII.
- [x] `M16-07` Buat listener `BroadcastToPublicDisplayListener` yang mendengarkan `QueueTicketCreated`, `QueueTicketStatusChanged`, `QueueTicketCounterAssigned`, dan `CounterStatusChanged`, memicu siaran ke channel display publik secara aman.
- [x] `M16-08` Daftarkan listener pada `AppServiceProvider`.

## 18.4 Tampilan Layar TV, Fullscreen & Audio Chime (P1-08)

- [x] `M16-09` Buat view publik `display/show.blade.php` dengan tema kontras tinggi elegan (TV mode), jam digital dan tanggal lokal timezone usaha, jam realtime, kartu loket adaptif (1, 2, 3, 4+ loket), dan status eksplisit (`DIPANGGIL`, `DILAYANI`, `STANDBY`).
- [x] `M16-10` Tambahkan audio chime synthesizer bawaan via Web Audio API (`AudioContext`) tanpa dependensi aset file eksternal beserta tombol mute/unmute.
- [x] `M16-11` Tambahkan dukungan Fullscreen API dengan tombol interaktif layar penuh dan keluar layar penuh.
- [x] `M16-12` Implementasikan sinkronisasi ganda: WebSocket real-time via Laravel Echo dan adaptive polling fallback (10 detik) saat koneksi terputus.

## 18.5 Manajemen Display Merchant (Owner-Only) (P1-08)

- [x] `M16-13` Implementasikan `DisplayManagementController` (`GET /umkm/display`, `POST /umkm/display/toggle`, `POST /umkm/display/generate`, `DELETE /umkm/display/revoke`) yang diproteksi khusus peran Owner (`business.role:owner`).
- [x] `M16-14` Buat antarmuka `merchant/display/index.blade.php` dengan display overview, penyalinan tautan, pratinjau live data, aktivasi/nonaktivasi, rotasi token, dan pencabutan akses.
- [x] `M16-15` Integrasikan navigasi "Layar TV" pada sidebar pemilik dan tab pengaturan usaha.

## 18.6 Pengujian Komprehensif & Verifikasi (P1-08)

- [x] `M16-16` Buat test suite komprehensif `tests/Feature/Display/DisplayManagementTest.php` dan `tests/Feature/Display/PublicQueueDisplayTest.php` (31 tests, 115 assertions) mencakup otorisasi owner/operator, rotasi token, pencabutan token, penolakan token invalid, privasi PII, multi-loket simultan, siaran real-time, dan pembersihan status.
- [x] `M16-17` Jalankan seluruh regression test suite proyek (356 automated tests, 2.342 assertions) dengan kelulusan 100%.
- [x] `M16-18` Kompilasi aset Vite frontend via `npm run build` berhasil.

**Exit Gate 16:** Fitur Public Queue Display (Layar TV Antrean) terimplementasi penuh dengan dukungan multi-loket real-time Reverb, fallback polling andal, audio chime bawaan, manajemen owner aman berbasis rotasi token kriptografis, dan seluruh 356 tests lulus 100%.

---

# 19. Milestone 17 — Customer Reviews & Feedback (P1-09)

## 19.1 Skema Database & Model Customer Review (P1-09)

- [x] `M17-01` Buat migrasi tabel `customer_reviews`: `id`, `business_id` (foreign key cascade), `queue_ticket_id` (foreign key unik cascade), `service_id` (foreign key cascade), `rating` (unsigned tiny integer 1-5), `comment` (text nullable), dan timestamps.
- [x] `M17-02` Buat model `CustomerReview` dengan mass assignable, casting rating ke integer, dan relasi `business()`, `queueTicket()`, `service()`.
- [x] `M17-03` Perbarui model `QueueTicket` dengan relasi `review()` (`HasOne`) dan helper `hasReview()`.
- [x] `M17-04` Perbarui model `Business` dengan relasi `customerReviews()` (`HasMany`), helper `averageRating()`, dan `totalReviewsCount()`.
- [x] `M17-05` Buat `CustomerReviewFactory` untuk mendukung otomasi pengujian database.

## 19.2 Pengambilan Ulasan Pelanggan & Keamanan (P1-09)

- [x] `M17-06` Implementasikan `CustomerReviewController::store` dengan validasi lifecycle ketat: ulasan hanya dapat dikirimkan untuk tiket dengan status terminal `selesai` (`STATUS_SELESAI`). Tiket `menunggu`, `dipanggil`, `sedang_dilayani`, `dilewati`, atau `dibatalkan` ditolak dengan HTTP 422.
- [x] `M17-07` Implementasikan proteksi idempotensi dan duplikasi: tiket antrean hanya dapat diulas satu kali (HTTP 422 jika sudah diulas).
- [x] `M17-08` Terapkan sanitasi anti-XSS (`strip_tags`) dan validasi panjang komentar maksimal 1000 karakter serta rating 1-5 bintang.
- [x] `M17-09` Terapkan rate limiting `throttle:15,1` dan pencatatan riwayat audit `ActivityLog` (`customer_review_submitted`).
- [x] `M17-10` Integrasikan formulir ulasan interaktif (selector 5 bintang Alpine, textarea komentar, dan penghitung karakter) pada halaman pelacakan pelanggan `resources/views/customer/track.blade.php`. Tampilkan ringkasan ulasan dan ucapan terima kasih jika tiket telah diulas.

## 19.3 Pemantauan & Analisis Ulasan Merchant (P1-09)

- [x] `M17-11` Implementasikan `Merchant\CustomerReviewController::index` dengan kalkulasi KPI analitik: rata-rata rating, total ulasan, total tiket selesai, tingkat respon ulasan, dan distribusi rating bintang 1-5.
- [x] `M17-12` Terapkan filter interaktif berdasarkan rating (1-5), jenis layanan (`service_id`), dan pencarian teks (`search`), serta paginasi daftar ulasan.
- [x] `M17-13` Terapkan isolasi tenant ketat: merchant hanya dapat melihat ulasan dari unit usaha miliknya sendiri.
- [x] `M17-14` Buat antarmuka `merchant/reviews/index.blade.php` dan tambahkan menu navigasi "Ulasan" pada navigasi staf dan pemilik UMKM.

## 19.4 Pengujian Komprehensif & Verifikasi (P1-09)

- [x] `M17-15` Buat test suite komprehensif `tests/Feature/Reviews/CustomerReviewTest.php` (17 tests, 53 assertions) mencakup intake ulasan, validasi rating/komentar, proteksi duplikasi, lifecycle enforcement, sanitasi XSS, otorisasi merchant, filter, dan isolasi tenant.
- [x] `M17-16` Jalankan seluruh regression test suite proyek (373 automated tests, 2.399 assertions) dengan kelulusan 100%.
- [x] `M17-17` Kompilasi aset Vite frontend via `npm run build` berhasil tanpa kendala.

**Exit Gate 17:** Fitur Customer Reviews & Feedback (`P1-09`) terimplementasi penuh dengan skema database unik, penegakan siklus hidup selesai, anti-XSS, rate limiting, visualisasi analitik merchant, dan seluruh 373 automated tests lulus 100%.

---

# 20. Milestone P1 — Pengembangan setelah MVP

Jangan mulai sebelum MVP selesai dan mendapat persetujuan.

- [x] `P1-01` Multi-staf dan relasi banyak user ke business.
- [x] `P1-02` Role dan permission operator/staf.
- [x] `P1-03` Multi-loket dan penugasan antrean.
- [x] `P1-04` Laravel Reverb/WebSocket untuk update status real-time.
- [x] `P1-05` Notifikasi email/browser sesuai kanal yang dipilih.
- [x] `P1-06` Ekspor CSV.
- [x] `P1-07` Ekspor PDF.
- [x] `P1-08` Display antrean publik.
- [x] `P1-09` Ulasan pelanggan.
- [ ] `P1-10` Estimasi waktu tunggu berbasis data historis jika kualitas data mencukupi.

# 18. Milestone P2 — Pengembangan lanjutan

- [ ] `P2-01` Multi-cabang.
- [ ] `P2-02` Integrasi WhatsApp API.
- [ ] `P2-03` Integrasi POS.
- [ ] `P2-04` Prediksi antrean berbasis AI/ML.
- [ ] `P2-05` Paket langganan dan billing.
- [ ] `P2-06` Direktori/marketplace UMKM.
- [ ] `P2-07` Printer tiket fisik.

---

# 18. Cross-cutting checklist — berlaku untuk semua fitur

Agent harus memeriksa bagian ini pada setiap halaman atau modul, bukan hanya pada tahap akhir.

## 15.1 UX dan design consistency

- [ ] Menggunakan token dan komponen dari `AntreIn-design.md`.
- [ ] Heading, typography, spacing, radius, border, dan shadow konsisten.
- [ ] Status selalu ditampilkan dengan teks dan indikator visual, bukan warna saja.
- [ ] Aksi utama memiliki hierarchy yang jelas.
- [ ] Aksi destruktif atau berdampak besar memiliki konfirmasi.
- [ ] Form memiliki label, helper text jika perlu, validasi, dan pesan error dekat field.
- [ ] Loading, empty, error, success, disabled, dan no-results states tersedia.
- [ ] Tidak menampilkan fitur P1/P2 seolah-olah sudah aktif.
- [ ] Tidak menampilkan statistik contoh sebagai data aktual.

## 15.2 Responsive web

- [ ] Admin/UMKM menggunakan desktop-first layout dan tetap bisa digunakan pada tablet/mobile.
- [ ] Customer menggunakan responsive public website; bukan native mobile app.
- [ ] Sidebar Admin/UMKM berubah menjadi drawer/icon rail pada breakpoint yang sesuai.
- [ ] Tabel panjang berubah menjadi kartu atau tampilan kolom prioritas pada layar kecil.
- [ ] Tombol aksi inti tetap mudah dijangkau.
- [ ] Tidak ada horizontal overflow yang mengganggu penggunaan.

## 15.3 Data integrity

- [ ] Semua perubahan status divalidasi di backend.
- [ ] Semua operasi nomor tiket berjalan dalam transaksi.
- [ ] Nomor tiket unik per UMKM/periode yang ditetapkan.
- [ ] Sumber tiket hanya `ONLINE` atau `WALK_IN` pada MVP.
- [ ] Tiket Selesai, Dilewati, dan Dibatalkan dibedakan dengan benar.
- [ ] Timestamp tidak dibuat-buat atau diisi nol untuk data tidak tersedia.
- [ ] Riwayat tiket tetap valid setelah layanan dinonaktifkan.

## 15.4 Security and privacy

- [ ] Authorization diterapkan di server.
- [ ] Tenant isolation diterapkan pada setiap query/action.
- [ ] Token pelanggan tidak mudah ditebak.
- [ ] Data pribadi hanya diminta dan ditampilkan sesuai kebutuhan.
- [ ] Admin monitoring tidak menyediakan aksi operasional tiket.
- [ ] Tidak ada secret atau data sensitif di Git.

---

# 16. Definisi selesai (Definition of Done)

Satu task dapat dicentang `[x]` hanya jika seluruh kondisi relevan terpenuhi:

1. Implementasi sesuai requirement/FR yang dirujuk.
2. Acceptance criteria berhasil diverifikasi.
3. Validasi backend dan otorisasi tersedia.
4. Komponen mengikuti `AntreIn-design.md`.
5. Responsive behavior telah diperiksa pada breakpoint relevan.
6. Loading/empty/error/success state yang relevan tersedia.
7. Automated test atau test manual terdokumentasi telah dijalankan.
8. Tidak ada bug kritis yang diketahui.
9. Dokumentasi diperbarui jika ada perubahan setup, route, schema, atau perilaku.
10. Agent melaporkan file yang berubah, command/test yang dijalankan, hasil, serta hal yang belum selesai.

## Definition of Done MVP

- [ ] Seluruh task P0 selesai atau memiliki pengecualian yang disetujui pemilik produk.
- [ ] Skenario end-to-end pelanggan online sampai tiket selesai lulus.
- [ ] Skenario end-to-end walk-in sampai tiket selesai lulus.
- [ ] FIFO gabungan online/walk-in terbukti benar.
- [ ] Concurrent ticket creation tidak menghasilkan nomor duplikat.
- [ ] Concurrent call-next tidak memproses tiket yang sama dua kali.
- [ ] Semua transisi status yang tidak valid ditolak backend.
- [ ] Data UMKM terisolasi.
- [ ] Admin hanya memonitor antrean dan tidak mengubah status operasional.
- [ ] Pelanggan dapat membuka tiket hanya dengan token yang valid.
- [ ] Statistik dan laporan dapat diverifikasi terhadap data uji.
- [ ] Semua area berfungsi sebagai responsive website.
- [ ] README setup lokal, migration, seeder, dan instruksi deployment tersedia.

---

# 17. Format laporan agent setiap menyelesaikan milestone

Agent harus menggunakan format laporan berikut agar progres mudah ditinjau:

```text
## Milestone: [nama dan ID]
Status: Selesai / Sebagian / Terblokir

### Task selesai
- [ID] Nama task

### File utama yang dibuat/diubah
- path/to/file

### Database / migration
- Perubahan schema dan relasi (jika ada)

### Test yang dijalankan
- Command:
- Hasil:

### Keputusan yang dibuat
- Keputusan dan alasan
- Referensi PRD/Design System/ADR

### Blocker / Open Questions
- Daftar item yang membutuhkan keputusan

### Langkah berikutnya
- Task ID berikutnya
```

---

# 18. Urutan kerja singkat untuk agent

Ikuti urutan berikut. Jangan melompat ke UI penuh sebelum domain dan aturan antrean siap.

1. **Audit repository dan kunci keputusan produk.** Selesaikan Gate 0.
2. **Finalisasi diagram, state machine, ERD, dan data dictionary.** Selesaikan M1.
3. **Setup Laravel serta design system reusable.** Selesaikan M2.
4. **Bangun database, authentication, authorization, dan onboarding.** Selesaikan M3.
5. **Bangun profil UMKM, jam operasional, kategori, dan layanan.** Selesaikan M4.
6. **Bangun Core Queue Engine dan uji concurrency.** Selesaikan M5.
7. **Bangun Customer Interface.** Selesaikan M6.
8. **Bangun Dashboard dan operasional UMKM.** Selesaikan M7.
9. **Bangun dashboard dan manajemen Admin.** Selesaikan M8.
10. **Bangun laporan dan audit log.** Selesaikan M9.
11. **Lakukan security, functional, responsive, dan release testing.** Selesaikan M10.
12. **Setelah MVP disetujui, lanjutkan P1 lalu P2.**

> **Prinsip utama:** selesaikan sistem antrean inti yang benar, aman, dan konsisten terlebih dahulu. Jangan mengorbankan integritas nomor antrean, FIFO, status tiket, atau isolasi data demi menyelesaikan tampilan lebih cepat.

---

**Akhir dokumen — AntreIn Development Tasklist v1.0**
