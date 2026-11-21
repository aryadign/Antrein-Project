@php
    use App\Models\QueueTicket;

    $canCallNext = ! $nowServing && $stats['menunggu'] > 0;
    $callNextHint = $nowServing
        ? 'Selesaikan atau lewati tiket aktif terlebih dahulu'
        : ($stats['menunggu'] === 0 ? 'Tidak ada tiket menunggu' : 'Panggil nomor antrean berikutnya');

    /** @var array<string, int> $statusCounts counts for today's session, keyed by ticket status */
    $statusCounts = [
        QueueTicket::STATUS_MENUNGGU => $stats['menunggu'],
        QueueTicket::STATUS_DIPANGGIL => $stats['dipanggil'],
        QueueTicket::STATUS_SEDANG_DILAYANI => $stats['sedang_dilayani'],
        QueueTicket::STATUS_SELESAI => $stats['selesai'],
        QueueTicket::STATUS_DILEWATI => max(0, $stats['dilewati'] - ($stats['tidak_hadir'] ?? 0)),
        QueueTicket::STATUS_TIDAK_HADIR => $stats['tidak_hadir'] ?? 0,
        QueueTicket::STATUS_DIBATALKAN => $stats['dibatalkan'],
    ];
    $primaryStatusKeys = [QueueTicket::STATUS_MENUNGGU, QueueTicket::STATUS_DIPANGGIL, QueueTicket::STATUS_SEDANG_DILAYANI, QueueTicket::STATUS_SELESAI];
    $statusTabs = [['key' => null, 'label' => 'Semua', 'count' => $stats['total']]];
    foreach ($primaryStatusKeys as $statusKey) {
        $statusTabs[] = ['key' => $statusKey, 'label' => QueueTicket::labelForStatus($statusKey), 'count' => $statusCounts[$statusKey]];
    }

    $hasAdvancedFilters = $selectedService || $selectedSource || $selectedCounterFilter
        || $selectedDate !== $operatingDate
        || ($selectedStatus && ! in_array($selectedStatus, $primaryStatusKeys, true));
    $hasActiveFilters = $search || $selectedStatus || $hasAdvancedFilters;
    $advancedFilterCount = collect([$selectedService, $selectedSource, $selectedCounterFilter, $selectedDate !== $operatingDate ? $selectedDate : null])->filter()->count()
        + (($selectedStatus && ! in_array($selectedStatus, $primaryStatusKeys, true)) ? 1 : 0);
    $controlClasses = 'h-11 w-full rounded-lg border border-neutral-300 bg-white px-3 text-sm text-neutral-900 focus:border-brand-500 focus:ring-brand-500';
    $megaphonePath = 'M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z';
@endphp

<x-layouts.merchant
    title="Meja Operasional Antrean"
    :businessName="$business->name"
    :businessStatus="$business->status"
    :businessSlug="$business->slug"
>
    <div class="space-y-5 pb-40 sm:space-y-6 sm:pb-0" x-data="{ walkInModalOpen: false, cancelModalOpen: false, cancelTicketId: null, cancelTicketNumber: '', filtersOpen: {{ $hasAdvancedFilters ? 'true' : 'false' }} }">
        {{-- Flash Messages --}}
        @if (session('status'))
            <div class="flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-800 shadow-card" role="status">
                <svg class="size-5 shrink-0 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <span>{{ session('status') }}</span>
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800 shadow-card" role="alert">
                <div class="flex items-start gap-2">
                    <svg class="mt-0.5 size-5 shrink-0 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <div>
                        <p class="mb-1 font-semibold">Aksi belum berhasil:</p>
                        <ul class="list-inside list-disc space-y-0.5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        {{-- Page Header --}}
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div class="min-w-0">
                <h1 class="text-[1.375rem] leading-[1.875rem] font-bold sm:text-[1.75rem] sm:leading-9 text-neutral-950">Meja Operasional Antrean</h1>
                <p class="mt-1 max-w-xl text-body text-pretty text-neutral-600">
                    Panggil pelanggan sesuai urutan. Tiket dari ponsel dan yang datang langsung masuk ke satu antrean yang sama.
                </p>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center lg:shrink-0">
                @if ($business->usesStations() && $availableCounters->isNotEmpty())
                    <form method="POST" action="{{ route('merchant.queues.select-counter') }}" class="flex min-w-0 items-center gap-2 rounded-lg border border-neutral-200 bg-white p-1 pl-3 shadow-card sm:shrink-0">
                        @csrf
                        <label for="queues_header_counter_select" class="shrink-0 text-sm font-medium text-neutral-600">Loket saya</label>
                        <select
                            id="queues_header_counter_select"
                            name="counter_id"
                            onchange="this.form.submit()"
                            class="h-10 min-w-52 flex-1 rounded-md border border-neutral-200 bg-neutral-50 py-0 pl-2.5 pr-8 text-sm font-semibold text-neutral-900 focus:border-brand-500 focus:ring-brand-500"
                        >
                            @foreach ($availableCounters as $counterOption)
                                <option value="{{ $counterOption->id }}" @selected($activeCounter?->id === $counterOption->id)>
                                    {{ $counterOption->name }} ({{ $counterOption->code }})
                                </option>
                            @endforeach
                        </select>
                    </form>
                @elseif (! $business->usesStations())
                    <span class="inline-flex h-11 items-center gap-1.5 rounded-lg border border-neutral-200 bg-white px-3 text-sm font-medium text-neutral-700 shadow-card">
                        <svg class="size-4 text-brand-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        1 Loket Utama
                    </span>
                @endif

                {{-- Desktop / tablet secondary action (on phones it sits in the thumb dock) --}}
                <div class="hidden sm:block">
                    <x-button variant="outline" class="whitespace-nowrap" @click="walkInModalOpen = true">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                        Tambah Datang Langsung
                    </x-button>
                </div>
            </div>
        </div>

        {{-- Now serving: the one primary object on this screen --}}
        <x-merchant.now-serving
            :ticket="$nowServing"
            :counter="$activeCounter"
            :waiting-count="$stats['menunggu']"
            :can-call-next="$canCallNext"
            :call-next-hint="$callNextHint"
        />

        {{-- Queue list with status tabs, search and progressive filters --}}
        <section class="overflow-hidden rounded-xl border border-neutral-200/80 bg-white shadow-card" aria-labelledby="queue-list-heading">
            <div class="space-y-4 border-b border-neutral-200/80 p-4 sm:px-5">
                <form method="GET" action="{{ route('merchant.queues') }}" class="space-y-4">
                    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <div>
                            <h2 id="queue-list-heading" class="text-h3 font-bold text-neutral-900">Daftar Antrean</h2>
                            <p class="text-sm text-neutral-600">
                                {{ $selectedDate === $operatingDate ? 'Sesi hari ini' : 'Semua riwayat antrean' }},
                                <span class="tabular-nums">{{ $tickets->total() }}</span> tiket ditampilkan
                            </p>
                        </div>

                        <div class="flex gap-2 md:w-[26rem]">
                            <div class="relative flex-1">
                                <label for="queue_search" class="sr-only">Cari nomor tiket atau nama</label>
                                <input
                                    id="queue_search"
                                    type="search"
                                    name="search"
                                    value="{{ $search }}"
                                    placeholder="Cari nomor atau nama"
                                    class="placeholder:text-neutral-500 {{ $controlClasses }} pl-9 pr-3 placeholder:text-neutral-500"
                                >
                                <svg class="pointer-events-none absolute left-3 top-3.5 size-4 text-neutral-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            </div>
                            <button
                                type="button"
                                @click="filtersOpen = ! filtersOpen"
                                class="inline-flex h-11 shrink-0 items-center gap-1.5 rounded-lg border px-3 text-sm font-semibold transition-colors duration-150 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 {{ $hasAdvancedFilters ? 'border-brand-300 bg-brand-50 text-brand-800' : 'border-neutral-300 bg-white text-neutral-700 hover:bg-neutral-50' }}"
                                :class="filtersOpen ? 'border-brand-300! bg-brand-50! text-brand-800!' : 'border-neutral-300! bg-white! text-neutral-700!'"
                                :aria-expanded="filtersOpen.toString()"
                                aria-controls="queue-extra-filters"
                            >
                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4h18l-7 8v6l-4 2v-8L3 4z"/></svg>
                                Filter
                                @if ($advancedFilterCount > 0)
                                    <span class="flex size-5 items-center justify-center rounded-full bg-brand-700 text-xs font-bold text-white tabular-nums">{{ $advancedFilterCount }}</span>
                                @endif
                            </button>
                        </div>
                    </div>

                    <div
                        id="queue-extra-filters"
                        x-show="filtersOpen"
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 -translate-y-1"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        @unless ($hasAdvancedFilters) style="display: none;" @endunless
                        class="rounded-lg border border-neutral-200 bg-neutral-50/70 p-3 sm:p-4"
                    >
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-5">
                            <div>
                                <label for="queue_status_filter" class="mb-1 block text-sm font-medium text-neutral-700">Status</label>
                                <select id="queue_status_filter" name="status" class="{{ $controlClasses }}">
                                    <option value="">Semua status</option>
                                    @foreach ($statusCounts as $statusKey => $statusCount)
                                        <option value="{{ $statusKey }}" @selected($selectedStatus === $statusKey)>{{ QueueTicket::labelForStatus($statusKey) }} ({{ $statusCount }})</option>
                                    @endforeach
                                </select>
                            </div>

                            @if ($business->usesStations())
                                <div>
                                    <label for="queue_counter_filter" class="mb-1 block text-sm font-medium text-neutral-700">Loket</label>
                                    <select id="queue_counter_filter" name="counter_id" class="{{ $controlClasses }}">
                                        <option value="">Semua loket</option>
                                        @foreach ($counters as $counterFilter)
                                            <option value="{{ $counterFilter->id }}" @selected((string) $selectedCounterFilter === (string) $counterFilter->id)>{{ $counterFilter->name }}</option>
                                        @endforeach
                                        <option value="unassigned" @selected($selectedCounterFilter === 'unassigned')>Belum ada loket</option>
                                    </select>
                                </div>
                            @endif

                            <div>
                                <label for="queue_service_filter" class="mb-1 block text-sm font-medium text-neutral-700">Layanan</label>
                                <select id="queue_service_filter" name="service_id" class="{{ $controlClasses }}">
                                    <option value="">Semua layanan</option>
                                    @foreach ($services as $srv)
                                        <option value="{{ $srv->id }}" @selected($selectedService == $srv->id)>{{ $srv->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label for="queue_source_filter" class="mb-1 block text-sm font-medium text-neutral-700">Cara datang</label>
                                <select id="queue_source_filter" name="source" class="{{ $controlClasses }}">
                                    <option value="">Semua</option>
                                    <option value="online" @selected($selectedSource === 'online')>Dari ponsel</option>
                                    <option value="walk_in" @selected($selectedSource === 'walk_in')>Datang langsung</option>
                                </select>
                            </div>

                            <div>
                                <label for="queue_date_filter" class="mb-1 block text-sm font-medium text-neutral-700">Periode</label>
                                <select id="queue_date_filter" name="date" class="{{ $controlClasses }}">
                                    <option value="{{ $operatingDate }}" @selected($selectedDate === $operatingDate)>Hari ini</option>
                                    <option value="all" @selected($selectedDate === 'all')>Semua riwayat</option>
                                </select>
                            </div>
                        </div>

                        <div class="mt-3 flex flex-wrap items-center gap-2">
                            <x-button type="submit" size="sm">Terapkan Filter</x-button>
                            @if ($hasActiveFilters)
                                <x-button variant="ghost" size="sm" :href="route('merchant.queues')">Hapus semua filter</x-button>
                            @endif
                        </div>
                    </div>
                </form>

                {{-- Status tabs: the four statuses an operator checks during service; the rest live in the Filter panel --}}
                <nav class="-mx-4 overflow-x-auto px-4 sm:mx-0 sm:px-0" aria-label="Saring status antrean">
                    <ul class="flex w-max gap-1.5 sm:w-auto sm:flex-wrap">
                        @foreach ($statusTabs as $tab)
                            @php($isSelected = $tab['key'] === null ? empty($selectedStatus) : $selectedStatus === $tab['key'])
                            <li>
                                <a
                                    href="{{ route('merchant.queues', array_merge(request()->query(), ['status' => $tab['key'], 'page' => null])) }}"
                                    @class([
                                        'inline-flex h-9 items-center gap-2 rounded-full border px-3.5 text-sm font-medium whitespace-nowrap transition-colors duration-150 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500',
                                        'border-brand-700 bg-brand-700 text-white shadow-card' => $isSelected,
                                        'border-neutral-200 bg-white text-neutral-700 hover:border-neutral-300 hover:bg-neutral-50' => ! $isSelected,
                                    ])
                                    @if ($isSelected) aria-current="true" @endif
                                >
                                    {{ $tab['label'] }}
                                    <span @class([
                                        'min-w-6 rounded-full px-1.5 text-center text-xs font-semibold tabular-nums',
                                        'bg-white/20 text-white' => $isSelected,
                                        'bg-neutral-100 text-neutral-700' => ! $isSelected,
                                    ])>{{ $tab['count'] }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </nav>
            </div>

            @if ($tickets->isEmpty())
                <div class="px-6 py-12 text-center">
                    <div class="mx-auto mb-3 flex size-12 items-center justify-center rounded-full bg-neutral-100 text-neutral-500" aria-hidden="true">
                        <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                    </div>
                    <h3 class="text-h3 font-semibold text-neutral-900">{{ $hasActiveFilters ? 'Tidak ada tiket yang cocok' : 'Belum ada tiket hari ini' }}</h3>
                    <p class="mx-auto mt-1 max-w-sm text-sm text-pretty text-neutral-600">
                        @if ($hasActiveFilters)
                            Ubah kata kunci atau filter, atau <a href="{{ route('merchant.queues') }}" class="font-semibold text-brand-700 underline-offset-4 hover:underline">hapus semua filter</a>.
                        @else
                            Tiket dari ponsel pelanggan muncul otomatis. Untuk pelanggan yang datang langsung, tekan Tambah Datang Langsung.
                        @endif
                    </p>
                </div>
            @else
                {{-- Table view: tablet & desktop --}}
                <div class="hidden overflow-x-auto sm:block">
                    <table class="w-full border-collapse text-left">
                        <thead>
                            <tr class="border-b border-neutral-200 bg-neutral-50 text-sm font-semibold text-neutral-600">
                                <th scope="col" class="px-4 py-3 sm:pl-5">Nomor</th>
                                <th scope="col" class="px-4 py-3">Pelanggan</th>
                                <th scope="col" class="px-4 py-3">Layanan</th>
                                <th scope="col" class="hidden px-4 py-3 md:table-cell">Dibuat</th>
                                <th scope="col" class="px-4 py-3">Status</th>
                                <th scope="col" class="px-4 py-3 text-right sm:pr-5"><span class="sr-only">Aksi</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-100 text-sm">
                            @foreach ($tickets as $t)
                                <tr @class([
                                    'transition-colors duration-150 hover:bg-neutral-50/80',
                                    'bg-brand-50/50' => $t->isActive() && $t->status !== QueueTicket::STATUS_MENUNGGU,
                                ])>
                                    <td class="px-4 py-3.5 align-top sm:pl-5">
                                        <a href="{{ route('merchant.queues.show', $t) }}" class="text-base font-bold text-neutral-950 tabular-nums underline-offset-4 hover:text-brand-700 hover:underline">
                                            {{ $t->ticket_number }}
                                        </a>
                                        @if ($t->counter)
                                            <span class="mt-1 block whitespace-nowrap text-xs text-neutral-600">{{ $t->counter->name }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3.5 align-top">
                                        <div class="font-medium text-neutral-900">{{ $t->customer_name }}</div>
                                        <div class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1">
                                            <x-merchant.source-badge :source="$t->source" />
                                            @if ($t->customer_phone)
                                                <span class="text-xs text-neutral-600 tabular-nums">{{ $t->customer_phone }}</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-4 py-3.5 align-top text-neutral-800">
                                        <div>{{ $t->service->name }}</div>
                                        @if ($t->bookingSlotWindowLabel())
                                            <span class="mt-1 block text-xs text-neutral-600 tabular-nums">Slot {{ $t->bookingSlotWindowLabel() }}</span>
                                        @endif
                                    </td>
                                    <td class="hidden px-4 py-3.5 align-top text-neutral-600 tabular-nums md:table-cell">
                                        {{ $t->created_at->format('H:i') }}
                                        @if ($selectedDate === 'all')
                                            <span class="block text-xs text-neutral-600">{{ $t->created_at->format('d/m/y') }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3.5 align-top">
                                        <x-status-badge :status="$t" size="sm" />
                                    </td>
                                    <td class="px-4 py-3 text-right align-top sm:pr-5">
                                        <div class="inline-flex items-center justify-end gap-1">
                                            @if ($t->status === QueueTicket::STATUS_MENUNGGU)
                                                <x-button
                                                    variant="ghost"
                                                    size="sm"
                                                    class="text-neutral-600 hover:bg-rose-50 hover:text-rose-800"
                                                    @click="cancelTicketId = {{ $t->id }}; cancelTicketNumber = '{{ $t->ticket_number }}'; cancelModalOpen = true;"
                                                    aria-label="Batalkan {{ $t->ticket_number }}"
                                                >Batalkan</x-button>
                                            @elseif ($t->status === QueueTicket::STATUS_DIPANGGIL)
                                                <form method="POST" action="{{ route('merchant.queues.start-service', $t) }}">
                                                    @csrf
                                                    <x-button type="submit" size="sm">Layani</x-button>
                                                </form>
                                            @elseif ($t->status === QueueTicket::STATUS_SEDANG_DILAYANI)
                                                <form method="POST" action="{{ route('merchant.queues.complete', $t) }}">
                                                    @csrf
                                                    <x-button type="submit" size="sm" class="bg-emerald-700! hover:bg-emerald-800!">Selesai</x-button>
                                                </form>
                                            @endif
                                            <x-button variant="ghost" size="sm" :href="route('merchant.queues.show', $t)" aria-label="Detail {{ $t->ticket_number }}">Detail</x-button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Card view: phones --}}
                <ul class="divide-y divide-neutral-100 sm:hidden">
                    @foreach ($tickets as $t)
                        <li @class([
                            'p-4',
                            'bg-brand-50/50' => $t->isActive() && $t->status !== QueueTicket::STATUS_MENUNGGU,
                        ])>
                            <a href="{{ route('merchant.queues.show', $t) }}" class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-xl font-extrabold text-neutral-950 tabular-nums">{{ $t->ticket_number }}</p>
                                    <p class="truncate text-sm font-semibold text-neutral-900">{{ $t->customer_name }}</p>
                                    <p class="mt-0.5 truncate text-sm text-neutral-600">
                                        {{ $t->service->name }}, <span class="tabular-nums">{{ $t->created_at->format($selectedDate === 'all' ? 'd/m H:i' : 'H:i') }}</span>
                                    </p>
                                </div>
                                <x-status-badge :status="$t" size="sm" class="shrink-0" />
                            </a>
                            <div class="mt-2 flex flex-wrap items-center gap-1.5">
                                <x-merchant.source-badge :source="$t->source" />
                                @if ($t->bookingSlotWindowLabel())
                                    <span class="rounded-full bg-neutral-100 px-2 py-0.5 text-xs font-medium text-neutral-700 tabular-nums">Slot {{ $t->bookingSlotWindowLabel() }}</span>
                                @endif
                                @if ($t->counter)
                                    <span class="rounded-full bg-neutral-100 px-2 py-0.5 text-xs font-medium text-neutral-700">{{ $t->counter->name }}</span>
                                @endif
                            </div>
                            @if ($t->status === QueueTicket::STATUS_MENUNGGU)
                                <div class="mt-3">
                                    <button
                                        type="button"
                                        @click="cancelTicketId = {{ $t->id }}; cancelTicketNumber = '{{ $t->ticket_number }}'; cancelModalOpen = true;"
                                        class="inline-flex h-11 items-center rounded-lg px-1 text-sm font-semibold text-rose-700"
                                    >
                                        Batalkan tiket
                                    </button>
                                </div>
                            @endif
                        </li>
                    @endforeach
                </ul>

                <div class="border-t border-neutral-200 p-4">
                    {{ $tickets->links() }}
                </div>
            @endif
        </section>

        {{-- Mobile thumb-zone action dock: the dominant action is always within reach --}}
        <div
            class="fixed inset-x-0 bottom-0 z-30 border-t border-neutral-200 bg-white px-4 pt-3 shadow-overlay sm:hidden"
            style="padding-bottom: max(0.75rem, env(safe-area-inset-bottom));"
            data-testid="queue-thumb-dock"
            aria-label="Kontrol antrean loket"
        >
            <p class="mb-2 truncate text-sm text-neutral-600">
                @if ($nowServing)
                    <span class="font-bold text-neutral-950 tabular-nums">{{ $nowServing->ticket_number }}</span>
                    {{ $nowServing->customer_name }}
                @else
                    <span class="font-bold text-neutral-950 tabular-nums">{{ $stats['menunggu'] }}</span> menunggu{{ $activeCounter ? ', dipanggil ke' : '' }}
                    @if ($activeCounter)
                        <span class="font-semibold text-neutral-900">{{ $activeCounter->name }}</span>
                    @endif
                @endif
            </p>

            <div class="grid grid-cols-[auto_1fr] gap-2">
                <button
                    type="button"
                    @click="walkInModalOpen = true"
                    class="inline-flex h-12 items-center justify-center gap-1.5 rounded-xl border border-neutral-300 bg-white px-3.5 text-sm font-semibold text-neutral-800 shadow-card"
                    aria-label="Tambah pelanggan datang langsung"
                >
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    Datang langsung
                </button>

                @if ($nowServing && $nowServing->status === QueueTicket::STATUS_DIPANGGIL)
                    <form method="POST" action="{{ route('merchant.queues.start-service', $nowServing) }}">
                        @csrf
                        <button type="submit" class="inline-flex h-12 w-full items-center justify-center gap-2 rounded-xl bg-brand-700 text-base font-bold text-white shadow-raised active:bg-brand-900">
                            Mulai Melayani
                        </button>
                    </form>
                @elseif ($nowServing && $nowServing->status === QueueTicket::STATUS_SEDANG_DILAYANI)
                    <form method="POST" action="{{ route('merchant.queues.complete', $nowServing) }}">
                        @csrf
                        <button type="submit" class="inline-flex h-12 w-full items-center justify-center gap-2 rounded-xl bg-emerald-700 text-base font-bold text-white shadow-raised active:bg-emerald-900">
                            <x-customer.icon name="check" class="size-5" />
                            Selesaikan
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
                            class="inline-flex h-12 w-full items-center justify-center gap-2 rounded-xl bg-brand-700 text-base font-bold text-white shadow-raised active:bg-brand-900 disabled:cursor-not-allowed disabled:bg-neutral-200 disabled:text-neutral-600 disabled:shadow-none"
                        >
                            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $megaphonePath }}"/></svg>
                            Panggil Berikutnya
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
                class="relative w-full max-w-md rounded-t-2xl border border-neutral-200 bg-white p-5 shadow-overlay sm:rounded-2xl sm:p-6"
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
                            class="border w-full rounded-lg border-neutral-300 text-sm focus:border-rose-500 focus:ring-rose-500"
                        ></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-2 border-t border-neutral-100 pt-3 sm:flex sm:justify-end">
                        <button type="button" @click="cancelModalOpen = false" class="inline-flex h-11 items-center justify-center rounded-lg border border-neutral-300 px-4 text-sm font-semibold text-neutral-700 transition hover:bg-neutral-50">
                            Tutup
                        </button>
                        <button type="submit" class="inline-flex h-11 items-center justify-center rounded-lg bg-rose-600 px-4 text-sm font-bold text-white shadow-card transition hover:bg-rose-700">
                            Konfirmasi Batalkan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <x-merchant.realtime-toasts :business-id="$business->id" :counter-id="$activeCounter?->id" />
</x-layouts.merchant>
