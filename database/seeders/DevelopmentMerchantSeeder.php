<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\BusinessCounter;
use App\Models\BusinessHour;
use App\Models\BusinessMembership;
use App\Models\BusinessOrganization;
use App\Models\CustomerReview;
use App\Models\DailyQueue;
use App\Models\OrganizationMembership;
use App\Models\QueueTicket;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DevelopmentMerchantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (app()->isProduction() && ! config('app.demo_data_enabled')) {
            $this->command?->warn('DevelopmentMerchantSeeder: environment production terdeteksi. Data contoh dilewati demi keamanan (set DEMO_DATA_ENABLED=true untuk server demo).');

            return;
        }

        $categories = BusinessCategory::all()->keyBy('slug');
        $admin = User::where('role', User::ROLE_ADMIN)->first();

        // 1. Seed diverse Bali/Indonesian Customer Accounts (including primary demo customer)
        $customers = $this->seedCustomers();

        // 2. Seed Primary Demo Business + Multi-Branch Cabang (used by DevDemoService & role switcher)
        $primaryBusiness = $this->provisionBusiness(
            ownerEmail: 'owner@garasi.id',
            ownerName: 'Made Aryawan',
            ownerPassword: 'merchant12345',
            operatorEmail: 'operator@garasi.id',
            operatorName: 'Wayan Sudirga',
            operatorPassword: 'operator12345',
            businessData: [
                'category_id' => $categories->get('barbershop-pangkas-rambut')?->id,
                'name' => 'Barbershop Garasi Denpasar',
                'slug' => 'barbershop-garasi-denpasar',
                'description' => 'Barbershop modern pria dengan layanan potong rambut presisi, cukur higienis, dan styling profesional.',
                'address' => 'Jl. Teuku Umar No. 88, Denpasar Barat, Bali',
                'area' => 'Denpasar',
                'phone' => '081234567890',
                'timezone' => 'Asia/Makassar',
                'status' => Business::STATUS_BUKA,
                'approval_status' => Business::APPROVAL_APPROVED,
                'reviewed_at' => now()->subDays(14),
                'reviewed_by' => $admin?->id,
                'is_suspended' => false,
            ],
            counters: [
                ['code' => 'L-01', 'name' => 'Pos Layanan 1'],
                ['code' => 'L-02', 'name' => 'Pos Layanan 2'],
            ],
            services: [
                ['name' => 'Potong Rambut Dewasa', 'description' => 'Cuci rambut, pangkas model pria, pijat kepala ringan, dan pomade.', 'duration_minutes' => 30, 'price' => 45000, 'daily_quota' => 30],
                ['name' => 'Cukur Jenggot & Kumis', 'description' => 'Shaving razor higienis dengan handuk hangat.', 'duration_minutes' => 15, 'price' => 25000, 'daily_quota' => 20],
                ['name' => 'Hair Spa & Coloring', 'description' => 'Perawatan kulit kepala plus pewarnaan natural.', 'duration_minutes' => 45, 'price' => 110000, 'daily_quota' => 12],
                ['name' => 'Skin Fade & Taper Cut', 'description' => 'Gradasi tipis presisi dengan clipper dan detail razor.', 'duration_minutes' => 35, 'price' => 55000, 'daily_quota' => 25],
                ['name' => 'Potong Rambut Anak', 'description' => 'Gunting rambut ramah anak usia 3–12 tahun dengan styling ringan.', 'duration_minutes' => 25, 'price' => 35000, 'daily_quota' => 20],
                ['name' => 'Paket Grooming Komplit', 'description' => 'Potong rambut, cuci bersih, cukur jenggot, dan pijat bahu.', 'duration_minutes' => 50, 'price' => 85000, 'daily_quota' => 15],
                ['name' => 'Black Mask Facial Peel', 'description' => 'Pembersihan komedo wajah dan masker arang aktif.', 'duration_minutes' => 25, 'price' => 40000, 'daily_quota' => 15],
                ['name' => 'Creambath Mint Segar', 'description' => 'Nutrisi rambut ekstrak peppermint untuk kulit kepala segar.', 'duration_minutes' => 30, 'price' => 60000, 'daily_quota' => 15],
                ['name' => 'Hair Tattoo & Line Up', 'description' => 'Desain garis rambut presisi dan perapihan hairline.', 'duration_minutes' => 20, 'price' => 35000, 'daily_quota' => 18],
                ['name' => 'Perming / Keriting Pria', 'description' => 'Wave perm natural tahan hingga 2 bulan.', 'duration_minutes' => 60, 'price' => 185000, 'daily_quota' => 8],
                ['name' => 'Down Perm Samping', 'description' => 'Merapikan rambut samping yang mengembang agar rapi natural.', 'duration_minutes' => 35, 'price' => 95000, 'daily_quota' => 10],
                ['name' => 'Wash & Blow Styling', 'description' => 'Keramas relaksasi plus penataan rambut untuk acara khusus.', 'duration_minutes' => 20, 'price' => 30000, 'daily_quota' => 25],
            ],
        );

        // The organisation is named after its first branch by default; give it the brand name instead.
        BusinessOrganization::whereKey($primaryBusiness->organization_id)->update(['name' => 'Barbershop Garasi']);

        // Second branch under the same owner organization (Canggu)
        $primarySecondBranch = $this->provisionBusiness(
            ownerEmail: 'owner@garasi.id',
            ownerName: 'Made Aryawan',
            ownerPassword: 'merchant12345',
            operatorEmail: 'operator.canggu@garasi.id',
            operatorName: 'Kadek Mahendra',
            operatorPassword: 'operator12345',
            businessData: [
                'organization_id' => $primaryBusiness->organization_id,
                'category_id' => $categories->get('barbershop-pangkas-rambut')?->id,
                'name' => 'Garasi Grooming Canggu',
                'slug' => 'garasi-grooming-canggu',
                'description' => 'Cabang Canggu untuk perawatan rambut modern, styling cepat, dan grooming ekspres.',
                'address' => 'Jl. Pantai Batu Bolong No. 64, Canggu, Badung, Bali',
                'area' => 'Canggu',
                'phone' => '081234567899',
                'timezone' => 'Asia/Makassar',
                'status' => Business::STATUS_BUKA,
                'approval_status' => Business::APPROVAL_APPROVED,
                'reviewed_at' => now()->subDays(10),
                'reviewed_by' => $admin?->id,
                'is_suspended' => false,
            ],
            counters: [
                ['code' => 'L-01', 'name' => 'Station A'],
                ['code' => 'L-02', 'name' => 'Station B'],
            ],
            services: [
                ['name' => 'Express Cut & Wash', 'description' => 'Pangkas rapi dan cuci rambut kilat.', 'duration_minutes' => 25, 'price' => 50000, 'daily_quota' => 30],
                ['name' => 'Beard Trim & Hot Towel', 'description' => 'Perawatan jenggot lengkap dengan handuk hangat.', 'duration_minutes' => 20, 'price' => 30000, 'daily_quota' => 20],
            ],
        );

        // 3. Seed Flagship Kopi Senja Organization (3 branches: Canggu, Seminyak, Ubud) + 7 other UMKMs covering ALL 10 Business::AREAS
        $kopiCanggu = $this->provisionBusiness(
            ownerEmail: 'owner@kopisenja.id',
            ownerName: 'Nadia Kusuma',
            ownerPassword: 'merchant12345',
            operatorEmail: 'operator@kopisenja.id',
            operatorName: 'Bima Sakti',
            operatorPassword: 'operator12345',
            businessData: [
                'category_id' => $categories->get('kuliner-kafe')?->id,
                'name' => 'Kopi Senja & Roastery',
                'slug' => 'kopi-senja-roastery',
                'description' => 'Kedai kopi spesialti dengan manual brew, espresso signature, dan ruang kerja nyaman di Canggu.',
                'address' => 'Jl. Pantai Berawa No. 42, Canggu, Badung, Bali',
                'area' => 'Canggu',
                'phone' => '081288990011',
                'timezone' => 'Asia/Makassar',
                'status' => Business::STATUS_BUKA,
                'approval_status' => Business::APPROVAL_APPROVED,
                'reviewed_at' => now()->subDays(12),
                'reviewed_by' => $admin?->id,
                'is_suspended' => false,
            ],
            counters: [
                ['code' => 'L-01', 'name' => 'Barista Counter 1'],
                ['code' => 'L-02', 'name' => 'Barista Counter 2'],
                ['code' => 'L-03', 'name' => 'Meja Pengambilan'],
            ],
            services: [
                ['name' => 'Pesan Kopi & Manual Brew', 'description' => 'Antrean pemesanan minuman kopi espresso atau V60.', 'duration_minutes' => 10, 'price' => 32000, 'daily_quota' => 80],
                ['name' => 'Reservasi Meja Indoor (2-4 Orang)', 'description' => 'Antrean duduk area ber-AC lengkap dengan colokan kerja.', 'duration_minutes' => 20, 'price' => 0, 'daily_quota' => 35],
                ['name' => 'Take Away Paket Sarapan', 'description' => 'Kopi susu aren + croissant hangat siap bawa.', 'duration_minutes' => 10, 'price' => 55000, 'daily_quota' => 50],
                ['name' => 'Es Kopi Susu Gula Aren', 'description' => 'Signature house blend dengan susu segar dan gula aren organik.', 'duration_minutes' => 8, 'price' => 28000, 'daily_quota' => 90],
                ['name' => 'V60 Single Origin Kintamani', 'description' => 'Seduh manual pour-over biji arabika Bali dengan aroma citrus.', 'duration_minutes' => 12, 'price' => 38000, 'daily_quota' => 45],
                ['name' => 'Matcha Uji Latte & Croissant', 'description' => 'Matcha ceremonial grade dengan butter croissant renyah.', 'duration_minutes' => 10, 'price' => 62000, 'daily_quota' => 40],
                ['name' => 'Cold Brew Botolan 500ml', 'description' => 'Kopi seduh dingin 16 jam siap bawa pulang.', 'duration_minutes' => 5, 'price' => 58000, 'daily_quota' => 35],
                ['name' => 'Meja Outdoor Terrace (4 Orang)', 'description' => 'Antrean area teras santai dengan angin pantai Berawa.', 'duration_minutes' => 20, 'price' => 0, 'daily_quota' => 30],
                ['name' => 'Paket Brunch Avocado Toast', 'description' => 'Sourdough panggang, alpukat, poached egg, dan Americano.', 'duration_minutes' => 15, 'price' => 75000, 'daily_quota' => 30],
                ['name' => 'Konsultasi Biji Kopi Roastery', 'description' => 'Cicipi dan pilih biji kopi sangrai segar untuk stok rumah.', 'duration_minutes' => 15, 'price' => 0, 'daily_quota' => 20],
                ['name' => 'Catering Coffee Box (10 Cup)', 'description' => 'Pengambilan paket kopi literan untuk rapat kantor atau acara.', 'duration_minutes' => 15, 'price' => 220000, 'daily_quota' => 15],
                ['name' => 'Quiet Focus Booth (1 Orang)', 'description' => 'Meja kerja individu tenang lengkap dengan colokan & lampu baca.', 'duration_minutes' => 15, 'price' => 0, 'daily_quota' => 25],
            ],
        );

        $kopiSeminyak = $this->provisionBusiness(
            ownerEmail: 'owner@kopisenja.id',
            ownerName: 'Nadia Kusuma',
            ownerPassword: 'merchant12345',
            operatorEmail: 'operator.seminyak@kopisenja.id',
            operatorName: 'Tari Wulandari',
            operatorPassword: 'operator12345',
            businessData: [
                'organization_id' => $kopiCanggu->organization_id,
                'category_id' => $categories->get('kuliner-kafe')?->id,
                'name' => 'Kopi Senja Express Seminyak',
                'slug' => 'kopi-senja-seminyak',
                'description' => 'Gerai grab-and-go Kopi Senja di jantung Seminyak dengan antrean kilat kopi dingin dan pastry.',
                'address' => 'Jl. Kayu Aya No. 108, Seminyak, Badung, Bali',
                'area' => 'Seminyak',
                'phone' => '081288990022',
                'timezone' => 'Asia/Makassar',
                'status' => Business::STATUS_BUKA,
                'approval_status' => Business::APPROVAL_APPROVED,
                'reviewed_at' => now()->subDays(9),
                'reviewed_by' => $admin?->id,
                'is_suspended' => false,
            ],
            counters: [
                ['code' => 'L-01', 'name' => 'Express Counter'],
            ],
            services: [
                ['name' => 'Cold Brew & Signature Latte', 'description' => 'Minuman kopi dingin racikan harian tanpa perlu antre di kasir.', 'duration_minutes' => 10, 'price' => 35000, 'daily_quota' => 70],
                ['name' => 'Pastry & Sandwich Box', 'description' => 'Paket roti panggang dan kue pendamping kopi.', 'duration_minutes' => 10, 'price' => 48000, 'daily_quota' => 45],
            ],
        );

        $kopiUbud = $this->provisionBusiness(
            ownerEmail: 'owner@kopisenja.id',
            ownerName: 'Nadia Kusuma',
            ownerPassword: 'merchant12345',
            operatorEmail: 'operator.ubud@kopisenja.id',
            operatorName: 'Gede Pranata',
            operatorPassword: 'operator12345',
            businessData: [
                'organization_id' => $kopiCanggu->organization_id,
                'category_id' => $categories->get('kuliner-kafe')?->id,
                'name' => 'Kopi Senja Garden Ubud',
                'slug' => 'kopi-senja-ubud',
                'description' => 'Kafe bernuansa taman hijau Ubud dengan sajian single origin Bali Kintamani dan brunch sehat.',
                'address' => 'Jl. Raya Sanggingan No. 27, Ubud, Gianyar, Bali',
                'area' => 'Ubud',
                'phone' => '081288990033',
                'timezone' => 'Asia/Makassar',
                'status' => Business::STATUS_BUKA,
                'approval_status' => Business::APPROVAL_APPROVED,
                'reviewed_at' => now()->subDays(8),
                'reviewed_by' => $admin?->id,
                'is_suspended' => false,
            ],
            counters: [
                ['code' => 'L-01', 'name' => 'Garden Bar'],
                ['code' => 'L-02', 'name' => 'Host Desk'],
            ],
            services: [
                ['name' => 'Meja Garden View', 'description' => 'Antrean tempat duduk area taman terbuka Ubud.', 'duration_minutes' => 20, 'price' => 0, 'daily_quota' => 35],
                ['name' => 'Single Origin Tasting', 'description' => 'Sesi seduh manual biji kopi pilihan Nusantara.', 'duration_minutes' => 15, 'price' => 42000, 'daily_quota' => 40],
            ],
        );

        $extraBusinesses = [
            [
                'owner_email' => 'owner@dapursunda.id',
                'owner_name' => 'Sari Wulandari',
                'operator_email' => 'operator@dapursunda.id',
                'operator_name' => 'Dedi Kurniawan',
                'category' => 'kuliner-kafe',
                'business' => [
                    'name' => 'Dapur Sunda Ibu Sari',
                    'slug' => 'dapur-sunda-ibu-sari',
                    'description' => 'Rumah makan khas Sunda dengan nasi liwet, gurame bakar, dan sambal dadak segar.',
                    'address' => 'Jl. Raya Puputan No. 115, Renon, Denpasar',
                    'area' => 'Denpasar',
                    'phone' => '081355667788',
                    'status' => Business::STATUS_BUKA,
                ],
                'counters' => [
                    ['code' => 'L-01', 'name' => 'Kasir Utama'],
                    ['code' => 'L-02', 'name' => 'Layanan Bungkus'],
                ],
                'services' => [
                    ['name' => 'Antrean Meja Makan Keluarga', 'description' => 'Meja kapasitas 4-6 orang untuk makan di tempat.', 'duration_minutes' => 25, 'price' => 0, 'daily_quota' => 40],
                    ['name' => 'Pesanan Bungkus / Take Away', 'description' => 'Paket nasi timbel & lauk siap bawa tanpa tunggu meja.', 'duration_minutes' => 12, 'price' => 38000, 'daily_quota' => 60],
                ],
                'waiting_count' => 4,
                'reviews' => [
                    ['rating' => 5, 'comment' => 'Makan siang keluarga jadi praktis, ambil antrean sebelum berangkat dari kantor.'],
                    ['rating' => 4, 'comment' => 'Gurame bakarnya mantap, sambal dadaknya segar sekali.'],
                    ['rating' => 5, 'comment' => 'Antrean bungkus dipisah dari meja makan jadi lebih cepat.'],
                    ['rating' => 5, 'comment' => 'Tempat bersih dan pelayanannya sigap walau jam makan siang ramai.'],
                    ['rating' => 4, 'comment' => 'Es cendol dan lalapannya segar.'],
                    ['rating' => 5, 'comment' => null],
                ],
            ],
            [
                'owner_email' => 'owner@pastrykuta.id',
                'owner_name' => 'Clara Wijaya',
                'operator_email' => 'operator@pastrykuta.id',
                'operator_name' => 'Reno Prasetyo',
                'category' => 'kuliner-kafe',
                'business' => [
                    'name' => 'Pastry & Bakehouse Kuta',
                    'slug' => 'pastry-bakehouse-kuta',
                    'description' => 'Artisan bakery dengan sourdough, almond croissant, dan kue ulang tahun custom.',
                    'address' => 'Jl. Sunset Road No. 21, Kuta, Badung, Bali',
                    'area' => 'Kuta',
                    'phone' => '081777888999',
                    'status' => Business::STATUS_BUKA,
                ],
                'counters' => [
                    ['code' => 'L-01', 'name' => 'Pickup Counter'],
                ],
                'services' => [
                    ['name' => 'Ambil Kue & Roti Fresh Oven', 'description' => 'Pengambilan paket roti & pastry harian di konter utama.', 'duration_minutes' => 10, 'price' => 65000, 'daily_quota' => 45],
                    ['name' => 'Konsultasi Custom Cake', 'description' => 'Diskusi desain & rasa kue ulang tahun/pernikahan.', 'duration_minutes' => 20, 'price' => 0, 'daily_quota' => 15],
                ],
                'waiting_count' => 2,
                'reviews' => [
                    ['rating' => 5, 'comment' => 'Almond croissant-nya renyah di luar lembut di dalam!'],
                    ['rating' => 5, 'comment' => 'Pesan custom cake gampang banget konsultasinya lewat antrean ini.'],
                    ['rating' => 4, 'comment' => 'Parkiran luas, proses ambil pesanan sangat tertib.'],
                    ['rating' => 5, 'comment' => 'Sourdough-nya wangi dan tekstur kenyal pas.'],
                ],
            ],
            [
                'owner_email' => 'owner@klinikmedika.id',
                'owner_name' => 'dr. Hendra Gunawan',
                'operator_email' => 'operator@klinikmedika.id',
                'operator_name' => 'Siti Rahmawati',
                'category' => 'klinik-praktik-dokter',
                'business' => [
                    'name' => 'Klinik Medika Utama Sanur',
                    'slug' => 'klinik-medika-utama',
                    'description' => 'Klinik pratama terpadu dengan layanan dokter umum, dokter gigi, dan cek laboratorium dasar.',
                    'address' => 'Jl. Danau Tamblingan No. 112, Sanur, Denpasar Selatan',
                    'area' => 'Sanur',
                    'phone' => '081122334455',
                    'status' => Business::STATUS_BUKA,
                ],
                'counters' => [
                    ['code' => 'L-01', 'name' => 'Poli Umum'],
                    ['code' => 'L-02', 'name' => 'Poli Gigi'],
                ],
                'services' => [
                    ['name' => 'Konsultasi Dokter Umum', 'description' => 'Pemeriksaan kesehatan umum, resep obat, dan surat keterangan sehat.', 'duration_minutes' => 15, 'price' => 75000, 'daily_quota' => 50],
                    ['name' => 'Perawatan & Scaling Gigi', 'description' => 'Pembersihan karang gigi dan pemeriksaan kesehatan mulut.', 'duration_minutes' => 30, 'price' => 200000, 'daily_quota' => 20],
                    ['name' => 'Cek Darah & Tensi Cepat', 'description' => 'Pengecekan gula darah, kolesterol, asam urat, dan tekanan darah.', 'duration_minutes' => 10, 'price' => 50000, 'daily_quota' => 40],
                    ['name' => 'Konsultasi Dokter Anak', 'description' => 'Pemantauan tumbuh kembang, imunisasi, dan pemeriksaan anak.', 'duration_minutes' => 20, 'price' => 110000, 'daily_quota' => 25],
                    ['name' => 'Perawatan Luka & Ganti Perban', 'description' => 'Sterilisasi dan perawatan luka pasca tindakan secara higienis.', 'duration_minutes' => 20, 'price' => 65000, 'daily_quota' => 20],
                    ['name' => 'Vaksinasi Influenza Tahunan', 'description' => 'Suntik vaksin flu dewasa dan anak dengan skrining dokter.', 'duration_minutes' => 15, 'price' => 280000, 'daily_quota' => 20],
                ],
                'waiting_count' => 7,
                'reviews' => [
                    ['rating' => 5, 'comment' => 'Sangat membantu! Pasien tidak perlu berkerumun di ruang tunggu lama-lama.'],
                    ['rating' => 5, 'comment' => 'Dokternya teliti menjelaskan keluhan, antrean dipanggil sesuai slot kedatangan.'],
                    ['rating' => 5, 'comment' => 'Scaling gigi bersih tanpa sakit, estimasi waktu tunggu akurat.'],
                    ['rating' => 4, 'comment' => 'Administrasi pendaftaran cepat dan ramah.'],
                    ['rating' => 5, 'comment' => 'Anak saya tidak rewel menunggu karena datang pas giliran sudah dekat.'],
                    ['rating' => 4, 'comment' => 'Ruang tunggu bersih dan nyaman.'],
                    ['rating' => 5, 'comment' => 'Hasil cek lab cepat keluar dan penjelasannya mudah dipahami.'],
                    ['rating' => 5, 'comment' => 'Jadwal poli gigi tepat waktu, perawatnya sangat komunikatif.'],
                    ['rating' => 4, 'comment' => 'Parkir depan klinik luas dan mudah diakses dari jalan utama Sanur.'],
                    ['rating' => 5, 'comment' => 'Vaksin flu prosesnya cepat, tidak sampai 15 menit sudah selesai.'],
                    ['rating' => 5, 'comment' => null],
                    ['rating' => 4, 'comment' => 'Sangat terbantu dengan antrean online sebelum berangkat ke klinik.'],
                ],
            ],
            [
                'owner_email' => 'owner@klinikjimbaran.id',
                'owner_name' => 'dr. Ayu Pradnyani',
                'operator_email' => 'operator@klinikjimbaran.id',
                'operator_name' => 'Komang Aditya',
                'category' => 'klinik-praktik-dokter',
                'business' => [
                    'name' => 'Klinik Sehat Jimbaran',
                    'slug' => 'klinik-sehat-jimbaran',
                    'description' => 'Layanan kesehatan keluarga, konsultasi dokter anak, dan cek kesehatan berkala di Jimbaran.',
                    'address' => 'Jl. Raya Uluwatu No. 89, Jimbaran, Kuta Selatan',
                    'area' => 'Jimbaran',
                    'phone' => '081233445566',
                    'status' => Business::STATUS_BUKA,
                ],
                'counters' => [
                    ['code' => 'L-01', 'name' => 'Loket Pendaftaran'],
                    ['code' => 'L-02', 'name' => 'Ruang Periksa'],
                ],
                'services' => [
                    ['name' => 'Pemeriksaan Umum & Keluarga', 'description' => 'Konsultasi medis harian untuk dewasa dan anak.', 'duration_minutes' => 15, 'price' => 80000, 'daily_quota' => 40],
                    ['name' => 'Vitamin Booster & Imunisasi', 'description' => 'Layanan suntik vitamin dan konsultasi imunitas.', 'duration_minutes' => 20, 'price' => 150000, 'daily_quota' => 25],
                ],
                'waiting_count' => 3,
                'reviews' => [
                    ['rating' => 5, 'comment' => 'Lokasi dekat kampus dan perumahan Jimbaran, sistem antreannya rapi sekali.'],
                    ['rating' => 5, 'comment' => 'Dokter anak ramah dan tidak terburu-buru menjelaskan.'],
                    ['rating' => 4, 'comment' => 'Sangat membantu dengan pilihan jam slot kedatangan.'],
                ],
            ],
            [
                'owner_email' => 'owner@saloncantika.id',
                'owner_name' => 'Putri Maharani',
                'operator_email' => 'operator@saloncantika.id',
                'operator_name' => 'Lina Marlina',
                'category' => 'salon-perawatan-kecantikan',
                'business' => [
                    'name' => 'Salon Cantika Studio',
                    'slug' => 'salon-cantika-studio',
                    'description' => 'Salon perawatan rambut, creambath aromaterapi, facial wajah, dan nail art modern.',
                    'address' => 'Jl. Bypass Ngurah Rai No. 19, Nusa Dua, Badung, Bali',
                    'area' => 'Nusa Dua',
                    'phone' => '081999112233',
                    'status' => Business::STATUS_BUKA,
                ],
                'counters' => [
                    ['code' => 'L-01', 'name' => 'Stylist Station 1'],
                    ['code' => 'L-02', 'name' => 'Beauty Room'],
                ],
                'services' => [
                    ['name' => 'Haircut & Blow Styling', 'description' => 'Potong rambut wanita sesuai bentuk wajah plus vitamin rambut.', 'duration_minutes' => 35, 'price' => 95000, 'daily_quota' => 20],
                    ['name' => 'Creambath Aromaterapi', 'description' => 'Relaksasi rambut dan pijat bahu selama 45 menit.', 'duration_minutes' => 45, 'price' => 120000, 'daily_quota' => 15],
                    ['name' => 'Facial Glowing & Hydrating', 'description' => 'Pembersihan komedo dan masker nutrisi kulit wajah.', 'duration_minutes' => 50, 'price' => 165000, 'daily_quota' => 12],
                    ['name' => 'Manicure & Gel Polish', 'description' => 'Perawatan kuku tangan dengan pilihan warna gel premium.', 'duration_minutes' => 40, 'price' => 130000, 'daily_quota' => 15],
                    ['name' => 'Hair Smoothing Keratin', 'description' => 'Pelurusan lembut bernutrisi keratin agar rambut berkilau.', 'duration_minutes' => 90, 'price' => 380000, 'daily_quota' => 6],
                    ['name' => 'Eyelash Lift & Tint', 'description' => 'Pelentikan bulu mata natural tanpa sambungan tahan 6 minggu.', 'duration_minutes' => 45, 'price' => 175000, 'daily_quota' => 10],
                ],
                'waiting_count' => 2,
                'reviews' => [
                    ['rating' => 5, 'comment' => 'Creambath-nya bikin rileks banget, terapisnya profesional!'],
                    ['rating' => 5, 'comment' => 'Hasil haircut rapi banget dan sesuai referensi foto.'],
                    ['rating' => 5, 'comment' => 'Nail art awet sampai 3 minggu, tempatnya estetik dan bersih.'],
                    ['rating' => 4, 'comment' => 'Pelayanan memuaskan, enak bisa atur jam datang lewat slot.'],
                    ['rating' => 5, 'comment' => 'Facial glowing bikin kulit terasa lembap dan bersih seketika.'],
                    ['rating' => 5, 'comment' => 'Stylist sangat paham bentuk wajah, hasil blow tahan seharian.'],
                    ['rating' => 4, 'comment' => 'Ruangan wangi aromaterapi dan stafnya ramah sekali.'],
                    ['rating' => 5, 'comment' => 'Lash lift-nya natural banget, tidak perih sama sekali.'],
                    ['rating' => 5, 'comment' => 'Tidak perlu antre lama di salon lagi, sangat praktis!'],
                    ['rating' => 4, 'comment' => 'Harga sesuai kualitas produk perawatan yang dipakai.'],
                    ['rating' => 5, 'comment' => null],
                    ['rating' => 5, 'comment' => 'Langganan perawatan akhir pekan di Nusa Dua.'],
                ],
            ],
            [
                'owner_email' => 'owner@technocare.id',
                'owner_name' => 'Fajar Nugroho',
                'operator_email' => 'operator@technocare.id',
                'operator_name' => 'Rizky Ramadhan',
                'category' => 'jasa-servis-elektronik',
                'business' => [
                    'name' => 'Servis Gadget TechnoCare',
                    'slug' => 'servis-gadget-technocare',
                    'description' => 'Pusat perbaikan smartphone, laptop, ganti baterai bergaransi, dan upgrade SSD.',
                    'address' => 'Jl. Dr. Ir. Soekarno No. 45, Kediri, Tabanan, Bali',
                    'area' => 'Tabanan',
                    'phone' => '081444555666',
                    'status' => Business::STATUS_BUKA,
                ],
                'counters' => [
                    ['code' => 'L-01', 'name' => 'Meja Teknisi 1'],
                    ['code' => 'L-02', 'name' => 'Konter Serah Terima'],
                ],
                'services' => [
                    ['name' => 'Diagnosa Kerusakan & Konsultasi', 'description' => 'Pengecekan awal kondisi hardware/software gadget Anda.', 'duration_minutes' => 15, 'price' => 0, 'daily_quota' => 30],
                    ['name' => 'Ganti Baterai / LCD Express', 'description' => 'Penggantian komponen layar atau baterai ditunggu langsung.', 'duration_minutes' => 40, 'price' => 250000, 'daily_quota' => 15],
                    ['name' => 'Cleaning Laptop & Ganti Pasta', 'description' => 'Pembersihan kipas pendingin dan thermal paste performa tinggi.', 'duration_minutes' => 35, 'price' => 150000, 'daily_quota' => 15],
                ],
                'waiting_count' => 3,
                'reviews' => [
                    ['rating' => 5, 'comment' => 'Ganti baterai iPhone cuma 30 menitan dan transparan pengerjaannya.'],
                    ['rating' => 5, 'comment' => 'Laptop jadi adem lagi setelah cleaning dan ganti pasta.'],
                    ['rating' => 4, 'comment' => 'Teknisi informatif menjelaskan kerusakan sebelum eksekusi.'],
                    ['rating' => 5, 'comment' => 'Datang sesuai slot jam, langsung ditangani tanpa nunggu lama.'],
                ],
            ],
            [
                'owner_email' => 'owner@pangkasnusantara.id',
                'owner_name' => 'Bagus Santoso',
                'operator_email' => 'operator@pangkasnusantara.id',
                'operator_name' => 'Agung Wijaya',
                'category' => 'barbershop-pangkas-rambut',
                'business' => [
                    'name' => 'Pangkas Rambut Nusantara',
                    'slug' => 'pangkas-rambut-nusantara',
                    'description' => 'Pangkas rambut klasik dan modern untuk dewasa dan anak dengan harga ramah kantong.',
                    'address' => 'Jl. Ngurah Rai No. 55, Gianyar Kota, Bali',
                    'area' => 'Gianyar',
                    'phone' => '081666777888',
                    'status' => Business::STATUS_BUKA,
                    'use_stations' => false,
                ],
                'counters' => [
                    ['code' => 'L-01', 'name' => 'Pos Layanan Utama'],
                ],
                'services' => [
                    ['name' => 'Potong Rambut Klasik', 'description' => 'Pangkas rapi model klasik atau fade pendek.', 'duration_minutes' => 25, 'price' => 30000, 'daily_quota' => 35],
                    ['name' => 'Potong Rambut Anak', 'description' => 'Potong rambut khusus anak-anak dengan stylist sabar.', 'duration_minutes' => 20, 'price' => 25000, 'daily_quota' => 25],
                ],
                'waiting_count' => 1,
                'reviews' => [
                    ['rating' => 5, 'comment' => 'Murah, cepat, dan hasilnya rapi sekali!'],
                    ['rating' => 5, 'comment' => 'Langganan potong anak di sini, tidak pernah kecewa.'],
                    ['rating' => 4, 'comment' => 'Tempat bersih dan strategis di Gianyar.'],
                ],
            ],
        ];

        $seededApprovedBusinesses = [
            ['business' => $primaryBusiness, 'waiting' => 3, 'include_noshow' => true, 'reviews' => [
                ['rating' => 5, 'comment' => 'Potongan fade-nya rapi sekali, petugasnya detail dan ramah.'],
                ['rating' => 5, 'comment' => 'Mantap! Ambil nomor antrean dari kos sesuai slot jam, datang langsung dilayani.'],
                ['rating' => 5, 'comment' => 'Tempat bersih, AC dingin, musiknya asik. Recommended grooming di Denpasar.'],
                ['rating' => 4, 'comment' => 'Hasil cukuran jenggot halus dan higienis, pasti balik lagi.'],
                ['rating' => 5, 'comment' => 'Sistem antrean QR memudahkan banget, gak perlu tunggu lama di lokasi.'],
                ['rating' => 5, 'comment' => 'Booking slot sangat membantu saat weekend.'],
                ['rating' => 5, 'comment' => 'Paket grooming komplit bikin segar lagi habis kerja seharian.'],
                ['rating' => 4, 'comment' => 'Creambath mint-nya dingin menyegarkan, pijat bahunya pas.'],
                ['rating' => 5, 'comment' => 'Potong anak di sini sabar banget stylists-nya, anak saya betah.'],
                ['rating' => 5, 'comment' => 'Skin fade rapi presisi, tidak perlu antre lama di lokasi.'],
                ['rating' => 4, 'comment' => 'Down perm samping hasilnya natural banget untuk rambut lurus.'],
                ['rating' => 3, 'comment' => 'Hasil potong bagus, hanya jam pulang kantor agak penuh jadi pilih slot malam.'],
                ['rating' => 5, 'comment' => 'Sudah 6 bulan langganan, kualitas semua barber konsisten bagus.'],
                ['rating' => 4, 'comment' => null],
                ['rating' => 5, 'comment' => 'Black mask facial bersih maksimal, wajah terasa enteng.'],
            ]],
            ['business' => $primarySecondBranch, 'waiting' => 2, 'include_noshow' => false, 'reviews' => [
                ['rating' => 5, 'comment' => 'Cabang Canggu gak kalah rapi sama pusatnya di Denpasar!'],
                ['rating' => 5, 'comment' => 'Express cut benar-benar cepat dan hasilnya presisi.'],
                ['rating' => 4, 'comment' => 'Parkir motor gampang, staf ramah.'],
            ]],
            ['business' => $kopiCanggu, 'waiting' => 6, 'include_noshow' => true, 'reviews' => $this->flagshipKopiReviews()],
            ['business' => $kopiSeminyak, 'waiting' => 3, 'include_noshow' => false, 'reviews' => [
                ['rating' => 5, 'comment' => 'Grab and go super cepat, tinggal scan pas sampai di konter Seminyak.'],
                ['rating' => 5, 'comment' => 'Cold brew dan pastry box cocok buat sarapan sebelum kerja.'],
                ['rating' => 4, 'comment' => 'Posisi strategis di Kayu Aya, antrean rapi.'],
                ['rating' => 5, 'comment' => null],
            ]],
            ['business' => $kopiUbud, 'waiting' => 2, 'include_noshow' => false, 'reviews' => [
                ['rating' => 5, 'comment' => 'Nuansa taman Ubud-nya adem banget untuk kerja santai.'],
                ['rating' => 5, 'comment' => 'Single origin Kintamani seduhan V60-nya juara!'],
                ['rating' => 4, 'comment' => 'Pelayanan cepat dan tidak perlu menunggu berdiri di depan kasir.'],
            ]],
        ];

        foreach ($extraBusinesses as $entry) {
            $biz = $this->provisionBusiness(
                ownerEmail: $entry['owner_email'],
                ownerName: $entry['owner_name'],
                ownerPassword: 'merchant12345',
                operatorEmail: $entry['operator_email'],
                operatorName: $entry['operator_name'],
                operatorPassword: 'operator12345',
                businessData: array_merge($entry['business'], [
                    'category_id' => $categories->get($entry['category'])?->id,
                    'timezone' => 'Asia/Makassar',
                    'approval_status' => Business::APPROVAL_APPROVED,
                    'reviewed_at' => now()->subDays(10),
                    'reviewed_by' => $admin?->id,
                    'is_suspended' => false,
                ]),
                counters: $entry['counters'],
                services: $entry['services'],
            );

            $seededApprovedBusinesses[] = [
                'business' => $biz,
                'waiting' => $entry['waiting_count'],
                'include_noshow' => false,
                'reviews' => $entry['reviews'],
            ];
        }

        // 4. Seed 1 Pending UMKM & 1 Rejected UMKM for Admin Verification Workflow
        $this->provisionBusiness(
            ownerEmail: 'dewi@warungtehrimbun.id',
            ownerName: 'Dewi Lestari',
            ownerPassword: 'merchant12345',
            operatorEmail: null,
            operatorName: null,
            operatorPassword: null,
            businessData: [
                'category_id' => $categories->get('kuliner-kafe')?->id,
                'name' => 'Warung Teh Rimbun',
                'slug' => 'warung-teh-rimbun',
                'description' => 'Kedai teh artisan nusantara dengan jajanan pasar hangat setiap sore.',
                'address' => 'Jl. Monkey Forest No. 18, Ubud, Gianyar, Bali',
                'area' => 'Ubud',
                'phone' => '081900112233',
                'timezone' => 'Asia/Makassar',
                'status' => Business::STATUS_BUKA,
                'approval_status' => Business::APPROVAL_PENDING,
                'is_suspended' => false,
            ],
            counters: [['code' => 'L-01', 'name' => 'Kasir Teh']],
            services: [
                ['name' => 'Racikan Teh Artisan Melati', 'description' => 'Seduhan daun teh pilihan hangat atau dingin dengan aroma melati alami.', 'image_path' => 'images/services/teh-artisan.jpg', 'duration_minutes' => 10, 'price' => 28000, 'daily_quota' => 40],
                ['name' => 'Paket Teh & Klepon Hangat', 'description' => 'Kombinasi teh tubruk rempah dengan klepon gula aren segar.', 'image_path' => 'images/stores/pastry.jpg', 'duration_minutes' => 12, 'price' => 42000, 'daily_quota' => 30],
                ['name' => 'Es Teh Leci Mint Segar', 'description' => 'Minuman teh buah segar disajikan dingin dengan daun mint petik.', 'image_path' => 'images/stores/kuliner.jpg', 'duration_minutes' => 8, 'price' => 26000, 'daily_quota' => 50],
                ['name' => 'Meja Gazebo Sore (2-4 Orang)', 'description' => 'Antrean tempat duduk area taman rimbun untuk bersantai sore.', 'image_path' => 'images/stores/restoran.jpg', 'duration_minutes' => 15, 'price' => 0, 'daily_quota' => 25],
            ],
        );

        $this->provisionBusiness(
            ownerEmail: 'andi@studiokilat.id',
            ownerName: 'Andi Wijaya',
            ownerPassword: 'merchant12345',
            operatorEmail: null,
            operatorName: null,
            operatorPassword: null,
            businessData: [
                'category_id' => $categories->get('jasa-servis-elektronik')?->id,
                'name' => 'Studio Foto & Cetak Kilat',
                'slug' => 'studio-foto-kilat',
                'description' => 'Layanan pas foto kilat dan cetak dokumen warna.',
                'address' => 'Jl. Sudirman No. 10, Denpasar',
                'area' => 'Denpasar',
                'phone' => '081200001111',
                'timezone' => 'Asia/Makassar',
                'status' => Business::STATUS_BUKA,
                'approval_status' => Business::APPROVAL_REJECTED,
                'rejection_reason' => 'Mohon lengkapi patokan alamat ruko dan nomor WhatsApp bisnis yang dapat dihubungi tim verifikasi.',
                'reviewed_at' => now()->subDay(),
                'reviewed_by' => $admin?->id,
                'is_suspended' => false,
            ],
            counters: [['code' => 'L-01', 'name' => 'Loket Cetak']],
            services: [
                ['name' => 'Pas Foto Ekspres', 'description' => 'Foto studio kilat ukuran 3x4 dan 4x6 siap cetak dalam 15 menit.', 'image_path' => 'images/services/studio-foto.jpg', 'duration_minutes' => 15, 'price' => 35000, 'daily_quota' => 30],
                ['name' => 'Cetak Foto & Laminating', 'description' => 'Cetak foto ukuran 4R sampai A4 kertas glossy tebal plus laminasi.', 'image_path' => 'images/services/studio-foto.jpg', 'duration_minutes' => 15, 'price' => 45000, 'daily_quota' => 25],
            ],
        );

        // 5. Seed Active Queues, Booking Slots & Reviews for all Approved Businesses
        foreach ($seededApprovedBusinesses as $idx => $item) {
            $this->seedQueuesAndReviews(
                business: $item['business'],
                customers: $customers,
                waitingCount: $item['waiting'],
                reviewEntries: $item['reviews'],
                givePrimaryCustomerTicket: in_array($idx, [0, 2], true), // Demo customer gets 2 active tickets (below limit 3)
                includeNoShow: $item['include_noshow'] ?? false,
            );
        }
    }

    /**
     * 24 rich reviews spanning 5★..2★ (commented + rating-only) to showcase Shopee-style rating breakdowns & pagination.
     *
     * @return list<array{rating: int, comment: string|null}>
     */
    protected function flagshipKopiReviews(): array
    {
        return [
            ['rating' => 5, 'comment' => 'Antrean cepat banget terpantau dari HP, kopinya konsisten enak!'],
            ['rating' => 5, 'comment' => 'V60 Kintamani juara, tidak perlu berdiri menunggu di depan kasir.'],
            ['rating' => 4, 'comment' => 'Suasana nyaman buat kerja, jam sore agak ramai tapi estimasi akurat.'],
            ['rating' => 5, 'comment' => 'Croissant dan es kopi susu arennya wajib coba.'],
            ['rating' => 5, 'comment' => 'Pelayanan barista ramah dan nomor dipanggil tepat waktu sesuai slot.'],
            ['rating' => 5, 'comment' => 'Booking slot pagi hari sangat membantu sebelum meeting online.'],
            ['rating' => 5, 'comment' => 'Wi-Fi kencang, colokan banyak, pesanan diantar rapi sesuai giliran.'],
            ['rating' => 4, 'comment' => 'Matcha latte-nya creamy, hanya parkir mobil agak penuh di jam makan siang.'],
            ['rating' => 5, 'comment' => 'QR tiket praktis banget tinggal scan pas tiba di kafe.'],
            ['rating' => 5, 'comment' => 'Barista telaten merekomendasikan beans sesuai selera.'],
            ['rating' => 4, 'comment' => 'Cold brew botolannya segar banget buat dibawa pulang.'],
            ['rating' => 3, 'comment' => 'Rasa kopi mantap, cuma pas hujan area outdoor tidak bisa dipakai jadi sedikit tunggu meja.'],
            ['rating' => 5, 'comment' => 'Favorit kerja remote di Canggu! Sistem slot bikin gak pernah antre sia-sia.'],
            ['rating' => 5, 'comment' => 'Paket sarapan take away siap tepat waktu dalam 8 menit.'],
            ['rating' => 4, 'comment' => 'Harga sebanding dengan kualitas biji kopi spesialti yang dipakai.'],
            ['rating' => 5, 'comment' => 'Interior bersih, musik tidak terlalu keras, cocok buat fokus kerja.'],
            ['rating' => 2, 'comment' => 'Menu pastry favorit sempat habis waktu datang sore, untung minumannya tetap cepat jadi.'],
            ['rating' => 5, 'comment' => 'Sudah langganan 3 bulan, konsistensi rasa espresso selalu terjaga.'],
            ['rating' => 5, 'comment' => null],
            ['rating' => 5, 'comment' => null],
            ['rating' => 4, 'comment' => null],
            ['rating' => 5, 'comment' => 'Sangat terbantu dengan informasi slot jam kedatangan yang jelas.'],
            ['rating' => 4, 'comment' => 'Pelayanan cepat, kasir informatif, suasana Berawa yang santai.'],
            ['rating' => 5, 'comment' => 'Biji kopi roastery sendiri harum banget begitu masuk pintu.'],
        ];
    }

    /**
     * @return list<User>
     */
    protected function seedCustomers(): array
    {
        $customerProfiles = [
            ['email' => 'pelanggan@antrein.id', 'name' => 'Raka Pratama', 'password' => 'pelanggan12345'],
            ['email' => 'sinta.dewi@contoh.id', 'name' => 'Sinta Dewi', 'password' => 'pelanggan12345'],
            ['email' => 'bayu.aji@contoh.id', 'name' => 'Bayu Aji Nugraha', 'password' => 'pelanggan12345'],
            ['email' => 'putri.amalia@contoh.id', 'name' => 'Putri Amalia', 'password' => 'pelanggan12345'],
            ['email' => 'dimas.pratama@contoh.id', 'name' => 'Dimas Pratama', 'password' => 'pelanggan12345'],
            ['email' => 'maya.kartika@contoh.id', 'name' => 'Maya Kartika', 'password' => 'pelanggan12345'],
            ['email' => 'reza.fauzan@contoh.id', 'name' => 'Reza Fauzan', 'password' => 'pelanggan12345'],
            ['email' => 'luh.putu@contoh.id', 'name' => 'Luh Putu Saraswati', 'password' => 'pelanggan12345'],
            ['email' => 'made.bagus@contoh.id', 'name' => 'Made Bagus Wirawan', 'password' => 'pelanggan12345'],
            ['email' => 'nadine.aurora@contoh.id', 'name' => 'Nadine Aurora', 'password' => 'pelanggan12345'],
            ['email' => 'hendra.wijaya@contoh.id', 'name' => 'Hendra Wijaya', 'password' => 'pelanggan12345'],
            ['email' => 'citra.lestari@contoh.id', 'name' => 'Citra Lestari', 'password' => 'pelanggan12345'],
        ];

        $users = [];

        foreach ($customerProfiles as $profile) {
            $users[] = User::firstOrCreate(
                ['email' => $profile['email']],
                [
                    'name' => $profile['name'],
                    'password' => Hash::make($profile['password']),
                    'role' => User::ROLE_CUSTOMER,
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );
        }

        return $users;
    }

    /**
     * @param  list<array{code: string, name: string}>  $counters
     * @param  list<array{name: string, description: string, duration_minutes: int, price: int|float, daily_quota: int}>  $services
     */
    protected function provisionBusiness(
        string $ownerEmail,
        string $ownerName,
        string $ownerPassword,
        ?string $operatorEmail,
        ?string $operatorName,
        ?string $operatorPassword,
        array $businessData,
        array $counters,
        array $services,
    ): Business {
        $owner = User::firstOrCreate(
            ['email' => $ownerEmail],
            [
                'name' => $ownerName,
                'password' => Hash::make($ownerPassword),
                'role' => User::ROLE_MERCHANT,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        $business = Business::firstOrCreate(
            ['slug' => $businessData['slug']],
            array_merge($businessData, ['user_id' => $owner->id])
        );

        // Keep approval, area & status updated cleanly on re-seed
        $updatePayload = [
            'approval_status' => $businessData['approval_status'] ?? Business::APPROVAL_APPROVED,
            'rejection_reason' => $businessData['rejection_reason'] ?? null,
            'reviewed_at' => $businessData['reviewed_at'] ?? null,
            'reviewed_by' => $businessData['reviewed_by'] ?? null,
            'is_suspended' => $businessData['is_suspended'] ?? false,
            'use_stations' => $businessData['use_stations'] ?? true,
        ];
        if (! empty($businessData['area'])) {
            $updatePayload['area'] = $businessData['area'];
        }
        if (! empty($businessData['organization_id']) && $business->organization_id !== $businessData['organization_id']) {
            $updatePayload['organization_id'] = $businessData['organization_id'];
        }
        $business->update($updatePayload);

        // Guarantee BusinessOrganization & OrganizationMembership exist even if model events were bypassed
        $organization = null;
        if (! empty($businessData['organization_id'])) {
            $organization = BusinessOrganization::find($businessData['organization_id']);
        } elseif ($business->organization_id) {
            $organization = BusinessOrganization::find($business->organization_id);
        } else {
            $baseOrgSlug = Str::slug($business->name) ?: 'organisasi-'.$business->id;
            $orgSlug = $baseOrgSlug;
            $suffix = 1;
            while (BusinessOrganization::where('slug', $orgSlug)->exists()) {
                $orgSlug = $baseOrgSlug.'-'.$suffix++;
            }

            $organization = BusinessOrganization::create([
                'owner_user_id' => $owner->id,
                'name' => $business->name,
                'slug' => $orgSlug,
                'description' => $business->description,
                'timezone' => $business->timezone ?: 'Asia/Makassar',
                'is_active' => true,
            ]);
        }

        if ($organization) {
            if ($business->organization_id !== $organization->id) {
                $business->forceFill(['organization_id' => $organization->id])->saveQuietly();
            }

            OrganizationMembership::updateOrCreate(
                ['organization_id' => $organization->id, 'user_id' => $owner->id],
                [
                    'role' => OrganizationMembership::ROLE_OWNER,
                    'is_active' => true,
                    'joined_at' => $business->created_at ?? now(),
                ]
            );
        }

        BusinessMembership::updateOrCreate(
            ['business_id' => $business->id, 'user_id' => $owner->id],
            [
                'organization_id' => $organization?->id ?? $business->organization_id,
                'role' => BusinessMembership::ROLE_OWNER,
                'is_active' => true,
                'joined_at' => now(),
            ]
        );

        for ($day = 0; $day <= 6; $day++) {
            BusinessHour::firstOrCreate(
                ['business_id' => $business->id, 'day_of_week' => $day],
                [
                    'open_time' => '08:00:00',
                    'close_time' => '21:30:00',
                    'is_closed' => false,
                ]
            );
        }

        foreach ($services as $svc) {
            Service::updateOrCreate(
                ['business_id' => $business->id, 'name' => $svc['name']],
                array_merge($svc, ['is_active' => true])
            );
        }

        $createdCounters = [];
        foreach ($counters as $c) {
            $counter = BusinessCounter::firstOrCreate(
                ['business_id' => $business->id, 'code' => $c['code']],
                ['name' => $c['name'], 'is_active' => true]
            );

            // Replace default/legacy counter names cleanly with domain-neutral labels
            if ($counter->name !== $c['name']) {
                $counter->update(['name' => $c['name']]);
            }

            $createdCounters[] = $counter;
        }

        if ($operatorEmail && $operatorName && $operatorPassword) {
            $operator = User::firstOrCreate(
                ['email' => $operatorEmail],
                [
                    'name' => $operatorName,
                    'password' => Hash::make($operatorPassword),
                    'role' => User::ROLE_MERCHANT,
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );

            $membership = BusinessMembership::updateOrCreate(
                ['business_id' => $business->id, 'user_id' => $operator->id],
                [
                    'organization_id' => $business->organization_id,
                    'role' => BusinessMembership::ROLE_OPERATOR,
                    'staff_password_encrypted' => Crypt::encryptString($operatorPassword),
                    'is_active' => true,
                    'joined_at' => now(),
                ]
            );

            foreach ($createdCounters as $counter) {
                $counter->assignedMemberships()->syncWithoutDetaching([$membership->id]);
            }
        }

        return $business;
    }

    /**
     * @param  list<User>  $customers
     * @param  list<array{rating: int, comment: string|null}>  $reviewEntries
     */
    protected function seedQueuesAndReviews(
        Business $business,
        array $customers,
        int $waitingCount,
        array $reviewEntries,
        bool $givePrimaryCustomerTicket = false,
        bool $includeNoShow = false,
    ): void {
        $timezone = $business->timezone ?: 'Asia/Makassar';
        $appTimezone = (string) config('app.timezone', 'Asia/Makassar');
        $nowLocal = Carbon::now($timezone);
        $today = $nowLocal->toDateString();

        $dailyQueue = DailyQueue::where('business_id', $business->id)
            ->whereDate('queue_date', $today)
            ->first();

        if (! $dailyQueue) {
            $dailyQueue = DailyQueue::create([
                'business_id' => $business->id,
                'queue_date' => $today,
                'current_number' => 0,
                'status' => DailyQueue::STATUS_ACTIVE,
            ]);
        }

        // Avoid duplicating tickets if seeder runs twice on existing DB
        if ($dailyQueue->queueTickets()->exists()) {
            return;
        }

        $services = $business->services()->active()->get();
        $firstCounter = $business->counters()->active()->first();

        if ($services->isEmpty()) {
            return;
        }

        $sequence = 0;
        $currentHourLocal = $nowLocal->copy()->startOfHour();
        $earlierHourLocal = $currentHourLocal->copy()->subHour();
        $nextHourLocal = $currentHourLocal->copy()->addHour();

        // Clamp seeded slot anchors inside business day (08:00 - 20:00) so labels stay realistic even late at night
        $minStart = Carbon::parse($today.' 08:00:00', $timezone);
        if ($earlierHourLocal->lt($minStart)) {
            $earlierHourLocal = $minStart->copy();
        }
        if ($nextHourLocal->hour >= 21) {
            $nextHourLocal = Carbon::parse($today.' 20:00:00', $timezone);
        }

        // Seed completed tickets + reviews across past few days / earlier today
        foreach ($reviewEntries as $index => $reviewData) {
            $sequence++;
            $service = $services[$index % $services->count()];
            $customer = $customers[($index + 1) % count($customers)];
            $createdAt = now()->subMinutes(240 - min(200, $index * 8));
            $slotStart = $earlierHourLocal->copy()->setTimezone($appTimezone);
            $slotEnd = $earlierHourLocal->copy()->addHour()->setTimezone($appTimezone);

            $rawToken = Str::random(40);
            $ticket = QueueTicket::create([
                'business_id' => $business->id,
                'daily_queue_id' => $dailyQueue->id,
                'service_id' => $service->id,
                'counter_id' => $firstCounter?->id,
                'user_id' => $customer->id,
                'ticket_number' => sprintf('TKT-%03d', $sequence),
                'sequence_number' => $sequence,
                'customer_name' => $customer->name,
                'customer_phone' => '08123'.str_pad((string) ($index + 100), 6, '0', STR_PAD_LEFT),
                'source' => QueueTicket::SOURCE_ONLINE,
                'status' => QueueTicket::STATUS_SELESAI,
                'booking_slot_start' => $slotStart,
                'booking_slot_end' => $slotEnd,
                'access_token' => $rawToken,
                'called_at' => $createdAt->copy()->addMinutes(5),
                'service_started_at' => $createdAt->copy()->addMinutes(7),
                'completed_at' => $createdAt->copy()->addMinutes(22),
                'created_at' => $createdAt,
            ]);

            CustomerReview::create([
                'business_id' => $business->id,
                'queue_ticket_id' => $ticket->id,
                'service_id' => $service->id,
                'rating' => $reviewData['rating'],
                'comment' => $reviewData['comment'],
            ]);
        }

        // Seed 1 auto no-show (tidak_hadir) ticket on flagship businesses
        if ($includeNoShow) {
            $sequence++;
            $noShowCustomer = $customers[4 % count($customers)];
            $rawToken = Str::random(40);
            QueueTicket::create([
                'business_id' => $business->id,
                'daily_queue_id' => $dailyQueue->id,
                'service_id' => $services->first()->id,
                'counter_id' => $firstCounter?->id,
                'user_id' => $noShowCustomer->id,
                'ticket_number' => sprintf('TKT-%03d', $sequence),
                'sequence_number' => $sequence,
                'customer_name' => $noShowCustomer->name,
                'customer_phone' => '081255667700',
                'source' => QueueTicket::SOURCE_ONLINE,
                'status' => QueueTicket::STATUS_TIDAK_HADIR,
                'booking_slot_start' => $earlierHourLocal->copy()->setTimezone($appTimezone),
                'booking_slot_end' => $earlierHourLocal->copy()->addHour()->setTimezone($appTimezone),
                'access_token' => $rawToken,
                'called_at' => now()->subMinutes(20),
                'notes' => 'Otomatis ditandai tidak hadir setelah melewati batas toleransi 15 menit dari slot kedatangan.',
                'created_at' => now()->subMinutes(55),
            ]);
        }

        // Seed 1 currently serving ticket if store has waiting guests
        if ($waitingCount > 0) {
            $sequence++;
            $servingCustomer = $customers[2 % count($customers)];
            $rawToken = Str::random(40);
            QueueTicket::create([
                'business_id' => $business->id,
                'daily_queue_id' => $dailyQueue->id,
                'service_id' => $services->first()->id,
                'counter_id' => $firstCounter?->id,
                'user_id' => $servingCustomer->id,
                'ticket_number' => sprintf('TKT-%03d', $sequence),
                'sequence_number' => $sequence,
                'customer_name' => $servingCustomer->name,
                'customer_phone' => '081298765432',
                'source' => QueueTicket::SOURCE_ONLINE,
                'status' => QueueTicket::STATUS_SEDANG_DILAYANI,
                'booking_slot_start' => $currentHourLocal->copy()->setTimezone($appTimezone),
                'booking_slot_end' => $currentHourLocal->copy()->addHour()->setTimezone($appTimezone),
                'access_token' => $rawToken,
                'called_at' => now()->subMinutes(6),
                'service_started_at' => now()->subMinutes(4),
                'created_at' => now()->subMinutes(15),
            ]);
        }

        // Seed active waiting tickets across earlier-slot leftovers & upcoming booking slots
        for ($i = 0; $i < $waitingCount; $i++) {
            $sequence++;
            $service = $services[$i % $services->count()];
            $useDemoCustomer = $givePrimaryCustomerTicket && $i === 0;
            $customer = $useDemoCustomer ? $customers[0] : $customers[(($i + 2) % (count($customers) - 1)) + 1];

            // First waiting guest simulates an earlier-slot leftover that takes priority in FIFO calling
            $slotAnchorLocal = $i === 0
                ? $currentHourLocal->copy()
                : $nextHourLocal->copy()->addHours((int) floor(($i - 1) / 2));

            $rawToken = Str::random(40);
            QueueTicket::create([
                'business_id' => $business->id,
                'daily_queue_id' => $dailyQueue->id,
                'service_id' => $service->id,
                'counter_id' => null,
                'user_id' => $customer->id,
                'ticket_number' => sprintf('TKT-%03d', $sequence),
                'sequence_number' => $sequence,
                'customer_name' => $customer->name,
                'customer_phone' => '0813'.str_pad((string) ($business->id * 100 + $i), 8, '0', STR_PAD_LEFT),
                'source' => $i % 3 === 2 ? QueueTicket::SOURCE_WALK_IN : QueueTicket::SOURCE_ONLINE,
                'status' => QueueTicket::STATUS_MENUNGGU,
                'booking_slot_start' => $slotAnchorLocal->copy()->setTimezone($appTimezone),
                'booking_slot_end' => $slotAnchorLocal->copy()->addHour()->setTimezone($appTimezone),
                'access_token' => $rawToken,
                'created_at' => now()->subMinutes(($waitingCount - $i) * 4),
            ]);
        }

        $dailyQueue->update(['current_number' => $sequence]);

        ActivityLog::log(
            action: 'seed_initialized',
            description: sprintf('Antrean hari ini untuk %s dibuka (%d tiket)', $business->name, $sequence),
            business: $business,
        );
    }
}
