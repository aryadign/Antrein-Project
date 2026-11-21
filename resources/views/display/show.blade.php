@php
    $timezoneAbbreviations = ['Asia/Jakarta' => 'WIB', 'Asia/Makassar' => 'WITA', 'Asia/Jayapura' => 'WIT'];
    $timezoneLabel = $timezoneAbbreviations[$business->timezone ?: 'Asia/Makassar'] ?? '';
@endphp

<x-layouts.base :title="'Layar Antrean — ' . $business->name">
    <div
        id="display-root"
        class="flex min-h-screen flex-col overflow-x-hidden bg-neutral-100 font-sans text-neutral-900 select-none selection:bg-brand-700 selection:text-white"
        x-data="publicQueueDisplay({
            token: '{{ $token }}',
            tokenHash: '{{ $tokenHash }}',
            dataUrl: '{{ route('display.data', ['token' => $token]) }}',
            initialData: @js($displayData)
        })"
        x-init="initDisplay()"
    >
        <header class="flex items-center justify-between gap-4 border-b border-neutral-200 bg-white px-4 py-3 shadow-card sm:px-8 sm:py-4 2xl:px-12 2xl:py-5">
            <div class="flex min-w-0 items-center gap-3 sm:gap-5">
                @if ($business->logo_path)
                    <img src="{{ $business->getLogoUrl() }}" alt="{{ $business->name }}" class="size-12 shrink-0 rounded-xl border border-neutral-200 object-cover sm:size-16">
                @else
                    <div class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-brand-700 text-xl font-bold text-white sm:size-16 sm:text-2xl" aria-hidden="true">
                        {{ strtoupper(substr($business->name, 0, 1)) }}
                    </div>
                @endif

                <div class="min-w-0">
                    <h1 class="truncate text-xl font-bold tracking-tight text-neutral-950 sm:text-3xl 2xl:text-4xl" x-text="business.name">{{ $business->name }}</h1>
                    <p class="mt-0.5 text-sm text-neutral-600 sm:text-base 2xl:text-lg">Layar antrean</p>
                </div>
            </div>

            <div class="flex shrink-0 items-center gap-3 sm:gap-6">
                <div class="hidden text-right sm:block">
                    <div class="text-sm font-medium text-neutral-600 2xl:text-base" x-text="serverTime.date">{{ $displayData['server_time']['date'] }}</div>
                    <div class="text-3xl font-bold tracking-tight text-neutral-950 tabular-nums lg:text-4xl 2xl:text-5xl">
                        <span x-text="currentTime">{{ $displayData['server_time']['time'] }}</span>
                        @if ($timezoneLabel)
                            <span class="text-base font-semibold text-neutral-600 2xl:text-xl">{{ $timezoneLabel }}</span>
                        @endif
                    </div>
                </div>

                <div class="flex items-center gap-2 sm:border-l sm:border-neutral-200 sm:pl-6">
                    <button
                        type="button"
                        @click="toggleAudio()"
                        class="flex size-11 items-center justify-center rounded-lg border transition-colors duration-150 ease-out focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 sm:size-12"
                        :class="isAudioEnabled ? 'border-brand-200 bg-brand-50 text-brand-700 hover:bg-brand-100' : 'border-neutral-200 bg-white text-neutral-600 hover:bg-neutral-100'"
                        :title="isAudioEnabled ? 'Suara panggilan aktif' : 'Suara panggilan dibisukan'"
                        :aria-pressed="isAudioEnabled.toString()"
                        aria-label="Suara panggilan"
                    >
                        <svg x-show="isAudioEnabled" class="size-5 sm:size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" />
                        </svg>
                        <svg x-show="!isAudioEnabled" class="size-5 sm:size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2" />
                        </svg>
                    </button>

                    <button
                        type="button"
                        @click="toggleFullscreen()"
                        class="flex size-11 items-center justify-center rounded-lg border border-neutral-200 bg-white text-neutral-700 transition-colors duration-150 ease-out hover:bg-neutral-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 sm:size-12"
                        :title="isFullscreen ? 'Keluar dari layar penuh' : 'Tampilkan layar penuh'"
                        aria-label="Layar penuh"
                    >
                        <svg x-show="!isFullscreen" class="size-5 sm:size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
                        </svg>
                        <svg x-show="isFullscreen" class="size-5 sm:size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 9L4 4m0 0v4m0-4h4m6 5l5-5m0 0v4m0-4h-4M9 15l-5 5m0 0v-4m0 4h4m11-5l5 5m0 0v-4m0 4h-4" />
                        </svg>
                    </button>
                </div>
            </div>
        </header>

        <div class="flex items-center justify-between border-b border-neutral-200 bg-white px-4 py-2 sm:hidden">
            <span class="text-sm font-medium text-neutral-600" x-text="serverTime.date">{{ $displayData['server_time']['date'] }}</span>
            <span class="text-lg font-bold text-neutral-950 tabular-nums" x-text="currentTime">{{ $displayData['server_time']['time'] }}</span>
        </div>

        <main class="mx-auto flex w-full max-w-7xl flex-1 flex-col p-4 sm:p-6 lg:p-8 2xl:max-w-[112rem] 2xl:p-10">
            <template x-if="counters.length === 0">
                <div class="m-auto max-w-xl rounded-xl border border-neutral-200 bg-white px-8 py-14 text-center shadow-card">
                    <div class="mx-auto mb-4 flex size-16 items-center justify-center rounded-full bg-brand-50 text-brand-700">
                        <svg class="size-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <h2 class="text-3xl font-bold text-neutral-950">Belum Ada Loket Aktif</h2>
                    <p class="mt-2 text-lg text-neutral-600">Loket sedang disiapkan. Nomor yang dipanggil akan tampil di layar ini.</p>
                </div>
            </template>

            <template x-if="counters.length > 0">
                <div class="grid w-full flex-1 auto-rows-fr gap-4 sm:gap-6 2xl:gap-8" :class="gridClass()">
                    <template x-for="counter in counters" :key="counter.id">
                        <section
                            class="@container flex min-h-64 flex-col rounded-2xl border transition-[background-color,border-color,box-shadow] duration-200 ease-out"
                            :class="{
                                'border-brand-800 bg-brand-700 text-white shadow-overlay': isCalled(counter),
                                'ring-8 ring-brand-200': isRecentlyCalled(counter),
                                'border-neutral-200 bg-white text-neutral-950 shadow-card': isServing(counter),
                                'border-neutral-200 bg-neutral-50 text-neutral-950': !counter.active_ticket
                            }"
                            :aria-label="counter.name"
                        >
                            <div class="flex items-center justify-between gap-3 px-6 pt-5 2xl:px-8 2xl:pt-6">
                                <h2 class="truncate text-xl font-semibold tracking-tight lg:text-2xl 2xl:text-3xl" x-text="counter.name"></h2>
                                <span
                                    class="shrink-0 rounded-md px-2.5 py-1 text-sm font-semibold tabular-nums 2xl:text-base"
                                    :class="isCalled(counter) ? 'bg-white/15 text-white' : 'bg-neutral-100 text-neutral-700'"
                                    x-text="counter.code"
                                ></span>
                            </div>

                            <div class="flex flex-1 flex-col items-center justify-center px-6 py-6 text-center 2xl:px-8">
                                <p
                                    class="text-lg font-semibold lg:text-xl 2xl:text-2xl"
                                    :class="isCalled(counter) ? 'text-brand-100' : 'text-neutral-600'"
                                    x-text="isCalled(counter) ? 'Silakan menuju loket' : (isServing(counter) ? counter.active_ticket.status_label : 'Belum ada panggilan')"
                                ></p>

                                <template x-if="counter.active_ticket">
                                    <div
                                        class="mt-2 max-w-full whitespace-nowrap text-[clamp(3.5rem,19cqi,12rem)] leading-none font-bold tracking-tight tabular-nums"
                                        :class="isCalled(counter) ? 'text-white' : 'text-neutral-800'"
                                        x-text="counter.active_ticket.ticket_number"
                                    ></div>
                                </template>

                                <template x-if="counter.active_ticket && counter.active_ticket.service_name">
                                    <p
                                        class="mt-4 line-clamp-1 text-lg font-medium lg:text-xl 2xl:text-2xl"
                                        :class="isCalled(counter) ? 'text-white' : 'text-neutral-700'"
                                        x-text="counter.active_ticket.service_name"
                                    ></p>
                                </template>
                            </div>

                            <div
                                class="flex min-h-12 items-center justify-between gap-3 border-t px-6 py-3 text-base tabular-nums 2xl:px-8 2xl:text-lg"
                                :class="isCalled(counter) ? 'border-white/20 text-brand-100' : 'border-neutral-200 text-neutral-600'"
                            >
                                <span x-text="counter.active_ticket && counter.active_ticket.booking_slot_window ? 'Jadwal ' + counter.active_ticket.booking_slot_window : ''"></span>
                                <span x-text="counter.active_ticket && counter.active_ticket.called_at_formatted ? 'Dipanggil ' + counter.active_ticket.called_at_formatted : 'Loket siap'"></span>
                            </div>
                        </section>
                    </template>
                </div>
            </template>
        </main>

        <footer class="border-t border-neutral-200 bg-white px-4 py-4 sm:px-8 2xl:px-12">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between sm:gap-8">
                <div class="min-w-0 space-y-2">
                    <p class="text-base font-medium text-pretty text-neutral-800 lg:text-lg 2xl:text-2xl">Perhatikan nomor Anda. Saat dipanggil, segera menuju loket yang tertera.</p>
                    <div class="flex flex-wrap items-center gap-x-5 gap-y-1 text-sm text-neutral-600 2xl:text-base">
                        <span class="inline-flex items-center gap-1.5">
                            <svg class="size-4 shrink-0" :class="isLiveConnected ? 'text-emerald-700' : 'text-brand-700'" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            <span x-text="isLiveConnected ? 'Diperbarui langsung' : 'Diperbarui otomatis setiap 10 detik'"></span>
                        </span>
                        <span class="inline-flex items-center gap-2">
                            <x-brand-logo size="xs" :showText="false" />
                            Powered by AntreIn — Platform Antrean Digital UMKM
                        </span>
                    </div>
                </div>

                <a
                    href="{{ $takeQueueUrl }}"
                    target="_blank"
                    rel="noopener"
                    data-testid="display-take-queue-qr"
                    class="flex shrink-0 items-center gap-4 rounded-xl border border-neutral-200 bg-white p-2 pr-5 text-neutral-900 shadow-card transition-colors duration-150 ease-out hover:border-brand-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500"
                >
                    <span class="block size-24 shrink-0 rounded-lg bg-white p-1.5 ring-1 ring-neutral-200 2xl:size-32 [&_svg]:h-full [&_svg]:w-full" role="img" aria-label="Kode QR ambil antrean {{ $business->name }}">
                        {!! $takeQueueQrSvg !!}
                    </span>
                    <span class="min-w-0">
                        <span class="block max-w-56 text-lg font-bold leading-snug text-neutral-950 2xl:max-w-64 2xl:text-xl">Scan untuk Ambil Antrean dari HP</span>
                        <span class="mt-1 block text-sm text-neutral-600 2xl:text-base">Arahkan kamera ponsel ke kode ini</span>
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
                recentlyCalled: {},
                audioContext: null,

                isCalled(counter) {
                    return !!counter.active_ticket && counter.active_ticket.status === 'dipanggil';
                },

                isServing(counter) {
                    return !!counter.active_ticket && counter.active_ticket.status === 'sedang_dilayani';
                },

                isRecentlyCalled(counter) {
                    return this.isCalled(counter) && !!this.recentlyCalled[counter.id];
                },

                // Fewer, larger cards: up to 3 side by side, then two rows.
                gridClass() {
                    const total = this.counters.length;
                    if (total === 1) return 'grid-cols-1 max-w-5xl mx-auto';
                    if (total === 2) return 'grid-cols-1 md:grid-cols-2';
                    if (total === 3) return 'grid-cols-1 md:grid-cols-3';
                    if (total === 4) return 'grid-cols-1 sm:grid-cols-2';
                    if (total <= 6) return 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3';
                    return 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-4';
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
                                this.markRecentlyCalled(counter.id);
                            }
                        }
                    });

                    this.updateCalledMap();

                    if (shouldChime && this.isAudioEnabled) {
                        this.playChime();
                    }
                },

                // Highlight a freshly called counter for a few seconds, then settle back.
                markRecentlyCalled(counterId) {
                    this.recentlyCalled = { ...this.recentlyCalled, [counterId]: true };
                    setTimeout(() => {
                        const next = { ...this.recentlyCalled };
                        delete next[counterId];
                        this.recentlyCalled = next;
                    }, 8000);
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

