<x-layouts.merchant title="Ulasan & Masukan Pelanggan — AntreIn">
    <div class="space-y-6">
        <!-- Header Section -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-h2 font-bold text-neutral-950">Ulasan & Masukan Pelanggan</h1>
                <p class="text-body text-neutral-600 mt-1">
                    Pantau kepuasan pelanggan, ulasan bintang, dan umpan balik setelah pelayanan antrean selesai.
                </p>
            </div>
        </div>

        <!-- Success Notifications -->
        @if (session('status'))
            <div class="rounded-xl bg-emerald-50 border border-emerald-200 p-4 text-sm font-medium text-emerald-800 flex items-start gap-3">
                <svg class="w-5 h-5 text-emerald-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div class="flex-1">
                    <p>{{ session('status') }}</p>
                </div>
            </div>
        @endif

        <!-- Summary KPI Cards Grid -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Average Rating Card -->
            <div class="p-5 rounded-2xl bg-white border border-neutral-200/80 shadow-2xs space-y-2">
                <div class="text-caption font-bold text-neutral-500 uppercase tracking-wider">
                    Rata-rata Rating
                </div>
                <div class="flex items-baseline gap-2">
                    <span class="text-3xl sm:text-4xl font-extrabold text-neutral-950 tabular-nums">
                        {{ $averageRating > 0 ? number_format($averageRating, 1) : '—' }}
                    </span>
                    <span class="text-sm font-semibold text-neutral-400">/ 5.0</span>
                </div>
                <div class="flex items-center text-amber-400 pt-1">
                    @for ($i = 1; $i <= 5; $i++)
                        @if ($i <= round($averageRating))
                            <svg class="w-4 h-4 fill-current" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                        @else
                            <svg class="w-4 h-4 text-neutral-200 fill-current" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                        @endif
                    @endfor
                </div>
            </div>

            <!-- Total Reviews Card -->
            <div class="p-5 rounded-2xl bg-white border border-neutral-200/80 shadow-2xs space-y-2">
                <div class="text-caption font-bold text-neutral-500 uppercase tracking-wider">
                    Total Ulasan
                </div>
                <div class="text-3xl sm:text-4xl font-extrabold text-neutral-950 tabular-nums">
                    {{ number_format($totalReviews) }}
                </div>
                <div class="text-xs text-neutral-500">
                    Masukan dari pelanggan
                </div>
            </div>

            <!-- Completed Tickets Card -->
            <div class="p-5 rounded-2xl bg-white border border-neutral-200/80 shadow-2xs space-y-2">
                <div class="text-caption font-bold text-neutral-500 uppercase tracking-wider">
                    Tiket Selesai
                </div>
                <div class="text-3xl sm:text-4xl font-extrabold text-neutral-950 tabular-nums">
                    {{ number_format($completedTicketsCount) }}
                </div>
                <div class="text-xs text-neutral-500">
                    Total antrean terlayani
                </div>
            </div>

            <!-- Review Rate Card -->
            <div class="p-5 rounded-2xl bg-white border border-neutral-200/80 shadow-2xs space-y-2">
                <div class="text-caption font-bold text-neutral-500 uppercase tracking-wider">
                    Tingkat Respon Ulasan
                </div>
                <div class="text-3xl sm:text-4xl font-extrabold text-brand-700 tabular-nums">
                    {{ $reviewRate }}%
                </div>
                <div class="text-xs text-neutral-500">
                    Dari tiket yang selesai
                </div>
            </div>
        </div>

        <!-- Rating Distribution Section -->
        <div class="p-5 rounded-2xl bg-white border border-neutral-200/80 shadow-2xs">
            <h3 class="text-sm font-bold text-neutral-900 mb-3">Distribusi Penilaian Bintang</h3>
            <div class="space-y-2 max-w-xl">
                @foreach ([5, 4, 3, 2, 1] as $star)
                    @php
                        $count = $ratingsDistribution[$star] ?? 0;
                        $pct = $totalReviews > 0 ? round(($count / $totalReviews) * 100) : 0;
                    @endphp
                    <div class="flex items-center gap-3 text-xs">
                        <span class="w-16 font-semibold text-neutral-700 flex items-center gap-1">
                            <span>{{ $star }} Bintang</span>
                        </span>
                        <div class="flex-1 h-3 rounded-full bg-neutral-100 overflow-hidden">
                            <div 
                                class="h-full rounded-full transition-all duration-500 {{ $star >= 4 ? 'bg-amber-400' : ($star === 3 ? 'bg-amber-300' : 'bg-neutral-300') }}"
                                style="width: {{ $pct }}%"
                            ></div>
                        </div>
                        <span class="w-16 text-right font-mono text-neutral-500">
                            {{ $count }} ({{ $pct }}%)
                        </span>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Filter & Search Controls -->
        <div class="p-4 rounded-2xl bg-white border border-neutral-200/80 shadow-2xs">
            <form method="GET" action="{{ route('merchant.reviews.index') }}" class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-center">
                <!-- Search -->
                <div class="sm:col-span-5">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="w-4 h-4 text-neutral-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input
                            type="text"
                            name="search"
                            value="{{ request('search') }}"
                            placeholder="Cari nomor tiket, nama, atau komentar..."
                            class="w-full pl-9 pr-3 py-2 text-xs sm:text-sm rounded-xl border border-neutral-300 bg-neutral-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500 text-neutral-800"
                        >
                    </div>
                </div>

                <!-- Rating Filter -->
                <div class="sm:col-span-3">
                    <select
                        name="rating"
                        onchange="this.form.submit()"
                        class="w-full px-3 py-2 text-xs sm:text-sm rounded-xl border border-neutral-300 bg-neutral-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500 text-neutral-800"
                    >
                        <option value="">Semua Rating</option>
                        @foreach ([5, 4, 3, 2, 1] as $r)
                            <option value="{{ $r }}" {{ request('rating') == $r ? 'selected' : '' }}>
                                {{ $r }} Bintang ({{ $ratingsDistribution[$r] ?? 0 }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Service Filter -->
                <div class="sm:col-span-3">
                    <select
                        name="service_id"
                        onchange="this.form.submit()"
                        class="w-full px-3 py-2 text-xs sm:text-sm rounded-xl border border-neutral-300 bg-neutral-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500 text-neutral-800"
                    >
                        <option value="">Semua Layanan</option>
                        @foreach ($services as $srv)
                            <option value="{{ $srv->id }}" {{ request('service_id') == $srv->id ? 'selected' : '' }}>
                                {{ $srv->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Actions -->
                <div class="sm:col-span-1 flex items-center gap-2">
                    <button
                        type="submit"
                        class="w-full p-2 rounded-xl bg-brand-700 hover:bg-brand-800 text-white font-semibold text-xs flex items-center justify-center transition"
                        title="Terapkan Filter"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                        </svg>
                    </button>
                    @if (request()->hasAny(['search', 'rating', 'service_id']))
                        <a
                            href="{{ route('merchant.reviews.index') }}"
                            class="p-2 rounded-xl bg-neutral-100 hover:bg-neutral-200 text-neutral-600 font-semibold text-xs transition"
                            title="Reset Filter"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Reviews List -->
        <div class="p-5 rounded-2xl bg-white border border-neutral-200/80 shadow-2xs space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-neutral-100">
                <h3 class="text-sm font-bold text-neutral-900">
                    Daftar Ulasan ({{ $reviews->total() }})
                </h3>
            </div>

            @if ($reviews->isEmpty())
                <div class="py-12 text-center space-y-3">
                    <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-500 mx-auto flex items-center justify-center">
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                    </div>
                    <div class="text-sm font-bold text-neutral-800">Belum ada ulasan yang sesuai</div>
                    <p class="text-xs text-neutral-500 max-w-sm mx-auto">
                        @if (request()->hasAny(['search', 'rating', 'service_id']))
                            Tidak ditemukan ulasan yang cocok dengan filter yang dipilih. Coba reset filter pencarian Anda.
                        @else
                            Ulasan dari pelanggan akan muncul di sini secara otomatis setelah pelanggan mengisi formulir kepuasan layanan.
                        @endif
                    </p>
                </div>
            @else
                <div class="divide-y divide-neutral-100 space-y-4 divide-y-reverse">
                    @foreach ($reviews as $review)
                        <div class="pt-4 first:pt-0 space-y-2">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                <div class="flex items-center gap-2.5">
                                    <span class="px-2 py-0.5 rounded-lg text-xs font-mono font-bold bg-brand-50 text-brand-700 border border-brand-200">
                                        {{ $review->queueTicket?->ticket_number ?? '—' }}
                                    </span>
                                    <span class="text-xs font-bold text-neutral-900">
                                        {{ $review->queueTicket?->customer_name ?? 'Pelanggan' }}
                                    </span>
                                    @if ($review->service)
                                        <span class="text-2xs px-2 py-0.5 rounded-full bg-neutral-100 text-neutral-600">
                                            {{ $review->service->name }}
                                        </span>
                                    @endif
                                </div>
                                <div class="flex items-center gap-3">
                                    <!-- Star Display -->
                                    <div class="flex items-center text-amber-400">
                                        @for ($i = 1; $i <= 5; $i++)
                                            @if ($i <= $review->rating)
                                                <svg class="w-4 h-4 fill-current" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                            @else
                                                <svg class="w-4 h-4 text-neutral-200 fill-current" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                            @endif
                                        @endfor
                                        <span class="text-xs font-bold text-neutral-700 ml-1.5">{{ $review->rating }}.0</span>
                                    </div>
                                    <span class="text-2xs text-neutral-400">
                                        {{ $review->created_at->setTimezone($business->timezone ?? 'Asia/Makassar')->translatedFormat('d M Y, H:i') }}
                                    </span>
                                </div>
                            </div>

                            @if ($review->comment)
                                <div class="text-xs sm:text-sm text-neutral-700 bg-neutral-50 p-3 rounded-xl border border-neutral-100 leading-relaxed">
                                    {{ $review->comment }}
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>

                <!-- Pagination Links -->
                <div class="pt-4 border-t border-neutral-100">
                    {{ $reviews->links() }}
                </div>
            @endif
        </div>
    </div>
</x-layouts.merchant>

