<x-layouts.base :title="'Layar Antrean — ' . $business->name">
    <div 
        id="display-root"
        class="min-h-screen bg-slate-950 text-slate-100 flex flex-col justify-between selection:bg-brand-500 selection:text-white select-none overflow-x-hidden font-sans"
        x-data="publicQueueDisplay({
            token: '{{ $token }}',
            tokenHash: '{{ $tokenHash }}',
            dataUrl: '{{ route('display.data', ['token' => $token]) }}',
            initialData: @js($displayData)
        })"
        x-init="initDisplay()"
    >
        {{-- TV Header Bar --}}
        <header class="bg-slate-900/90 border-b border-slate-800 px-6 sm:px-10 py-4 sm:py-5 flex items-center justify-between shadow-lg backdrop-blur-md sticky top-0 z-30">
            {{-- Business Brand & Title --}}
            <div class="flex items-center gap-4 sm:gap-6 min-w-0">
                @if ($business->logo_path)
                    <img src="{{ $business->getLogoUrl() }}" alt="{{ $business->name }}" class="w-12 h-12 sm:w-14 sm:h-14 rounded-2xl object-cover border border-slate-700/80 shrink-0 shadow-md">
                @else
                    <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-2xl bg-brand-700/80 border border-brand-500/50 flex items-center justify-center shrink-0 shadow-md text-white font-black text-xl">
                        {{ strtoupper(substr($business->name, 0, 1)) }}
                    </div>
                @endif

                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-black tracking-widest uppercase text-brand-400">AntreIn Display</span>
                        <span class="text-slate-500 text-xs">•</span>
                        <span class="text-xs font-semibold text-slate-400" x-text="business.timezone || '{{ $business->timezone }}'"></span>
                    </div>
                    <h1 class="text-xl sm:text-2xl lg:text-3xl font-black text-white tracking-tight truncate mt-0.5" x-text="business.name">
                        {{ $business->name }}
                    </h1>
                </div>
            </div>

            {{-- Live Time, Controls & Status --}}
            <div class="flex items-center gap-4 sm:gap-6 shrink-0">
                {{-- Date and Digital Clock --}}
                <div class="text-right hidden sm:block">
                    <div class="text-xs sm:text-sm font-semibold text-slate-300 tracking-wide" x-text="serverTime.date">
                        {{ $displayData['server_time']['date'] }}
                    </div>
                    <div class="text-2xl sm:text-3xl lg:text-4xl font-black font-mono tracking-tight text-white tabular-nums" x-text="currentTime">
                        {{ $displayData['server_time']['time'] }}
                    </div>
                </div>

                {{-- Control Actions (Audio Mute, Fullscreen, Connection) --}}
                <div class="flex items-center gap-2 border-l border-slate-800 pl-4 sm:pl-6">
                    {{-- Audio Chime Toggle --}}
                    <button 
                        type="button" 
                        @click="toggleAudio()"
                        class="p-2.5 sm:p-3 rounded-xl border transition flex items-center justify-center"
                        :class="isAudioEnabled ? 'bg-slate-800 border-slate-700 text-brand-400 hover:bg-slate-700' : 'bg-slate-800/50 border-slate-700/50 text-slate-500 hover:bg-slate-800'"
                        :title="isAudioEnabled ? 'Suara Panggilan Aktif' : 'Suara Panggilan Dibisukan'"
                        aria-label="Toggle Suara"
                    >
                        <template x-if="isAudioEnabled">
                            <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" />
                            </svg>
                        </template>
                        <template x-if="!isAudioEnabled">
                            <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2" />
                            </svg>
                        </template>
                    </button>

                    {{-- Fullscreen Toggle --}}
                    <button 
                        type="button" 
                        @click="toggleFullscreen()"
                        class="p-2.5 sm:p-3 rounded-xl bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-200 transition flex items-center justify-center"
                        :title="isFullscreen ? 'Keluar Layar Penuh' : 'Mode Layar Penuh (TV)'"
                        aria-label="Toggle Fullscreen"
                    >
                        <template x-if="!isFullscreen">
                            <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
                            </svg>
                        </template>
                        <template x-if="isFullscreen">
                            <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 9L4 4m0 0v4m0-4h4m6 5l5-5m0 0v4m0-4h-4M9 15l-5 5m0 0v-4m0 4h4m11-5l5 5m0 0v-4m0 4h-4" />
                            </svg>
                        </template>
                    </button>
                </div>
            </div>
        </header>

        {{-- Main Public Counters Grid Area --}}
        <main class="flex-1 p-6 sm:p-10 lg:p-12 flex flex-col justify-center max-w-7xl w-full mx-auto">
            {{-- Empty State: No active counters configured --}}
            <template x-if="counters.length === 0">
                <div class="text-center py-16 px-6 rounded-3xl bg-slate-900/50 border border-slate-800 max-w-xl mx-auto shadow-xl">
                    <div class="w-16 h-16 rounded-2xl bg-slate-800 text-slate-500 mx-auto flex items-center justify-center mb-4">
                        <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <h2 class="text-2xl font-bold text-white mb-1">Belum Ada Loket Pelayanan Aktif</h2>
                    <p class="text-sm text-slate-400">Unit usaha sedang menyiapkan meja dan loket operasional antrean.</p>
                </div>
            </template>

            {{-- Multi-Counter Dynamic Grid --}}
            <template x-if="counters.length > 0">
                <div 
                    class="grid gap-6 sm:gap-8 lg:gap-10 w-full"
                    :class="{
                        'grid-cols-1 max-w-2xl mx-auto': counters.length === 1,
                        'grid-cols-1 md:grid-cols-2': counters.length === 2,
                        'grid-cols-1 md:grid-cols-2 lg:grid-cols-3': counters.length === 3,
                        'grid-cols-1 sm:grid-cols-2 lg:grid-cols-4': counters.length >= 4
                    }"
                >
                    <template x-for="counter in counters" :key="counter.id">
                        <div 
                            class="rounded-3xl border transition-all duration-300 flex flex-col overflow-hidden relative shadow-xl"
                            :class="{
                                'bg-gradient-to-b from-amber-950/40 to-slate-900/90 border-amber-500/80 shadow-amber-500/10 ring-2 ring-amber-500/30': counter.active_ticket && counter.active_ticket.status === 'dipanggil',
                                'bg-gradient-to-b from-emerald-950/30 to-slate-900/90 border-emerald-500/60 shadow-emerald-500/10': counter.active_ticket && counter.active_ticket.status === 'sedang_dilayani',
                                'bg-slate-900/70 border-slate-800 hover:border-slate-700': !counter.active_ticket
                            }"
                        >
                            {{-- Counter Top Header --}}
                            <div 
                                class="px-6 py-4 flex items-center justify-between border-b"
                                :class="{
                                    'border-amber-500/30 bg-amber-500/10 text-amber-300': counter.active_ticket && counter.active_ticket.status === 'dipanggil',
                                    'border-emerald-500/20 bg-emerald-500/10 text-emerald-300': counter.active_ticket && counter.active_ticket.status === 'sedang_dilayani',
                                    'border-slate-800 bg-slate-800/40 text-slate-400': !counter.active_ticket
                                }"
                            >
                                <div class="flex items-center gap-2">
                                    <span class="w-3 h-3 rounded-full" :class="{
                                        'bg-amber-400 animate-ping': counter.active_ticket && counter.active_ticket.status === 'dipanggil',
                                        'bg-emerald-400': counter.active_ticket && counter.active_ticket.status === 'sedang_dilayani',
                                        'bg-slate-600': !counter.active_ticket
                                    }"></span>
                                    <h3 class="font-extrabold text-lg sm:text-xl tracking-tight text-white uppercase" x-text="counter.name"></h3>
                                </div>

                                <span class="text-xs font-mono font-bold px-2.5 py-0.5 rounded-lg bg-slate-800/80 border border-slate-700/80 text-slate-300" x-text="counter.code"></span>
                            </div>

                            {{-- Ticket Number & Service Section --}}
                            <div class="px-6 py-8 sm:py-12 flex-1 flex flex-col items-center justify-center text-center">
                                {{-- Subtitle info --}}
                                <div class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-2">
                                    <template x-if="counter.active_ticket && counter.active_ticket.status === 'dipanggil'">
                                        <span class="text-amber-400 flex items-center gap-1.5 justify-center">
                                            <svg class="w-4 h-4 animate-bounce" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                                            Panggilan Menuju Loket
                                        </span>
                                    </template>
                                    <template x-if="counter.active_ticket && counter.active_ticket.status === 'sedang_dilayani'">
                                        <span class="text-emerald-400">Sedang Melayani</span>
                                    </template>
                                    <template x-if="!counter.active_ticket">
                                        <span>Status Loket</span>
                                    </template>
                                </div>

                                {{-- Main Extra-Large Number Display --}}
                                <template x-if="counter.active_ticket">
                                    <div 
                                        class="font-mono font-black tracking-tight tabular-nums py-2 leading-none transition-transform"
                                        :class="{
                                            'text-amber-300 text-6xl sm:text-7xl lg:text-8xl scale-105 animate-pulse': counter.active_ticket.status === 'dipanggil',
                                            'text-white text-5xl sm:text-6xl lg:text-7xl': counter.active_ticket.status === 'sedang_dilayani'
                                        }"
                                        x-text="counter.active_ticket.ticket_number"
                                    ></div>
                                </template>

                                <template x-if="!counter.active_ticket">
                                    <div class="py-6 sm:py-8 text-center">
                                        <div class="text-4xl sm:text-5xl font-black text-slate-700 font-mono tracking-wider">—</div>
                                        <div class="text-xs text-slate-500 font-medium mt-2">Belum ada panggilan</div>
                                    </div>
                                </template>

                                {{-- Service Name --}}
                                <template x-if="counter.active_ticket && counter.active_ticket.service_name">
                                    <div class="mt-3 text-sm sm:text-base font-bold text-slate-300 line-clamp-1" x-text="counter.active_ticket.service_name"></div>
                                </template>
                            </div>

                            {{-- Bottom State Pill Bar --}}
                            <div class="px-6 py-3.5 border-t border-slate-800/80 bg-slate-900/60 flex items-center justify-between text-xs font-semibold">
                                <div>
                                    <template x-if="counter.active_ticket && counter.active_ticket.status === 'dipanggil'">
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black bg-amber-400 text-slate-950 uppercase tracking-wider shadow-sm">
                                            DIPANGGIL
                                        </span>
                                    </template>
                                    <template x-if="counter.active_ticket && counter.active_ticket.status === 'sedang_dilayani'">
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black bg-emerald-500 text-slate-950 uppercase tracking-wider shadow-sm">
                                            DILAYANI
                                        </span>
                                    </template>
                                    <template x-if="!counter.active_ticket">
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-slate-800 text-slate-400">
                                            STANDBY
                                        </span>
                                    </template>
                                </div>

                                <div class="text-[11px] font-mono text-slate-500">
                                    <template x-if="counter.active_ticket && counter.active_ticket.called_at_formatted">
                                        <span x-text="'Dipanggil ' + counter.active_ticket.called_at_formatted"></span>
                                    </template>
                                    <template x-if="!counter.active_ticket">
                                        <span>Loket Siap</span>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </template>
        </main>

        {{-- TV Bottom Ticker Bar --}}
        <footer class="bg-slate-900/90 border-t border-slate-800 px-6 sm:px-10 py-3.5 flex items-center justify-between text-xs text-slate-400 backdrop-blur-md">
            <div class="flex items-center gap-3">
                <span class="relative flex h-2.5 w-2.5">
                    <span x-show="isLiveConnected" class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2.5 w-2.5" :class="isLiveConnected ? 'bg-emerald-500' : 'bg-amber-400'"></span>
                </span>
                <span x-text="isLiveConnected ? 'Live WebSocket Terhubung' : 'Sinkronisasi Otomatis (Polling)'" class="font-medium text-slate-300"></span>
            </div>

            <div class="flex items-center gap-4">
                <span class="hidden sm:inline text-slate-500">Harap memperhatikan nomor antrean dan menuju loket yang ditentukan saat nomor Anda dipanggil.</span>
                <span class="font-bold text-slate-300 tracking-tight">AntreIn<span class="text-brand-400">.</span></span>
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

