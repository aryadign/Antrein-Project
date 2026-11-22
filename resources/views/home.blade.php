<x-layouts.base title="AntreIn — Antrean Lebih Teratur, Pelayanan Lebih Lancar.">
    @php
        $user = auth()->user();

        $workspaceLink = match (true) {
            $user === null => null,
            $user->isAdmin() => ['label' => 'Dasbor Admin', 'url' => route('admin.dashboard')],
            $user->isCustomer() => ['label' => 'Antrean Saya', 'url' => route('customer.my-tickets')],
            default => ['label' => 'Dasbor UMKM', 'url' => route('merchant.dashboard')],
        };

        $isCustomer = $user?->isCustomer() ?? false;
        $isStaff = $user !== null && ! $isCustomer;

        $navLinks = [
            ['label' => 'Cara Kerja', 'href' => '#cara-kerja'],
            ['label' => 'Fitur', 'href' => '#fitur'],
            ['label' => 'Untuk UMKM', 'href' => '#untuk-umkm'],
            ['label' => 'Pertanyaan Umum', 'href' => '#pertanyaan'],
        ];

        $steps = [
            ['title' => 'Cari toko', 'body' => 'Buka katalog toko atau pindai kode QR yang ada di meja kasir.'],
            ['title' => 'Pilih layanan', 'body' => 'Lihat estimasi waktu tunggu tiap layanan sebelum memilih.'],
            ['title' => 'Ambil nomor', 'body' => 'Masuk dengan akun pelanggan, lalu ambil hingga 3 antrean aktif.'],
            ['title' => 'Datang saat dipanggil', 'body' => 'Pantau posisi dari ponsel dan datang ketika giliran Anda dekat.'],
        ];

        /** The visitor's own ticket in the fair-queue strip matches the hero ticket (TKT-003). */
        $fairQueue = [
            ['number' => 'TKT-001', 'source' => 'Dari ponsel', 'icon' => 'phone', 'state' => 'Dipanggil'],
            ['number' => 'TKT-002', 'source' => 'Datang langsung', 'icon' => 'store', 'state' => 'Berikutnya'],
            ['number' => 'TKT-003', 'source' => 'Dari ponsel', 'icon' => 'phone', 'state' => 'Tiket Anda'],
            ['number' => 'TKT-004', 'source' => 'Datang langsung', 'icon' => 'store', 'state' => 'Menunggu'],
        ];

        $featureGroups = [
            [
                'title' => 'Untuk pelanggan',
                'image' => 'images/landing/app-ticket.jpg',
                'alt' => 'Tampilan tiket antrean AntreIn di ponsel pelanggan: nomor antrean, perkiraan dilayani, dan jumlah orang di depan',
                'items' => [
                    'Tiket digital dengan nomor dan kode QR',
                    'Posisi antrean dan estimasi tunggu diperbarui otomatis',
                    'Pemberitahuan lewat email dan peramban saat giliran dekat',
                    'Ulasan dan rating setelah dilayani',
                ],
            ],
            [
                'title' => 'Untuk pemilik dan operator',
                'image' => 'images/landing/app-merchant.jpg',
                'alt' => 'Tampilan dasbor pemilik usaha di AntreIn dengan ringkasan tiket, waktu tunggu, dan kepuasan pelanggan',
                'items' => [
                    'Satu antrean untuk pelanggan dari ponsel dan yang datang langsung',
                    'Banyak loket, banyak staf, dan banyak cabang',
                    'Estimasi waktu tunggu dari riwayat pelayanan',
                    'Laporan harian yang bisa diunduh sebagai CSV atau PDF',
                ],
            ],
            [
                'title' => 'Untuk ruang tunggu',
                'image' => 'images/landing/app-display.jpg',
                'alt' => 'Tampilan layar antrean AntreIn di TV ruang tunggu, menampilkan nomor yang sedang dilayani per loket',
                'items' => [
                    'Layar antrean untuk TV atau monitor',
                    'Panggilan suara otomatis saat nomor dipanggil',
                    'Nomor yang sedang dilayani di setiap loket',
                ],
            ],
        ];

        $businessTypes = [
            [
                'title' => 'Barbershop & Salon',
                'image' => 'images/stores/barbershop.jpg',
                'alt' => 'Barbershop mitra AntreIn',
                'body' => 'Setiap layanan punya antrean sendiri, jadi pelanggan tidak perlu duduk berjam-jam di kursi tunggu.',
            ],
            [
                'title' => 'Klinik & Praktik Dokter',
                'image' => 'images/stores/klinik.jpg',
                'alt' => 'Klinik mitra AntreIn',
                'body' => 'Atur setiap poli sebagai layanan terpisah dan kurangi kepadatan pasien di ruang tunggu.',
            ],
            [
                'title' => 'Kuliner & Kafe',
                'image' => 'images/stores/kopi.jpg',
                'alt' => 'Kedai kopi mitra AntreIn',
                'body' => 'Pelanggan tidak perlu berkerumun di depan kasir saat jam makan siang ramai.',
            ],
            [
                'title' => 'Studio Foto & Jasa Lainnya',
                'image' => 'images/services/studio-foto.jpg',
                'alt' => 'Studio foto mitra AntreIn',
                'body' => 'Pelanggan tahu nomor yang sedang dikerjakan tanpa harus bertanya ke kasir.',
            ],
        ];

        /** Closing pair: both audiences get the same weight, copy length and button style. */
        $audiencePanels = [
            [
                'title' => 'Untuk pelanggan',
                'benefits' => [
                    'Tidak perlu memasang aplikasi, cukup peramban di ponsel.',
                    'Tunggu di mana saja, tidak harus berdiri di lokasi.',
                    'Diberi tahu saat giliran Anda sudah dekat.',
                ],
                'action' => $isCustomer
                    ? $workspaceLink
                    : ['label' => 'Cari Toko', 'url' => route('customer.dashboard')],
                'secondary' => $user === null
                    ? ['label' => 'Daftar Pelanggan', 'url' => route('register', ['type' => 'customer'])]
                    : null,
            ],
            [
                'title' => 'Untuk pemilik usaha',
                'benefits' => [
                    'Tidak perlu lagi mencatat dan memanggil antrean secara manual.',
                    'Cukup pakai ponsel, tablet, atau komputer kasir yang sudah ada.',
                    'Laporan semua cabang terkumpul di satu dasbor.',
                ],
                'action' => $isStaff
                    ? $workspaceLink
                    : ['label' => 'Daftarkan UMKM', 'url' => route('register', ['type' => 'merchant'])],
                'secondary' => null,
            ],
        ];

        $faqs = [
            [
                'question' => 'Apakah pelanggan harus masuk ke akun untuk mengambil antrean?',
                'answer' => 'Ya. Pelanggan cukup mendaftar dan masuk satu kali lewat peramban ponsel, tanpa mengunduh aplikasi. Dengan akun pelanggan, Anda bisa memegang hingga 3 antrean aktif dan menyimpan riwayat tiket.',
            ],
            [
                'question' => 'Bagaimana dengan pelanggan yang datang langsung tanpa ponsel?',
                'answer' => 'Operator membuatkan tiket dari meja loket dalam satu klik. Tiket itu masuk ke antrean yang sama dengan tiket dari ponsel dan dilayani sesuai urutan kedatangan.',
            ],
            [
                'question' => 'Bagaimana pelanggan tahu nomornya sedang dipanggil?',
                'answer' => 'Halaman tiket diperbarui otomatis. Pelanggan juga bisa menerima pemberitahuan lewat email atau peramban, dan layar antrean di lokasi menyebutkan nomor yang dipanggil dengan suara.',
            ],
            [
                'question' => 'Apakah satu usaha bisa punya beberapa loket dan staf?',
                'answer' => 'Bisa. Pemilik usaha membuat loket sebanyak yang dibutuhkan, lalu mengundang staf operator untuk menjalankan loket dan layar antrean. Usaha dengan beberapa cabang juga bisa dikelola dari satu akun pemilik.',
            ],
            [
                'question' => 'Bagaimana cara mendaftarkan usaha di AntreIn?',
                'answer' => 'Pilih "Daftarkan UMKM", isi nama dan email, lalu ikuti langkah pengaturan: profil usaha, jam buka, layanan, dan loket. Setelah itu admin AntreIn meninjau pendaftaran sebelum usaha Anda tampil untuk pelanggan.',
            ],
        ];

        $buttonBase = 'inline-flex min-h-11 items-center justify-center gap-2 rounded-full px-5 text-sm font-semibold transition-colors focus-visible:outline-2 focus-visible:outline-offset-2';
    @endphp

    {{-- Local/testing helper bar; never rendered in production. --}}
    @if (app()->environment(['local', 'testing']))
        <div class="border-b border-brand-800 bg-brand-900 px-4 py-2 text-xs text-brand-100">
            <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-2">
                <p class="flex items-center gap-2">
                    <span class="rounded border border-amber-300/30 bg-amber-400/20 px-1.5 py-0.5 text-xs font-bold text-amber-200">DEV MODE</span>
                    Panel akses cepat ada di halaman masuk, dan pengalih peran ada di pojok kanan bawah.
                </p>
                <div class="flex items-center gap-4 text-brand-200">
                    <a href="{{ route('login') }}" class="underline hover:text-white">Akses cepat masuk</a>
                    <a href="{{ route('customer.queue', 'barbershop-garasi-denpasar') }}" class="underline hover:text-white">Contoh toko</a>
                    <a href="{{ route('preview.design-system') }}" class="underline hover:text-white">Pratinjau design system</a>
                </div>
            </div>
        </div>
    @endif

    <header x-data="{ mobileMenuOpen: false }" class="sticky top-0 z-40 border-b border-neutral-200 bg-white text-neutral-900">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex h-16 items-center justify-between">
                <div class="flex items-center gap-8">
                    <a href="{{ route('home') }}" class="rounded-lg focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-brand-700" aria-label="AntreIn, ke beranda">
                        <x-brand-logo size="md" variant="light" />
                    </a>

                    <nav class="hidden items-center gap-6 lg:flex" aria-label="Navigasi halaman">
                        @foreach ($navLinks as $link)
                            <a href="{{ $link['href'] }}" class="text-sm font-medium text-neutral-600 transition-colors hover:text-brand-700">{{ $link['label'] }}</a>
                        @endforeach
                    </nav>
                </div>

                <div class="hidden items-center gap-2 sm:flex">
                    <a href="{{ route('customer.dashboard') }}" class="{{ $buttonBase }} text-brand-700 hover:bg-brand-50 focus-visible:outline-brand-700">
                        <x-customer.icon name="search" class="size-4" />
                        Cari Toko
                    </a>
                    @if ($workspaceLink)
                        <a href="{{ $workspaceLink['url'] }}" class="{{ $buttonBase }} border border-neutral-300 text-neutral-800 hover:bg-neutral-50 focus-visible:outline-brand-700">
                            {{ $workspaceLink['label'] }}
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="{{ $buttonBase }} border border-neutral-300 text-neutral-800 hover:bg-neutral-50 focus-visible:outline-brand-700">Masuk</a>
                    @endif
                </div>

                <div class="flex items-center gap-1.5 sm:hidden">
                    @if ($workspaceLink)
                        <a href="{{ $workspaceLink['url'] }}" class="inline-flex h-11 items-center rounded-full px-3 text-sm font-semibold text-brand-700">{{ $workspaceLink['label'] }}</a>
                    @else
                        <a href="{{ route('login') }}" class="inline-flex h-11 items-center rounded-full px-3 text-sm font-semibold text-brand-700">Masuk</a>
                    @endif
                    <button
                        type="button"
                        @click="mobileMenuOpen = !mobileMenuOpen"
                        class="flex size-11 items-center justify-center rounded-lg text-neutral-700 hover:bg-neutral-100 focus-visible:outline-2 focus-visible:outline-brand-700"
                        aria-label="Buka menu"
                        aria-controls="menu-mobile"
                        :aria-expanded="mobileMenuOpen.toString()"
                    >
                        <svg x-show="!mobileMenuOpen" class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                        <svg x-show="mobileMenuOpen" x-cloak class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <nav id="menu-mobile" x-show="mobileMenuOpen" x-collapse x-cloak class="space-y-1 border-t border-neutral-200 bg-white px-4 pt-2 pb-4 lg:hidden" aria-label="Navigasi halaman">
            @foreach ($navLinks as $link)
                <a href="{{ $link['href'] }}" @click="mobileMenuOpen = false" class="flex min-h-11 items-center text-base font-medium text-neutral-700 hover:text-brand-700">{{ $link['label'] }}</a>
            @endforeach
        </nav>
    </header>

    <main class="flex-1 bg-white">
        {{-- Hero: short photo band so the two doors and the ticket preview land in the first screen. --}}
        <section class="relative overflow-hidden bg-brand-800 text-white">
            <div class="relative h-36 w-full overflow-hidden sm:h-44 lg:h-52">
                <img
                    src="{{ asset('images/landing/hero.jpg') }}"
                    alt="Pemilik usaha dan pelanggan di sebuah UMKM yang memakai antrean digital AntreIn"
                    class="h-full w-full object-cover object-[center_30%]"
                />
                <div class="absolute inset-0 bg-linear-to-b from-brand-900/20 via-brand-800/30 to-brand-800"></div>
            </div>

            <div class="relative mx-auto -mt-8 max-w-7xl px-4 pb-16 sm:px-6 lg:-mt-12 lg:px-8">
                <div class="grid grid-cols-1 items-start gap-8 lg:grid-cols-12">
                    <div class="space-y-8 lg:col-span-7">
                        <div class="space-y-3">
                            <h1 class="text-4xl leading-tight font-bold tracking-tight text-balance sm:text-5xl">
                                Antrean Lebih Teratur, Pelayanan Lebih Lancar.
                            </h1>
                            <p class="max-w-xl text-base leading-relaxed text-pretty text-brand-100 sm:text-lg">
                                Satu antrean untuk pelanggan yang mendaftar dari ponsel dan yang datang langsung, sehingga UMKM melayani dengan urutan yang adil.
                            </p>
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div class="flex flex-col gap-4 rounded-2xl border border-white/20 bg-brand-900/50 p-5">
                                <div class="space-y-1.5">
                                    <h2 class="text-lg font-bold">Saya pelanggan</h2>
                                    <p class="text-sm leading-relaxed text-pretty text-brand-100">Ambil nomor dari ponsel dan pantau giliran tanpa menunggu di lokasi.</p>
                                </div>
                                <a href="{{ $isCustomer ? $workspaceLink['url'] : route('customer.dashboard') }}" class="{{ $buttonBase }} mt-auto w-full bg-white text-brand-800 hover:bg-brand-50 focus-visible:outline-white">
                                    {{ $isCustomer ? $workspaceLink['label'] : 'Cari Toko' }}
                                    <x-customer.icon name="arrow-right" class="size-4" />
                                </a>
                            </div>

                            <div class="flex flex-col gap-4 rounded-2xl border border-white/20 bg-brand-900/50 p-5">
                                <div class="space-y-1.5">
                                    <h2 class="text-lg font-bold">Saya pemilik usaha</h2>
                                    <p class="text-sm leading-relaxed text-pretty text-brand-100">Kelola loket, layanan, staf, dan layar antrean dari satu dasbor.</p>
                                </div>
                                <a href="{{ $isStaff ? $workspaceLink['url'] : route('register', ['type' => 'merchant']) }}" class="{{ $buttonBase }} mt-auto w-full bg-white text-brand-800 hover:bg-brand-50 focus-visible:outline-white">
                                    {{ $isStaff ? $workspaceLink['label'] : 'Daftarkan UMKM' }}
                                    <x-customer.icon name="arrow-right" class="size-4" />
                                </a>
                            </div>
                        </div>
                    </div>

                    {{-- Illustrative ticket; TKT-003 is also marked "Tiket Anda" in the fair-queue strip below. --}}
                    <figure class="flex justify-center lg:col-span-5 lg:justify-end lg:pt-10">
                        <figcaption class="sr-only">Contoh tiket antrean digital AntreIn</figcaption>
                        <div class="w-full max-w-sm space-y-5 rounded-3xl bg-white p-6 text-neutral-900 shadow-[0_24px_48px_-12px_rgba(15,23,42,0.45)]">
                            <div class="flex items-center justify-between gap-3 border-b border-neutral-100 pb-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex size-10 items-center justify-center rounded-xl bg-brand-700 text-sm font-bold text-white" aria-hidden="true">BG</div>
                                    <div>
                                        <p class="text-sm font-bold text-neutral-900">Barbershop Garasi</p>
                                        <span class="mt-1 inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-800 ring-1 ring-emerald-600/20 ring-inset">
                                            <x-customer.icon name="clock" class="size-3" />
                                            Buka s/d 21.00
                                        </span>
                                    </div>
                                </div>
                                <span class="text-xs font-semibold text-neutral-600">Contoh tiket</span>
                            </div>

                            <div class="space-y-1 rounded-2xl bg-brand-50 py-4 text-center">
                                <p class="text-xs font-semibold text-neutral-600">Nomor antrean Anda</p>
                                <p class="text-4xl font-bold text-brand-700 tabular-nums sm:text-5xl">TKT-003</p>
                                <p class="text-sm text-neutral-700">Potong Rambut Dewasa</p>
                            </div>

                            <dl class="grid grid-cols-2 divide-x divide-neutral-200 text-center">
                                <div class="px-2">
                                    <dt class="text-xs font-semibold text-neutral-600">Di depan Anda</dt>
                                    <dd class="mt-1 text-sm font-bold text-neutral-900 tabular-nums">2 orang</dd>
                                </div>
                                <div class="px-2">
                                    <dt class="text-xs font-semibold text-neutral-600">Estimasi tunggu</dt>
                                    <dd class="mt-1 text-sm font-bold text-brand-900 tabular-nums">20–30 menit</dd>
                                </div>
                            </dl>

                            <dl class="space-y-1.5 text-sm">
                                <div class="flex justify-between gap-3">
                                    <dt class="text-neutral-600">Loket</dt>
                                    <dd class="font-semibold text-neutral-900">Loket 1</dd>
                                </div>
                                <div class="flex justify-between gap-3">
                                    <dt class="text-neutral-600">Perkiraan dipanggil</dt>
                                    <dd class="font-semibold text-brand-700 tabular-nums">10.20–10.30 WITA</dd>
                                </div>
                            </dl>

                            <p class="flex items-center gap-1.5 border-t border-neutral-100 pt-3 text-xs font-medium text-emerald-800">
                                <x-customer.icon name="refresh" class="size-4" />
                                Posisi diperbarui otomatis
                            </p>
                        </div>
                    </figure>
                </div>
            </div>
        </section>

        {{-- Cara kerja + the one-queue strip that shows fairness instead of claiming it. --}}
        <section id="cara-kerja" class="scroll-mt-20 py-20">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="max-w-2xl space-y-3">
                    <h2 class="text-3xl font-bold tracking-tight text-balance text-neutral-950 sm:text-4xl">Cara kerja AntreIn</h2>
                    <p class="text-base leading-relaxed text-pretty text-neutral-600 sm:text-lg">
                        Pelanggan mengambil nomor dari ponsel tanpa memasang aplikasi. Staf mengelola semua antrean dari satu dasbor.
                    </p>
                </div>

                <ol class="mt-12 grid grid-cols-1 gap-x-8 gap-y-10 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($steps as $step)
                        <li class="space-y-2 border-t-2 border-brand-700 pt-5">
                            <p class="text-sm font-semibold text-brand-700 tabular-nums" aria-hidden="true">Langkah {{ $loop->iteration }}</p>
                            <h3 class="text-lg font-bold text-neutral-900">{{ $step['title'] }}</h3>
                            <p class="text-sm leading-relaxed text-pretty text-neutral-600">{{ $step['body'] }}</p>
                        </li>
                    @endforeach
                </ol>

                <div class="mt-16 grid grid-cols-1 items-center gap-8 rounded-3xl bg-neutral-50 p-6 ring-1 ring-neutral-200 sm:p-10 lg:grid-cols-12">
                    <div class="space-y-3 lg:col-span-5">
                        <h3 class="text-2xl font-bold tracking-tight text-balance text-neutral-950">Satu antrean, urutan yang adil</h3>
                        <p class="text-base leading-relaxed text-pretty text-neutral-600">
                            Pelanggan yang datang tanpa ponsel tetap dibuatkan tiket oleh operator. Tiket dari ponsel dan tiket di lokasi masuk ke antrean yang sama dan dilayani sesuai urutan kedatangan.
                        </p>
                    </div>

                    <ol class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:col-span-7" aria-label="Contoh urutan antrean gabungan">
                        @foreach ($fairQueue as $ticket)
                            @php
                                $isCalled = $ticket['state'] === 'Dipanggil';
                                $isVisitor = $ticket['state'] === 'Tiket Anda';
                            @endphp
                            <li @class([
                                'flex items-center justify-between gap-3 rounded-2xl p-4',
                                'bg-brand-700 text-white shadow-[0_8px_20px_-8px_rgba(30,64,175,0.6)]' => $isCalled,
                                'bg-white text-neutral-900 ring-2 ring-brand-700' => $isVisitor,
                                'bg-white text-neutral-900 ring-1 ring-neutral-200' => ! $isCalled && ! $isVisitor,
                            ])>
                                <div>
                                    <p class="text-lg font-bold tabular-nums">{{ $ticket['number'] }}</p>
                                    <p @class(['flex items-center gap-1.5 text-sm', 'text-brand-100' => $isCalled, 'text-neutral-600' => ! $isCalled])>
                                        <x-customer.icon :name="$ticket['icon']" class="size-4" />
                                        {{ $ticket['source'] }}
                                    </p>
                                </div>
                                <span @class([
                                    'rounded-full px-2.5 py-1 text-xs font-semibold',
                                    'bg-white text-brand-800' => $isCalled,
                                    'bg-brand-700 text-white' => $isVisitor,
                                    'bg-brand-50 text-brand-800' => $ticket['state'] === 'Berikutnya',
                                    'bg-neutral-100 text-neutral-700' => $ticket['state'] === 'Menunggu',
                                ])>{{ $ticket['state'] }}</span>
                            </li>
                        @endforeach
                    </ol>
                </div>
            </div>
        </section>

        <section id="fitur" class="scroll-mt-20 border-t border-neutral-200 bg-neutral-50 py-20">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="max-w-2xl space-y-3">
                    <h2 class="text-3xl font-bold tracking-tight text-balance text-neutral-950 sm:text-4xl">Fitur untuk setiap sisi antrean</h2>
                    <p class="text-base leading-relaxed text-pretty text-neutral-600 sm:text-lg">
                        Dari ponsel pelanggan, meja loket, sampai layar di ruang tunggu, semuanya menampilkan antrean yang sama.
                    </p>
                </div>

                <div class="mt-12 grid grid-cols-1 gap-8 md:grid-cols-3">
                    @foreach ($featureGroups as $group)
                        <div class="space-y-5">
                            <div class="aspect-video overflow-hidden rounded-2xl border border-neutral-200 bg-neutral-50 shadow-card sm:aspect-4/3">
                                <img src="{{ asset($group['image']) }}" alt="{{ $group['alt'] }}" class="h-full w-full object-cover object-left-top" loading="lazy" />
                            </div>
                            <h3 class="text-xl font-bold text-neutral-900">{{ $group['title'] }}</h3>
                            <ul class="space-y-3">
                                @foreach ($group['items'] as $item)
                                    <li class="flex gap-3 text-sm leading-relaxed text-neutral-700">
                                        <x-customer.icon name="check" class="mt-0.5 size-4 text-brand-700" />
                                        {{ $item }}
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <section id="untuk-umkm" class="scroll-mt-20 border-t border-neutral-200 py-20">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="max-w-2xl space-y-3">
                    <h2 class="text-3xl font-bold tracking-tight text-balance text-neutral-950 sm:text-4xl">Cocok untuk berbagai jenis usaha</h2>
                    <p class="text-base leading-relaxed text-pretty text-neutral-600 sm:text-lg">
                        Dari barbershop sampai klinik, setiap UMKM bisa menjalankan antrean digital tanpa membeli perangkat tambahan.
                    </p>
                </div>

                <div class="mt-12 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($businessTypes as $type)
                        <div class="space-y-3">
                            <div class="aspect-video overflow-hidden rounded-2xl border border-neutral-200 bg-neutral-50 shadow-card sm:aspect-4/3">
                                <img src="{{ asset($type['image']) }}" alt="{{ $type['alt'] }}" class="h-full w-full object-cover" loading="lazy" />
                            </div>
                            <h3 class="text-lg font-bold text-neutral-900">{{ $type['title'] }}</h3>
                            <p class="text-sm leading-relaxed text-pretty text-neutral-600">{{ $type['body'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <section id="pertanyaan" class="scroll-mt-20 border-t border-neutral-200 bg-neutral-50 py-20">
            <div class="mx-auto max-w-3xl space-y-10 px-4 sm:px-6 lg:px-8">
                <h2 class="text-3xl font-bold tracking-tight text-balance text-neutral-950 sm:text-4xl">Pertanyaan yang sering diajukan</h2>

                <div class="divide-y divide-neutral-200 border-y border-neutral-200" x-data="{ active: 1 }">
                    @foreach ($faqs as $faq)
                        @php($faqId = 'pertanyaan-'.$loop->iteration)
                        <div>
                            <h3>
                                <button
                                    type="button"
                                    id="{{ $faqId }}-tombol"
                                    @click="active = (active === {{ $loop->iteration }} ? null : {{ $loop->iteration }})"
                                    :aria-expanded="(active === {{ $loop->iteration }}).toString()"
                                    aria-controls="{{ $faqId }}-jawaban"
                                    class="flex w-full items-center justify-between gap-4 rounded-lg py-5 text-left text-base font-semibold text-neutral-900 hover:text-brand-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-700"
                                >
                                    <span>{{ $faq['question'] }}</span>
                                    <svg class="size-5 shrink-0 transition-transform duration-200" :class="{ 'rotate-180': active === {{ $loop->iteration }} }" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>
                            </h3>
                            <div
                                id="{{ $faqId }}-jawaban"
                                role="region"
                                aria-labelledby="{{ $faqId }}-tombol"
                                x-show="active === {{ $loop->iteration }}"
                                x-collapse
                                @unless ($loop->first) x-cloak @endunless
                                class="pb-5 text-base leading-relaxed text-neutral-600"
                            >
                                {{ $faq['answer'] }}
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- Closing: two equal panels, one per audience. --}}
        <section class="bg-brand-800 py-16 text-white" aria-labelledby="mulai-judul">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <h2 id="mulai-judul" class="text-3xl font-bold tracking-tight text-balance sm:text-4xl">Mulai antrean yang lebih teratur hari ini</h2>

                <div class="mt-10 grid grid-cols-1 gap-6 md:grid-cols-2">
                    @foreach ($audiencePanels as $panel)
                        <div class="flex flex-col gap-6 rounded-2xl border border-white/20 bg-brand-900/50 p-6 sm:p-8">
                            <h3 class="text-xl font-bold">{{ $panel['title'] }}</h3>
                            <ul class="space-y-3">
                                @foreach ($panel['benefits'] as $benefit)
                                    <li class="flex gap-3 text-sm leading-relaxed text-brand-100">
                                        <x-customer.icon name="check" class="mt-0.5 size-4 text-white" />
                                        {{ $benefit }}
                                    </li>
                                @endforeach
                            </ul>
                            @if ($panel['action'])
                                <div class="mt-auto flex flex-wrap items-center gap-x-5 gap-y-2">
                                    <a href="{{ $panel['action']['url'] }}" class="{{ $buttonBase }} h-12 bg-white px-6 text-base text-brand-800 hover:bg-brand-50 focus-visible:outline-white">
                                        {{ $panel['action']['label'] }}
                                        <x-customer.icon name="arrow-right" class="size-4" />
                                    </a>
                                    @if ($panel['secondary'])
                                        <a href="{{ $panel['secondary']['url'] }}" class="inline-flex min-h-11 items-center text-sm font-semibold text-white underline-offset-4 hover:underline">{{ $panel['secondary']['label'] }}</a>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    </main>

    <footer class="border-t border-neutral-200 bg-white py-12 text-sm text-neutral-600">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mb-8 grid grid-cols-1 gap-8 md:grid-cols-4">
                <div class="space-y-4 md:col-span-2">
                    <x-brand-logo size="sm" />
                    <p class="max-w-sm leading-relaxed text-pretty">
                        AntreIn membantu UMKM mengelola antrean dari ponsel dan antrean di lokasi dalam satu sistem, sehingga pelayanan lebih teratur.
                    </p>
                </div>

                <nav class="space-y-3" aria-label="Navigasi kaki halaman">
                    <p class="font-semibold text-neutral-900">Navigasi</p>
                    <ul class="space-y-2">
                        @foreach ($navLinks as $link)
                            <li><a href="{{ $link['href'] }}" class="hover:text-brand-700">{{ $link['label'] }}</a></li>
                        @endforeach
                    </ul>
                </nav>

                <div class="space-y-3">
                    <p class="font-semibold text-neutral-900">Akses</p>
                    <ul class="space-y-2">
                        <li><a href="{{ route('customer.dashboard') }}" class="hover:text-brand-700">Cari Toko</a></li>
                        @guest
                            <li><a href="{{ route('login') }}" class="hover:text-brand-700">Masuk</a></li>
                            <li><a href="{{ route('register', ['type' => 'customer']) }}" class="hover:text-brand-700">Daftar Pelanggan</a></li>
                            <li><a href="{{ route('register', ['type' => 'merchant']) }}" class="hover:text-brand-700">Daftarkan UMKM</a></li>
                        @endguest
                    </ul>
                </div>
            </div>

            <div class="flex flex-col items-center justify-between gap-3 border-t border-neutral-100 pt-8 text-xs text-neutral-600 sm:flex-row">
                <p>&copy; {{ date('Y') }} AntreIn. Hak cipta dilindungi undang-undang.</p>
                <p>Powered by AntreIn — Platform Antrean Digital UMKM</p>
            </div>
        </div>
    </footer>
</x-layouts.base>
