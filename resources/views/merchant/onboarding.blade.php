@php
    $stepLabels = [1 => 'Profil Usaha', 2 => 'Jam Operasional', 3 => 'Layanan Perdana', 4 => 'QR Code & Kirim'];
    $hoursMissing = $business && $hours->isEmpty();
    $openDayCount = $hours->where('is_closed', false)->count();
    $businessInitials = $business
        ? collect(preg_split('/\s+/', trim($business->name)))->filter()->take(2)->map(fn ($word) => Str::upper(Str::substr($word, 0, 1)))->implode('')
        : null;
@endphp

<x-layouts.base title="Onboarding UMKM — AntreIn">
    <div class="min-h-screen bg-neutral-50 px-4 py-8 sm:px-6 sm:py-10 lg:px-8">
        <div class="mx-auto max-w-3xl">
            {{-- Header --}}
            <div class="mb-6 text-center sm:mb-8">
                <a href="/" class="inline-flex items-center gap-1.5 focus:outline-none">
                    <span class="text-3xl font-extrabold tracking-tight text-neutral-950">AntreIn<span class="text-brand-700">.</span></span>
                </a>
                <h1 class="mt-3 text-h2 font-bold text-neutral-950">Konfigurasi Awal Usaha</h1>
                <p class="mt-1 text-body text-neutral-600">Selesaikan 4 langkah mudah untuk mulai menerima antrean digital.</p>
            </div>

            {{-- Stepper --}}
            <nav class="mb-6 rounded-xl border border-neutral-200 bg-white p-3 shadow-xs sm:mb-8 sm:p-4" aria-label="Tahap onboarding">
                <ol class="grid grid-cols-4 gap-2">
                    @foreach ($stepLabels as $number => $label)
                        @php
                            $isDone = $step > $number;
                            $isCurrent = $step === $number;
                            $isReachable = $number === 1 || $business !== null;
                        @endphp
                        <li>
                            <{{ $isReachable && ! $isCurrent ? 'a' : 'span' }}
                                @if ($isReachable && ! $isCurrent) href="{{ route('merchant.onboarding', ['step' => $number]) }}" @endif
                                @class([
                                    'flex min-h-11 flex-col items-center gap-1.5 rounded-lg px-1 py-1.5 text-center transition md:flex-row md:gap-3 md:px-2 md:text-left',
                                    'hover:bg-neutral-50' => $isReachable && ! $isCurrent,
                                ])
                                @if ($isCurrent) aria-current="step" @endif
                            >
                                <span @class([
                                    'flex size-8 shrink-0 items-center justify-center rounded-full text-caption font-bold',
                                    'bg-brand-700 text-white' => $isDone,
                                    'bg-brand-100 text-brand-800 ring-2 ring-brand-700' => $isCurrent,
                                    'bg-neutral-100 text-neutral-500' => ! $isDone && ! $isCurrent,
                                ])>
                                    @if ($isDone)
                                        <x-customer.icon name="check" class="size-4" />
                                    @else
                                        {{ $number }}
                                    @endif
                                </span>
                                <span class="flex flex-col">
                                    <span class="hidden text-xs font-semibold tracking-wider uppercase md:block {{ $isCurrent ? 'text-brand-700' : 'text-neutral-500' }}">Langkah {{ $number }}</span>
                                    <span class="text-[11px] leading-4 font-medium md:text-caption {{ $isCurrent ? 'text-neutral-900' : 'text-neutral-600' }}">{{ $label }}</span>
                                </span>
                            </{{ $isReachable && ! $isCurrent ? 'a' : 'span' }}>
                        </li>
                    @endforeach
                </ol>
            </nav>

            {{-- Status & error --}}
            @if (session('status'))
                <div class="mb-6 flex items-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-800">
                    <x-customer.icon name="check" class="size-5 text-emerald-600" />
                    <span>{{ session('status') }}</span>
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-6 rounded-lg border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">
                    <p class="mb-1 font-semibold">Periksa kembali data yang dimasukkan:</p>
                    <ul class="list-inside list-disc space-y-0.5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Pengingat jam operasional (tidak memblokir) --}}
            @if ($hoursMissing && $step >= 3)
                <div class="mb-6 flex flex-col gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4 sm:flex-row sm:items-center sm:justify-between" role="status">
                    <div class="flex items-start gap-3">
                        <x-customer.icon name="clock" class="mt-0.5 size-5 text-amber-700" />
                        <div class="text-sm">
                            <p class="font-semibold text-amber-900">Jam operasional belum diatur</p>
                            <p class="text-amber-800">Jika dilewati, kami pasang jam standar Senin sampai Sabtu 09.00–21.00 (Minggu tutup) saat onboarding selesai.</p>
                        </div>
                    </div>
                    <a href="{{ route('merchant.onboarding', ['step' => 2]) }}" class="inline-flex h-11 shrink-0 items-center justify-center rounded-lg border border-amber-300 bg-white px-4 text-sm font-semibold text-amber-900 transition hover:bg-amber-100">
                        Atur di Langkah 2
                    </a>
                </div>
            @endif

            <x-card padding="lg" class="border-neutral-200 shadow-sm">
                @if ($step === 1)
                    {{-- LANGKAH 1: PROFIL USAHA --}}
                    <div class="mb-6">
                        <h2 class="text-h3 font-bold text-neutral-900">1. Data Profil Usaha</h2>
                        <p class="text-caption text-neutral-500">Informasi ini akan ditampilkan kepada pelanggan saat mengakses antrean online.</p>
                    </div>

                    <form method="POST" action="{{ route('merchant.onboarding.step1') }}" enctype="multipart/form-data" class="space-y-5">
                        @csrf

                        {{-- Logo usaha --}}
                        <div
                            x-data="{
                                preview: @js($business?->getLogoUrl()),
                                fileName: '',
                                pick(event) {
                                    const file = event.target.files[0];
                                    if (! file) { return; }
                                    this.fileName = file.name;
                                    this.preview = URL.createObjectURL(file);
                                },
                            }"
                            class="flex flex-col gap-4 rounded-xl border border-dashed border-neutral-300 bg-neutral-50/60 p-4 sm:flex-row sm:items-center"
                        >
                            <div class="flex size-20 shrink-0 items-center justify-center overflow-hidden rounded-2xl border border-neutral-200 bg-white shadow-xs">
                                <img x-show="preview" :src="preview" alt="Pratinjau logo usaha" class="size-full object-cover" @if (! $business?->getLogoUrl()) style="display: none;" @endif @if ($business?->getLogoUrl()) src="{{ $business->getLogoUrl() }}" @endif />
                                <span x-show="! preview" class="text-xl font-bold text-brand-700" @if ($business?->getLogoUrl()) style="display: none;" @endif>{{ $businessInitials ?: 'Logo' }}</span>
                            </div>
                            <div class="flex min-w-0 flex-1 flex-col gap-1">
                                <p class="text-sm font-semibold text-neutral-900">Logo Usaha <span class="ml-1 rounded-full bg-neutral-100 px-2 py-0.5 text-[10px] font-bold text-neutral-500">Opsional</span></p>
                                <p class="text-caption text-neutral-500">JPG, PNG, atau WEBP, maksimal 2 MB. Tampil di kartu toko dan tiket pelanggan.</p>
                                <p x-show="fileName" x-text="fileName" class="truncate text-caption font-medium text-brand-700" style="display: none;"></p>
                            </div>
                            <label for="logo" class="inline-flex h-11 shrink-0 cursor-pointer items-center justify-center rounded-lg border border-neutral-300 bg-white px-4 text-sm font-semibold text-neutral-800 shadow-xs transition hover:bg-neutral-50 focus-within:ring-2 focus-within:ring-brand-500">
                                <span x-text="preview ? 'Ganti Logo' : 'Pilih Logo'">{{ $business?->getLogoUrl() ? 'Ganti Logo' : 'Pilih Logo' }}</span>
                                <input id="logo" name="logo" type="file" accept="image/jpeg,image/png,image/webp" class="sr-only" @change="pick($event)" />
                            </label>
                        </div>
                        <x-input-error :messages="$errors->get('logo')" class="-mt-3" />

                        <div>
                            <x-form-label for="name" value="Nama Usaha / Toko" :required="true" />
                            <x-input id="name" name="name" type="text" :value="old('name', $business?->name)" required placeholder="Contoh: Kopi Senja Jimbaran" :error="$errors->has('name')" />
                            <x-input-error :messages="$errors->get('name')" class="mt-1" />
                        </div>

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <x-form-label for="category_id" value="Kategori Usaha" :required="true" />
                                <x-select id="category_id" name="category_id" required :error="$errors->has('category_id')">
                                    <option value="">Pilih kategori usaha</option>
                                    @foreach ($categories as $cat)
                                        <option value="{{ $cat->id }}" @selected(old('category_id', $business?->category_id) == $cat->id)>{{ $cat->name }}</option>
                                    @endforeach
                                </x-select>
                                <x-input-error :messages="$errors->get('category_id')" class="mt-1" />
                            </div>

                            <div>
                                <x-form-label for="area" value="Daerah / Area Usaha" />
                                <x-select id="area" name="area" :error="$errors->has('area')">
                                    <option value="">Pilih daerah / area (opsional)</option>
                                    @foreach ($areas as $areaKey => $areaLabel)
                                        <option value="{{ $areaKey }}" @selected(old('area', $business?->area) === $areaKey)>{{ $areaLabel }}</option>
                                    @endforeach
                                </x-select>
                                <x-input-error :messages="$errors->get('area')" class="mt-1" />
                            </div>
                        </div>

                        <div>
                            <x-form-label for="description" value="Deskripsi Singkat (Opsional)" />
                            <x-textarea id="description" name="description" rows="3" placeholder="Contoh: Kedai kopi specialty, minuman segar, dan camilan hangat untuk bekerja maupun bersantai.">{{ old('description', $business?->description) }}</x-textarea>
                            <x-input-error :messages="$errors->get('description')" class="mt-1" />
                        </div>

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <x-form-label for="phone" value="Nomor WhatsApp / Telepon Usaha" :required="true" />
                                <x-input id="phone" name="phone" type="tel" :value="old('phone', $business?->phone)" required placeholder="081234567890" :error="$errors->has('phone')" />
                                <x-input-error :messages="$errors->get('phone')" class="mt-1" />
                            </div>

                            <div>
                                <x-form-label for="timezone" value="Zona Waktu Usaha" :required="true" />
                                <x-select id="timezone" name="timezone" required :error="$errors->has('timezone')">
                                    <option value="Asia/Makassar" @selected(old('timezone', $business?->timezone ?? 'Asia/Makassar') === 'Asia/Makassar')>WITA (Asia/Makassar)</option>
                                    <option value="Asia/Jakarta" @selected(old('timezone', $business?->timezone) === 'Asia/Jakarta')>WIB (Asia/Jakarta)</option>
                                    <option value="Asia/Jayapura" @selected(old('timezone', $business?->timezone) === 'Asia/Jayapura')>WIT (Asia/Jayapura)</option>
                                </x-select>
                                <x-input-error :messages="$errors->get('timezone')" class="mt-1" />
                            </div>
                        </div>

                        <div>
                            <x-form-label for="address" value="Alamat Lengkap Usaha" :required="true" />
                            <x-textarea id="address" name="address" rows="2" required placeholder="Contoh: Jl. Uluwatu No. 88, Jimbaran, Kuta Selatan, Badung, Bali">{{ old('address', $business?->address) }}</x-textarea>
                            <x-input-error :messages="$errors->get('address')" class="mt-1" />
                        </div>

                        <div class="flex justify-end pt-2">
                            <x-button type="submit" variant="primary" class="h-11 w-full px-6 sm:w-auto">
                                Simpan & Lanjut ke Jam Operasional &rarr;
                            </x-button>
                        </div>
                    </form>

                @elseif ($step === 2)
                    {{-- LANGKAH 2: JAM OPERASIONAL --}}
                    @php
                        $days = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 0 => 'Minggu'];
                        $dayRows = collect($days)->map(function (string $dayName, int $dow) use ($hours) {
                            $index = array_search($dow, [1, 2, 3, 4, 5, 6, 0], true);
                            $existingHour = $hours->firstWhere('day_of_week', $dow);

                            return [
                                'index' => $index,
                                'dow' => $dow,
                                'name' => $dayName,
                                'open' => old("hours.{$index}.open_time", $existingHour ? substr($existingHour->open_time, 0, 5) : '09:00'),
                                'close' => old("hours.{$index}.close_time", $existingHour ? substr($existingHour->close_time, 0, 5) : '21:00'),
                                'closed' => (bool) old("hours.{$index}.is_closed", $existingHour ? $existingHour->is_closed : $dow === 0),
                            ];
                        })->values();
                    @endphp

                    <div class="mb-5 flex flex-col gap-1">
                        <h2 class="text-h3 font-bold text-neutral-900">2. Atur Jam Operasional Mingguan</h2>
                        <p class="text-caption text-neutral-500">Tentukan hari dan jam buka. Pelanggan melihat jam ini di kartu toko.</p>
                    </div>

                    <form
                        method="POST"
                        action="{{ route('merchant.onboarding.step2') }}"
                        x-data="{
                            days: @js($dayRows),
                            copied: false,
                            applyMondayToWeekdays() {
                                const monday = this.days.find((day) => day.dow === 1);
                                this.days.forEach((day) => {
                                    if ([2, 3, 4, 5].includes(day.dow)) {
                                        day.open = monday.open;
                                        day.close = monday.close;
                                        day.closed = monday.closed;
                                    }
                                });
                                this.flash();
                            },
                            useStandardHours() {
                                this.days.forEach((day) => {
                                    day.open = '09:00';
                                    day.close = '21:00';
                                    day.closed = day.dow === 0;
                                });
                                this.flash();
                            },
                            flash() {
                                this.copied = true;
                                setTimeout(() => this.copied = false, 2000);
                            },
                        }"
                        class="space-y-4"
                    >
                        @csrf

                        {{-- Bantuan cepat --}}
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                            <button type="button" @click="applyMondayToWeekdays()" class="inline-flex h-11 items-center justify-center gap-2 rounded-lg border border-brand-200 bg-brand-50 px-4 text-sm font-semibold text-brand-700 transition hover:bg-brand-100">
                                <x-customer.icon name="copy" class="size-4" />
                                Terapkan Jam Senin ke Semua Hari Kerja
                            </button>
                            <button type="button" @click="useStandardHours()" class="inline-flex h-11 items-center justify-center gap-2 rounded-lg border border-neutral-300 bg-white px-4 text-sm font-semibold text-neutral-700 transition hover:bg-neutral-50">
                                <x-customer.icon name="clock" class="size-4" />
                                Gunakan Jam Standar
                            </button>
                            <span x-show="copied" x-transition.opacity class="text-caption font-semibold text-emerald-700" style="display: none;">Jam diperbarui</span>
                        </div>
                        <p class="-mt-2 text-caption text-neutral-500">Hari kerja: Senin sampai Jumat. Jam standar: Senin sampai Sabtu 09.00–21.00, Minggu tutup.</p>

                        <div class="divide-y divide-neutral-200 overflow-hidden rounded-xl border border-neutral-200">
                            @foreach ($dayRows as $row)
                                @php $i = $row['index']; @endphp
                                <div class="grid grid-cols-[1fr_auto] items-center gap-3 p-3.5 transition-colors sm:grid-cols-[8rem_1fr_auto]" :class="days[{{ $loop->index }}].closed ? 'bg-neutral-50' : 'bg-white'">
                                    <input type="hidden" name="hours[{{ $i }}][day_of_week]" value="{{ $row['dow'] }}">

                                    <span class="font-semibold text-neutral-900">{{ $row['name'] }}</span>

                                    <div class="col-span-2 grid grid-cols-[1fr_auto_1fr] items-center gap-2 transition-opacity sm:col-span-1 sm:col-start-2 sm:row-start-1" :class="days[{{ $loop->index }}].closed && 'opacity-40'">
                                        <input
                                            type="time"
                                            name="hours[{{ $i }}][open_time]"
                                            value="{{ $row['open'] }}"
                                            x-model="days[{{ $loop->index }}].open"
                                            :disabled="days[{{ $loop->index }}].closed"
                                            aria-label="Jam buka {{ $row['name'] }}"
                                            class="h-11 w-full min-w-0 rounded-lg border border-neutral-300 px-3 text-sm focus:border-brand-700 focus:ring-1 focus:ring-brand-700 disabled:cursor-not-allowed disabled:bg-neutral-100"
                                        />
                                        <span class="text-sm text-neutral-400">s/d</span>
                                        <input
                                            type="time"
                                            name="hours[{{ $i }}][close_time]"
                                            value="{{ $row['close'] }}"
                                            x-model="days[{{ $loop->index }}].close"
                                            :disabled="days[{{ $loop->index }}].closed"
                                            aria-label="Jam tutup {{ $row['name'] }}"
                                            class="h-11 w-full min-w-0 rounded-lg border border-neutral-300 px-3 text-sm focus:border-brand-700 focus:ring-1 focus:ring-brand-700 disabled:cursor-not-allowed disabled:bg-neutral-100"
                                        />
                                    </div>

                                    <label
                                        class="col-start-2 row-start-1 inline-flex h-11 shrink-0 cursor-pointer items-center gap-2 rounded-lg border px-3 text-sm font-semibold transition sm:col-start-3"
                                        :class="days[{{ $loop->index }}].closed ? 'border-neutral-800 bg-neutral-800 text-white' : 'border-neutral-300 bg-white text-neutral-700 hover:bg-neutral-50'"
                                    >
                                        <input
                                            type="checkbox"
                                            name="hours[{{ $i }}][is_closed]"
                                            value="1"
                                            x-model="days[{{ $loop->index }}].closed"
                                            @checked($row['closed'])
                                            class="rounded border-neutral-300 text-brand-700 focus:ring-brand-700"
                                        />
                                        Tutup
                                    </label>
                                </div>
                            @endforeach
                        </div>

                        <div class="flex flex-col-reverse gap-3 pt-2 sm:flex-row sm:items-center sm:justify-between">
                            <a href="{{ route('merchant.onboarding', ['step' => 1]) }}" class="inline-flex h-11 items-center justify-center text-sm font-medium text-neutral-600 hover:text-neutral-900">
                                &larr; Sebelumnya
                            </a>
                            <x-button type="submit" variant="primary" class="h-11 w-full px-6 sm:w-auto">
                                Simpan & Lanjut ke Layanan &rarr;
                            </x-button>
                        </div>
                    </form>

                @elseif ($step === 3)
                    {{-- LANGKAH 3: LAYANAN PERDANA --}}
                    <div class="mb-5 flex flex-col gap-1">
                        <h2 class="text-h3 font-bold text-neutral-900">3. {{ $services->isEmpty() ? 'Tambah Layanan Perdana' : 'Layanan Anda' }}</h2>
                        <p class="text-caption text-neutral-500">Pelanggan memilih layanan ini saat mengambil tiket antrean.</p>
                    </div>

                    @if ($services->isNotEmpty())
                        <div class="mb-6 flex flex-col gap-3 rounded-xl border border-emerald-200 bg-emerald-50/60 p-4">
                            <div class="flex items-center justify-between gap-3">
                                <p class="text-sm font-semibold text-emerald-900">{{ $services->count() }} layanan sudah terdaftar</p>
                                <x-customer.icon name="check" class="size-5 text-emerald-600" />
                            </div>
                            <ul class="flex flex-col gap-2">
                                @foreach ($services as $svc)
                                    <li class="flex items-center gap-3 rounded-lg bg-white p-2.5 ring-1 ring-emerald-100">
                                        <x-customer.service-thumb :service="$svc" :category="$business->category" class="size-12" />
                                        <div class="flex min-w-0 flex-1 flex-col">
                                            <span class="truncate text-sm font-semibold text-neutral-900">{{ $svc->name }}</span>
                                            <span class="text-caption text-neutral-500">{{ $svc->duration_minutes }} menit</span>
                                        </div>
                                        <span class="shrink-0 text-sm font-semibold text-neutral-900">Rp {{ number_format($svc->price, 0, ',', '.') }}</span>
                                    </li>
                                @endforeach
                            </ul>
                            <a href="{{ route('merchant.onboarding', ['step' => 4]) }}" class="inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-emerald-700 px-4 text-sm font-semibold text-white transition hover:bg-emerald-800">
                                Lewati, Lanjut ke Langkah 4
                                <x-customer.icon name="arrow-right" class="size-4" />
                            </a>
                        </div>
                        <p class="mb-3 text-sm font-semibold text-neutral-900">Tambah layanan lain (opsional)</p>
                    @endif

                    <form method="POST" action="{{ route('merchant.onboarding.step3') }}" enctype="multipart/form-data" class="space-y-4">
                        @csrf

                        {{-- Foto layanan --}}
                        <div
                            x-data="{
                                preview: null,
                                fileName: '',
                                pick(event) {
                                    const file = event.target.files[0];
                                    if (! file) { return; }
                                    this.fileName = file.name;
                                    this.preview = URL.createObjectURL(file);
                                },
                            }"
                            class="flex flex-col gap-4 rounded-xl border border-dashed border-neutral-300 bg-neutral-50/60 p-4 sm:flex-row sm:items-center"
                        >
                            <div class="flex size-24 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-neutral-200 bg-white">
                                <img x-show="preview" :src="preview" alt="Pratinjau foto layanan" class="size-full object-cover" style="display: none;" />
                                <x-customer.icon name="ticket" class="size-8 text-neutral-300" x-show="! preview" />
                            </div>
                            <div class="flex min-w-0 flex-1 flex-col gap-1">
                                <p class="text-sm font-semibold text-neutral-900">Foto Layanan <span class="ml-1 rounded-full bg-neutral-100 px-2 py-0.5 text-[10px] font-bold text-neutral-500">Opsional</span></p>
                                <p class="text-caption text-neutral-500">JPG, PNG, atau WEBP, maksimal 2 MB. Tanpa foto, kami tampilkan ilustrasi sesuai kategori.</p>
                                <p x-show="fileName" x-text="fileName" class="truncate text-caption font-medium text-brand-700" style="display: none;"></p>
                            </div>
                            <label for="svc_image" class="inline-flex h-11 shrink-0 cursor-pointer items-center justify-center rounded-lg border border-neutral-300 bg-white px-4 text-sm font-semibold text-neutral-800 shadow-xs transition hover:bg-neutral-50 focus-within:ring-2 focus-within:ring-brand-500">
                                <span x-text="preview ? 'Ganti Foto' : 'Pilih Foto'">Pilih Foto</span>
                                <input id="svc_image" name="image" type="file" accept="image/jpeg,image/png,image/webp" class="sr-only" @change="pick($event)" />
                            </label>
                        </div>
                        <x-input-error :messages="$errors->get('image')" class="-mt-2" />

                        <div>
                            <x-form-label for="svc_name" value="Nama Layanan" :required="true" />
                            <x-input id="svc_name" name="name" type="text" :value="old('name', $services->isEmpty() ? 'Pelayanan Standar' : '')" required placeholder="Contoh: Konsultasi Standar, Paket Hemat, Pemeriksaan Umum" :error="$errors->has('name')" />
                            <x-input-error :messages="$errors->get('name')" class="mt-1" />
                        </div>

                        <div class="grid grid-cols-2 gap-4 md:grid-cols-3">
                            <div>
                                <x-form-label for="duration_minutes" value="Durasi (Menit)" :required="true" />
                                <x-input id="duration_minutes" name="duration_minutes" type="number" inputmode="numeric" min="5" max="480" :value="old('duration_minutes', 30)" required placeholder="30" :error="$errors->has('duration_minutes')" />
                                <x-input-error :messages="$errors->get('duration_minutes')" class="mt-1" />
                            </div>

                            <div>
                                <x-form-label for="price" value="Biaya (Rp)" :required="true" />
                                <x-input id="price" name="price" type="number" inputmode="numeric" min="0" step="1000" :value="old('price', 45000)" required placeholder="45000" :error="$errors->has('price')" />
                                <x-input-error :messages="$errors->get('price')" class="mt-1" />
                            </div>

                            <div class="col-span-2 md:col-span-1">
                                <x-form-label for="daily_quota" value="Kuota Harian (Opsional)" />
                                <x-input id="daily_quota" name="daily_quota" type="number" inputmode="numeric" min="1" :value="old('daily_quota', 50)" placeholder="Kosongkan jika tidak dibatasi" :error="$errors->has('daily_quota')" />
                                <x-input-error :messages="$errors->get('daily_quota')" class="mt-1" />
                            </div>
                        </div>

                        <div class="flex flex-col-reverse gap-3 pt-2 sm:flex-row sm:items-center sm:justify-between">
                            <a href="{{ route('merchant.onboarding', ['step' => 2]) }}" class="inline-flex h-11 items-center justify-center text-sm font-medium text-neutral-600 hover:text-neutral-900">
                                &larr; Sebelumnya
                            </a>
                            <x-button type="submit" variant="{{ $services->isEmpty() ? 'primary' : 'outline' }}" class="h-11 w-full px-6 sm:w-auto">
                                {{ $services->isEmpty() ? 'Simpan & Lanjut ke Preview' : 'Tambah Layanan Ini' }} &rarr;
                            </x-button>
                        </div>
                    </form>

                @elseif ($step === 4)
                    {{-- LANGKAH 4: QR CODE & KIRIM VERIFIKASI ADMIN --}}
                    <div class="mb-6 text-center">
                        <div class="mx-auto mb-3 flex size-14 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-700">
                            <x-customer.icon name="check" class="size-7" />
                        </div>
                        <h2 class="text-h3 font-bold text-neutral-900">Pendaftaran UMKM Siap Dikirim!</h2>
                        <p class="text-body text-neutral-600">Simpan QR Code toko Anda dan kirim pendaftaran untuk verifikasi Admin AntreIn.</p>
                    </div>

                    <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                        {{-- QR --}}
                        <div class="flex flex-col items-center gap-4 rounded-2xl border border-neutral-200 bg-linear-to-b from-brand-50 to-white p-5">
                            <div class="w-full max-w-[240px] rounded-2xl bg-white p-4 shadow-md ring-1 ring-neutral-200 [&_svg]:h-auto [&_svg]:w-full">
                                {!! $qrSvg !!}
                            </div>
                            <p class="text-center text-caption text-neutral-500">Setelah disetujui Admin, tempel QR Code ini di meja kasir agar pelanggan dapat memindai dengan kamera HP.</p>
                            <a href="{{ route('merchant.onboarding.download-qr') }}" class="inline-flex h-11 w-full items-center justify-center gap-2 rounded-lg border border-neutral-300 bg-white px-4 text-sm font-semibold text-neutral-800 shadow-xs transition hover:bg-neutral-50">
                                <svg class="size-4 text-neutral-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                                Unduh QR Code (SVG)
                            </a>
                        </div>

                        {{-- Ringkasan & Status Verifikasi --}}
                        <div class="flex flex-col gap-4">
                            <div class="flex items-center gap-3 rounded-2xl border border-neutral-200 bg-white p-4">
                                <div class="flex size-14 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-brand-50 ring-1 ring-brand-100">
                                    @if ($business->getLogoUrl())
                                        <img src="{{ $business->getLogoUrl() }}" alt="Logo {{ $business->name }}" class="size-full object-cover" />
                                    @else
                                        <span class="text-lg font-bold text-brand-700">{{ $businessInitials }}</span>
                                    @endif
                                </div>
                                <div class="flex min-w-0 flex-col">
                                    <p class="truncate text-base font-bold text-neutral-900">{{ $business->name }}</p>
                                    <p class="truncate text-caption text-neutral-500">{{ $business->address }}</p>
                                </div>
                            </div>

                            <ul class="flex flex-col gap-2 text-sm">
                                <li class="flex items-center gap-2 text-neutral-700">
                                    <x-customer.icon name="check" class="size-4 text-emerald-600" />
                                    Profil usaha lengkap
                                </li>
                                <li class="flex items-center gap-2 {{ $hoursMissing ? 'text-amber-800' : 'text-neutral-700' }}">
                                    <x-customer.icon :name="$hoursMissing ? 'clock' : 'check'" :class="$hoursMissing ? 'size-4 text-amber-600' : 'size-4 text-emerald-600'" />
                                    {{ $hoursMissing ? 'Jam standar akan dipasang otomatis' : $openDayCount.' hari buka per minggu' }}
                                </li>
                                <li class="flex items-center gap-2 text-neutral-700">
                                    <x-customer.icon name="check" class="size-4 text-emerald-600" />
                                    {{ $services->count() }} layanan siap dipilih pelanggan
                                </li>
                            </ul>

                            <div class="rounded-2xl border border-amber-200 bg-amber-50/70 p-4 text-xs text-amber-950">
                                <div class="flex items-start gap-2.5">
                                    <x-customer.icon name="clock" class="mt-0.5 size-4 shrink-0 text-amber-600" />
                                    <div class="space-y-1">
                                        <p class="font-bold">Verifikasi Admin AntreIn</p>
                                        <p class="leading-relaxed text-amber-900">
                                            Setelah pendaftaran dikirim, status UMKM Anda akan masuk ke halaman tunggu persetujuan Admin. Tim kami akan memverifikasi data dan menghubungi Anda setelah disetujui (Accept) atau ditolak (Reject).
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('merchant.onboarding.complete') }}" class="mt-6 flex flex-col-reverse gap-3 border-t border-neutral-200 pt-5 sm:flex-row sm:items-center sm:justify-between">
                        @csrf
                        <a href="{{ route('merchant.onboarding', ['step' => 3]) }}" class="inline-flex h-11 items-center justify-center text-sm font-medium text-neutral-600 hover:text-neutral-900">
                            &larr; Ubah Layanan
                        </a>
                        <x-button type="submit" variant="primary" class="h-12 w-full px-8 sm:w-auto">
                            Kirim Pendaftaran untuk Verifikasi Admin &rarr;
                        </x-button>
                    </form>
                @endif
            </x-card>
        </div>
    </div>
</x-layouts.base>
