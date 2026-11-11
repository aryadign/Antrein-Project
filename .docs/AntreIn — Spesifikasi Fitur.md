# **SPESIFIKASI FUNGSIONAL WEBSITE ANTREIN**

**Platform Manajemen Antrean Digital untuk UMKM**

| Informasi | Keterangan |
| ----- | ----- |
| Nama Produk | AntreIn |
| Jenis Aplikasi | Website berbasis web |
| Versi Dokumen | 1.0 |
| Platform | Web Application |
| Framework Backend | Laravel |
| Database | MySQL |
| Frontend | Blade, Livewire, Tailwind CSS |
| Target Pengguna | Admin AntreIn, Pemilik UMKM, dan Pelanggan |
| Fokus Utama | Pengelolaan antrean online dan walk-in secara terintegrasi |

---

# **1\. PENDAHULUAN**

## **1.1 Latar Belakang**

AntreIn merupakan platform manajemen antrean digital yang dirancang untuk membantu Usaha Mikro, Kecil, dan Menengah (UMKM) dalam mengelola proses pelayanan pelanggan secara lebih terstruktur dan efisien.

Dalam operasional sehari-hari, UMKM sering kali masih mengandalkan pencatatan antrean secara manual. Kondisi tersebut dapat menyebabkan kesulitan dalam mengatur urutan pelanggan, memantau jumlah antrean, serta memberikan informasi mengenai status pelayanan kepada pelanggan.

AntreIn hadir sebagai solusi berbasis website yang mengintegrasikan pengelolaan antrean pelanggan yang mengambil nomor secara online maupun pelanggan yang datang langsung (*walk-in*) ke dalam satu sistem.

Platform ini memiliki tiga sisi pengguna utama, yaitu Admin AntreIn sebagai pengelola platform, Pemilik UMKM sebagai pengelola operasional usaha, serta Pelanggan sebagai pengguna layanan pengambilan dan pemantauan antrean.

Dengan sistem yang terintegrasi, AntreIn diharapkan dapat memberikan pengalaman antrean yang lebih praktis bagi pelanggan sekaligus membantu UMKM meningkatkan keteraturan dan efisiensi pelayanan.

## **1.2 Tujuan Sistem**

1. Memudahkan UMKM mengelola antrean online dan walk-in dalam satu sistem.  
2. Membantu UMKM mengatur layanan, jam operasional, dan kapasitas antrean.  
3. Memudahkan pelanggan mengambil nomor antrean tanpa harus datang langsung atau membuat akun.  
4. Memberikan informasi posisi dan status antrean kepada pelanggan.  
5. Menyediakan laporan operasional yang dapat digunakan UMKM untuk mengevaluasi pelayanan.  
6. Memungkinkan Admin AntreIn mengelola UMKM dan memantau aktivitas platform secara terpusat.

## **1.3 Ruang Lingkup Sistem**

AntreIn dikembangkan sebagai satu website dengan tiga area utama:

1. **Admin AntreIn:** area pengelolaan platform secara keseluruhan.  
2. **Merchant/UMKM:** area pengelolaan profil usaha, layanan, dan operasional antrean.  
3. **Pelanggan:** area publik untuk melihat informasi usaha, mengambil antrean, serta memantau status tiket.

Ketiga area tersebut menggunakan backend dan database yang sama, tetapi memiliki hak akses, navigasi, dan kebutuhan antarmuka yang berbeda.

## **1.4 Prinsip Pengembangan**

* Sederhana: fitur mudah dipahami oleh UMKM yang hanya memiliki satu operator.  
* Terintegrasi: antrean online dan walk-in dikelola dalam satu sistem.  
* Efisien: proses operasional utama dapat dilakukan dengan sedikit interaksi.  
* Transparan: pelanggan dapat mengetahui status antrean secara mandiri.  
* Aman: setiap UMKM hanya dapat mengakses data milik usahanya.  
* Responsif: website dapat digunakan melalui desktop, tablet, dan ponsel.  
* Bertahap: pengembangan dimulai dari fungsi inti sebelum fitur lanjutan ditambahkan.

---

# **2\. STRUKTUR SISTEM DAN HAK AKSES**

## **2.1 Struktur Tiga Sisi Aplikasi**

### **A. Admin AntreIn**

Admin bertanggung jawab mengelola dan memantau keseluruhan platform, termasuk data UMKM, akun pengguna, kategori usaha, dan aktivitas antrean.

### **B. Pemilik UMKM**

Pemilik UMKM bertanggung jawab mengelola informasi usaha, layanan, jam operasional, antrean pelanggan, dan laporan operasional.

### **C. Pelanggan**

Pelanggan menggunakan halaman publik AntreIn untuk mengakses informasi UMKM, memilih layanan, mengambil nomor antrean, dan memantau status antrean.

## **2.2 Matriks Hak Akses**

| Modul/Fungsi | Admin | Pemilik UMKM | Pelanggan |
| ----- | ----- | ----- | ----- |
| Login Admin | ✓ | \- | \- |
| Registrasi UMKM | \- | ✓ | \- |
| Login UMKM | \- | ✓ | \- |
| Dashboard platform | ✓ | \- | \- |
| Dashboard usaha | \- | ✓ | \- |
| Melihat daftar UMKM | ✓ | \- | \- |
| Mengelola data UMKM | ✓ | ✓ (milik sendiri) | \- |
| Mengelola kategori usaha | ✓ | \- | \- |
| Mengelola layanan | \- | ✓ | \- |
| Mengelola jam operasional | \- | ✓ | \- |
| Melihat seluruh antrean platform | ✓ (monitoring) | \- | \- |
| Mengelola antrean usaha | \- | ✓ | \- |
| Mengambil antrean | \- | \- | ✓ |
| Melihat tiket antrean | \- | \- | ✓ |
| Memantau status antrean | \- | \- | ✓ |
| Melihat laporan usaha | \- | ✓ | \- |
| Mengelola akun pengguna platform | ✓ | \- | \- |
| Mengatur platform | ✓ | \- | \- |

**Ketentuan:** Admin memiliki akses monitoring platform, bukan bertindak sebagai operator antrean setiap UMKM. Pemilik UMKM hanya boleh mengelola data yang berada dalam lingkup usahanya.

Pada MVP, pengelolaan operasional UMKM dapat dilakukan oleh pemilik akun. Multi-staf dan pembagian hak akses operator disiapkan untuk tahap pengembangan berikutnya.

---

# **3\. MODUL ADMIN ANTREIN**

## **3.1 Tujuan**

Modul Admin digunakan untuk mengelola keseluruhan platform AntreIn, memastikan data UMKM dan pengguna terorganisasi, serta memantau aktivitas sistem.

## **3.2 Struktur Navigasi Admin**

Menu utama Admin:

1. Dashboard  
2. Manajemen UMKM  
3. Manajemen Pengguna  
4. Monitoring Antrean  
5. Kategori UMKM  
6. Pengaturan Platform

## **3.3 Dashboard Admin**

### **Tujuan**

Memberikan gambaran umum mengenai kondisi dan aktivitas platform AntreIn.

### **Komponen Informasi**

**A. Ringkasan Statistik Platform**

* Total UMKM terdaftar.  
* Jumlah UMKM aktif.  
* Jumlah UMKM nonaktif.  
* Total akun pengguna.  
* Total antrean yang tercatat.  
* Total antrean selesai.

**B. Grafik Aktivitas**

* Tren pendaftaran UMKM.  
* Tren jumlah antrean.  
* Distribusi antrean berdasarkan status.  
* Aktivitas platform dalam periode tertentu.

**C. Aktivitas Terbaru**

* UMKM yang baru terdaftar.  
* Perubahan status UMKM.  
* Akun pengguna yang baru dibuat.  
* Aktivitas administratif penting.

### **Interaksi**

* Admin dapat memilih periode statistik.  
* Admin dapat membuka detail UMKM dari ringkasan.  
* Admin dapat membuka halaman monitoring antrean.  
* Sistem menampilkan kondisi kosong apabila belum terdapat data.

## **3.4 Manajemen UMKM**

### **Tujuan**

Mengelola data seluruh UMKM yang menggunakan platform AntreIn.

### **Halaman Daftar UMKM**

Informasi yang ditampilkan:

* Nama usaha.  
* Pemilik usaha.  
* Kategori usaha.  
* Alamat.  
* Tanggal pendaftaran.  
* Jumlah layanan.  
* Jumlah antrean.  
* Status UMKM.

Fitur:

* Pencarian nama usaha.  
* Filter kategori.  
* Filter status.  
* Filter periode pendaftaran.  
* Pagination.

### **Detail UMKM**

Informasi yang ditampilkan:

* Profil usaha.  
* Informasi pemilik.  
* Kategori usaha.  
* Jam operasional.  
* Daftar layanan.  
* Jumlah antrean.  
* Aktivitas usaha.  
* Status akun.

### **Aksi Admin**

1. Melihat detail UMKM.  
2. Mengaktifkan UMKM.  
3. Menonaktifkan atau menangguhkan UMKM.  
4. Mengubah kategori usaha apabila diperlukan.  
5. Melihat aktivitas usaha.

### **Ketentuan**

* UMKM yang dinonaktifkan tidak dapat menerima antrean online baru.  
* Data historis UMKM tetap dipertahankan.  
* Penonaktifan akun tidak boleh menghapus riwayat antrean.  
* Admin harus memberikan konfirmasi sebelum melakukan penonaktifan.  
* Perubahan status dicatat dalam log aktivitas.

## **3.5 Manajemen Pengguna**

### **Tujuan**

Mengelola akun yang terdaftar dalam platform AntreIn.

### **Informasi Pengguna**

* Nama pengguna.  
* Email.  
* Jenis akun.  
* UMKM terkait, apabila ada.  
* Tanggal pendaftaran.  
* Status akun.

### **Fitur**

* Melihat daftar pengguna.  
* Mencari pengguna berdasarkan nama/email.  
* Memfilter jenis akun.  
* Melihat detail akun.  
* Mengaktifkan atau menonaktifkan akun.  
* Melihat hubungan pengguna dengan UMKM.

### **Jenis Akun**

* Admin: pengelola platform.  
* Pemilik UMKM: pengelola usaha.

Pembuatan akun Admin dibatasi dan tidak tersedia melalui registrasi publik.

## **3.6 Monitoring Antrean**

### **Tujuan**

Memberikan kemampuan kepada Admin untuk melihat aktivitas antrean yang berlangsung di seluruh platform.

### **Informasi**

* Nomor tiket.  
* Nama UMKM.  
* Nama layanan.  
* Nama atau label pelanggan.  
* Sumber antrean.  
* Waktu pembuatan.  
* Status antrean.

### **Fitur**

* Pencarian nomor tiket.  
* Pencarian nama UMKM.  
* Filter status.  
* Filter sumber antrean.  
* Filter tanggal.  
* Melihat detail tiket.

### **Batasan**

Admin hanya menggunakan modul ini untuk monitoring dan pemeriksaan aktivitas. Perubahan status operasional antrean tetap dilakukan oleh UMKM agar tanggung jawab pelayanan tidak tercampur.

## **3.7 Kategori UMKM**

### **Tujuan**

Mengelola klasifikasi jenis usaha yang dapat digunakan oleh UMKM saat melengkapi profil.

### **Data Kategori**

* Nama kategori.  
* Deskripsi singkat.  
* Status aktif/nonaktif.

### **Fitur**

* Tambah kategori.  
* Edit kategori.  
* Aktifkan/nonaktifkan kategori.  
* Pencarian kategori.

### **Aturan**

Kategori yang telah digunakan oleh UMKM tidak boleh dihapus secara permanen tanpa mekanisme penggantian kategori yang jelas.

## **3.8 Pengaturan Platform**

Pengaturan dasar mencakup:

* Nama dan identitas platform.  
* Logo AntreIn.  
* Informasi kontak platform.  
* Pengaturan dasar sistem.  
* Konfigurasi kategori apabila diperlukan.  
* Informasi kebijakan dan ketentuan penggunaan.

## **3.9 Kondisi yang Harus Ditangani**

* Belum ada UMKM terdaftar.  
* Belum ada aktivitas antrean.  
* Data gagal dimuat.  
* Akun dinonaktifkan.  
* Akses tidak diizinkan.  
* Terjadi kegagalan ketika menyimpan perubahan.  
* Pencarian tidak menghasilkan data.

---

# **4\. MODUL MERCHANT / UMKM**

## **4.1 Tujuan**

Modul UMKM merupakan area operasional utama yang digunakan pemilik usaha untuk mengelola antrean pelanggan serta konfigurasi usaha.

## **4.2 Struktur Navigasi UMKM**

Menu utama:

1. Beranda  
2. Antrean  
3. Layanan  
4. Laporan  
5. Pengaturan

## **4.3 Autentikasi dan Onboarding UMKM**

### **A. Registrasi**

UMKM dapat membuat akun melalui halaman registrasi.

Field:

* Nama pemilik.  
* Email.  
* Password.  
* Konfirmasi password.

Setelah registrasi berhasil, pengguna diarahkan ke proses penyiapan usaha.

### **B. Login**

Field:

* Email.  
* Password.

Fitur tambahan:

* Tampilkan/sembunyikan password.  
* Ingat sesi login sesuai mekanisme keamanan.  
* Lupa password.  
* Validasi kesalahan autentikasi.

### **C. Onboarding Usaha**

Langkah penyiapan:

1. Melengkapi profil usaha.  
2. Menentukan kategori usaha.  
3. Mengatur jam operasional.  
4. Membuat minimal satu layanan.  
5. Membuat tautan antrean.  
6. Membagikan QR atau tautan kepada pelanggan.

Onboarding ditampilkan melalui checklist di halaman Beranda.

Pengguna tetap dapat mengakses bagian aplikasi lainnya meskipun beberapa langkah onboarding belum selesai, tetapi pengambilan antrean online belum dibuka sebelum konfigurasi minimum terpenuhi.

## **4.4 Beranda UMKM**

### **Tujuan**

Membantu pemilik usaha memahami kondisi operasional dalam waktu singkat.

### **A. Header**

* Nama usaha.  
* Tanggal aktif.  
* Status operasional: Buka, Tutup, atau Jeda Sementara.  
* Informasi akun pengguna.

### **B. Kartu Statistik Antrean**

* Total antrean hari ini.  
* Menunggu.  
* Dipanggil.  
* Sedang dilayani.  
* Selesai.  
* Dilewati/dibatalkan.

Semua statistik mengacu pada tanggal operasional yang sama.

### **C. Antrean Aktif**

Menampilkan antrean yang sedang menunggu, dipanggil, atau dilayani.

Informasi:

* Nomor tiket.  
* Nama pelanggan.  
* Layanan.  
* Sumber antrean.  
* Status.  
* Waktu masuk.

### **D. Aksi Cepat**

* Kelola Antrean.  
* Tambah Antrean Manual.  
* Tambah Layanan.  
* Atur Jam Operasional.

### **E. Ringkasan Operasional**

* Jumlah antrean selesai.  
* Rata-rata waktu tunggu apabila data memadai.  
* Layanan yang paling sering digunakan apabila data memadai.

### **Kondisi Halaman**

* Pertama kali menggunakan sistem.  
* Belum memiliki layanan.  
* Belum terdapat antrean.  
* Usaha sedang tutup.  
* Usaha sedang dijeda.  
* Data gagal dimuat.  
* Sedang memuat data.

## **4.5 Modul Antrean**

### **Tujuan**

Menjadi pusat pengelolaan antrean pelanggan yang berasal dari online maupun walk-in.

### **A. Ringkasan Status**

* Menunggu.  
* Dipanggil.  
* Sedang dilayani.  
* Selesai.  
* Dilewati.  
* Dibatalkan.

Setiap status memiliki jumlah tiket yang sesuai dan dapat digunakan sebagai filter.

### **B. Area Now Serving**

Menampilkan tiket yang sedang dipanggil atau sedang dilayani.

Informasi:

* Nomor tiket.  
* Nama pelanggan.  
* Layanan.  
* Status.  
* Waktu panggilan atau mulai pelayanan.

Aksi:

* Panggil ulang apabila status Dipanggil.  
* Mulai melayani apabila status Dipanggil.  
* Selesaikan apabila status Sedang dilayani.

### **C. Daftar Antrean**

Setiap data menampilkan:

* Nomor tiket.  
* Nama/label pelanggan.  
* Layanan.  
* Sumber antrean: Online atau Walk-in.  
* Waktu pembuatan.  
* Status.  
* Aksi yang tersedia.

Tampilan menggunakan tabel pada desktop dan kartu responsif pada layar sempit.

### **D. Panggil Berikutnya**

Fungsi:

* Mengambil tiket dengan urutan antrean paling awal yang masih berstatus Menunggu.  
* Mengubah status tiket menjadi Dipanggil.  
* Mencatat waktu pemanggilan.  
* Menampilkan konfirmasi keberhasilan.  
* Memperbarui tampilan pelanggan.

Ketentuan:

* Hanya dapat dilakukan apabila tidak ada tiket yang sedang dipanggil atau dilayani oleh operator tunggal.  
* Jika tidak ada antrean, tombol dinonaktifkan atau sistem menampilkan pesan yang jelas.  
* Operasi harus mencegah pemanggilan tiket yang sama secara bersamaan.

### **E. Panggil Ulang**

* Memanggil ulang tiket yang berstatus Dipanggil.  
* Tidak membuat tiket baru.  
* Tidak mengubah nomor antrean.  
* Memperbarui waktu pemanggilan ulang.  
* Menampilkan feedback kepada operator.

### **F. Mulai Melayani**

* Mengubah status Dipanggil menjadi Sedang dilayani.  
* Mencatat waktu mulai pelayanan.  
* Memperbarui halaman pelanggan.

### **G. Selesaikan Antrean**

* Mengubah status Sedang dilayani menjadi Selesai.  
* Mencatat waktu selesai.  
* Menyimpan durasi pelayanan.  
* Memperbarui statistik.

### **H. Lewati Antrean**

* Mengubah status antrean menjadi Dilewati.  
* Digunakan ketika pelanggan tidak merespons atau tidak hadir.  
* Tidak menghitung tiket sebagai pelayanan selesai.  
* Mencatat waktu dan alasan apabila alasan diwajibkan dalam kebijakan usaha.

Pada MVP, antrean yang dilewati menjadi status akhir dan tidak otomatis kembali ke urutan aktif.

### **I. Tambah Antrean Manual**

Digunakan untuk mencatat pelanggan yang datang langsung ke lokasi usaha.

Field:

* Layanan.  
* Nama/label pelanggan.  
* Catatan (opsional).

Nomor telepon bersifat opsional apabila dibutuhkan oleh kebijakan usaha.

Aturan:

* Nomor tiket dibuat otomatis.  
* Sumber tiket dicatat sebagai Walk-in.  
* Tiket masuk ke urutan antrean yang sama dengan tiket online.  
* Sistem memvalidasi ketersediaan layanan dan kapasitas.  
* Setelah berhasil, nomor tiket ditampilkan kepada operator.

### **J. Detail Antrean**

Informasi:

* Nomor tiket.  
* Nama pelanggan.  
* Layanan.  
* Sumber antrean.  
* Waktu dibuat.  
* Waktu dipanggil.  
* Waktu mulai pelayanan.  
* Waktu selesai.  
* Status saat ini.  
* Riwayat perubahan status.

### **K. Riwayat Antrean**

Menampilkan tiket dengan status:

* Selesai.  
* Dilewati.  
* Dibatalkan.

Fitur:

* Pencarian nomor tiket.  
* Pencarian nama pelanggan.  
* Filter tanggal.  
* Filter layanan.  
* Filter status.

Data riwayat pada MVP bersifat baca-saja untuk mencegah perubahan data historis tanpa prosedur koreksi.

## **4.6 Modul Layanan**

### **Tujuan**

Mengelola daftar layanan yang dapat dipilih pelanggan saat mengambil antrean.

### **A. Daftar Layanan**

Informasi:

* Nama layanan.  
* Deskripsi singkat.  
* Durasi estimasi.  
* Harga opsional.  
* Kuota opsional.  
* Status aktif/nonaktif.

### **B. Tambah dan Edit Layanan**

Field:

1. Nama layanan (wajib).  
2. Deskripsi (opsional).  
3. Estimasi durasi (wajib).  
4. Harga (opsional).  
5. Kuota antrean harian (opsional).  
6. Status layanan.

### **C. Aksi Layanan**

* Tambah layanan.  
* Edit layanan.  
* Aktifkan/nonaktifkan layanan.  
* Cari layanan.

### **D. Aturan Layanan**

* Nama layanan tidak boleh kosong.  
* Durasi harus lebih besar dari nol.  
* Harga tidak boleh negatif.  
* Kuota harus berupa angka valid apabila diisi.  
* Layanan nonaktif tidak muncul pada halaman pelanggan.  
* Layanan yang pernah digunakan tetap disimpan untuk menjaga riwayat tiket.  
* Layanan tidak dapat dihapus permanen apabila masih memiliki hubungan dengan data antrean.

Harga hanya digunakan sebagai informasi, bukan sebagai transaksi pembayaran.

## **4.7 Modul Laporan**

### **Tujuan**

Menyediakan ringkasan performa antrean untuk membantu UMKM mengevaluasi operasional.

### **A. Filter Periode**

* Hari ini.  
* 7 hari terakhir.  
* 30 hari terakhir.  
* Rentang tanggal khusus.

### **B. Metrik Utama**

* Total antrean.  
* Antrean selesai.  
* Antrean dilewati.  
* Antrean dibatalkan.  
* Rata-rata waktu tunggu.  
* Rata-rata durasi pelayanan.

### **C. Visualisasi**

* Grafik tren jumlah antrean.  
* Distribusi antrean berdasarkan status.  
* Jumlah antrean berdasarkan layanan.  
* Ringkasan hari atau jam ramai apabila data mencukupi.

### **D. Definisi Perhitungan**

**Total antrean:** jumlah tiket yang dibuat pada periode yang dipilih.

**Antrean selesai:** jumlah tiket yang berubah menjadi status Selesai.

**Rata-rata waktu tunggu:** selisih antara waktu pembuatan tiket dan waktu mulai pelayanan. Hanya data dengan timestamp lengkap yang digunakan.

**Rata-rata durasi pelayanan:** selisih waktu mulai pelayanan hingga waktu selesai.

**Antrean dibatalkan dan dilewati:** dilaporkan secara terpisah dari antrean selesai.

### **E. Kondisi Halaman**

* Belum terdapat data.  
* Data belum cukup untuk menghitung rata-rata.  
* Gagal memuat laporan.  
* Rentang tanggal tidak valid.

Ekspor CSV/PDF ditempatkan sebagai pengembangan setelah MVP.

## **4.8 Modul Pengaturan**

### **A. Profil Usaha**

Field:

* Nama usaha.  
* Logo.  
* Kategori usaha.  
* Deskripsi.  
* Alamat.  
* Kontak usaha.

Fitur:

* Melihat profil.  
* Mengedit profil.  
* Mengunggah logo.  
* Menyimpan perubahan.

### **B. Jam Operasional**

Komponen:

* Jadwal Senin sampai Minggu.  
* Jam buka.  
* Jam tutup.  
* Pilihan hari libur.  
* Status operasional manual.

Status:

* Buka.  
* Tutup.  
* Jeda Sementara.

Ketentuan:

* Jadwal terstruktur secara terpisah dari status operasional manual.  
* Pemilik dapat menjeda operasional sementara.  
* Status Tutup atau Jeda mencegah pengambilan antrean online baru.  
* Tiket yang sudah aktif tetap dapat diselesaikan.  
* Perubahan jadwal tidak otomatis membatalkan tiket yang sudah dibuat.

### **C. Tautan dan QR Antrean**

Komponen:

* URL publik UMKM.  
* Preview halaman pelanggan.  
* Tombol Salin Tautan.  
* QR Code.  
* Tombol Unduh QR.

Aturan:

* Tautan setiap UMKM memiliki slug unik.  
* QR Code mengarah ke halaman publik UMKM yang sesuai.  
* Perubahan nama tampilan usaha tidak boleh merusak tautan lama tanpa mekanisme perubahan slug yang jelas.

### **D. Notifikasi**

MVP menyediakan informasi dan feedback perubahan antrean melalui halaman website.

Pengaturan kanal email, browser, maupun WhatsApp dikembangkan setelah integrasi masing-masing kanal tersedia.

### **E. Akun**

* Informasi akun pemilik.  
* Ubah nama akun.  
* Ubah email melalui mekanisme verifikasi.  
* Ubah password.  
* Keluar dari sistem.

### **Kondisi Pengaturan**

* Profil belum lengkap.  
* Perubahan belum disimpan.  
* Jadwal tidak valid.  
* Tautan belum tersedia.  
* Gagal menyimpan.  
* Logo gagal diunggah.

---

# **5\. MODUL PELANGGAN**

## **5.1 Tujuan**

Modul pelanggan merupakan halaman publik yang memungkinkan pelanggan mengakses layanan antrean UMKM tanpa harus membuat akun atau melakukan login.

Halaman pelanggan menggunakan pendekatan mobile-first karena sebagian besar pelanggan diperkirakan mengakses website melalui ponsel, termasuk melalui QR Code yang tersedia di lokasi usaha.

## **5.2 Struktur Halaman Pelanggan**

Halaman pelanggan terdiri dari:

1. Halaman Informasi UMKM.  
2. Halaman Pengambilan Antrean.  
3. Halaman Konfirmasi Tiket.  
4. Halaman Pemantauan Antrean.

## **5.3 Halaman Informasi UMKM**

URL: `/q/{slug}`

### **Tujuan**

Menampilkan informasi usaha dan layanan yang tersedia.

### **Komponen**

**A. Identitas UMKM**

* Logo usaha.  
* Nama usaha.  
* Kategori usaha.  
* Deskripsi singkat.  
* Alamat.  
* Kontak usaha apabila tersedia.

**B. Status Operasional**

* Buka.  
* Tutup.  
* Jeda Sementara.

Status ditampilkan secara jelas agar pelanggan mengetahui apakah antrean dapat diambil.

**C. Daftar Layanan**

Setiap layanan menampilkan:

* Nama layanan.  
* Deskripsi.  
* Estimasi durasi.  
* Harga apabila diisi.  
* Status ketersediaan.

**D. Aksi**

Tombol Ambil Antrean untuk memulai proses pengambilan nomor.

### **Ketentuan**

* Layanan nonaktif tidak ditampilkan.  
* Layanan dengan kuota habis ditampilkan sebagai penuh atau tidak tersedia.  
* Jika usaha tutup atau dijeda, tombol pengambilan antrean dinonaktifkan.  
* Apabila UMKM belum memiliki layanan aktif, tampilkan informasi yang sesuai.

## **5.4 Halaman Pengambilan Antrean**

### **Tujuan**

Memungkinkan pelanggan mengambil nomor antrean secara mandiri.

### **Field**

1. Pilih layanan (wajib).  
2. Nama/label pelanggan (wajib).  
3. Nomor telepon (opsional, sesuai kebutuhan).  
4. Persetujuan terhadap ketentuan pengambilan antrean apabila kebijakan tersebut diterapkan.

### **Interaksi**

1. Pelanggan memilih layanan.  
2. Pelanggan mengisi nama.  
3. Pelanggan memeriksa ringkasan layanan.  
4. Pelanggan menekan tombol Ambil Antrean.  
5. Sistem memvalidasi status UMKM, layanan, dan kuota.  
6. Sistem membuat tiket antrean.  
7. Sistem menampilkan tiket yang berhasil dibuat.

### **Ketentuan**

* Pelanggan tidak perlu membuat akun.  
* Pelanggan tidak boleh memperoleh nomor tiket sebelum proses penyimpanan berhasil.  
* Sistem mencegah pengiriman form berulang yang menyebabkan tiket ganda.  
* Apabila kuota habis ketika pelanggan mengirimkan form, sistem menolak permintaan dan memberikan informasi yang jelas.

## **5.5 Halaman Konfirmasi Tiket**

### **Tujuan**

Menunjukkan bahwa pengambilan antrean berhasil.

### **Informasi**

* Nama UMKM.  
* Nomor tiket.  
* Nama pelanggan.  
* Layanan.  
* Waktu pengambilan.  
* Status antrean.  
* Tautan untuk memantau antrean.

### **Aksi**

* Lihat Status Antrean.  
* Salin Tautan Tiket.  
* Kembali ke Halaman UMKM.

Nomor tiket harus ditampilkan dengan jelas agar pelanggan mudah mengenali antreannya.

## **5.6 Halaman Pemantauan Antrean**

URL: `/ticket/{token}`

### **Tujuan**

Memungkinkan pelanggan melihat kondisi antrean tanpa perlu login.

### **Informasi Utama**

**A. Identitas Tiket**

* Nomor tiket.  
* Nama pelanggan.  
* Nama UMKM.  
* Layanan.

**B. Status Tiket**

* Menunggu.  
* Dipanggil.  
* Sedang dilayani.  
* Selesai.  
* Dilewati.  
* Dibatalkan.

**C. Informasi Antrean**

* Nomor yang sedang dilayani.  
* Jumlah antrean di depan.  
* Estimasi waktu tunggu apabila data memadai.  
* Waktu pengambilan tiket.

**D. Petunjuk Pelayanan**

Contoh informasi yang dapat ditampilkan:

* Silakan menunggu hingga nomor Anda dipanggil.  
* Nomor Anda sedang dipanggil, silakan menuju lokasi pelayanan.  
* Pelayanan untuk nomor Anda sedang berlangsung.  
* Pelayanan telah selesai.  
* Antrean Anda telah dilewati.

### **Mekanisme Pembaruan**

Pada MVP, halaman pelanggan menggunakan pembaruan data berkala (*polling*) untuk mendapatkan informasi status terbaru.

Penggunaan WebSocket untuk pembaruan secara real-time dapat dikembangkan pada tahap selanjutnya.

### **Keamanan Tiket**

* Setiap tiket memiliki token akses unik.  
* Token tidak boleh mudah ditebak.  
* Nomor tiket saja tidak boleh menjadi satu-satunya mekanisme akses detail tiket.  
* Pelanggan hanya dapat melihat tiket yang sesuai dengan token tersebut.  
* Informasi pribadi pelanggan tidak boleh ditampilkan secara berlebihan.

## **5.7 Pembatalan Antrean oleh Pelanggan**

Fitur ini dapat diaktifkan berdasarkan kebijakan sistem.

Rekomendasi aturan:

* Pelanggan hanya dapat membatalkan tiket yang masih berstatus Menunggu.  
* Tiket yang telah dipanggil atau sedang dilayani tidak dapat dibatalkan oleh pelanggan melalui halaman publik.  
* Setelah pembatalan berhasil, status berubah menjadi Dibatalkan.  
* Tiket yang dibatalkan tidak dapat digunakan kembali.  
* UMKM dapat melihat tiket tersebut dalam riwayat.

Apabila fitur pembatalan belum masuk implementasi MVP, tiket cukup dikelola oleh operator.

## **5.8 Kondisi yang Harus Ditangani**

* UMKM tidak ditemukan.  
* UMKM sedang tutup.  
* UMKM sedang jeda.  
* Layanan tidak tersedia.  
* Kuota habis.  
* Pengambilan antrean gagal.  
* Tiket tidak ditemukan.  
* Token tidak valid.  
* Tiket telah selesai.  
* Tiket dibatalkan.  
* Koneksi terputus.

---

# **6\. ALUR UTAMA SISTEM**

## **6.1 Alur Registrasi UMKM**

1. Pemilik membuka halaman AntreIn.  
2. Pemilik memilih Daftar UMKM.  
3. Pemilik mengisi data akun.  
4. Sistem memvalidasi data.  
5. Akun berhasil dibuat.  
6. Pemilik masuk ke dashboard.  
7. Pemilik mengisi profil usaha.  
8. Pemilik mengatur jam operasional.  
9. Pemilik membuat layanan.  
10. Sistem menyiapkan tautan antrean UMKM.

## **6.2 Alur Pengambilan Antrean Online**

1. UMKM membagikan tautan atau QR Code.  
2. Pelanggan membuka halaman UMKM.  
3. Pelanggan melihat status dan layanan yang tersedia.  
4. Pelanggan memilih layanan.  
5. Pelanggan mengisi nama.  
6. Pelanggan menekan Ambil Antrean.  
7. Backend memvalidasi permintaan.  
8. Backend membuat tiket dan nomor antrean.  
9. Pelanggan menerima halaman konfirmasi.  
10. Tiket muncul di dashboard UMKM.

## **6.3 Alur Antrean Walk-in**

1. Pelanggan datang langsung ke UMKM.  
2. Operator membuka menu Antrean.  
3. Operator memilih Tambah Antrean Manual.  
4. Operator memilih layanan.  
5. Operator mengisi nama pelanggan.  
6. Sistem membuat nomor antrean.  
7. Tiket masuk dalam daftar antrean yang sama dengan pelanggan online.

## **6.4 Alur Pelayanan**

1. Operator membuka halaman Antrean.  
2. Operator melihat tiket yang berstatus Menunggu.  
3. Operator menekan Panggil Berikutnya.  
4. Tiket berubah menjadi Dipanggil.  
5. Operator dapat melakukan Panggil Ulang apabila diperlukan.  
6. Operator menekan Mulai Melayani.  
7. Tiket berubah menjadi Sedang dilayani.  
8. Setelah pelayanan selesai, operator menekan Selesaikan.  
9. Tiket berubah menjadi Selesai.  
10. Statistik dan laporan diperbarui.

Jika pelanggan tidak hadir, operator dapat memilih Lewati. Tiket berubah menjadi Dilewati.

## **6.5 Alur Admin Mengelola UMKM**

1. Admin login.  
2. Admin membuka Manajemen UMKM.  
3. Admin mencari UMKM.  
4. Admin melihat detail usaha.  
5. Admin melakukan pengelolaan status apabila dibutuhkan.  
6. Sistem menyimpan perubahan.  
7. Sistem mencatat aktivitas administratif.

## **6.6 Alur Pemantauan Pelanggan**

1. Pelanggan membuka tautan tiket.  
2. Sistem memvalidasi token.  
3. Sistem menampilkan status terbaru.  
4. Sistem memperbarui posisi antrean secara berkala.  
5. Sistem menampilkan instruksi sesuai status.  
6. Setelah antrean selesai atau berakhir, pelanggan tetap dapat melihat status akhir tiket.

---

# **7\. ATURAN BISNIS SISTEM**

Bagian ini merupakan ketentuan utama yang menjadi dasar implementasi database dan backend.

## **7.1 Aturan Penomoran Antrean**

* Nomor antrean dibuat otomatis oleh sistem.  
* Setiap UMKM memiliki urutan nomor tersendiri.  
* Nomor antrean direset setiap hari operasional.  
* Nomor diberikan berdasarkan waktu permintaan berhasil dicatat.  
* Antrean online dan walk-in menggunakan satu urutan penomoran.  
* Satu nomor tidak boleh diberikan kepada dua tiket dalam periode antrean yang sama.  
* Format nomor dapat menggunakan kode atau prefix usaha, diikuti nomor urut, misalnya A-001.

Sistem harus menjamin keunikan nomor meskipun terdapat beberapa permintaan antrean dalam waktu bersamaan.

## **7.2 Aturan Urutan Antrean**

Pada MVP:

* Satu UMKM memiliki satu antrean utama.  
* Semua layanan menggunakan urutan antrean yang sama.  
* Tiket diurutkan berdasarkan urutan pembuatan yang tercatat secara konsisten di database.  
* Antrean online dan walk-in tidak dibedakan dalam prioritas.  
* Sistem menggunakan prinsip FIFO (*First In, First Out*).  
* Operator tidak dapat melewati urutan secara sembarangan melalui fungsi Panggil Berikutnya.

Pengaturan beberapa antrean berdasarkan layanan atau loket dapat dikembangkan pada P1.

## **7.3 Aturan Status Antrean**

| Status | Keterangan | Status Akhir |
| ----- | ----- | ----- |
| Menunggu | Tiket telah dibuat dan menunggu dipanggil | Tidak |
| Dipanggil | Tiket telah dipanggil operator | Tidak |
| Sedang dilayani | Proses pelayanan telah dimulai | Tidak |
| Selesai | Pelayanan telah diselesaikan | Ya |
| Dilewati | Pelanggan dilewati oleh operator | Ya pada MVP |
| Dibatalkan | Tiket dibatalkan sesuai kebijakan | Ya |

Transisi utama:

* Menunggu → Dipanggil.  
* Dipanggil → Sedang dilayani.  
* Sedang dilayani → Selesai.  
* Menunggu → Dibatalkan.  
* Menunggu → Dilewati.  
* Dipanggil → Dilewati.

Setiap transisi yang valid harus mencatat waktu perubahan status.

Transisi yang tidak sesuai aturan harus ditolak oleh backend.

## **7.4 Aturan Operasional UMKM**

* UMKM harus mempunyai minimal satu layanan aktif agar pelanggan dapat mengambil antrean.  
* Status Buka memungkinkan antrean online baru dibuat.  
* Status Tutup menonaktifkan pengambilan antrean online.  
* Status Jeda Sementara menonaktifkan pengambilan antrean online.  
* Antrean yang sudah dibuat tetap dapat diselesaikan ketika usaha ditutup atau dijeda.  
* Perubahan status operasional tidak menghapus tiket yang sudah ada.

## **7.5 Aturan Kuota Layanan**

* Kuota dapat dikonfigurasi per layanan per hari.  
* Kuota tidak wajib diisi.  
* Apabila kuota tidak diatur, sistem tidak menerapkan pembatasan kuota khusus layanan.  
* Tiket Dibatalkan tidak dihitung sebagai kapasitas pelayanan yang terpakai setelah pembatalan berhasil.  
* Tiket Selesai, Menunggu, Dipanggil, dan Sedang dilayani dihitung sebagai penggunaan kuota.  
* Ketika kuota tercapai, layanan tidak dapat menerima tiket baru sampai periode antrean berikutnya atau kuota diubah oleh UMKM.

## **7.6 Aturan Estimasi Waktu**

* Estimasi waktu bukan janji waktu pelayanan.  
* Durasi layanan digunakan sebagai dasar perkiraan.  
* Estimasi hanya ditampilkan apabila data yang dibutuhkan tersedia.  
* Sistem tidak boleh mengarang nilai estimasi saat data tidak mencukupi.  
* Posisi antrean lebih diutamakan daripada estimasi waktu yang belum akurat.

## **7.7 Aturan Data dan Keamanan**

* Setiap data operasional UMKM harus terhubung dengan identitas UMKM.  
* Pemilik UMKM hanya dapat membaca atau mengubah data usaha miliknya.  
* Admin memiliki akses sesuai kewenangan administratif.  
* Halaman publik hanya menampilkan informasi yang memang ditujukan untuk pelanggan.  
* Token tiket harus unik dan sulit ditebak.  
* Perubahan status penting dicatat.  
* Data historis tidak boleh hilang ketika layanan dinonaktifkan atau UMKM dinonaktifkan.  
* Penghapusan data harus mengikuti kebijakan retensi dan kebutuhan audit.

---

# **8\. RANCANGAN STRUKTUR DATABASE KONSEPTUAL**

Rancangan berikut merupakan gambaran entitas awal. Struktur fisik, tipe kolom, indeks, dan relasi final akan ditetapkan ketika ERD disusun.

| Nama Tabel | Fungsi |
| ----- | ----- |
| `users` | Menyimpan akun Admin dan Pemilik UMKM |
| `businesses` | Menyimpan profil usaha |
| `business_categories` | Menyimpan kategori usaha |
| `business_hours` | Menyimpan jadwal operasional per hari |
| `services` | Menyimpan layanan setiap UMKM |
| `daily_queues` | Menyimpan periode antrean harian per UMKM |
| `queue_tickets` | Menyimpan tiket antrean pelanggan |
| `queue_status_histories` | Mencatat riwayat perubahan status tiket |
| `notifications` | Menyimpan notifikasi sistem apabila diimplementasikan |
| `activity_logs` | Mencatat aktivitas penting Admin dan UMKM |
| `queue_sequences` | Mengelola nomor urut tiket secara aman dan konsisten |

### **Hubungan Entitas Konseptual**

* Satu user pemilik dapat memiliki satu UMKM pada MVP.  
* Satu UMKM memiliki satu kategori.  
* Satu UMKM memiliki banyak layanan.  
* Satu UMKM memiliki banyak jadwal operasional.  
* Satu UMKM memiliki banyak periode antrean harian.  
* Satu periode antrean harian memiliki banyak tiket.  
* Satu layanan dapat digunakan oleh banyak tiket.  
* Satu tiket memiliki banyak riwayat perubahan status.  
* Satu pengguna dapat menghasilkan banyak aktivitas sistem.

### **Ketentuan Database**

* Data antrean harus menyimpan relasi UMKM secara eksplisit.  
* Nomor urut harus unik dalam periode antrean dan UMKM terkait.  
* Token tiket harus mempunyai constraint unik.  
* Pembuatan nomor antrean harus menggunakan transaksi database serta mekanisme penguncian yang sesuai untuk mencegah duplikasi nomor.  
* Relasi historis harus tetap valid meskipun suatu layanan dinonaktifkan.

---

# **9\. KEBUTUHAN NONFUNGSIONAL**

## **9.1 Keamanan**

* Password disimpan menggunakan mekanisme hashing yang aman.  
* Autentikasi Admin dan UMKM dipisahkan berdasarkan peran.  
* Otorisasi diterapkan pada backend.  
* Setiap permintaan data UMKM memeriksa kepemilikan atau hak akses.  
* Form menggunakan validasi backend.  
* Proteksi terhadap CSRF dan penyalahgunaan request diterapkan.  
* Endpoint publik dibatasi menggunakan rate limiting sesuai kebutuhan.  
* Token tiket tidak boleh mudah ditebak.  
* Operasi penting menggunakan transaksi database.

## **9.2 Performa**

* Halaman operasional harus dapat memuat data secara efisien.  
* Daftar antrean menggunakan pagination atau mekanisme pemuatan bertahap apabila data besar.  
* Query laporan menggunakan agregasi yang sesuai.  
* Dashboard tidak perlu memuat seluruh riwayat antrean sekaligus.  
* Pembaruan data pelanggan tidak boleh menghasilkan request yang berlebihan.

## **9.3 Responsivitas**

**Admin dan UMKM:**

* Desktop-first.  
* Mendukung layar laptop dan desktop.  
* Tetap dapat dioperasikan melalui tablet.  
* Navigasi menyesuaikan ukuran layar.

**Pelanggan:**

* Mobile-first.  
* Tampilan sederhana dan mudah digunakan melalui ponsel.  
* Tombol pengambilan antrean mudah dijangkau.  
* Nomor tiket ditampilkan dengan ukuran yang jelas.  
* Tidak memerlukan horizontal scrolling.

## **9.4 Konsistensi UI/UX**

* Setiap status menggunakan label teks dan indikator visual.  
* Warna bukan satu-satunya pembeda status.  
* Tombol aksi memiliki fungsi dan umpan balik jelas.  
* Halaman form memiliki validasi.  
* Sistem memiliki loading, empty, error, disabled, dan success states.  
* Aksi yang berdampak besar menggunakan konfirmasi.  
* Antarmuka pelanggan tidak menampilkan elemen dashboard administratif.

## **9.5 Kompatibilitas**

Website ditujukan untuk dapat digunakan melalui browser modern pada perangkat desktop, tablet, dan ponsel.

## **9.6 Ketersediaan dan Konsistensi Data**

* Data tiket yang berhasil dibuat harus tetap tercatat.  
* Perubahan status harus tersimpan sebelum ditampilkan sebagai keberhasilan.  
* Permintaan ganda harus ditangani agar tidak menghasilkan tiket duplikat.  
* Kegagalan koneksi harus ditampilkan secara jelas.  
* Data statistik harus berasal dari sumber data antrean yang konsisten.

---

# **10\. TEKNOLOGI DAN ARSITEKTUR IMPLEMENTASI**

## **10.1 Teknologi yang Direkomendasikan**

| Komponen | Teknologi |
| ----- | ----- |
| Backend | Laravel |
| Bahasa Pemrograman | PHP |
| Database | MySQL |
| Template Frontend | Blade |
| Interaktivitas | Livewire |
| Styling | Tailwind CSS |
| JavaScript Pendukung | Alpine.js |
| Grafik | Chart.js |
| Real-time lanjutan | Laravel Reverb |
| Version Control | Git dan GitHub |
| Development Environment | Laragon / VS Code |

## **10.2 Arsitektur Aplikasi**

AntreIn menggunakan pendekatan monolithic web application, yaitu Admin, UMKM, dan Pelanggan berada dalam satu proyek Laravel.

Pemisahan area dilakukan melalui:

* Route.  
* Middleware autentikasi.  
* Role dan permission.  
* Controller atau Livewire component.  
* Service/business logic.  
* Model dan relasi database.  
* Blade layout yang berbeda untuk setiap area.

## **10.3 Struktur URL**

| Area | Contoh URL |
| ----- | ----- |
| Landing page platform | `/` |
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
| Pengambilan antrean | `/q/{slug}/take-queue` |
| Halaman tiket | `/ticket/{token}` |

## **10.4 Pembagian Layout**

* Admin: layout dashboard administratif dengan sidebar dan topbar.  
* UMKM: layout dashboard operasional dengan sidebar dan topbar.  
* Pelanggan: layout publik minimalis tanpa sidebar dashboard.  
* Landing page: halaman pengenalan AntreIn dan akses registrasi/login.

---

# **11\. PRIORITAS PENGEMBANGAN**

## **11.1 P0 — MVP**

Fitur wajib yang harus tersedia agar AntreIn dapat digunakan secara fungsional.

### **Admin**

* Login Admin.  
* Dashboard platform dasar.  
* Manajemen UMKM.  
* Manajemen pengguna.  
* Monitoring antrean.  
* Manajemen kategori usaha.

### **UMKM**

* Registrasi dan login.  
* Onboarding usaha.  
* Profil usaha.  
* Jam operasional.  
* CRUD layanan.  
* Dashboard usaha.  
* Antrean online dan walk-in.  
* Tambah antrean manual.  
* Panggil berikutnya.  
* Panggil ulang.  
* Mulai melayani.  
* Selesaikan.  
* Lewati.  
* Riwayat antrean.  
* Laporan dasar.  
* Tautan dan QR antrean.  
* Pengaturan dasar.

### **Pelanggan**

* Halaman informasi UMKM.  
* Daftar layanan.  
* Form pengambilan antrean.  
* Nomor tiket otomatis.  
* Halaman konfirmasi.  
* Halaman pemantauan antrean.  
* Pembaruan status melalui polling.

### **Sistem**

* Autentikasi dan otorisasi.  
* Pembatasan akses data per UMKM.  
* Penanganan error.  
* Validasi form.  
* Pencegahan duplikasi tiket.  
* Pencatatan timestamp.  
* Loading, empty, success, dan error states.

## **11.2 P1 — Penyempurnaan**

* Multi-staf.  
* Multi-loket.  
* Pembagian hak akses operator.  
* Pembaruan antrean real-time menggunakan WebSocket.  
* Notifikasi email/browser.  
* Ekspor laporan CSV/PDF.  
* Layar display antrean publik.  
* Ulasan pelanggan.  
* Pengembangan estimasi waktu tunggu berdasarkan data historis.

## **11.3 P2 — Fitur Lanjutan**

* Multi-cabang.  
* Integrasi WhatsApp API.  
* Integrasi POS.  
* Prediksi waktu tunggu berbasis AI/ML.  
* Paket langganan.  
* Sistem pembayaran.  
* Direktori atau marketplace UMKM.  
* Fitur prioritas antrean apabila model bisnis dan kebijakannya telah ditentukan.

---

# **12\. FITUR YANG TIDAK TERMASUK MVP**

Untuk menjaga fokus dan realistisnya implementasi, fitur berikut tidak menjadi kebutuhan versi pertama:

1. Sistem pembayaran digital.  
2. Kasir dan Point of Sale (POS).  
3. Manajemen stok dan inventaris.  
4. Akuntansi dan laporan keuangan.  
5. Penggajian karyawan.  
6. Multi-cabang.  
7. Marketplace atau direktori UMKM.  
8. Sistem reservasi berdasarkan tanggal dan jam.  
9. Prediksi antrean berbasis AI.  
10. Integrasi WhatsApp otomatis.  
11. Printer tiket fisik.  
12. Multi-loket dan penugasan operator.

Fitur tersebut dapat masuk ke roadmap selanjutnya setelah proses antrean inti berhasil diimplementasikan dan diuji.

---

# **13\. KRITERIA KEBERHASILAN IMPLEMENTASI**

AntreIn dianggap berhasil memenuhi fungsi MVP apabila skenario berikut dapat dilakukan dengan benar.

## **13.1 Admin**

* Admin dapat login dan logout.  
* Admin dapat melihat statistik platform.  
* Admin dapat melihat daftar UMKM.  
* Admin dapat melihat detail UMKM.  
* Admin dapat mengubah status UMKM.  
* Admin dapat mengelola kategori usaha.  
* Admin dapat melihat daftar pengguna.  
* Admin dapat memantau tiket dari seluruh UMKM tanpa mengubah alur operasionalnya.

## **13.2 UMKM**

* Pemilik dapat melakukan registrasi dan login.  
* Pemilik dapat melengkapi profil usaha.  
* Pemilik dapat mengatur jam operasional.  
* Pemilik dapat menambahkan dan mengubah layanan.  
* Pemilik dapat melihat antrean online dan walk-in.  
* Pemilik dapat menambahkan antrean manual.  
* Pemilik dapat memanggil antrean sesuai urutan.  
* Pemilik dapat memproses perubahan status tiket.  
* Pemilik dapat melihat riwayat.  
* Pemilik dapat melihat laporan operasional.  
* Pemilik dapat menyalin tautan dan menggunakan QR antrean.

## **13.3 Pelanggan**

* Pelanggan dapat membuka halaman UMKM melalui tautan.  
* Pelanggan dapat melihat layanan aktif.  
* Pelanggan dapat mengambil antrean tanpa registrasi.  
* Pelanggan memperoleh nomor yang unik.  
* Pelanggan dapat membuka kembali tiket menggunakan token.  
* Pelanggan dapat melihat perubahan status antrean.  
* Pelanggan tidak dapat melihat informasi tiket milik pelanggan lain melalui token yang berbeda.

## **13.4 Integrasi**

* Tiket online muncul pada dashboard UMKM.  
* Tiket walk-in masuk ke urutan antrean yang sama.  
* Perubahan status UMKM tercermin pada halaman pelanggan.  
* Perubahan status tiket tercermin pada halaman pemantauan pelanggan.  
* Statistik dashboard sesuai dengan data tiket.  
* Laporan menggunakan timestamp yang benar.  
* Data antar-UMKM terisolasi.

---

# **14\. HAL YANG HARUS DIKUNCI SEBELUM IMPLEMENTASI**

Keputusan berikut menjadi dasar finalisasi ERD, alur backend, dan UI high-fidelity.

| No. | Keputusan | Rekomendasi Awal |
| ----- | ----- | ----- |
| 1 | Jumlah UMKM per akun | Satu UMKM per akun pada MVP |
| 2 | Jumlah operator | Satu operator aktif pada MVP |
| 3 | Sistem antrean | Satu antrean utama per UMKM |
| 4 | Urutan online dan walk-in | FIFO berdasarkan waktu pencatatan |
| 5 | Penomoran tiket | Reset setiap hari per UMKM |
| 6 | Format tiket | Prefix \+ nomor urut |
| 7 | Nama pelanggan | Wajib berupa nama/label |
| 8 | Nomor kontak | Opsional |
| 9 | Kuota layanan | Opsional per layanan per hari |
| 10 | Status Dipanggil dan Dilayani | Dipisahkan |
| 11 | Antrean yang dilewati | Status akhir pada MVP |
| 12 | Pembatalan pelanggan | Hanya sebelum dipanggil apabila diaktifkan |
| 13 | Usaha tutup/jeda | Tidak menerima antrean baru |
| 14 | Tiket aktif ketika tutup | Tetap dapat diselesaikan |
| 15 | Estimasi waktu | Hanya jika data mencukupi |
| 16 | Update pelanggan | Polling pada MVP |
| 17 | Akun pelanggan | Tidak diperlukan |
| 18 | Multi-staf dan loket | P1 |

---

# **15\. PENUTUP**

AntreIn dirancang sebagai platform manajemen antrean digital yang mengintegrasikan kebutuhan Admin AntreIn, UMKM, dan pelanggan dalam satu sistem berbasis website.

Fokus pengembangan tahap awal adalah menyediakan proses pengelolaan antrean yang sederhana dan konsisten, mulai dari pembuatan tiket secara online maupun walk-in, pengaturan layanan, pemanggilan pelanggan, hingga pemantauan status antrean.

Dengan pembagian tiga area pengguna, AntreIn dapat memberikan fungsi yang sesuai untuk setiap pihak tanpa membebani pengguna dengan fitur yang tidak relevan.

Pengembangan dilakukan secara bertahap dengan mengutamakan fungsi inti pada MVP, kemudian diperluas melalui penambahan notifikasi, multi-staf, multi-loket, serta kemampuan analitik lanjutan.

Dokumen ini menjadi dasar untuk penyusunan kebutuhan sistem teknis, Use Case Diagram, Activity Diagram, ERD, rancangan antarmuka, serta implementasi website AntreIn menggunakan Laravel.

