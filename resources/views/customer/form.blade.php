<x-layouts.customer 
    :title="'Pendaftaran Antrean — ' . $business->name"
    :business="$business"
    :backUrl="route('customer.queue', ['slug' => $business->slug])"
>
    <div class="max-w-xl mx-auto py-2 sm:py-6">
        <x-card padding="lg" class="shadow-sm">
            {{-- Header --}}
            <div class="border-b border-neutral-200 pb-5 mb-6">
                <div class="flex items-center gap-2 text-caption text-neutral-500 mb-1">
                    <span>{{ $business->name }}</span>
                    <span>•</span>
                    <span class="text-brand-700 font-medium">Antrean Online Hari Ini</span>
                </div>
                <h1 class="text-xl sm:text-2xl font-extrabold text-neutral-950 tracking-tight">
                    Ambil Nomor Antrean
                </h1>
                <p class="text-body text-neutral-500 mt-1">
                    Silakan lengkapi informasi di bawah untuk mendapatkan tiket antrean digital Anda.
                </p>
            </div>

            {{-- Validation / Domain Error Alert --}}
            @if ($errors->has('queue_error'))
                <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 mb-6 flex items-start gap-3">
                    <svg class="w-5 h-5 text-rose-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div class="text-sm">
                        <span class="font-bold block">Pendaftaran Antrean Tidak Berhasil</span>
                        <span class="mt-0.5 block leading-relaxed">{{ $errors->first('queue_error') }}</span>
                    </div>
                </div>
            @elseif ($errors->any())
                <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 mb-6 flex items-start gap-3">
                    <svg class="w-5 h-5 text-rose-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div class="text-sm">
                        <span class="font-bold block">Terdapat kesalahan pengisian data:</span>
                        <ul class="list-disc list-inside mt-1 space-y-0.5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            {{-- Form Submission with Double-Submit Prevention --}}
            <form 
                action="{{ route('customer.queue.take', ['slug' => $business->slug]) }}" 
                method="POST" 
                x-data="{ isSubmitting: false }" 
                @submit="if (isSubmitting) { $event.preventDefault(); return false; } isSubmitting = true;"
                class="space-y-6"
            >
                @csrf

                {{-- Hidden Idempotency Key --}}
                <input type="hidden" name="idempotency_key" value="{{ old('idempotency_key', $idempotencyKey) }}">

                {{-- Service Selection --}}
                <div>
                    <label class="block text-label font-semibold text-neutral-900 mb-2">
                        Pilih Layanan <span class="text-rose-500">*</span>
                    </label>

                    @if ($services->isEmpty())
                        <div class="p-4 rounded-xl bg-neutral-100 text-caption text-neutral-600 text-center">
                            Tidak ada layanan aktif yang tersedia saat ini.
                        </div>
                    @else
                        <div class="space-y-3">
                            @foreach ($services as $service)
                                @php
                                    $isSelected = (int) old('service_id', $selectedServiceId) === $service->id;
                                    $isFull = $service->is_quota_full;
                                @endphp

                                <label 
                                    class="relative flex items-start p-4 rounded-xl border transition-all cursor-pointer select-none {{ $isFull ? 'bg-neutral-50/70 border-neutral-200 opacity-60 cursor-not-allowed' : ($isSelected ? 'bg-brand-50/50 border-brand-500 ring-1 ring-brand-500' : 'bg-white border-neutral-200 hover:border-neutral-300') }}"
                                >
                                    <div class="flex items-center h-5 mr-3 mt-0.5">
                                        <input 
                                            type="radio" 
                                            name="service_id" 
                                            value="{{ $service->id }}" 
                                            {{ $isSelected ? 'checked' : '' }}
                                            {{ $isFull ? 'disabled' : '' }}
                                            required
                                            class="h-4 w-4 text-brand-700 border-neutral-300 focus:ring-brand-500"
                                        >
                                    </div>

                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center justify-between gap-2">
                                            <span class="font-bold text-sm text-neutral-900">
                                                {{ $service->name }}
                                            </span>

                                            @if ($service->price)
                                                <span class="font-bold text-sm text-neutral-900 shrink-0">
                                                    Rp {{ number_format($service->price, 0, ',', '.') }}
                                                </span>
                                            @endif
                                        </div>

                                        @if ($service->description)
                                            <p class="text-caption text-neutral-500 mt-0.5 line-clamp-1">
                                                {{ $service->description }}
                                            </p>
                                        @endif

                                        <div class="mt-2 flex items-center gap-2 text-[11px] text-neutral-500">
                                            @if ($service->duration_minutes)
                                                <span>±{{ $service->duration_minutes }} menit</span>
                                                <span>•</span>
                                            @endif

                                            @if ($service->daily_quota !== null)
                                                @if ($isFull)
                                                    <span class="text-rose-600 font-medium">Kuota penuh</span>
                                                @else
                                                    <span class="text-emerald-700 font-medium">Sisa {{ $service->remaining_quota }} kuota</span>
                                                @endif
                                            @else
                                                <span class="text-blue-700 font-medium">Kuota tersedia</span>
                                            @endif
                                        </div>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    @endif

                    @error('service_id')
                        <p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Customer Name --}}
                <div>
                    <label for="customer_name" class="block text-label font-semibold text-neutral-900 mb-1.5">
                        Nama Lengkap / Panggilan <span class="text-rose-500">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="customer_name" 
                        name="customer_name" 
                        value="{{ old('customer_name') }}" 
                        placeholder="Contoh: Budi Santoso" 
                        required 
                        maxlength="100"
                        autofocus
                        class="w-full px-3.5 py-2.5 bg-white border border-neutral-300 rounded-xl text-neutral-900 placeholder:text-neutral-400 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition text-sm @error('customer_name') border-rose-300 text-rose-900 focus:ring-rose-500 focus:border-rose-500 @enderror"
                    >
                    <p class="mt-1 text-[11px] text-neutral-500">
                        Nama akan dipanggil dan tertera pada nomor tiket antrean Anda.
                    </p>
                    @error('customer_name')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Customer Phone (Optional) --}}
                <div>
                    <label for="customer_phone" class="block text-label font-semibold text-neutral-900 mb-1.5">
                        Nomor WhatsApp / HP <span class="text-caption text-neutral-400 font-normal">(Opsional)</span>
                    </label>
                    <input 
                        type="tel" 
                        id="customer_phone" 
                        name="customer_phone" 
                        value="{{ old('customer_phone') }}" 
                        placeholder="Contoh: 081234567890" 
                        maxlength="30"
                        class="w-full px-3.5 py-2.5 bg-white border border-neutral-300 rounded-xl text-neutral-900 placeholder:text-neutral-400 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition text-sm @error('customer_phone') border-rose-300 text-rose-900 focus:ring-rose-500 focus:border-rose-500 @enderror"
                    >
                    <p class="mt-1 text-[11px] text-neutral-500">
                        Nomor telepon Anda akan dimasking untuk menjaga privasi pada halaman pemantauan publik.
                    </p>
                    @error('customer_phone')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Action Buttons --}}
                <div class="pt-4 border-t border-neutral-200 flex flex-col sm:flex-row items-center gap-3">
                    <button 
                        type="submit" 
                        :disabled="isSubmitting"
                        class="inline-flex items-center justify-center w-full sm:w-auto flex-1 px-5 py-3 text-sm font-bold text-white bg-brand-700 hover:bg-brand-600 disabled:bg-brand-400 disabled:cursor-not-allowed rounded-xl shadow-2xs transition focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2"
                    >
                        <span x-show="!isSubmitting">Ambil Nomor Antrean</span>
                        <span x-show="isSubmitting" class="inline-flex items-center gap-2" style="display: none;">
                            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Mendaftarkan Tiket...
                        </span>
                    </button>

                    <a 
                        href="{{ route('customer.queue', ['slug' => $business->slug]) }}" 
                        class="inline-flex items-center justify-center w-full sm:w-auto px-4 py-3 text-sm font-semibold text-neutral-600 hover:text-neutral-900 bg-white hover:bg-neutral-100 border border-neutral-300 rounded-xl transition"
                    >
                        Batal
                    </a>
                </div>
            </form>
        </x-card>
    </div>
</x-layouts.customer>
