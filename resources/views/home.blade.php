<x-layouts.base title="AntreIn — Manajemen Antrean Digital UMKM Modern">
    {{-- Top Notification Bar (Demo Helper in local/testing) --}}
    @if (app()->environment(['local', 'testing']))
        <div class="bg-neutral-900 text-neutral-200 text-xs py-2 px-4 border-b border-neutral-800">
            <div class="max-w-7xl mx-auto flex flex-wrap items-center justify-between gap-2">
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-400/20 text-amber-300 border border-amber-400/30">
                        DEV / TEST ENVIRONMENT
                    </span>
                    <span>Tersedia panel akses cepat di halaman login &amp; floating persona switcher.</span>
                </div>
                <div class="flex items-center gap-4 text-neutral-400">
                    <a href="{{ route('login') }}" class="hover:text-white underline">Akses Cepat Login</a>
                    <span class="text-neutral-700">|</span>
                    <a href="{{ route('customer.queue', 'barbershop-garasi-demo') }}" class="hover:text-white underline">Demo Tiket Pelanggan</a>
                    <span class="text-neutral-700">|</span>
                    <a href="{{ route('preview.design-system') }}" class="hover:text-white underline">Design System Testbed</a>
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
                        <a href="#cara-kerja" class="text-sm font-medium text-neutral-600 hover:text-brand-700 transition">
                            Cara Kerja
                        </a>
                        <a href="#fitur-umkm" class="text-sm font-medium text-neutral-600 hover:text-brand-700 transition">
                            Fitur UMKM
                        </a>
                        <a href="#demo-pelanggan" class="text-sm font-medium text-neutral-600 hover:text-brand-700 transition">
                            Akses Pelanggan
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
                            Daftarkan Usaha
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
            <a href="#cara-kerja" @click="mobileMenuOpen = false" class="block py-2 text-base font-medium text-neutral-700 hover:text-brand-700">Cara Kerja</a>
            <a href="#fitur-umkm" @click="mobileMenuOpen = false" class="block py-2 text-base font-medium text-neutral-700 hover:text-brand-700">Fitur UMKM</a>
            <a href="#demo-pelanggan" @click="mobileMenuOpen = false" class="block py-2 text-base font-medium text-neutral-700 hover:text-brand-700">Akses Pelanggan</a>
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
                    <a href="{{ route('register') }}" class="w-full text-center py-2.5 px-4 rounded-lg text-sm font-semibold text-white bg-brand-700">Daftarkan Usaha</a>
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
                            Platform Antrean Digital UMKM #1 di Indonesia
                        </div>

                        <h1 class="text-4xl sm:text-5xl lg:text-6xl font-black text-neutral-950 tracking-tight leading-[1.12]">
                            Kelola Antrean Usaha Anda, <span class="text-brand-700">Bebas Antre Berdesakan.</span>
                        </h1>

                        <p class="text-base sm:text-lg text-neutral-600 max-w-2xl mx-auto lg:mx-0 leading-relaxed">
                            Tingkatkan kepuasan pelanggan dengan antrean digital berbasis QR &amp; web. Pelanggan cukup scan untuk ambil nomor, pantau giliran dari HP, dan datang tepat waktu.
                        </p>

                        {{-- Action Buttons --}}
                        <div class="flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-3 sm:gap-4 pt-2">
                            <a href="{{ route('register') }}" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-xl text-base font-bold text-white bg-brand-700 hover:bg-brand-800 shadow-lg shadow-brand-700/25 transition">
                                <span>Daftarkan UMKM Gratis</span>
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                            </a>
                            <a href="{{ route('customer.queue', 'barbershop-garasi-demo') }}" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-xl text-base font-semibold text-neutral-700 bg-white hover:bg-neutral-50 border border-neutral-300 shadow-sm transition">
                                <svg class="w-5 h-5 text-neutral-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                </svg>
                                <span>Coba Ambil Tiket Demo</span>
                            </a>
                        </div>

                        {{-- Social Proof & Key Points --}}
                        <div class="pt-6 grid grid-cols-3 gap-4 border-t border-neutral-200/80 text-left">
                            <div>
                                <div class="text-2xl font-black text-neutral-950">100%</div>
                                <div class="text-xs text-neutral-500 font-medium mt-0.5">Tanpa Aplikasi (Web Instan)</div>
                            </div>
                            <div>
                                <div class="text-2xl font-black text-neutral-950">&lt; 1 Detik</div>
                                <div class="text-xs text-neutral-500 font-medium mt-0.5">Pembaruan Real-Time</div>
                            </div>
                            <div>
                                <div class="text-2xl font-black text-neutral-950">Multi-Loket</div>
                                <div class="text-xs text-neutral-500 font-medium mt-0.5">Dukungan Banyak Operator</div>
                            </div>
                        </div>
                    </div>

                    {{-- Right Hero Mockup (Blade Ticket Mockup) --}}
                    <div class="lg:col-span-5 flex justify-center">
                        <div class="relative w-full max-w-sm sm:max-w-md">
                            {{-- Decorative Background Glow --}}
                            <div class="absolute -inset-4 bg-gradient-to-r from-brand-500/20 to-blue-400/20 rounded-3xl blur-2xl -z-10"></div>

                            {{-- Mockup Card --}}
                            <div class="bg-white rounded-2xl border border-neutral-200/90 shadow-2xl p-6 space-y-6">
                                {{-- Card Header --}}
                                <div class="flex items-center justify-between border-b border-neutral-100 pb-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-xl bg-neutral-900 text-white flex items-center justify-center font-bold text-sm">
                                            BG
                                        </div>
                                        <div>
                                            <h2 class="text-sm font-bold text-neutral-900">Barbershop Garasi</h2>
                                            <div class="flex items-center gap-1.5 text-xs text-emerald-600 font-medium">
                                                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-ping"></span>
                                                <span>Buka &bull; Antrean Berjalan</span>
                                            </div>
                                        </div>
                                    </div>
                                    <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-brand-50 text-brand-700 border border-brand-200">
                                        Demo Tiket
                                    </span>
                                </div>

                                {{-- Ticket Big Number Display --}}
                                <div class="text-center py-4 bg-neutral-50 rounded-xl border border-neutral-200/60 space-y-1">
                                    <div class="text-xs font-semibold tracking-wider uppercase text-neutral-500">Nomor Antrean Anda</div>
                                    <div class="text-5xl font-black tracking-tight text-brand-700">B-014</div>
                                    <div class="text-xs font-medium text-neutral-600">Layanan: Potong Rambut Dewasa</div>
                                </div>

                                {{-- Live Status Details --}}
                                <div class="grid grid-cols-2 gap-3 text-center">
                                    <div class="p-3 bg-emerald-50/70 border border-emerald-200 rounded-lg">
                                        <div class="text-[11px] font-semibold text-emerald-800 uppercase tracking-wide">Status Sekarang</div>
                                        <div class="text-sm font-bold text-emerald-950 mt-0.5">Sedang Dilayani</div>
                                    </div>
                                    <div class="p-3 bg-blue-50/70 border border-blue-200 rounded-lg">
                                        <div class="text-[11px] font-semibold text-blue-800 uppercase tracking-wide">Loket Tujuan</div>
                                        <div class="text-sm font-bold text-blue-950 mt-0.5">Loket 1 (Wayan)</div>
                                    </div>
                                </div>

                                {{-- Progress Stepper Mini --}}
                                <div class="space-y-2 pt-2">
                                    <div class="flex justify-between text-xs text-neutral-500 font-medium">
                                        <span>Menunggu (2 antrean lagi)</span>
                                        <span class="text-brand-700 font-bold">Giliran Tiba!</span>
                                    </div>
                                    <div class="w-full bg-neutral-200 h-2 rounded-full overflow-hidden">
                                        <div class="bg-brand-600 h-full w-full rounded-full animate-pulse"></div>
                                    </div>
                                </div>

                                {{-- Floating Pill --}}
                                <div class="pt-2 flex items-center justify-between text-xs text-neutral-500 border-t border-neutral-100">
                                    <span class="flex items-center gap-1">
                                        <svg class="w-4 h-4 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                        Live Reverb WebSocket
                                    </span>
                                    <span class="text-neutral-400">Panggilan Audio Aktif</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- How It Works Section (Cara Kerja 5 Langkah) --}}
        <section id="cara-kerja" class="py-20 bg-white border-t border-neutral-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-3xl mx-auto mb-16 space-y-4">
                    <span class="text-xs font-bold tracking-wider uppercase text-brand-700 bg-brand-50 px-3 py-1 rounded-full border border-brand-200">
                        Alur Pelanggan Tanpa Hambatan
                    </span>
                    <h2 class="text-3xl sm:text-4xl font-black text-neutral-950 tracking-tight">
                        Cara Kerja AntreIn dalam 5 Langkah Mudah
                    </h2>
                    <p class="text-neutral-600 text-base sm:text-lg">
                        Pelanggan tidak perlu repot install aplikasi baru. Cukup scan, pantau, dan nikmati layanan dengan nyaman.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-5 gap-6">
                    {{-- Step 1 --}}
                    <div class="p-6 rounded-2xl bg-neutral-50 border border-neutral-200 flex flex-col items-center text-center space-y-4 relative">
                        <div class="w-12 h-12 rounded-xl bg-brand-700 text-white flex items-center justify-center font-black text-lg shadow-md shadow-brand-700/20">
                            1
                        </div>
                        <h3 class="font-bold text-neutral-900 text-lg">Scan QR Code</h3>
                        <p class="text-neutral-600 text-sm leading-relaxed">
                            Pelanggan scan QR code di meja kasir, banner, atau akses link langsung toko dari smartphone.
                        </p>
                    </div>

                    {{-- Step 2 --}}
                    <div class="p-6 rounded-2xl bg-neutral-50 border border-neutral-200 flex flex-col items-center text-center space-y-4 relative">
                        <div class="w-12 h-12 rounded-xl bg-brand-700 text-white flex items-center justify-center font-black text-lg shadow-md shadow-brand-700/20">
                            2
                        </div>
                        <h3 class="font-bold text-neutral-900 text-lg">Ambil Tiket</h3>
                        <p class="text-neutral-600 text-sm leading-relaxed">
                            Pilih layanan yang diinginkan dan dapatkan nomor tiket digital resmi tanpa perlu registrasi akun.
                        </p>
                    </div>

                    {{-- Step 3 --}}
                    <div class="p-6 rounded-2xl bg-neutral-50 border border-neutral-200 flex flex-col items-center text-center space-y-4 relative">
                        <div class="w-12 h-12 rounded-xl bg-brand-700 text-white flex items-center justify-center font-black text-lg shadow-md shadow-brand-700/20">
                            3
                        </div>
                        <h3 class="font-bold text-neutral-900 text-lg">Pantau Waktu</h3>
                        <p class="text-neutral-600 text-sm leading-relaxed">
                            Lihat sisa antrean di depan Anda dan estimasi waktu tunggu secara langsung di layar ponsel.
                        </p>
                    </div>

                    {{-- Step 4 --}}
                    <div class="p-6 rounded-2xl bg-neutral-50 border border-neutral-200 flex flex-col items-center text-center space-y-4 relative">
                        <div class="w-12 h-12 rounded-xl bg-brand-700 text-white flex items-center justify-center font-black text-lg shadow-md shadow-brand-700/20">
                            4
                        </div>
                        <h3 class="font-bold text-neutral-900 text-lg">Panggilan Suara</h3>
                        <p class="text-neutral-600 text-sm leading-relaxed">
                            Notifikasi real-time dan panggilan audio loket berbunyi saat nomor Anda dipanggil oleh operator.
                        </p>
                    </div>

                    {{-- Step 5 --}}
                    <div class="p-6 rounded-2xl bg-neutral-50 border border-neutral-200 flex flex-col items-center text-center space-y-4 relative">
                        <div class="w-12 h-12 rounded-xl bg-brand-700 text-white flex items-center justify-center font-black text-lg shadow-md shadow-brand-700/20">
                            5
                        </div>
                        <h3 class="font-bold text-neutral-900 text-lg">Beri Ulasan</h3>
                        <p class="text-neutral-600 text-sm leading-relaxed">
                            Setelah pelayanan selesai, beri rating bintang dan ulasan langsung dari halaman tiket.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        {{-- Merchant Features Section (Fitur UMKM) --}}
        <section id="fitur-umkm" class="py-20 bg-neutral-50 border-t border-neutral-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-3xl mx-auto mb-16 space-y-4">
                    <span class="text-xs font-bold tracking-wider uppercase text-brand-700 bg-brand-100/70 px-3 py-1 rounded-full border border-brand-200">
                        Solusi Lengkap Bisnis
                    </span>
                    <h2 class="text-3xl sm:text-4xl font-black text-neutral-950 tracking-tight">
                        Fitur Andal yang Dirancang Khusus untuk UMKM
                    </h2>
                    <p class="text-neutral-600 text-base sm:text-lg">
                        Mulai dari operasional kasir, pembagian loket staf, tampilan layar monitor TV, hingga laporan performa harian.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    {{-- Feature 1 --}}
                    <div class="bg-white p-8 rounded-2xl border border-neutral-200 shadow-sm hover:shadow-md transition space-y-4">
                        <div class="w-12 h-12 rounded-xl bg-blue-50 text-brand-700 flex items-center justify-center">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-neutral-900">Multi-Loket &amp; Peran Staf</h3>
                        <p class="text-neutral-600 text-sm leading-relaxed">
                            Buat loket kasir atau meja layanan tanpa batas (Loket 1, Loket 2). Tugaskan operator staf dengan hak akses terpisah secara aman.
                        </p>
                    </div>

                    {{-- Feature 2 --}}
                    <div class="bg-white p-8 rounded-2xl border border-neutral-200 shadow-sm hover:shadow-md transition space-y-4">
                        <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-neutral-900">Pembaruan Real-Time (WebSocket)</h3>
                        <p class="text-neutral-600 text-sm leading-relaxed">
                            Didukung Laravel Reverb. Perubahan panggilan nomor langsung tersinkronisasi instan ke smartphone pelanggan dan monitor TV.
                        </p>
                    </div>

                    {{-- Feature 3 --}}
                    <div class="bg-white p-8 rounded-2xl border border-neutral-200 shadow-sm hover:shadow-md transition space-y-4">
                        <div class="w-12 h-12 rounded-xl bg-purple-50 text-purple-700 flex items-center justify-center">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-neutral-900">Layar Antrean TV (Public Display)</h3>
                        <p class="text-neutral-600 text-sm leading-relaxed">
                            Tampilkan antrean di Smart TV ruang tunggu dengan suara panggilan otomatis berbahasa Indonesia yang jernih.
                        </p>
                    </div>

                    {{-- Feature 4 --}}
                    <div class="bg-white p-8 rounded-2xl border border-neutral-200 shadow-sm hover:shadow-md transition space-y-4">
                        <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-700 flex items-center justify-center">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-neutral-900">Estimasi Waktu Pintar</h3>
                        <p class="text-neutral-600 text-sm leading-relaxed">
                            Algoritma estimasi cerdas menghitung sisa waktu tunggu pelanggan berdasarkan durasi riil pelayanan sebelumnya.
                        </p>
                    </div>

                    {{-- Feature 5 --}}
                    <div class="bg-white p-8 rounded-2xl border border-neutral-200 shadow-sm hover:shadow-md transition space-y-4">
                        <div class="w-12 h-12 rounded-xl bg-rose-50 text-rose-700 flex items-center justify-center">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-neutral-900">Laporan &amp; Ekspor CSV/PDF</h3>
                        <p class="text-neutral-600 text-sm leading-relaxed">
                            Pantau jumlah pelanggan yang dilayani, jam sibuk, rata-rata durasi tunggu, dan ekspor laporan berkala dengan mudah.
                        </p>
                    </div>

                    {{-- Feature 6 --}}
                    <div class="bg-white p-8 rounded-2xl border border-neutral-200 shadow-sm hover:shadow-md transition space-y-4">
                        <div class="w-12 h-12 rounded-xl bg-teal-50 text-teal-700 flex items-center justify-center">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-neutral-900">Ulasan &amp; Rating Pelanggan</h3>
                        <p class="text-neutral-600 text-sm leading-relaxed">
                            Kumpulkan kepuasan pelanggan secara objektif pasca layanan dengan sistem rating bintang dan komentar langsung.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        {{-- Customer Guest Flow / Live Demo Section --}}
        <section id="demo-pelanggan" class="py-20 bg-white border-t border-neutral-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="bg-gradient-to-br from-brand-700 to-blue-900 rounded-3xl text-white p-8 sm:p-12 lg:p-16 relative overflow-hidden shadow-xl">
                    <div class="relative z-10 max-w-3xl space-y-6">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-white/20 text-white border border-white/30 uppercase tracking-wider">
                            Akses Tamu / Pelanggan
                        </span>
                        <h2 class="text-3xl sm:text-4xl lg:text-5xl font-black tracking-tight leading-tight">
                            Pelanggan Tidak Perlu Akun. Cukup Buka Link &amp; Ambil Antrean.
                        </h2>
                        <p class="text-blue-100 text-base sm:text-lg leading-relaxed">
                            Salah satu keunggulan terbesar AntreIn: Pelanggan tidak merasa terbebani untuk mendaftar akun atau mengingat password. Tiket antrean tersimpan aman di browser dengan token unik.
                        </p>
                        <div class="pt-2 flex flex-col sm:flex-row items-center gap-4">
                            <a href="{{ route('customer.queue', 'barbershop-garasi-demo') }}" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-xl font-bold text-brand-900 bg-white hover:bg-neutral-100 shadow-md transition">
                                <svg class="w-5 h-5 text-brand-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <span>Coba Ambil Tiket Barbershop Garasi</span>
                            </a>
                            <span class="text-xs text-blue-200">
                                Link publik: <code class="bg-blue-950/50 px-2 py-1 rounded text-white font-mono">/q/barbershop-garasi-demo</code>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- FAQ Section --}}
        <section id="faq" class="py-20 bg-neutral-50 border-t border-neutral-200">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
                <div class="text-center space-y-4">
                    <span class="text-xs font-bold tracking-wider uppercase text-brand-700 bg-brand-100/70 px-3 py-1 rounded-full border border-brand-200">
                        Pertanyaan Umum
                    </span>
                    <h2 class="text-3xl sm:text-4xl font-black text-neutral-950 tracking-tight">
                        Pertanyaan yang Sering Diajukan (FAQ)
                    </h2>
                </div>

                <div class="space-y-4" x-data="{ active: 1 }">
                    {{-- FAQ 1 --}}
                    <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden">
                        <button 
                            type="button" 
                            @click="active = (active === 1 ? null : 1)"
                            class="w-full px-6 py-5 text-left flex items-center justify-between gap-4 font-bold text-neutral-900 hover:text-brand-700 focus:outline-none"
                        >
                            <span>Apakah pelanggan wajib registrasi akun atau mengunduh aplikasi?</span>
                            <svg class="w-5 h-5 shrink-0 transition-transform duration-200" :class="{ 'rotate-180': active === 1 }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <div x-show="active === 1" x-collapse class="px-6 pb-5 text-neutral-600 text-sm leading-relaxed border-t border-neutral-100 pt-3">
                            Tidak. Pelanggan cukup membuka tautan usaha atau memindai QR code dari kamera smartphone mereka. Tiket antrean langsung diterbitkan dan dipantau melalui peramban web instan.
                        </div>
                    </div>

                    {{-- FAQ 2 --}}
                    <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden">
                        <button 
                            type="button" 
                            @click="active = (active === 2 ? null : 2)"
                            class="w-full px-6 py-5 text-left flex items-center justify-between gap-4 font-bold text-neutral-900 hover:text-brand-700 focus:outline-none"
                        >
                            <span>Apakah AntreIn mendukung banyak loket dan banyak operator staf?</span>
                            <svg class="w-5 h-5 shrink-0 transition-transform duration-200" :class="{ 'rotate-180': active === 2 }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <div x-show="active === 2" x-collapse class="px-6 pb-5 text-neutral-600 text-sm leading-relaxed border-t border-neutral-100 pt-3">
                            Ya, AntreIn mendukung fitur Multi-Loket (Multi-Counter) dan Role-Based Access Control (RBAC). Pemilik UMKM (Owner) dapat mengundang staf sebagai Operator dan menetapkan loket mana yang dioperasikan masing-masing operator.
                        </div>
                    </div>

                    {{-- FAQ 3 --}}
                    <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden">
                        <button 
                            type="button" 
                            @click="active = (active === 3 ? null : 3)"
                            class="w-full px-6 py-5 text-left flex items-center justify-between gap-4 font-bold text-neutral-900 hover:text-brand-700 focus:outline-none"
                        >
                            <span>Bagaimana cara menampilkan nomor antrean di TV ruang tunggu?</span>
                            <svg class="w-5 h-5 shrink-0 transition-transform duration-200" :class="{ 'rotate-180': active === 3 }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <div x-show="active === 3" x-collapse class="px-6 pb-5 text-neutral-600 text-sm leading-relaxed border-t border-neutral-100 pt-3">
                            Tersedia fitur Public Queue Display. Anda cukup membuka tautan token monitor yang disediakan pada peramban Smart TV, Android TV Box, atau mini PC Anda. Tampilan dioptimalkan untuk layar besar dengan suara panggilan otomatis.
                        </div>
                    </div>

                    {{-- FAQ 4 --}}
                    <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden">
                        <button 
                            type="button" 
                            @click="active = (active === 4 ? null : 4)"
                            class="w-full px-6 py-5 text-left flex items-center justify-between gap-4 font-bold text-neutral-900 hover:text-brand-700 focus:outline-none"
                        >
                            <span>Bagaimana jika pelanggan datang langsung tanpa smartphone (Walk-in)?</span>
                            <svg class="w-5 h-5 shrink-0 transition-transform duration-200" :class="{ 'rotate-180': active === 4 }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <div x-show="active === 4" x-collapse class="px-6 pb-5 text-neutral-600 text-sm leading-relaxed border-t border-neutral-100 pt-3">
                            Operator UMKM dapat menerbitkan tiket walk-in langsung dari panel kasir hanya dengan satu klik dan memasukkan nama pelanggan jika diperlukan. Nomor tiket tetap terintegrasi ke antrean umum.
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- Bottom CTA Banner --}}
        <section class="py-16 bg-neutral-950 text-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center space-y-6">
                <h2 class="text-3xl sm:text-4xl font-black tracking-tight">
                    Siap Mengubah Pengalaman Antrean Usaha Anda?
                </h2>
                <p class="text-neutral-400 max-w-2xl mx-auto text-base sm:text-lg">
                    Bergabung bersama UMKM modern di seluruh Indonesia. Proses pendaftaran hanya memakan waktu 2 menit.
                </p>
                <div class="pt-4 flex flex-col sm:flex-row items-center justify-center gap-4">
                    <a href="{{ route('register') }}" class="w-full sm:w-auto inline-flex items-center justify-center px-8 py-4 rounded-xl font-bold text-base text-white bg-brand-700 hover:bg-brand-800 shadow-lg shadow-brand-700/30 transition">
                        Daftarkan Usaha Sekarang &rarr;
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
                    <p class="text-neutral-500 max-w-sm">
                        Platform manajemen antrean digital modern untuk UMKM Indonesia. Menghubungkan kasir dan pelanggan secara real-time tanpa ribet instalasi aplikasi.
                    </p>
                </div>

                <div class="space-y-3">
                    <div class="font-bold text-neutral-900">Navigasi</div>
                    <ul class="space-y-2">
                        <li><a href="#cara-kerja" class="hover:text-brand-700">Cara Kerja</a></li>
                        <li><a href="#fitur-umkm" class="hover:text-brand-700">Fitur UMKM</a></li>
                        <li><a href="#demo-pelanggan" class="hover:text-brand-700">Akses Pelanggan</a></li>
                        <li><a href="#faq" class="hover:text-brand-700">FAQ</a></li>
                    </ul>
                </div>

                <div class="space-y-3">
                    <div class="font-bold text-neutral-900">Akses Akun</div>
                    <ul class="space-y-2">
                        <li><a href="{{ route('login') }}" class="hover:text-brand-700">Masuk Akun</a></li>
                        <li><a href="{{ route('register') }}" class="hover:text-brand-700">Daftar UMKM Baru</a></li>
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
                    <span>Dirancang untuk kemajuan UMKM Indonesia</span>
                </div>
            </div>
        </div>
    </footer>
</x-layouts.base>

