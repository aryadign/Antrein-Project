<x-layouts.merchant 
    title="Dashboard Operasional UMKM" 
    :businessName="$business->name"
    :businessStatus="$business->status"
    :businessSlug="$business->slug"
>
    <div class="space-y-6" x-data="{ walkInModalOpen: false }">
        {{-- Flash Messages --}}
        @if (session('status'))
            <div class="rounded-xl bg-emerald-50 border border-emerald-200 p-4 text-sm font-medium text-emerald-800 flex items-center justify-between shadow-xs">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <span>{{ session('status') }}</span>
                </div>
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-xl bg-rose-50 border border-rose-200 p-4 text-sm text-rose-800 shadow-xs">
                <div class="flex items-start gap-2">
                    <svg class="w-5 h-5 text-rose-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <div>
                        <p class="font-semibold mb-1">Perhatian:</p>
                        <ul class="list-disc list-inside space-y-0.5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        {{-- Operational Status Notices (M7-07) --}}
        @if ($business->is_suspended)
            <div class="rounded-xl bg-rose-50 border border-rose-300 p-5 text-rose-900 flex items-start gap-3 shadow-xs">
                <svg class="w-6 h-6 text-rose-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                <div>
                    <h3 class="font-bold text-sm text-rose-950">Akun Usaha Ditangguhkan (Suspended)</h3>
                    <p class="text-xs text-rose-800 mt-1">
                        Penerimaan tiket antrean baru diblokir oleh administrator platform. Silakan hubungi tim dukungan AntreIn.
                    </p>
                </div>
            </div>
        @elseif ($business->status === 'tutup')
            <div class="rounded-xl bg-neutral-100 border border-neutral-300 p-4 text-neutral-800 flex items-center justify-between shadow-xs">
                <div class="flex items-center gap-3">
                    <span class="w-3 h-3 rounded-full bg-neutral-400"></span>
                    <div>
                        <h4 class="font-bold text-sm text-neutral-900">Operasional Antrean Sedang TUTUP</h4>
                        <p class="text-xs text-neutral-600">Pelanggan tidak dapat mengambil nomor tiket online hari ini.</p>
                    </div>
                </div>
                <a href="{{ route('merchant.settings', ['tab' => 'status']) }}" class="text-xs font-semibold text-brand-700 hover:text-brand-800 underline">
                    Ubah Status Usaha &rarr;
                </a>
            </div>
        @elseif ($business->status === 'jeda')
            <div class="rounded-xl bg-amber-50 border border-amber-300 p-4 text-amber-900 flex items-center justify-between shadow-xs">
                <div class="flex items-center gap-3">
                    <span class="w-3 h-3 rounded-full bg-amber-500 animate-pulse"></span>
                    <div>
                        <h4 class="font-bold text-sm text-amber-950">Operasional Antrean Sedang DIJEDA</h4>
                        <p class="text-xs text-amber-800">Pendaftaran online baru ditahan sementara. Tiket berjalan tetap dapat dilayani hingga selesai.</p>
                    </div>
                </div>
                <a href="{{ route('merchant.settings', ['tab' => 'status']) }}" class="text-xs font-semibold text-amber-900 hover:text-amber-950 underline">
                    Buka Kembali &rarr;
                </a>
            </div>
        @endif

        {{-- Page Header (M7-02) --}}
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 pb-2 border-b border-neutral-200">
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-h2 font-bold text-neutral-950">{{ $business->name }}</h1>
                    <x-status-badge :status="$business->status" size="sm" />
                </div>
                <p class="text-body text-neutral-500 mt-1 flex items-center gap-2">
                    <svg class="w-4 h-4 text-neutral-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    <span>Hari ini: <strong class="text-neutral-800">{{ \Carbon\Carbon::parse($operatingDate)->translatedFormat('l, d F Y') }}</strong> ({{ $timezone }})</span>
                </p>
            </div>

            {{-- Primary Header Actions --}}
            <div class="flex flex-wrap items-center gap-3">
                @if (isset($availableCounters) && $availableCounters->isNotEmpty())
                    <form method="POST" action="{{ route('merchant.queues.select-counter') }}" class="inline-flex items-center gap-2 bg-neutral-100 rounded-lg p-1 border border-neutral-300">
                        @csrf
                        <label for="header_counter_select" class="text-xs font-bold text-neutral-600 pl-2 flex items-center gap-1">
                            <svg class="w-3.5 h-3.5 text-neutral-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            Loket:
                        </label>
                        <select
                            id="header_counter_select"
                            name="counter_id"
                            onchange="this.form.submit()"
                            class="text-xs font-semibold rounded-md border-neutral-300 py-1.5 pl-2.5 pr-8 bg-white text-neutral-900 focus:border-brand-500 focus:ring-brand-500 shadow-2xs"
                        >
                            @foreach ($availableCounters as $c)
                                <option value="{{ $c->id }}" {{ isset($selectedCounter) && $selectedCounter && $selectedCounter->id === $c->id ? 'selected' : '' }}>
                                    {{ $c->name }} ({{ $c->code }})
                                </option>
                            @endforeach
                        </select>
                    </form>
                @endif

                <button 
                    type="button" 
                    @click="walkInModalOpen = true"
                    class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg bg-neutral-900 text-white text-sm font-semibold hover:bg-neutral-800 transition shadow-xs focus:ring-2 focus:ring-offset-2 focus:ring-neutral-900"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <span>Tambah Walk-in</span>
                </button>

                <a 
                    href="{{ route('merchant.queues') }}" 
                    class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg bg-brand-700 text-white text-sm font-semibold hover:bg-brand-800 transition shadow-xs focus:ring-2 focus:ring-offset-2 focus:ring-brand-700"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    <span>Meja Antrean</span>
                </a>
            </div>
        </div>

        {{-- Setup Checklist Banner (M7-06) --}}
        @if (! $isSetupComplete)
            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-xl p-5 shadow-xs">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-blue-600 text-white text-xs font-bold">!</span>
                            <h3 class="text-sm font-bold text-blue-950">Checklist Persiapan Operasional Usaha</h3>
                        </div>
                        <p class="text-xs text-blue-800 mt-1">Lengkapi data konfigurasi berikut agar pengalaman antrean UMKM Anda berjalan optimal:</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mt-4">
                    <a href="{{ route('merchant.settings', ['tab' => 'profile']) }}" class="flex items-center gap-2.5 p-3 rounded-lg bg-white border {{ $profileComplete ? 'border-emerald-300 text-emerald-800' : 'border-amber-300 text-amber-900' }} hover:shadow-xs transition">
                        @if ($profileComplete)
                            <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                            <span class="text-xs font-semibold">1. Profil & Kontak Lengkap</span>
                        @else
                            <svg class="w-5 h-5 text-amber-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke-width="2"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01"/></svg>
                            <span class="text-xs font-semibold">1. Lengkapi Profil Usaha &rarr;</span>
                        @endif
                    </a>

                    <a href="{{ route('merchant.settings', ['tab' => 'hours']) }}" class="flex items-center gap-2.5 p-3 rounded-lg bg-white border {{ $hoursConfigured ? 'border-emerald-300 text-emerald-800' : 'border-amber-300 text-amber-900' }} hover:shadow-xs transition">
                        @if ($hoursConfigured)
                            <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                            <span class="text-xs font-semibold">2. Jam Operasional Aktif</span>
                        @else
                            <svg class="w-5 h-5 text-amber-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke-width="2"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01"/></svg>
                            <span class="text-xs font-semibold">2. Atur Jam Operasional &rarr;</span>
                        @endif
                    </a>

                    <a href="{{ route('merchant.services.index') }}" class="flex items-center gap-2.5 p-3 rounded-lg bg-white border {{ $servicesConfigured ? 'border-emerald-300 text-emerald-800' : 'border-amber-300 text-amber-900' }} hover:shadow-xs transition">
                        @if ($servicesConfigured)
                            <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                            <span class="text-xs font-semibold">3. Layanan Terdaftar ({{ $services->count() }})</span>
                        @else
                            <svg class="w-5 h-5 text-amber-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke-width="2"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01"/></svg>
                            <span class="text-xs font-semibold">3. Tambahkan Layanan &rarr;</span>
                        @endif
                    </a>
                </div>
            </div>
        @endif

        {{-- 7 Queue Operational Metric Cards (M7-03) --}}
        <div>
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-h3 font-bold text-neutral-900">Statistik Antrean Hari Ini</h2>
                <span class="text-xs text-neutral-500 font-medium">Nomor Terakhir: #{{ $dailyQueue->current_number }}</span>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-7 gap-3">
                {{-- Total --}}
                <div class="bg-white rounded-xl border border-neutral-200 p-4 shadow-xs">
                    <span class="text-caption font-semibold text-neutral-500 block truncate">Total Tiket</span>
                    <span class="text-2xl font-extrabold text-neutral-950 tabular-nums block mt-1">{{ $stats['total'] }}</span>
                    <span class="text-[11px] text-neutral-400 mt-1 block">Semua antrean</span>
                </div>

                {{-- Menunggu --}}
                <div class="bg-amber-50/50 rounded-xl border border-amber-200 p-4 shadow-xs">
                    <span class="text-caption font-semibold text-amber-700 block truncate">Menunggu</span>
                    <span class="text-2xl font-extrabold text-amber-900 tabular-nums block mt-1">{{ $stats['menunggu'] }}</span>
                    <span class="text-[11px] text-amber-600 mt-1 block">Dalam antrean</span>
                </div>

                {{-- Dipanggil --}}
                <div class="bg-blue-50/50 rounded-xl border border-blue-200 p-4 shadow-xs">
                    <span class="text-caption font-semibold text-blue-700 block truncate">Dipanggil</span>
                    <span class="text-2xl font-extrabold text-blue-900 tabular-nums block mt-1">{{ $stats['dipanggil'] }}</span>
                    <span class="text-[11px] text-blue-600 mt-1 block">Menuju loket</span>
                </div>

                {{-- Sedang Dilayani --}}
                <div class="bg-indigo-50/50 rounded-xl border border-indigo-200 p-4 shadow-xs">
                    <span class="text-caption font-semibold text-indigo-700 block truncate">Sedang Dilayani</span>
                    <span class="text-2xl font-extrabold text-indigo-900 tabular-nums block mt-1">{{ $stats['sedang_dilayani'] }}</span>
                    <span class="text-[11px] text-indigo-600 mt-1 block">Di loket layanan</span>
                </div>

                {{-- Selesai --}}
                <div class="bg-emerald-50/50 rounded-xl border border-emerald-200 p-4 shadow-xs">
                    <span class="text-caption font-semibold text-emerald-700 block truncate">Selesai</span>
                    <span class="text-2xl font-extrabold text-emerald-900 tabular-nums block mt-1">{{ $stats['selesai'] }}</span>
                    <span class="text-[11px] text-emerald-600 mt-1 block">Berhasil dilayani</span>
                </div>

                {{-- Dilewati --}}
                <div class="bg-neutral-50 rounded-xl border border-neutral-200 p-4 shadow-xs">
                    <span class="text-caption font-semibold text-neutral-600 block truncate">Dilewati</span>
                    <span class="text-2xl font-extrabold text-neutral-800 tabular-nums block mt-1">{{ $stats['dilewati'] }}</span>
                    <span class="text-[11px] text-neutral-500 mt-1 block">Tidak hadir</span>
                </div>

                {{-- Dibatalkan --}}
                <div class="bg-rose-50/50 rounded-xl border border-rose-200 p-4 shadow-xs">
                    <span class="text-caption font-semibold text-rose-700 block truncate">Dibatalkan</span>
                    <span class="text-2xl font-extrabold text-rose-900 tabular-nums block mt-1">{{ $stats['dibatalkan'] }}</span>
                    <span class="text-[11px] text-rose-600 mt-1 block">Kuota dilepas</span>
                </div>
            </div>
        </div>

        {{-- Operational Grid: Now Serving & Quick Actions --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Now Serving Box --}}
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white rounded-xl border-2 {{ $nowServing ? 'border-brand-300 bg-gradient-to-b from-brand-50/30 to-white' : 'border-neutral-200' }} p-6 shadow-xs">
                    <div class="flex items-center justify-between border-b border-neutral-200 pb-4 mb-5">
                        <div class="flex items-center gap-2.5">
                            @if ($nowServing)
                                <span class="w-3 h-3 rounded-full bg-brand-600 animate-ping"></span>
                            @else
                                <span class="w-3 h-3 rounded-full bg-neutral-300"></span>
                            @endif
                            <h3 class="text-h3 font-bold text-neutral-900">Sedang Berlangsung (Now Serving)</h3>
                        </div>
                        @if ($nowServing)
                            <x-status-badge :status="$nowServing->status" size="md" />
                        @else
                            <span class="text-xs px-2.5 py-1 rounded-full bg-neutral-100 text-neutral-600 font-medium">Standby</span>
                        @endif
                    </div>

                    @if ($nowServing)
                        <div class="text-center py-4">
                            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-brand-100 text-brand-900 text-xs font-bold mb-2">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                <span>{{ $nowServing->counter ? $nowServing->counter->name : ($selectedCounter ? $selectedCounter->name : 'Loket 1') }}</span>
                            </div>
                            <span class="text-caption text-neutral-500 font-semibold uppercase tracking-wider block mb-1">
                                Tiket Aktif Loket
                            </span>
                            <div class="text-4xl sm:text-5xl font-extrabold text-neutral-950 font-sans tabular-nums tracking-tight">
                                {{ $nowServing->ticket_number }}
                            </div>

                            <div class="mt-3 flex items-center justify-center gap-3 text-body">
                                <span class="font-bold text-neutral-900">{{ $nowServing->customer_name }}</span>
                                <span class="text-neutral-400">•</span>
                                <span class="text-neutral-600">{{ $nowServing->service->name }}</span>
                                <span class="text-neutral-400">•</span>
                                <span class="text-xs px-2 py-0.5 rounded font-medium {{ $nowServing->source === 'online' ? 'bg-blue-50 text-blue-700' : 'bg-neutral-100 text-neutral-700' }}">
                                    {{ $nowServing->source === 'online' ? 'Online' : 'Walk-in' }}
                                </span>
                            </div>

                            {{-- Operational Control Shortcuts from Dashboard --}}
                            <div class="mt-6 flex flex-wrap items-center justify-center gap-3">
                                @if ($nowServing->status === 'dipanggil')
                                    <form method="POST" action="{{ route('merchant.queues.recall', $nowServing) }}">
                                        @csrf
                                        <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg border border-neutral-300 bg-white text-neutral-700 text-xs font-semibold hover:bg-neutral-50 shadow-xs transition">
                                            <svg class="w-4 h-4 text-neutral-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
                                            Panggil Ulang
                                        </button>
                                    </form>

                                    <form method="POST" action="{{ route('merchant.queues.start-service', $nowServing) }}">
                                        @csrf
                                        <button type="submit" class="inline-flex items-center gap-1.5 px-5 py-2 rounded-lg bg-brand-700 text-white text-xs font-bold hover:bg-brand-800 shadow-xs transition">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            Mulai Melayani
                                        </button>
                                    </form>
                                @elseif ($nowServing->status === 'sedang_dilayani')
                                    <form method="POST" action="{{ route('merchant.queues.complete', $nowServing) }}">
                                        @csrf
                                        <button type="submit" class="inline-flex items-center gap-1.5 px-5 py-2 rounded-lg bg-emerald-600 text-white text-xs font-bold hover:bg-emerald-700 shadow-xs transition">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                            Selesaikan Pelayanan
                                        </button>
                                    </form>
                                @endif

                                <a href="{{ route('merchant.queues') }}" class="inline-flex items-center gap-1 text-xs font-semibold text-brand-700 hover:text-brand-800 ml-2">
                                    Buka Kontrol Lengkap &rarr;
                                </a>
                            </div>
                        </div>
                    @else
                        <div class="py-8 text-center">
                            <div class="w-12 h-12 rounded-full bg-neutral-100 text-neutral-400 mx-auto flex items-center justify-center mb-3">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <h4 class="text-sm font-bold text-neutral-800">
                                @if (isset($selectedCounter) && $selectedCounter)
                                    {{ $selectedCounter->name }} Siap Melayani
                                @else
                                    Tidak Ada Antrean Aktif
                                @endif
                            </h4>
                            <p class="text-xs text-neutral-500 mt-1 max-w-sm mx-auto">
                                @if (isset($selectedCounter) && $selectedCounter)
                                    Loket {{ $selectedCounter->name }} saat ini dalam status standby dan siap memanggil antrean berikutnya.
                                @else
                                    Loket saat ini tidak sedang melayani pelanggan.
                                @endif
                            </p>

                            @if ($stats['menunggu'] > 0)
                                <form method="POST" action="{{ route('merchant.queues.call-next') }}" class="mt-4">
                                    @csrf
                                    @if (isset($selectedCounter) && $selectedCounter)
                                        <input type="hidden" name="counter_id" value="{{ $selectedCounter->id }}">
                                    @endif
                                    <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg bg-brand-700 text-white text-sm font-bold hover:bg-brand-800 shadow-md transition">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
                                        Panggil Antrean Berikutnya @if(isset($selectedCounter) && $selectedCounter) ke {{ $selectedCounter->name }} @else (FIFO) @endif
                                    </button>
                                </form>
                            @endif
                        </div>
                    @endif
                </div>

                {{-- Multi-Counter Status Overview Strip --}}
                @if (isset($activeCountersStatus) && count($activeCountersStatus) > 1)
                    <div class="bg-white rounded-xl border border-neutral-200 p-4 shadow-xs">
                        <h4 class="text-xs font-bold text-neutral-600 uppercase tracking-wider mb-3">Status Operasional Seluruh Loket</h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                            @foreach ($activeCountersStatus as $cStatus)
                                <div class="p-3 rounded-lg border {{ $cStatus['active_ticket'] ? 'border-brand-200 bg-brand-50/40' : 'border-neutral-200 bg-neutral-50/50' }} flex items-center justify-between">
                                    <div>
                                        <div class="flex items-center gap-1.5">
                                            <span class="w-2 h-2 rounded-full {{ $cStatus['active_ticket'] ? 'bg-brand-600 animate-pulse' : 'bg-emerald-500' }}"></span>
                                            <span class="text-xs font-bold text-neutral-900">{{ $cStatus['counter_name'] }}</span>
                                            <span class="font-mono text-[10px] text-neutral-500 bg-white px-1 py-0.5 rounded border border-neutral-200">{{ $cStatus['counter_code'] }}</span>
                                        </div>
                                        <div class="text-[11px] text-neutral-500 mt-1">
                                            @if ($cStatus['active_ticket'])
                                                Melayani: <strong class="text-neutral-900 font-mono">{{ $cStatus['active_ticket']->ticket_number }}</strong>
                                            @else
                                                <span class="text-emerald-700 font-medium">Standby (Siap)</span>
                                            @endif
                                        </div>
                                    </div>
                                    @if (isset($selectedCounter) && $selectedCounter && $selectedCounter->id === $cStatus['counter_id'])
                                        <span class="text-[10px] font-semibold bg-brand-600 text-white px-2 py-0.5 rounded-full">Loket Anda</span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Active Tickets Preview (M7-04) --}}
                <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden shadow-xs">
                    <div class="p-4 sm:p-5 border-b border-neutral-200 flex items-center justify-between">
                        <div>
                            <h3 class="text-h3 font-bold text-neutral-900">Antrean Aktif Berjalan</h3>
                            <p class="text-caption text-neutral-500">Maksimal 5 antrean prioritas teratas.</p>
                        </div>
                        <a href="{{ route('merchant.queues') }}" class="text-xs font-semibold text-brand-700 hover:text-brand-800">
                            Lihat Semua &rarr;
                        </a>
                    </div>

                    @if ($activeTickets->isEmpty())
                        <div class="p-8 text-center text-neutral-500">
                            <p class="text-sm">Belum ada tiket aktif menunggu giliran saat ini.</p>
                        </div>
                    @else
                        <div class="divide-y divide-neutral-100">
                            @foreach ($activeTickets as $ticket)
                                <div class="p-4 flex items-center justify-between hover:bg-neutral-50/60 transition">
                                    <div class="flex items-center gap-3">
                                        <div class="w-12 h-12 rounded-lg bg-neutral-100 flex items-center justify-center font-bold text-sm text-neutral-900 tabular-nums">
                                            {{ $ticket->ticket_number }}
                                        </div>
                                        <div>
                                            <p class="text-sm font-semibold text-neutral-900">{{ $ticket->customer_name }}</p>
                                            <p class="text-xs text-neutral-500">{{ $ticket->service->name }} • <span class="capitalize">{{ $ticket->source }}</span></p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <x-status-badge :status="$ticket->status" size="sm" />
                                        <a href="{{ route('merchant.queues.show', $ticket) }}" class="p-1.5 text-neutral-400 hover:text-neutral-700 rounded">
                                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            {{-- Right Column: Quick Actions & Public QR Card (M7-05) --}}
            <div class="space-y-6">
                {{-- Quick Actions Card --}}
                <div class="bg-white rounded-xl border border-neutral-200 p-5 shadow-xs">
                    <h3 class="text-h3 font-bold text-neutral-900 mb-4">Aksi Cepat</h3>

                    <div class="space-y-2.5">
                        <a href="{{ route('merchant.queues') }}" class="flex items-center justify-between p-3 rounded-lg border border-neutral-200 hover:border-brand-300 hover:bg-brand-50/30 transition text-neutral-800">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-lg bg-brand-50 text-brand-700 flex items-center justify-center">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold">Kelola Meja Antrean</p>
                                    <p class="text-[11px] text-neutral-500">Panggil, layani, atau lewati tiket</p>
                                </div>
                            </div>
                            <svg class="w-4 h-4 text-neutral-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </a>

                        <button type="button" @click="walkInModalOpen = true" class="w-full flex items-center justify-between p-3 rounded-lg border border-neutral-200 hover:border-brand-300 hover:bg-brand-50/30 transition text-neutral-800 text-left">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-lg bg-neutral-100 text-neutral-800 flex items-center justify-center">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold">Tambah Antrean Walk-in</p>
                                    <p class="text-[11px] text-neutral-500">Terbitkan tiket untuk pelanggan langsung</p>
                                </div>
                            </div>
                            <svg class="w-4 h-4 text-neutral-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </button>

                        <a href="{{ route('merchant.services.index') }}" class="flex items-center justify-between p-3 rounded-lg border border-neutral-200 hover:border-brand-300 hover:bg-brand-50/30 transition text-neutral-800">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-lg bg-emerald-50 text-emerald-700 flex items-center justify-center">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.121 14.121L19 19m-7-7l7-7m-7 7l-2.879 2.879a3 3 0 11-4.242-4.242 3 3 0 014.242 0M12 12L9.121 9.121m0 5.758a3 3 0 10-4.243 4.243 3 3 0 004.243-4.243z"/></svg>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold">Manajemen Layanan</p>
                                    <p class="text-[11px] text-neutral-500">Tambah layanan & kuota harian</p>
                                </div>
                            </div>
                            <svg class="w-4 h-4 text-neutral-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </a>

                        <a href="{{ route('merchant.settings', ['tab' => 'hours']) }}" class="flex items-center justify-between p-3 rounded-lg border border-neutral-200 hover:border-brand-300 hover:bg-brand-50/30 transition text-neutral-800">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-lg bg-amber-50 text-amber-700 flex items-center justify-center">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold">Jam Operasional</p>
                                    <p class="text-[11px] text-neutral-500">Atur jam buka mingguan UMKM</p>
                                </div>
                            </div>
                            <svg class="w-4 h-4 text-neutral-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </a>
                    </div>
                </div>

                {{-- QR Code & Public Link Card (M7-32, M7-33) --}}
                <div class="bg-white rounded-xl border border-neutral-200 p-5 shadow-xs">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-h3 font-bold text-neutral-900">Tautan & QR Antrean</h3>
                        <a href="{{ route('merchant.queues.qr') }}" class="text-xs font-semibold text-brand-700 hover:text-brand-800">
                            Halaman QR &rarr;
                        </a>
                    </div>
                    <p class="text-xs text-neutral-500 mb-4">
                        Bagikan tautan berikut atau cetak kode QR agar pelanggan dapat mengambil nomor tiket secara online tanpa login.
                    </p>

                    <div class="p-3 bg-neutral-50 rounded-lg border border-neutral-200 mb-3 flex items-center justify-between">
                        <span class="text-xs font-mono text-neutral-700 truncate mr-2">{{ url('/q/' . $business->slug) }}</span>
                        <button 
                            type="button" 
                            onclick="navigator.clipboard.writeText('{{ url('/q/' . $business->slug) }}'); alert('Tautan antrean berhasil disalin!');"
                            class="text-xs font-semibold text-brand-700 hover:text-brand-800 shrink-0"
                        >
                            Salin
                        </button>
                    </div>

                    <a href="{{ route('merchant.queues.qr') }}" class="w-full inline-flex items-center justify-center gap-2 px-4 py-2 rounded-lg bg-neutral-100 hover:bg-neutral-200 text-neutral-800 text-xs font-semibold transition">
                        <svg class="w-4 h-4 text-neutral-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                        <span>Lihat & Unduh QR Code</span>
                    </a>
                </div>
            </div>
        </div>

        {{-- Walk-In Modal (M7-24, M7-25) --}}
        <div 
            x-show="walkInModalOpen" 
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-50 overflow-y-auto bg-neutral-950/60 flex items-center justify-center p-4"
            style="display: none;"
            @keydown.escape.window="walkInModalOpen = false"
        >
            <div 
                @click.outside="walkInModalOpen = false"
                class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-neutral-200 relative"
            >
                <div class="flex items-center justify-between border-b border-neutral-100 pb-4 mb-5">
                    <div>
                        <h3 class="text-h3 font-bold text-neutral-900">Terbitkan Tiket Walk-in</h3>
                        <p class="text-caption text-neutral-500">Untuk pelanggan yang datang langsung ke lokasi usaha.</p>
                    </div>
                    <button @click="walkInModalOpen = false" class="text-neutral-400 hover:text-neutral-600 p-1">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                @if ($services->isEmpty())
                    <div class="p-4 bg-amber-50 rounded-xl text-amber-800 text-xs mb-4">
                        Belum ada layanan aktif. Silakan tambahkan layanan terlebih dahulu di menu Manajemen Layanan.
                    </div>
                @else
                    <form method="POST" action="{{ route('merchant.queues.walk-in') }}" class="space-y-4">
                        @csrf

                        @if ($business->status === 'jeda')
                            <div class="p-3 bg-amber-50 border border-amber-200 rounded-lg text-xs text-amber-900">
                                <label class="flex items-start gap-2 cursor-pointer">
                                    <input type="checkbox" name="confirmed_paused" value="1" required class="mt-0.5 rounded border-amber-300 text-amber-600 focus:ring-amber-500">
                                    <span>Konfirmasi penerbitan tiket manual saat status operasional usaha sedang Jeda.</span>
                                </label>
                            </div>
                        @endif

                        <div>
                            <label class="block text-label text-neutral-700 font-semibold mb-1.5">Layanan Antrean <span class="text-rose-500">*</span></label>
                            <select name="service_id" required class="w-full rounded-lg border-neutral-300 text-sm focus:border-brand-500 focus:ring-brand-500">
                                <option value="">-- Pilih Layanan --</option>
                                @foreach ($services as $srv)
                                    <option value="{{ $srv->id }}">{{ $srv->name }} (Estimasi: {{ $srv->duration_minutes }} mnt)</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-label text-neutral-700 font-semibold mb-1.5">Nama Pelanggan <span class="text-rose-500">*</span></label>
                            <input type="text" name="customer_name" required maxlength="100" placeholder="Contoh: Budi Santoso" class="w-full rounded-lg border-neutral-300 text-sm focus:border-brand-500 focus:ring-brand-500">
                        </div>

                        <div>
                            <label class="block text-label text-neutral-700 font-semibold mb-1.5">Nomor WhatsApp / HP (Opsional)</label>
                            <input type="text" name="customer_phone" maxlength="30" placeholder="08xxxxxxxxxx" class="w-full rounded-lg border-neutral-300 text-sm focus:border-brand-500 focus:ring-brand-500">
                        </div>

                        <div>
                            <label class="block text-label text-neutral-700 font-semibold mb-1.5">Catatan Tambahan (Opsional)</label>
                            <textarea name="notes" rows="2" maxlength="255" placeholder="Catatan preferensi atau kebutuhan khusus..." class="w-full rounded-lg border-neutral-300 text-sm focus:border-brand-500 focus:ring-brand-500"></textarea>
                        </div>

                        <div class="pt-3 border-t border-neutral-100 flex items-center justify-end gap-3">
                            <button type="button" @click="walkInModalOpen = false" class="px-4 py-2 rounded-lg border border-neutral-300 text-sm font-semibold text-neutral-700 hover:bg-neutral-50 transition">
                                Batal
                            </button>
                            <button type="submit" class="px-5 py-2 rounded-lg bg-neutral-900 text-white text-sm font-bold hover:bg-neutral-800 transition shadow-sm">
                                Terbitkan Tiket
                            </button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
</x-layouts.merchant>

