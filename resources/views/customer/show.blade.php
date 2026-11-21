{{-- Figma: Konsumen, Jelajahi Layanan (1:1254) --}}
@php
    $canTakeQueue = $business->canAcceptOnlineQueue();
    $scheduleLabel = $business->todayScheduleLabel();
    $rating = $business->customerRating();
    $reviewCount = $business->customerReviewCount();
    $waitRange = $waitRange ?? $business->customerWaitRange($waitingCount);
    $availableServiceCount = $services->reject(fn ($service) => $service->is_quota_full)->count();

    $category = $business->category;
    ['text' => $initialsColor] = $category?->customerPalette() ?? \App\Models\BusinessCategory::CUSTOMER_PALETTES['umum'];
    $initials = collect(preg_split('/\s+/', trim(preg_replace('/\(.*?\)/', '', (string) $business->name))))
        ->filter()
        ->take(2)
        ->map(fn (string $word): string => Str::upper(Str::substr($word, 0, 1)))
        ->implode('');
    $logoUrl = $business->getLogoUrl();
@endphp

<x-layouts.customer :title="$business->name" :business="$business" activeNav="beranda">
    <div x-data="{ query: '', showAll: false }">
        <div class="grid grid-cols-1 gap-5 pb-5 lg:grid-cols-12 lg:items-start lg:gap-8">
            {{-- Konteks kedai: banner foto penuh + summary card dengan logo terintegrasi tanpa tumpang tindih --}}
            <aside class="lg:sticky lg:top-24 lg:col-span-5 xl:col-span-4">
                <div class="relative overflow-hidden md:rounded-2xl">
                    <x-customer.store-cover :business="$business" size="hero" :show-category="false" :show-logo="false" class="h-44 sm:h-48">
                        <div class="absolute inset-x-3 top-3 flex items-center justify-between gap-2">
                            <a href="{{ route('customer.search') }}" class="inline-flex items-center gap-1.5 rounded-full bg-white/95 px-3 py-1.5 text-xs font-extrabold text-gray-900 shadow-sm transition hover:bg-white">
                                <x-customer.icon name="arrow-left" class="size-3.5" />
                                Cari toko lain
                            </a>
                            <x-customer.hours-badge :business="$business" class="bg-white/95! shadow-sm" />
                        </div>
                    </x-customer.store-cover>
                </div>

                <div class="relative z-10 mx-4 -mt-6 flex flex-col gap-4 rounded-2xl border border-gray-200 bg-white p-4 shadow-raised md:mx-0 md:-mt-5 md:p-5">
                    <div class="flex flex-col gap-2.5">
                        <div class="flex items-start gap-3">
                            <div class="flex size-14 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xs">
                                @if ($logoUrl)
                                    <img src="{{ $logoUrl }}" alt="Logo {{ $business->name }}" class="size-full object-cover" />
                                @else
                                    <span class="text-lg font-extrabold {{ $initialsColor }}" aria-hidden="true">{{ $initials ?: '?' }}</span>
                                @endif
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-start justify-between gap-2">
                                    <h1 class="text-xl font-extrabold leading-7 text-gray-900 md:text-2xl md:leading-8">{{ $business->name }}</h1>
                                    <x-customer.rating :business="$business" variant="pill" />
                                </div>
                                <div class="mt-1.5 flex flex-wrap items-center gap-1.5">
                                    @if ($reviewCount === 0)
                                        <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold leading-4 text-gray-600">Belum ada ulasan</span>
                                    @endif
                                    @if ($business->category)
                                        <span class="rounded-full bg-brand-50 px-2.5 py-1 text-xs font-bold leading-4 text-brand-700">{{ $business->category->name }}</span>
                                    @endif
                                    @if ($business->areaLabel())
                                        <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold leading-4 text-slate-700">
                                            <x-customer.icon name="map-pin" class="size-3 text-slate-500" />
                                            {{ $business->areaLabel() }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <ul class="flex flex-col gap-2 text-xs leading-5 text-gray-700 sm:text-sm">
                        @if ($business->address || $business->areaLabel())
                            <li class="flex items-start gap-2">
                                <x-customer.icon name="map-pin" class="mt-0.5 size-4 shrink-0 text-gray-400" />
                                <span>{{ $business->address ?: $business->cityLabel() }}</span>
                            </li>
                        @endif
                        @if ($scheduleLabel)
                            <li class="flex items-center gap-2">
                                <x-customer.icon name="clock" class="size-4 shrink-0 text-gray-400" />
                                <span>Jam hari ini <span class="font-bold text-gray-900">{{ $scheduleLabel }}</span></span>
                            </li>
                        @endif
                        @if ($business->phone)
                            <li class="flex items-center gap-2">
                                <x-customer.icon name="phone" class="size-4 shrink-0 text-gray-400" />
                                <a href="tel:{{ preg_replace('/[^0-9+]/', '', $business->phone) }}" class="font-semibold text-brand-700 hover:underline">{{ $business->phone }}</a>
                            </li>
                        @endif
                    </ul>

                    <dl class="grid grid-cols-3 divide-x divide-brand-100 rounded-xl bg-brand-50/70 py-3 text-center">
                        @foreach ([
                            ['label' => 'Menunggu', 'value' => $waitingCount.' orang'],
                            ['label' => 'Perkiraan tunggu', 'value' => $waitRange !== null ? $waitRange['label'] : '—'],
                            ['label' => 'Layanan tersedia', 'value' => $availableServiceCount],
                        ] as $fact)
                            <div class="flex min-w-0 flex-col-reverse gap-0.5 px-2">
                                <dt class="text-xs leading-4 text-gray-600">{{ $fact['label'] }}</dt>
                                <dd class="text-base font-bold leading-6 text-gray-900 tabular-nums">{{ $fact['value'] }}</dd>
                            </div>
                        @endforeach
                    </dl>

                    @if (isset($siblingBranches) && $siblingBranches->isNotEmpty())
                        <div class="border-t border-gray-100 pt-3">
                            <div class="mb-2 flex items-center justify-between gap-2">
                                <p class="text-sm font-bold text-gray-900">Cabang lainnya</p>
                                <span class="text-xs font-semibold text-gray-500">{{ $siblingBranches->count() }} cabang</span>
                            </div>
                            <div class="-mx-2 flex flex-col">
                                @foreach ($siblingBranches as $branch)
                                    <a href="{{ route('customer.queue', ['slug' => $branch->slug]) }}" class="group flex min-h-11 items-center justify-between gap-2 rounded-lg px-2 py-1.5 text-sm transition hover:bg-gray-50">
                                        <div class="min-w-0">
                                            <p class="truncate font-bold text-gray-900 group-hover:text-brand-700">{{ $branch->name }}</p>
                                            <p class="truncate text-xs text-gray-500">{{ $branch->cityLabel() ?: ($branch->address ?? 'Cabang resmi') }}</p>
                                        </div>
                                        <x-status-badge :status="$branch->isOpen() ? 'buka' : ($branch->isPaused() ? 'jeda' : 'tutup')" size="sm" class="shrink-0" />
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </aside>

            <div class="flex min-w-0 flex-col gap-4 px-5 md:px-0 lg:col-span-7 xl:col-span-8">
                {{-- Status operasional --}}
                @if ($business->is_suspended)
                    <div class="flex flex-col gap-1 rounded-xl border border-red-200 bg-red-50 p-3.5 text-xs leading-4" role="alert">
                        <p class="text-sm font-bold text-danger-700">Layanan Antrean Sementara Tidak Tersedia</p>
                        <p class="text-gray-700">Kedai ini sedang dinonaktifkan sementara. Pengambilan antrean baru belum bisa dilakukan.</p>
                    </div>
                @elseif ($business->status === \App\Models\Business::STATUS_TUTUP)
                    <div class="flex flex-col gap-1 rounded-xl border border-gray-200 bg-gray-100 p-3.5 text-xs leading-4" role="status">
                        <p class="text-sm font-bold text-gray-900">UMKM Sedang Tutup</p>
                        <p class="text-gray-700">Kedai sedang tidak menerima antrean baru. Silakan cek kembali di jam operasional.</p>
                    </div>
                @elseif ($business->status === \App\Models\Business::STATUS_JEDA)
                    <div class="flex flex-col gap-1 rounded-xl border border-amber-200 bg-amber-50 p-3.5 text-xs leading-4" role="status">
                        <p class="text-sm font-bold text-amber-900">Pendaftaran Antrean Sedang Dijeda</p>
                        <p class="text-gray-700">Pengambilan antrean online dijeda sementara. Silakan tunggu sebentar atau tanyakan langsung ke kasir.</p>
                    </div>
                @endif

                @if (session('status'))
                    <div class="rounded-xl bg-green-50 p-3 text-xs font-semibold leading-4 text-success-700" role="status">{{ session('status') }}</div>
                @endif

                @if ($errors->has('queue_error'))
                    <div class="rounded-xl bg-red-50 p-3 text-xs font-semibold leading-4 text-danger-700" role="alert">{{ $errors->first('queue_error') }}</div>
                @endif

                @auth
                    <div class="flex items-start justify-between gap-3 rounded-xl border border-brand-200 bg-brand-50 p-3.5 text-xs leading-4">
                        <div class="flex items-start gap-3">
                            <x-customer.icon name="ticket" class="mt-0.5 size-4 shrink-0 text-brand-700" />
                            <div class="flex flex-col gap-0.5">
                                <p class="font-bold text-brand-700">{{ $activeTicketCount ?? 0 }} dari {{ $maxActiveTickets ?? 3 }} antrean aktif terpakai</p>
                                <p class="text-gray-700">Setelah memilih layanan, Anda mendapat QR tiket untuk memantau giliran dari ponsel.</p>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="flex flex-col gap-3 rounded-xl border border-brand-200 bg-brand-50 p-3.5 text-xs leading-4 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex items-start gap-3">
                            <x-customer.icon name="user" class="mt-0.5 size-4 shrink-0 text-brand-700" />
                            <div class="flex flex-col gap-0.5">
                                <p class="font-bold text-brand-700">Masuk untuk mengambil antrean</p>
                                <p class="text-gray-700">Daftar atau masuk akun pelanggan untuk mengambil hingga 3 antrean aktif dan menyimpan QR tiket Anda.</p>
                            </div>
                        </div>
                        <div class="flex shrink-0 items-center gap-2">
                            <a href="{{ route('login') }}" class="inline-flex h-9 items-center rounded-lg bg-brand-700 px-3 text-xs font-bold text-white transition hover:bg-blue-900">Masuk</a>
                            <a href="{{ route('register', ['type' => 'customer']) }}" class="inline-flex h-9 items-center rounded-lg border border-brand-200 bg-white px-3 text-xs font-bold text-brand-700 transition hover:bg-brand-100">Daftar</a>
                        </div>
                    </div>
                @endauth

                {{-- Daftar layanan --}}
                <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
                    <div class="flex flex-col gap-0.5">
                        <h2 class="text-lg font-extrabold leading-7 text-gray-900">Pilih Layanan</h2>
                        <p class="text-xs leading-4 text-gray-600">{{ $services->isEmpty() ? 'Belum ada layanan online.' : $availableServiceCount.' dari '.$services->count().' layanan tersedia hari ini.' }}</p>
                    </div>
                    @if ($services->count() > 3)
                        <label class="flex h-11 items-center gap-2.5 rounded-xl border-[1.5px] border-gray-300 bg-white px-3.5 transition focus-within:border-brand-700 focus-within:ring-2 focus-within:ring-brand-100 md:w-72">
                            <x-customer.icon name="search" class="size-4 text-gray-400" />
                            <span class="sr-only">Cari layanan</span>
                            <input
                                type="search"
                                x-model.debounce.150ms="query"
                                placeholder="Cari layanan di kedai ini"
                                class="w-full min-w-0 border-0 bg-transparent p-0 text-sm leading-5 text-gray-900 placeholder:text-gray-500 focus:outline-none focus:ring-0"
                            />
                        </label>
                    @endif
                </div>

                @if ($services->isEmpty())
                    <div class="flex flex-col items-center gap-1 rounded-2xl border border-dashed border-gray-300 bg-white px-5 py-10 text-center">
                        <p class="text-sm font-bold leading-5 text-gray-900">Belum Ada Layanan Tersedia</p>
                        <p class="text-xs leading-4 text-gray-600">Kedai ini belum membuka layanan antrean online.</p>
                    </div>
                @else
                    <div class="grid grid-cols-1 gap-3 xl:grid-cols-2">
                        @foreach ($services as $service)
                            @php
                                $isAvailable = $canTakeQueue && ! $service->is_quota_full;
                                $isQuotaLow = ! $service->is_quota_full && $service->remaining_quota !== null && $service->remaining_quota <= 5;
                                $unavailableLabel = match (true) {
                                    $service->is_quota_full => 'Kuota Habis',
                                    $business->isPaused() && ! $business->is_suspended => 'Antrean Dijeda',
                                    $business->isClosed() => 'Sedang Tutup',
                                    default => 'Tidak Tersedia',
                                };
                                $serviceSearchText = Str::lower($service->name.' '.$service->description);
                            @endphp
                            <{{ $isAvailable ? 'a' : 'div' }}
                                @if ($isAvailable) href="{{ route('customer.queue.create', ['slug' => $business->slug, 'service_id' => $service->id]) }}" @endif
                                x-show="query.trim() !== '' ? @js($serviceSearchText).includes(query.trim().toLowerCase()) : (showAll || {{ $loop->index }} < 6)"
                                @if ($loop->index >= 6) x-cloak @endif
                                x-transition.opacity.duration.200ms
                                @class([
                                    'group flex items-stretch justify-between gap-3 rounded-2xl border bg-white p-3.5 transition-[border-color,box-shadow,transform] duration-200',
                                    'border-gray-200 shadow-card hover:border-brand-300 hover:shadow-raised active:scale-[0.99]' => $isAvailable,
                                    'border-gray-200 bg-gray-50' => ! $isAvailable,
                                ])
                            >
                                <div class="flex min-w-0 flex-1 flex-col gap-1.5">
                                    <p @class(['text-base font-extrabold leading-snug wrap-break-word', 'text-gray-900' => $isAvailable, 'text-gray-500' => ! $isAvailable])>{{ $service->name }}</p>
                                    @if ($service->description)
                                        <p class="line-clamp-2 text-xs leading-4 text-gray-600">{{ $service->description }}</p>
                                    @endif

                                    <div class="mt-auto flex flex-wrap items-center gap-2 pt-1.5 text-xs">
                                        @if ($service->price)
                                            <span class="font-extrabold text-gray-900 whitespace-nowrap">Rp {{ number_format($service->price, 0, ',', '.') }}</span>
                                            <span class="h-3 w-px bg-gray-300" aria-hidden="true"></span>
                                        @endif
                                        <span class="inline-flex items-center gap-1 font-semibold text-gray-700">
                                            <x-customer.icon name="clock" class="size-3.5 text-gray-400" />
                                            {{ $service->duration_minutes ? '±'.$service->duration_minutes.' menit' : 'Fleksibel' }}
                                        </span>
                                        @if ($service->daily_quota !== null)
                                            <span @class([
                                                'rounded-md px-2 py-0.5 text-xs font-bold',
                                                'bg-red-50 text-danger-700' => $service->is_quota_full,
                                                'bg-amber-100 text-amber-900' => $isQuotaLow,
                                                'bg-emerald-100 text-emerald-800' => ! $service->is_quota_full && ! $isQuotaLow,
                                            ])>{{ $service->is_quota_full ? 'Kuota habis' : 'Sisa kuota '.$service->remaining_quota }}</span>
                                        @endif
                                    </div>
                                </div>

                                <div class="flex shrink-0 flex-col items-center justify-between gap-2">
                                    <x-customer.service-thumb :service="$service" :category="$business->category" :class="Arr::toCssClasses(['size-20 sm:size-22 border border-gray-200', 'grayscale opacity-60' => ! $isAvailable])" />

                                    @if ($isAvailable)
                                        <span class="inline-flex w-full items-center justify-center gap-1 rounded-full bg-brand-700 px-3 py-1.5 text-xs font-bold text-white transition-colors group-hover:bg-blue-900">
                                            Ambil Antrean
                                        </span>
                                    @else
                                        <span class="inline-flex w-full items-center justify-center rounded-full border border-gray-200 bg-white px-2.5 py-1 text-xs font-semibold text-gray-500">{{ $unavailableLabel }}</span>
                                    @endif
                                </div>
                            </{{ $isAvailable ? 'a' : 'div' }}>
                        @endforeach
                    </div>

                    @if ($services->count() > 6)
                        <div x-show="query.trim() === ''" class="flex justify-center pt-1">
                            <button
                                type="button"
                                @click="showAll = ! showAll"
                                class="inline-flex h-10 items-center justify-center gap-2 rounded-xl border border-brand-200 bg-brand-50/70 px-5 text-xs font-extrabold text-brand-700 transition hover:bg-brand-100"
                            >
                                <span x-text="showAll ? 'Tampilkan lebih sedikit' : 'Tampilkan semua ({{ $services->count() }}) layanan'"></span>
                                <svg class="size-4 transition-transform duration-200" :class="showAll && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                        </div>
                    @endif
                @endif

                {{-- Ulasan pelanggan gaya Shopee: ringkasan rating, distribusi 5★–1★, filter chip, urutkan & paginasi --}}
                @if ($reviewCount > 0)
                    <section id="ulasan" class="flex flex-col gap-4 rounded-2xl border border-gray-200 bg-white p-4 shadow-card md:p-5" aria-labelledby="reviews-title">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div>
                                <h2 id="reviews-title" class="text-base font-extrabold leading-6 text-gray-900">Ulasan Pelanggan</h2>
                                <p class="text-xs text-gray-500">Berdasarkan {{ $reviewCount }} pelanggan yang telah selesai dilayani.</p>
                            </div>
                            <form method="GET" action="{{ route('customer.queue', ['slug' => $business->slug]) }}#ulasan" class="flex items-center gap-2">
                                @if ($selectedStar)
                                    <input type="hidden" name="bintang" value="{{ $selectedStar }}">
                                @endif
                                @if ($withCommentOnly)
                                    <input type="hidden" name="dengan_komentar" value="1">
                                @endif
                                <label for="urut_ulasan" class="text-xs font-semibold text-gray-500">Urutkan:</label>
                                <select id="urut_ulasan" name="urut_ulasan" onchange="this.form.submit()" class="h-8 rounded-lg border border-gray-200 bg-gray-50 px-2.5 text-xs font-bold text-gray-800 focus:border-brand-700 focus:outline-none">
                                    <option value="terbaru" @selected($reviewSort === 'terbaru')>Terbaru</option>
                                    <option value="rating_tinggi" @selected($reviewSort === 'rating_tinggi')>Rating Tertinggi</option>
                                    <option value="rating_rendah" @selected($reviewSort === 'rating_rendah')>Rating Terendah</option>
                                </select>
                            </form>
                        </div>

                        {{-- Summary Box ala Shopee --}}
                        <div class="grid grid-cols-1 gap-4 border-b border-gray-100 pb-4 sm:grid-cols-12 sm:items-center">
                            <div class="flex items-center gap-3 sm:col-span-4 sm:flex-col sm:items-start sm:gap-1 sm:border-r sm:border-gray-100 sm:pr-4">
                                <div class="flex items-baseline gap-1">
                                    <span class="text-3xl font-extrabold leading-none text-gray-900 tabular-nums">{{ number_format($rating, 1) }}</span>
                                    <span class="text-xs font-bold text-gray-500">/ 5</span>
                                </div>
                                <div class="flex flex-col gap-0.5">
                                    <span class="flex text-amber-500" aria-label="{{ number_format($rating, 1) }} dari 5 bintang">
                                        @for ($star = 1; $star <= 5; $star++)
                                            <x-customer.icon name="star" :class="$star > round($rating) ? 'size-4 text-gray-300' : 'size-4'" />
                                        @endfor
                                    </span>
                                    <span class="text-xs font-semibold text-gray-600">{{ $reviewCount }} ulasan</span>
                                </div>
                            </div>

                            <div class="flex flex-col gap-1.5 sm:col-span-8">
                                @foreach ($ratingBreakdown as $starValue => $stat)
                                    <div class="flex items-center gap-2 text-xs">
                                        <span class="inline-flex w-8 shrink-0 items-center gap-0.5 font-bold text-gray-700 tabular-nums">{{ $starValue }} <x-customer.icon name="star" class="size-3 text-amber-500" /><span class="sr-only">bintang</span></span>
                                        <div class="h-2 flex-1 overflow-hidden rounded-full bg-amber-100">
                                            <div class="h-full rounded-full bg-amber-500 transition-all duration-300" style="width: {{ $stat['percent'] }}%"></div>
                                        </div>
                                        <span class="w-10 shrink-0 text-right text-xs font-semibold text-gray-500 tabular-nums">{{ $stat['count'] }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        {{-- Filter Chips --}}
                        <div class="flex flex-wrap items-center gap-1.5">
                            @php
                                $baseFilterQuery = array_filter([
                                    'urut_ulasan' => $reviewSort !== 'terbaru' ? $reviewSort : null,
                                ]);
                            @endphp
                            <a
                                href="{{ route('customer.queue', array_merge(['slug' => $business->slug], $baseFilterQuery)) }}#ulasan"
                                @class([
                                    'inline-flex items-center rounded-full border px-3 py-1.5 text-xs font-bold transition',
                                    'border-brand-700 bg-brand-700 text-white' => $selectedStar === null && ! $withCommentOnly,
                                    'border-gray-200 bg-white text-gray-700 hover:border-brand-300' => $selectedStar !== null || $withCommentOnly,
                                ])
                            >
                                Semua ({{ $reviewCount }})
                            </a>
                            @for ($star = 5; $star >= 1; $star--)
                                @php $starCount = $ratingBreakdown[$star]['count'] ?? 0; @endphp
                                <a
                                    href="{{ route('customer.queue', array_merge(['slug' => $business->slug, 'bintang' => $star, 'dengan_komentar' => $withCommentOnly ? 1 : null], $baseFilterQuery)) }}#ulasan"
                                    @class([
                                        'inline-flex items-center gap-1 rounded-full border px-3 py-1.5 text-xs font-bold transition tabular-nums',
                                        'border-amber-500 bg-amber-50 text-amber-900 ring-1 ring-amber-300' => $selectedStar === $star,
                                        'border-gray-200 bg-white text-gray-700 hover:border-amber-300' => $selectedStar !== $star,
                                    ])
                                >
                                    {{ $star }} <x-customer.icon name="star" class="size-3.5 text-amber-500" /><span class="sr-only">bintang</span> <span class="text-xs font-semibold text-gray-600">({{ $starCount }})</span>
                                </a>
                            @endfor
                            <a
                                href="{{ route('customer.queue', array_merge(['slug' => $business->slug, 'bintang' => $selectedStar, 'dengan_komentar' => $withCommentOnly ? null : 1], $baseFilterQuery)) }}#ulasan"
                                @class([
                                    'inline-flex items-center rounded-full border px-3 py-1.5 text-xs font-bold transition tabular-nums',
                                    'border-brand-700 bg-brand-50 text-brand-700 ring-1 ring-brand-200' => $withCommentOnly,
                                    'border-gray-200 bg-white text-gray-700 hover:border-brand-300' => ! $withCommentOnly,
                                ])
                            >
                                Dengan Komentar ({{ $reviewsWithCommentCount }})
                            </a>
                        </div>

                        @if ($reviews->isNotEmpty())
                            <ul class="flex flex-col divide-y divide-gray-100">
                                @foreach ($reviews as $review)
                                    <li class="flex flex-col gap-1.5 py-3.5 first:pt-0 last:pb-0">
                                        <div class="flex items-center justify-between gap-2">
                                            <div class="flex items-center gap-2">
                                                <span class="flex text-amber-500" aria-label="{{ $review->rating }} dari 5 bintang">
                                                    @for ($star = 1; $star <= 5; $star++)
                                                        <x-customer.icon name="star" :class="$star > $review->rating ? 'size-3.5 text-gray-200' : 'size-3.5'" />
                                                    @endfor
                                                </span>
                                                @if ($review->service)
                                                    <span class="rounded-full bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-700">{{ $review->service->name }}</span>
                                                @endif
                                            </div>
                                            <span class="text-xs leading-4 text-gray-500">{{ $review->created_at->translatedFormat('d M Y') }}</span>
                                        </div>
                                        @if ($review->comment)
                                            <p class="text-sm leading-5 text-pretty text-gray-800">{{ $review->comment }}</p>
                                        @else
                                            <p class="text-xs italic text-gray-500">Pelanggan memberikan rating tanpa komentar tertulis.</p>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>

                            @if ($reviews->hasPages())
                                <div class="border-t border-gray-100 pt-3">
                                    {{ $reviews->fragment('ulasan')->links() }}
                                </div>
                            @endif
                        @else
                            <div class="rounded-xl border border-dashed border-gray-200 bg-gray-50 px-4 py-6 text-center text-xs text-gray-600">
                                Belum ada ulasan yang sesuai dengan filter yang dipilih.
                            </div>
                        @endif
                    </section>
                @endif

            </div>
        </div>
    </div>
</x-layouts.customer>
