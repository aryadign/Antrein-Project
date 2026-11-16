{{-- Figma: Konsumen, Data Pemesan (1:1583) → Pilih Layanan (1:1508) → Konfirmasi Antrean (1:1360) --}}
@php
    $initialStep = match (true) {
        $errors->hasAny(['customer_name', 'customer_phone', 'customer_email']) => 1,
        $errors->hasAny(['service_id', 'booking_slot', 'queue_error']) => 2,
        default => 1,
    };

    $selectedId = (int) old('service_id', $selectedServiceId);
    $selectableIds = $services->reject(fn ($service) => $service->is_quota_full)->pluck('id');
    $bookingSlotsByService = $bookingSlotsByService ?? [];

    $wizardServices = $services->mapWithKeys(fn ($service) => [$service->id => [
        'name' => $service->name,
        'duration' => $service->duration_minutes,
        'price' => $service->price ? 'Rp '.number_format($service->price, 0, ',', '.') : null,
    ]]);

    $steps = [1 => 'Data diri', 2 => 'Layanan & Slot', 3 => 'Konfirmasi'];
    $stepTitles = [
        1 => ['Data Pemesan', 'Isi data Anda untuk mengambil antrean.'],
        2 => ['Pilih Layanan & Slot Jam', 'Pilih layanan dan waktu kedatangan yang masih tersedia.'],
        3 => ['Konfirmasi Antrean', 'Periksa data, layanan, dan slot jam sebelum bergabung.'],
    ];

    $prefillName = old('customer_name', session('customer_profile.name') ?? $customerUser?->name ?? auth()->user()?->name ?? '');
    $prefillPhone = old('customer_phone', session('customer_profile.phone') ?? '');
    $prefillEmail = old('customer_email', session('customer_profile.email') ?? $customerUser?->email ?? auth()->user()?->email ?? '');
    $prefillBookingSlot = old('booking_slot', '');
    $activeTicketCount = (int) ($activeTicketCount ?? 0);
    $maxActiveTickets = (int) ($maxActiveTickets ?? 3);
    $reachedTicketLimit = $activeTicketCount >= $maxActiveTickets;
    $waitRange = $waitRange ?? $business->customerWaitRange($waitingCount ?? 0);
@endphp

<x-layouts.customer :title="'Ambil Nomor Antrean — '.$business->name" :business="$business" activeNav="beranda">
    <form
        action="{{ route('customer.queue.take', ['slug' => $business->slug]) }}"
        method="POST"
        novalidate
        x-data="{
            step: {{ $initialStep }},
            isSubmitting: false,
            name: @js($prefillName),
            phone: @js($prefillPhone),
            email: @js($prefillEmail),
            notifyEmail: {{ old('notify_email') ? 'true' : 'false' }},
            serviceId: {{ $selectableIds->contains($selectedId) ? $selectedId : 'null' }},
            serviceQuery: '',
            services: @js($wizardServices),
            slotsByService: @js($bookingSlotsByService),
            bookingSlot: @js($prefillBookingSlot),
            nameError: false,
            emailError: false,
            init() {
                this.syncDefaultSlot();
                this.$watch('serviceId', () => this.syncDefaultSlot(true));
            },
            get service() {
                return this.serviceId ? this.services[this.serviceId] : null;
            },
            currentSlots() {
                if (!this.serviceId) return [];
                return this.slotsByService[String(this.serviceId)] || [];
            },
            hasAvailableSlot() {
                const slots = this.currentSlots();
                return slots.length === 0 || slots.some((s) => s.is_available);
            },
            selectedSlotObj() {
                return this.currentSlots().find((s) => s.slot_key === this.bookingSlot) || null;
            },
            selectedSlotLabel() {
                const obj = this.selectedSlotObj();
                return obj ? obj.label : (this.bookingSlot || 'Menyesuaikan jadwal tersedia');
            },
            syncDefaultSlot(forceReset = false) {
                const slots = this.currentSlots();
                if (!slots.length) {
                    this.bookingSlot = '';
                    return;
                }
                const current = !forceReset ? slots.find((s) => s.slot_key === this.bookingSlot && s.is_available) : null;
                if (current) return;
                const firstAvailable = slots.find((s) => s.is_available);
                this.bookingSlot = firstAvailable ? firstAvailable.slot_key : '';
            },
            goTo(target) {
                this.step = target;
                window.scrollTo({ top: 0, behavior: 'smooth' });
            },
            submitIdentity() {
                this.nameError = this.name.trim() === '';
                this.emailError = this.notifyEmail && ! /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.email.trim());
                if (this.nameError) { this.$refs.name.focus(); return; }
                if (this.emailError) { this.$refs.email.focus(); return; }
                this.goTo(2);
            },
            back() {
                this.step > 1 ? this.goTo(this.step - 1) : window.location.assign(@js(route('customer.queue', ['slug' => $business->slug])));
            },
        }"
        @submit="if (isSubmitting || step !== 3 || ! serviceId || {{ $reachedTicketLimit ? 'true' : 'false' }}) { $event.preventDefault(); return; } isSubmitting = true;"
        class="md:mx-auto md:max-w-2xl"
    >
        @csrf
        <input type="hidden" name="idempotency_key" value="{{ old('idempotency_key', $idempotencyKey) }}">
        <input type="hidden" name="booking_slot" :value="bookingSlot">

        {{-- Header & 3-segment progress bar (Screen 1B) --}}
        <div class="border-b border-gray-200 bg-white px-5 pt-6 pb-4 md:rounded-2xl md:border md:p-6 md:shadow-xs">
            <div class="flex items-start gap-3">
                <button type="button" @click="back()" class="mt-0.5 flex size-9 shrink-0 items-center justify-center rounded-xl bg-gray-100 text-gray-800 transition hover:bg-gray-200" aria-label="Kembali">
                    <x-customer.icon name="arrow-left" class="size-4.5" />
                </button>
                <div class="min-w-0 flex-1">
                    @foreach ($stepTitles as $number => [$heading])
                        <h1 x-show="step === {{ $number }}" @if ($number !== $initialStep) x-cloak @endif class="text-xl font-extrabold leading-7 text-gray-900 md:text-2xl md:leading-8">{{ $heading }}</h1>
                    @endforeach
                    @foreach ($stepTitles as $number => [, $subtitle])
                        <p x-show="step === {{ $number }}" @if ($number !== $initialStep) x-cloak @endif class="text-xs leading-4 text-gray-600 sm:text-sm">{{ $subtitle }}</p>
                    @endforeach
                    <div class="mt-2.5 flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-2.5 py-1 text-xs font-bold leading-4 text-gray-800">
                            <x-customer.icon name="store" class="size-3.5 text-brand-700" />
                            {{ $business->name }}
                        </span>
                        <x-customer.hours-badge :business="$business" />
                        @if ($waitRange)
                            <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2.5 py-1 text-xs font-bold text-amber-800 ring-1 ring-amber-200">
                                Estimasi {{ $waitRange['label'] }}
                            </span>
                        @endif
                        <span class="inline-flex items-center rounded-full {{ $reachedTicketLimit ? 'bg-red-50 text-danger-700 ring-red-200' : 'bg-brand-50 text-brand-700 ring-brand-200' }} px-2.5 py-1 text-xs font-bold ring-1 tabular-nums">
                            {{ $activeTicketCount }}/{{ $maxActiveTickets }} Antrean Aktif
                        </span>
                    </div>
                </div>
            </div>

            <ol class="mt-4 grid grid-cols-3 gap-2" aria-label="Tahap pengambilan antrean">
                @foreach ($steps as $number => $label)
                    <li class="flex min-w-0 flex-col gap-1.5">
                        <div class="h-1.5 w-full rounded-full transition-colors duration-300" :class="step >= {{ $number }} ? 'bg-brand-700' : 'bg-gray-200'"></div>
                        <span
                            class="truncate text-[11px] font-bold transition-colors duration-300 sm:text-xs"
                            :class="step >= {{ $number }} ? 'text-brand-700' : 'text-gray-500'"
                        >{{ $number }}. {{ $label }}</span>
                    </li>
                @endforeach
            </ol>
        </div>

        <div class="flex flex-col gap-4 px-5 pt-4 pb-5 md:px-0">
            @if ($errors->has('queue_error'))
                <div class="rounded-xl border border-red-200 bg-red-50 p-3.5 text-xs font-semibold leading-4 text-danger-700" role="alert">{{ $errors->first('queue_error') }}</div>
            @endif

            @if ($reachedTicketLimit)
                <div class="flex flex-col gap-1 rounded-xl border border-red-200 bg-red-50 p-3.5 text-xs leading-4" role="alert">
                    <p class="font-bold text-danger-700">Batas Maksimal {{ $maxActiveTickets }} Antrean Aktif Tercapai</p>
                    <p class="text-gray-700">Anda sudah memiliki {{ $activeTicketCount }} antrean aktif. Selesaikan atau batalkan salah satu tiket terlebih dahulu di menu <a href="{{ route('customer.my-tickets') }}" class="font-bold text-brand-700 underline">Antrean Saya</a>.</p>
                </div>
            @endif

            @unless ($business->canAcceptOnlineQueue())
                <div class="flex flex-col gap-1 rounded-xl border border-amber-200 bg-amber-50 p-3.5 text-xs leading-4" role="status">
                    <p class="font-bold text-amber-900">Kedai belum menerima antrean online</p>
                    <p class="text-gray-700">Silakan kembali saat kedai sudah buka.</p>
                </div>
            @endunless

            {{-- Context summary chip saat layanan sudah dipilih di Tahap 1 --}}
            <div x-show="step === 1 && service" x-cloak class="flex items-center gap-3 rounded-2xl border-[1.5px] border-amber-200 bg-white p-3 shadow-xs">
                @foreach ($services as $service)
                    <div x-show="serviceId === {{ $service->id }}" x-cloak class="shrink-0">
                        <x-customer.service-thumb :service="$service" :category="$business->category" class="size-14 border border-gray-200" />
                    </div>
                @endforeach
                <div class="flex min-w-0 flex-1 flex-col gap-0.5">
                    <span class="w-fit rounded-md bg-amber-100 px-2 py-0.5 text-[10px] font-extrabold tracking-wide text-amber-900 uppercase">Layanan Dipilih</span>
                    <p class="truncate text-sm font-extrabold leading-5 text-gray-900" x-text="service ? service.name : ''"></p>
                    <div class="flex flex-wrap items-center gap-1.5 text-xs leading-4 text-gray-600">
                        <span x-show="service && service.price" class="font-bold text-gray-900" x-text="service ? service.price : ''"></span>
                        <span x-show="service && service.price && service.duration" class="text-gray-300" aria-hidden="true">|</span>
                        <span x-text="service && service.duration ? 'Estimasi ±' + service.duration + ' mnt' : 'Durasi fleksibel'"></span>
                    </div>
                </div>
                <button type="button" @click="goTo(2)" class="shrink-0 rounded-lg border border-brand-200 bg-brand-50 px-3 py-1.5 text-xs font-bold text-brand-700 transition hover:bg-brand-100">Ubah</button>
            </div>

            {{-- Tahap 1: Data diri --}}
            <div x-show="step === 1" @if ($initialStep !== 1) x-cloak @endif x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="opacity-0 translate-x-2" x-transition:enter-end="opacity-100 translate-x-0" class="flex flex-col gap-4">
                <div class="flex flex-col gap-3.5 rounded-2xl border border-gray-200 bg-white p-4 shadow-xs md:p-6">
                    <div class="flex flex-col gap-0.5">
                        <h2 class="text-base font-extrabold leading-6 text-gray-900">Data diri Anda</h2>
                        <p class="text-xs leading-4 text-gray-600">Nama ini digunakan saat nomor Anda dipanggil.</p>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label for="customer_name" class="flex items-center justify-between text-xs font-bold leading-4 text-gray-900">
                            <span>Nama lengkap</span>
                            <span class="rounded-md bg-brand-50 px-2 py-0.5 text-[10px] font-extrabold text-brand-700">Wajib</span>
                        </label>
                        <input
                            id="customer_name"
                            name="customer_name"
                            type="text"
                            x-ref="name"
                            x-model="name"
                            @input="nameError = false"
                            value="{{ old('customer_name', session('customer_profile.name')) }}"
                            autocomplete="name"
                            maxlength="100"
                            placeholder="Nama Anda"
                            class="min-h-[46px] rounded-xl border-[1.5px] bg-white px-3.5 py-2.5 text-sm font-medium leading-5 text-gray-900 placeholder:text-gray-400 transition focus:border-brand-700 focus:outline-none focus:ring-3 focus:ring-brand-100 @error('customer_name') border-danger-700 @else border-gray-300 @enderror"
                            :class="nameError && 'border-danger-700'"
                        />
                        <p x-show="nameError" x-cloak class="text-xs font-medium leading-4 text-danger-700">Nama lengkap wajib diisi.</p>
                        @error('customer_name')
                            <p class="text-xs font-medium leading-4 text-danger-700">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label for="customer_phone" class="flex items-center justify-between text-xs font-bold leading-4 text-gray-900">
                            <span>Nomor WhatsApp</span>
                            <span class="rounded-md bg-gray-100 px-2 py-0.5 text-[10px] font-bold text-gray-600">Opsional</span>
                        </label>
                        <input
                            id="customer_phone"
                            name="customer_phone"
                            type="tel"
                            x-model="phone"
                            value="{{ old('customer_phone', session('customer_profile.phone')) }}"
                            autocomplete="tel"
                            maxlength="20"
                            placeholder="0812 3456 7890"
                            class="min-h-[46px] rounded-xl border-[1.5px] bg-white px-3.5 py-2.5 text-sm font-medium leading-5 text-gray-900 placeholder:text-gray-400 transition focus:border-brand-700 focus:outline-none focus:ring-3 focus:ring-brand-100 @error('customer_phone') border-danger-700 @else border-gray-300 @enderror"
                        />
                        @error('customer_phone')
                            <p class="text-xs font-medium leading-4 text-danger-700">{{ $message }}</p>
                        @enderror
                    </div>

                    <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-gray-200 bg-gray-50/70 p-3 transition hover:bg-gray-50">
                        <input type="checkbox" name="notify_email" value="1" x-model="notifyEmail" @checked(old('notify_email')) class="mt-0.5 size-4 rounded border-gray-300 text-brand-700 focus:ring-brand-700" />
                        <span class="flex flex-col gap-0.5 text-xs leading-4">
                            <span class="flex flex-wrap items-center gap-1.5 font-bold text-gray-900">Kirim pembaruan status ke email saya <span class="rounded-md bg-gray-200/70 px-2 py-0.5 text-[10px] font-bold text-gray-600">Opsional</span></span>
                            <span class="text-gray-600">Kami kabari saat nomor Anda dipanggil atau selesai dilayani.</span>
                        </span>
                    </label>

                    <div x-show="notifyEmail" @unless (old('notify_email')) x-cloak @endunless x-transition.opacity.duration.200ms class="flex flex-col gap-1.5">
                        <label for="customer_email" class="text-xs font-bold leading-4 text-gray-900">Alamat email</label>
                        <input
                            id="customer_email"
                            name="customer_email"
                            type="email"
                            x-ref="email"
                            x-model="email"
                            @input="emailError = false"
                            value="{{ old('customer_email', session('customer_profile.email')) }}"
                            autocomplete="email"
                            maxlength="255"
                            placeholder="nama@email.com"
                            class="min-h-[46px] rounded-xl border-[1.5px] bg-white px-3.5 py-2.5 text-sm font-medium leading-5 text-gray-900 placeholder:text-gray-400 transition focus:border-brand-700 focus:outline-none focus:ring-3 focus:ring-brand-100 @error('customer_email') border-danger-700 @else border-gray-300 @enderror"
                            :class="emailError && 'border-danger-700'"
                        />
                        <p x-show="emailError" x-cloak class="text-xs font-medium leading-4 text-danger-700">Masukkan alamat email yang valid.</p>
                        @error('customer_email')
                            <p class="text-xs font-medium leading-4 text-danger-700">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="flex flex-col gap-1 rounded-xl border border-brand-200 bg-brand-50 p-3.5 text-xs leading-4">
                    <p class="font-bold text-brand-700">Data Anda tetap pribadi</p>
                    <p class="text-gray-700">WhatsApp boleh dikosongkan. Data hanya digunakan untuk antrean Anda di {{ $business->name }}.</p>
                </div>

                <button type="button" @click="submitIdentity()" class="flex h-12 w-full items-center justify-center gap-1.5 rounded-xl bg-brand-700 text-sm font-bold text-white shadow-sm transition hover:bg-blue-900 active:scale-[0.99]">
                    Pilih Layanan
                    <x-customer.icon name="arrow-right" class="size-4" />
                </button>
            </div>

            {{-- Tahap 2: Pilih layanan (zero clipping cards) --}}
            <div x-show="step === 2" @if ($initialStep !== 2) x-cloak @endif x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="opacity-0 translate-x-2" x-transition:enter-end="opacity-100 translate-x-0" class="flex flex-col gap-4">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <h2 class="text-base font-extrabold leading-6 text-gray-900">Jenis pesanan tersedia</h2>
                    @if ($services->count() > 5)
                        <label class="flex h-10 items-center gap-2 rounded-xl border-[1.5px] border-gray-300 bg-white px-3 transition focus-within:border-brand-700 focus-within:ring-2 focus-within:ring-brand-100 sm:w-64">
                            <x-customer.icon name="search" class="size-4 text-gray-400" />
                            <span class="sr-only">Cari layanan</span>
                            <input
                                type="search"
                                x-model.debounce.150ms="serviceQuery"
                                placeholder="Cari layanan..."
                                class="w-full min-w-0 border-0 bg-transparent p-0 text-sm leading-5 text-gray-900 placeholder:text-gray-400 focus:outline-none focus:ring-0"
                            />
                        </label>
                    @endif
                </div>

                @if ($services->isEmpty())
                    <div class="rounded-2xl border border-gray-200 bg-white px-5 py-7 text-center text-sm leading-5 text-gray-700">Belum ada layanan yang bisa dipilih.</div>
                @else
                    <div class="flex flex-col gap-2.5" role="radiogroup" aria-label="Pilih layanan">
                        @foreach ($services as $service)
                            @php($serviceMatchText = Str::lower($service->name.' '.$service->description))
                            <label
                                x-show="serviceQuery.trim() === '' || @js($serviceMatchText).includes(serviceQuery.trim().toLowerCase())"
                                @class([
                                    'flex w-full min-w-0 items-center gap-3 rounded-2xl border-[1.5px] p-3.5 shadow-xs transition-colors duration-200',
                                    'cursor-pointer' => ! $service->is_quota_full,
                                    'cursor-not-allowed opacity-60' => $service->is_quota_full,
                                ])
                                :class="serviceId === {{ $service->id }} ? 'border-brand-700 bg-brand-50/80 ring-2 ring-brand-100' : 'border-gray-200 bg-white {{ $service->is_quota_full ? '' : 'hover:border-brand-300' }}'"
                            >
                                <input
                                    type="radio"
                                    name="service_id"
                                    value="{{ $service->id }}"
                                    x-model.number="serviceId"
                                    @checked($selectedId === $service->id && ! $service->is_quota_full)
                                    @disabled($service->is_quota_full)
                                    class="sr-only"
                                />
                                <x-customer.service-thumb :service="$service" :category="$business->category" class="size-16 border border-gray-200 sm:size-20" />
                                <span class="flex min-w-0 flex-1 flex-col gap-1">
                                    <span class="wrap-break-word text-sm font-extrabold leading-snug text-gray-900">{{ $service->name }}</span>
                                    @if ($service->description)
                                        <span class="line-clamp-2 text-xs leading-4 text-gray-600">{{ $service->description }}</span>
                                    @endif
                                    <span class="mt-0.5 flex flex-wrap items-center gap-1.5">
                                        @if ($service->price)
                                            <span class="text-sm font-extrabold leading-5 text-gray-900 whitespace-nowrap">Rp {{ number_format($service->price, 0, ',', '.') }}</span>
                                        @endif
                                        @if ($service->is_quota_full)
                                            <span class="rounded-full bg-red-50 px-2 py-0.5 text-[11px] font-bold text-danger-700">Kuota hari ini habis</span>
                                        @else
                                            <span class="inline-flex items-center gap-1 rounded-full bg-brand-50 px-2 py-0.5 text-[11px] font-bold text-brand-700">
                                                <x-customer.icon name="clock" class="size-3" />
                                                {{ $service->duration_minutes ? '±'.$service->duration_minutes.' menit' : 'Durasi fleksibel' }}
                                            </span>
                                        @endif
                                    </span>
                                </span>
                                <img x-show="serviceId === {{ $service->id }}" x-cloak src="{{ asset('images/figma/radio-selected.svg') }}" alt="" width="20" height="20" class="size-5 shrink-0" />
                                <img x-show="serviceId !== {{ $service->id }}" src="{{ asset('images/figma/radio-unselected.svg') }}" alt="" width="20" height="20" class="size-5 shrink-0" />
                            </label>
                        @endforeach
                    </div>
                @endif

                @error('service_id')
                    <p class="text-xs font-medium leading-4 text-danger-700">{{ $message }}</p>
                @enderror

                {{-- Slot jam booking dinamis untuk layanan terpilih --}}
                <div x-show="serviceId && currentSlots().length > 0" x-cloak x-transition.opacity.duration.200ms class="flex flex-col gap-3 rounded-2xl border border-gray-200 bg-white p-4 shadow-xs">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <h3 class="text-sm font-extrabold leading-5 text-gray-900">Pilih Slot Jam Kedatangan</h3>
                            <p class="text-xs leading-4 text-gray-600">Kapasitas slot menyesuaikan durasi layanan dan loket aktif. Slot yang sudah berjalan atau penuh tidak dapat dipilih.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-3" role="radiogroup" aria-label="Pilih slot jam booking">
                        <template x-for="slot in currentSlots()" :key="slot.slot_key">
                            <button
                                type="button"
                                @click="slot.is_available && (bookingSlot = slot.slot_key)"
                                :disabled="!slot.is_available"
                                class="flex flex-col items-start gap-1 rounded-xl border-[1.5px] px-3 py-2.5 text-left transition"
                                :class="{
                                    'border-brand-700 bg-brand-50 ring-2 ring-brand-100': slot.is_available && bookingSlot === slot.slot_key,
                                    'border-gray-200 bg-white hover:border-brand-300': slot.is_available && bookingSlot !== slot.slot_key,
                                    'cursor-not-allowed border-gray-200 bg-gray-100 opacity-55': !slot.is_available,
                                }"
                            >
                                <span class="text-xs font-extrabold text-gray-900 tabular-nums" x-text="slot.label"></span>
                                <span
                                    class="text-[11px] font-semibold"
                                    :class="slot.is_available ? 'text-emerald-700' : (slot.is_started ? 'text-gray-500' : 'text-danger-700')"
                                    x-text="slot.is_started ? 'Sudah berjalan' : (slot.is_full ? 'Penuh' : 'Sisa ' + slot.remaining + '/' + slot.capacity + ' slot')"
                                ></span>
                            </button>
                        </template>
                    </div>

                    <div x-show="currentSlots().length > 0 && !hasAvailableSlot()" x-cloak class="rounded-xl border border-red-200 bg-red-50 p-3 text-xs font-semibold text-danger-700">
                        Semua slot waktu untuk hari ini sudah berjalan atau penuh. Silakan coba kembali di hari operasional berikutnya.
                    </div>
                </div>

                @error('booking_slot')
                    <p class="text-xs font-medium leading-4 text-danger-700">{{ $message }}</p>
                @enderror

                <div x-show="service" x-cloak x-transition.opacity.duration.200ms class="flex flex-col gap-1 rounded-xl border border-brand-200 bg-brand-50 p-3.5 text-xs leading-4">
                    <p class="font-bold text-brand-700" x-text="service && service.duration ? `Durasi layanan sekitar ${service.duration} menit` : 'Durasi layanan fleksibel'"></p>
                    <p class="text-gray-700">Mohon hadir maksimal 15 menit setelah dipanggil agar tiket tidak otomatis dibatalkan (tidak hadir).</p>
                </div>

                <button
                    type="button"
                    @click="serviceId && hasAvailableSlot() && goTo(3)"
                    :disabled="! serviceId || ! hasAvailableSlot()"
                    class="flex h-12 w-full items-center justify-center gap-1.5 rounded-xl bg-brand-700 text-sm font-bold text-white shadow-sm transition hover:bg-blue-900 active:scale-[0.99] disabled:cursor-not-allowed disabled:bg-gray-200 disabled:text-gray-500 disabled:shadow-none"
                >
                    Lanjut ke Konfirmasi
                    <x-customer.icon name="arrow-right" class="size-4" />
                </button>
            </div>

            {{-- Tahap 3: Konfirmasi --}}
            <div x-show="step === 3" x-cloak x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="opacity-0 translate-x-2" x-transition:enter-end="opacity-100 translate-x-0" class="flex flex-col gap-4">
                {{-- Layanan dipilih --}}
                <div class="flex items-center gap-3 rounded-2xl border-[1.5px] border-amber-200 bg-white p-3.5 shadow-xs">
                    @foreach ($services as $service)
                        <div x-show="serviceId === {{ $service->id }}" x-cloak class="shrink-0">
                            <x-customer.service-thumb :service="$service" :category="$business->category" class="size-16 border border-gray-200 sm:size-20" />
                        </div>
                    @endforeach
                    <div class="flex min-w-0 flex-1 flex-col gap-1">
                        <span class="w-fit rounded-md bg-amber-100 px-2 py-0.5 text-[10px] font-extrabold tracking-wide text-amber-900 uppercase">Layanan Dipilih</span>
                        <p class="wrap-break-word text-base font-extrabold leading-snug text-gray-900" x-text="service ? service.name : ''"></p>
                        <div class="flex flex-wrap items-center gap-1.5 text-xs leading-4">
                            <span x-show="service && service.price" class="font-extrabold text-gray-900" x-text="service ? service.price : ''"></span>
                            <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2 py-0.5 font-semibold text-gray-700">
                                <x-customer.icon name="clock" class="size-3" />
                                <span x-text="service && service.duration ? `±${service.duration} menit` : 'Fleksibel'"></span>
                            </span>
                            <span x-show="bookingSlot" x-cloak class="inline-flex items-center gap-1 rounded-full bg-brand-50 px-2 py-0.5 font-bold text-brand-700">
                                Slot <span x-text="selectedSlotLabel()"></span>
                            </span>
                        </div>
                    </div>
                    <button type="button" @click="goTo(2)" class="shrink-0 self-start rounded-lg border border-brand-200 bg-brand-50 px-3 py-1.5 text-xs font-bold text-brand-700 transition hover:bg-brand-100">Ubah</button>
                </div>

                <div class="flex flex-col gap-3 rounded-2xl border border-gray-200 bg-white p-4 shadow-xs md:p-6">
                    <div class="flex items-center gap-3" x-show="currentSlots().length > 0" x-cloak>
                        <img src="{{ asset('images/figma/clock.svg') }}" alt="" width="20" height="20" class="size-5 shrink-0" />
                        <div class="flex min-w-0 flex-1 flex-col gap-[3px]">
                            <p class="text-xs leading-4 text-gray-600">Slot jam booking</p>
                            <p class="truncate text-sm font-bold leading-5 text-brand-700" x-text="selectedSlotLabel()"></p>
                        </div>
                    </div>
                    @foreach ([
                        ['icon' => 'store', 'label' => 'Kedai', 'text' => $business->name],
                        ['icon' => 'user', 'label' => 'Nama pemesan', 'model' => 'name'],
                        ['icon' => 'phone', 'label' => 'WhatsApp', 'model' => 'phone', 'show' => 'phone.trim() !== \'\''],
                        ['icon' => 'file-text', 'label' => 'Notifikasi email', 'model' => 'email', 'show' => 'notifyEmail'],
                    ] as $row)
                        <div class="flex items-center gap-3" @isset($row['show']) x-show="{{ $row['show'] }}" @endisset>
                            <img src="{{ asset('images/figma/'.$row['icon'].'.svg') }}" alt="" width="20" height="20" class="size-5 shrink-0" />
                            <div class="flex min-w-0 flex-1 flex-col gap-[3px]">
                                <p class="text-xs leading-4 text-gray-600">{{ $row['label'] }}</p>
                                @isset($row['text'])
                                    <p class="truncate text-sm font-bold leading-5 text-gray-900">{{ $row['text'] }}</p>
                                @else
                                    <p class="truncate text-sm font-bold leading-5 text-gray-900" x-text="{{ $row['model'] }}"></p>
                                @endisset
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="flex flex-col gap-1 rounded-xl border border-brand-200 bg-brand-50 p-3.5 text-xs leading-4">
                    <p class="font-bold text-brand-700">Nomor antrean dibuat setelah konfirmasi</p>
                    <p class="text-gray-700">Anda belum masuk antrean sebelum menekan tombol di bawah.</p>
                </div>

                <p class="text-center text-xs leading-4 text-gray-600">Setelah konfirmasi, QR Code tiket Anda langsung siap dipantau dari HP.</p>

                <button
                    type="submit"
                    :disabled="isSubmitting || {{ ($business->canAcceptOnlineQueue() && ! $reachedTicketLimit) ? 'false' : 'true' }}"
                    class="flex h-12 w-full items-center justify-center gap-2 rounded-xl bg-brand-700 text-sm font-bold text-white shadow-sm transition hover:bg-blue-900 active:scale-[0.99] disabled:cursor-not-allowed disabled:bg-blue-300"
                >
                    <svg x-show="isSubmitting" x-cloak class="size-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <span x-text="isSubmitting ? 'Mengambil antrean…' : 'Ya, Ambil Antrean'">Ya, Ambil Antrean</span>
                </button>
            </div>
        </div>
    </form>
</x-layouts.customer>
