<x-layouts.base :title="'Layar Antrean — ' . $business->name">
    <div
        id="display-root"
        class="flex min-h-screen flex-col overflow-x-hidden bg-neutral-50 font-sans text-neutral-900 select-none selection:bg-brand-700 selection:text-white"
        x-data="publicQueueDisplay({
            token: '{{ $token }}',
            tokenHash: '{{ $tokenHash }}',
            dataUrl: '{{ route('display.data', ['token' => $token]) }}',
            initialData: @js($displayData)
        })"
        x-init="initDisplay()"
    >
        {{-- TV header: crisp AntreIn light bar with brand-700 accent --}}
        <header class="sticky top-0 z-30 flex items-center justify-between gap-3 border-b border-neutral-200 bg-white/95 px-4 py-3 shadow-xs backdrop-blur-md sm:px-8 sm:py-4 2xl:px-12">
            <div class="flex min-w-0 items-center gap-3 sm:gap-5">
                @if ($business->logo_path)
                    <img src="{{ $business->getLogoUrl() }}" alt="{{ $business->name }}" class="size-11 shrink-0 rounded-2xl border border-neutral-200 object-cover shadow-xs sm:size-14">
                @else
                    <div class="flex size-11 shrink-0 items-center justify-center rounded-2xl bg-brand-700 text-lg font-black text-white shadow-xs sm:size-14 sm:text-xl">
                        {{ strtoupper(substr($business->name, 0, 1)) }}
                    </div>
                @endif

                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <x-brand-logo size="xs" :showText="false" />
                        <span class="inline-flex items-center rounded-lg bg-brand-700 px-2.5 py-0.5 text-[11px] font-black uppercase tracking-widest text-white sm:text-xs">AntreIn Display</span>
                        <span class="hidden items-center gap-1 rounded-lg border border-neutral-200 bg-neutral-100 px-2 py-0.5 text-[11px] font-semibold text-neutral-600 sm:inline-flex">
                            <svg class="size-3.5 text-neutral-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span x-text="business.timezone || @js($business->timezone)">{{ $business->timezone }}</span>
                        </span>
                    </div>
                    <h1 class="mt-1 truncate text-lg font-black tracking-tight text-neutral-950 sm:text-2xl lg:text-3xl" x-text="business.name">
                        {{ $business->name }}
                    </h1>
                </div>
            </div>

            <div class="flex shrink-0 items-center gap-3 sm:gap-6">
                <div class="hidden text-right sm:block">
                    <div class="text-xs font-semibold tracking-wide text-neutral-500 sm:text-sm" x-text="serverTime.date">
                        {{ $displayData['server_time']['date'] }}
                    </div>
                    <div class="font-mono text-2xl font-black tracking-tight text-neutral-950 tabular-nums sm:text-3xl lg:text-4xl" x-text="currentTime">
                        {{ $displayData['server_time']['time'] }}
                    </div>
                </div>

                <div class="flex items-center gap-2 sm:border-l sm:border-neutral-200 sm:pl-6">
                    {{-- Audio chime toggle --}}
                    <button
                        type="button"
                        @click="toggleAudio()"
                        class="flex size-11 items-center justify-center rounded-xl border transition focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-700 sm:size-12"
                        :class="isAudioEnabled ? 'border-brand-200 bg-brand-50 text-brand-700 hover:bg-brand-100' : 'border-neutral-200 bg-white text-neutral-500 hover:bg-neutral-100'"
                        :title="isAudioEnabled ? 'Suara Panggilan Aktif' : 'Suara Panggilan Dibisukan'"
                        :aria-pressed="isAudioEnabled.toString()"
                        aria-label="Toggle Suara"
                    >
                        <template x-if="isAudioEnabled">
                            <svg class="size-5 sm:size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" />
                            </svg>
                        </template>
                        <template x-if="!isAudioEnabled">
                            <svg class="size-5 sm:size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2" />
                            </svg>
                        </template>
                    </button>

                    {{-- Fullscreen toggle --}}
                    <button
                        type="button"
                        @click="toggleFullscreen()"
                        class="flex size-11 items-center justify-center rounded-xl border border-neutral-200 bg-white text-neutral-700 transition hover:bg-neutral-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-700 sm:size-12"
                        :title="isFullscreen ? 'Keluar Layar Penuh' : 'Mode Layar Penuh (TV)'"
                        aria-label="Toggle Fullscreen"
                    >
                        <template x-if="!isFullscreen">
                            <svg class="size-5 sm:size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
                            </svg>
                        </template>
                        <template x-if="isFullscreen">
                            <svg class="size-5 sm:size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 9L4 4m0 0v4m0-4h4m6 5l5-5m0 0v4m0-4h-4M9 15l-5 5m0 0v-4m0 4h4m11-5l5 5m0 0v-4m0 4h-4" />
                            </svg>
                        </template>
                    </button>
                </div>
            </div>
        </header>

        {{-- Mobile clock (header clock is hidden below sm) --}}
        <div class="flex items-center justify-between border-b border-neutral-200 bg-white px-4 py-2 sm:hidden">
            <span class="text-xs font-semibold text-neutral-500" x-text="serverTime.date">{{ $displayData['server_time']['date'] }}</span>
            <span class="font-mono text-lg font-black text-neutral-950 tabular-nums" x-text="currentTime">{{ $displayData['server_time']['time'] }}</span>
        </div>

        {{-- Counters grid --}}
        <main class="mx-auto flex w-full max-w-7xl flex-1 flex-col justify-center p-4 sm:p-8 lg:p-10 2xl:max-w-[112rem]">
            <template x-if="counters.length === 0">
                <div class="mx-auto max-w-xl rounded-3xl border border-neutral-200 bg-white px-6 py-16 text-center shadow-sm">
                    <div class="mx-auto mb-4 flex size-16 items-center justify-center rounded-2xl bg-brand-50 text-brand-700">
                        <svg class="size-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <h2 class="mb-1 text-2xl font-bold text-neutral-950">Belum Ada Loket Pelayanan Aktif</h2>
                    <p class="text-sm text-neutral-500">Unit usaha sedang menyiapkan loket atau meja operasional antrean.</p>
                </div>
            </template>

            <template x-if="counters.length > 0">
                <div
                    class="grid w-full gap-4 sm:gap-6 lg:gap-8"
                    :class="{
                        'grid-cols-1 max-w-3xl mx-auto': counters.length === 1,
                        'grid-cols-1 md:grid-cols-2': counters.length === 2,
                        'grid-cols-1 md:grid-cols-2 lg:grid-cols-3': counters.length === 3,
                        'grid-cols-1 sm:grid-cols-2 lg:grid-cols-4': counters.length >= 4
                    }"
                >
                    <template x-for="counter in counters" :key="counter.id">
                        <section
                            class="relative flex flex-col overflow-hidden rounded-3xl border-2 bg-white shadow-sm transition-colors duration-300"
                            :class="{
                                'border-amber-400 bg-amber-50/70 ring-4 ring-amber-400/25 shadow-lg shadow-amber-500/10': isCalled(counter),
                                'border-brand-600 shadow-lg shadow-brand-700/10': isServing(counter),
                                'border-neutral-200': !counter.active_ticket
                            }"
                            :aria-label="counter.name"
                        >
                            {{-- Counter header --}}
                            <div
                                class="flex items-center justify-between gap-3 border-b px-5 py-3.5 sm:px-6 sm:py-4"
                                :class="{
                                    'border-amber-300 bg-amber-400 text-amber-950': isCalled(counter),
                                    'border-brand-700 bg-brand-700 text-white': isServing(counter),
                                    'border-neutral-200 bg-neutral-50 text-neutral-900': !counter.active_ticket
                                }"
                            >
                                <div class="flex min-w-0 items-center gap-2.5">
                                    <span
                                        class="flex size-8 shrink-0 items-center justify-center rounded-xl sm:size-9"
                                        :class="{
                                            'bg-amber-950/10 text-amber-950': isCalled(counter),
                                            'bg-white/15 text-white': isServing(counter),
                                            'bg-white text-neutral-500 ring-1 ring-neutral-200': !counter.active_ticket
                                        }"
                                        aria-hidden="true"
                                    >
                                        <svg x-show="isCalled(counter)" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                                        </svg>
                                        <svg x-show="isServing(counter)" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                        </svg>
                                        <svg x-show="!counter.active_ticket" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    </span>
                                    <h3
                                        class="truncate text-lg font-extrabold tracking-tight sm:text-xl 2xl:text-2xl"
                                        x-text="counter.name"
                                    ></h3>
                                </div>

                                <span
                                    class="shrink-0 rounded-lg px-2.5 py-0.5 font-mono text-xs font-bold"
                                    :class="{
                                        'bg-amber-950/15 text-amber-950': isCalled(counter),
                                        'bg-white/15 text-white': isServing(counter),
                                        'border border-neutral-200 bg-white text-neutral-600': !counter.active_ticket
                                    }"
                                    x-text="counter.code"
                                ></span>
                            </div>

                            {{-- Ticket number and service --}}
                            <div
                                class="flex flex-1 flex-col items-center justify-center px-5 text-center sm:px-6"
                                :class="counters.length > 4 ? 'py-6 2xl:py-7' : 'py-8 sm:py-10 2xl:py-14'"
                            >
                                <div class="mb-2 text-xs font-black uppercase tracking-widest sm:text-sm">
                                    <template x-if="isCalled(counter)">
                                        <span class="text-amber-800">Silakan Menuju Loket</span>
                                    </template>
                                    <template x-if="isServing(counter)">
                                        <span class="text-emerald-700">Sedang Melayani</span>
                                    </template>
                                    <template x-if="!counter.active_ticket">
                                        <span class="text-neutral-400">Status Loket</span>
                                    </template>
                                </div>

                                <template x-if="counter.active_ticket">
                                    <div
                                        class="max-w-full whitespace-nowrap py-2 font-mono font-black leading-none tracking-tight tabular-nums"
                                        :class="[ticketNumberSizeClass(), isCalled(counter) ? 'text-neutral-950' : 'text-brand-700']"
                                        x-text="counter.active_ticket.ticket_number"
                                    ></div>
                                </template>

                                <template x-if="!counter.active_ticket">
                                    <div class="py-6 text-center sm:py-8">
                                        <div class="font-mono text-4xl font-black tracking-wider text-neutral-300 sm:text-5xl">—</div>
                                        <div class="mt-2 text-xs font-medium text-neutral-500 sm:text-sm">Belum ada panggilan</div>
                                    </div>
                                </template>

                                <template x-if="counter.active_ticket && counter.active_ticket.service_name">
                                    <div
                                        class="mt-3 line-clamp-1 text-sm font-bold sm:text-base 2xl:text-lg"
                                        :class="isCalled(counter) ? 'text-neutral-900' : 'text-neutral-700'"
                                        x-text="counter.active_ticket.service_name"
                                    ></div>
                                </template>

                                <template x-if="counter.active_ticket && counter.active_ticket.booking_slot_window">
                                    <div class="mt-2 inline-flex items-center gap-1 rounded-full border border-neutral-200 bg-white px-3 py-0.5 text-xs font-semibold text-neutral-600 tabular-nums">
                                        <span>Slot</span>
                                        <span x-text="counter.active_ticket.booking_slot_window"></span>
                                    </div>
                                </template>
                            </div>

                            {{-- State bar --}}
                            <div
                                class="flex items-center justify-between gap-3 border-t px-5 py-3 text-xs font-semibold sm:px-6"
                                :class="isCalled(counter) ? 'border-amber-300 bg-amber-100/80' : (isServing(counter) ? 'border-neutral-100 bg-brand-50/40' : 'border-neutral-100 bg-neutral-50')"
                            >
                                <template x-if="isCalled(counter)">
                                    <span class="inline-flex items-center rounded-full bg-amber-500 px-3 py-1 text-xs font-black uppercase tracking-wider text-amber-950 sm:text-sm">DIPANGGIL</span>
                                </template>
                                <template x-if="isServing(counter)">
                                    <span class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-100 px-3 py-1 text-xs font-black uppercase tracking-wider text-emerald-800">DILAYANI</span>
                                </template>
                                <template x-if="!counter.active_ticket">
                                    <span class="inline-flex items-center rounded-full bg-neutral-200/80 px-3 py-1 text-xs font-bold text-neutral-600">STANDBY</span>
                                </template>

                                <span class="font-mono text-[11px] tabular-nums sm:text-xs" :class="isCalled(counter) ? 'text-amber-950' : 'text-neutral-500'">
                                    <template x-if="counter.active_ticket && counter.active_ticket.called_at_formatted">
                                        <span x-text="'Dipanggil ' + counter.active_ticket.called_at_formatted"></span>
                                    </template>
                                    <template x-if="!counter.active_ticket">
                                        <span>Loket Siap</span>
                                    </template>
                                </span>
                            </div>
                        </section>
                    </template>
                </div>
            </template>
        </main>

        {{-- Footer: connection state, guidance and take-queue QR card (in normal flow so it never overlaps counters) --}}
        <footer class="border-t border-neutral-200 bg-white px-4 py-3 text-xs text-neutral-600 sm:px-8 2xl:px-12">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between sm:gap-6">
                <div class="flex min-w-0 flex-col gap-1.5">
                    <div class="flex items-center gap-2">
                        <svg x-show="isLiveConnected" class="size-4 shrink-0 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"/>
                        </svg>
                        <svg x-show="!isLiveConnected" class="size-4 shrink-0 text-brand-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        <span class="font-semibold text-neutral-800" x-text="isLiveConnected ? 'Live WebSocket Terhubung' : 'Sinkronisasi Otomatis (Polling)'"></span>
                    </div>
                    <p class="hidden text-neutral-500 md:block 2xl:text-sm">Harap memperhatikan nomor antrean dan menuju loket yang ditentukan saat nomor Anda dipanggil.</p>
                    <x-brand-logo size="xs" class="hidden sm:inline-flex" />
                </div>

                <a
                    href="{{ $takeQueueUrl }}"
                    target="_blank"
                    rel="noopener"
                    data-testid="display-take-queue-qr"
                    class="flex shrink-0 items-center gap-3 rounded-2xl border border-neutral-200 bg-neutral-50 p-2 pr-4 text-neutral-900 shadow-xs transition hover:border-brand-300 hover:bg-brand-50/40 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-700"
                >
                    <span class="block size-20 shrink-0 rounded-xl bg-white p-1.5 ring-1 ring-neutral-200 sm:size-24 2xl:size-28 [&_svg]:h-full [&_svg]:w-full" role="img" aria-label="QR code ambil antrean {{ $business->name }}">
                        {!! $takeQueueQrSvg !!}
                    </span>
                    <span class="min-w-0">
                        <span class="flex items-center gap-1.5 text-[11px] font-black uppercase tracking-wider text-brand-700">
                            <svg class="size-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                            </svg>
                            Antre dari HP
                        </span>
                        <span class="mt-0.5 block max-w-48 text-sm font-extrabold leading-snug text-neutral-950 2xl:max-w-56 2xl:text-base">Scan untuk Ambil Antrean dari HP</span>
                    </span>
                </a>
            </div>
        </footer>
    </div>

    @push('scripts')
    <script>
        function publicQueueDisplay(config) {
            return {
                token: config.token,
                tokenHash: config.tokenHash,
                dataUrl: config.dataUrl,
                business: config.initialData?.business || {},
                serverTime: config.initialData?.server_time || {},
                counters: config.initialData?.counters || [],
                currentTime: config.initialData?.server_time?.time || '',
                isLiveConnected: false,
                isAudioEnabled: false,
                isFullscreen: false,
                pollTimer: null,
                clockTimer: null,
                lastCalledTicketMap: {},
                audioContext: null,

                isCalled(counter) {
                    return !!counter.active_ticket && counter.active_ticket.status === 'dipanggil';
                },

                isServing(counter) {
                    return !!counter.active_ticket && counter.active_ticket.status === 'sedang_dilayani';
                },

                // Scale the ticket number to the grid density so it never overflows its card.
                ticketNumberSizeClass() {
                    const total = this.counters.length;
                    if (total <= 1) return 'text-7xl sm:text-8xl lg:text-9xl 2xl:text-[10rem]';
                    if (total === 2) return 'text-6xl sm:text-7xl lg:text-8xl 2xl:text-9xl';
                    if (total === 3) return 'text-6xl lg:text-7xl xl:text-8xl 2xl:text-9xl';
                    return 'text-6xl lg:text-5xl xl:text-6xl 2xl:text-8xl';
                },

                initDisplay() {
                    // Populate initial called ticket map
                    this.updateCalledMap();

                    // Start local clock ticker
                    this.startClock();

                    // Connect Reverb WebSocket via Echo
                    this.initRealtime();

                    // Start fallback adaptive polling
                    this.startPolling();

                    // Monitor Fullscreen changes
                    document.addEventListener('fullscreenchange', () => {
                        this.isFullscreen = !!document.fullscreenElement;
                    });

                    // Network online/offline recovery
                    window.addEventListener('online', () => {
                        this.fetchDisplayData();
                    });
                },

                updateCalledMap() {
                    this.counters.forEach(counter => {
                        if (counter.active_ticket) {
                            this.lastCalledTicketMap[counter.id] = counter.active_ticket.ticket_number + '_' + counter.active_ticket.status;
                        } else {
                            delete this.lastCalledTicketMap[counter.id];
                        }
                    });
                },

                initRealtime() {
                    if (window.Echo && this.tokenHash) {
                        try {
                            const channelName = 'display.' + this.tokenHash;
                            window.Echo.channel(channelName)
                                .listen('.PublicQueueDisplayUpdated', (e) => {
                                    this.isLiveConnected = true;
                                    this.fetchDisplayData();
                                });

                            this.isLiveConnected = true;
                        } catch (err) {
                            console.warn('Display Echo channel subscription error:', err);
                            this.isLiveConnected = false;
                        }
                    }
                },

                startPolling() {
                    if (this.pollTimer) clearInterval(this.pollTimer);
                    // 10 seconds background sync
                    this.pollTimer = setInterval(() => {
                        this.fetchDisplayData();
                    }, 10000);
                },

                startClock() {
                    if (this.clockTimer) clearInterval(this.clockTimer);
                    this.clockTimer = setInterval(() => {
                        const now = new Date();
                        const hours = String(now.getHours()).padStart(2, '0');
                        const minutes = String(now.getMinutes()).padStart(2, '0');
                        this.currentTime = hours + ':' + minutes;
                    }, 1000);
                },

                async fetchDisplayData() {
                    try {
                        const response = await fetch(this.dataUrl, {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });

                        if (response.status === 404) {
                            // Display revoked or disabled
                            window.location.reload();
                            return;
                        }

                        if (!response.ok) return;

                        const result = await response.json();
                        if (result.success && result.data) {
                            const prevCounters = this.counters;
                            this.business = result.data.business;
                            this.serverTime = result.data.server_time;
                            this.counters = result.data.counters || [];
                            if (result.data.server_time?.time) {
                                this.currentTime = result.data.server_time.time;
                            }

                            // Check if a new call was made to trigger audio chime
                            this.detectNewCalls();
                        }
                    } catch (err) {
                        // Silent catch on temporary connection drop
                    }
                },

                detectNewCalls() {
                    let shouldChime = false;

                    this.counters.forEach(counter => {
                        if (counter.active_ticket && counter.active_ticket.status === 'dipanggil') {
                            const key = counter.active_ticket.ticket_number + '_' + counter.active_ticket.status;
                            if (this.lastCalledTicketMap[counter.id] !== key) {
                                shouldChime = true;
                            }
                        }
                    });

                    this.updateCalledMap();

                    if (shouldChime && this.isAudioEnabled) {
                        this.playChime();
                    }
                },

                toggleAudio() {
                    this.isAudioEnabled = !this.isAudioEnabled;
                    if (this.isAudioEnabled) {
                        this.ensureAudioContext();
                        this.playChime(); // Play test chime
                    }
                },

                ensureAudioContext() {
                    if (!this.audioContext) {
                        const AudioCtx = window.AudioContext || window.webkitAudioContext;
                        if (AudioCtx) {
                            this.audioContext = new AudioCtx();
                        }
                    }
                    if (this.audioContext && this.audioContext.state === 'suspended') {
                        this.audioContext.resume();
                    }
                },

                playChime() {
                    try {
                        this.ensureAudioContext();
                        if (!this.audioContext) return;

                        const ctx = this.audioContext;
                        const now = ctx.currentTime;

                        // Two-tone bell chime: C5 (523.25 Hz) then G5 (783.99 Hz)
                        const notes = [
                            { freq: 523.25, time: now, dur: 0.35 },
                            { freq: 783.99, time: now + 0.25, dur: 0.6 }
                        ];

                        notes.forEach(note => {
                            const osc = ctx.createOscillator();
                            const gain = ctx.createGain();

                            osc.type = 'sine';
                            osc.frequency.setValueAtTime(note.freq, note.time);

                            gain.gain.setValueAtTime(0, note.time);
                            gain.gain.linearRampToValueAtTime(0.25, note.time + 0.05);
                            gain.gain.exponentialRampToValueAtTime(0.001, note.time + note.dur);

                            osc.connect(gain);
                            gain.connect(ctx.destination);

                            osc.start(note.time);
                            osc.stop(note.time + note.dur);
                        });
                    } catch (e) {
                        console.warn('Audio chime play error:', e);
                    }
                },

                toggleFullscreen() {
                    const el = document.documentElement;
                    if (!document.fullscreenElement) {
                        if (el.requestFullscreen) {
                            el.requestFullscreen();
                        } else if (el.webkitRequestFullscreen) {
                            el.webkitRequestFullscreen();
                        }
                    } else {
                        if (document.exitFullscreen) {
                            document.exitFullscreen();
                        } else if (document.webkitExitFullscreen) {
                            document.webkitExitFullscreen();
                        }
                    }
                }
            };
        }
    </script>
    @endpush
</x-layouts.base>

