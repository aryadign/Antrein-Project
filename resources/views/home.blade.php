<x-layouts.base title="AntreIn — Antrean Lebih Teratur, Pelayanan Lebih Lancar.">
    {{-- Top Notification Bar (Local & Testing Developer Environment Helper) --}}
    @if (app()->environment(['local', 'testing']))
        <div class="bg-neutral-900 text-neutral-200 text-xs py-2 px-4 border-b border-neutral-800">
            <div class="max-w-7xl mx-auto flex flex-wrap items-center justify-between gap-2">
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-400/20 text-amber-300 border border-amber-400/30">
                        DEV MODE
                    </span>
                    <span>Tersedia panel akses cepat di halaman login &amp; floating role switcher di pojok kanan bawah.</span>
                </div>
                <div class="flex items-center gap-4 text-neutral-400">
                    <a href="{{ route('login') }}" class="hover:text-white underline">Akses Cepat Login</a>
                    <span class="text-neutral-700">|</span>
                    <a href="{{ route('customer.queue', 'barbershop-garasi-demo') }}" class="hover:text-white underline">Demo Tiket Pelanggan</a>
                    <span class="text-neutral-700">|</span>
                    <a href="{{ route('preview.design-system') }}" class="hover:text-white underline">Design System Preview</a>
                </div>
            </div>
        </div>
    @endif

    {{-- Main Navbar --}}
    <header x-data="{ mobileMenuOpen: false }" class="sticky top-0 z-40 bg-white/95 backdrop-blur-md border-b border-neutral-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16 sm:h-20">
                {{-- Brand Logo --}}
                <div class="flex items-center gap-8">
                    <a href="{{ route('home') }}" class="flex items-center gap-2 group focus:outline-none">
                        <div class="w-10 h-10 rounded-xl bg-brand-700 flex items-center justify-center text-white font-black text-xl shadow-md shadow-brand-700/20 group-hover:bg-brand-800 transition">
                            A
                        </div>
                        <span class="text-2xl font-black tracking-tight text-neutral-950">
                            AntreIn<span class="text-brand-600">.</span>
                        </span>
                    </a>

                    {{-- Desktop Nav Links --}}
                    <nav class="hidden md:flex items-center gap-6">
                        <a href="#" class="text-sm font-medium text-neutral-600 hover:text-brand-700 transition">
                            Beranda
                        </a>
                        <a href="#cara-kerja" class="text-sm font-medium text-neutral-600 hover:text-brand-700 transition">
                            Cara Kerja
                        </a>
                        <a href="#fitur" class="text-sm font-medium text-neutral-600 hover:text-brand-700 transition">
                            Fitur
                        </a>
                        <a href="#untuk-umkm" class="text-sm font-medium text-neutral-600 hover:text-brand-700 transition">
                            Untuk UMKM
                        </a>
                        <a href="#faq" class="text-sm font-medium text-neutral-600 hover:text-brand-700 transition">
                            FAQ
                        </a>
                    </nav>
                </div>

                {{-- Desktop Action Buttons --}}
                <div class="hidden sm:flex items-center gap-3">
                    @auth
                        @if (auth()->user()->isAdmin())
                            <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center justify-center px-4 py-2.5 rounded-lg text-sm font-semibold text-white bg-brand-700 hover:bg-brand-800 shadow-sm transition">
                                Dashboard Admin &rarr;
                            </a>
                        @else
                            <a href="{{ route('merchant.dashboard') }}" class="inline-flex items-center justify-center px-4 py-2.5 rounded-lg text-sm font-semibold text-white bg-brand-700 hover:bg-brand-800 shadow-sm transition">
                                Dashboard UMKM &rarr;
                            </a>
                        @endif
                    @else
                        <a href="{{ route('login') }}" class="inline-flex items-center justify-center px-4 py-2.5 rounded-lg text-sm font-semibold text-neutral-700 hover:text-neutral-950 hover:bg-neutral-100 transition">
                            Masuk
                        </a>
                        <a href="{{ route('register') }}" class="inline-flex items-center justify-center px-4 py-2.5 rounded-lg text-sm font-semibold text-white bg-brand-700 hover:bg-brand-800 shadow-sm shadow-brand-700/20 transition">
                            Daftarkan UMKM
                        </a>
                    @endauth
                </div>

                {{-- Mobile Menu Button --}}
                <div class="flex sm:hidden">
                    <button 
                        type="button" 
                        @click="mobileMenuOpen = !mobileMenuOpen"
                        class="p-2 rounded-lg text-neutral-600 hover:text-neutral-900 hover:bg-neutral-100 focus:outline-none"
                        aria-label="Menu"
                    >
                        <svg x-show="!mobileMenuOpen" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                        <svg x-show="mobileMenuOpen" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="display:none;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        {{-- Mobile Drawer --}}
        <div x-show="mobileMenuOpen" x-collapse class="sm:hidden border-b border-neutral-200 bg-white px-4 pt-2 pb-6 space-y-3">
            <a href="#" @click="mobileMenuOpen = false" class="block py-2 text-base font-medium text-neutral-700 hover:text-brand-700">Beranda</a>
            <a href="#cara-kerja" @click="mobileMenuOpen = false" class="block py-2 text-base font-medium text-neutral-700 hover:text-brand-700">Cara Kerja</a>
            <a href="#fitur" @click="mobileMenuOpen = false" class="block py-2 text-base font-medium text-neutral-700 hover:text-brand-700">Fitur</a>
            <a href="#untuk-umkm" @click="mobileMenuOpen = false" class="block py-2 text-base font-medium text-neutral-700 hover:text-brand-700">Untuk UMKM</a>
            <a href="#faq" @click="mobileMenuOpen = false" class="block py-2 text-base font-medium text-neutral-700 hover:text-brand-700">FAQ</a>
            <div class="pt-4 border-t border-neutral-100 flex flex-col gap-2">
                @auth
                    @if (auth()->user()->isAdmin())
                        <a href="{{ route('admin.dashboard') }}" class="w-full text-center py-2.5 px-4 rounded-lg text-sm font-semibold text-white bg-brand-700">Dashboard Admin</a>
                    @else
                        <a href="{{ route('merchant.dashboard') }}" class="w-full text-center py-2.5 px-4 rounded-lg text-sm font-semibold text-white bg-brand-700">Dashboard UMKM</a>
                    @endif
                @else
                    <a href="{{ route('login') }}" class="w-full text-center py-2.5 px-4 rounded-lg text-sm font-semibold text-neutral-800 bg-neutral-100">Masuk</a>
                    <a href="{{ route('register') }}" class="w-full text-center py-2.5 px-4 rounded-lg text-sm font-semibold text-white bg-brand-700">Daftarkan UMKM</a>
                @endauth
            </div>
        </div>
    </header>

    <main class="flex-1">
        {{-- Hero Section --}}
        <section class="relative overflow-hidden pt-12 pb-20 lg:pt-20 lg:pb-28 bg-gradient-to-b from-brand-50/50 via-white to-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-8 items-center">
                    {{-- Left Hero Text --}}
                    <div class="lg:col-span-7 text-center lg:text-left space-y-6">
                        <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-brand-100/70 text-brand-800 border border-brand-200 text-xs sm:text-sm font-semibold">
                            <span class="flex h-2 w-2 rounded-full bg-brand-600 animate-pulse"></span>
                            Platform Manajemen Antrean Digital Terpadu
                        </div>

                        <h1 class="text-4xl sm:text-5xl lg:text-6xl font-black text-neutral-950 tracking-tight leading-[1.12]">
                            Antrean Lebih Teratur, <span class="text-brand-700">Pelayanan Lebih Lancar.</span>
                        </h1>

                        <p class="text-base sm:text-lg text-neutral-600 max-w-2xl mx-auto lg:mx-0 leading-relaxed">
                            Kelola antrean online dan walk-in dalam satu platform digital yang membantu UMKM melayani pelanggan dengan lebih mudah.
                        </p>

                        {{-- Action Buttons --}}
                        <div class="flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-3 sm:gap-4 pt-2">
                            <a href="{{ route('register') }}" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-xl text-base font-bold text-white bg-brand-700 hover:bg-brand-800 shadow-lg shadow-brand-700/25 transition">
                                <span>Mulai Kelola UMKM</span>
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                            </a>
                            <a href="#cara-kerja" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-xl text-base font-semibold text-neutral-700 bg-white hover:bg-neutral-50 border border-neutral-300 shadow-sm transition">
                                <span>Pelajari Cara Kerja</span>
                            </a>
                        </div>

                        {{-- Value Highlights --}}
                        <div class="pt-6 grid grid-cols-3 gap-4 border-t border-neutral-200/80 text-left">
                            <div>
                                <div class="text-2xl font-black text-neutral-950">1 Sistem</div>
                                <div class="text-xs text-neutral-500 font-medium mt-0.5">Online &amp; Walk-in Terpadu</div>
                            </div>
                            <div>
                                <div class="text-2xl font-black text-neutral-950">Real-Time</div>
                                <div class="text-xs text-neutral-500 font-medium mt-0.5">Pembaruan Tanpa Delay</div>
                            </div>
                            <div>
                                <div class="text-2xl font-black text-neutral-950">Multi-Loket</div>
                                <div class="text-xs text-neutral-500 font-medium mt-0.5">Dukungan Banyak Staf</div>
                            </div>
                        </div>
                    </div>

                    {{-- Right Hero Mockup (Customer Queue Card Illustrative Mockup) --}}
                    <div class="lg:col-span-5 flex justify-center">
                        <div class="relative w-full max-w-sm sm:max-w-md">
                            {{-- Decorative Background Glow --}}
                            <div class="absolute -inset-4 bg-gradient-to-r from-brand-500/20 to-blue-400/20 rounded-3xl blur-2xl -z-10"></div>

                            {{-- Mockup Card --}}
                            <div class="bg-white rounded-2xl border border-neutral-200/90 shadow-2xl p-6 space-y-6">
                                {{-- Card Header --}}
                                <div class="flex items-center justify-between border-b border-neutral-100 pb-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-xl bg-brand-700 text-white flex items-center justify-center font-bold text-sm shadow-sm">
                                            UM
                                        </div>
                                        <div>
                                            <h2 class="text-sm font-bold text-neutral-900">Unit Usaha Mitra</h2>
                                            <div class="flex items-center gap-1.5 text-xs text-emerald-600 font-medium">
                                                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-ping"></span>
                                                <span>Buka &bull; Antrean Berjalan</span>
                                            </div>
                                        </div>
                                    </div>
                                    <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-brand-50 text-brand-700 border border-brand-200">
                                        Tiket Digital
                                    </span>
                                </div>

                                {{-- Ticket Big Number Display --}}
                                <div class="text-center py-4 bg-neutral-50 rounded-xl border border-neutral-200/60 space-y-1">
                                    <div class="text-xs font-semibold tracking-wider uppercase text-neutral-500">Nomor Antrean Anda</div>
                                    <div class="text-5xl font-black tracking-tight text-brand-700 tabular-nums">A-001</div>
                                    <div class="text-xs font-medium text-neutral-600">Layanan: Konsultasi &amp; Pelayanan Utama</div>
                                </div>

                                {{-- Live Status Details --}}
                                <div class="grid grid-cols-2 gap-3 text-center">
                                    <div class="p-3 bg-amber-50/70 border border-amber-200 rounded-lg">
                                        <div class="text-[11px] font-semibold text-amber-800 uppercase tracking-wide">Status Antrean</div>
                                        <div class="text-sm font-bold text-amber-950 mt-0.5">Menunggu Giliran</div>
                                    </div>
                                    <div class="p-3 bg-blue-50/70 border border-blue-200 rounded-lg">
                                        <div class="text-[11px] font-semibold text-blue-800 uppercase tracking-wide">Estimasi Tunggu</div>
                                        <div class="text-sm font-bold text-blue-950 mt-0.5 tabular-nums">~10 Menit</div>
                                    </div>
                                </div>

                                {{-- Counter Display & Service Info --}}
                                <div class="p-3 bg-neutral-50 border border-neutral-200/80 rounded-xl space-y-1 text-xs">
                                    <div class="flex justify-between text-neutral-500">
                                        <span>Loket Pelayanan:</span>
                                        <span class="font-semibold text-neutral-900">Loket 1 (L-01)</span>
                                    </div>
                                    <div class="flex justify-between text-neutral-500">
                                        <span>Sedang Dilayani Saat Ini:</span>
                                        <span class="font-semibold text-emerald-700 tabular-nums">A-000 (Selesai)</span>
                                    </div>
                                </div>

                                {{-- Progress Indicator --}}
                                <div class="space-y-1.5 pt-1">
                                    <div class="flex justify-between text-xs text-neutral-500 font-medium">
                                        <span>Antrean Berikutnya</span>
                                        <span class="text-brand-700 font-bold">Segera Dipanggil</span>
                                    </div>
                                    <div class="w-full bg-neutral-200 h-2 rounded-full overflow-hidden">
                                        <div class="bg-brand-600 h-full w-4/5 rounded-full animate-pulse"></div>
                                    </div>
                                </div>

                                {{-- Card Footer Pill --}}
                                <div class="pt-2 flex items-center justify-between text-xs text-neutral-500 border-t border-neutral-100">
                                    <span class="flex items-center gap-1.5 text-emerald-600 font-medium">
                                        <svg class="w-4 h-4 text-emerald-500" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                        </svg>
                                        Panggilan Real-Time Aktif
                                    </span>
                                    <span class="text-neutral-400">Audio Loket Siap</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- How It Works Section (Cara Kerja Alur Pelanggan & Walk-in) --}}
        <section id="cara-kerja" class="py-20 bg-white border-t border-neutral-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-3xl mx-auto mb-16 space-y-4">
                    <span class="text-xs font-bold tracking-wider uppercase text-brand-700 bg-brand-50 px-3 py-1 rounded-full border border-brand-200">
                        Alur Layanan Praktis
                    </span>
                    <h2 class="text-3xl sm:text-4xl font-black text-neutral-950 tracking-tight">
                        Cara Kerja AntreIn dalam 5 Langkah Mudah
                    </h2>
                    <p class="text-neutral-600 text-base sm:text-lg">
                        Pelanggan dapat mengambil antrean secara online tanpa perlu instalasi aplikasi, sementara staf kasir mengelola seluruh antrean online maupun walk-in dari satu dashboard terpadu.
                    </p>
                </div>

                {{-- 5 Step Customer Flow --}}
                <div class="grid grid-cols-1 md:grid-cols-5 gap-6">
                    {{-- Step 1 --}}
                    <div class="p-6 rounded-2xl bg-neutral-50 border border-neutral-200 flex flex-col items-center text-center space-y-4">
                        <div class="w-12 h-12 rounded-xl bg-brand-700 text-white flex items-center justify-center font-black text-lg shadow-md shadow-brand-700/20">
                            1
                        </div>
                        <h3 class="font-bold text-neutral-900 text-lg">Pilih UMKM</h3>
                        <p class="text-neutral-600 text-sm leading-relaxed">
                            Buka tautan langsung profil UMKM atau pindai QR Code yang terpasang di lokasi meja kasir usaha.
                        </p>
                    </div>

                    {{-- Step 2 --}}
                    <div class="p-6 rounded-2xl bg-neutral-50 border border-neutral-200 flex flex-col items-center text-center space-y-4">
                        <div class="w-12 h-12 rounded-xl bg-brand-700 text-white flex items-center justify-center font-black text-lg shadow-md shadow-brand-700/20">
                            2
                        </div>
                        <h3 class="font-bold text-neutral-900 text-lg">Pilih Layanan</h3>
                        <p class="text-neutral-600 text-sm leading-relaxed">
                            Tentukan jenis layanan usaha yang ingin didapatkan beserta estimasi biaya dan durasi pengerjaannya.
                        </p>
                    </div>

                    {{-- Step 3 --}}
                    <div class="p-6 rounded-2xl bg-neutral-50 border border-neutral-200 flex flex-col items-center text-center space-y-4">
                        <div class="w-12 h-12 rounded-xl bg-brand-700 text-white flex items-center justify-center font-black text-lg shadow-md shadow-brand-700/20">
                            3
                        </div>
                        <h3 class="font-bold text-neutral-900 text-lg">Ambil Antrean Online</h3>
                        <p class="text-neutral-600 text-sm leading-relaxed">
                            Dapatkan nomor tiket digital seketika tanpa perlu mendaftar akun atau mengingat kata sandi.
                        </p>
                    </div>

                    {{-- Step 4 --}}
                    <div class="p-6 rounded-2xl bg-neutral-50 border border-neutral-200 flex flex-col items-center text-center space-y-4">
                        <div class="w-12 h-12 rounded-xl bg-brand-700 text-white flex items-center justify-center font-black text-lg shadow-md shadow-brand-700/20">
                            4
                        </div>
                        <h3 class="font-bold text-neutral-900 text-lg">Pantau Posisi Antrean</h3>
                        <p class="text-neutral-600 text-sm leading-relaxed">
                            Pantau pergerakan antrean dan estimasi waktu tunggu secara real-time langsung dari layar smartphone Anda.
                        </p>
                    </div>

                    {{-- Step 5 --}}
                    <div class="p-6 rounded-2xl bg-neutral-50 border border-neutral-200 flex flex-col items-center text-center space-y-4">
                        <div class="w-12 h-12 rounded-xl bg-brand-700 text-white flex items-center justify-center font-black text-lg shadow-md shadow-brand-700/20">
                            5
                        </div>
                        <h3 class="font-bold text-neutral-900 text-lg">Datang Saat Dipanggil</h3>
                        <p class="text-neutral-600 text-sm leading-relaxed">
                            Tiba di loket tepat waktu ketika nomor Anda dipanggil dengan notifikasi audio dan visual yang jelas.
                        </p>
                    </div>
                </div>

                {{-- Walk-in Integration Note Box --}}
                <div class="mt-12 bg-blue-50/70 border border-blue-200 rounded-2xl p-6 sm:p-8 flex flex-col sm:flex-row items-center gap-6">
                    <div class="w-14 h-14 rounded-2xl bg-brand-700 text-white flex items-center justify-center shrink-0 shadow-md">
                        <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                    </div>
                    <div class="space-y-1 text-center sm:text-left">
                        <h4 class="text-base sm:text-lg font-bold text-blue-950">
                            Dukungan Antrean Walk-in untuk Pelanggan di Lokasi
                        </h4>
                        <p class="text-sm text-blue-900/80 leading-relaxed">
                            Bagaimana jika pelanggan datang langsung tanpa smartphone? Operator UMKM dapat menerbitkan tiket walk-in dalam 1 klik dari dashboard merchant. Seluruh antrean online dan walk-in otomatis terurut adil secara sistematis (FIFO).
                        </p>
                    </div>
                </div>
            </div>
        </section>

        {{-- Features Section (Fitur yang benar-benar tersedia) --}}
        <section id="fitur" class="py-20 bg-neutral-50 border-t border-neutral-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-3xl mx-auto mb-16 space-y-4">
                    <span class="text-xs font-bold tracking-wider uppercase text-brand-700 bg-brand-100/70 px-3 py-1 rounded-full border border-brand-200">
                        Kemampuan Teruji
                    </span>
                    <h2 class="text-3xl sm:text-4xl font-black text-neutral-950 tracking-tight">
                        Fitur Lengkap untuk Efisiensi Antrean Bisnis Anda
                    </h2>
                    <p class="text-neutral-600 text-base sm:text-lg">
                        Seluruh fitur dirancang untuk kebutuhan operasional UMKM Indonesia, mulai dari kasir, ruang tunggu, hingga analisis performa layanan.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    {{-- 1. Unified Online & Walk-in Queue --}}
                    <div class="bg-white p-8 rounded-2xl border border-neutral-200 shadow-sm hover:shadow-md transition space-y-4">
                        <div class="w-12 h-12 rounded-xl bg-blue-50 text-brand-700 flex items-center justify-center">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-neutral-900">Unified Online &amp; Walk-in Queue</h3>
                        <p class="text-neutral-600 text-sm leading-relaxed">
                            Menggabungkan antrean yang diambil pelanggan via web dengan antrean walk-in di lokasi ke dalam satu urutan antrean adil berbasis First-In First-Out.
                        </p>
                    </div>

                    {{-- 2. Multi-Counter Management --}}
                    <div class="bg-white p-8 rounded-2xl border border-neutral-200 shadow-sm hover:shadow-md transition space-y-4">
                        <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-700 flex items-center justify-center">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-neutral-900">Multi-Counter Management</h3>
                        <p class="text-neutral-600 text-sm leading-relaxed">
                            Kelola beberapa loket layanan sekaligus (Loket 1, Loket 2, dll). Bagi tugas operator staf secara rapi dengan hak akses berbasis RBAC yang aman.
                        </p>
                    </div>

                    {{-- 3. Real-Time Queue Updates --}}
                    <div class="bg-white p-8 rounded-2xl border border-neutral-200 shadow-sm hover:shadow-md transition space-y-4">
                        <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-neutral-900">Real-Time Queue Updates</h3>
                        <p class="text-neutral-600 text-sm leading-relaxed">
                            Didukung Laravel Reverb WebSocket. Perubahan status panggilan antrean langsung tersinkronisasi instan ke smartphone pelanggan tanpa perlu memuat ulang halaman.
                        </p>
                    </div>

                    {{-- 4. Email & Browser Notifications --}}
                    <div class="bg-white p-8 rounded-2xl border border-neutral-200 shadow-sm hover:shadow-md transition space-y-4">
                        <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-700 flex items-center justify-center">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-neutral-900">Email &amp; Browser Notifications</h3>
                        <p class="text-neutral-600 text-sm leading-relaxed">
                            Kirimkan pembaruan nomor antrean melalui email atau notifikasi peramban langsung saat giliran pelanggan semakin dekat ke meja loket.
                        </p>
                    </div>

                    {{-- 5. Reports & Analytics --}}
                    <div class="bg-white p-8 rounded-2xl border border-neutral-200 shadow-sm hover:shadow-md transition space-y-4">
                        <div class="w-12 h-12 rounded-xl bg-purple-50 text-purple-700 flex items-center justify-center">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-neutral-900">Reports &amp; Analytics</h3>
                        <p class="text-neutral-600 text-sm leading-relaxed">
                            Pantau volume antrean harian, tren jam-jam sibuk, durasi pelayanan rata-rata tiap operator, dan tingkat kepuasan pelanggan secara komprehensif.
                        </p>
                    </div>

                    {{-- 6. CSV & PDF Export --}}
                    <div class="bg-white p-8 rounded-2xl border border-neutral-200 shadow-sm hover:shadow-md transition space-y-4">
                        <div class="w-12 h-12 rounded-xl bg-rose-50 text-rose-700 flex items-center justify-center">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-neutral-900">CSV &amp; PDF Export</h3>
                        <p class="text-neutral-600 text-sm leading-relaxed">
                            Unduh rekapitulasi data operasional antrean bisnis Anda ke dalam berkas format CSV untuk analisis spreadsheet atau dokumen PDF siap cetak.
                        </p>
                    </div>

                    {{-- 7. Public Queue Display --}}
                    <div class="bg-white p-8 rounded-2xl border border-neutral-200 shadow-sm hover:shadow-md transition space-y-4">
                        <div class="w-12 h-12 rounded-xl bg-cyan-50 text-cyan-700 flex items-center justify-center">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-neutral-900">Public Queue Display</h3>
                        <p class="text-neutral-600 text-sm leading-relaxed">
                            Tampilkan nomor antrean yang sedang aktif pada layar Smart TV atau monitor ruang tunggu dengan dukungan pemanggilan suara (Text-to-Speech) otomatis.
                        </p>
                    </div>

                    {{-- 8. Customer Reviews --}}
                    <div class="bg-white p-8 rounded-2xl border border-neutral-200 shadow-sm hover:shadow-md transition space-y-4">
                        <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-700 flex items-center justify-center">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-neutral-900">Customer Reviews</h3>
                        <p class="text-neutral-600 text-sm leading-relaxed">
                            Kumpulkan feedback kepuasan dan rating bintang dari pelanggan segera setelah tiket pelayanan diselesaikan guna evaluasi mutu layanan UMKM.
                        </p>
                    </div>

                    {{-- 9. Historical Waiting Time Estimation --}}
                    <div class="bg-white p-8 rounded-2xl border border-neutral-200 shadow-sm hover:shadow-md transition space-y-4">
                        <div class="w-12 h-12 rounded-xl bg-teal-50 text-teal-700 flex items-center justify-center">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-neutral-900">Historical Waiting Time Estimation</h3>
                        <p class="text-neutral-600 text-sm leading-relaxed">
                            Kalkulasi sisa waktu tunggu secara cerdas berdasarkan riwayat durasi riil pelayanan sebelumnya, membantu pelanggan datang tepat pada waktunya.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        {{-- UMKM Section (Manfaat untuk Pelaku Usaha) --}}
        <section id="untuk-umkm" class="py-20 bg-white border-t border-neutral-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
                    <div class="lg:col-span-6 space-y-6">
                        <span class="text-xs font-bold tracking-wider uppercase text-brand-700 bg-brand-50 px-3 py-1 rounded-full border border-brand-200">
                            Untuk Pemilik &amp; Pengelola UMKM
                        </span>
                        <h2 class="text-3xl sm:text-4xl font-black text-neutral-950 tracking-tight leading-tight">
                            Solusi Nyata untuk Mengatasi Antrean Berdesakan di Tempat Usaha
                        </h2>
                        <p class="text-neutral-600 text-base leading-relaxed">
                            AntreIn dirancang untuk mengeliminasi kekacauan antrean fisik, menghemat waktu staf, dan memberikan kepastian layanan bagi pelanggan Anda.
                        </p>

                        {{-- Benefits List --}}
                        <div class="space-y-4 pt-2">
                            <div class="flex items-start gap-3">
                                <div class="w-6 h-6 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0 mt-0.5 font-bold text-xs">
                                    &check;
                                </div>
                                <div class="text-sm text-neutral-700">
                                    <strong class="text-neutral-950 font-semibold">Mengurangi pengelolaan antrean manual</strong> yang rawan terjadi salah panggil dan kericuhan antrean.
                                </div>
                            </div>

                            <div class="flex items-start gap-3">
                                <div class="w-6 h-6 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0 mt-0.5 font-bold text-xs">
                                    &check;
                                </div>
                                <div class="text-sm text-neutral-700">
                                    <strong class="text-neutral-950 font-semibold">Menggabungkan antrean online dan walk-in</strong> dalam satu urutan terpusat yang terpantau real-time.
                                </div>
                            </div>

                            <div class="flex items-start gap-3">
                                <div class="w-6 h-6 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0 mt-0.5 font-bold text-xs">
                                    &check;
                                </div>
                                <div class="text-sm text-neutral-700">
                                    <strong class="text-neutral-950 font-semibold">Membantu staf melayani pelanggan berdasarkan prinsip FIFO</strong> (First-In First-Out) yang adil.
                                </div>
                            </div>

                            <div class="flex items-start gap-3">
                                <div class="w-6 h-6 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0 mt-0.5 font-bold text-xs">
                                    &check;
                                </div>
                                <div class="text-sm text-neutral-700">
                                    <strong class="text-neutral-950 font-semibold">Memudahkan pemantauan loket &amp; kinerja staf</strong> operasional dari panel dashboard pemilik.
                                </div>
                            </div>

                            <div class="flex items-start gap-3">
                                <div class="w-6 h-6 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0 mt-0.5 font-bold text-xs">
                                    &check;
                                </div>
                                <div class="text-sm text-neutral-700">
                                    <strong class="text-neutral-950 font-semibold">Menyediakan laporan operasional lengkap</strong> untuk melihat jam-jam puncak dan evaluasi bisnis.
                                </div>
                            </div>

                            <div class="flex items-start gap-3">
                                <div class="w-6 h-6 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0 mt-0.5 font-bold text-xs">
                                    &check;
                                </div>
                                <div class="text-sm text-neutral-700">
                                    <strong class="text-neutral-950 font-semibold">Memberikan pengalaman pelanggan yang lebih jelas</strong> dan menyenangkan bagi reputasi usaha Anda.
                                </div>
                            </div>
                        </div>

                        <div class="pt-4">
                            <a href="{{ route('register') }}" class="inline-flex items-center gap-2 px-6 py-3.5 rounded-xl font-bold text-base text-white bg-brand-700 hover:bg-brand-800 shadow-md shadow-brand-700/20 transition">
                                <span>Mulai Kelola Antrean Usahamu</span>
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                            </a>
                        </div>
                    </div>

                    {{-- Right Feature Highlights --}}
                    <div class="lg:col-span-6 bg-neutral-50 rounded-3xl p-8 border border-neutral-200 space-y-6">
                        <h3 class="text-xl font-bold text-neutral-950">
                            Cocok untuk Berbagai Jenis Unit Usaha:
                        </h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="p-4 bg-white rounded-xl border border-neutral-200">
                                <div class="font-bold text-neutral-900">Barbershop &amp; Salon</div>
                                <p class="text-xs text-neutral-600 mt-1">Pelanggan tidak perlu menunggu berjam-jam di kursi tunggu.</p>
                            </div>
                            <div class="p-4 bg-white rounded-xl border border-neutral-200">
                                <div class="font-bold text-neutral-900">Klinik &amp; Praktik Dokter</div>
                                <p class="text-xs text-neutral-600 mt-1">Mengurangi kepadatan pasien di ruang tunggu secara tertib.</p>
                            </div>
                            <div class="p-4 bg-white rounded-xl border border-neutral-200">
                                <div class="font-bold text-neutral-900">Kuliner &amp; Restoran</div>
                                <p class="text-xs text-neutral-600 mt-1">Antrean meja santai tanpa kerumunan di depan pintu masuk.</p>
                            </div>
                            <div class="p-4 bg-white rounded-xl border border-neutral-200">
                                <div class="font-bold text-neutral-900">Bengkel &amp; Jasa Servis</div>
                                <p class="text-xs text-neutral-600 mt-1">Transparansi nomor pengerjaan kendaraan secara berkala.</p>
                            </div>
                        </div>

                        <div class="p-4 bg-brand-50 border border-brand-200 rounded-xl text-xs text-brand-900 leading-relaxed">
                            <strong>Setup Mudah:</strong> Tanpa instalasi perangkat keras khusus. Cukup gunakan smartphone, tablet, atau komputer kasir yang sudah Anda miliki saat ini.
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- Customer Section (Pengalaman Pelanggan) --}}
        <section class="py-20 bg-neutral-50 border-t border-neutral-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="max-w-3xl mx-auto text-center space-y-4 mb-16">
                    <span class="text-xs font-bold tracking-wider uppercase text-brand-700 bg-brand-100/70 px-3 py-1 rounded-full border border-brand-200">
                        Pengalaman Pelanggan
                    </span>
                    <h2 class="text-3xl sm:text-4xl font-black text-neutral-950 tracking-tight">
                        Kemudahan Maksimal bagi Pelanggan Anda
                    </h2>
                    <p class="text-neutral-600 text-base sm:text-lg">
                        Pelanggan tidak diwajibkan mengunduh aplikasi tambahan atau mengisi form registrasi yang rumit. Semuanya dapat diakses secara instan dari peramban ponsel.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div class="bg-white p-6 rounded-2xl border border-neutral-200 space-y-3">
                        <div class="w-10 h-10 rounded-xl bg-blue-100 text-brand-700 flex items-center justify-center font-bold">
                            QR
                        </div>
                        <h3 class="font-bold text-neutral-950 text-base">Akses QR Code atau Tautan Langsung</h3>
                        <p class="text-sm text-neutral-600 leading-relaxed">
                            Cukup buka tautan toko atau arahkan kamera smartphone ke QR Code di lokasi kasir untuk langsung mengakses daftar layanan.
                        </p>
                    </div>

                    <div class="bg-white p-6 rounded-2xl border border-neutral-200 space-y-3">
                        <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold">
                            RT
                        </div>
                        <h3 class="font-bold text-neutral-950 text-base">Pantau Status &amp; Estimasi Historis</h3>
                        <p class="text-sm text-neutral-600 leading-relaxed">
                            Layar tiket digital menampilkan sisa antrean dan perkiraan waktu tunggu berdasarkan durasi riil pelayanan sebelumnya.
                        </p>
                    </div>

                    <div class="bg-white p-6 rounded-2xl border border-neutral-200 space-y-3">
                        <div class="w-10 h-10 rounded-xl bg-purple-100 text-purple-700 flex items-center justify-center font-bold">
                            NT
                        </div>
                        <h3 class="font-bold text-neutral-950 text-base">Pembaruan &amp; Notifikasi Panggilan</h3>
                        <p class="text-sm text-neutral-600 leading-relaxed">
                            Pelanggan mendapatkan notifikasi dan panggilan suara saat gilirannya tiba, sehingga dapat menunggu dengan tenang di mana saja.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        {{-- FAQ Section --}}
        <section id="faq" class="py-20 bg-white border-t border-neutral-200">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
                <div class="text-center space-y-4">
                    <span class="text-xs font-bold tracking-wider uppercase text-brand-700 bg-brand-50 px-3 py-1 rounded-full border border-brand-200">
                        Pertanyaan Umum
                    </span>
                    <h2 class="text-3xl sm:text-4xl font-black text-neutral-950 tracking-tight">
                        Pertanyaan yang Sering Diajukan (FAQ)
                    </h2>
                    <p class="text-neutral-600 text-base">
                        Informasi lengkap mengenai cara kerja, fitur, dan penggunaan platform AntreIn.
                    </p>
                </div>

                <div class="space-y-4" x-data="{ active: 1 }">
                    {{-- FAQ 1 --}}
                    <div class="bg-neutral-50 rounded-xl border border-neutral-200 overflow-hidden">
                        <button
                            type="button"
                            @click="active = (active === 1 ? null : 1)"
                            class="w-full px-6 py-5 text-left flex items-center justify-between gap-4 font-bold text-neutral-900 hover:text-brand-700 focus:outline-none"
                        >
                            <span>Apakah pelanggan harus membuat akun untuk mengambil antrean?</span>
                            <svg class="w-5 h-5 shrink-0 transition-transform duration-200" :class="{ 'rotate-180': active === 1 }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <div x-show="active === 1" x-collapse class="px-6 pb-5 text-neutral-600 text-sm leading-relaxed border-t border-neutral-200/60 pt-3">
                            Tidak. Pelanggan dapat mengambil antrean secara langsung melalui peramban ponsel tanpa perlu mengunduh aplikasi dari app store atau mendaftar akun pengguna. Nomor tiket digital disimpan dengan aman di sesi browser pelanggan.
                        </div>
                    </div>

                    {{-- FAQ 2 --}}
                    <div class="bg-neutral-50 rounded-xl border border-neutral-200 overflow-hidden">
                        <button
                            type="button"
                            @click="active = (active === 2 ? null : 2)"
                            class="w-full px-6 py-5 text-left flex items-center justify-between gap-4 font-bold text-neutral-900 hover:text-brand-700 focus:outline-none"
                        >
                            <span>Apakah AntreIn mendukung antrean walk-in bagi pelanggan yang datang langsung?</span>
                            <svg class="w-5 h-5 shrink-0 transition-transform duration-200" :class="{ 'rotate-180': active === 2 }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <div x-show="active === 2" x-collapse class="px-6 pb-5 text-neutral-600 text-sm leading-relaxed border-t border-neutral-200/60 pt-3">
                            Ya. Operator UMKM dapat menerbitkan tiket walk-in dalam 1 klik dari panel kasir dashboard merchant. Sistem otomatis menggabungkan antrean walk-in dan antrean online dalam satu urutan adil terpadu.
                        </div>
                    </div>

                    {{-- FAQ 3 --}}
                    <div class="bg-neutral-50 rounded-xl border border-neutral-200 overflow-hidden">
                        <button
                            type="button"
                            @click="active = (active === 3 ? null : 3)"
                            class="w-full px-6 py-5 text-left flex items-center justify-between gap-4 font-bold text-neutral-900 hover:text-brand-700 focus:outline-none"
                        >
                            <span>Bagaimana pelanggan mengetahui nomor antrean sedang dipanggil?</span>
                            <svg class="w-5 h-5 shrink-0 transition-transform duration-200" :class="{ 'rotate-180': active === 3 }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <div x-show="active === 3" x-collapse class="px-6 pb-5 text-neutral-600 text-sm leading-relaxed border-t border-neutral-200/60 pt-3">
                            Status tiket diperbarui secara real-time via WebSocket. Selain itu, pelanggan dapat menerima notifikasi peramban/email, serta mendengarkan panggilan suara audio yang disiarkan di layar monitor Public Display lokasi UMKM.
                        </div>
                    </div>

                    {{-- FAQ 4 --}}
                    <div class="bg-neutral-50 rounded-xl border border-neutral-200 overflow-hidden">
                        <button
                            type="button"
                            @click="active = (active === 4 ? null : 4)"
                            class="w-full px-6 py-5 text-left flex items-center justify-between gap-4 font-bold text-neutral-900 hover:text-brand-700 focus:outline-none"
                        >
                            <span>Apakah UMKM dapat memiliki lebih dari satu loket dan banyak operator?</span>
                            <svg class="w-5 h-5 shrink-0 transition-transform duration-200" :class="{ 'rotate-180': active === 4 }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <div x-show="active === 4" x-collapse class="px-6 pb-5 text-neutral-600 text-sm leading-relaxed border-t border-neutral-200/60 pt-3">
                            Ya. AntreIn menyediakan fitur Multi-Counter Management dan Role-Based Access Control (RBAC). Pemilik usaha (Owner) dapat membuat banyak loket dan menugaskan staf (Operator) untuk mengoperasikan masing-masing loket tersebut.
                        </div>
                    </div>

                    {{-- FAQ 5 --}}
                    <div class="bg-neutral-50 rounded-xl border border-neutral-200 overflow-hidden">
                        <button
                            type="button"
                            @click="active = (active === 5 ? null : 5)"
                            class="w-full px-6 py-5 text-left flex items-center justify-between gap-4 font-bold text-neutral-900 hover:text-brand-700 focus:outline-none"
                        >
                            <span>Bagaimana cara mendaftar sebagai merchant di AntreIn?</span>
                            <svg class="w-5 h-5 shrink-0 transition-transform duration-200" :class="{ 'rotate-180': active === 5 }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <div x-show="active === 5" x-collapse class="px-6 pb-5 text-neutral-600 text-sm leading-relaxed border-t border-neutral-200/60 pt-3">
                            Cukup klik tombol "Daftarkan UMKM", isi nama dan email Anda. Setelah akun terdaftar, ikuti langkah onboarding terpandu (nama usaha, kategori, jam operasional, dan layanan awal) yang dapat diselesaikan dalam 2 menit.
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- Bottom CTA Banner --}}
        <section class="py-16 bg-neutral-950 text-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center space-y-6">
                <h2 class="text-3xl sm:text-4xl font-black tracking-tight">
                    Mulai Layani Pelanggan Anda Lebih Teratur Hari Ini
                </h2>
                <p class="text-neutral-400 max-w-2xl mx-auto text-base sm:text-lg">
                    AntreIn adalah platform manajemen antrean digital yang membantu UMKM mengelola antrean online dan walk-in dalam satu sistem terpadu, sehingga pelayanan pelanggan menjadi lebih teratur dan efisien.
                </p>
                <div class="pt-4 flex flex-col sm:flex-row items-center justify-center gap-4">
                    <a href="{{ route('register') }}" class="w-full sm:w-auto inline-flex items-center justify-center px-8 py-4 rounded-xl font-bold text-base text-white bg-brand-700 hover:bg-brand-800 shadow-lg shadow-brand-700/30 transition">
                        Daftarkan UMKM Sekarang &rarr;
                    </a>
                    <a href="{{ route('login') }}" class="w-full sm:w-auto inline-flex items-center justify-center px-8 py-4 rounded-xl font-bold text-base text-neutral-300 hover:text-white bg-neutral-900 hover:bg-neutral-800 border border-neutral-800 transition">
                        Masuk ke Akun
                    </a>
                </div>
            </div>
        </section>
    </main>

    {{-- Footer --}}
    <footer class="bg-white border-t border-neutral-200 py-12 text-sm text-neutral-600">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-8">
                <div class="md:col-span-2 space-y-4">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg bg-brand-700 text-white flex items-center justify-center font-bold text-lg">
                            A
                        </div>
                        <span class="text-xl font-black tracking-tight text-neutral-950">
                            AntreIn<span class="text-brand-600">.</span>
                        </span>
                    </div>
                    <p class="text-neutral-500 max-w-sm text-sm leading-relaxed">
                        AntreIn adalah platform manajemen antrean digital yang membantu UMKM mengelola antrean online dan walk-in dalam satu sistem terpadu, sehingga pelayanan pelanggan menjadi lebih teratur dan efisien.
                    </p>
                </div>

                <div class="space-y-3">
                    <div class="font-bold text-neutral-900">Navigasi</div>
                    <ul class="space-y-2">
                        <li><a href="#" class="hover:text-brand-700">Beranda</a></li>
                        <li><a href="#cara-kerja" class="hover:text-brand-700">Cara Kerja</a></li>
                        <li><a href="#fitur" class="hover:text-brand-700">Fitur</a></li>
                        <li><a href="#untuk-umkm" class="hover:text-brand-700">Untuk UMKM</a></li>
                        <li><a href="#faq" class="hover:text-brand-700">FAQ</a></li>
                    </ul>
                </div>

                <div class="space-y-3">
                    <div class="font-bold text-neutral-900">Akses Akun</div>
                    <ul class="space-y-2">
                        <li><a href="{{ route('login') }}" class="hover:text-brand-700">Masuk Akun</a></li>
                        <li><a href="{{ route('register') }}" class="hover:text-brand-700">Daftarkan UMKM</a></li>
                        @if (app()->environment(['local', 'testing']))
                            <li><a href="{{ route('preview.design-system') }}" class="text-amber-700 font-semibold hover:underline">Design System Preview</a></li>
                        @endif
                    </ul>
                </div>
            </div>

            <div class="pt-8 border-t border-neutral-100 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-neutral-500">
                <div>
                    &copy; {{ date('Y') }} AntreIn Platform. Hak cipta dilindungi undang-undang.
                </div>
                <div class="flex items-center gap-4">
                    <span>Platform Manajemen Antrean Digital Terpadu</span>
                </div>
            </div>
        </div>
    </footer>
</x-layouts.base>
