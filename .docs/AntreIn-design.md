# AntreIn — Web Design System

> **Dokumen acuan visual dan UI untuk website AntreIn**  
> Versi: 1.0  
> Status: Draft awal untuk Figma dan implementasi  
> Platform: Responsive Web Application  
> Area: Admin AntreIn, Merchant/UMKM, dan Pelanggan

---

## 1. Tujuan dan cakupan

Dokumen ini menjadi sumber acuan tunggal (*single source of truth*) untuk menjaga konsistensi tampilan AntreIn pada seluruh halaman dan area pengguna. Design system ini berlaku untuk:

1. **Admin AntreIn** — mengelola dan memantau platform.
2. **Merchant/UMKM** — mengelola layanan dan operasional antrean.
3. **Pelanggan** — menemukan informasi usaha, mengambil antrean, dan memantau tiket.

AntreIn adalah **website responsif**, bukan aplikasi mobile native. Ketiga area harus dapat digunakan melalui browser desktop, tablet, dan ponsel. Layout Admin dan UMKM mengutamakan desktop, sedangkan halaman pelanggan harus tetap nyaman di desktop dan sangat mudah digunakan pada layar ponsel.

### Prinsip desain

- **Clear first:** informasi penting harus mudah ditemukan dan dipahami.
- **Operational first:** aksi pengelolaan antrean lebih penting daripada dekorasi.
- **Consistent:** warna, tipografi, status, jarak, dan perilaku komponen harus konsisten.
- **Responsive:** fungsi utama tetap tersedia pada berbagai ukuran layar.
- **Accessible:** warna bukan satu-satunya penanda status; gunakan label, ikon, dan kontras yang memadai.
- **Trustworthy:** status dan data yang ditampilkan harus jelas, tidak menyesatkan, dan memiliki feedback.
- **Scalable:** komponen dirancang agar dapat digunakan ulang di tiga area aplikasi.

---

## 2. Brand direction

### 2.1 Karakter brand

AntreIn memiliki karakter visual:

- Modern
- Minimalis
- Profesional
- Ramah untuk UMKM
- Efisien dan terstruktur
- Terpercaya

Hindari tampilan yang terlalu ramai, terlalu banyak gradasi, dekorasi berlebihan, atau penggunaan warna mencolok pada setiap komponen.

### 2.2 Identitas visual

Gunakan wordmark **AntreIn.** secara konsisten. Ikon brand dapat menggunakan simbol antrean/tiket/list yang sederhana. Logo harus tetap terbaca pada sidebar, header publik, dan ukuran layar kecil.

**Aturan logo:**

- Gunakan versi biru pada latar putih atau sangat terang.
- Gunakan versi putih pada latar navy/biru gelap.
- Jangan mengubah proporsi, jarak ikon dan wordmark, atau warna logo secara bebas.
- Jika aset logo final belum tersedia, gunakan placeholder ikon secara konsisten; jangan membuat varian logo baru pada setiap halaman.

---

## 3. Design tokens

Gunakan token berikut sebagai nilai dasar. Hindari memasukkan nilai warna, radius, atau spacing baru secara acak di setiap halaman. Perubahan token harus dilakukan terpusat dan diterapkan konsisten.

### 3.1 Color palette

#### Brand colors

| Token | Hex | Penggunaan |
|---|---|---|
| `color.brand.700` | `#1E40AF` | Primary brand, tombol utama, link aktif, fokus visual |
| `color.brand.600` | `#2563EB` | Hover primary, elemen interaktif sekunder |
| `color.brand.500` | `#3B82F6` | Aksen biru, grafik, ikon informatif |
| `color.brand.400` | `#60A5FA` | Aksen ringan dan elemen dekoratif terbatas |
| `color.brand.100` | `#DBEAFE` | Background lembut untuk selected state |
| `color.brand.50` | `#EFF6FF` | Background highlight atau panel informasi |

#### Neutral colors

| Token | Hex | Penggunaan |
|---|---|---|
| `color.neutral.950` | `#0F172A` | Teks utama / heading |
| `color.neutral.800` | `#1E293B` | Teks sekunder kuat |
| `color.neutral.700` | `#334155` | Teks isi |
| `color.neutral.600` | `#475569` | Label dan teks pendukung |
| `color.neutral.500` | `#64748B` | Placeholder dan metadata |
| `color.neutral.400` | `#94A3B8` | Ikon atau teks disabled |
| `color.neutral.300` | `#CBD5E1` | Border standar |
| `color.neutral.200` | `#E2E8F0` | Divider dan border lembut |
| `color.neutral.100` | `#F1F5F9` | Background permukaan sekunder |
| `color.neutral.50` | `#F8FAFC` | Background halaman |
| `color.white` | `#FFFFFF` | Card, panel, dan permukaan utama |

#### Semantic colors

| Token | Hex | Penggunaan |
|---|---|---|
| `color.success.700` | `#15803D` | Teks/ikon sukses |
| `color.success.100` | `#DCFCE7` | Background sukses |
| `color.warning.700` | `#B45309` | Teks/ikon peringatan |
| `color.warning.100` | `#FEF3C7` | Background peringatan |
| `color.danger.700` | `#B91C1C` | Teks/ikon bahaya/destruktif |
| `color.danger.100` | `#FEE2E2` | Background bahaya |
| `color.info.700` | `#1D4ED8` | Teks/ikon informasi |
| `color.info.100` | `#DBEAFE` | Background informasi |

#### Queue status colors

Warna status harus selalu disertai label teks. Jangan menggunakan warna saja untuk menyampaikan status.

| Status antrean | Token | Tampilan yang disarankan |
|---|---|---|
| Menunggu | `warning` | Amber background + teks “Menunggu” + ikon jam |
| Dipanggil | `info` | Blue background + teks “Dipanggil” + ikon megafon |
| Sedang dilayani | `brand` | Blue emphasis + teks “Sedang dilayani” + ikon pengguna/check |
| Selesai | `success` | Green background + teks “Selesai” + ikon centang |
| Dilewati | `neutral` | Neutral background + teks “Dilewati” |
| Dibatalkan | `danger` | Red background + teks “Dibatalkan” + ikon batal |

> Status operasional UMKM (Buka, Tutup, Jeda) berbeda dari status tiket antrean. Gunakan badge semantic yang sesuai dan jangan mencampur definisinya.

### 3.2 Color usage rules

- Primary blue digunakan untuk aksi utama dan navigasi aktif, bukan untuk semua elemen.
- Background utama halaman menggunakan `neutral.50` atau putih.
- Card menggunakan putih dengan border lembut.
- Navy/dark blue boleh digunakan pada sidebar Admin sebagai pembeda area administratif, tetapi warna brand tetap terlihat pada logo, active item, dan aksi utama.
- Sidebar UMKM dapat menggunakan putih dengan active state biru muda.
- Customer interface menggunakan header terang dan tombol primary biru.
- Gunakan semantic colors hanya ketika menyampaikan makna status/feedback.
- Jangan menggunakan merah untuk dekorasi atau informasi biasa.

### 3.3 Typography

**Font utama: Inter.** Gunakan satu keluarga font untuk seluruh area agar tampilan konsisten.

| Style | Ukuran | Weight | Line height | Penggunaan |
|---|---:|---:|---:|---|
| Display | 32 px | 700 | 40 px | Angka tiket besar atau hero terbatas |
| H1 | 28 px | 700 | 36 px | Judul halaman utama |
| H2 | 22 px | 700 | 30 px | Judul section |
| H3 | 18 px | 600 | 26 px | Judul card / sub-section |
| Body Large | 16 px | 400 | 24 px | Isi utama dan deskripsi |
| Body | 14 px | 400 | 22 px | Tabel, form, konten umum |
| Label | 13 px | 500 | 18 px | Label input, navigasi, status |
| Caption | 12 px | 400 | 16 px | Metadata, hint, timestamp |
| Micro | 11 px | 500 | 14 px | Metadata padat; gunakan terbatas |

**Aturan tipografi:**

- Gunakan weight 700 untuk heading utama, 600 untuk judul komponen, dan 400 untuk body.
- Hindari terlalu banyak ukuran font dalam satu halaman.
- Gunakan angka tabular (`font-variant-numeric: tabular-nums`) untuk nomor tiket dan metrik.
- Jangan menggunakan ALL CAPS pada teks panjang; boleh digunakan untuk label kecil.
- Pastikan teks tabel dan form tetap terbaca pada layar kecil.

### 3.4 Spacing scale

Gunakan skala berbasis kelipatan 4 px.

| Token | Nilai | Penggunaan umum |
|---|---:|---|
| `space.1` | 4 px | Jarak ikon dan label |
| `space.2` | 8 px | Jarak elemen rapat |
| `space.3` | 12 px | Jarak field dan komponen kecil |
| `space.4` | 16 px | Padding standar komponen |
| `space.5` | 20 px | Jarak kelompok konten |
| `space.6` | 24 px | Padding card / antar section |
| `space.8` | 32 px | Jarak section |
| `space.10` | 40 px | Jarak section besar |
| `space.12` | 48 px | Pemisah area utama |
| `space.16` | 64 px | Ruang layout besar |

**Aturan:**

- Form field dalam satu kelompok: 16–20 px.
- Isi card ke tepi card: 20–24 px.
- Antar card: 16–24 px.
- Padding konten desktop: 24–32 px.
- Padding konten mobile: 16 px.

### 3.5 Border radius

| Token | Nilai | Penggunaan |
|---|---:|---|
| `radius.sm` | 6 px | Badge kecil, elemen kompak |
| `radius.md` | 8 px | Input, tombol kecil |
| `radius.lg` | 12 px | Card standar, dropdown |
| `radius.xl` | 16 px | Card besar, modal |
| `radius.2xl` | 20 px | Panel hero atau kartu tiket pelanggan |
| `radius.full` | 9999 px | Avatar, chip, status pill |

Gunakan radius secara konsisten. Hindari mencampurkan card yang sangat membulat dengan komponen lain yang terlalu tajam.

### 3.6 Border dan elevation

| Token | Nilai | Penggunaan |
|---|---|---|
| `border.subtle` | `1px solid #E2E8F0` | Border card dan pemisah |
| `border.default` | `1px solid #CBD5E1` | Input dan komponen interaktif |
| `border.focus` | `2px solid #3B82F6` | Focus ring |
| `shadow.sm` | `0 1px 2px rgba(15,23,42,.05)` | Card standar |
| `shadow.md` | `0 4px 12px rgba(15,23,42,.08)` | Dropdown dan card elevated |
| `shadow.lg` | `0 12px 28px rgba(15,23,42,.12)` | Modal dan overlay |

Jangan memberikan shadow tebal pada semua card. Border halus lebih diutamakan untuk menjaga tampilan bersih.

---

## 4. Responsive layout system

AntreIn adalah satu website responsif. Jangan membuat versi aplikasi native terpisah.

### 4.1 Breakpoints

Gunakan breakpoint berikut sebagai standar awal:

| Nama | Lebar viewport | Perilaku |
|---|---:|---|
| `sm` | 640 px | Ponsel besar / layout form mulai melebar |
| `md` | 768 px | Tablet; sidebar dapat menjadi drawer |
| `lg` | 1024 px | Laptop kecil; dashboard mulai menggunakan layout desktop |
| `xl` | 1280 px | Desktop standar |
| `2xl` | 1536 px | Desktop lebar; batasi lebar konten agar tetap mudah dibaca |

### 4.2 Container

- Desktop: gunakan konten selebar ruang yang tersedia, dengan padding horizontal 24–32 px.
- Desktop sangat lebar: pertimbangkan batas lebar konten sekitar 1600 px.
- Tablet: padding horizontal 20–24 px.
- Mobile: padding horizontal 16 px.
- Hindari konten utama menempel pada tepi viewport.

### 4.3 Admin dan UMKM

**Desktop (>= 1024 px):**
- Sidebar tetap di kiri.
- Topbar berada di atas area konten.
- Konten dashboard menggunakan grid.
- Tabel dapat ditampilkan dalam bentuk tabel penuh.

**Tablet (768–1023 px):**
- Sidebar dapat diperkecil menjadi icon rail atau dibuka sebagai drawer.
- Kartu statistik menyesuaikan menjadi dua kolom.
- Area grafik dapat ditumpuk jika lebar tidak mencukupi.

**Mobile (< 768 px):**
- Sidebar menjadi drawer atau navigasi ringkas.
- Kartu statistik ditampilkan satu atau dua kolom sesuai lebar.
- Tabel berubah menjadi daftar/kartu, atau menyediakan kolom penting saja.
- Aksi utama tetap mudah dijangkau.
- Jangan memaksa horizontal scrolling pada halaman operasional inti.

### 4.4 Customer interface

- Desktop: gunakan layout website dengan header, konten usaha, daftar layanan, dan panel tiket/antrean bila relevan.
- Tablet: gunakan satu atau dua kolom sesuai kebutuhan.
- Mobile: susun menjadi satu kolom; CTA utama dapat dibuat lebar penuh.
- Informasi nomor tiket harus tetap dominan pada semua ukuran layar.
- Jangan tampilkan elemen dashboard Admin/UMKM pada halaman publik pelanggan.

---

## 5. Application shell per area

Ketiga area berbagi design tokens dan komponen dasar, tetapi struktur navigasinya boleh berbeda sesuai tugas pengguna.

### 5.1 Admin shell

**Karakter:** platform management, informatif, terstruktur.

- Sidebar: navy/dark blue.
- Active navigation: blue yang jelas dengan teks/icon kontras.
- Topbar: putih, berisi breadcrumb/judul konteks, notifikasi, dan profil Admin.
- Main background: `neutral.50`.
- Card: putih dengan border subtle.

**Navigasi utama:**

1. Dashboard
2. Manajemen UMKM
3. Manajemen Pengguna
4. Monitoring Antrean
5. Kategori UMKM
6. Pengaturan Platform

### 5.2 Merchant/UMKM shell

**Karakter:** operasional, cepat dipindai, tidak membebani operator.

- Sidebar: putih atau neutral terang.
- Active navigation: `brand.50` dengan teks/icon `brand.700`.
- Topbar: putih, menampilkan konteks usaha, notifikasi, dan profil.
- Main background: `neutral.50`.
- Card: putih dengan border subtle.

**Navigasi utama:**

1. Beranda
2. Antrean
3. Layanan
4. Laporan
5. Pengaturan

**Aturan khusus:**
- Area *Now Serving* harus menjadi elemen paling menonjol pada halaman antrean.
- Tombol operasional utama harus terlihat tanpa membuka menu tambahan.
- Jangan memenuhi halaman beranda dengan semua data historis.

### 5.3 Customer shell

**Karakter:** sederhana, ramah, fokus menyelesaikan satu tugas.

- Header website sederhana dengan logo AntreIn dan navigasi seperlunya.
- Tidak menggunakan sidebar dashboard.
- Informasi usaha dan layanan menjadi konten utama.
- CTA “Ambil Antrean” harus mudah ditemukan.
- Halaman tiket memiliki nomor antrean dan status sebagai fokus visual.
- Footer ringkas, misalnya “Powered by AntreIn”.

---

## 6. Component library

Komponen di bawah perlu dibuat sebagai reusable component di Figma dan diimplementasikan secara reusable pada Laravel/Blade/Livewire.

### 6.1 Buttons

#### Variants

| Variant | Penggunaan |
|---|---|
| Primary | Aksi utama seperti Ambil Antrean, Simpan, Panggil Berikutnya |
| Secondary | Aksi pendamping seperti Batal atau Kembali |
| Outline | Aksi alternatif seperti Lihat Detail |
| Ghost | Aksi ringan seperti navigasi atau menu |
| Destructive | Hapus, nonaktifkan, batalkan |
| Icon button | Aksi ikon dengan tooltip/accessible label |

#### Sizes

- Small: tinggi 32 px.
- Medium: tinggi 40 px.
- Large: tinggi 48 px.

#### States

- Default
- Hover
- Focus
- Active/Pressed
- Disabled
- Loading

Aturan: setiap tombol memiliki label aksi yang jelas. Jangan menggunakan ikon saja untuk aksi penting kecuali tersedia tooltip dan accessible label.

### 6.2 Inputs and forms

Komponen:

- Text input
- Email input
- Password input
- Number input
- Textarea
- Select/dropdown
- Search field
- Checkbox
- Radio
- Toggle/switch
- Date picker
- Time picker
- File upload

Standar:

- Tinggi input standar: 44 px.
- Label berada di atas input.
- Helper text berada di bawah input.
- Error text berada dekat field terkait.
- Field wajib diberi penanda yang konsisten.
- Jangan hanya mengandalkan placeholder sebagai label.

States:

- Default
- Hover
- Focus
- Filled
- Disabled
- Error
- Success

### 6.3 Cards

Jenis card:

- Stat card
- Content card
- Queue ticket card
- Now Serving card
- Information card
- Empty-state card

Aturan:

- Gunakan padding 20–24 px untuk card desktop.
- Gunakan padding 16 px untuk card mobile.
- Judul dan isi harus memiliki hierarki yang jelas.
- Jangan membuat semua card memiliki warna latar berbeda tanpa alasan fungsional.

### 6.4 Status badge

Badge harus menampilkan teks status dan dapat disertai ikon.

- Tinggi visual sekitar 24–28 px.
- Padding horizontal 8–10 px.
- Radius full.
- Ukuran teks 12–13 px dengan weight 500 atau 600.

Status tiket harus menggunakan mapping warna pada bagian 3.1.

### 6.5 Data table

Digunakan terutama pada Admin dan halaman Antrean UMKM.

Komponen:

- Header kolom
- Data row
- Checkbox selection (jika diperlukan)
- Status badge
- Row action menu
- Pagination
- Search dan filter
- Empty state

Aturan:

- Header tabel harus jelas.
- Hindari terlalu banyak kolom; prioritaskan informasi yang dibutuhkan untuk mengambil keputusan.
- Angka dan waktu disejajarkan secara konsisten.
- Aksi destruktif ditempatkan terpisah dari aksi rutin.
- Mobile menggunakan card/list layout atau kolom prioritas, bukan tabel yang memaksa scroll horizontal berlebihan.

### 6.6 Navigation

Komponen:

- Sidebar
- Sidebar item
- Topbar
- Breadcrumb
- Tabs
- Mobile drawer
- Pagination/navigation link

Aturan active state:

- Active state harus terlihat melalui background, teks, dan/atau ikon.
- Jangan hanya membedakan active state dengan warna yang terlalu samar.
- Navigasi tidak boleh berubah urutan antarhalaman tanpa alasan.

### 6.7 Feedback and overlay

Komponen:

- Toast/snackbar
- Alert banner
- Confirmation dialog
- Modal
- Drawer
- Tooltip
- Dropdown menu
- Loading spinner
- Skeleton

Gunakan feedback setelah aksi penting, misalnya antrean berhasil dibuat, status berubah, layanan disimpan, atau data gagal dimuat.

### 6.8 Data visualization

Gunakan grafik sederhana dan hanya jika membantu keputusan.

- Line chart: tren antrean dari waktu ke waktu.
- Bar chart: perbandingan antrean per layanan atau periode.
- Donut chart: distribusi status jika jumlah kategori sedikit.
- Tooltip dan label harus jelas.
- Sertakan periode data.
- Jangan menggunakan grafik ketika data tidak mencukupi.
- Jangan mengganti nilai yang tidak tersedia menjadi nol.

---

## 7. Queue-specific UI patterns

Karena pengelolaan antrean adalah fungsi inti AntreIn, pola komponen berikut harus konsisten di seluruh aplikasi.

### 7.1 Queue ticket identity

Setiap tiket menampilkan:

- Nomor tiket.
- Nama/label pelanggan sesuai hak akses.
- Nama layanan.
- Sumber: Online atau Walk-in.
- Waktu dibuat.
- Status.

Nomor tiket harus menggunakan angka tabular dan mudah dipindai.

### 7.2 Now Serving

- Menampilkan satu tiket yang sedang dipanggil atau sedang dilayani untuk model satu operator pada MVP.
- Nomor tiket menjadi elemen visual paling dominan.
- Status harus dibedakan dengan jelas.
- Aksi yang tersedia mengikuti status.
- Jangan menampilkan aksi yang tidak valid.

### 7.3 Queue actions

| Status saat ini | Aksi yang dapat ditampilkan |
|---|---|
| Menunggu | Panggil, lihat detail |
| Dipanggil | Panggil ulang, Mulai Melayani, Lewati |
| Sedang dilayani | Selesaikan |
| Selesai | Lihat detail |
| Dilewati | Lihat detail |
| Dibatalkan | Lihat detail |

Aksi harus tetap divalidasi oleh backend. UI bukan satu-satunya pengaman transisi status.

### 7.4 Source indicator

Gunakan label yang konsisten:

- `Online` — pelanggan mengambil antrean melalui halaman publik.
- `Walk-in` — tiket dibuat oleh operator untuk pelanggan yang datang langsung.

Jangan menggunakan warna sebagai satu-satunya pembeda sumber.

---

## 8. Page patterns

### 8.1 Dashboard page

Struktur standar:

1. Page heading dan deskripsi singkat.
2. Filter periode atau konteks halaman (jika relevan).
3. Stat cards.
4. Konten prioritas (misalnya Now Serving atau grafik utama).
5. Tabel/daftar data terbaru.
6. Aksi tambahan.

Tidak semua dashboard harus memiliki semua bagian. Tampilkan hanya data yang relevan untuk peran pengguna.

### 8.2 List page

Struktur standar:

1. Judul dan deskripsi.
2. Primary action.
3. Search dan filter.
4. Summary (opsional).
5. Table/list.
6. Pagination.
7. Empty/loading/error state.

### 8.3 Form page

Struktur standar:

1. Judul dan tujuan form.
2. Field dikelompokkan secara logis.
3. Helper text untuk field yang berpotensi membingungkan.
4. Error dekat field.
5. Primary action dan secondary action.
6. Konfirmasi jika perubahan berdampak besar.

### 8.4 Customer public page

Struktur standar:

1. Header AntreIn.
2. Identitas UMKM dan status buka/tutup.
3. Daftar layanan.
4. Form pengambilan antrean setelah layanan dipilih.
5. Konfirmasi tiket.
6. Halaman tracking tiket.

### 8.5 Empty, loading, error states

Setiap halaman yang bergantung pada data harus memiliki:

- **Loading:** skeleton yang menjaga layout tetap stabil.
- **Empty:** penjelasan mengapa belum ada data dan CTA relevan.
- **No results:** pesan bahwa filter/pencarian tidak menemukan data.
- **Error:** pesan yang menjelaskan kegagalan dan aksi coba lagi bila memungkinkan.
- **Success:** feedback setelah operasi berhasil.

Jangan menampilkan angka `0` ketika data sebenarnya gagal dimuat. Bedakan kondisi tidak ada data dari kondisi gagal mengambil data.

---

## 9. Accessibility and usability

- Semua kontrol interaktif dapat dioperasikan dengan keyboard.
- Focus state harus terlihat.
- Gunakan label form yang jelas dan terhubung dengan input.
- Gunakan kontras teks yang memadai; hindari teks abu-abu terlalu muda pada background putih.
- Ikon yang tidak jelas harus disertai label atau tooltip.
- Status antrean selalu memiliki teks, bukan warna saja.
- Ukuran area sentuh pada perangkat ponsel disarankan minimal sekitar 44 × 44 px.
- Jangan mengandalkan hover sebagai satu-satunya cara menampilkan informasi.
- Gunakan bahasa Indonesia yang konsisten pada label UI.

---

## 10. Content and microcopy guidelines

Gunakan bahasa yang singkat, jelas, sopan, dan konsisten.

| Kondisi | Copy yang disarankan |
|---|---|
| Berhasil membuat antrean | “Nomor antrean berhasil dibuat.” |
| Berhasil menyimpan layanan | “Layanan berhasil disimpan.” |
| Gagal memuat data | “Data belum dapat dimuat. Coba lagi.” |
| Tidak ada antrean | “Belum ada antrean untuk saat ini.” |
| Kuota penuh | “Kuota layanan hari ini sudah penuh.” |
| UMKM tutup | “Usaha sedang tutup dan belum menerima antrean baru.” |
| UMKM jeda | “Pengambilan antrean sedang dijeda sementara.” |
| Tiket dipanggil | “Nomor Anda sedang dipanggil. Silakan menuju lokasi pelayanan.” |
| Tiket selesai | “Pelayanan Anda telah selesai. Terima kasih.” |
| Aksi destruktif | “Tindakan ini tidak dapat dibatalkan. Lanjutkan?” |

Hindari copy yang menyalahkan pengguna atau menjanjikan waktu tunggu yang belum pasti.

---

## 11. Iconography and imagery

- Gunakan satu keluarga ikon yang konsisten, misalnya Lucide.
- Ukuran ikon standar: 16, 20, dan 24 px.
- Ikon navigasi sidebar umumnya 20 px.
- Ikon aksi dalam tombol umumnya 16–18 px.
- Gunakan ikon yang mudah dikenali dan tidak dekoratif berlebihan.
- Foto usaha/layanan digunakan hanya pada halaman publik UMKM jika tersedia.
- Avatar dan logo usaha harus memiliki fallback ketika gambar tidak tersedia.
- Hindari ilustrasi stok yang tidak relevan dengan konteks antrean.

---

## 12. Figma organization and naming convention

Gunakan penamaan yang konsisten agar design file mudah dikelola.

### 12.1 Figma pages

1. `00 - Cover`
2. `01 - Design System`
3. `02 - Admin`
4. `03 - Merchant`
5. `04 - Customer`
6. `05 - Responsive`
7. `06 - Prototype`

### 12.2 Component naming

Gunakan pola:

`Category / Component / Variant / State`

Contoh:

- `Button / Primary / Medium / Default`
- `Button / Primary / Medium / Loading`
- `Badge / Queue Status / Waiting`
- `Navigation / Sidebar / Admin / Active`
- `Card / Stat / Default`
- `Table / Queue / Row / Default`
- `Input / Text / Error`
- `Modal / Confirmation / Default`

### 12.3 Variables naming

Contoh nama variable:

- `color/brand/700`
- `color/neutral/50`
- `color/status/warning/100`
- `spacing/4`
- `radius/lg`
- `shadow/md`
- `typography/body/md`

Gunakan variables untuk warna, spacing, radius, dan typography agar perubahan bisa diterapkan secara terpusat.

---

## 13. Implementation guidelines — Laravel

Design system ini dapat diterapkan dalam satu proyek Laravel dengan Blade, Livewire, Tailwind CSS, dan Alpine.js sesuai kebutuhan.

### 13.1 Struktur UI yang disarankan

- Layout Admin terpisah dari layout Merchant.
- Customer menggunakan public layout tanpa sidebar dashboard.
- Komponen visual dasar dibuat reusable.
- Komponen yang memuat aksi/data menggunakan Livewire atau controller sesuai kompleksitasnya.
- Alpine.js digunakan untuk interaksi ringan seperti dropdown, drawer, dan disclosure.

### 13.2 Komponen reusable

Contoh kelompok komponen:

- `Button`
- `Badge`
- `Card`
- `StatCard`
- `PageHeader`
- `Sidebar`
- `Topbar`
- `FormField`
- `SearchInput`
- `DataTable`
- `Pagination`
- `Modal`
- `Toast`
- `EmptyState`
- `LoadingSkeleton`
- `QueueTicket`
- `QueueStatusBadge`
- `NowServingCard`

### 13.3 Aturan implementasi

- Simpan warna dan token dasar pada konfigurasi/theme terpusat.
- Hindari hardcode nilai warna dan radius berulang di banyak file.
- Gunakan utility class berbasis token atau CSS variables.
- Pertahankan validasi dan otorisasi di backend.
- Pastikan UI state sesuai status antrean dari server.
- Setiap aksi async harus memiliki loading, success, dan error feedback.
- Periksa layout pada viewport desktop, tablet, dan mobile.

### 13.4 Suggested CSS custom properties

```css
:root {
  --color-brand-700: #1E40AF;
  --color-brand-600: #2563EB;
  --color-brand-500: #3B82F6;
  --color-brand-100: #DBEAFE;
  --color-brand-50: #EFF6FF;

  --color-text-primary: #0F172A;
  --color-text-secondary: #475569;
  --color-text-muted: #64748B;
  --color-border: #E2E8F0;
  --color-surface: #FFFFFF;
  --color-page: #F8FAFC;

  --radius-sm: 6px;
  --radius-md: 8px;
  --radius-lg: 12px;
  --radius-xl: 16px;
  --radius-2xl: 20px;

  --shadow-sm: 0 1px 2px rgba(15, 23, 42, 0.05);
  --shadow-md: 0 4px 12px rgba(15, 23, 42, 0.08);

  --font-sans: "Inter", ui-sans-serif, system-ui, sans-serif;
}
```

---

## 14. Design quality checklist

Gunakan checklist ini sebelum halaman dianggap selesai.

### Visual consistency

- [ ] Warna berasal dari token design system.
- [ ] Typography mengikuti skala yang ditentukan.
- [ ] Spacing dan radius konsisten.
- [ ] Ikon berasal dari keluarga ikon yang sama.
- [ ] Tidak ada dekorasi yang mengganggu informasi utama.

### Component consistency

- [ ] Tombol memiliki variant dan state yang jelas.
- [ ] Input memiliki label, helper, dan error state.
- [ ] Status tiket menggunakan mapping warna dan label yang konsisten.
- [ ] Card dan table menggunakan pola yang sama.
- [ ] Modal, toast, dan feedback memiliki perilaku konsisten.

### Responsive behavior

- [ ] Desktop layout rapi pada 1280–1440 px.
- [ ] Tablet tidak mengalami tabrakan konten.
- [ ] Mobile tidak memiliki horizontal scrolling yang tidak perlu.
- [ ] Sidebar berubah menjadi drawer/compact navigation pada layar kecil.
- [ ] CTA penting tetap mudah dijangkau.

### Functional UX

- [ ] Aksi utama terlihat jelas.
- [ ] Loading, empty, error, success, dan disabled states tersedia.
- [ ] Informasi tidak tersedia tidak ditampilkan sebagai angka nol.
- [ ] Status antrean tidak hanya dibedakan melalui warna.
- [ ] UI tidak menampilkan aksi yang tidak valid untuk status terkait.

### Area-specific

- [ ] Admin berfokus pada monitoring dan manajemen platform.
- [ ] UMKM berfokus pada operasional usaha dan antrean.
- [ ] Pelanggan berfokus pada informasi usaha, pengambilan, dan tracking antrean.
- [ ] Tidak ada menu atau data internal Admin/UMKM yang bocor ke halaman publik.

---

## 15. Decisions and items to validate

Nilai dalam dokumen ini adalah baseline design system awal. Beberapa hal perlu dikonfirmasi sebelum dianggap final:

1. Logo dan aset identitas AntreIn final.
2. Apakah warna navy sidebar Admin akan dipertahankan atau diseragamkan dengan sidebar Merchant.
3. Finalisasi typography dan ukuran heading berdasarkan desain high-fidelity.
4. Finalisasi breakpoint setelah pengujian layout nyata.
5. Finalisasi status operasional UMKM dan status tiket sesuai aturan backend.
6. Finalisasi komponen tabel, form, dan modal setelah halaman inti selesai dirancang.
7. Audit kontras warna dan aksesibilitas pada komponen final.

**Prinsip perubahan:** jika token atau komponen berubah, perbarui dokumen ini dan terapkan perubahan secara konsisten pada Admin, Merchant, dan Customer. Jangan melakukan perubahan lokal yang membuat satu area menyimpang tanpa alasan produk yang jelas.

---

**AntreIn Design System — End of Document**
