# PRODUCT REQUIREMENTS DOCUMENT (PRD)

# AntreIn — Platform Manajemen Antrean Digital untuk UMKM

| Informasi | Keterangan |
|---|---|
| Produk | AntreIn |
| Dokumen | Product Requirements Document (PRD) |
| Versi PRD | 1.0 |
| Status | Draft baseline untuk pengembangan MVP |
| Tanggal | 9 November 2026 |
| Jenis Produk | Responsive Web Application |
| Target Pengguna | Admin AntreIn, Pemilik UMKM, dan Pelanggan |
| Backend yang direncanakan | Laravel |
| Database yang direncanakan | MySQL |
| Frontend yang direncanakan | Blade, Livewire, Tailwind CSS |
| Dokumen pendukung | Spesifikasi Fungsional Website AntreIn v1.0; `AntreIn-design.md` |

> **Tujuan dokumen:** Menjadi acuan product, UI/UX, backend, frontend, database, dan QA selama pengembangan AntreIn. Dokumen ini mendefinisikan masalah produk, pengguna, ruang lingkup, kebutuhan fungsional, aturan bisnis, kriteria penerimaan, batasan MVP, serta keputusan yang masih perlu dikonfirmasi.

---

## 1. Ringkasan Produk

### 1.1 Deskripsi Produk

AntreIn adalah platform manajemen antrean digital berbasis website yang membantu UMKM mengelola pelanggan yang mengambil antrean secara online maupun pelanggan yang datang langsung (*walk-in*) dalam satu sistem.

AntreIn menyediakan tiga area penggunaan yang saling terhubung melalui satu backend dan database:

1. **Admin AntreIn:** mengelola platform, UMKM, akun pengguna, kategori usaha, serta memantau aktivitas antrean.
2. **Pemilik UMKM:** mengatur profil usaha, jam operasional, layanan, antrean, dan laporan operasional.
3. **Pelanggan:** melihat informasi UMKM, memilih layanan, mengambil nomor antrean, dan memantau status tiket tanpa membuat akun.

AntreIn merupakan **satu responsive web application**, bukan tiga aplikasi terpisah dan bukan aplikasi Android/iOS. Masing-masing area memiliki layout dan navigasi sesuai kebutuhan perannya.

### 1.2 Masalah yang Ingin Diselesaikan

UMKM dapat mengalami kesulitan dalam mengatur urutan pelanggan ketika pencatatan antrean masih dilakukan secara manual atau melalui beberapa media terpisah. Pelanggan juga tidak selalu memiliki informasi yang jelas mengenai nomor, posisi, dan status antreannya.

AntreIn ditujukan untuk mengurangi masalah tersebut dengan menyediakan:

- Satu daftar antrean yang menggabungkan sumber online dan walk-in.
- Proses pemanggilan dan perubahan status yang terstruktur.
- Halaman pelanggan untuk mengambil dan memantau tiket.
- Pengaturan layanan dan jam operasional.
- Ringkasan serta laporan operasional dasar.
- Pengelolaan platform oleh Admin AntreIn.

### 1.3 Nilai Utama Produk

| Pengguna | Nilai yang diberikan |
|---|---|
| Admin AntreIn | Visibilitas dan kendali administratif terhadap data platform dan UMKM. |
| Pemilik UMKM | Pengelolaan antrean dan konfigurasi usaha melalui satu dashboard. |
| Pelanggan | Pengambilan antrean dan pemantauan tiket secara mandiri tanpa registrasi. |

### 1.4 Visi Produk

Menjadi platform antrean digital yang mudah digunakan UMKM dan mudah diakses pelanggan, dengan proses pelayanan yang lebih tertata dan transparan.

---

## 2. Tujuan Produk dan Indikator Keberhasilan

### 2.1 Product Goals

| ID | Tujuan | Hasil yang diharapkan |
|---|---|---|
| PG-01 | Mengintegrasikan antrean online dan walk-in. | Kedua sumber tiket muncul dalam satu urutan antrean per UMKM. |
| PG-02 | Menyederhanakan operasional UMKM. | Pemilik dapat menambah, memanggil, memproses, dan menyelesaikan antrean dari dashboard. |
| PG-03 | Memberikan akses antrean kepada pelanggan. | Pelanggan dapat mengambil antrean dan membuka kembali tiket tanpa login. |
| PG-04 | Memberikan informasi status yang konsisten. | Perubahan status di dashboard tercermin pada halaman pemantauan pelanggan. |
| PG-05 | Mendukung evaluasi operasional. | Pemilik dapat melihat metrik dasar berdasarkan data antrean dan timestamp. |
| PG-06 | Menyediakan kontrol platform. | Admin dapat mengelola UMKM, pengguna, kategori, dan memonitor antrean. |

### 2.2 Indikator Keberhasilan MVP

MVP dinyatakan memenuhi tujuan produk apabila skenario berikut berhasil diuji:

1. UMKM dapat menyelesaikan onboarding minimum dan membuat setidaknya satu layanan aktif.
2. Pelanggan dapat mengambil antrean online tanpa membuat akun.
3. Operator dapat menambahkan antrean walk-in.
4. Tiket online dan walk-in memperoleh nomor unik dan masuk ke urutan FIFO yang sama.
5. Operator dapat memproses status tiket melalui alur yang valid.
6. Pelanggan dapat melihat status tiket terkini menggunakan tautan bertoken.
7. Data dan statistik satu UMKM tidak dapat diakses atau diubah oleh UMKM lain.
8. Admin dapat mengelola data platform dan melihat antrean tanpa mengambil alih proses pelayanan UMKM.
9. Laporan dasar menggunakan definisi metrik dan timestamp yang konsisten.
10. Fitur inti dapat digunakan pada desktop, tablet, dan ponsel melalui browser.

**Catatan pengukuran:** Target numerik seperti jumlah UMKM uji coba, waktu respons maksimum, tingkat keberhasilan transaksi, atau jumlah pengguna aktif belum ditetapkan dalam spesifikasi sumber. Nilai target tersebut perlu disepakati sebelum pengujian penerimaan akhir.

---

## 3. Prinsip Produk

1. **Sederhana:** alur utama dapat digunakan oleh UMKM dengan satu operator.
2. **Terintegrasi:** tiket online dan walk-in masuk ke satu antrean utama pada MVP.
3. **Efisien:** tindakan operasional utama mudah ditemukan dan tidak membutuhkan banyak langkah.
4. **Transparan:** pelanggan dapat memantau status tiket secara mandiri.
5. **Aman:** data operasional dipisahkan berdasarkan UMKM dan hak akses.
6. **Responsif:** seluruh area merupakan website responsif pada desktop, tablet, dan ponsel.
7. **Bertahap:** fitur inti diprioritaskan sebelum integrasi dan kemampuan lanjutan.
8. **Berbasis data:** metrik hanya ditampilkan jika sumber data dan timestamp memadai.

---

## 4. Pengguna, Persona, dan Hak Akses

### 4.1 Persona Utama

#### A. Admin AntreIn

**Peran:** pengelola platform.

**Kebutuhan:**
- Melihat ringkasan aktivitas platform.
- Mengelola daftar UMKM dan akun pengguna.
- Mengelola kategori usaha.
- Memantau aktivitas antrean lintas UMKM.
- Mengubah status administratif UMKM dan pengguna sesuai kewenangan.

**Batasan:** Admin memantau aktivitas antrean. Admin bukan operator harian yang mengubah status pelayanan tiket milik UMKM.

#### B. Pemilik UMKM

**Peran:** pemilik akun dan pengelola operasional usaha pada MVP.

**Kebutuhan:**
- Membuat akun dan menyiapkan profil usaha.
- Mengatur layanan dan jam operasional.
- Mengelola antrean online dan walk-in.
- Melihat ringkasan dan laporan operasional.
- Membagikan tautan dan QR antrean.

**Batasan:** Pada MVP, satu akun pemilik mengelola satu UMKM dan satu operator aktif. Multi-staf dan pembagian hak akses disiapkan untuk tahap berikutnya.

#### C. Pelanggan

**Peran:** pengguna publik yang mengambil dan memantau antrean.

**Kebutuhan:**
- Menemukan halaman UMKM melalui tautan atau QR.
- Melihat status usaha dan layanan.
- Mengambil nomor antrean dengan data minimum.
- Melihat nomor, posisi, dan status antrean.
- Membuka kembali tiket melalui tautan khusus.

**Batasan:** Pelanggan tidak perlu membuat akun atau login pada MVP.

### 4.2 Matriks Hak Akses

| Fungsi | Admin | Pemilik UMKM | Pelanggan |
|---|:---:|:---:|:---:|
| Login | Ya | Ya | Tidak |
| Registrasi | Tidak melalui publik | Ya | Tidak |
| Dashboard platform | Ya | Tidak | Tidak |
| Dashboard operasional usaha | Tidak | Ya, milik sendiri | Tidak |
| Mengelola profil UMKM | Administratif | Ya, milik sendiri | Tidak |
| Mengelola kategori UMKM | Ya | Tidak | Tidak |
| Mengelola layanan | Tidak | Ya, milik sendiri | Tidak |
| Mengelola jam operasional | Tidak | Ya, milik sendiri | Tidak |
| Mengelola antrean usaha | Monitoring saja | Ya, milik sendiri | Tidak |
| Mengambil antrean | Tidak | Tidak | Ya |
| Melihat tiket | Monitoring sesuai kewenangan | Tiket usaha sendiri | Tiket dengan token valid |
| Melihat laporan usaha | Tidak | Ya, milik sendiri | Tidak |
| Mengelola akun platform | Ya | Akun sendiri | Tidak |
| Pengaturan platform | Ya | Tidak | Tidak |

---

## 5. Cakupan Produk dan Prioritas

### 5.1 In Scope — MVP (P0)

#### Admin
- Login Admin.
- Dashboard platform dasar.
- Manajemen UMKM.
- Manajemen pengguna.
- Monitoring antrean.
- Manajemen kategori usaha.
- Pengaturan platform dasar.

#### UMKM
- Registrasi, login, logout, dan lupa password.
- Onboarding usaha.
- Profil usaha.
- Jam operasional dan status Buka/Tutup/Jeda.
- CRUD layanan.
- Dashboard usaha.
- Antrean online dan walk-in pada satu daftar.
- Tambah antrean manual.
- Panggil berikutnya, panggil ulang, mulai melayani, selesaikan, dan lewati.
- Detail dan riwayat antrean.
- Laporan operasional dasar.
- Tautan publik dan QR antrean.
- Pengaturan akun dasar.

#### Pelanggan
- Halaman informasi UMKM.
- Daftar layanan aktif.
- Form pengambilan antrean.
- Pembuatan tiket dan nomor otomatis.
- Halaman konfirmasi tiket.
- Halaman pemantauan tiket menggunakan token unik.
- Pembaruan status melalui polling.

#### Kebutuhan Sistem
- Autentikasi dan otorisasi.
- Isolasi data berdasarkan UMKM.
- Validasi frontend dan backend.
- Pencegahan duplikasi tiket dan nomor.
- Pencatatan waktu perubahan status.
- Loading, empty, success, error, dan disabled states.
- Responsive web layout.

### 5.2 Out of Scope — MVP

Fitur berikut tidak dikembangkan pada versi MVP:

- Pembayaran digital.
- Kasir atau POS.
- Inventaris dan stok.
- Akuntansi atau laporan keuangan.
- Penggajian karyawan.
- Multi-cabang.
- Marketplace atau direktori UMKM.
- Reservasi berdasarkan tanggal dan jam.
- Prediksi antrean berbasis AI/ML.
- Integrasi WhatsApp otomatis.
- Printer tiket fisik.
- Multi-loket dan penugasan operator.
- Sistem langganan dan paket berbayar.

### 5.3 Roadmap Setelah MVP

| Prioritas | Fitur | Tujuan |
|---|---|---|
| P1 | Multi-staf | Mendukung lebih dari satu pengguna operasional. |
| P1 | Multi-loket | Mendukung beberapa titik pelayanan. |
| P1 | Hak akses operator | Memisahkan kewenangan pemilik dan staf. |
| P1 | WebSocket / Laravel Reverb | Memperbarui status secara real-time. |
| P1 | Notifikasi email/browser | Memberi pemberitahuan melalui kanal tambahan. |
| P1 | Ekspor CSV/PDF | Memudahkan penggunaan laporan di luar aplikasi. |
| P1 | Display antrean publik | Menampilkan nomor yang dipanggil pada layar publik. |
| P1 | Ulasan pelanggan | Mengumpulkan umpan balik setelah pelayanan. |
| P1 | Estimasi waktu historis | Meningkatkan kualitas estimasi berdasarkan data. |
| P2 | Multi-cabang | Mengelola beberapa lokasi usaha. |
| P2 | WhatsApp API | Mengirim pemberitahuan melalui WhatsApp. |
| P2 | Integrasi POS | Menghubungkan sistem antrean dengan kasir. |
| P2 | Prediksi AI/ML | Menghasilkan prediksi waktu tunggu berbasis data. |
| P2 | Paket langganan | Mendukung model bisnis berlangganan. |
| P2 | Direktori UMKM | Menemukan usaha melalui katalog AntreIn. |

---

## 6. Informasi Arsitektur Produk dan Navigasi

### 6.1 Bentuk Aplikasi

AntreIn dikembangkan sebagai satu *monolithic responsive web application* menggunakan satu proyek Laravel dan satu database utama. Area Admin, UMKM, dan Pelanggan memiliki route, layout, serta middleware/otorisasi berbeda.

Tidak ada aplikasi mobile native terpisah dalam ruang lingkup MVP.

### 6.2 Peta Navigasi Admin

| Menu | Subhalaman/Fungsi |
|---|---|
| Dashboard | Statistik platform, grafik, aktivitas terbaru |
| Manajemen UMKM | Daftar UMKM, pencarian, filter, detail, ubah status |
| Manajemen Pengguna | Daftar akun, detail, filter jenis akun, aktif/nonaktif |
| Monitoring Antrean | Daftar tiket lintas UMKM, pencarian, filter, detail baca-saja |
| Kategori UMKM | Daftar kategori, tambah, edit, aktif/nonaktif |
| Pengaturan Platform | Identitas dan konfigurasi dasar platform |

### 6.3 Peta Navigasi UMKM

| Menu | Subhalaman/Fungsi |
|---|---|
| Beranda | Statistik hari ini, antrean aktif, aksi cepat, ringkasan operasional |
| Antrean | Antrean aktif, Now Serving, tambah manual, detail, riwayat |
| Layanan | Daftar layanan, tambah, edit, aktif/nonaktif |
| Laporan | Filter periode, metrik, grafik, ringkasan per layanan |
| Pengaturan | Profil usaha, jam operasional, tautan & QR, notifikasi yang didukung, akun |

### 6.4 Peta Halaman Pelanggan

| Halaman | Route yang direncanakan | Fungsi |
|---|---|---|
| Informasi UMKM | `/q/{slug}` | Profil, status usaha, dan daftar layanan |
| Pengambilan antrean | `/q/{slug}/take-queue` | Form data pelanggan dan konfirmasi layanan |
| Konfirmasi tiket | Hasil pembuatan tiket | Nomor tiket dan tautan pemantauan |
| Pemantauan tiket | `/ticket/{token}` | Status, posisi antrean, dan detail tiket |

### 6.5 Route Utama yang Direncanakan

| Area | Route |
|---|---|
| Landing page | `/` |
| Login UMKM | `/login` |
| Registrasi UMKM | `/register` |
| Dashboard UMKM | `/umkm/dashboard` |
| Antrean UMKM | `/umkm/queues` |
| Layanan UMKM | `/umkm/services` |
| Laporan UMKM | `/umkm/reports` |
| Pengaturan UMKM | `/umkm/settings` |
| Dashboard Admin | `/admin/dashboard` |
| Manajemen UMKM | `/admin/businesses` |
| Manajemen Pengguna | `/admin/users` |
| Monitoring Antrean | `/admin/queues` |
| Halaman publik UMKM | `/q/{slug}` |
| Form antrean | `/q/{slug}/take-queue` |
| Halaman tiket | `/ticket/{token}` |

Route final dapat disesuaikan selama prinsip pemisahan akses dan fungsi tetap dipenuhi.

---

## 7. Kebutuhan Fungsional dan Acceptance Criteria

Kebutuhan menggunakan ID agar mudah dirujuk pada issue tracker, test case, dan pull request.

### 7.1 Autentikasi dan Onboarding UMKM

#### FR-AUTH-01 — Registrasi UMKM

**Prioritas:** P0  
**Aktor:** Pemilik UMKM

Pemilik dapat membuat akun melalui form registrasi dengan nama pemilik, email, password, dan konfirmasi password.

**Acceptance criteria:**
- Form menerima nama, email, password, dan konfirmasi password.
- Sistem menolak email yang sudah digunakan.
- Sistem memvalidasi kekuatan/ketentuan password sesuai konfigurasi aplikasi.
- Sistem menolak konfirmasi password yang tidak sama.
- Password tidak disimpan sebagai teks biasa.
- Setelah registrasi berhasil, pemilik dapat masuk ke proses onboarding usaha.
- Registrasi publik tidak menyediakan pembuatan akun Admin.

#### FR-AUTH-02 — Login dan Logout UMKM

**Prioritas:** P0  
**Aktor:** Pemilik UMKM

Pemilik dapat masuk dan keluar dari dashboard.

**Acceptance criteria:**
- Login menggunakan email dan password yang valid.
- Kredensial tidak valid menghasilkan pesan kesalahan yang aman dan jelas.
- Pengguna yang belum login tidak dapat membuka route UMKM yang dilindungi.
- Logout mengakhiri sesi autentikasi.
- Pengguna yang akunnya dinonaktifkan tidak dapat mengakses area yang dilindungi.

#### FR-AUTH-03 — Login Admin

**Prioritas:** P0  
**Aktor:** Admin

Admin dapat login melalui area administratif. Pembuatan akun Admin tidak tersedia melalui registrasi publik.

**Acceptance criteria:**
- Hanya akun dengan role Admin yang dapat membuka route Admin.
- Akun UMKM tidak memperoleh akses Admin dengan mengganti URL.
- Akses yang tidak berhak ditolak di backend.

#### FR-AUTH-04 — Lupa Password

**Prioritas:** P0

Sistem menyediakan mekanisme pemulihan password untuk akun UMKM. Kanal dan konfigurasi pengiriman reset password mengikuti infrastruktur email yang disiapkan.

**Acceptance criteria:**
- Pengguna dapat meminta tautan reset password.
- Token reset memiliki masa berlaku dan hanya dapat digunakan sesuai mekanisme keamanan.
- Sistem tidak membocorkan apakah alamat email tertentu terdaftar melalui respons yang tidak aman.

#### FR-ONB-01 — Onboarding Usaha

**Prioritas:** P0  
**Aktor:** Pemilik UMKM

Setelah akun dibuat, pemilik diarahkan untuk melengkapi profil, kategori, jam operasional, minimal satu layanan, dan tautan antrean.

**Acceptance criteria:**
- Checklist onboarding menampilkan langkah yang sudah dan belum selesai.
- Pemilik dapat berpindah ke area aplikasi lain tanpa dipaksa menyelesaikan semua langkah sekaligus.
- Antrean online belum dapat dibuka sebelum konfigurasi minimum terpenuhi.
- Kondisi onboarding ditampilkan pada Beranda.

### 7.2 Modul Admin

#### FR-ADM-01 — Dashboard Admin

**Prioritas:** P0  
**Aktor:** Admin

Dashboard menampilkan ringkasan total UMKM, UMKM aktif/nonaktif, total akun pengguna, total antrean, dan antrean selesai.

**Acceptance criteria:**
- Angka statistik berasal dari data tersimpan.
- Admin dapat memilih periode untuk data yang memiliki cakupan periode.
- Grafik menampilkan tren pendaftaran UMKM, jumlah antrean, distribusi status, dan aktivitas sesuai ketersediaan data.
- Aktivitas terbaru menampilkan pendaftaran UMKM, perubahan status, akun baru, dan aktivitas administratif penting.
- Kondisi tanpa data ditampilkan sebagai empty state, bukan angka yang menyesatkan.

#### FR-ADM-02 — Daftar dan Pencarian UMKM

**Prioritas:** P0  
**Aktor:** Admin

Admin dapat melihat dan mencari UMKM yang terdaftar.

**Acceptance criteria:**
- Daftar menampilkan nama usaha, pemilik, kategori, alamat, tanggal daftar, jumlah layanan, jumlah antrean, dan status.
- Admin dapat mencari nama usaha.
- Admin dapat memfilter kategori, status, dan periode pendaftaran.
- Daftar mendukung pagination.

#### FR-ADM-03 — Detail UMKM

**Prioritas:** P0  
**Aktor:** Admin

Admin dapat melihat profil, pemilik, kategori, jadwal, layanan, statistik antrean, aktivitas, dan status UMKM.

**Acceptance criteria:**
- Detail mengacu pada satu UMKM yang dipilih.
- Admin dapat membuka detail dari daftar UMKM.
- Data historis tetap dapat dilihat ketika UMKM dinonaktifkan.

#### FR-ADM-04 — Aktivasi dan Penonaktifan UMKM

**Prioritas:** P0  
**Aktor:** Admin

Admin dapat mengaktifkan atau menonaktifkan/menangguhkan UMKM.

**Acceptance criteria:**
- Aksi penonaktifan meminta konfirmasi.
- Status berubah hanya setelah penyimpanan berhasil.
- Penonaktifan mencegah pembuatan antrean online baru untuk UMKM terkait.
- Tiket dan riwayat yang sudah ada tidak dihapus.
- Perubahan dicatat ke activity log.
- Ketentuan penyelesaian tiket aktif saat UMKM dinonaktifkan perlu mengikuti keputusan produk yang dikunci sebelum rilis.

#### FR-ADM-05 — Manajemen Pengguna

**Prioritas:** P0  
**Aktor:** Admin

Admin dapat melihat dan mengelola akun Admin serta Pemilik UMKM.

**Acceptance criteria:**
- Daftar menampilkan nama, email, jenis akun, UMKM terkait, tanggal pendaftaran, dan status.
- Admin dapat mencari berdasarkan nama/email.
- Admin dapat memfilter jenis akun.
- Admin dapat membuka detail akun.
- Admin dapat mengaktifkan atau menonaktifkan akun.
- Hubungan akun dengan UMKM ditampilkan jika ada.

#### FR-ADM-06 — Monitoring Antrean

**Prioritas:** P0  
**Aktor:** Admin

Admin dapat memantau antrean dari seluruh UMKM tanpa mengubah status operasional tiket.

**Acceptance criteria:**
- Daftar menampilkan nomor tiket, UMKM, layanan, label pelanggan, sumber, waktu dibuat, dan status.
- Admin dapat mencari nomor tiket atau nama UMKM.
- Admin dapat memfilter status, sumber antrean, dan tanggal.
- Admin dapat melihat detail tiket.
- Kontrol perubahan status operasional tidak tersedia pada area monitoring Admin.

#### FR-ADM-07 — Kategori UMKM

**Prioritas:** P0  
**Aktor:** Admin

Admin dapat menambah, mengubah, mencari, mengaktifkan, dan menonaktifkan kategori usaha.

**Acceptance criteria:**
- Kategori memiliki nama, deskripsi, dan status.
- Kategori aktif tersedia saat UMKM memilih kategori.
- Kategori nonaktif tidak ditawarkan untuk pilihan baru.
- Kategori yang telah digunakan tidak dihapus permanen tanpa mekanisme penggantian yang jelas.

#### FR-ADM-08 — Pengaturan Platform

**Prioritas:** P0 dasar

Admin dapat mengelola identitas dan konfigurasi dasar AntreIn.

**Acceptance criteria:**
- Admin dapat melihat dan mengubah nama/identitas platform, logo, informasi kontak, dan konfigurasi dasar yang disepakati.
- Perubahan hanya berlaku setelah tersimpan.
- Kegagalan penyimpanan menampilkan pesan yang jelas.

### 7.3 Modul UMKM — Dashboard dan Operasional

#### FR-MER-01 — Dashboard UMKM

**Prioritas:** P0  
**Aktor:** Pemilik UMKM

Dashboard menampilkan ringkasan kondisi usaha dan antrean hari ini.

**Acceptance criteria:**
- Header menampilkan nama usaha, tanggal aktif, dan status operasional.
- Statistik menampilkan total, menunggu, dipanggil, sedang dilayani, selesai, serta dilewati/dibatalkan.
- Semua statistik antrean harian menggunakan periode yang sama.
- Antrean aktif menampilkan nomor tiket, label pelanggan, layanan, sumber, status, dan waktu masuk.
- Tersedia tautan menuju daftar antrean lengkap.
- Aksi cepat mengarah ke kelola antrean, tambah manual, tambah layanan, atau jam operasional.
- Insight rata-rata waktu tunggu dan layanan terpopuler hanya tampil jika data memadai.
- Loading, empty, onboarding, usaha tutup/jeda, dan error states ditangani.

#### FR-MER-02 — Daftar Antrean

**Prioritas:** P0  
**Aktor:** Pemilik UMKM

Pemilik dapat melihat antrean online dan walk-in dalam satu daftar.

**Acceptance criteria:**
- Daftar menampilkan nomor, nama/label, layanan, sumber, waktu pembuatan, status, dan aksi yang sesuai.
- Status dapat difilter.
- Layanan dapat difilter.
- Sumber Online/Walk-in dapat difilter.
- Pencarian mendukung nomor tiket atau label pelanggan.
- Riwayat mendukung filter tanggal dan status.
- Desktop menggunakan tabel; layar sempit menggunakan layout responsif tanpa horizontal scrolling berlebihan.

#### FR-MER-03 — Now Serving

**Prioritas:** P0  
**Aktor:** Pemilik UMKM

Area Now Serving menampilkan tiket yang sedang dipanggil atau dilayani.

**Acceptance criteria:**
- Menampilkan nomor tiket, nama pelanggan, layanan, status, dan waktu relevan.
- Jika status Dipanggil, tersedia aksi panggil ulang dan mulai melayani.
- Jika status Sedang dilayani, tersedia aksi selesaikan.
- Jika tidak ada tiket yang sedang diproses, tampilkan empty state.

#### FR-MER-04 — Panggil Berikutnya

**Prioritas:** P0  
**Aktor:** Pemilik UMKM

Sistem memilih tiket Menunggu paling awal berdasarkan urutan pencatatan.

**Acceptance criteria:**
- Hanya tiket Menunggu yang dapat dipanggil melalui aksi ini.
- Pemilihan mengikuti FIFO.
- Tiket berubah menjadi Dipanggil setelah transaksi berhasil.
- Waktu pemanggilan dicatat.
- Antrean online dan walk-in mengikuti urutan yang sama.
- Pada mode satu operator, tombol tidak dapat memproses tiket baru ketika masih ada tiket berstatus Dipanggil atau Sedang dilayani.
- Klik ganda atau request bersamaan tidak boleh menghasilkan dua pemanggilan untuk tiket yang sama.
- Jika tidak ada tiket Menunggu, sistem menampilkan kondisi yang sesuai.

#### FR-MER-05 — Panggil Ulang

**Prioritas:** P0  
**Aktor:** Pemilik UMKM

Pemilik dapat memanggil ulang tiket berstatus Dipanggil.

**Acceptance criteria:**
- Aksi hanya tersedia untuk status Dipanggil.
- Nomor dan identitas tiket tidak berubah.
- Tidak dibuat tiket baru.
- Waktu panggil ulang dicatat jika diperlukan untuk histori panggilan.
- Sistem memberikan feedback keberhasilan/kegagalan.

#### FR-MER-06 — Mulai Melayani

**Prioritas:** P0  
**Aktor:** Pemilik UMKM

Pemilik mengubah tiket dari Dipanggil menjadi Sedang dilayani.

**Acceptance criteria:**
- Transisi hanya valid dari Dipanggil.
- Timestamp mulai pelayanan disimpan.
- Halaman pelanggan menerima status terbaru pada polling berikutnya.
- Transisi yang tidak valid ditolak backend.

#### FR-MER-07 — Selesaikan Antrean

**Prioritas:** P0  
**Aktor:** Pemilik UMKM

Pemilik menyelesaikan tiket yang sedang dilayani.

**Acceptance criteria:**
- Aksi hanya valid dari Sedang dilayani.
- Status menjadi Selesai.
- Timestamp selesai disimpan.
- Durasi pelayanan dapat dihitung dari timestamp mulai dan selesai.
- Dashboard dan laporan menggunakan status terbaru.

#### FR-MER-08 — Lewati Antrean

**Prioritas:** P0  
**Aktor:** Pemilik UMKM

Pemilik dapat melewati tiket yang tidak merespons atau tidak hadir.

**Acceptance criteria:**
- Transisi yang didukung MVP adalah Menunggu → Dilewati dan Dipanggil → Dilewati.
- Status Dilewati terpisah dari Selesai.
- Timestamp perubahan dicatat.
- Alasan dicatat apabila kebijakan produk mewajibkannya.
- Tiket Dilewati menjadi status akhir pada MVP dan tidak otomatis kembali ke antrean aktif.

#### FR-MER-09 — Tambah Antrean Manual

**Prioritas:** P0  
**Aktor:** Pemilik UMKM

Pemilik dapat memasukkan pelanggan walk-in ke daftar antrean.

**Acceptance criteria:**
- Form menyediakan layanan dan nama/label pelanggan.
- Catatan bersifat opsional.
- Nomor telepon tidak wajib.
- Sistem memvalidasi layanan aktif dan kapasitas.
- Nomor tiket dibuat otomatis.
- Sumber disimpan sebagai Walk-in.
- Tiket masuk pada urutan antrean utama yang sama dengan online.
- Setelah berhasil, nomor tiket ditampilkan kepada operator.
- Jika penyimpanan gagal, tidak ada tiket setengah jadi yang dianggap berhasil.

#### FR-MER-10 — Detail dan Riwayat Antrean

**Prioritas:** P0  
**Aktor:** Pemilik UMKM

Pemilik dapat melihat detail tiket dan histori antrean.

**Acceptance criteria:**
- Detail memuat nomor, nama, layanan, sumber, timestamp yang tersedia, status, catatan, dan riwayat perubahan.
- Riwayat menampilkan tiket Selesai, Dilewati, dan Dibatalkan.
- Riwayat bersifat baca-saja pada MVP.
- Pencarian dan filter tanggal/status/layanan tersedia.

### 7.4 Modul Layanan

#### FR-SVC-01 — Daftar Layanan

**Prioritas:** P0  
**Aktor:** Pemilik UMKM

Pemilik dapat melihat layanan milik usahanya.

**Acceptance criteria:**
- Daftar menampilkan nama, deskripsi singkat, durasi, harga opsional, kuota opsional, dan status.
- Pencarian tersedia jika daftar layanan memerlukan pencarian.
- Empty state mengarahkan pengguna untuk membuat layanan.

#### FR-SVC-02 — Tambah dan Edit Layanan

**Prioritas:** P0  
**Aktor:** Pemilik UMKM

Field layanan meliputi nama (wajib), deskripsi (opsional), estimasi durasi (wajib), harga (opsional), kuota harian (opsional), dan status.

**Acceptance criteria:**
- Nama tidak boleh kosong.
- Durasi harus positif dan satuannya jelas.
- Harga tidak boleh negatif.
- Kuota harus angka valid jika diisi.
- Perubahan hanya disimpan jika validasi berhasil.
- Form yang berubah tetapi belum disimpan memberikan peringatan saat pengguna meninggalkan halaman, apabila mekanisme tersebut diterapkan.

#### FR-SVC-03 — Aktif/Nonaktif Layanan

**Prioritas:** P0  
**Aktor:** Pemilik UMKM

Pemilik dapat menonaktifkan layanan tanpa menghilangkan histori tiket.

**Acceptance criteria:**
- Layanan nonaktif tidak tersedia untuk tiket baru.
- Tiket lama tetap mempertahankan hubungan dan informasi layanan.
- Penghapusan permanen tidak dilakukan bila layanan masih direferensikan data antrean.

### 7.5 Modul Laporan

#### FR-REP-01 — Filter Periode Laporan

**Prioritas:** P0  
**Aktor:** Pemilik UMKM

Pilihan periode: Hari ini, 7 hari terakhir, 30 hari terakhir, dan rentang tanggal khusus.

**Acceptance criteria:**
- Periode aktif ditampilkan secara eksplisit.
- Semua metrik dan grafik memakai periode yang sama.
- Rentang tanggal tidak valid ditolak dan dijelaskan.

#### FR-REP-02 — Metrik Laporan

**Prioritas:** P0  
**Aktor:** Pemilik UMKM

Metrik mencakup total tiket, selesai, dilewati, dibatalkan, rata-rata waktu tunggu, dan rata-rata durasi pelayanan.

**Acceptance criteria:**
- Total antrean dihitung dari tiket yang dibuat dalam periode terpilih.
- Status akhir dilaporkan secara terpisah.
- Rata-rata waktu tunggu menggunakan selisih waktu pembuatan dengan mulai pelayanan dan hanya menghitung data timestamp lengkap.
- Rata-rata durasi pelayanan menggunakan selisih mulai pelayanan dan selesai.
- Data tidak lengkap tidak diganti dengan angka nol.
- Sistem menjelaskan bila data belum cukup untuk menghitung suatu metrik.

#### FR-REP-03 — Visualisasi Laporan

**Prioritas:** P0 dasar

Visualisasi meliputi tren jumlah antrean, distribusi status, jumlah antrean berdasarkan layanan, dan ringkasan jam/hari ramai apabila data memadai.

**Acceptance criteria:**
- Grafik mengikuti filter periode.
- Grafik memiliki label/legend yang dapat dipahami.
- No-data state tersedia.
- Jam/hari ramai tidak ditampilkan sebagai insight jika data belum mencukupi.

### 7.6 Pengaturan UMKM

#### FR-SET-01 — Profil Usaha

**Prioritas:** P0

Pemilik dapat mengelola nama usaha, logo opsional, kategori, deskripsi, alamat, dan kontak.

**Acceptance criteria:**
- Nama usaha wajib.
- Kategori dipilih dari daftar kategori aktif.
- Logo, alamat, deskripsi, dan kontak mengikuti sifat opsional pada spesifikasi.
- Perubahan hanya berlaku setelah tersimpan.
- Kegagalan upload logo ditampilkan dengan jelas.

#### FR-SET-02 — Jam Operasional

**Prioritas:** P0

Pemilik dapat mengatur jadwal per hari dan status operasional manual.

**Acceptance criteria:**
- Jadwal mendukung hari buka dan hari libur/tutup.
- Jam buka dan tutup divalidasi.
- Status manual Buka, Tutup, atau Jeda dapat diatur sesuai aturan produk.
- Status Tutup/Jeda menghentikan pengambilan antrean online baru.
- Tiket yang telah dibuat tetap dapat diselesaikan.
- Perubahan tidak otomatis membatalkan tiket aktif.

#### FR-SET-03 — Tautan dan QR Antrean

**Prioritas:** P0

Pemilik dapat melihat URL publik, menyalin tautan, melihat QR, dan mengunduh QR apabila fitur unduh telah tersedia.

**Acceptance criteria:**
- Slug publik unik untuk setiap UMKM.
- QR mengarah ke halaman publik UMKM yang benar.
- Tombol salin memberikan feedback.
- Preview publik sesuai data yang dapat dilihat pelanggan.

#### FR-SET-04 — Akun Pemilik

**Prioritas:** P0

Pemilik dapat mengelola informasi akun dan sesi.

**Acceptance criteria:**
- Pemilik dapat mengubah nama akun.
- Perubahan email mengikuti mekanisme verifikasi.
- Pemilik dapat mengubah password.
- Pemilik dapat logout.

#### FR-SET-05 — Notifikasi

**Prioritas:** P0 terbatas

MVP memberikan feedback dalam website untuk perubahan status dan hasil aksi. Integrasi kanal email/browser/WhatsApp tidak dianggap aktif sebelum backend dan kanal terkait tersedia.

**Acceptance criteria:**
- UI tidak menawarkan kanal yang belum diimplementasikan.
- Feedback aksi di dashboard dapat dibedakan antara sukses dan gagal.

### 7.7 Modul Pelanggan

#### FR-CUS-01 — Halaman Publik UMKM

**Prioritas:** P0  
**Aktor:** Pelanggan

Pelanggan membuka `/q/{slug}` untuk melihat profil dan layanan UMKM.

**Acceptance criteria:**
- Halaman menampilkan logo, nama usaha, kategori, deskripsi, alamat, dan kontak jika tersedia.
- Status Buka/Tutup/Jeda terlihat jelas.
- Daftar menampilkan layanan aktif, deskripsi, durasi, harga jika tersedia, dan ketersediaan.
- Layanan nonaktif tidak muncul.
- Layanan penuh ditampilkan sebagai tidak tersedia/penuh.
- Tombol Ambil Antrean tidak aktif saat UMKM Tutup/Jeda atau belum memenuhi konfigurasi minimum.
- Slug tidak dikenal menghasilkan halaman UMKM tidak ditemukan.

#### FR-CUS-02 — Form Pengambilan Antrean

**Prioritas:** P0  
**Aktor:** Pelanggan

Pelanggan memilih layanan dan memasukkan nama/label. Nomor telepon bersifat opsional.

**Acceptance criteria:**
- Pelanggan tidak perlu registrasi/login.
- Layanan wajib dipilih.
- Nama/label wajib diisi.
- Nomor telepon opsional.
- Sistem memvalidasi UMKM masih menerima tiket.
- Sistem memvalidasi layanan masih aktif dan kuota tersedia.
- Permintaan ganda tidak membuat tiket duplikat.
- Nomor tiket hanya ditampilkan sebagai berhasil setelah data tersimpan.
- Jika kuota habis sebelum submit diproses, permintaan ditolak dengan pesan yang jelas.

#### FR-CUS-03 — Konfirmasi Tiket

**Prioritas:** P0  
**Aktor:** Pelanggan

Setelah tiket berhasil dibuat, pelanggan menerima halaman konfirmasi.

**Acceptance criteria:**
- Menampilkan nama UMKM, nomor tiket, nama pelanggan, layanan, waktu pengambilan, dan status.
- Menyediakan tautan pemantauan tiket.
- Menyediakan salin tautan tiket.
- Nomor tiket terlihat jelas.

#### FR-CUS-04 — Pemantauan Tiket

**Prioritas:** P0  
**Aktor:** Pelanggan

Pelanggan membuka `/ticket/{token}` untuk melihat status tiket.

**Acceptance criteria:**
- Token unik dan sulit ditebak.
- Token valid hanya membuka tiket yang terkait.
- Token tidak valid/tiket tidak ditemukan menghasilkan pesan yang sesuai.
- Halaman menampilkan nomor, nama, UMKM, layanan, status, nomor yang sedang dilayani, jumlah antrean di depan, waktu ambil, dan estimasi jika data mencukupi.
- Informasi pelanggan lain tidak ditampilkan secara berlebihan.
- Data diperbarui berkala melalui polling pada MVP.
- Status akhir seperti Selesai, Dilewati, dan Dibatalkan tetap dapat dilihat.

#### FR-CUS-05 — Pembatalan oleh Pelanggan (Keputusan MVP Belum Dikunci)

**Prioritas:** Optional P0 / dapat ditunda

Spesifikasi sumber mengusulkan pembatalan hanya saat tiket masih Menunggu, tetapi fitur ini perlu dipastikan sebelum implementasi.

**Acceptance criteria jika diaktifkan:**
- Hanya tiket Menunggu yang dapat dibatalkan pelanggan.
- Tiket Dipanggil atau Sedang dilayani tidak dapat dibatalkan oleh pelanggan.
- Status berubah menjadi Dibatalkan setelah proses berhasil.
- Tiket yang dibatalkan tidak dapat digunakan kembali.
- UMKM melihat tiket pada riwayat.
- Kuota dikembalikan sesuai aturan kapasitas yang telah disetujui.

---

## 8. Aturan Bisnis Inti

### 8.1 Model Antrean MVP

- Satu UMKM memiliki satu antrean utama per hari operasional.
- Semua layanan masuk ke antrean utama yang sama.
- Antrean online dan walk-in menggunakan urutan FIFO yang sama.
- Pemanggilan berikutnya mengambil tiket Menunggu paling awal.
- P1 dapat menambahkan antrean terpisah berdasarkan layanan/loket jika dibutuhkan.

### 8.2 Penomoran Tiket

- Nomor dibuat otomatis oleh sistem.
- Nomor bersifat unik dalam konteks UMKM dan periode antrean.
- Nomor direset setiap hari sesuai kebijakan periode antrean.
- Nomor diberikan berdasarkan urutan permintaan yang berhasil dicatat.
- Format dapat menggunakan prefix dan nomor urut, misalnya `A-001`.
- Sistem harus mencegah duplikasi pada permintaan bersamaan.

### 8.3 Sumber Antrean

Nilai sumber antrean:
- `ONLINE`: dibuat melalui halaman publik pelanggan.
- `WALK_IN`: dibuat manual oleh pemilik/operator UMKM.

Sumber tidak mengubah prioritas antrean.

### 8.4 Status dan Transisi

| Status | Makna | Final? | Transisi yang didukung |
|---|---|:---:|---|
| Menunggu | Tiket dibuat dan menunggu dipanggil | Tidak | Dipanggil, Dilewati, Dibatalkan |
| Dipanggil | Operator sudah memanggil pelanggan | Tidak | Sedang dilayani, Dilewati |
| Sedang dilayani | Pelayanan sudah dimulai | Tidak | Selesai |
| Selesai | Pelayanan selesai | Ya | Tidak ada transisi standar |
| Dilewati | Pelanggan dilewati | Ya pada MVP | Tidak ada transisi standar |
| Dibatalkan | Tiket dibatalkan sesuai kebijakan | Ya | Tidak ada transisi standar |

Transisi yang tidak tercantum harus ditolak oleh backend pada MVP. Setiap transisi valid dicatat ke riwayat status.

### 8.5 Aturan Operator Tunggal

- Satu operator aktif pada MVP.
- Operator tidak dapat memanggil tiket berikutnya jika masih ada tiket Dipanggil atau Sedang dilayani.
- Setelah tiket Selesai atau Dilewati, operator dapat memanggil tiket Menunggu berikutnya.
- Panggil ulang tidak membuat tiket baru dan tidak mengubah nomor tiket.

### 8.6 Status Operasional Usaha

- `BUKA`: menerima antrean online baru selama konfigurasi layanan dan kuota memungkinkan.
- `TUTUP`: tidak menerima antrean online baru.
- `JEDA`: tidak menerima antrean online baru sampai dibuka kembali.
- Tiket yang sudah dibuat tetap dapat diproses hingga selesai/dilewati sesuai aturan.
- Perubahan jadwal atau status tidak otomatis menghapus tiket.

### 8.7 Kuota Layanan

- Kuota diatur per layanan per hari.
- Kuota bersifat opsional.
- Jika tidak ditentukan, tidak ada batas khusus layanan.
- Tiket Menunggu, Dipanggil, Sedang dilayani, dan Selesai dihitung sebagai pemakaian kuota.
- Tiket Dibatalkan tidak dihitung setelah pembatalan berhasil.
- Saat kuota tercapai, tiket baru untuk layanan tersebut ditolak.

### 8.8 Estimasi Waktu

- Estimasi bukan janji waktu pelayanan.
- Durasi layanan dapat digunakan sebagai salah satu dasar perkiraan.
- Estimasi hanya ditampilkan jika data mencukupi.
- Posisi antrean lebih diutamakan jika estimasi belum dapat dihitung secara wajar.
- Nilai yang tidak tersedia tidak boleh diganti angka nol.

### 8.9 Isolasi dan Kepemilikan Data

- Setiap data operasional terkait dengan `business_id`.
- Pemilik hanya dapat membaca/mengubah data UMKM miliknya.
- Pembatasan diterapkan di backend, bukan hanya di UI.
- Admin dapat melihat data platform sesuai kewenangannya.
- Pelanggan hanya dapat melihat data tiket berdasarkan token valid.

---

## 9. Kebutuhan Data dan Domain Model

Struktur fisik database akan ditetapkan pada tahap ERD. Entitas konseptual MVP yang direncanakan:

| Entitas/Tabel | Fungsi | Prioritas |
|---|---|---|
| `users` | Akun Admin dan pemilik UMKM | P0 |
| `businesses` | Profil dan status usaha | P0 |
| `business_categories` | Kategori usaha | P0 |
| `business_hours` | Jadwal operasional | P0 |
| `services` | Layanan UMKM | P0 |
| `daily_queues` | Periode antrean harian per UMKM | P0 |
| `queue_tickets` | Tiket antrean | P0 |
| `queue_status_histories` | Histori transisi status | P0 |
| `queue_sequences` | Pengendalian nomor urut | P0, desain teknis perlu divalidasi |
| `activity_logs` | Audit aktivitas penting | P0 dasar |
| `notifications` | Notifikasi aplikasi | Sesuai implementasi kanal |

### 9.1 Relasi Konseptual

- Satu pemilik memiliki satu UMKM pada MVP.
- Satu UMKM memiliki satu kategori.
- Satu UMKM mempunyai banyak layanan.
- Satu UMKM mempunyai banyak jadwal operasional.
- Satu UMKM mempunyai banyak periode antrean harian.
- Satu periode antrean mempunyai banyak tiket.
- Satu layanan dapat direferensikan oleh banyak tiket.
- Satu tiket mempunyai banyak riwayat status.
- Akun dapat menghasilkan banyak activity log.

### 9.2 Field Data Kunci yang Perlu Dipertimbangkan pada ERD

**Queue ticket:** business, daily queue, service, ticket number/sequence, customer label, optional phone, source, status, access token, created timestamp, called timestamp, service-start timestamp, completed timestamp, cancelled/skipped timestamp bila relevan.

**Status history:** ticket, previous status, new status, actor type/ID bila tersedia, timestamp, optional note/reason.

**Daily queue:** business, queue date/period, sequence state, status operasional atau relasi ke konfigurasi periode.

**Catatan:** daftar field ini merupakan rekomendasi untuk tahap desain database; nama kolom dan normalisasi final belum ditetapkan oleh PRD.

---

## 10. Pengalaman Pengguna dan Kebutuhan UI/UX

### 10.1 Prinsip Layout

Semua area menggunakan satu responsive web application dengan layout sesuai peran.

| Area | Prioritas desain | Desktop | Tablet | Mobile |
|---|---|---|---|---|
| Admin | Desktop-first | Sidebar + topbar + konten dashboard | Sidebar adaptif/collapsible | Navigasi ringkas dan konten bertumpuk |
| UMKM | Desktop-first | Sidebar + topbar + area operasional | Sidebar adaptif/collapsible | Navigasi dan tabel disesuaikan; aksi operasional tetap mudah dijangkau |
| Pelanggan | Responsive, mobile-friendly | Website dengan layout multi-kolom bila sesuai | Konten menyesuaikan lebar | Layout satu kolom dan tombol mudah dijangkau |

Tidak ada desain aplikasi native terpisah. Preview mobile hanya merupakan representasi responsif dari website.

### 10.2 Komponen dan States Minimum

Komponen UI yang diperlukan:
- App shell, sidebar, topbar, dan responsive navigation.
- Page header, breadcrumb bila diperlukan, tab/sub-navigation.
- Summary/metric card.
- Table/list antrean dan queue ticket item.
- Status badge.
- Search dan filter.
- Date/range picker.
- Button primary/secondary/tertiary/destructive.
- Form input, select, textarea, checkbox, toggle, time picker.
- Modal/drawer dan confirmation dialog.
- Toast/snackbar.
- Empty state, loading skeleton, error state.
- Chart card, tooltip, legend, no-data state.
- URL copy field dan QR preview.

State yang perlu didefinisikan sesuai konteks:
- Default.
- Hover.
- Focus.
- Pressed/active.
- Disabled.
- Loading.
- Success.
- Error.
- Empty.

### 10.3 Status Visual

Status wajib menggunakan label teks dan indikator visual. Warna tidak boleh menjadi satu-satunya penanda status.

### 10.4 Design System

Implementasi visual mengikuti dokumen pendamping `AntreIn-design.md` sebagai referensi token warna, tipografi, spacing, radius, shadow, komponen, dan responsive behavior. Perubahan terhadap design system harus diterapkan konsisten pada Admin, UMKM, dan Pelanggan.

---

## 11. Kebutuhan Nonfungsional

### 11.1 Security

- Password disimpan dengan password hashing yang aman.
- Autentikasi dan role Admin/UMKM dipisahkan.
- Otorisasi dijalankan di backend.
- Setiap akses data UMKM memeriksa kepemilikan/otorisasi.
- Form memvalidasi data di backend.
- Proteksi CSRF diterapkan pada form/session-based request yang relevan.
- Endpoint publik menerapkan rate limiting sesuai risiko.
- Token tiket unik, acak, dan sulit ditebak.
- Operasi perubahan status dan pembuatan nomor menggunakan transaksi database bila diperlukan.
- Aksi administratif penting dicatat.

### 11.2 Data Integrity

- Nomor tiket tidak boleh duplikat dalam cakupan UMKM dan periode antrean.
- Pembuatan tiket harus aman terhadap request bersamaan.
- Transisi status harus divalidasi di backend.
- Data histori tidak dihapus saat layanan dinonaktifkan atau UMKM dinonaktifkan.
- Statistik dan laporan dihitung dari sumber data yang konsisten.

### 11.3 Performance

- Dashboard tidak memuat seluruh histori tiket sekaligus.
- Daftar besar menggunakan pagination atau pemuatan bertahap.
- Query laporan menggunakan agregasi yang sesuai.
- Polling pelanggan memiliki interval yang ditentukan dan tidak menghasilkan request berlebihan.
- Target waktu respons kuantitatif perlu ditetapkan saat persiapan deployment dan load test.

### 11.4 Responsiveness and Compatibility

- Website mendukung browser modern.
- Admin dan UMKM dapat digunakan pada desktop, tablet, dan ponsel.
- Pelanggan dapat menggunakan website dari tautan/QR di berbagai ukuran layar.
- Tabel pada layar sempit beralih menjadi list/card atau pola responsif lain yang tidak memaksa scroll horizontal berlebihan.

### 11.5 Accessibility and Usability

- Kontrol utama memiliki label yang jelas.
- Input mempunyai label dan pesan validasi.
- Status tidak hanya dibedakan oleh warna.
- Fokus keyboard terlihat pada elemen interaktif.
- Kontras teks dan elemen interaktif perlu diperiksa sesuai standar aksesibilitas yang dipilih tim.
- Aksi berisiko memerlukan konfirmasi yang proporsional.

### 11.6 Reliability and Error Handling

- Gagal memuat data tidak boleh ditampilkan sebagai jumlah nol.
- Gagal menyimpan tidak boleh menampilkan notifikasi sukses.
- Request ganda ditangani agar tidak menghasilkan tiket atau perubahan ganda.
- Jika koneksi terputus, pengguna menerima feedback yang jelas.
- Data status hanya dianggap berubah setelah penyimpanan berhasil.

---

## 12. Teknologi dan Batasan Implementasi

### 12.1 Stack yang Direncanakan

| Lapisan | Teknologi |
|---|---|
| Backend | Laravel |
| Bahasa | PHP |
| Database | MySQL |
| Template UI | Blade |
| Interaktivitas | Livewire |
| Styling | Tailwind CSS |
| JavaScript pendukung | Alpine.js |
| Grafik | Chart.js |
| Real-time lanjutan | Laravel Reverb |
| Version control | Git dan GitHub |
| Development | Laragon / VS Code |

Versi framework, PHP, package, dan dependency harus dikunci berdasarkan kompatibilitas resmi pada saat proyek mulai dibuat. PRD ini tidak menetapkan versi patch.

### 12.2 Arsitektur

Gunakan satu aplikasi Laravel dengan pemisahan:
- Route publik, Admin, dan UMKM.
- Middleware autentikasi dan role.
- Policy/otorisasi untuk akses resource.
- Model dan relasi database.
- Service/business logic untuk aturan antrean dan penomoran.
- Livewire component untuk interaksi halaman.
- Blade layout berbeda untuk masing-masing area.

### 12.3 Polling dan Real-Time

- MVP menggunakan polling untuk memperbarui status di halaman pelanggan.
- WebSocket/Laravel Reverb merupakan pengembangan P1.
- Perubahan arsitektur real-time tidak boleh mengubah aturan status dan sumber data tiket.

---

## 13. Alur Pengguna Utama

### 13.1 Onboarding UMKM

1. Pemilik membuka AntreIn.
2. Pemilik mendaftar dan login.
3. Pemilik melengkapi profil dan kategori usaha.
4. Pemilik mengatur jadwal operasional.
5. Pemilik membuat minimal satu layanan aktif.
6. Sistem menyediakan halaman publik/slug UMKM.
7. Pemilik memeriksa tautan dan QR.
8. Pemilik membagikan tautan/QR kepada pelanggan.
9. Pemilik membuka operasional antrean.

### 13.2 Antrean Online

1. Pelanggan membuka tautan/QR UMKM.
2. Pelanggan melihat status usaha dan layanan.
3. Pelanggan memilih layanan.
4. Pelanggan mengisi nama/label dan nomor telepon opsional.
5. Sistem memvalidasi status, layanan, dan kuota.
6. Sistem membuat tiket dan nomor urut.
7. Pelanggan melihat konfirmasi dan menyimpan tautan tiket.
8. Tiket muncul pada dashboard UMKM.

### 13.3 Antrean Walk-in

1. Pelanggan datang langsung ke UMKM.
2. Operator membuka menu Antrean.
3. Operator memilih Tambah Antrean Manual.
4. Operator memilih layanan dan mengisi nama/label.
5. Sistem membuat tiket sumber Walk-in.
6. Tiket masuk antrean utama sesuai FIFO.

### 13.4 Pelayanan Antrean

1. Operator melihat tiket Menunggu.
2. Operator memilih Panggil Berikutnya.
3. Tiket menjadi Dipanggil.
4. Operator dapat memanggil ulang jika perlu.
5. Operator memilih Mulai Melayani.
6. Tiket menjadi Sedang dilayani.
7. Operator memilih Selesaikan.
8. Tiket menjadi Selesai dan timestamp tercatat.
9. Jika pelanggan tidak hadir, operator dapat memilih Lewati sesuai transisi yang diizinkan.

### 13.5 Monitoring Pelanggan

1. Pelanggan membuka tautan bertoken.
2. Sistem memvalidasi token.
3. Halaman menampilkan status dan posisi terbaru.
4. Polling memperbarui informasi berkala.
5. Halaman tetap menampilkan status akhir tiket.

### 13.6 Pengelolaan Platform

1. Admin login.
2. Admin membuka dashboard platform.
3. Admin melihat ringkasan dan aktivitas terbaru.
4. Admin membuka data UMKM/pengguna/monitoring antrean.
5. Admin melakukan perubahan administratif yang diizinkan.
6. Sistem menyimpan perubahan dan mencatat aktivitas penting.

---

## 14. Analitik Produk dan Audit

### 14.1 Data Operasional yang Harus Tercatat

Untuk mendukung dashboard dan laporan, sistem perlu menyimpan:
- Waktu tiket dibuat.
- Waktu tiket dipanggil.
- Waktu panggil ulang bila dicatat.
- Waktu pelayanan dimulai.
- Waktu pelayanan selesai.
- Waktu tiket dilewati/dibatalkan.
- Sumber tiket (Online/Walk-in).
- Layanan yang dipilih.
- UMKM pemilik tiket.

### 14.2 Audit Log

Aktivitas penting yang perlu dicatat antara lain:
- Perubahan status UMKM.
- Perubahan status akun pengguna.
- Perubahan kategori usaha.
- Perubahan konfigurasi usaha yang penting.
- Perubahan status tiket.

Detail retensi log, actor metadata, dan kebijakan penghapusan data perlu ditetapkan sebelum production.

---

## 15. Risiko, Dependensi, dan Mitigasi

| Risiko/Dependensi | Dampak | Mitigasi yang disarankan |
|---|---|---|
| Nomor tiket dibuat bersamaan | Nomor duplikat | Transaksi DB, row locking/sequence control, dan unique constraint. |
| Klik ganda Panggil Berikutnya | Tiket terproses dua kali | Validasi status di backend, locking, dan idempotent action bila sesuai. |
| Kebocoran data lintas UMKM | Risiko privasi dan keamanan tinggi | Scope query berdasarkan business, Policy, middleware, dan pengujian akses lintas tenant. |
| Status usaha berubah ketika tiket aktif | Kebingungan operasional | Pertahankan tiket aktif dan jelaskan dampak perubahan status. |
| Estimasi waktu tidak akurat | Menurunkan kepercayaan pelanggan | Tampilkan hanya jika data memadai dan beri label perkiraan. |
| Polling terlalu sering | Beban server meningkat | Tetapkan interval, optimalkan payload, dan evaluasi Reverb pada P1. |
| Integrasi email belum tersedia | Lupa password/notifikasi tidak bekerja | Siapkan konfigurasi mail sebelum fitur yang bergantung pada email dirilis. |
| Aturan pembatalan belum final | Ketidaksesuaian kuota dan status | Putuskan sebelum implementasi fitur pembatalan publik. |
| Jadwal operasional lintas zona waktu | Nomor harian dan jam buka salah | Tetapkan zona waktu usaha dan definisi hari operasional. |
| Data historis layanan dihapus | Laporan/tiket rusak | Gunakan nonaktif/soft delete dan pertahankan referensi historis. |

---

## 16. Rencana Pengembangan dan Milestone

Urutan berikut merupakan rekomendasi dependensi implementasi, bukan estimasi durasi.

### Milestone 1 — Finalisasi Produk dan Desain Sistem

- Mengunci keputusan bisnis terbuka.
- Menyusun Use Case Diagram.
- Menyusun Activity Diagram alur inti.
- Menyusun ERD dan skema database.
- Menyelesaikan design system dan UI/UX halaman inti.

**Exit criteria:** aturan status, urutan, penomoran, kuota, dan hak akses sudah disepakati.

### Milestone 2 — Fondasi Aplikasi dan Autentikasi

- Setup Laravel dan MySQL.
- Setup Blade, Livewire, Tailwind CSS.
- Setup layout publik, Admin, dan UMKM.
- Registrasi/login UMKM.
- Login Admin.
- Middleware, role, dan Policy.
- Profil dan onboarding UMKM.

**Exit criteria:** Admin dan pemilik hanya dapat membuka area sesuai perannya.

### Milestone 3 — Data UMKM dan Layanan

- CRUD kategori oleh Admin.
- Manajemen UMKM dan pengguna.
- Profil usaha.
- Jam operasional.
- CRUD layanan.
- Slug halaman publik.

**Exit criteria:** UMKM dapat menyelesaikan konfigurasi minimum untuk menerima antrean.

### Milestone 4 — Core Queue Engine

- Daily queue dan sequence.
- Pembuatan tiket online.
- Pembuatan tiket walk-in.
- FIFO.
- Transisi status.
- Riwayat status.
- Pencegahan duplikasi dan konkurensi.

**Exit criteria:** tiket online dan walk-in mendapat nomor unik serta diproses mengikuti aturan antrean.

### Milestone 5 — Customer Interface dan Dashboard UMKM

- Halaman publik UMKM.
- Form pengambilan antrean.
- Konfirmasi dan halaman tiket.
- Polling status tiket.
- Dashboard UMKM.
- Now Serving dan aksi operasional.
- Riwayat antrean.

**Exit criteria:** alur ujung ke ujung dari pelanggan mengambil tiket sampai tiket selesai dapat dijalankan.

### Milestone 6 — Admin Dashboard dan Laporan

- Dashboard Admin.
- Monitoring antrean.
- Statistik platform.
- Laporan UMKM.
- Grafik dan no-data states.
- Activity log.

**Exit criteria:** metrik sesuai perhitungan dan Admin dapat melakukan monitoring tanpa mengoperasikan antrean UMKM.

### Milestone 7 — QA, Security, dan Responsive Testing

- Functional testing.
- Authorization dan tenant isolation testing.
- Concurrent ticket creation testing.
- State transition testing.
- Responsive testing desktop/tablet/mobile.
- Error/empty/loading state testing.
- Browser compatibility.
- Deployment readiness.

**Exit criteria:** seluruh acceptance criteria P0 lulus dan tidak ada defect kritis yang diketahui.

---

## 17. Strategi Pengujian dan Definition of Done

### 17.1 Area Pengujian

1. **Authentication:** login, logout, reset password, role access.
2. **Authorization:** akses lintas role dan lintas UMKM.
3. **Business setup:** profil, kategori, jadwal, layanan.
4. **Queue creation:** online, walk-in, validasi, kuota, nomor unik.
5. **Queue lifecycle:** seluruh transisi status valid dan penolakan transisi invalid.
6. **Concurrency:** permintaan tiket dan panggil berikutnya secara bersamaan.
7. **Customer tracking:** token valid/invalid dan pembaruan polling.
8. **Reports:** definisi metrik, timestamp, periode, no-data.
9. **Admin:** pengelolaan UMKM, pengguna, kategori, monitoring.
10. **Responsive UI:** desktop, tablet, dan mobile.
11. **Error handling:** gagal simpan, gagal muat, koneksi terputus, dan validasi.

### 17.2 Definition of Done untuk Fitur

Sebuah fitur dianggap selesai apabila:
- Kebutuhan dan acceptance criteria terkait telah diimplementasikan.
- Validasi backend tersedia untuk data dan aturan bisnis.
- Otorisasi diterapkan jika fitur memerlukan pembatasan akses.
- Loading, empty, success, dan error state yang relevan tersedia.
- Tampilan konsisten dengan `AntreIn-design.md`.
- Responsif pada breakpoint yang relevan.
- Test fungsional dan test otorisasi yang relevan lulus.
- Tidak ada error kritis yang diketahui.
- Dokumentasi teknis diperbarui jika ada perubahan arsitektur atau perilaku.

### 17.3 Definition of Done untuk MVP

MVP dapat dinyatakan siap diuji oleh pengguna apabila:
- Seluruh fungsi P0 pada Admin, UMKM, Pelanggan, dan Sistem selesai.
- Alur antrean online dan walk-in terintegrasi.
- Aturan penomoran dan transisi status telah diuji.
- Data lintas UMKM terisolasi.
- Halaman pelanggan dapat memantau tiket dengan aman.
- Laporan dasar konsisten dengan data sumber.
- Responsive testing selesai.
- Tidak ada defect keamanan atau data-integrity berlevel kritis yang diketahui.

---

## 18. Keputusan Terbuka yang Harus Dikonfirmasi

Bagian ini sengaja dipisahkan dari kebutuhan yang sudah didefinisikan. Jangan menganggap rekomendasi berikut sebagai keputusan final sampai disetujui.

| ID | Pertanyaan | Rekomendasi awal | Dampak jika belum diputuskan |
|---|---|---|---|
| OQ-01 | Apakah satu akun pemilik hanya mengelola satu UMKM? | Satu UMKM per akun untuk MVP. | Struktur relasi user-business dan onboarding. |
| OQ-02 | Bagaimana definisi hari antrean dan zona waktu? | Satu periode per tanggal lokal UMKM. | Reset nomor, kuota harian, laporan, jam operasional. |
| OQ-03 | Apakah pembatalan oleh pelanggan masuk MVP? | Opsional; dapat ditunda agar aturan kuota lebih sederhana. | UI pelanggan, status, kapasitas kuota. |
| OQ-04 | Apakah antrean walk-in mendapat tautan tiket publik? | Belum ditentukan; minimal nomor tiket ditampilkan kepada operator. | Apakah pelanggan walk-in bisa memantau status mandiri. |
| OQ-05 | Apakah prefix tiket dikonfigurasi pemilik atau ditentukan sistem? | Prefix default per UMKM. | Pengaturan, format tiket, potensi konflik nomor. |
| OQ-06 | Apakah UMKM baru langsung aktif atau perlu persetujuan Admin? | Belum ditentukan oleh spesifikasi. | Status registrasi, onboarding, dan proses moderasi. |
| OQ-07 | Apa dampak penonaktifan UMKM oleh Admin terhadap tiket aktif? | Hentikan tiket baru; tetapkan kebijakan untuk tiket aktif. | Pengalaman pelanggan dan operasional. |
| OQ-08 | Bagaimana penanganan tiket Dipanggil yang tidak ditanggapi? | Operator dapat Lewati sesuai aturan status. | Batas waktu panggilan dan operasional operator tunggal. |
| OQ-09 | Apakah kuota layanan dihitung berdasarkan tiket dibuat atau tiket aktif? | Ikuti aturan awal: tiket non-batal memakai kuota. | Konsistensi kapasitas dan pembatalan. |
| OQ-10 | Apakah pelanggan dapat mengambil lebih dari satu tiket pada hari yang sama? | Belum ditentukan. | Validasi duplikasi pelanggan dan penyalahgunaan. |
| OQ-11 | Apakah nomor telepon opsional hanya disimpan atau digunakan untuk notifikasi? | Simpan hanya jika diperlukan; notifikasi eksternal bukan MVP. | Privasi, persetujuan, dan kebutuhan data. |
| OQ-12 | Interval polling pelanggan berapa detik? | Tetapkan melalui uji performa. | Beban server dan kesegaran status. |
| OQ-13 | Berapa lama tiket dan token dapat diakses? | Tetapkan kebijakan retensi dan akses. | Privasi, keamanan, dan penyimpanan data. |
| OQ-14 | Apakah Admin boleh melihat nomor telepon pelanggan saat monitoring? | Batasi pada data yang benar-benar diperlukan. | Privasi dan tampilan monitoring. |
| OQ-15 | Apakah laporan MVP perlu ringkasan jam/hari ramai? | Hanya jika jumlah dan kualitas data memadai. | Kompleksitas query dan interpretasi metrik. |

---

## 19. Asumsi dan Batasan PRD

1. PRD ini disusun berdasarkan dokumen **Spesifikasi Fungsional Website AntreIn v1.0** yang diberikan sebagai sumber utama.
2. Tiga area pengguna adalah Admin, UMKM, dan Pelanggan.
3. AntreIn merupakan satu website responsif, bukan aplikasi mobile native.
4. Pada MVP, satu pemilik mengelola satu UMKM dan satu operator aktif; multi-staf/multi-loket termasuk P1.
5. Satu UMKM mempunyai satu antrean utama; seluruh layanan serta sumber online/walk-in berbagi urutan FIFO.
6. Pelanggan tidak perlu registrasi/login.
7. Pembaruan status pelanggan menggunakan polling pada MVP.
8. Laravel, MySQL, Blade, Livewire, Tailwind CSS, Alpine.js, dan Chart.js merupakan stack yang direncanakan; detail versi dan paket harus dikonfirmasi saat setup proyek.
9. PRD ini tidak menentukan target SLA, target trafik, jumlah pengguna, biaya infrastruktur, kebijakan privasi legal, retensi data, atau strategi monetisasi karena belum ditetapkan pada spesifikasi sumber.
10. Rekomendasi dalam bagian keputusan terbuka harus dikonfirmasi sebelum menjadi aturan implementasi.

---

## 20. Referensi dan Dokumen Pendukung

| Dokumen | Peran |
|---|---|
| Spesifikasi Fungsional Website AntreIn v1.0 | Sumber utama kebutuhan fitur, alur, dan aturan bisnis. |
| `AntreIn-design.md` | Referensi design system, token visual, komponen, dan responsive behavior. |
| Use Case Diagram | Akan menggambarkan interaksi aktor dengan sistem. |
| Activity Diagram | Akan menggambarkan alur proses utama. |
| ERD dan Data Dictionary | Akan menetapkan struktur database final. |
| Test Plan / Test Case | Akan memetakan acceptance criteria ke skenario pengujian. |

---

## 21. Penutup

PRD ini menjadi baseline kebutuhan produk AntreIn untuk pengembangan MVP. Seluruh implementasi harus mengacu pada tujuan produk, ruang lingkup, kebutuhan fungsional, aturan bisnis, acceptance criteria, dan batasan yang ditetapkan di dalam dokumen ini.

Perubahan kebutuhan setelah dokumen disetujui perlu dicatat sebagai perubahan scope atau revisi PRD agar desain UI/UX, struktur database, backend, frontend, dan pengujian tetap konsisten.

**Prinsip utama pengembangan AntreIn:** selesaikan alur antrean inti yang andal, mudah digunakan, dan terintegrasi sebelum memperluas produk ke fitur operasional bisnis yang lebih kompleks.

---

**Akhir Dokumen — AntreIn PRD v1.0**
