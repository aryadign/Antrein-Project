<x-layouts.customer title="Beranda" activeNav="beranda">
    @php
        $firstName = $customerName ? Str::before($customerName, ' ') : null;
        $openStoreCount = $stores->filter(fn ($store) => $store->isOpen())->count();
    @endphp

    <div x-data="{ qrOpen: false }">
        {{-- Sapaan & pencarian terpadu (Screen 1) --}}
        <div class="border-b border-gray-200 bg-white px-5 pt-6 pb-4 md:border-0 md:bg-transparent md:px-0 md:pt-2 md:pb-6">
            <div class="flex flex-col gap-1">
                <h1 class="text-2xl font-extrabold tracking-tight leading-8 text-gray-900 md:text-3xl md:leading-10">Halo{{ $firstName ? ', '.$firstName : '' }}</h1>
                <p class="text-sm leading-5 text-pretty text-gray-600">Ambil nomor antrean dari ponsel, datang saat giliran Anda.</p>
            </div>

            <div class="mt-3.5 flex h-12 items-center gap-2 rounded-xl border-[1.5px] border-gray-300 bg-gray-50/90 pr-1.5 pl-3.5 shadow-xs transition focus-within:border-brand-700 focus-within:bg-white focus-within:ring-2 focus-within:ring-brand-100 lg:max-w-2xl">
                <form action="{{ route('customer.search') }}" method="GET" role="search" class="flex min-w-0 flex-1 items-center gap-2.5">
                    <x-customer.icon name="search" class="size-4.5 text-gray-500" />
                    <label for="home-search-input" class="sr-only">Cari toko atau layanan</label>
                    <input
                        id="home-search-input"
                        type="search"
                        name="q"
                        placeholder="Cari kedai, klinik, atau jasa"
                        class="w-full min-w-0 border-0 bg-transparent p-0 text-sm leading-5 text-gray-900 placeholder:text-gray-500 focus:outline-none focus:ring-0"
                    />
                </form>
                <button
                    type="button"
                    @click="qrOpen = true"
                    class="inline-flex h-9 shrink-0 items-center gap-1.5 rounded-lg bg-brand-700 px-3 text-xs font-bold text-white shadow-xs transition hover:bg-blue-900 active:scale-95"
                    aria-label="Pindai QR kedai"
                >
                    <img src="{{ asset('images/icons/qr-code.svg') }}" alt="" width="16" height="16" class="size-4 brightness-0 invert" />
                    <span>Pindai QR</span>
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 px-5 pt-4 pb-5 md:px-0 md:pt-0 lg:grid-cols-12 lg:items-start lg:gap-8">
            {{-- Panel kiri: status pribadi & kategori --}}
            <aside class="flex min-w-0 flex-col gap-5 lg:sticky lg:top-24 lg:col-span-4">
                @if ($activeTicket)
                    <section class="flex flex-col gap-2.5" aria-label="Antrean aktif Anda">
                        <div class="flex items-center justify-between gap-2">
                            <h2 class="text-base font-bold leading-6 text-gray-900">Antrean aktif Anda</h2>
                            <a href="{{ route('customer.my-tickets') }}" class="inline-flex min-h-9 items-center gap-1 text-sm font-semibold text-brand-700 hover:underline">
                                Antrean Saya ({{ $activeTicketCount ?? 1 }} dari 3)
                            </a>
                        </div>
                        <x-customer.ticket-card :ticket="$activeTicket" compact heading-id="active-ticket-title" />
                    </section>
                @elseif ($isFirstVisit)
                    <section class="flex flex-col gap-3.5 rounded-2xl border-[1.5px] border-brand-200 bg-linear-to-b from-brand-50 to-white p-4 shadow-xs" aria-labelledby="how-title">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex flex-col gap-0.5">
                                <h2 id="how-title" class="text-base font-extrabold leading-6 text-gray-900">Cara pakai AntreIn</h2>
                                <p class="text-xs leading-4 text-gray-600">3 langkah mudah mengambil hingga 3 antrean aktif.</p>
                            </div>
                            <span class="shrink-0 rounded-full bg-brand-100 px-2.5 py-1 text-xs font-extrabold text-brand-700">Maks 3 Tiket</span>
                        </div>

                        <x-customer.how-it-works />

                        @guest
                            <div class="grid grid-cols-2 gap-2">
                                <a href="{{ route('login') }}" class="flex h-10 items-center justify-center rounded-xl bg-brand-700 px-3 text-xs font-bold text-white shadow-xs transition hover:bg-blue-900 active:scale-[0.99]">
                                    Masuk Pelanggan
                                </a>
                                <a href="{{ route('register', ['type' => 'customer']) }}" class="flex h-10 items-center justify-center rounded-xl border-[1.5px] border-brand-200 bg-white px-3 text-xs font-bold text-brand-700 transition hover:bg-brand-50 active:scale-[0.99]">
                                    Daftar Pelanggan
                                </a>
                            </div>
                        @else
                            <div class="grid grid-cols-2 gap-2">
                                <button type="button" @click="qrOpen = true" class="flex h-10 items-center justify-center rounded-xl bg-brand-700 px-3 text-xs font-bold text-white shadow-xs transition hover:bg-blue-900 active:scale-[0.99]">
                                    Pindai QR Kedai
                                </button>
                                <a href="#toko" class="flex h-10 items-center justify-center rounded-xl border-[1.5px] border-gray-300 bg-white px-3 text-xs font-bold text-gray-900 transition hover:bg-gray-50 active:scale-[0.99]">
                                    Lihat Semua Toko
                                </a>
                            </div>
                        @endguest
                    </section>
                @else
                    <section class="flex flex-col gap-3.5 rounded-2xl border-[1.5px] border-brand-200 bg-linear-to-b from-brand-50/70 to-white p-4 shadow-xs" aria-labelledby="active-ticket-title">
                        <div class="flex items-center justify-between gap-3">
                            <div class="flex items-center gap-3 min-w-0">
                                <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-white text-brand-700 ring-1 ring-brand-200">
                                    <x-customer.icon name="ticket" class="size-5" />
                                </span>
                                <div class="flex min-w-0 flex-col">
                                    <h2 id="active-ticket-title" class="text-sm font-extrabold leading-5 text-gray-900">Belum ada antrean aktif</h2>
                                    <p class="text-xs leading-4 text-gray-600">Ambil nomor dalam 3 langkah mudah.</p>
                                </div>
                            </div>
                            @auth
                                <span class="shrink-0 rounded-full bg-brand-100 px-2.5 py-0.5 text-xs font-bold text-brand-700 tabular-nums">{{ $activeTicketCount ?? 0 }}/3 aktif</span>
                            @endauth
                        </div>
                        <x-customer.how-it-works class="border-t border-brand-100 pt-3" />
                    </section>

                    <section class="flex flex-col gap-3" aria-labelledby="featured-title">
                        <h2 id="featured-title" class="text-base font-extrabold leading-6 text-gray-900">Kunjungi lagi</h2>
                        <a href="{{ route('customer.queue', $featuredStore->slug) }}" class="group overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm transition hover:shadow-md">
                            <x-customer.store-cover :business="$featuredStore" :show-category="false">
                                <span class="absolute top-3 left-3 rounded-full bg-white/95 px-2.5 py-1 text-xs font-bold tracking-wide text-brand-700 shadow-sm">KEDAI PILIHAN ANDA</span>
                            </x-customer.store-cover>
                            <div class="flex flex-col gap-2.5 p-4">
                                <p class="text-lg font-extrabold leading-6 text-gray-900">{{ $featuredStore->name }}</p>
                                <x-customer.hours-badge :business="$featuredStore" />
                                <span class="inline-flex items-center gap-1 text-sm font-bold text-brand-700">
                                    Antre lagi di sini
                                    <x-customer.icon name="arrow-right" class="size-4 transition-transform group-hover:translate-x-0.5" />
                                </span>
                            </div>
                        </a>
                    </section>
                @endif

                {{-- Kategori (responsive grid penuh tanpa scroll-clipping) --}}
                <section class="flex flex-col gap-3" aria-labelledby="category-title">
                    <div class="flex items-center justify-between gap-2">
                        <h2 id="category-title" class="text-base font-extrabold leading-6 text-gray-900">Mau antre di mana?</h2>
                        <a href="{{ route('customer.search') }}" class="inline-flex items-center gap-1 rounded-full border border-brand-200 bg-brand-50 px-3 py-1 text-xs font-bold text-brand-700 transition hover:bg-brand-100">
                            Semua toko
                            <x-customer.icon name="arrow-right" class="size-3" />
                        </a>
                    </div>
                    <div class="grid grid-cols-3 gap-2.5 sm:grid-cols-4 md:grid-cols-6 lg:grid-cols-2">
                        <a href="{{ route('customer.search') }}" class="flex min-w-0 flex-col items-center justify-center gap-1.5 rounded-xl border border-gray-200 bg-white p-3 shadow-card transition duration-200 ease-out hover:border-brand-300 hover:bg-brand-50/40 active:scale-[0.97] lg:flex-row lg:justify-start lg:gap-2.5">
                            <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-brand-50"><span class="size-5 bg-brand-700" style="mask: url('{{ asset('images/figma/layout-grid.svg') }}') center / contain no-repeat; -webkit-mask: url('{{ asset('images/figma/layout-grid.svg') }}') center / contain no-repeat;" aria-hidden="true"></span></span>
                            <span class="flex min-w-0 flex-col items-center text-center lg:items-start lg:text-left">
                                <span class="text-xs font-bold leading-tight text-gray-900">Semua</span>
                                <span class="text-xs leading-tight text-gray-600">{{ $storeCount }} toko</span>
                            </span>
                        </a>
                        @foreach ($categories as $category)
                            @php
                                ['background' => $categoryBackground, 'tint' => $categoryTint] = $category->customerPalette();
                            @endphp
                            <a href="{{ route('customer.search', ['kategori' => $category->slug]) }}" class="flex min-w-0 flex-col items-center justify-center gap-1.5 rounded-xl border border-gray-200 bg-white p-3 shadow-card transition duration-200 ease-out hover:border-brand-300 hover:bg-brand-50/40 active:scale-[0.97] lg:flex-row lg:justify-start lg:gap-2.5">
                                <span class="flex size-9 shrink-0 items-center justify-center rounded-lg {{ $categoryBackground }}"><span class="size-5 {{ $categoryTint }}" style="mask: url('{{ asset($category->iconPath()) }}') center / contain no-repeat; -webkit-mask: url('{{ asset($category->iconPath()) }}') center / contain no-repeat;" aria-hidden="true"></span></span>
                                <span class="flex min-w-0 w-full flex-col items-center text-center lg:items-start lg:text-left">
                                    <span class="w-full wrap-break-word text-xs font-bold leading-tight text-gray-900">{{ $category->shortName() }}</span>
                                    <span class="text-xs leading-tight text-gray-600">{{ $category->stores_count > 0 ? $category->stores_count.' toko' : 'Segera hadir' }}</span>
                                </span>
                            </a>
                        @endforeach
                    </div>
                </section>
            </aside>

            {{-- Panel kanan: daftar toko --}}
            <section id="toko" class="flex min-w-0 scroll-mt-24 flex-col gap-3.5 lg:col-span-8" aria-labelledby="stores-title">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex flex-col">
                        <h2 id="stores-title" class="text-base font-extrabold leading-6 text-gray-900 md:text-lg">Toko di AntreIn</h2>
                        @if ($openStoreCount > 0)
                            <p class="text-xs leading-4 text-gray-600">{{ $openStoreCount }} toko sedang buka</p>
                        @endif
                    </div>
                    @if ($storeCount > $stores->count())
                        <a href="{{ route('customer.search') }}" class="inline-flex shrink-0 items-center gap-1 rounded-full border border-brand-200 bg-brand-50 px-3 py-1.5 text-xs font-bold text-brand-700 transition hover:bg-brand-100">
                            Lihat semua ({{ $storeCount }})
                            <x-customer.icon name="arrow-right" class="size-3.5" />
                        </a>
                    @endif
                </div>
                @if ($stores->isEmpty())
                    <div class="rounded-2xl border border-dashed border-gray-300 bg-white px-5 py-10 text-center text-sm leading-5 text-gray-600">Belum ada toko yang bergabung.</div>
                @else
                    <div class="grid grid-cols-1 gap-3.5 sm:grid-cols-2 md:gap-4">
                        @foreach ($stores as $store)
                            <x-customer.store-card :business="$store" />
                        @endforeach
                    </div>
                @endif
            </section>
        </div>

        {{-- Pindai QR: QR kedai langsung membuka halaman antrean toko saat dipindai kamera ponsel --}}
        <div
            x-show="qrOpen"
            x-cloak
            x-transition.opacity.duration.200ms
            class="fixed inset-0 z-50 flex items-end justify-center bg-black/50 sm:items-center"
            @keydown.escape.window="qrOpen = false"
        >
            <div
                x-show="qrOpen"
                x-transition:enter="transition duration-200 ease-out"
                x-transition:enter-start="translate-y-6 opacity-0"
                x-transition:enter-end="translate-y-0 opacity-100"
                @click.outside="qrOpen = false"
                class="w-full max-w-[430px] rounded-t-2xl bg-white p-5 sm:rounded-2xl"
                role="dialog"
                aria-modal="true"
                aria-labelledby="qr-title"
            >
                <p id="qr-title" class="text-base font-bold leading-6 text-gray-900">Pindai QR Kedai</p>
                <p class="mt-1 text-sm leading-5 text-gray-700">Buka kamera ponsel dan arahkan ke QR yang terpasang di kedai. Halaman antrean kedai akan terbuka otomatis.</p>
                <button type="button" @click="qrOpen = false" class="mt-4 flex h-12 w-full items-center justify-center rounded-xl bg-brand-700 text-sm font-semibold text-white transition hover:bg-blue-900">
                    Mengerti
                </button>
            </div>
        </div>
    </div>
</x-layouts.customer>
