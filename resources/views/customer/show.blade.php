<x-layouts.customer 
    :title="$business->name"
    :business="$business"
>
    {{-- Responsive Layout: Desktop 2 Columns (5:7), Tablet/Mobile Single Column --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 lg:gap-8 items-start">
        
        {{-- Left Column (Desktop 5 cols): UMKM Profile, Schedule & Live Queue Status --}}
        <div class="lg:col-span-5 space-y-6">
            <x-card padding="lg" class="shadow-sm">
                {{-- Header with Logo / Avatar --}}
                <div class="flex items-start gap-4">
                    @if ($business->logo_path)
                        <img 
                            src="{{ $business->getLogoUrl() }}" 
                            alt="{{ $business->name }}" 
                            class="w-16 h-16 rounded-2xl object-cover shrink-0 border border-neutral-200 shadow-2xs"
                        >
                    @else
                        <div class="w-16 h-16 rounded-2xl bg-brand-700 text-white font-extrabold text-2xl flex items-center justify-center shrink-0 shadow-2xs tracking-tight">
                            {{ strtoupper(substr($business->name, 0, 2)) }}
                        </div>
                    @endif

                    <div class="min-w-0 flex-1">
                        @if ($business->category)
                            <span class="inline-block text-[11px] font-semibold uppercase tracking-wider text-brand-700 bg-brand-50 px-2.5 py-0.5 rounded-md border border-brand-200/80 mb-1">
                                {{ $business->category->name }}
                            </span>
                        @endif
                        <h1 class="text-xl sm:text-2xl font-extrabold text-neutral-950 tracking-tight leading-snug">
                            {{ $business->name }}
                        </h1>
                        @if ($business->description)
                            <p class="text-body text-neutral-600 mt-1 line-clamp-2">
                                {{ $business->description }}
                            </p>
                        @endif
                    </div>
                </div>

                {{-- Location, Hours & Contact Details --}}
                <div class="border-t border-neutral-200 pt-5 mt-6 space-y-3.5 text-body">
                    @if ($business->address)
                        <div class="flex items-start gap-3 text-neutral-700">
                            <svg class="w-5 h-5 text-neutral-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <span class="text-neutral-700 leading-relaxed">{{ $business->address }}</span>
                        </div>
                    @endif

                    <div class="flex items-start gap-3 text-neutral-700">
                        <svg class="w-5 h-5 text-neutral-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <div class="flex-1">
                            <div class="flex items-center justify-between">
                                <span class="font-medium text-neutral-900">
                                    {{ $todayHour ? $todayHour->getFormattedSchedule() : 'Jadwal belum diatur' }}
                                </span>
                                @if ($business->timezone)
                                    <span class="text-[11px] font-mono text-neutral-500 uppercase">
                                        {{ $business->timezone }}
                                    </span>
                                @endif
                            </div>
                            <span class="text-[11px] text-neutral-500 block">Jadwal hari ini ({{ $todayHour ? $todayHour->getDayName() : 'Hari ini' }})</span>
                        </div>
                    </div>

                    @if ($business->phone)
                        <div class="flex items-center gap-3 text-neutral-700">
                            <svg class="w-5 h-5 text-neutral-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                            </svg>
                            <span class="text-neutral-700 font-medium">{{ $business->phone }}</span>
                        </div>
                    @endif
                </div>

                {{-- Live Operational Box: Now Serving & Waiting Count --}}
                <div class="mt-6 pt-5 border-t border-neutral-200">
                    <div class="p-4 bg-brand-50/80 rounded-xl border border-brand-200 flex items-center justify-between">
                        <div>
                            <div class="text-[11px] font-semibold text-brand-800 uppercase tracking-wider">
                                Sedang Dilayani
                            </div>
                            <div class="text-xs text-brand-700/80 mt-0.5">
                                {{ $waitingCount }} antrean menunggu
                            </div>
                        </div>
                        <div class="font-extrabold text-brand-700 text-2xl tabular-nums tracking-tight">
                            {{ $servingTicket ? $servingTicket->ticket_number : '—' }}
                        </div>
                    </div>
                </div>
            </x-card>
        </div>

        {{-- Right Column (Desktop 7 cols): Service Catalog & Registration --}}
        <div class="lg:col-span-7 space-y-6">

            {{-- Operational Status Banner Alert --}}
            @if ($business->is_suspended)
                <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 flex items-start gap-3">
                    <svg class="w-5 h-5 text-rose-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <div>
                        <div class="font-bold text-sm">Layanan Antrean Sementara Tidak Tersedia</div>
                        <p class="text-xs text-rose-700 mt-0.5 leading-relaxed">
                            Akun unit usaha ini sedang dinonaktifkan sementara oleh administrator. Pendaftaran antrean baru tidak dapat dilakukan.
                        </p>
                    </div>
                </div>
            @elseif ($business->status === \App\Models\Business::STATUS_TUTUP)
                <div class="p-4 rounded-xl bg-neutral-100 border border-neutral-300 text-neutral-800 flex items-start gap-3">
                    <svg class="w-5 h-5 text-neutral-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                    </svg>
                    <div>
                        <div class="font-bold text-sm">UMKM Sedang Tutup</div>
                        <p class="text-xs text-neutral-600 mt-0.5 leading-relaxed">
                            Layanan antrean saat ini sedang tutup dan tidak menerima pendaftaran tiket baru. Silakan periksa jam operasional usaha ini.
                        </p>
                    </div>
                </div>
            @elseif ($business->status === \App\Models\Business::STATUS_JEDA)
                <div class="p-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-900 flex items-start gap-3">
                    <svg class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div>
                        <div class="font-bold text-sm">Pendaftaran Antrean Sedang Dijeda</div>
                        <p class="text-xs text-amber-800 mt-0.5 leading-relaxed">
                            Pengambilan tiket online sedang dijeda sementara oleh pemilik usaha. Silakan tunggu beberapa saat atau hubungi kasir/staf langsung.
                        </p>
                    </div>
                </div>
            @endif

            {{-- Flash Messages --}}
            @if (session('status'))
                <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-center gap-3">
                    <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="text-sm font-medium">{{ session('status') }}</span>
                </div>
            @endif

            @if ($errors->has('queue_error'))
                <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 flex items-center gap-3">
                    <svg class="w-5 h-5 text-rose-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="text-sm font-medium">{{ $errors->first('queue_error') }}</span>
                </div>
            @endif

            {{-- Service Catalog Section --}}
            <div>
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-lg sm:text-xl font-bold text-neutral-950">
                            Pilih Layanan
                        </h2>
                        <p class="text-body text-neutral-500 mt-0.5">
                            Pilih salah satu layanan untuk mendaftar antrean digital hari ini.
                        </p>
                    </div>
                    <span class="hidden sm:inline-flex items-center gap-1.5 text-xs text-brand-700 bg-brand-50 px-2.5 py-1 rounded-md font-medium border border-brand-200">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        Tanpa Login
                    </span>
                </div>

                @if ($services->isEmpty())
                    <x-card padding="lg" class="text-center py-12">
                        <x-empty-state 
                            title="Belum Ada Layanan Tersedia"
                            description="Unit usaha ini belum menyediakan layanan antrean aktif untuk pendaftaran publik saat ini."
                        />
                    </x-card>
                @else
                    <div class="space-y-4">
                        @foreach ($services as $service)
                            <x-card class="transition-all duration-150 {{ $business->canAcceptOnlineQueue() && ! $service->is_quota_full ? 'hover:border-brand-500 hover:shadow-xs' : 'opacity-90 bg-neutral-50/50' }}">
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2.5 flex-wrap">
                                            <h3 class="text-base sm:text-lg font-bold text-neutral-900 tracking-tight">
                                                {{ $service->name }}
                                            </h3>

                                            {{-- Quota Badge --}}
                                            @if ($service->daily_quota !== null)
                                                @if ($service->is_quota_full)
                                                    <span class="inline-flex items-center gap-1 text-[11px] font-semibold px-2 py-0.5 rounded bg-rose-50 text-rose-700 border border-rose-200">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                                        Kuota Hari Ini Habis
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center gap-1 text-[11px] font-semibold px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                                        Sisa {{ $service->remaining_quota }} kuota
                                                    </span>
                                                @endif
                                            @else
                                                <span class="inline-flex items-center gap-1 text-[11px] font-semibold px-2 py-0.5 rounded bg-blue-50 text-blue-700 border border-blue-200">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                                                    Kuota Tersedia
                                                </span>
                                            @endif
                                        </div>

                                        @if ($service->description)
                                            <p class="text-body text-neutral-600 mt-1 line-clamp-2">
                                                {{ $service->description }}
                                            </p>
                                        @endif

                                        <div class="mt-2.5 flex items-center gap-3 text-caption text-neutral-500 flex-wrap">
                                            @if ($service->price)
                                                <span class="font-bold text-neutral-900 text-sm">
                                                    Rp {{ number_format($service->price, 0, ',', '.') }}
                                                </span>
                                                <span>•</span>
                                            @endif

                                            @if ($service->duration_minutes)
                                                <span class="flex items-center gap-1">
                                                    <svg class="w-3.5 h-3.5 text-neutral-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                    ±{{ $service->duration_minutes }} menit
                                                </span>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="sm:shrink-0 pt-2 sm:pt-0">
                                        @if ($business->canAcceptOnlineQueue() && ! $service->is_quota_full)
                                            <a 
                                                href="{{ route('customer.queue.create', ['slug' => $business->slug, 'service_id' => $service->id]) }}" 
                                                class="inline-flex items-center justify-center w-full sm:w-auto px-4 py-2.5 text-sm font-bold text-white bg-brand-700 hover:bg-brand-600 rounded-xl shadow-2xs transition focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2"
                                            >
                                                Ambil Antrean
                                                <svg class="w-4 h-4 ml-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                            </a>
                                        @else
                                            <button 
                                                type="button" 
                                                disabled 
                                                class="inline-flex items-center justify-center w-full sm:w-auto px-4 py-2.5 text-sm font-semibold text-neutral-400 bg-neutral-100 border border-neutral-200 rounded-xl cursor-not-allowed"
                                            >
                                                @if ($service->is_quota_full)
                                                    Kuota Habis
                                                @elseif ($business->isPaused())
                                                    Antrean Dijeda
                                                @elseif ($business->isClosed())
                                                    Sedang Tutup
                                                @else
                                                    Tidak Tersedia
                                                @endif
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </x-card>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Informational Privacy Callout --}}
            <div class="p-4 rounded-xl bg-neutral-100/80 border border-neutral-200 text-caption text-neutral-600 flex items-start gap-3">
                <svg class="w-5 h-5 text-neutral-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
                <div class="leading-relaxed">
                    <span class="font-semibold text-neutral-800">Privasi & Keamanan:</span>
                    Pendaftaran antrean publik ini tidak memerlukan akun ataupun password. Setelah berhasil, Anda akan menerima tautan akses unik untuk memantau status secara langsung dari peramban Anda.
                </div>
            </div>
        </div>
    </div>
</x-layouts.customer>

