@php
    $canCallNext = ! $nowServing && $stats['menunggu'] > 0;
    $callNextHint = $nowServing
        ? 'Selesaikan atau lewati tiket aktif terlebih dahulu'
        : ($stats['menunggu'] === 0 ? 'Tidak ada tiket menunggu' : 'Panggil nomor antrean berikutnya');
    $hasActiveFilters = $search || $selectedStatus || $selectedService || $selectedSource || $selectedCounterFilter || $selectedDate !== $operatingDate;
    $statusTiles = [
        ['key' => null, 'label' => 'Semua Status', 'count' => $stats['total'], 'active' => 'border-brand-500 bg-brand-50 ring-1 ring-brand-500', 'label_class' => 'text-neutral-500', 'count_class' => 'text-neutral-900'],
        ['key' => 'menunggu', 'label' => 'Menunggu', 'count' => $stats['menunggu'], 'active' => 'border-amber-500 bg-amber-50 ring-1 ring-amber-500', 'label_class' => 'text-amber-700', 'count_class' => 'text-amber-900'],
        ['key' => 'dipanggil', 'label' => 'Dipanggil', 'count' => $stats['dipanggil'], 'active' => 'border-blue-500 bg-blue-50 ring-1 ring-blue-500', 'label_class' => 'text-blue-700', 'count_class' => 'text-blue-900'],
        ['key' => 'sedang_dilayani', 'label' => 'Sedang Dilayani', 'count' => $stats['sedang_dilayani'], 'active' => 'border-indigo-500 bg-indigo-50 ring-1 ring-indigo-500', 'label_class' => 'text-indigo-700', 'count_class' => 'text-indigo-900'],
        ['key' => 'selesai', 'label' => 'Selesai', 'count' => $stats['selesai'], 'active' => 'border-emerald-500 bg-emerald-50 ring-1 ring-emerald-500', 'label_class' => 'text-emerald-700', 'count_class' => 'text-emerald-900'],
        ['key' => 'tidak_hadir', 'label' => 'Tidak Hadir', 'count' => $stats['tidak_hadir'] ?? 0, 'active' => 'border-rose-500 bg-rose-50 ring-1 ring-rose-500', 'label_class' => 'text-rose-700', 'count_class' => 'text-rose-900'],
        ['key' => 'dilewati', 'label' => 'Dilewati', 'count' => $stats['dilewati'], 'active' => 'border-neutral-500 bg-neutral-100 ring-1 ring-neutral-500', 'label_class' => 'text-neutral-600', 'count_class' => 'text-neutral-800'],
        ['key' => 'dibatalkan', 'label' => 'Dibatalkan', 'count' => $stats['dibatalkan'], 'active' => 'border-rose-500 bg-rose-50 ring-1 ring-rose-500', 'label_class' => 'text-rose-700', 'count_class' => 'text-rose-900'],
    ];
    $sourceLabel = fn (string $source): string => $source === 'online' ? 'Online' : 'Walk-in';
    $sourceClasses = fn (string $source): string => $source === 'online' ? 'bg-blue-50 text-blue-700 ring-blue-600/20' : 'bg-neutral-100 text-neutral-700 ring-neutral-500/20';
    $controlClasses = 'h-11 w-full rounded-lg border-neutral-300 text-sm focus:border-brand-500 focus:ring-brand-500';
@endphp

<x-layouts.merchant
    title="Meja Operasional Antrean"
    :businessName="$business->name"
    :businessStatus="$business->status"
    :businessSlug="$business->slug"
>
    <div class="space-y-5 pb-36 sm:space-y-6 sm:pb-0" x-data="{ walkInModalOpen: false, cancelModalOpen: false, cancelTicketId: null, cancelTicketNumber: '', filtersOpen: {{ $hasActiveFilters ? 'true' : 'false' }} }">
        {{-- Flash Messages --}}
        @if (session('status'))
            <div class="rounded-xl bg-emerald-50 border border-emerald-200 p-4 text-sm font-medium text-emerald-800 flex items-center gap-2 shadow-xs" role="status">
                <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <span>{{ session('status') }}</span>
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-xl bg-rose-50 border border-rose-200 p-4 text-sm text-rose-800 shadow-xs" role="alert">
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
        <div class="flex flex-col gap-4 border-b border-neutral-200 pb-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="min-w-0">
                <h1 class="text-h2 font-bold text-neutral-950">Meja Operasional Antrean</h1>
                <p class="text-body text-neutral-500 mt-1">
                    Kelola antrean pelanggan secara real-time dengan pemanggilan berurutan (FIFO).
                </p>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">
                @if ($business->usesStations() && $availableCounters->isNotEmpty())
                    <form method="POST" action="{{ route('merchant.queues.select-counter') }}" class="flex items-center gap-2 rounded-lg border border-neutral-300 bg-neutral-100 p-1">
                        @csrf
                        <label for="queues_header_counter_select" class="flex shrink-0 items-center gap-1 pl-2 text-xs font-bold text-neutral-600">
                            <svg class="w-3.5 h-3.5 text-neutral-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            Loket Saya
                        </label>
                        <select
                            id="queues_header_counter_select"
                            name="counter_id"
                            onchange="this.form.submit()"
                            class="h-10 min-w-0 flex-1 rounded-md border-neutral-300 bg-white py-0 pl-2.5 pr-8 text-sm font-semibold text-neutral-900 shadow-2xs focus:border-brand-500 focus:ring-brand-500"
                        >
                            @foreach ($availableCounters as $counterOption)
                                <option value="{{ $counterOption->id }}" @selected($activeCounter?->id === $counterOption->id)>
                                    {{ $counterOption->name }} ({{ $counterOption->code }})
                                </option>
                            @endforeach
                        </select>
                    </form>
                @elseif (! $business->usesStations())
                    <span class="inline-flex h-10 items-center gap-1.5 rounded-lg border border-neutral-200 bg-neutral-100 px-3 text-xs font-bold text-neutral-700">
                        <svg class="size-3.5 text-brand-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        1 Station Utama
                    </span>
                @endif

                {{-- Desktop / tablet actions (mobile uses the thumb-zone dock) --}}
                <div class="hidden items-center gap-3 sm:flex">
                    <button
                        type="button"
                        @click="walkInModalOpen = true"
                        class="inline-flex h-11 items-center gap-2 rounded-lg bg-neutral-900 px-4 text-sm font-semibold text-white shadow-xs transition hover:bg-neutral-800 focus:ring-2 focus:ring-neutral-900 focus:ring-offset-2"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        <span>Tambah Walk-in</span>
                    </button>

                    <form method="POST" action="{{ route('merchant.queues.call-next') }}" x-data="{ submitting: false }" @submit="submitting = true">
                        @csrf
                        @if ($activeCounter)
                            <input type="hidden" name="counter_id" value="{{ $activeCounter->id }}">
                        @endif
                        <button
                            type="submit"
                            @disabled(! $canCallNext)
                            :disabled="submitting || {{ $canCallNext ? 'false' : 'true' }}"
                            class="inline-flex h-11 items-center gap-2 rounded-lg bg-brand-700 px-5 text-sm font-bold text-white shadow-md transition hover:bg-brand-800 focus:ring-2 focus:ring-brand-700 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                            title="{{ $callNextHint }}"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
                            <span>Panggil Berikutnya @if ($activeCounter) ke {{ $activeCounter->name }} @endif</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Now Serving Arena (M7-15) --}}
        <section class="rounded-2xl border-2 p-4 shadow-sm sm:p-6 {{ $nowServing ? 'border-brand-300 bg-linear-to-b from-brand-50/40 to-white' : 'border-neutral-200 bg-white' }}" aria-labelledby="now-serving-heading">
            <div class="mb-5 flex flex-wrap items-center justify-between gap-2 border-b border-neutral-200 pb-4">
                <div class="flex min-w-0 items-center gap-2.5">
                    <span class="flex size-8 shrink-0 items-center justify-center rounded-lg {{ $nowServing ? 'bg-brand-700 text-white' : 'bg-neutral-100 text-neutral-500' }}">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
                    </span>
                    <h2 id="now-serving-heading" class="text-base font-bold tracking-tight text-neutral-900 sm:text-h3">Sedang Berlangsung (Now Serving)</h2>
                </div>
                @if ($nowServing)
                    <x-status-badge :status="$nowServing->status" size="md" />
                @else
                    <span class="rounded-full bg-neutral-100 px-3 py-1 text-caption font-semibold text-neutral-600">
                        Loket Siap Melayani
                    </span>
                @endif
            </div>

            @if ($nowServing)
                <div class="text-center">
                    <span class="mb-2 inline-flex items-center gap-1.5 rounded-full bg-brand-100 px-3 py-1 text-xs font-bold text-brand-900">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        {{ $nowServing->counter?->name ?? $activeCounter?->name ?? 'Loket 1' }}
                    </span>
                    <span class="mb-1 block text-caption font-semibold uppercase tracking-wider text-neutral-500">Nomor Antrean Dipanggil</span>
                    <div class="text-5xl font-extrabold tracking-tight text-neutral-950 tabular-nums sm:text-6xl">
                        {{ $nowServing->ticket_number }}
                    </div>

                    <p class="mt-3 text-lg font-bold text-neutral-900">{{ $nowServing->customer_name }}</p>
                    <div class="mt-2 flex flex-wrap items-center justify-center gap-2 text-sm">
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-white px-2.5 py-1 font-medium text-neutral-800 ring-1 ring-neutral-200">
                            {{ $nowServing->service->name }}
                            <span class="text-neutral-500 tabular-nums">{{ $nowServing->service->duration_minutes }} mnt</span>
                        </span>
                        @if ($nowServing->customer_phone)
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-white px-2.5 py-1 text-neutral-600 ring-1 ring-neutral-200 tabular-nums">
                                <x-customer.icon name="phone" class="size-3.5" />
                                {{ $nowServing->customer_phone }}
                            </span>
                        @endif
                        <span class="rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {{ $sourceClasses($nowServing->source) }}">
                            {{ $sourceLabel($nowServing->source) }}
                        </span>
                    </div>

                    @if ($nowServing->called_at)
                        <div class="mt-3 inline-flex flex-wrap items-center justify-center gap-x-2 gap-y-1 rounded-full border border-blue-200 bg-blue-50 px-3 py-1 text-caption font-medium text-brand-800">
                            <x-customer.icon name="clock" class="size-4" />
                            <span class="tabular-nums">Dipanggil pukul {{ $nowServing->called_at->format('H:i') }}</span>
                            @if ($nowServing->recalled_at)
                                <span class="tabular-nums">(ulang {{ $nowServing->recalled_at->format('H:i') }})</span>
                            @endif
                        </div>
                    @endif

                    {{-- Lifecycle Action Buttons for Active Ticket (M7-17 to M7-21) --}}
                    <div class="mt-5 flex flex-col gap-3 border-t border-neutral-200 pt-5 sm:flex-row sm:items-center sm:justify-between">
                        <div class="grid grid-cols-3 gap-2 sm:flex sm:items-center">
                            @if ($nowServing->status === 'dipanggil')
                                {{-- Recall Button (M7-17) --}}
                                <form method="POST" action="{{ route('merchant.queues.recall', $nowServing) }}">
                                    @csrf
                                    <button type="submit" class="inline-flex h-11 w-full items-center justify-center gap-1.5 rounded-lg border border-neutral-300 bg-white px-3 text-sm font-semibold text-neutral-700 shadow-xs transition hover:bg-neutral-50 sm:px-4">
                                        <svg class="hidden w-4 h-4 text-neutral-500 sm:block" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                        Panggil Ulang
                                    </button>
                                </form>

                                {{-- Skip Button (M7-20) --}}
                                <form method="POST" action="{{ route('merchant.queues.skip', $nowServing) }}" onsubmit="return confirm('Apakah Anda yakin ingin melewati nomor antrean ini?');">
                                    @csrf
                                    <button type="submit" class="inline-flex h-11 w-full items-center justify-center gap-1.5 rounded-lg border border-neutral-300 bg-white px-3 text-sm font-semibold text-neutral-700 shadow-xs transition hover:bg-neutral-50 sm:px-4">
                                        <svg class="hidden w-4 h-4 text-neutral-500 sm:block" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"/></svg>
                                        Lewati
                                    </button>
                                </form>
                            @endif

                            {{-- Cancel Button --}}
                            <button
                                type="button"
                                @click="cancelTicketId = {{ $nowServing->id }}; cancelTicketNumber = '{{ $nowServing->ticket_number }}'; cancelModalOpen = true;"
                                class="inline-flex h-11 items-center justify-center rounded-lg px-3 text-sm font-semibold text-rose-600 transition hover:bg-rose-50 hover:text-rose-800 {{ $nowServing->status === 'dipanggil' ? '' : 'col-span-3 sm:col-span-1' }}"
                            >
                                Batalkan
                            </button>
                        </div>

                        {{-- Primary lifecycle action (on mobile this lives in the thumb-zone dock) --}}
                        <div class="hidden items-center gap-3 sm:flex">
                            @if ($nowServing->status === 'dipanggil')
                                {{-- Start Service Button (M7-18) --}}
                                <form method="POST" action="{{ route('merchant.queues.start-service', $nowServing) }}">
                                    @csrf
                                    <button type="submit" class="inline-flex h-11 items-center gap-2 rounded-lg bg-brand-700 px-6 text-sm font-bold text-white shadow-md transition hover:bg-brand-800">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        Mulai Melayani
                                    </button>
                                </form>
                            @elseif ($nowServing->status === 'sedang_dilayani')
                                {{-- Complete Button (M7-19) --}}
                                <form method="POST" action="{{ route('merchant.queues.complete', $nowServing) }}">
                                    @csrf
                                    <button type="submit" class="inline-flex h-12 items-center gap-2 rounded-lg bg-emerald-600 px-8 text-sm font-bold text-white shadow-md transition hover:bg-emerald-700">
                                        <x-customer.icon name="check" class="size-5" />
                                        Selesaikan Pelayanan
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            @else
                <div class="py-6 text-center sm:py-8">
                    <div class="mx-auto mb-3 flex size-12 items-center justify-center rounded-full bg-neutral-100 text-neutral-400">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"/></svg>
                    </div>
                    <h3 class="text-h3 font-bold text-neutral-800">Meja Operasional Kosong</h3>
                    <p class="mx-auto mt-1 max-w-sm text-caption text-neutral-500">
                        Tekan tombol <strong>"Panggil Berikutnya"</strong> untuk memanggil antrean tertua yang sedang menunggu secara otomatis.
                    </p>
                    <p class="mt-3 inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-3 py-1 text-caption font-semibold text-amber-800 ring-1 ring-amber-600/20 tabular-nums">
                        {{ $stats['menunggu'] }} pelanggan menunggu
                    </p>
                </div>
            @endif
        </section>

        {{-- Status Filter Strip (M7-09): horizontal scroll on mobile, grid on larger screens --}}
        <nav class="-mx-4 overflow-x-auto px-4 sm:mx-0 sm:px-0" aria-label="Filter status antrean">
            <div class="flex snap-x gap-2 sm:grid sm:grid-cols-4 sm:gap-3 lg:grid-cols-7">
                @foreach ($statusTiles as $tile)
                    @php($isSelected = $tile['key'] === null ? empty($selectedStatus) : $selectedStatus === $tile['key'])
                    <a
                        href="{{ route('merchant.queues', array_merge(request()->query(), ['status' => $tile['key'], 'page' => null])) }}"
                        class="min-w-[7.25rem] shrink-0 snap-start rounded-xl border p-3 transition sm:min-w-0 {{ $isSelected ? $tile['active'] : 'border-neutral-200 bg-white hover:bg-neutral-50' }}"
                        @if ($isSelected) aria-current="true" @endif
                    >
                        <span class="block truncate text-caption font-semibold {{ $tile['label_class'] }}">{{ $tile['label'] }}</span>
                        <span class="mt-1 block text-xl font-extrabold tabular-nums {{ $tile['count_class'] }}">{{ $tile['count'] }}</span>
                    </a>
                @endforeach
            </div>
        </nav>

        {{-- Search & Multi-Filter Bar (M7-11, M7-12) --}}
        <div class="rounded-xl border border-neutral-200 bg-white p-4 shadow-xs">
            <form method="GET" action="{{ route('merchant.queues') }}" class="grid grid-cols-1 items-end gap-3 sm:grid-cols-2 lg:grid-cols-6">
                @if ($selectedStatus)
                    <input type="hidden" name="status" value="{{ $selectedStatus }}">
                @endif

                {{-- Search Box --}}
                <div class="sm:col-span-2">
                    <label for="queue_search" class="mb-1 block text-caption font-semibold text-neutral-700">Cari Nomor / Nama</label>
                    <div class="flex gap-2">
                        <div class="relative flex-1">
                            <input
                                id="queue_search"
                                type="search"
                                name="search"
                                value="{{ $search }}"
                                placeholder="Cari nomor tiket atau nama..."
                                class="{{ $controlClasses }} pl-9 pr-3"
                            >
                            <svg class="pointer-events-none absolute left-3 top-3.5 w-4 h-4 text-neutral-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </div>
                        <button
                            type="button"
                            @click="filtersOpen = ! filtersOpen"
                            class="inline-flex h-11 shrink-0 items-center gap-1.5 rounded-lg border px-3 text-sm font-semibold transition sm:hidden"
                            :class="filtersOpen ? 'border-brand-300 bg-brand-50 text-brand-700' : 'border-neutral-300 bg-white text-neutral-700'"
                            :aria-expanded="filtersOpen.toString()"
                            aria-controls="queue-extra-filters"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h18l-7 8v6l-4 2v-8L3 4z"/></svg>
                            Filter
                        </button>
                    </div>
                </div>

                <div id="queue-extra-filters" class="contents">
                    {{-- Counter Filter --}}
                    @if ($business->usesStations())
                        <div class="{{ $hasActiveFilters ? '' : 'max-sm:hidden' }}" :class="{ 'max-sm:hidden': ! filtersOpen }">
                            <label for="queue_counter_filter" class="mb-1 block text-caption font-semibold text-neutral-700">Loket</label>
                            <select id="queue_counter_filter" name="counter_id" class="{{ $controlClasses }}">
                                <option value="">Semua Loket</option>
                                @foreach ($counters as $counterFilter)
                                    <option value="{{ $counterFilter->id }}" @selected((string) $selectedCounterFilter === (string) $counterFilter->id)>
                                        {{ $counterFilter->name }}
                                    </option>
                                @endforeach
                                <option value="unassigned" @selected($selectedCounterFilter === 'unassigned')>Belum ada loket</option>
                            </select>
                        </div>
                    @endif

                    {{-- Service Filter --}}
                    <div class="{{ $hasActiveFilters ? '' : 'max-sm:hidden' }}" :class="{ 'max-sm:hidden': ! filtersOpen }">
                        <label for="queue_service_filter" class="mb-1 block text-caption font-semibold text-neutral-700">Layanan</label>
                        <select id="queue_service_filter" name="service_id" class="{{ $controlClasses }}">
                            <option value="">Semua Layanan</option>
                            @foreach ($services as $srv)
                                <option value="{{ $srv->id }}" @selected($selectedService == $srv->id)>{{ $srv->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Source Filter --}}
                    <div class="{{ $hasActiveFilters ? '' : 'max-sm:hidden' }}" :class="{ 'max-sm:hidden': ! filtersOpen }">
                        <label for="queue_source_filter" class="mb-1 block text-caption font-semibold text-neutral-700">Sumber</label>
                        <select id="queue_source_filter" name="source" class="{{ $controlClasses }}">
                            <option value="">Semua Sumber</option>
                            <option value="online" @selected($selectedSource === 'online')>Online</option>
                            <option value="walk_in" @selected($selectedSource === 'walk_in')>Walk-in</option>
                        </select>
                    </div>

                    {{-- Date / History Filter --}}
                    <div class="{{ $hasActiveFilters ? '' : 'max-sm:hidden' }}" :class="{ 'max-sm:hidden': ! filtersOpen }">
                        <label for="queue_date_filter" class="mb-1 block text-caption font-semibold text-neutral-700">Periode Tanggal</label>
                        <select id="queue_date_filter" name="date" class="{{ $controlClasses }}">
                            <option value="{{ $operatingDate }}" @selected($selectedDate === $operatingDate)>Hari Ini</option>
                            <option value="all" @selected($selectedDate === 'all')>Semua Riwayat</option>
                        </select>
                    </div>
                </div>

                {{-- Action Filter Buttons --}}
                <div class="flex flex-col gap-3 border-t border-neutral-100 pt-3 sm:col-span-2 sm:flex-row sm:items-center sm:justify-between lg:col-span-6">
                    <div class="grid grid-cols-2 gap-2 sm:flex sm:items-center">
                        <button type="submit" class="inline-flex h-11 items-center justify-center rounded-lg bg-brand-700 px-4 text-sm font-bold text-white shadow-xs transition hover:bg-brand-800">
                            Terapkan Filter
                        </button>
                        <a href="{{ route('merchant.queues') }}" class="inline-flex h-11 items-center justify-center rounded-lg border border-neutral-300 px-4 text-sm font-medium text-neutral-700 transition hover:bg-neutral-50">
                            Reset
                        </a>
                    </div>

                    <span class="text-caption text-neutral-500">
                        Menampilkan <strong class="tabular-nums">{{ $tickets->total() }}</strong> tiket antrean
                    </span>
                </div>
            </form>
        </div>

        {{-- Queue List Section --}}
        <section class="overflow-hidden rounded-xl border border-neutral-200 bg-white shadow-xs" aria-labelledby="queue-list-heading">
            <div class="border-b border-neutral-200 p-4 sm:p-5">
                <h3 id="queue-list-heading" class="text-h3 font-bold text-neutral-900">Daftar Antrean</h3>
                <p class="text-caption text-neutral-500">
                    {{ $selectedDate === $operatingDate ? 'Antrean sesi operasional hari ini.' : 'Riwayat arsip antrean usaha.' }}
                </p>
            </div>

            @if ($tickets->isEmpty())
                <div class="p-10 text-center sm:p-12">
                    <div class="mx-auto mb-3 flex size-12 items-center justify-center rounded-full bg-neutral-100 text-neutral-400">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                    </div>
                    <h3 class="text-h3 font-semibold text-neutral-900">Tidak Ada Antrean Ditemukan</h3>
                    <p class="mx-auto mt-1 max-w-sm text-caption text-neutral-500">
                        @if ($hasActiveFilters)
                            Tidak ada tiket yang sesuai dengan filter pencarian Anda.
                        @else
                            Belum ada tiket antrean yang tercatat pada sesi ini.
                        @endif
                    </p>
                </div>
            @else
                {{-- Table View: tablet & desktop (M7-10, M7-14) --}}
                <div class="hidden overflow-x-auto sm:block">
                    <table class="w-full border-collapse text-left">
                        <thead>
                            <tr class="border-b border-neutral-200 bg-neutral-50/70 text-caption font-semibold text-neutral-600">
                                <th scope="col" class="px-4 py-3">No. Tiket</th>
                                <th scope="col" class="px-4 py-3">Pelanggan</th>
                                <th scope="col" class="px-4 py-3">Layanan</th>
                                <th scope="col" class="hidden px-4 py-3 lg:table-cell">Sumber</th>
                                <th scope="col" class="hidden px-4 py-3 md:table-cell">Dibuat</th>
                                <th scope="col" class="px-4 py-3">Status</th>
                                <th scope="col" class="px-4 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-100 text-body">
                            @foreach ($tickets as $t)
                                <tr class="transition hover:bg-neutral-50/80 {{ in_array($t->status, ['dipanggil', 'sedang_dilayani']) ? 'bg-brand-50/30' : '' }}">
                                    <td class="px-4 py-3.5 align-top">
                                        <a href="{{ route('merchant.queues.show', $t) }}" class="font-bold text-neutral-950 tabular-nums hover:text-brand-700">
                                            {{ $t->ticket_number }}
                                        </a>
                                        @if ($t->counter)
                                            <span class="mt-1 block w-fit rounded border border-neutral-200 bg-neutral-100 px-1.5 py-0.5 text-[10px] font-semibold text-neutral-700" title="Dilayani di {{ $t->counter->name }}">
                                                {{ $t->counter->name }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3.5 align-top">
                                        <div class="font-medium text-neutral-900">{{ $t->customer_name }}</div>
                                        @if ($t->customer_phone)
                                            <div class="text-[11px] text-neutral-500 tabular-nums">{{ $t->customer_phone }}</div>
                                        @endif
                                        <span class="mt-1 inline-flex rounded px-1.5 py-0.5 text-[10px] font-semibold ring-1 ring-inset lg:hidden {{ $sourceClasses($t->source) }}">{{ $sourceLabel($t->source) }}</span>
                                    </td>
                                    <td class="px-4 py-3.5 align-top text-neutral-700">
                                        <div>{{ $t->service->name }}</div>
                                        @if ($t->bookingSlotWindowLabel())
                                            <span class="mt-1 inline-flex items-center rounded border border-brand-200 bg-brand-50 px-1.5 py-0.5 text-[10px] font-semibold text-brand-700 tabular-nums">
                                                Slot {{ $t->bookingSlotWindowLabel() }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="hidden px-4 py-3.5 align-top lg:table-cell">
                                        <span class="inline-flex rounded px-2 py-0.5 text-[11px] font-semibold ring-1 ring-inset {{ $sourceClasses($t->source) }}">{{ $sourceLabel($t->source) }}</span>
                                    </td>
                                    <td class="hidden px-4 py-3.5 align-top text-caption text-neutral-500 tabular-nums md:table-cell">
                                        {{ $t->created_at->format('H:i') }}
                                        @if ($selectedDate === 'all')
                                            <span class="block text-[11px] text-neutral-400">{{ $t->created_at->format('d/m/y') }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3.5 align-top">
                                        <x-status-badge :status="$t->status" size="sm" />
                                    </td>
                                    <td class="px-4 py-3.5 text-right align-top">
                                        <div class="inline-flex items-center justify-end gap-1.5">
                                            <a href="{{ route('merchant.queues.show', $t) }}" class="inline-flex h-9 items-center rounded-md px-2.5 text-xs font-medium text-neutral-600 transition hover:bg-neutral-100 hover:text-neutral-900">
                                                Detail
                                            </a>

                                            {{-- Status-Appropriate Actions (M7-21) --}}
                                            @if ($t->status === 'menunggu')
                                                <button
                                                    type="button"
                                                    @click="cancelTicketId = {{ $t->id }}; cancelTicketNumber = '{{ $t->ticket_number }}'; cancelModalOpen = true;"
                                                    class="inline-flex h-9 items-center rounded-md px-2.5 text-xs font-medium text-rose-600 transition hover:bg-rose-50 hover:text-rose-800"
                                                >
                                                    Batalkan
                                                </button>
                                            @elseif ($t->status === 'dipanggil')
                                                <form method="POST" action="{{ route('merchant.queues.recall', $t) }}">
                                                    @csrf
                                                    <button type="submit" class="inline-flex size-9 items-center justify-center rounded-md text-neutral-500 transition hover:bg-neutral-100 hover:text-neutral-900" title="Panggil Ulang" aria-label="Panggil ulang {{ $t->ticket_number }}">
                                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                                    </button>
                                                </form>
                                                <form method="POST" action="{{ route('merchant.queues.start-service', $t) }}">
                                                    @csrf
                                                    <button type="submit" class="inline-flex h-9 items-center rounded-md bg-brand-700 px-3 text-xs font-bold text-white transition hover:bg-brand-800">
                                                        Layani
                                                    </button>
                                                </form>
                                            @elseif ($t->status === 'sedang_dilayani')
                                                <form method="POST" action="{{ route('merchant.queues.complete', $t) }}">
                                                    @csrf
                                                    <button type="submit" class="inline-flex h-9 items-center rounded-md bg-emerald-600 px-3 text-xs font-bold text-white transition hover:bg-emerald-700">
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

                {{-- Card View: mobile 390px (M7-14) --}}
                <ul class="divide-y divide-neutral-100 sm:hidden">
                    @foreach ($tickets as $t)
                        <li class="flex flex-col gap-3 p-4 {{ in_array($t->status, ['dipanggil', 'sedang_dilayani']) ? 'bg-brand-50/30' : '' }}">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <a href="{{ route('merchant.queues.show', $t) }}" class="text-xl font-extrabold text-neutral-950 tabular-nums">{{ $t->ticket_number }}</a>
                                    <p class="truncate text-sm font-semibold text-neutral-900">{{ $t->customer_name }}</p>
                                </div>
                                <x-status-badge :status="$t->status" size="sm" class="shrink-0" />
                            </div>

                            <div class="flex flex-wrap gap-1.5 text-[11px] font-medium">
                                <span class="rounded-md bg-neutral-100 px-2 py-1 text-neutral-700">{{ $t->service->name }}</span>
                                @if ($t->bookingSlotWindowLabel())
                                    <span class="rounded-md border border-brand-200 bg-brand-50 px-2 py-1 font-semibold text-brand-700 tabular-nums">Slot {{ $t->bookingSlotWindowLabel() }}</span>
                                @endif
                                <span class="rounded-md px-2 py-1 ring-1 ring-inset {{ $sourceClasses($t->source) }}">{{ $sourceLabel($t->source) }}</span>
                                <span class="rounded-md bg-neutral-100 px-2 py-1 text-neutral-600 tabular-nums">Dibuat {{ $t->created_at->format($selectedDate === 'all' ? 'd/m H:i' : 'H:i') }}</span>
                                @if ($t->counter)
                                    <span class="rounded-md border border-neutral-200 bg-white px-2 py-1 text-neutral-700">{{ $t->counter->name }}</span>
                                @endif
                            </div>

                            <div class="grid grid-cols-2 gap-2">
                                <a href="{{ route('merchant.queues.show', $t) }}" class="inline-flex h-11 items-center justify-center rounded-lg border border-neutral-300 bg-white text-sm font-semibold text-neutral-700">
                                    Detail Tiket
                                </a>

                                @if ($t->status === 'menunggu')
                                    <button
                                        type="button"
                                        @click="cancelTicketId = {{ $t->id }}; cancelTicketNumber = '{{ $t->ticket_number }}'; cancelModalOpen = true;"
                                        class="inline-flex h-11 items-center justify-center rounded-lg bg-rose-50 text-sm font-semibold text-rose-700"
                                    >
                                        Batalkan
                                    </button>
                                @elseif ($t->status === 'dipanggil')
                                    <form method="POST" action="{{ route('merchant.queues.start-service', $t) }}">
                                        @csrf
                                        <button type="submit" class="inline-flex h-11 w-full items-center justify-center rounded-lg bg-brand-700 text-sm font-bold text-white">
                                            Mulai Layani
                                        </button>
                                    </form>
                                @elseif ($t->status === 'sedang_dilayani')
                                    <form method="POST" action="{{ route('merchant.queues.complete', $t) }}">
                                        @csrf
                                        <button type="submit" class="inline-flex h-11 w-full items-center justify-center rounded-lg bg-emerald-600 text-sm font-bold text-white">
                                            Selesaikan
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>

                {{-- Pagination (M7-13) --}}
                <div class="border-t border-neutral-200 p-4">
                    {{ $tickets->links() }}
                </div>
            @endif
        </section>

        {{-- Mobile Thumb-Zone Action Dock --}}
        <div
            class="fixed inset-x-0 bottom-0 z-30 border-t border-neutral-200 bg-white/95 px-4 pt-3 shadow-[0_-8px_24px_-12px_rgba(15,23,42,0.25)] backdrop-blur sm:hidden"
            style="padding-bottom: max(0.75rem, env(safe-area-inset-bottom));"
            data-testid="queue-thumb-dock"
            aria-label="Kontrol antrean kasir"
        >
            @if ($nowServing)
                <div class="mb-2 flex items-center justify-between gap-2 text-xs">
                    <span class="truncate text-neutral-600">
                        <span class="font-extrabold text-neutral-950 tabular-nums">{{ $nowServing->ticket_number }}</span>
                        {{ $nowServing->customer_name }}
                    </span>
                    <x-status-badge :status="$nowServing->status" size="sm" class="shrink-0" />
                </div>
            @else
                <p class="mb-2 text-xs text-neutral-600">
                    <span class="font-bold text-neutral-900 tabular-nums">{{ $stats['menunggu'] }}</span> menunggu
                    @if ($activeCounter)
                        di antrean, memanggil ke <span class="font-semibold text-neutral-900">{{ $activeCounter->name }}</span>
                    @endif
                </p>
            @endif

            <div class="grid grid-cols-[auto_1fr] gap-2">
                <button
                    type="button"
                    @click="walkInModalOpen = true"
                    class="inline-flex h-12 items-center justify-center gap-1.5 rounded-xl border border-neutral-300 bg-white px-4 text-sm font-semibold text-neutral-800"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Walk-in
                </button>

                @if ($nowServing && $nowServing->status === 'dipanggil')
                    <form method="POST" action="{{ route('merchant.queues.start-service', $nowServing) }}">
                        @csrf
                        <button type="submit" class="inline-flex h-12 w-full items-center justify-center gap-2 rounded-xl bg-brand-700 text-base font-bold text-white shadow-md">
                            Mulai Melayani {{ $nowServing->ticket_number }}
                        </button>
                    </form>
                @elseif ($nowServing && $nowServing->status === 'sedang_dilayani')
                    <form method="POST" action="{{ route('merchant.queues.complete', $nowServing) }}">
                        @csrf
                        <button type="submit" class="inline-flex h-12 w-full items-center justify-center gap-2 rounded-xl bg-emerald-600 text-base font-bold text-white shadow-md">
                            <x-customer.icon name="check" class="size-5" />
                            Selesaikan {{ $nowServing->ticket_number }}
                        </button>
                    </form>
                @else
                    <form method="POST" action="{{ route('merchant.queues.call-next') }}" x-data="{ submitting: false }" @submit="submitting = true">
                        @csrf
                        @if ($activeCounter)
                            <input type="hidden" name="counter_id" value="{{ $activeCounter->id }}">
                        @endif
                        <button
                            type="submit"
                            @disabled(! $canCallNext)
                            :disabled="submitting || {{ $canCallNext ? 'false' : 'true' }}"
                            class="inline-flex h-12 w-full items-center justify-center gap-2 rounded-xl bg-brand-700 text-base font-bold text-white shadow-md disabled:cursor-not-allowed disabled:bg-neutral-300 disabled:text-neutral-600 disabled:shadow-none"
                        >
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
                            {{ $canCallNext ? 'Panggil Berikutnya' : 'Tidak Ada Antrean' }}
                        </button>
                    </form>
                @endif
            </div>
        </div>

        <x-merchant.walk-in-modal :business="$business" :services="$services" />

        {{-- Cancellation Reason Modal --}}
        <div
            x-show="cancelModalOpen"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-50 flex items-end justify-center overflow-y-auto bg-neutral-950/60 sm:items-center sm:p-4"
            style="display: none;"
            @keydown.escape.window="cancelModalOpen = false"
            role="dialog"
            aria-modal="true"
        >
            <div
                @click.outside="cancelModalOpen = false"
                class="relative w-full max-w-md rounded-t-2xl border border-neutral-200 bg-white p-5 shadow-2xl sm:rounded-2xl sm:p-6"
            >
                <div class="mb-4 flex items-center justify-between border-b border-neutral-100 pb-3">
                    <h3 class="text-h3 font-bold text-neutral-900">Batalkan Antrean <span x-text="cancelTicketNumber" class="text-rose-600 tabular-nums"></span></h3>
                    <button type="button" @click="cancelModalOpen = false" class="-mr-2 flex size-11 items-center justify-center rounded-lg text-neutral-400 hover:bg-neutral-100 hover:text-neutral-600" aria-label="Tutup">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <form :action="'{{ url('/umkm/queues') }}/' + cancelTicketId + '/cancel'" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label for="cancel_reason" class="mb-1 block text-caption font-semibold text-neutral-700">
                            Alasan Pembatalan <span class="text-rose-500">*</span>
                        </label>
                        <textarea
                            id="cancel_reason"
                            name="reason"
                            required
                            rows="3"
                            maxlength="255"
                            placeholder="Tuliskan alasan pembatalan tiket antrean ini..."
                            class="w-full rounded-lg border-neutral-300 text-sm focus:border-rose-500 focus:ring-rose-500"
                        ></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-2 border-t border-neutral-100 pt-3 sm:flex sm:justify-end">
                        <button type="button" @click="cancelModalOpen = false" class="inline-flex h-11 items-center justify-center rounded-lg border border-neutral-300 px-4 text-sm font-semibold text-neutral-700 transition hover:bg-neutral-50">
                            Tutup
                        </button>
                        <button type="submit" class="inline-flex h-11 items-center justify-center rounded-lg bg-rose-600 px-4 text-sm font-bold text-white shadow-xs transition hover:bg-rose-700">
                            Konfirmasi Batalkan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <x-merchant.realtime-toasts :business-id="$business->id" :counter-id="$activeCounter?->id" />
</x-layouts.merchant>
