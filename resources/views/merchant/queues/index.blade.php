<x-layouts.merchant 
    title="Meja Operasional Antrean" 
    :businessName="$business->name"
    :businessStatus="$business->status"
    :businessSlug="$business->slug"
>
    <div class="space-y-6" x-data="{ walkInModalOpen: false, cancelModalOpen: false, cancelTicketId: null, cancelTicketNumber: '' }">
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
                        <p class="font-semibold mb-1">Terjadi Kesalahan:</p>
                        <ul class="list-disc list-inside space-y-0.5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        {{-- Page Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-2 border-b border-neutral-200">
            <div>
                <h1 class="text-h2 font-bold text-neutral-950">Meja Operasional Antrean</h1>
                <p class="text-body text-neutral-500 mt-1">
                    Kelola antrean pelanggan secara real-time dengan pemanggilan berurutan (FIFO).
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <button 
                    type="button" 
                    @click="walkInModalOpen = true"
                    class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg bg-neutral-900 text-white text-sm font-semibold hover:bg-neutral-800 transition shadow-xs focus:ring-2 focus:ring-offset-2 focus:ring-neutral-900"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <span>Tambah Walk-in</span>
                </button>

                <form method="POST" action="{{ route('merchant.queues.call-next') }}" x-data="{ submitting: false }" @submit="submitting = true">
                    @csrf
                    <button 
                        type="submit" 
                        :disabled="submitting || {{ $nowServing ? 'true' : 'false' }} || {{ $stats['menunggu'] === 0 ? 'true' : 'false' }}"
                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg bg-brand-700 text-white text-sm font-bold hover:bg-brand-800 disabled:opacity-50 disabled:cursor-not-allowed transition shadow-md focus:ring-2 focus:ring-offset-2 focus:ring-brand-700"
                        title="{{ $nowServing ? 'Selesaikan atau lewati tiket aktif terlebih dahulu' : ($stats['menunggu'] === 0 ? 'Tidak ada tiket menunggu' : 'Panggil nomor antrean berikutnya') }}"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
                        <span>Panggil Berikutnya</span>
                    </button>
                </form>
            </div>
        </div>

        {{-- Now Serving Arena (M7-15) --}}
        <div class="bg-white rounded-2xl border-2 {{ $nowServing ? 'border-brand-300 bg-gradient-to-b from-brand-50/30 to-white' : 'border-neutral-200' }} p-6 shadow-sm">
            <div class="flex items-center justify-between border-b border-neutral-200 pb-4 mb-6">
                <div class="flex items-center gap-2.5">
                    @if ($nowServing)
                        <span class="w-3.5 h-3.5 rounded-full bg-brand-600 animate-ping"></span>
                    @else
                        <span class="w-3.5 h-3.5 rounded-full bg-neutral-300"></span>
                    @endif
                    <h2 class="text-h3 font-bold text-neutral-900 tracking-tight">Sedang Berlangsung (Now Serving)</h2>
                </div>
                @if ($nowServing)
                    <x-status-badge :status="$nowServing->status" size="md" />
                @else
                    <span class="text-caption font-semibold px-3 py-1 rounded-full bg-neutral-100 text-neutral-600">
                        Loket Siap Melayani
                    </span>
                @endif
            </div>

            @if ($nowServing)
                <div class="py-4 text-center">
                    <span class="text-caption text-neutral-500 font-semibold uppercase tracking-wider block mb-1">
                        Nomor Antrean Dipanggil
                    </span>
                    <div class="text-5xl sm:text-6xl font-extrabold text-neutral-950 font-sans tabular-nums tracking-tight">
                        {{ $nowServing->ticket_number }}
                    </div>

                    <div class="mt-3 flex items-center justify-center gap-3 text-body text-neutral-700">
                        <span class="font-bold text-neutral-900 text-lg">{{ $nowServing->customer_name }}</span>
                        @if ($nowServing->customer_phone)
                            <span class="text-neutral-400">•</span>
                            <span class="text-neutral-500">{{ $nowServing->customer_phone }}</span>
                        @endif
                        <span class="text-neutral-400">•</span>
                        <span class="font-medium text-neutral-800">{{ $nowServing->service->name }} ({{ $nowServing->service->duration_minutes }} mnt)</span>
                        <span class="text-neutral-400">•</span>
                        <span class="text-xs px-2 py-0.5 rounded font-medium {{ $nowServing->source === 'online' ? 'bg-blue-50 text-blue-700' : 'bg-neutral-100 text-neutral-700' }}">
                            {{ $nowServing->source === 'online' ? 'Online' : 'Walk-in' }}
                        </span>
                    </div>

                    @if ($nowServing->called_at)
                        <div class="mt-2 inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-50 text-brand-800 text-caption font-medium border border-blue-200">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Dipanggil sejak: {{ $nowServing->called_at->format('H:i') }} WITA
                            @if ($nowServing->recalled_at)
                                (Dipanggil ulang: {{ $nowServing->recalled_at->format('H:i') }})
                            @endif
                        </div>
                    @endif

                    {{-- Lifecycle Action Buttons for Active Ticket (M7-17 to M7-21) --}}
                    <div class="border-t border-neutral-200 pt-6 mt-6 flex flex-wrap items-center justify-center sm:justify-between gap-3">
                        <div class="flex items-center gap-2">
                            {{-- Recall Button (M7-17) --}}
                            @if ($nowServing->status === 'dipanggil')
                                <form method="POST" action="{{ route('merchant.queues.recall', $nowServing) }}">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-lg border border-neutral-300 bg-white text-neutral-700 text-sm font-semibold hover:bg-neutral-50 shadow-xs transition">
                                        <svg class="w-4 h-4 text-neutral-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
                                        Panggil Ulang
                                    </button>
                                </form>
                            @endif

                            {{-- Skip Button (M7-20) --}}
                            @if ($nowServing->status === 'dipanggil')
                                <form method="POST" action="{{ route('merchant.queues.skip', $nowServing) }}" onsubmit="return confirm('Apakah Anda yakin ingin melewati nomor antrean ini?');">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-lg border border-neutral-300 bg-white text-neutral-700 text-sm font-semibold hover:bg-neutral-50 shadow-xs transition">
                                        <svg class="w-4 h-4 text-neutral-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"/></svg>
                                        Lewati Antrean
                                    </button>
                                </form>
                            @endif

                            {{-- Cancel Button --}}
                            <button 
                                type="button" 
                                @click="cancelTicketId = {{ $nowServing->id }}; cancelTicketNumber = '{{ $nowServing->ticket_number }}'; cancelModalOpen = true;"
                                class="inline-flex items-center gap-1.5 px-3 py-2 text-rose-600 hover:text-rose-800 text-sm font-semibold transition"
                            >
                                Batalkan
                            </button>
                        </div>

                        <div class="flex items-center gap-3">
                            {{-- Start Service Button (M7-18) --}}
                            @if ($nowServing->status === 'dipanggil')
                                <form method="POST" action="{{ route('merchant.queues.start-service', $nowServing) }}">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center gap-2 px-6 py-2.5 rounded-lg bg-brand-700 text-white text-sm font-bold hover:bg-brand-800 shadow-md transition">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        Mulai Melayani
                                    </button>
                                </form>
                            @endif

                            {{-- Complete Button (M7-19) --}}
                            @if ($nowServing->status === 'sedang_dilayani')
                                <form method="POST" action="{{ route('merchant.queues.complete', $nowServing) }}">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center gap-2 px-8 py-3 rounded-lg bg-emerald-600 text-white text-sm font-bold hover:bg-emerald-700 shadow-md transition">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        Selesaikan Pelayanan
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            @else
                <div class="py-8 text-center">
                    <div class="w-12 h-12 rounded-full bg-neutral-100 text-neutral-400 mx-auto flex items-center justify-center mb-3">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"/></svg>
                    </div>
                    <h3 class="text-h3 font-bold text-neutral-800">Meja Operasional Kosong</h3>
                    <p class="text-caption text-neutral-500 mt-1 max-w-sm mx-auto">
                        Tekan tombol <strong>"Panggil Berikutnya"</strong> untuk memanggil antrean tertua yang sedang menunggu secara otomatis.
                    </p>
                </div>
            @endif
        </div>

        {{-- Status Filter Badges Strip (M7-09) --}}
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-7 gap-3">
            <a href="{{ route('merchant.queues', array_merge(request()->query(), ['status' => null])) }}" class="p-3 rounded-xl border {{ empty($selectedStatus) ? 'border-brand-500 bg-brand-50/50 ring-1 ring-brand-500' : 'border-neutral-200 bg-white hover:bg-neutral-50' }} transition">
                <span class="text-caption font-semibold text-neutral-500 block truncate">Semua Status</span>
                <span class="text-xl font-extrabold text-neutral-900 tabular-nums block mt-1">{{ $stats['total'] }}</span>
            </a>
            <a href="{{ route('merchant.queues', array_merge(request()->query(), ['status' => 'menunggu'])) }}" class="p-3 rounded-xl border {{ $selectedStatus === 'menunggu' ? 'border-amber-500 bg-amber-50 ring-1 ring-amber-500' : 'border-neutral-200 bg-white hover:bg-neutral-50' }} transition">
                <span class="text-caption font-semibold text-amber-700 block truncate">Menunggu</span>
                <span class="text-xl font-extrabold text-amber-900 tabular-nums block mt-1">{{ $stats['menunggu'] }}</span>
            </a>
            <a href="{{ route('merchant.queues', array_merge(request()->query(), ['status' => 'dipanggil'])) }}" class="p-3 rounded-xl border {{ $selectedStatus === 'dipanggil' ? 'border-blue-500 bg-blue-50 ring-1 ring-blue-500' : 'border-neutral-200 bg-white hover:bg-neutral-50' }} transition">
                <span class="text-caption font-semibold text-blue-700 block truncate">Dipanggil</span>
                <span class="text-xl font-extrabold text-blue-900 tabular-nums block mt-1">{{ $stats['dipanggil'] }}</span>
            </a>
            <a href="{{ route('merchant.queues', array_merge(request()->query(), ['status' => 'sedang_dilayani'])) }}" class="p-3 rounded-xl border {{ $selectedStatus === 'sedang_dilayani' ? 'border-indigo-500 bg-indigo-50 ring-1 ring-indigo-500' : 'border-neutral-200 bg-white hover:bg-neutral-50' }} transition">
                <span class="text-caption font-semibold text-indigo-700 block truncate">Sedang Dilayani</span>
                <span class="text-xl font-extrabold text-indigo-900 tabular-nums block mt-1">{{ $stats['sedang_dilayani'] }}</span>
            </a>
            <a href="{{ route('merchant.queues', array_merge(request()->query(), ['status' => 'selesai'])) }}" class="p-3 rounded-xl border {{ $selectedStatus === 'selesai' ? 'border-emerald-500 bg-emerald-50 ring-1 ring-emerald-500' : 'border-neutral-200 bg-white hover:bg-neutral-50' }} transition">
                <span class="text-caption font-semibold text-emerald-700 block truncate">Selesai</span>
                <span class="text-xl font-extrabold text-emerald-900 tabular-nums block mt-1">{{ $stats['selesai'] }}</span>
            </a>
            <a href="{{ route('merchant.queues', array_merge(request()->query(), ['status' => 'dilewati'])) }}" class="p-3 rounded-xl border {{ $selectedStatus === 'dilewati' ? 'border-neutral-500 bg-neutral-100 ring-1 ring-neutral-500' : 'border-neutral-200 bg-white hover:bg-neutral-50' }} transition">
                <span class="text-caption font-semibold text-neutral-600 block truncate">Dilewati</span>
                <span class="text-xl font-extrabold text-neutral-800 tabular-nums block mt-1">{{ $stats['dilewati'] }}</span>
            </a>
            <a href="{{ route('merchant.queues', array_merge(request()->query(), ['status' => 'dibatalkan'])) }}" class="p-3 rounded-xl border {{ $selectedStatus === 'dibatalkan' ? 'border-rose-500 bg-rose-50 ring-1 ring-rose-500' : 'border-neutral-200 bg-white hover:bg-neutral-50' }} transition">
                <span class="text-caption font-semibold text-rose-700 block truncate">Dibatalkan</span>
                <span class="text-xl font-extrabold text-rose-900 tabular-nums block mt-1">{{ $stats['dibatalkan'] }}</span>
            </a>
        </div>

        {{-- Search & Multi-Filter Bar (M7-11, M7-12) --}}
        <div class="bg-white rounded-xl border border-neutral-200 p-4 shadow-xs">
            <form method="GET" action="{{ route('merchant.queues') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 items-end">
                {{-- Search Box --}}
                <div class="lg:col-span-2">
                    <label class="block text-caption font-semibold text-neutral-700 mb-1">Cari Nomor / Nama</label>
                    <div class="relative">
                        <input 
                            type="text" 
                            name="search" 
                            value="{{ $search }}" 
                            placeholder="Cari nomor tiket atau nama..." 
                            class="w-full pl-9 pr-3 py-2 rounded-lg border-neutral-300 text-sm focus:border-brand-500 focus:ring-brand-500"
                        >
                        <svg class="w-4 h-4 text-neutral-400 absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                </div>

                {{-- Service Filter --}}
                <div>
                    <label class="block text-caption font-semibold text-neutral-700 mb-1">Layanan</label>
                    <select name="service_id" class="w-full rounded-lg border-neutral-300 text-sm focus:border-brand-500 focus:ring-brand-500">
                        <option value="">Semua Layanan</option>
                        @foreach ($services as $srv)
                            <option value="{{ $srv->id }}" {{ $selectedService == $srv->id ? 'selected' : '' }}>
                                {{ $srv->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Source Filter --}}
                <div>
                    <label class="block text-caption font-semibold text-neutral-700 mb-1">Sumber Antrean</label>
                    <select name="source" class="w-full rounded-lg border-neutral-300 text-sm focus:border-brand-500 focus:ring-brand-500">
                        <option value="">Semua Sumber</option>
                        <option value="online" {{ $selectedSource === 'online' ? 'selected' : '' }}>Online (Customer Page)</option>
                        <option value="walk_in" {{ $selectedSource === 'walk_in' ? 'selected' : '' }}>Walk-in (Manual Operator)</option>
                    </select>
                </div>

                {{-- Date / History Filter --}}
                <div>
                    <label class="block text-caption font-semibold text-neutral-700 mb-1">Periode Tanggal</label>
                    <select name="date" class="w-full rounded-lg border-neutral-300 text-sm focus:border-brand-500 focus:ring-brand-500">
                        <option value="{{ $operatingDate }}" {{ $selectedDate === $operatingDate ? 'selected' : '' }}>Hari Ini ({{ $operatingDate }})</option>
                        <option value="all" {{ $selectedDate === 'all' ? 'selected' : '' }}>Semua Riwayat (Arsip)</option>
                    </select>
                </div>

                {{-- Action Filter Buttons --}}
                <div class="sm:col-span-2 lg:col-span-5 flex items-center justify-between pt-2 border-t border-neutral-100">
                    <div class="flex items-center gap-2">
                        <button type="submit" class="px-4 py-2 rounded-lg bg-brand-700 text-white text-xs font-bold hover:bg-brand-800 transition shadow-xs">
                            Terapkan Filter
                        </button>
                        <a href="{{ route('merchant.queues') }}" class="px-3 py-2 rounded-lg border border-neutral-300 text-neutral-700 text-xs font-medium hover:bg-neutral-50 transition">
                            Reset
                        </a>
                    </div>

                    <span class="text-caption text-neutral-500">
                        Menampilkan <strong>{{ $tickets->total() }}</strong> tiket antrean
                    </span>
                </div>
            </form>
        </div>

        {{-- Queue List Section --}}
        <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden shadow-xs">
            <div class="p-4 sm:p-5 border-b border-neutral-200 flex items-center justify-between">
                <div>
                    <h3 class="text-h3 font-bold text-neutral-900">Daftar Antrean</h3>
                    <p class="text-caption text-neutral-500">
                        {{ $selectedDate === $operatingDate ? 'Antrean sesi operasional hari ini.' : 'Riwayat arsip antrean usaha.' }}
                    </p>
                </div>
            </div>

            @if ($tickets->isEmpty())
                <div class="p-12 text-center">
                    <div class="w-12 h-12 rounded-full bg-neutral-100 text-neutral-400 mx-auto flex items-center justify-center mb-3">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                    </div>
                    <h3 class="text-h3 font-semibold text-neutral-900">Tidak Ada Antrean Ditemukan</h3>
                    <p class="text-caption text-neutral-500 mt-1 max-w-sm mx-auto">
                        @if ($search || $selectedStatus || $selectedService || $selectedSource)
                            Tidak ada tiket yang sesuai dengan filter pencarian Anda.
                        @else
                            Belum ada tiket antrean yang tercatat pada sesi ini.
                        @endif
                    </p>
                </div>
            @else
                {{-- Desktop Table View (M7-10, M7-14) --}}
                <div class="hidden md:block overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-neutral-200 bg-neutral-50/70 text-caption font-semibold text-neutral-600">
                                <th class="py-3 px-4">No. Tiket</th>
                                <th class="py-3 px-4">Pelanggan</th>
                                <th class="py-3 px-4">Layanan</th>
                                <th class="py-3 px-4">Sumber</th>
                                <th class="py-3 px-4">Waktu Dibuat</th>
                                <th class="py-3 px-4">Status</th>
                                <th class="py-3 px-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-100 text-body">
                            @foreach ($tickets as $t)
                                <tr class="hover:bg-neutral-50/80 transition {{ in_array($t->status, ['dipanggil', 'sedang_dilayani']) ? 'bg-brand-50/30' : '' }}">
                                    <td class="py-3.5 px-4">
                                        <a href="{{ route('merchant.queues.show', $t) }}" class="font-bold text-neutral-950 hover:text-brand-700 tabular-nums">
                                            {{ $t->ticket_number }}
                                        </a>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <div class="font-medium text-neutral-900">{{ $t->customer_name }}</div>
                                        @if ($t->customer_phone)
                                            <div class="text-[11px] text-neutral-500">{{ $t->customer_phone }}</div>
                                        @endif
                                    </td>
                                    <td class="py-3.5 px-4 text-neutral-700">
                                        {{ $t->service->name }}
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium {{ $t->source === 'online' ? 'bg-blue-50 text-blue-700' : 'bg-neutral-100 text-neutral-700' }}">
                                            {{ $t->source === 'online' ? 'Online' : 'Walk-in' }}
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4 text-caption text-neutral-500 tabular-nums">
                                        {{ $t->created_at->format('H:i') }}
                                        @if ($selectedDate === 'all')
                                            <span class="block text-[11px] text-neutral-400">{{ $t->created_at->format('d/m/y') }}</span>
                                        @endif
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <x-status-badge :status="$t->status" size="sm" />
                                    </td>
                                    <td class="py-3.5 px-4 text-right">
                                        <div class="inline-flex items-center gap-1.5 justify-end">
                                            <a href="{{ route('merchant.queues.show', $t) }}" class="px-2.5 py-1 rounded text-xs font-medium text-neutral-600 hover:text-neutral-900 hover:bg-neutral-100 transition" title="Lihat detail">
                                                Detail
                                            </a>

                                            {{-- Status-Appropriate Actions (M7-21) --}}
                                            @if ($t->status === 'menunggu')
                                                <button 
                                                    type="button" 
                                                    @click="cancelTicketId = {{ $t->id }}; cancelTicketNumber = '{{ $t->ticket_number }}'; cancelModalOpen = true;"
                                                    class="px-2 py-1 rounded text-xs font-medium text-rose-600 hover:text-rose-800 hover:bg-rose-50 transition"
                                                    title="Batalkan tiket"
                                                >
                                                    Batalkan
                                                </button>
                                            @elseif ($t->status === 'dipanggil')
                                                <form method="POST" action="{{ route('merchant.queues.start-service', $t) }}">
                                                    @csrf
                                                    <button type="submit" class="px-2.5 py-1 rounded text-xs font-bold bg-brand-700 text-white hover:bg-brand-800 transition">
                                                        Layani
                                                    </button>
                                                </form>
                                                <form method="POST" action="{{ route('merchant.queues.recall', $t) }}">
                                                    @csrf
                                                    <button type="submit" class="p-1 rounded text-neutral-500 hover:text-neutral-900 transition" title="Panggil Ulang">
                                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
                                                    </button>
                                                </form>
                                            @elseif ($t->status === 'sedang_dilayani')
                                                <form method="POST" action="{{ route('merchant.queues.complete', $t) }}">
                                                    @csrf
                                                    <button type="submit" class="px-2.5 py-1 rounded text-xs font-bold bg-emerald-600 text-white hover:bg-emerald-700 transition">
                                                        Selesai
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Mobile Card View (M7-14) --}}
                <div class="md:hidden divide-y divide-neutral-100">
                    @foreach ($tickets as $t)
                        <div class="p-4 space-y-3 {{ in_array($t->status, ['dipanggil', 'sedang_dilayani']) ? 'bg-brand-50/20' : '' }}">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="text-lg font-bold text-neutral-950 tabular-nums">{{ $t->ticket_number }}</span>
                                    <span class="text-[11px] px-2 py-0.5 rounded font-medium {{ $t->source === 'online' ? 'bg-blue-50 text-blue-700' : 'bg-neutral-100 text-neutral-700' }}">
                                        {{ $t->source === 'online' ? 'Online' : 'Walk-in' }}
                                    </span>
                                </div>
                                <x-status-badge :status="$t->status" size="sm" />
                            </div>

                            <div>
                                <h4 class="text-sm font-semibold text-neutral-900">{{ $t->customer_name }}</h4>
                                <p class="text-xs text-neutral-500 mt-0.5">{{ $t->service->name }} • Dibuat: {{ $t->created_at->format('H:i') }}</p>
                            </div>

                            <div class="flex items-center justify-between pt-2 border-t border-neutral-100">
                                <a href="{{ route('merchant.queues.show', $t) }}" class="text-xs font-semibold text-brand-700">
                                    Lihat Riwayat &rarr;
                                </a>

                                <div class="flex items-center gap-2">
                                    @if ($t->status === 'menunggu')
                                        <button 
                                            type="button" 
                                            @click="cancelTicketId = {{ $t->id }}; cancelTicketNumber = '{{ $t->ticket_number }}'; cancelModalOpen = true;"
                                            class="px-2.5 py-1 rounded text-xs font-semibold text-rose-600 bg-rose-50"
                                        >
                                            Batalkan
                                        </button>
                                    @elseif ($t->status === 'dipanggil')
                                        <form method="POST" action="{{ route('merchant.queues.start-service', $t) }}">
                                            @csrf
                                            <button type="submit" class="px-3 py-1.5 rounded text-xs font-bold bg-brand-700 text-white">
                                                Mulai Layani
                                            </button>
                                        </form>
                                    @elseif ($t->status === 'sedang_dilayani')
                                        <form method="POST" action="{{ route('merchant.queues.complete', $t) }}">
                                            @csrf
                                            <button type="submit" class="px-3 py-1.5 rounded text-xs font-bold bg-emerald-600 text-white">
                                                Selesaikan
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Pagination (M7-13) --}}
                <div class="p-4 border-t border-neutral-200">
                    {{ $tickets->links() }}
                </div>
            @endif
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
                        <p class="text-caption text-neutral-500">Pelanggan langsung di lokasi usaha.</p>
                    </div>
                    <button @click="walkInModalOpen = false" class="text-neutral-400 hover:text-neutral-600 p-1">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                @if ($services->isEmpty())
                    <div class="p-4 bg-amber-50 rounded-xl text-amber-800 text-xs mb-4">
                        Belum ada layanan aktif. Silakan tambahkan layanan terlebih dahulu.
                    </div>
                @else
                    <form method="POST" action="{{ route('merchant.queues.walk-in') }}" class="space-y-4">
                        @csrf

                        @if ($business->status === 'jeda')
                            <div class="p-3 bg-amber-50 border border-amber-200 rounded-lg text-xs text-amber-900">
                                <label class="flex items-start gap-2 cursor-pointer">
                                    <input type="checkbox" name="confirmed_paused" value="1" required class="mt-0.5 rounded border-amber-300 text-amber-600 focus:ring-amber-500">
                                    <span>Konfirmasi penerbitan tiket walk-in saat usaha dalam status Jeda.</span>
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

        {{-- Cancellation Reason Modal --}}
        <div 
            x-show="cancelModalOpen" 
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-50 overflow-y-auto bg-neutral-950/60 flex items-center justify-center p-4"
            style="display: none;"
            @keydown.escape.window="cancelModalOpen = false"
        >
            <div 
                @click.outside="cancelModalOpen = false"
                class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-neutral-200 relative"
            >
                <div class="flex items-center justify-between border-b border-neutral-100 pb-3 mb-4">
                    <h3 class="text-h3 font-bold text-neutral-900">Batalkan Antrean <span x-text="cancelTicketNumber" class="text-rose-600"></span></h3>
                    <button @click="cancelModalOpen = false" class="text-neutral-400 hover:text-neutral-600 p-1">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <form :action="'{{ url('/umkm/queues') }}/' + cancelTicketId + '/cancel'" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-caption font-semibold text-neutral-700 mb-1">
                            Alasan Pembatalan <span class="text-rose-500">*</span>
                        </label>
                        <textarea 
                            name="reason" 
                            required 
                            rows="3" 
                            maxlength="255"
                            placeholder="Tuliskan alasan pembatalan tiket antrean ini..."
                            class="w-full rounded-lg border-neutral-300 text-sm focus:border-rose-500 focus:ring-rose-500"
                        ></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-neutral-100">
                        <button type="button" @click="cancelModalOpen = false" class="px-4 py-2 rounded-lg border border-neutral-300 text-xs font-semibold text-neutral-700 hover:bg-neutral-50 transition">
                            Tutup
                        </button>
                        <button type="submit" class="px-4 py-2 rounded-lg bg-rose-600 text-white text-xs font-bold hover:bg-rose-700 transition shadow-xs">
                            Konfirmasi Batalkan Tiket
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-layouts.merchant>
