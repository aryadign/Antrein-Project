{{-- Figma: Konsumen, Antrean Berhasil (1:1315) & Detail Tiket (1:1438) --}}
@php
    $isFreshTicket = (bool) session('ticket_created');
    $ticketStatuses = array_keys(\App\Models\QueueTicket::statusPresentation());
    $nextStepMessages = [
        'menunggu' => 'Tetap di sekitar kedai. Halaman ini diperbarui otomatis saat giliran Anda mendekat.',
        'dipanggil' => 'Segera menuju loket. Nomor Anda menunggu maksimal 15 menit.',
        'sedang_dilayani' => 'Anda sedang dilayani. Terima kasih sudah menunggu.',
    ];
    $journey = [
        ['key' => 'terdaftar', 'label' => 'Terdaftar', 'done' => 'true', 'current' => 'false'],
        ['key' => 'menunggu', 'label' => 'Menunggu', 'done' => "['dipanggil', 'sedang_dilayani', 'selesai'].includes(status)", 'current' => "status === 'menunggu'"],
        ['key' => 'dilayani', 'label' => 'Dilayani', 'done' => "status === 'selesai'", 'current' => "['dipanggil', 'sedang_dilayani'].includes(status)"],
        ['key' => 'selesai', 'label' => 'Selesai', 'done' => "status === 'selesai'", 'current' => 'false'],
    ];
    $registeredAt = $ticket->created_at->timezone($business->timezone ?: config('app.timezone'));
    $feedbackChips = ['Pelayanan cepat', 'Staf ramah', 'Antrean tertib', 'Tempat nyaman', 'Sesuai estimasi'];
    $cancelReasons = ['Waktu tunggu lama', 'Salah pilih layanan', 'Berubah pikiran'];
    $ratingLabels = [1 => 'Kurang memuaskan', 2 => 'Perlu perbaikan', 3 => 'Cukup baik', 4 => 'Baik', 5 => 'Sangat memuaskan'];
    $starPath = 'M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z';
    ['label' => $hoursLabel] = $business->customerHoursBadge();
    $bookingSlotWindow = $ticket->bookingSlotWindowLabel();
    $scheduleEstimate = $scheduleEstimate ?? [];
    $ticketFacts = array_values(array_filter([
        $bookingSlotWindow ? ['icon' => 'clock', 'label' => 'Slot jam booking', 'value' => $bookingSlotWindow, 'model' => 'bookingSlot'] : null,
        ['icon' => 'shopping-bag', 'label' => 'Cara pemesanan', 'value' => $ticket->source === \App\Models\QueueTicket::SOURCE_ONLINE ? 'Dari ponsel' : 'Datang langsung'],
        $maskedPhone ? ['icon' => 'phone', 'label' => 'WhatsApp', 'value' => $maskedPhone, 'model' => 'customerPhone'] : null,
        $ticket->service->duration_minutes ? ['icon' => 'clock', 'label' => 'Estimasi Durasi', 'value' => '±'.$ticket->service->duration_minutes.' menit'] : null,
        $ticket->service->price ? ['icon' => 'file-text', 'label' => 'Harga layanan', 'value' => 'Rp '.number_format($ticket->service->price, 0, ',', '.')] : null,
    ]));
@endphp

<x-layouts.customer :title="'Tiket '.$ticket->ticket_number.' — '.$business->name" :business="$business" activeNav="antrean">
    <div
        x-data="queueTracker({
            ticketId: {{ $ticket->id }},
            token: '{{ $ticket->access_token }}',
            tokenHash: '{{ hash('sha256', $ticket->access_token) }}',
            trackUrl: '{{ route('customer.queue.track', ['token' => $ticket->access_token]) }}',
            status: '{{ $ticket->status }}',
            ticketNumber: '{{ $ticket->ticket_number }}',
            bookingSlot: {{ json_encode($bookingSlotWindow) }},
            queuePosition: {{ (int) $position }},
            aheadCount: {{ (int) $aheadCount }},
            serviceName: '{{ addslashes($ticket->service->name) }}',
            customerName: '{{ addslashes($ticket->customer_name) }}',
            customerPhone: '{{ $maskedPhone }}',
            counterName: '{{ $ticket->counter ? addslashes($ticket->counter->name) : '' }}',
            estimatedWaitMinutes: {{ $estimate['minutes'] !== null ? (int) $estimate['minutes'] : 'null' }},
            estimatedWaitRange: {{ json_encode($estimate['range_label'] ?? null) }},
            estimatedWaitReason: {{ json_encode($estimate['reason']) }},
            estimatedServeTime: {{ json_encode($scheduleEstimate['estimated_serve_time_label'] ?? null) }},
            hasDelayShift: {{ ! empty($scheduleEstimate['has_delay_shift']) ? 'true' : 'false' }},
            shiftNotice: {{ json_encode($scheduleEstimate['shift_notice'] ?? null) }},
            calledAt: '{{ $ticket->called_at?->translatedFormat('H.i, d M Y') ?? '' }}',
            completedAt: '{{ $ticket->completed_at?->translatedFormat('H.i, d M Y') ?? '' }}',
            cancellationReason: '{{ addslashes($ticket->cancellation_reason ?? '') }}',
            lastUpdated: '{{ now($business->timezone ?: config('app.timezone'))->format('H.i') }}',
            isExpired: false
        })"
        x-init="initTracker()"
        @keydown.escape.window="cancelModalOpen = false"
    >
        {{-- Kedaluwarsa (dipicu klien saat respons 410) --}}
        <div x-show="isExpired" x-cloak class="flex flex-col items-center gap-3 px-5 py-10 text-center md:mx-auto md:max-w-lg">
            <h1 class="text-xl font-bold leading-7 text-gray-900">Tiket Pelacakan Kedaluwarsa</h1>
            <p class="text-sm leading-5 text-gray-700">Akses pelacakan tiket ini berakhir 24 jam setelah selesai atau dibatalkan. Demi privasi, data pribadi tidak lagi ditampilkan.</p>
            <a href="{{ route('customer.queue', ['slug' => $business->slug]) }}" class="flex h-12 w-full items-center justify-center rounded-xl bg-brand-700 text-sm font-semibold text-white transition hover:bg-blue-900">Kembali ke Halaman Kedai</a>
        </div>

        <div x-show="!isExpired" x-data="{ showSuccess: {{ $isFreshTicket ? 'true' : 'false' }} }">
            {{-- Antrean Berhasil: tampil sekali setelah tiket baru dibuat --}}
            @if ($isFreshTicket)
                <div x-show="showSuccess" x-transition.opacity.duration.200ms class="md:mx-auto md:max-w-xl">
                    <div class="border-b border-gray-200 bg-white px-5 pt-6 pb-4 md:border-0 md:bg-transparent md:px-0 md:pt-2 md:pb-6">
                        <h1 class="text-2xl font-extrabold leading-8 text-gray-900 md:text-3xl md:leading-10">Antrean Berhasil</h1>
                        <p class="mt-0.5 text-xs leading-4 text-gray-600 md:text-sm md:leading-5">Nomor Anda sudah masuk antrean.</p>
                    </div>

                    <div class="flex flex-col gap-4 px-5 pt-4 pb-5 md:px-0">
                        <div class="flex flex-col items-center gap-2.5">
                            <div class="relative h-[160px] w-[220px] overflow-hidden" aria-hidden="true">
                                <img src="{{ asset('images/figma/ticket-success.png') }}" alt="" class="absolute top-[-924.39%] left-[-53.48%] h-[1161.44%] w-[395.88%] max-w-none" />
                            </div>
                            <p class="text-center text-xl font-extrabold leading-7 text-gray-900">Anda berhasil masuk antrean!</p>
                        </div>

                        {{-- Two-Tone Pass Summary --}}
                        <div class="overflow-hidden rounded-2xl border border-brand-200 bg-white shadow-raised">
                            <div class="flex items-center justify-between gap-3 border-b border-brand-100 bg-brand-50/70 px-4 py-3">
                                <p class="truncate text-sm font-bold leading-5 text-gray-900">{{ $business->name }}</p>
                                <x-status-badge :status="$ticket" size="sm" class="shrink-0" />
                            </div>
                            <div class="p-4">
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <p class="text-xs font-medium text-gray-600">Nomor Anda</p>
                                        <p class="text-4xl font-extrabold leading-none text-brand-700 tabular-nums" x-text="ticketNumber">{{ $ticket->ticket_number }}</p>
                                    </div>
                                    <div class="text-right">
                                        <span class="inline-flex items-center gap-1 rounded-full bg-brand-50 px-2.5 py-1 text-xs font-bold text-brand-700">
                                            <x-customer.icon name="ticket" class="size-3.5" />
                                            {{ $ticket->service->name }}
                                        </span>
                                        <p class="mt-1 text-xs text-gray-600">Atas nama <span class="font-bold text-gray-900">{{ $ticket->customer_name }}</span></p>
                                    </div>
                                </div>
                                <div class="mt-3 flex flex-wrap items-center justify-between gap-2 border-t border-dashed border-gray-200 pt-3 text-xs">
                                    <span class="font-bold text-gray-900"><span x-text="aheadCount">{{ (int) $aheadCount }}</span> orang di depan Anda</span>
                                    <span class="font-extrabold text-brand-700 tabular-nums" x-text="estimatedServeTime ? 'Estimasi pukul ' + estimatedServeTime : (estimatedWaitRange ? estimatedWaitRange + ' lagi' : 'Estimasi menyesuaikan')">{{ ! empty($scheduleEstimate['estimated_serve_time_label']) ? 'Estimasi pukul '.$scheduleEstimate['estimated_serve_time_label'] : (($estimate['range_label'] ?? null) ? $estimate['range_label'].' lagi' : 'Estimasi menyesuaikan') }}</span>
                                </div>
                                <div x-show="bookingSlot" @if (! $bookingSlotWindow) x-cloak @endif class="mt-2 flex items-center justify-between gap-2 rounded-lg bg-brand-50 px-3 py-1.5 text-xs">
                                    <span class="font-semibold text-gray-700">Slot jam booking</span>
                                    <span class="font-extrabold text-brand-700 tabular-nums" x-text="bookingSlot">{{ $bookingSlotWindow }}</span>
                                </div>
                            </div>
                        </div>

                        {{-- QR Code Tiket (menggantikan tombol link tiket) --}}
                        <div class="flex flex-col items-center gap-3 rounded-2xl border border-gray-200 bg-white p-4 text-center shadow-card">
                            <p class="text-sm font-bold text-gray-900">QR Code Tiket</p>
                            <div class="rounded-xl border border-gray-200 bg-white p-2.5" data-qr-ticket>
                                {!! $qrSvg !!}
                            </div>
                            <p class="text-xs leading-4 text-gray-600">Simpan atau pindai QR Code ini untuk membuka kembali status tiket <span class="font-bold text-gray-900">{{ $ticket->ticket_number }}</span>.</p>
                            <x-button variant="outline" @click="downloadQr($el)">
                                <x-customer.icon name="qr-code" class="size-4" />
                                Unduh QR Tiket
                            </x-button>
                        </div>

                        <div class="flex flex-col gap-2.5">
                            <x-button size="lg" @click="showSuccess = false; window.scrollTo({ top: 0 })" class="w-full">
                                Lihat Tiket Saya
                            </x-button>
                            <x-button variant="outline" size="lg" :href="route('customer.dashboard')" class="w-full">
                                Kembali ke Beranda
                            </x-button>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Detail Tiket (Screen 4 & 1D) --}}
            <div x-show="!showSuccess" @if ($isFreshTicket) x-cloak @endif x-transition.opacity.duration.200ms>
                {{-- Header --}}
                <div class="border-b border-gray-200 bg-white px-5 pt-5 pb-4 md:border-0 md:bg-transparent md:px-0 md:pt-2 md:pb-6">
                    <div class="flex items-center gap-3">
                        <a href="{{ route('customer.my-tickets') }}" class="flex size-11 shrink-0 items-center justify-center rounded-xl border border-gray-200 bg-white text-gray-800 shadow-card transition hover:bg-gray-50" aria-label="Kembali ke Antrean Saya">
                            <x-customer.icon name="arrow-left" class="size-5" />
                        </a>
                        <div class="min-w-0 flex-1">
                            <h1 class="text-xl font-bold leading-7 text-gray-900 md:text-3xl md:leading-10">Detail Tiket</h1>
                            <p class="truncate text-sm leading-5 text-gray-600">{{ $business->name }}</p>
                        </div>
                        <span
                            class="inline-flex shrink-0 items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold"
                            :class="isPollingActive ? 'bg-emerald-50 text-emerald-800' : 'bg-gray-100 text-gray-700'"
                            role="status"
                        >
                            <span class="relative flex size-2" aria-hidden="true">
                                <span x-show="isPollingActive" class="absolute inline-flex size-full animate-ping rounded-full bg-emerald-500 opacity-60 motion-reduce:hidden"></span>
                                <span class="relative inline-flex size-2 rounded-full" :class="isPollingActive ? 'bg-emerald-600' : 'bg-gray-400'"></span>
                            </span>
                            <span x-text="isPollingActive ? 'Terhubung' : 'Selesai dipantau'">Terhubung</span>
                        </span>
                    </div>
                </div>

                {{-- Panggilan: banner paling menonjol saat nomor dipanggil (Screen 1D) --}}
                <div x-show="status === 'dipanggil'" @if ($ticket->status !== 'dipanggil') x-cloak @endif class="mx-5 mt-4 rounded-2xl bg-brand-700 p-4 text-white shadow-raised md:mx-0 md:p-5" role="alert">
                    <p class="flex items-center gap-2 text-lg font-bold leading-6 text-white">
                        <x-customer.icon name="volume" class="size-5" />
                        Nomor Anda sedang dipanggil
                    </p>
                    <p class="mt-1 text-sm leading-5 text-brand-100">Segera menuju <span class="font-bold text-white" x-text="counterName || 'meja pelayanan'">{{ $ticket->counter?->name ?? 'meja pelayanan' }}</span> di {{ $business->name }} dalam 15 menit.</p>

                    <div class="mt-3.5 flex items-center justify-between gap-3 rounded-xl bg-white px-4 py-3 text-gray-900">
                        <div>
                            <p class="text-xs font-medium text-gray-600">Nomor Anda</p>
                            <p class="text-3xl font-extrabold leading-none text-brand-700 tabular-nums" x-text="ticketNumber">{{ $ticket->ticket_number }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-xs font-medium text-gray-600">Menuju ke</p>
                            <p class="text-base font-bold text-gray-900" x-text="counterName || 'Loket pelayanan'">{{ $ticket->counter?->name ?? 'Loket pelayanan' }}</p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 px-5 pt-4 pb-5 md:px-0 lg:grid-cols-12 lg:items-start lg:gap-8">
                    {{-- Kolom utama: kartu tiket, status, ulasan, notifikasi --}}
                    <div class="flex min-w-0 flex-col gap-4 lg:col-span-7">
                        @if (session('status') && ! $isFreshTicket)
                            <div class="flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 p-3.5 text-sm font-semibold leading-5 text-emerald-800" role="status">
                                <x-customer.icon name="check-circle" class="size-4" />
                                {{ session('status') }}
                            </div>
                        @endif

                        @if ($errors->has('queue_error'))
                            <div class="flex items-center gap-2 rounded-xl border border-red-200 bg-red-50 p-3.5 text-sm font-semibold leading-5 text-danger-700" role="alert">
                                <x-customer.icon name="alert-triangle" class="size-4" />
                                {{ $errors->first('queue_error') }}
                            </div>
                        @endif

                        {{-- Kartu tiket utama: satu-satunya objek yang diangkat di layar ini --}}
                        <section class="overflow-hidden rounded-2xl border border-brand-200 bg-white shadow-raised" aria-labelledby="ticket-card-title">
                            <header class="flex items-start justify-between gap-3 border-b border-brand-100 bg-brand-50/70 px-4 py-3 md:px-5">
                                <div class="min-w-0">
                                    <h2 id="ticket-card-title" class="text-sm font-bold leading-5 text-gray-900" x-text="serviceName">{{ $ticket->service->name }}</h2>
                                    <p class="truncate text-xs leading-4 text-gray-600">{{ $business->name }} · {{ $hoursLabel }}</p>
                                </div>
                                @foreach ($ticketStatuses as $statusKey)
                                    <span x-show="status === '{{ $statusKey }}'" @if ($ticket->status !== $statusKey) x-cloak @endif class="shrink-0">
                                        <x-status-badge :status="$statusKey" size="sm" />
                                    </span>
                                @endforeach
                            </header>

                            <div class="flex flex-col gap-4 p-4 md:p-5">
                                <div>
                                    <p class="text-xs font-medium leading-4 text-gray-600">Nomor antrean</p>
                                    <p class="mt-1 text-5xl font-extrabold leading-none tracking-tight whitespace-nowrap text-brand-700 tabular-nums" x-text="ticketNumber">{{ $ticket->ticket_number }}</p>
                                </div>

                                <dl x-show="status === 'menunggu'" @if ($ticket->status !== 'menunggu') x-cloak @endif class="grid grid-cols-2 border-y border-gray-100 py-3">
                                    <div class="flex min-w-0 flex-col gap-0.5 pr-3">
                                        <dt class="text-xs font-medium leading-4 text-gray-600">Perkiraan dilayani</dt>
                                        <dd class="text-lg font-bold leading-6 text-gray-900 tabular-nums" x-text="estimatedServeTime ? estimatedServeTime : 'Menyesuaikan'">{{ $scheduleEstimate['estimated_serve_time_label'] ?? 'Menyesuaikan' }}</dd>
                                        <dd x-show="bookingSlot" @if (! $bookingSlotWindow) x-cloak @endif class="text-xs leading-4 text-gray-600 tabular-nums" x-text="'Slot ' + bookingSlot">{{ $bookingSlotWindow ? 'Slot '.$bookingSlotWindow : '' }}</dd>
                                    </div>
                                    <div class="flex min-w-0 flex-col gap-0.5 border-l border-gray-100 pl-3">
                                        <dt class="text-xs font-medium leading-4 text-gray-600">Di depan Anda</dt>
                                        <dd class="text-lg font-bold leading-6 text-gray-900 tabular-nums" x-text="aheadCount > 0 ? aheadCount + ' orang' : 'Tidak ada'">{{ $aheadCount > 0 ? $aheadCount.' orang' : 'Tidak ada' }}</dd>
                                        <dd class="text-xs leading-4 text-gray-600 tabular-nums" x-text="'Tunggu ' + (estimatedWaitRange ? estimatedWaitRange : (estimatedWaitMinutes !== null ? '± ' + estimatedWaitMinutes + ' menit' : 'belum tersedia'))">Tunggu {{ $estimate['range_label'] ?? ($estimate['minutes'] !== null ? '± '.$estimate['minutes'].' menit' : 'belum tersedia') }}</dd>
                                    </div>
                                </dl>

                                <p x-show="status === 'menunggu' && hasDelayShift && shiftNotice" @if ($ticket->status !== 'menunggu' || empty($scheduleEstimate['has_delay_shift']) || empty($scheduleEstimate['shift_notice'])) x-cloak @endif class="flex items-start gap-2 rounded-lg bg-amber-50 px-3 py-2 text-xs leading-4 text-amber-900">
                                    <x-customer.icon name="alert-triangle" class="mt-px size-3.5 text-amber-700" />
                                    <span x-text="shiftNotice">{{ $scheduleEstimate['shift_notice'] ?? '' }}</span>
                                </p>

                                {{-- Langkah berikutnya: satu kalimat aksi sesuai status --}}
                                <p x-show="!isTerminalStatus()" @if ($ticket->isTerminal()) x-cloak @endif class="flex items-start gap-2 text-sm leading-5 text-gray-800">
                                    <x-customer.icon name="bell" class="mt-0.5 size-4 text-brand-700" />
                                    <span x-text="nextStepMessage()">{{ $nextStepMessages[$ticket->status] ?? '' }}</span>
                                </p>

                                {{-- Perjalanan antrean --}}
                                <div class="flex flex-col gap-2" role="group" aria-label="Perjalanan antrean Anda">
                                    <div class="flex items-center justify-between gap-2 text-xs leading-4">
                                        <span class="font-medium text-gray-600">Perjalanan antrean Anda</span>
                                        <span class="font-semibold text-gray-900 tabular-nums" x-text="'Tahap ' + currentStage() + ' dari 4'">Tahap 2 dari 4</span>
                                    </div>
                                    <div class="grid grid-cols-4 gap-1.5">
                                        @foreach ($journey as $step)
                                            <div class="h-1.5 rounded-full transition-colors duration-300" :class="({{ $step['done'] }}) ? 'bg-brand-700' : (({{ $step['current'] }}) ? 'bg-brand-400' : 'bg-gray-200')"></div>
                                        @endforeach
                                    </div>
                                    <div class="grid grid-cols-4 gap-1.5 text-xs leading-4">
                                        @foreach ($journey as $step)
                                            <span class="truncate" :class="({{ $step['done'] }} || {{ $step['current'] }}) ? 'font-semibold text-brand-700' : 'text-gray-600'">{{ $step['label'] }}</span>
                                        @endforeach
                                    </div>
                                </div>
                            </div>

                            <footer class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1 border-t border-gray-100 bg-gray-50/60 px-4 py-2.5 text-xs leading-4 text-gray-600 md:px-5">
                                <span>Atas nama <span class="font-semibold text-gray-900" x-text="customerName">{{ $ticket->customer_name }}</span></span>
                                <span class="inline-flex items-center gap-1">
                                    <x-customer.icon name="clock" class="size-3.5 text-gray-500" />
                                    Diambil {{ $registeredAt->translatedFormat('d M Y') }}, {{ $registeredAt->format('H.i') }}
                                </span>
                            </footer>
                        </section>

                        {{-- Cara estimasi dihitung: detail sekunder disembunyikan sampai dibutuhkan --}}
                        <details x-show="status === 'menunggu'" @if ($ticket->status !== 'menunggu') x-cloak @endif class="group rounded-xl border border-gray-200 bg-white shadow-card">
                            <summary class="flex min-h-11 cursor-pointer list-none items-center justify-between gap-2 px-4 py-2.5 text-sm font-semibold text-gray-900 [&::-webkit-details-marker]:hidden">
                                Bagaimana perkiraan dihitung?
                                <x-customer.icon name="chevron-down" class="size-4 text-gray-500 transition-transform duration-200 group-open:rotate-180" />
                            </summary>
                            <div class="flex flex-col gap-1.5 border-t border-gray-100 px-4 py-3 text-sm leading-5">
                                <p class="text-gray-700" x-show="estimatedServeTime" @if (empty($scheduleEstimate['estimated_serve_time_label'])) style="display: none;" @endif>
                                    Perkiraan giliran Anda dilayani sekitar <span class="font-semibold text-gray-900" x-text="'pukul ' + estimatedServeTime">{{ ! empty($scheduleEstimate['estimated_serve_time_label']) ? 'pukul '.$scheduleEstimate['estimated_serve_time_label'] : '' }}</span> berdasarkan slot booking, antrean di depan, dan loket aktif.
                                </p>
                                <p class="text-gray-700" x-show="estimatedWaitMinutes !== null" @if ($estimate['minutes'] === null) style="display: none;" @endif>
                                    Perkiraan waktu tunggu <span class="font-semibold text-gray-900" x-text="estimatedWaitRange ? estimatedWaitRange : (estimatedWaitMinutes !== null ? '± ' + estimatedWaitMinutes + ' menit' : '')">@if ($estimate['minutes'] !== null) {{ $estimate['range_label'] ?? '± '.$estimate['minutes'].' menit' }} @endif</span>, dihitung dari jumlah antrean, riwayat pelayanan, dan loket aktif (estimasi ini bukan janji waktu pelayanan).
                                </p>
                                <p class="text-gray-700" x-show="estimatedWaitMinutes === null" x-text="estimateFallbackMessage()" @if ($estimate['minutes'] !== null) style="display: none;" @endif>
                                    @if ($estimate['minutes'] === null)
                                        {{ $estimate['reason'] === 'no_active_counters'
                                            ? 'Estimasi waktu tunggu tidak tersedia karena belum ada loket yang aktif.'
                                            : 'Estimasi waktu tunggu belum tersedia karena riwayat pelayanan historis belum cukup.' }}
                                    @endif
                                </p>
                                <p class="text-xs text-gray-600">Diperbarui pukul <span x-text="lastUpdated"></span></p>
                            </div>
                        </details>

                        <div x-show="status === 'sedang_dilayani'" @if ($ticket->status !== 'sedang_dilayani') x-cloak @endif class="flex flex-col gap-1 rounded-xl border border-green-200 bg-green-50 p-3.5 text-xs leading-4">
                            <p class="font-bold text-green-800">Pelayanan Sedang Berlangsung</p>
                            <p class="text-gray-700">Anda sedang dilayani di <span class="font-semibold" x-text="counterName || 'meja pelayanan'"></span>. Terima kasih sudah menunggu.</p>
                        </div>

                        <div x-show="status === 'tidak_hadir'" @if ($ticket->status !== 'tidak_hadir') x-cloak @endif class="flex flex-col gap-1 rounded-xl border border-rose-200 bg-rose-50 p-3.5 text-xs leading-4">
                            <p class="font-bold text-rose-900">Tidak Hadir (Batas 15 Menit Terlewati)</p>
                            <p class="text-gray-700">Tiket antrean ini dibatalkan otomatis karena Anda belum hadir dalam waktu 15 menit setelah dipanggil oleh petugas.</p>
                        </div>

                        <div x-show="status === 'dilewati'" @if ($ticket->status !== 'dilewati') x-cloak @endif class="flex flex-col gap-1 rounded-xl border border-gray-200 bg-gray-100 p-3.5 text-xs leading-4">
                            <p class="font-bold text-gray-900">Antrean Dilewati</p>
                            <p class="text-gray-700">Nomor Anda dilewati karena tidak hadir saat dipanggil. Hubungi staf kedai jika Anda masih di lokasi.</p>
                        </div>

                        <div x-show="status === 'dibatalkan'" @if ($ticket->status !== 'dibatalkan') x-cloak @endif class="flex flex-col gap-1 rounded-xl border border-red-200 bg-red-50 p-3.5 text-xs leading-4">
                            <p class="font-bold text-danger-700">Antrean Dibatalkan</p>
                            <p class="text-gray-700" x-text="cancellationReason ? 'Alasan: ' + cancellationReason : 'Tiket antrean ini telah dibatalkan.'">{{ $ticket->cancellation_reason ? 'Alasan: '.$ticket->cancellation_reason : 'Tiket antrean ini telah dibatalkan.' }}</p>
                        </div>

                        {{-- Selesai + ulasan (Screen 1D emerald-accented review card) --}}
                        <div x-show="status === 'selesai'" @if ($ticket->status !== 'selesai') x-cloak @endif class="flex flex-col gap-3">
                            <div class="flex flex-col gap-1 rounded-xl border border-emerald-200 bg-emerald-50 p-3.5 text-xs leading-4">
                                <p class="font-bold text-emerald-800">Pelayanan Selesai</p>
                                <p class="text-gray-700">Terima kasih telah menggunakan antrean digital AntreIn!</p>
                            </div>

                            @if ($review)
                                <div class="flex flex-col gap-3 rounded-2xl border border-emerald-200 bg-emerald-50/40 p-4 shadow-card">
                                    <div class="flex items-center justify-between gap-2">
                                        <div class="flex items-center gap-1.5 text-sm font-bold text-gray-900">
                                            <span>Penilaian Anda:</span>
                                            <span class="flex items-center" aria-label="{{ $review->rating }} dari 5 bintang">
                                                @for ($i = 1; $i <= 5; $i++)
                                                    <svg class="size-4 fill-current {{ $i <= $review->rating ? 'text-amber-400' : 'text-gray-300' }}" viewBox="0 0 20 20" aria-hidden="true"><path d="{{ $starPath }}"/></svg>
                                                @endfor
                                            </span>
                                        </div>
                                        <span class="text-xs text-gray-500">{{ $review->created_at->translatedFormat('d M Y, H.i') }}</span>
                                    </div>
                                    <span class="w-fit rounded-full bg-amber-50 px-2.5 py-1 text-xs font-bold text-amber-900 ring-1 ring-inset ring-amber-500/25">{{ $ratingLabels[$review->rating] ?? '' }}</span>
                                    @if ($review->comment)
                                        <p class="rounded-xl border border-emerald-200 bg-white p-3 text-sm leading-5 text-gray-800">"{{ $review->comment }}"</p>
                                    @endif
                                    <p class="flex items-center gap-1.5 text-xs font-bold text-emerald-800">
                                        <x-customer.icon name="check" class="size-4" />
                                        Ulasan Anda telah tersimpan. Terima kasih atas masukan Anda!
                                    </p>
                                </div>
                            @else
                                <form
                                    method="POST"
                                    action="{{ route('customer.review.store', ['token' => $ticket->access_token]) }}"
                                    x-data="{
                                        rating: {{ (int) old('rating', 0) }},
                                        hoverRating: 0,
                                        comment: @js((string) old('comment', '')),
                                        labels: @js($ratingLabels),
                                        isSubmitting: false,
                                        parts() {
                                            return this.comment.split(',').map((part) => part.trim()).filter((part) => part !== '');
                                        },
                                        hasChip(chip) {
                                            return this.parts().includes(chip);
                                        },
                                        toggleChip(chip) {
                                            const parts = this.parts();
                                            this.comment = (parts.includes(chip) ? parts.filter((part) => part !== chip) : [...parts, chip]).join(', ').slice(0, 1000);
                                        },
                                    }"
                                    @submit="if (rating < 1 || isSubmitting) { $event.preventDefault(); return; } isSubmitting = true;"
                                    class="flex flex-col gap-4 rounded-2xl border border-emerald-200 bg-emerald-50/50 p-4 shadow-card md:p-5"
                                >
                                    @csrf
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="flex flex-col gap-0.5">
                                            <h2 class="text-base font-extrabold leading-6 text-emerald-950">Bagaimana pelayanan hari ini?</h2>
                                            <p class="text-xs leading-4 text-emerald-800">Ulasan Anda membantu {{ $business->name }} melayani lebih baik.</p>
                                        </div>
                                        <span x-show="(hoverRating || rating) > 0" x-cloak class="shrink-0 rounded-full bg-white px-2.5 py-0.5 text-xs font-bold text-emerald-800 ring-1 ring-emerald-200" x-text="(hoverRating || rating) + ' / 5 Bintang'"></span>
                                    </div>

                                    <div class="flex flex-col gap-2">
                                        <div class="flex items-center gap-1.5" @mouseleave="hoverRating = 0" role="radiogroup" aria-label="Penilaian bintang">
                                            <template x-for="star in [1, 2, 3, 4, 5]" :key="star">
                                                <button
                                                    type="button"
                                                    @click="rating = star"
                                                    @mouseenter="hoverRating = star"
                                                    role="radio"
                                                    :aria-checked="rating === star"
                                                    class="flex size-10 items-center justify-center rounded-xl border bg-white transition hover:scale-105 focus-visible:outline-2 focus-visible:outline-brand-700"
                                                    :class="star <= (hoverRating || rating) ? 'border-amber-300 bg-amber-50/40' : 'border-gray-200'"
                                                    :aria-label="star + ' bintang'"
                                                >
                                                    <svg class="size-6 fill-current transition-colors" :class="star <= (hoverRating || rating) ? 'text-amber-400' : 'text-gray-300'" viewBox="0 0 20 20" aria-hidden="true"><path d="{{ $starPath }}"/></svg>
                                                </button>
                                            </template>
                                            <input type="hidden" name="rating" :value="rating">
                                        </div>
                                        <p class="text-xs font-bold leading-4" :class="(hoverRating || rating) ? 'text-amber-800' : 'text-emerald-800/80'" x-text="(hoverRating || rating) ? labels[hoverRating || rating] : 'Ketuk bintang untuk menilai'">Ketuk bintang untuk menilai</p>
                                    </div>
                                    @error('rating')
                                        <p class="text-xs leading-4 text-danger-700">{{ $message }}</p>
                                    @enderror

                                    <div class="flex flex-col gap-2">
                                        <p class="text-xs font-bold leading-4 text-emerald-950">Apa yang paling terasa?</p>
                                        <div class="flex flex-wrap gap-1.5" role="group" aria-label="Masukan cepat">
                                            @foreach ($feedbackChips as $chip)
                                                <button
                                                    type="button"
                                                    @click="toggleChip(@js($chip))"
                                                    :aria-pressed="hasChip(@js($chip))"
                                                    :class="hasChip(@js($chip)) ? 'border-emerald-700 bg-emerald-700 text-white' : 'border-emerald-200 bg-white text-emerald-900 hover:border-emerald-500'"
                                                    class="inline-flex h-8 items-center gap-1.5 rounded-full border px-3 text-xs font-bold transition active:scale-[0.97]"
                                                >
                                                    <x-customer.icon name="check" class="size-3.5" x-show="hasChip({{ Js::from($chip) }})" x-cloak />
                                                    {{ $chip }}
                                                </button>
                                            @endforeach
                                        </div>
                                    </div>

                                    <div class="flex flex-col gap-1.5">
                                        <div class="flex items-center justify-between">
                                            <label for="review-comment" class="flex items-center gap-1.5 text-xs font-bold leading-4 text-emerald-950">
                                                Ulasan tertulis
                                                <span class="rounded-md bg-white px-2 py-0.5 text-xs font-bold text-gray-600">Opsional</span>
                                            </label>
                                            <span class="text-xs text-emerald-800/80 tabular-nums" x-text="comment.length + '/1000'"></span>
                                        </div>
                                        <textarea id="review-comment" name="comment" x-model="comment" maxlength="1000" rows="3" placeholder="Ceritakan pengalaman Anda, misalnya: pesanan cepat disiapkan dan panggilan sesuai estimasi." class="resize-none rounded-xl border border-emerald-200 bg-white p-3 text-sm leading-5 text-gray-900 placeholder:text-gray-500 focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-100"></textarea>
                                        @error('comment')
                                            <p class="text-xs leading-4 text-danger-700">{{ $message }}</p>
                                        @enderror
                                        @error('review')
                                            <p class="text-xs leading-4 text-danger-700">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <button type="submit" :disabled="rating < 1 || isSubmitting" class="flex h-11 w-full items-center justify-center rounded-xl bg-emerald-700 text-sm font-bold text-white shadow-xs transition hover:bg-emerald-800 active:scale-[0.99] disabled:cursor-not-allowed disabled:bg-gray-200 disabled:text-gray-500">
                                        <span x-text="isSubmitting ? 'Mengirim…' : 'Kirim Ulasan'">Kirim Ulasan</span>
                                    </button>
                                </form>
                            @endif
                        </div>

                        {{-- Notifikasi peramban (hanya bila kunci VAPID tersedia) --}}
                        @if (! empty(config('webpush.vapid.public_key')))
                            <div
                                x-show="!isTerminalStatus()"
                                x-data="customerPushManager({
                                    token: '{{ $ticket->access_token }}',
                                    vapidPublicKey: '{{ config('webpush.vapid.public_key') }}',
                                    subscribeUrl: '{{ route('customer.push.subscribe') }}',
                                    unsubscribeUrl: '{{ route('customer.push.unsubscribe') }}'
                                })"
                                x-init="checkSubscription()"
                                class="flex flex-col gap-2 rounded-2xl border border-gray-200 bg-white p-4 shadow-card"
                            >
                                <div class="flex items-center justify-between gap-2">
                                    <div class="flex items-center gap-2">
                                        <img src="{{ asset('images/figma/notification.svg') }}" alt="" class="size-5 rounded bg-brand-700 p-0.5" />
                                        <p class="text-sm font-bold leading-5 text-gray-900">Notifikasi giliran</p>
                                    </div>
                                    <span x-show="isSubscribed" x-cloak class="rounded-full bg-green-50 px-2.5 py-0.5 text-xs font-bold text-success-700">Aktif</span>
                                </div>
                                <p class="text-xs leading-4 text-gray-600">Dapat peringatan saat nomor Anda dipanggil, meski layar ponsel sedang mati.</p>
                                <p x-show="permission === 'denied'" x-cloak class="text-xs font-medium leading-4 text-danger-700">Izin notifikasi diblokir. Buka pengaturan situs di peramban untuk mengaktifkannya.</p>
                                <div x-show="isSupported && permission !== 'denied'" class="flex flex-wrap items-center gap-2">
                                    <button type="button" x-show="!isSubscribed" @click="subscribe()" :disabled="isLoading" class="flex h-10 items-center rounded-xl bg-brand-700 px-4 text-xs font-bold text-white transition hover:bg-blue-900 disabled:opacity-50">
                                        <span x-text="isLoading ? 'Menghubungkan…' : 'Aktifkan Notifikasi Giliran'">Aktifkan Notifikasi Giliran</span>
                                    </button>
                                    <button type="button" x-show="isSubscribed" x-cloak @click="unsubscribe()" :disabled="isLoading" class="flex h-10 items-center rounded-xl bg-gray-100 px-4 text-xs font-bold text-gray-700 transition hover:bg-gray-200 disabled:opacity-50">
                                        <span x-text="isLoading ? 'Menghapus…' : 'Nonaktifkan Notifikasi'"></span>
                                    </button>
                                    <span x-show="message" class="text-xs font-semibold" :class="isError ? 'text-danger-700' : 'text-success-700'" x-text="message"></span>
                                </div>
                            </div>
                        @endif

                    </div>

                    {{-- Kolom pendukung: QR, informasi tiket, aksi sekunder --}}
                    <div class="flex min-w-0 flex-col gap-4 lg:col-span-5">
                        {{-- QR Code Tiket --}}
                        <div class="flex flex-col items-center gap-3 rounded-2xl border border-gray-200 bg-white p-4 text-center shadow-card md:p-5">
                            <div class="flex w-full flex-col gap-0.5 text-left">
                                <h2 class="text-sm font-bold leading-5 text-gray-900">QR Code Tiket</h2>
                                <p class="text-xs leading-4 text-gray-600">Pindai untuk membuka kembali tiket <span class="font-semibold text-gray-900" x-text="ticketNumber">{{ $ticket->ticket_number }}</span> di perangkat lain.</p>
                            </div>
                            <div class="rounded-xl border border-gray-200 bg-white p-3" data-qr-ticket>
                                {!! $qrSvg !!}
                            </div>
                            <x-button variant="outline" @click="downloadQr($el)" class="w-full">
                                <x-customer.icon name="qr-code" class="size-4" />
                                Unduh QR Tiket
                            </x-button>
                        </div>

                        {{-- Informasi tiket --}}
                        <dl class="flex flex-col rounded-2xl border border-gray-200 bg-white px-4 py-1 shadow-card md:px-5">
                            @foreach ($ticketFacts as $row)
                                <div class="flex items-center justify-between gap-3 border-b border-gray-100 py-3 last:border-0">
                                    <dt class="flex items-center gap-2.5 text-sm leading-5 text-gray-600">
                                        <img src="{{ asset('images/figma/'.$row['icon'].'.svg') }}" alt="" width="18" height="18" class="size-4.5 shrink-0 opacity-80" />
                                        {{ $row['label'] }}
                                    </dt>
                                    <dd class="truncate text-right text-sm font-semibold leading-5 text-gray-900 tabular-nums" @isset($row['model']) x-text="{{ $row['model'] }}" @endisset>{{ $row['value'] }}</dd>
                                </div>
                            @endforeach
                        </dl>

                        <x-button variant="outline" :href="route('customer.queue', ['slug' => $business->slug])" class="w-full">
                            Lihat layanan {{ $business->name }}
                        </x-button>

                        {{-- Batalkan Antrean: aksi destruktif diletakkan terakhir dan selalu dikonfirmasi --}}
                        @if ($ticket->status === \App\Models\QueueTicket::STATUS_MENUNGGU)
                            <div x-show="status === 'menunggu'" class="flex flex-col gap-1.5 border-t border-gray-200 pt-4">
                                <button
                                    type="button"
                                    @click="cancelModalOpen = true"
                                    class="flex h-11 w-full items-center justify-center gap-2 rounded-lg border border-red-200 bg-white text-sm font-semibold text-danger-700 transition duration-150 ease-out hover:border-red-300 hover:bg-red-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-danger-700"
                                >
                                    Batalkan Antrean
                                </button>
                                <p class="text-center text-xs leading-4 text-gray-600">Nomor yang dibatalkan tidak bisa dipulihkan.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Bottom Sheet / Modal Konfirmasi Batalkan Antrean (Screen 1C) --}}
        @if ($ticket->status === \App\Models\QueueTicket::STATUS_MENUNGGU)
            <div
                x-show="cancelModalOpen && status === 'menunggu'"
                x-cloak
                x-transition.opacity.duration.200ms
                class="fixed inset-0 z-50 flex items-end justify-center bg-slate-950/50 sm:items-center sm:p-4"
                role="dialog"
                aria-modal="true"
                aria-labelledby="cancel-sheet-title"
            >
                <div
                    x-show="cancelModalOpen && status === 'menunggu'"
                    x-transition:enter="transition duration-200 ease-out"
                    x-transition:enter-start="translate-y-6 opacity-0"
                    x-transition:enter-end="translate-y-0 opacity-100"
                    @click.outside="cancelModalOpen = false"
                    class="w-full max-w-[430px] rounded-t-2xl bg-white p-5 shadow-overlay sm:rounded-2xl"
                >
                    <div class="mx-auto mb-3 h-1 w-10 rounded-full bg-gray-300 sm:hidden" aria-hidden="true"></div>

                    <div class="mb-3 flex size-11 items-center justify-center rounded-full bg-red-50 text-danger-700">
                        <x-customer.icon name="alert-triangle" class="size-5.5" />
                    </div>

                    <h2 id="cancel-sheet-title" class="text-lg font-bold leading-6 text-gray-900">
                        Batalkan Antrean <span x-text="ticketNumber">{{ $ticket->ticket_number }}</span>?
                    </h2>
                    <p class="mt-1 text-sm leading-5 text-gray-600">
                        Nomor <span class="font-bold text-gray-900" x-text="ticketNumber">{{ $ticket->ticket_number }}</span> di {{ $business->name }} akan dilepas. Anda harus mengambil nomor baru dari akhir antrean jika ingin mengantre kembali.
                    </p>

                    <form method="POST" action="{{ route('customer.queue.cancel', ['token' => $ticket->access_token]) }}" @submit="isCancelling = true" class="mt-4 flex flex-col gap-3">
                        @csrf
                        <div class="flex flex-col gap-2 border-t border-gray-100 pt-3">
                            <p class="text-sm font-semibold text-gray-900">Alasan pembatalan <span class="font-normal text-gray-600">(opsional)</span></p>
                            <div class="flex flex-wrap gap-1.5">
                                @foreach ($cancelReasons as $reasonOption)
                                    <button
                                        type="button"
                                        @click="cancelReason = (cancelReason === @js($reasonOption) ? '' : @js($reasonOption))"
                                        :aria-pressed="cancelReason === @js($reasonOption)"
                                        :class="cancelReason === @js($reasonOption) ? 'border-brand-700 bg-brand-50 text-brand-700 font-bold' : 'border-gray-300 bg-white text-gray-700 hover:border-gray-400'"
                                        class="inline-flex min-h-9 items-center rounded-full border px-3 text-sm font-medium transition"
                                    >{{ $reasonOption }}</button>
                                @endforeach
                            </div>
                            <input
                                type="text"
                                name="cancellation_reason"
                                x-model="cancelReason"
                                maxlength="255"
                                placeholder="Atau tulis alasan lain…"
                                class="mt-1 h-11 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm text-gray-900 placeholder:text-gray-500 focus:border-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-100"
                            />
                        </div>

                        <div class="flex flex-col gap-2">
                            <button
                                type="button"
                                @click="cancelModalOpen = false"
                                class="flex h-11 w-full items-center justify-center rounded-lg bg-brand-700 text-sm font-semibold text-white shadow-card transition hover:bg-brand-800"
                            >
                                Tetap antre
                            </button>
                            <button
                                type="submit"
                                :disabled="isCancelling"
                                class="flex h-11 w-full items-center justify-center rounded-lg border border-red-300 bg-white text-sm font-semibold text-danger-700 transition hover:bg-red-50 disabled:opacity-60"
                            >
                                <span x-text="isCancelling ? 'Membatalkan…' : 'Ya, Batalkan Nomor ' + ticketNumber">Ya, Batalkan Nomor {{ $ticket->ticket_number }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
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
                bookingSlot: config.bookingSlot ?? null,
                queuePosition: config.queuePosition,
                aheadCount: config.aheadCount,
                serviceName: config.serviceName,
                customerName: config.customerName,
                customerPhone: config.customerPhone,
                counterName: config.counterName || '',
                estimatedWaitMinutes: config.estimatedWaitMinutes,
                estimatedWaitRange: config.estimatedWaitRange,
                estimatedWaitReason: config.estimatedWaitReason,
                estimatedServeTime: config.estimatedServeTime ?? null,
                hasDelayShift: Boolean(config.hasDelayShift),
                shiftNotice: config.shiftNotice ?? null,
                calledAt: config.calledAt,
                completedAt: config.completedAt,
                cancellationReason: config.cancellationReason,
                lastUpdated: config.lastUpdated,
                isExpired: config.isExpired,
                cancelModalOpen: false,
                cancelReason: '',
                isCancelling: false,
                pollTimer: null,
                isPollingActive: true,
                isFetching: false,
                echoChannel: null,
                isRealtimeConnected: false,

                nextStepMessage() {
                    if (this.status === 'dipanggil') {
                        return 'Segera menuju ' + (this.counterName || 'loket') + '. Nomor Anda menunggu maksimal 15 menit.';
                    }
                    return @js($nextStepMessages)[this.status] ?? '';
                },

                currentStage() {
                    if (this.status === 'selesai') return 4;
                    if (this.status === 'dipanggil' || this.status === 'sedang_dilayani') return 3;
                    if (this.status === 'menunggu') return 2;
                    return 1;
                },

                downloadQr(triggerEl) {
                    const card = triggerEl.closest('div');
                    const svg = card ? card.querySelector('[data-qr-ticket] svg') : null;
                    if (!svg) return;
                    const serializer = new XMLSerializer();
                    const blob = new Blob([serializer.serializeToString(svg)], { type: 'image/svg+xml;charset=utf-8' });
                    const url = URL.createObjectURL(blob);
                    const link = document.createElement('a');
                    link.href = url;
                    link.download = 'tiket-' + (this.ticketNumber || 'antrein').toLowerCase() + '.svg';
                    document.body.appendChild(link);
                    link.click();
                    link.remove();
                    setTimeout(() => URL.revokeObjectURL(url), 1000);
                },

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
                    return ['selesai', 'dilewati', 'tidak_hadir', 'dibatalkan'].includes(this.status);
                },

                // M18 / P1-10: human-readable message when no historical estimate is available.
                estimateFallbackMessage() {
                    if (this.estimatedWaitReason === 'no_active_counters') {
                        return 'Estimasi waktu tunggu tidak tersedia karena belum ada loket yang aktif.';
                    }
                    return 'Estimasi waktu tunggu belum tersedia — riwayat pelayanan historis belum cukup untuk memberikan perkiraan yang andal.';
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
                            this.scheduleNextPoll();
                            return;
                        }

                        const result = await response.json();
                        if (result.success && result.data) {
                            const data = result.data;

                            this.status = data.status;
                            this.lastUpdated = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
                            this.ticketNumber = data.ticket_number;
                            this.bookingSlot = data.booking_slot ?? null;
                            this.queuePosition = data.queue_position;
                            this.aheadCount = data.ahead_count ?? Math.max(0, data.queue_position - 1);
                            this.estimatedWaitMinutes = data.estimated_wait_minutes ?? null;
                            this.estimatedWaitRange = data.estimated_wait_range ?? null;
                            this.estimatedWaitReason = data.estimated_wait_reason ?? null;
                            this.estimatedServeTime = data.estimated_serve_time ?? null;
                            this.hasDelayShift = Boolean(data.has_delay_shift);
                            this.shiftNotice = data.shift_notice ?? null;
                            if (data.counter_name) this.counterName = data.counter_name;
                            if (data.customer_name) this.customerName = data.customer_name;
                            if (data.customer_phone_masked) this.customerPhone = data.customer_phone_masked;

                            if (this.isTerminalStatus()) {
                                this.isPollingActive = false;
                                this.clearPollTimer();
                                return;
                            }

                            this.scheduleNextPoll();
                        }
                    } catch (err) {
                        this.scheduleNextPoll();
                    } finally {
                        this.isFetching = false;
                    }
                }
            };
        }

        function urlBase64ToUint8Array(base64String) {
            const padding = '='.repeat((4 - base64String.length % 4) % 4);
            const base64 = (base64String + padding).replace(/\-/g, '+').replace(/_/g, '/');
            const rawData = window.atob(base64);
            const outputArray = new Uint8Array(rawData.length);
            for (let i = 0; i < rawData.length; ++i) {
                outputArray[i] = rawData.charCodeAt(i);
            }
            return outputArray;
        }

        function customerPushManager(config) {
            return {
                token: config.token,
                vapidPublicKey: config.vapidPublicKey,
                vapidConfigured: (config.vapidPublicKey || '').length > 0,
                subscribeUrl: config.subscribeUrl,
                unsubscribeUrl: config.unsubscribeUrl,
                isSupported: ('serviceWorker' in navigator && 'PushManager' in window && 'Notification' in window),
                permission: ('Notification' in window) ? Notification.permission : 'default',
                isSubscribed: false,
                isLoading: false,
                message: '',
                isError: false,

                async checkSubscription() {
                    if (!this.isSupported) return;
                    try {
                        const registration = await navigator.serviceWorker.register('/sw.js');
                        const subscription = await registration.pushManager.getSubscription();
                        this.isSubscribed = !(subscription === null);
                    } catch (e) {
                        console.warn('Customer push check error:', e);
                    }
                },

                async subscribe() {
                    if (!this.isSupported || !this.vapidConfigured) return;
                    this.isLoading = true;
                    this.message = '';
                    this.isError = false;

                    try {
                        const perm = await Notification.requestPermission();
                        this.permission = perm;
                        if (perm !== 'granted') {
                            this.isError = true;
                            this.message = 'Izin notifikasi tidak diberikan.';
                            this.isLoading = false;
                            return;
                        }

                        const registration = await navigator.serviceWorker.register('/sw.js');
                        await navigator.serviceWorker.ready;

                        const convertedVapidKey = urlBase64ToUint8Array(this.vapidPublicKey);
                        const subscription = await registration.pushManager.subscribe({
                            userVisibleOnly: true,
                            applicationServerKey: convertedVapidKey
                        });

                        const subJson = subscription.toJSON();
                        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

                        const response = await fetch(this.subscribeUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: JSON.stringify({
                                token: this.token,
                                endpoint: subscription.endpoint,
                                public_key: subJson.keys?.p256dh,
                                auth_token: subJson.keys?.auth,
                                content_encoding: (PushManager.supportedContentEncodings || ['aesgcm'])[0]
                            })
                        });

                        const result = await response.json();
                        if (response.ok && result.success) {
                            this.isSubscribed = true;
                            this.message = 'Notifikasi berhasil diaktifkan!';
                        } else {
                            this.isError = true;
                            this.message = result.message || 'Gagal mengaktifkan notifikasi.';
                        }
                    } catch (err) {
                        this.isError = true;
                        this.message = 'Terjadi kesalahan: ' + err.message;
                    } finally {
                        this.isLoading = false;
                    }
                },

                async unsubscribe() {
                    if (!this.isSupported) return;
                    this.isLoading = true;
                    this.message = '';
                    this.isError = false;

                    try {
                        const registration = await navigator.serviceWorker.ready;
                        const subscription = await registration.pushManager.getSubscription();
                        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

                        if (subscription) {
                            await fetch(this.unsubscribeUrl, {
                                method: 'DELETE',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': csrfToken,
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest'
                                },
                                body: JSON.stringify({
                                    token: this.token,
                                    endpoint: subscription.endpoint
                                })
                            });

                            await subscription.unsubscribe();
                        }

                        this.isSubscribed = false;
                        this.message = 'Notifikasi dinonaktifkan.';
                    } catch (err) {
                        this.isError = true;
                        this.message = 'Gagal mematikan: ' + err.message;
                    } finally {
                        this.isLoading = false;
                    }
                }
            };
        }
    </script>
    @endpush
</x-layouts.customer>
