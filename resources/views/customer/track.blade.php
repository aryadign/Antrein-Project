<x-layouts.customer 
    :title="'Tiket ' . $ticket->ticket_number . ' — ' . $business->name"
    :business="$business"
    :backUrl="route('customer.queue', ['slug' => $business->slug])"
>
    <div 
        class="max-w-xl mx-auto py-2 sm:py-6"
        x-data="queueTracker({
            ticketId: {{ $ticket->id }},
            token: '{{ $ticket->access_token }}',
            tokenHash: '{{ hash('sha256', $ticket->access_token) }}',
            trackUrl: '{{ route('customer.queue.track', ['token' => $ticket->access_token]) }}',
            status: '{{ $ticket->status }}',
            ticketNumber: '{{ $ticket->ticket_number }}',
            queuePosition: {{ (int) $position }},
            aheadCount: {{ (int) $aheadCount }},
            nowServing: '{{ $servingTicket ? $servingTicket->ticket_number : '—' }}',
            serviceName: '{{ addslashes($ticket->service->name) }}',
            customerName: '{{ addslashes($ticket->customer_name) }}',
            customerPhone: '{{ $maskedPhone }}',
            counterName: '{{ $ticket->counter ? addslashes($ticket->counter->name) : '' }}',
            estimatedWaitMinutes: {{ (int) $estimatedWaitMinutes }},
            calledAt: '{{ $ticket->called_at?->translatedFormat('H:i, d M Y') ?? '' }}',
            completedAt: '{{ $ticket->completed_at?->translatedFormat('H:i, d M Y') ?? '' }}',
            cancellationReason: '{{ addslashes($ticket->cancellation_reason ?? '') }}',
            isExpired: false
        })"
        x-init="initTracker()"
    >
        {{-- Flash message on fresh ticket intake --}}
        @if (session('status'))
            <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 mb-6 flex items-center gap-3 shadow-2xs">
                <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div class="text-sm font-semibold">
                    {{ session('status') }}
                </div>
            </div>
        @endif

        {{-- Expired State Banner (Client-side trigger on 410) --}}
        <div x-show="isExpired" class="p-6 rounded-2xl bg-white border border-neutral-200 text-center shadow-sm space-y-4" style="display: none;">
            <div class="w-12 h-12 rounded-full bg-neutral-100 text-neutral-500 mx-auto flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <h2 class="text-xl font-bold text-neutral-900">Tautan Pelacakan Kedaluwarsa</h2>
            <p class="text-sm text-neutral-600 max-w-md mx-auto">
                Akses pelacakan untuk tiket ini telah berakhir setelah 24 jam status selesai/dibatalkan. Sesuai kebijakan privasi, informasi pribadi tidak lagi ditampilkan.
            </p>
            <div class="pt-2">
                <a href="{{ route('customer.queue', ['slug' => $business->slug]) }}" class="inline-flex items-center px-4 py-2.5 rounded-xl bg-brand-700 text-white font-semibold text-sm hover:bg-brand-600 transition">
                    Kembali ke Halaman UMKM
                </a>
            </div>
        </div>

        {{-- Active Ticket Card Container --}}
        <div x-show="!isExpired" class="space-y-6">
            <x-card padding="lg" class="shadow-sm relative overflow-hidden">
                {{-- Live connection indicator --}}
                <div class="flex items-center justify-between border-b border-neutral-100 pb-4 mb-5">
                    <div class="flex items-center gap-2">
                        <span class="relative flex h-2.5 w-2.5">
                            <span x-show="isPollingActive" class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2.5 w-2.5" :class="isPollingActive ? 'bg-emerald-500' : 'bg-neutral-300'"></span>
                        </span>
                        <span class="text-[11px] font-medium text-neutral-500" x-text="isPollingActive ? 'Pembaruan Otomatis Aktif' : 'Pembaruan Selesai'"></span>
                    </div>

                    <div class="text-caption text-neutral-500">
                        {{ $ticket->source === 'online' ? 'Tiket Online' : 'Tiket Walk-in' }}
                    </div>
                </div>

                {{-- Prominent Ticket Number --}}
                <div class="text-center py-2 sm:py-4">
                    <div class="text-xs uppercase font-bold tracking-widest text-neutral-400 mb-1">
                        Nomor Antrean Anda
                    </div>
                    
                    <div class="tabular-nums font-sans font-extrabold text-5xl sm:text-6xl tracking-tight text-neutral-950 py-1" x-text="ticketNumber">
                        {{ $ticket->ticket_number }}
                    </div>

                    <div class="mt-2 text-sm font-semibold text-neutral-700" x-text="serviceName">
                        {{ $ticket->service->name }}
                    </div>

                    {{-- Dynamic Status Pill --}}
                    <div class="mt-4 flex flex-col items-center justify-center gap-2">
                        <div>
                            <template x-if="status === 'menunggu'">
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-800 border border-amber-200">
                                    <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                                    Menunggu
                                </span>
                            </template>

                            <template x-if="status === 'dipanggil'">
                                <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-bold bg-blue-100 text-blue-800 border border-blue-300 animate-pulse">
                                    <span class="w-2 h-2 rounded-full bg-blue-600"></span>
                                    Sedang Dipanggil
                                </span>
                            </template>

                            <template x-if="status === 'sedang_dilayani'">
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-teal-50 text-teal-800 border border-teal-200">
                                    <span class="w-2 h-2 rounded-full bg-teal-500"></span>
                                    Sedang Dilayani
                                </span>
                            </template>

                            <template x-if="status === 'selesai'">
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                    Selesai
                                </span>
                            </template>

                            <template x-if="status === 'dilewati'">
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-neutral-100 text-neutral-700 border border-neutral-300">
                                    <span class="w-2 h-2 rounded-full bg-neutral-400"></span>
                                    Dilewati
                                </span>
                            </template>

                            <template x-if="status === 'dibatalkan'">
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-rose-50 text-rose-800 border border-rose-200">
                                    <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                                    Dibatalkan
                                </span>
                            </template>
                        </div>

                        <template x-if="counterName && (status === 'dipanggil' || status === 'sedang_dilayani')">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-brand-50 text-brand-800 border border-brand-200 shadow-2xs">
                                <svg class="w-3.5 h-3.5 text-brand-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                <span x-text="counterName"></span>
                            </span>
                        </template>
                    </div>
                </div>

                {{-- Status Details Callout Card --}}
                <div class="mt-6">
                    {{-- Status: MENUNGGU --}}
                    <div x-show="status === 'menunggu'" class="p-4 rounded-xl bg-amber-50/80 border border-amber-200 text-amber-900">
                        <div class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <div class="text-sm">
                                <div class="font-bold">
                                    <span x-show="aheadCount > 0">Ada <span class="text-amber-950 font-extrabold" x-text="aheadCount"></span> antrean di depan Anda</span>
                                    <span x-show="aheadCount === 0">Anda berada di urutan berikutnya!</span>
                                </div>
                                <p class="text-xs text-amber-800/90 mt-1 leading-relaxed">
                                    Mohon tetap berada di area tunggu atau bersiap ketika giliran nomor Anda mendekati pemanggilan.
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Status: DIPANGGIL --}}
                    <div x-show="status === 'dipanggil'" class="p-4 rounded-xl bg-blue-50 border-2 border-brand-500 text-blue-900 shadow-sm" style="display: none;">
                        <div class="flex items-start gap-3">
                            <svg class="w-6 h-6 text-brand-700 shrink-0 mt-0.5 animate-bounce" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
                            </svg>
                            <div class="text-sm">
                                <div class="font-extrabold text-base text-brand-950 flex flex-wrap items-center gap-2">
                                    <span>NOMOR ANDA SEDANG DIPANGGIL!</span>
                                    <template x-if="counterName">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-brand-700 text-white" x-text="'Menuju ' + counterName"></span>
                                    </template>
                                </div>
                                <p class="text-xs text-blue-800 mt-1 font-medium leading-relaxed">
                                    Silakan segera menuju <span class="font-bold" x-text="counterName ? counterName + ' (' + '{{ $business->name }}' + ')' : 'meja pelayanan / kasir {{ $business->name }}'"></span> untuk mendapatkan pelayanan.
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Status: SEDANG DILAYANI --}}
                    <div x-show="status === 'sedang_dilayani'" class="p-4 rounded-xl bg-teal-50 border border-teal-200 text-teal-900" style="display: none;">
                        <div class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-teal-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <div class="text-sm">
                                <div class="font-bold flex items-center gap-2">
                                    <span>Pelayanan Sedang Berlangsung</span>
                                    <template x-if="counterName">
                                        <span class="text-xs px-2 py-0.5 rounded bg-teal-200/70 text-teal-900 font-semibold" x-text="counterName"></span>
                                    </template>
                                </div>
                                <p class="text-xs text-teal-800/90 mt-1 leading-relaxed">
                                    Anda saat ini sedang dilayani di <span class="font-semibold" x-text="counterName || 'meja pelayanan'"></span> staf {{ $business->name }}. Terima kasih atas kesabaran Anda menunggu.
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Status: SELESAI --}}
                    <div x-show="status === 'selesai'" class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-900" style="display: none;">
                        <div class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-emerald-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <div class="text-sm">
                                <div class="font-bold">Pelayanan Selesai</div>
                                <p class="text-xs text-emerald-800 mt-1 leading-relaxed">
                                    Layanan Anda telah selesai. Terima kasih telah menggunakan layanan antrean digital AntreIn!
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Status: DILEWATI --}}
                    <div x-show="status === 'dilewati'" class="p-4 rounded-xl bg-neutral-100 border border-neutral-300 text-neutral-800" style="display: none;">
                        <div class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-neutral-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"/>
                            </svg>
                            <div class="text-sm">
                                <div class="font-bold">Antrean Dilewati</div>
                                <p class="text-xs text-neutral-600 mt-1 leading-relaxed">
                                    Nomor antrean Anda telah dilewati oleh operator karena tidak hadir saat dipanggil. Silakan hubungi staf kasir jika Anda masih berada di lokasi.
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Status: DIBATALKAN --}}
                    <div x-show="status === 'dibatalkan'" class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-900" style="display: none;">
                        <div class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-rose-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <div class="text-sm">
                                <div class="font-bold">Antrean Dibatalkan</div>
                                <p class="text-xs text-rose-700 mt-1 leading-relaxed" x-text="cancellationReason ? 'Alasan: ' + cancellationReason : 'Tiket antrean ini telah dibatalkan.'">
                                    {{ $ticket->cancellation_reason ? 'Alasan: ' . $ticket->cancellation_reason : 'Tiket antrean ini telah dibatalkan.' }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Key Operational Metrics Grid --}}
                <div class="mt-6 pt-5 border-t border-neutral-200 grid grid-cols-2 gap-4">
                    <div class="p-3.5 bg-neutral-50 rounded-xl border border-neutral-200/80 text-center">
                        <div class="text-[11px] font-semibold text-neutral-500 uppercase tracking-wider">
                            Sedang Dilayani
                        </div>
                        <div class="text-xl sm:text-2xl font-extrabold text-neutral-900 tabular-nums mt-0.5" x-text="nowServing">
                            {{ $servingTicket ? $servingTicket->ticket_number : '—' }}
                        </div>
                    </div>

                    <div class="p-3.5 bg-neutral-50 rounded-xl border border-neutral-200/80 text-center">
                        <div class="text-[11px] font-semibold text-neutral-500 uppercase tracking-wider">
                            Posisi Antrean
                        </div>
                        <div class="text-xl sm:text-2xl font-extrabold text-brand-700 tabular-nums mt-0.5" x-text="queuePosition > 0 && status === 'menunggu' ? '#' + queuePosition : '—'">
                            {{ $position > 0 && $ticket->status === 'menunggu' ? '#' . $position : '—' }}
                        </div>
                    </div>
                </div>

                {{-- Ticket Metadata Details --}}
                <div class="mt-6 pt-5 border-t border-neutral-200 space-y-2.5 text-xs text-neutral-600">
                    <div class="flex items-center justify-between">
                        <span class="text-neutral-500">Nama Pelanggan</span>
                        <span class="font-semibold text-neutral-900" x-text="customerName">{{ $ticket->customer_name }}</span>
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-neutral-500">Nomor Telepon</span>
                        <span class="font-mono text-neutral-900" x-text="customerPhone">{{ $maskedPhone ?: '—' }}</span>
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-neutral-500">Waktu Pendaftaran</span>
                        <span class="text-neutral-900">{{ $ticket->created_at->translatedFormat('H:i, d M Y') }}</span>
                    </div>

                    @if ($ticket->service->duration_minutes)
                        <div class="flex items-center justify-between">
                            <span class="text-neutral-500">Estimasi Durasi</span>
                            <span class="text-neutral-900">±{{ $ticket->service->duration_minutes }} menit</span>
                        </div>
                    @endif

                    @if ($ticket->service->price)
                        <div class="flex items-center justify-between">
                            <span class="text-neutral-500">Harga Layanan</span>
                            <span class="font-bold text-neutral-900">Rp {{ number_format($ticket->service->price, 0, ',', '.') }}</span>
                        </div>
                    @endif
                </div>

                {{-- Copy Tracking URL Box --}}
                <div class="mt-6 pt-5 border-t border-neutral-200" x-data="{ copied: false }">
                    <label class="block text-[11px] font-semibold uppercase tracking-wider text-neutral-500 mb-1.5">
                        Simpan Tautan Pelacakan
                    </label>

                    <div class="flex items-center gap-2">
                        <input 
                            type="text" 
                            readonly 
                            value="{{ url()->current() }}"
                            class="flex-1 px-3 py-2 text-xs font-mono bg-neutral-100 border border-neutral-300 rounded-xl text-neutral-700 select-all focus:outline-none"
                        >

                        <button 
                            type="button" 
                            @click="navigator.clipboard.writeText('{{ url()->current() }}'); copied = true; setTimeout(() => copied = false, 2500);"
                            class="shrink-0 px-3.5 py-2 text-xs font-bold text-brand-700 bg-brand-50 hover:bg-brand-100 border border-brand-200 rounded-xl transition flex items-center gap-1.5"
                        >
                            <svg x-show="!copied" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                            <svg x-show="copied" class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="display: none;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            <span x-text="copied ? 'Tersalin!' : 'Salin'">Salin</span>
                        </button>
                    </div>
                    <p class="text-[11px] text-neutral-400 mt-1">
                        Gunakan tautan ini untuk memantau status giliran Anda dari HP lain kapan saja.
                    </p>
                </div>
            </x-card>

            {{-- Footer link back to merchant --}}
            <div class="text-center">
                <a 
                    href="{{ route('customer.queue', ['slug' => $business->slug]) }}" 
                    class="text-xs font-semibold text-neutral-500 hover:text-brand-700 transition inline-flex items-center gap-1"
                >
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    Halaman Utama {{ $business->name }}
                </a>
            </div>
        </div>
    </div>

    {{-- Adaptive Polling Script Component --}}
    @push('scripts')
    <script>
        function queueTracker(config) {
            return {
                ticketId: config.ticketId,
                token: config.token,
                tokenHash: config.tokenHash,
                trackUrl: config.trackUrl,
                status: config.status,
                ticketNumber: config.ticketNumber,
                queuePosition: config.queuePosition,
                aheadCount: config.aheadCount,
                nowServing: config.nowServing,
                serviceName: config.serviceName,
                customerName: config.customerName,
                customerPhone: config.customerPhone,
                counterName: config.counterName || '',
                estimatedWaitMinutes: config.estimatedWaitMinutes,
                calledAt: config.calledAt,
                completedAt: config.completedAt,
                cancellationReason: config.cancellationReason,
                isExpired: config.isExpired,
                pollTimer: null,
                isPollingActive: true,
                isFetching: false,
                echoChannel: null,
                isRealtimeConnected: false,

                initTracker() {
                    window.__ANTREIN_TICKET_TOKEN__ = this.token;

                    // Initialize Real-time WebSocket listener with Laravel Echo & Reverb
                    this.initRealtime();

                    // Fallback Adaptive Polling schedule
                    this.scheduleNextPoll();

                    // Visibility awareness: pause timer when tab is hidden, resume immediately when visible
                    document.addEventListener('visibilitychange', () => {
                        if (document.hidden) {
                            this.clearPollTimer();
                        } else {
                            if (!this.isTerminalStatus() && !this.isExpired) {
                                this.fetchLatestStatus();
                                this.scheduleNextPoll();
                            }
                        }
                    });
                },

                initRealtime() {
                    if (window.Echo && this.tokenHash && !this.isTerminalStatus() && !this.isExpired) {
                        try {
                            this.echoChannel = window.Echo.private('ticket.' + this.tokenHash)
                                .listen('.QueueTicketStatusChanged', (e) => {
                                    if (e.ticket_id && e.ticket_id === this.ticketId) {
                                        this.status = e.status;
                                        if (e.counter_name) this.counterName = e.counter_name;
                                        this.fetchLatestStatus();
                                    }
                                })
                                .listen('.QueueTicketCounterAssigned', (e) => {
                                    if (e.ticket_id && e.ticket_id === this.ticketId) {
                                        if (e.counter_name) this.counterName = e.counter_name;
                                        this.fetchLatestStatus();
                                    }
                                });

                            this.isRealtimeConnected = true;
                        } catch (err) {
                            console.warn('Realtime channel subscription error:', err);
                        }
                    }
                },

                isTerminalStatus() {
                    return ['selesai', 'dilewati', 'dibatalkan'].includes(this.status);
                },

                getPollInterval() {
                    if (this.status === 'menunggu' || this.status === 'dipanggil') {
                        return 5000; // 5 seconds
                    }
                    if (this.status === 'sedang_dilayani') {
                        return 15000; // 15 seconds
                    }
                    return null; // Stop polling on terminal statuses
                },

                scheduleNextPoll() {
                    this.clearPollTimer();

                    const interval = this.getPollInterval();
                    if (!interval || this.isExpired) {
                        this.isPollingActive = false;
                        return;
                    }

                    this.isPollingActive = true;
                    this.pollTimer = setTimeout(() => {
                        this.fetchLatestStatus();
                    }, interval);
                },

                clearPollTimer() {
                    if (this.pollTimer) {
                        clearTimeout(this.pollTimer);
                        this.pollTimer = null;
                    }
                },

                async fetchLatestStatus() {
                    if (this.isFetching || this.isExpired) return;
                    this.isFetching = true;

                    try {
                        const response = await fetch(this.trackUrl, {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });

                        if (response.status === 410) {
                            this.isExpired = true;
                            this.isPollingActive = false;
                            this.clearPollTimer();
                            return;
                        }

                        if (!response.ok) {
                            // Non-200 response, schedule next try quietly
                            this.scheduleNextPoll();
                            return;
                        }

                        const result = await response.json();
                        if (result.success && result.data) {
                            const data = result.data;
                            const prevStatus = this.status;

                            this.status = data.status;
                            this.ticketNumber = data.ticket_number;
                            this.queuePosition = data.queue_position;
                            this.aheadCount = data.ahead_count ?? Math.max(0, data.queue_position - 1);
                            this.nowServing = data.now_serving || '—';
                            this.estimatedWaitMinutes = data.estimated_wait_minutes ?? 0;
                            if (data.counter_name) this.counterName = data.counter_name;
                            if (data.customer_name) this.customerName = data.customer_name;
                            if (data.customer_phone_masked) this.customerPhone = data.customer_phone_masked;

                            // If status transitioned to terminal, permanently cease polling
                            if (this.isTerminalStatus()) {
                                this.isPollingActive = false;
                                this.clearPollTimer();
                                return;
                            }

                            // Continue adaptive schedule
                            this.scheduleNextPoll();
                        }
                    } catch (err) {
                        // Resilient catch for offline/network glitches
                        this.scheduleNextPoll();
                    } finally {
                        this.isFetching = false;
                    }
                }
            };
        }
    </script>
    @endpush
</x-layouts.customer>

