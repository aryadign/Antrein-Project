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
                <p class="text-sm leading-5 text-gray-600">Ambil nomor antrean dari HP, datang pas giliran Anda.</p>
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
                    <span>Scan QR</span>
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 px-5 pt-4 pb-5 md:px-0 md:pt-0 lg:grid-cols-12 lg:items-start lg:gap-8">
            {{-- Panel kiri: status pribadi & kategori --}}
            <aside class="flex min-w-0 flex-col gap-5 lg:sticky lg:top-24 lg:col-span-4">
                @if ($activeTicket)
                    @php
                        $peopleAhead = max(0, (int) ($activeTicket->queue_position ?? 0) - 1);
                        $scheduleEstimate = $activeTicket->schedule_estimate ?? [];
                        $serveTimeLabel = data_get($scheduleEstimate, 'estimated_serve_time_label');
                        $waitRangeLabel = data_get($scheduleEstimate, 'wait_range_label');
                        $hasDelayShift = (bool) data_get($scheduleEstimate, 'has_delay_shift');
                        $shiftNotice = data_get($scheduleEstimate, 'shift_notice');
                        $bookingSlotLabel = $activeTicket->bookingSlotWindowLabel();
                        ['label' => $storeHoursLabel] = $activeTicket->business->customerHoursBadge();
                        [$ticketStatusLabel, $ticketStatusClass] = match ($activeTicket->status) {
                            \App\Models\QueueTicket::STATUS_DIPANGGIL => ['Dipanggil', 'bg-white text-brand-700'],
                            \App\Models\QueueTicket::STATUS_SEDANG_DILAYANI => ['Sedang dilayani', 'bg-emerald-200 text-emerald-950'],
                            default => ['Menunggu', 'bg-amber-300 text-slate-900'],
                        };
                    @endphp
                    <section class="overflow-hidden rounded-2xl border border-brand-600/30 bg-white shadow-md" aria-labelledby="active-ticket-title">
                        <div class="flex items-center justify-between gap-3 bg-linear-to-r from-brand-800 to-brand-600 px-4 py-3 text-white">
                            <div class="flex min-w-0 flex-col">
                                <h2 id="active-ticket-title" class="text-[11px] font-bold tracking-wider text-brand-100 uppercase">Antrean aktif Anda ({{ $activeTicketCount ?? 1 }}/3)</h2>
                                <p class="truncate text-xs font-bold leading-4 text-white">{{ $storeHoursLabel }}</p>
                            </div>
                            <span class="shrink-0 rounded-full px-2.5 py-1 text-[11px] font-extrabold {{ $ticketStatusClass }}">{{ $ticketStatusLabel }}</span>
                        </div>

                        <div class="relative p-4">
                            <span class="pointer-events-none absolute -top-2 -left-2 size-4 rounded-full bg-gray-100 md:bg-white" aria-hidden="true"></span>
                            <span class="pointer-events-none absolute -top-2 -right-2 size-4 rounded-full bg-gray-100 md:bg-white" aria-hidden="true"></span>

                            <div class="flex items-center justify-between gap-3">
                                <div class="flex shrink-0 flex-col items-center rounded-xl border border-brand-200 bg-brand-50 px-3.5 py-2 text-center">
                                    <span class="text-[9px] font-bold tracking-wider text-brand-700/80 uppercase">Tiket</span>
                                    <span class="text-2xl font-extrabold leading-none text-brand-700 tabular-nums">{{ $activeTicket->ticket_number }}</span>
                                </div>

                                <div class="flex min-w-0 flex-1 flex-col gap-0.5">
                                    <p class="truncate text-sm font-extrabold leading-5 text-gray-900">{{ $activeTicket->business->name }}</p>
                                    <p class="truncate text-xs leading-4 text-gray-600">
                                        {{ $activeTicket->service?->name ?? 'Layanan' }}
                                    </p>
                                    @if ($bookingSlotLabel)
                                        <p class="text-[11px] font-bold text-brand-700 tabular-nums">Slot {{ $bookingSlotLabel }}</p>
                                    @endif
                                </div>

                                <a href="{{ route('customer.queue.track', $activeTicket->access_token) }}" class="inline-flex shrink-0 items-center gap-1 rounded-xl bg-brand-700 px-3.5 py-2 text-xs font-bold text-white shadow-xs transition hover:bg-blue-900 active:scale-[0.98]">
                                    Pantau
                                    <x-customer.icon name="arrow-right" class="size-3.5" />
                                </a>
                            </div>

                            @if ($activeTicket->status === \App\Models\QueueTicket::STATUS_MENUNGGU)
                                <div class="mt-3 flex flex-col gap-1.5 border-t border-dashed border-gray-200 pt-2.5 text-xs">
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="font-bold text-gray-900">Posisi #{{ $activeTicket->queue_position }} ({{ $peopleAhead > 0 ? $peopleAhead.' orang di depan' : 'Giliran berikutnya' }})</span>
                                        <span class="font-extrabold text-brand-700 tabular-nums">{{ $serveTimeLabel ? 'Est. '.$serveTimeLabel : ($waitRangeLabel ?? 'Segera') }}</span>
                                    </div>
                                    @if ($hasDelayShift && $shiftNotice)
                                        <p class="rounded-lg border border-amber-200 bg-amber-50 px-2.5 py-1.5 text-[11px] leading-4 font-medium text-amber-900">{{ $shiftNotice }}</p>
                                    @endif
                                </div>
                            @endif

                            <div class="mt-3 flex items-center justify-between border-t border-gray-100 pt-2.5">
                                <span class="truncate text-xs text-gray-500">{{ $activeTicket->business->areaLabel() ?? 'Layanan' }}</span>
                                <a href="{{ route('customer.my-tickets') }}" class="shrink-0 text-xs font-bold text-brand-700 hover:underline">Semua tiket</a>
                            </div>
                        </div>
                    </section>
                @elseif ($isFirstVisit)
                    <section class="flex flex-col gap-3.5 rounded-2xl border-[1.5px] border-brand-200 bg-linear-to-b from-brand-50 to-white p-4 shadow-xs" aria-labelledby="how-title">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex flex-col gap-0.5">
                                <h2 id="how-title" class="text-base font-extrabold leading-6 text-gray-900">Cara pakai AntreIn</h2>
                                <p class="text-xs leading-4 text-gray-600">3 langkah mudah mengambil hingga 3 antrean aktif.</p>
                            </div>
                            <span class="shrink-0 rounded-full bg-brand-100 px-2.5 py-1 text-[11px] font-extrabold text-brand-700">Maks 3 Tiket</span>
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
                                <span class="shrink-0 rounded-full bg-brand-100 px-2.5 py-0.5 text-[11px] font-bold text-brand-700 tabular-nums">{{ $activeTicketCount ?? 0 }}/3 aktif</span>
                            @endauth
                        </div>
                        <x-customer.how-it-works class="border-t border-brand-100 pt-3" />
                    </section>

                    <section class="flex flex-col gap-3" aria-labelledby="featured-title">
                        <h2 id="featured-title" class="text-base font-extrabold leading-6 text-gray-900">Kunjungi lagi</h2>
                        <a href="{{ route('customer.queue', $featuredStore->slug) }}" class="group overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm transition hover:shadow-md">
                            <x-customer.store-cover :business="$featuredStore" :show-category="false">
                                <span class="absolute top-3 left-3 rounded-full bg-white/95 px-2.5 py-1 text-[11px] font-bold tracking-wide text-brand-700 shadow-sm">KEDAI PILIHAN ANDA</span>
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
                        <a href="{{ route('customer.search') }}" class="flex min-w-0 flex-col items-center justify-center gap-1.5 rounded-2xl border border-brand-700 bg-brand-700 p-3 text-white shadow-xs transition hover:bg-blue-900 active:scale-[0.97] lg:flex-row lg:justify-start lg:gap-2.5">
                            <span class="size-6 shrink-0 bg-white" style="mask: url('{{ asset('images/figma/layout-grid.svg') }}') center / contain no-repeat; -webkit-mask: url('{{ asset('images/figma/layout-grid.svg') }}') center / contain no-repeat;" aria-hidden="true"></span>
                            <span class="flex min-w-0 flex-col items-center text-center lg:items-start lg:text-left">
                                <span class="text-xs font-bold leading-tight">Semua</span>
                                <span class="text-[10px] leading-tight text-white/85">{{ $storeCount }} toko</span>
                            </span>
                        </a>
                        @foreach ($categories as $category)
                            @php
                                ['background' => $categoryBackground, 'border' => $categoryBorder, 'tint' => $categoryTint] = $category->customerPalette();
                            @endphp
                            <a href="{{ route('customer.search', ['kategori' => $category->slug]) }}" class="flex min-w-0 flex-col items-center justify-center gap-1.5 rounded-2xl border p-3 shadow-xs transition hover:brightness-95 active:scale-[0.97] lg:flex-row lg:justify-start lg:gap-2.5 {{ $categoryBackground }} {{ $categoryBorder }}">
                                <span class="size-6 shrink-0 {{ $categoryTint }}" style="mask: url('{{ asset($category->iconPath()) }}') center / contain no-repeat; -webkit-mask: url('{{ asset($category->iconPath()) }}') center / contain no-repeat;" aria-hidden="true"></span>
                                <span class="flex min-w-0 w-full flex-col items-center text-center lg:items-start lg:text-left">
                                    <span class="w-full wrap-break-word text-xs font-bold leading-tight text-gray-900">{{ $category->shortName() }}</span>
                                    <span class="text-[10px] leading-tight text-gray-600">{{ $category->stores_count > 0 ? $category->stores_count.' toko' : 'Segera hadir' }}</span>
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
