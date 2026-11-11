<x-layouts.base title="Onboarding UMKM — AntreIn">
    <div class="min-h-screen bg-neutral-50 py-10 px-4 sm:px-6 lg:px-8">
        <div class="max-w-3xl mx-auto">
            <!-- Header Brand -->
            <div class="text-center mb-8">
                <a href="/" class="inline-flex items-center gap-1.5 focus:outline-none">
                    <span class="text-3xl font-extrabold tracking-tight text-neutral-950">AntreIn<span class="text-brand-700">.</span></span>
                </a>
                <h1 class="mt-3 text-h2 font-bold text-neutral-950">Konfigurasi Awal Usaha</h1>
                <p class="mt-1 text-body text-neutral-600">Selesaikan 4 langkah mudah untuk mulai menerima antrean digital.</p>
            </div>

            <!-- Stepper Navigation -->
            <div class="mb-8 bg-white border border-neutral-200 rounded-xl p-4 shadow-xs">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-center">
                    <!-- Step 1 -->
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-caption font-bold {{ $step > 1 ? 'bg-brand-700 text-white' : ($step === 1 ? 'bg-brand-100 text-brand-800 ring-2 ring-brand-700' : 'bg-neutral-100 text-neutral-500') }}">
                            @if ($step > 1)
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>
                            @else
                                1
                            @endif
                        </div>
                        <div class="text-left">
                            <span class="text-xs font-semibold uppercase tracking-wider {{ $step === 1 ? 'text-brand-700' : 'text-neutral-500' }}">Langkah 1</span>
                            <p class="text-caption font-medium {{ $step === 1 ? 'text-neutral-900' : 'text-neutral-600' }}">Profil Usaha</p>
                        </div>
                    </div>

                    <!-- Step 2 -->
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-caption font-bold {{ $step > 2 ? 'bg-brand-700 text-white' : ($step === 2 ? 'bg-brand-100 text-brand-800 ring-2 ring-brand-700' : 'bg-neutral-100 text-neutral-500') }}">
                            @if ($step > 2)
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>
                            @else
                                2
                            @endif
                        </div>
                        <div class="text-left">
                            <span class="text-xs font-semibold uppercase tracking-wider {{ $step === 2 ? 'text-brand-700' : 'text-neutral-500' }}">Langkah 2</span>
                            <p class="text-caption font-medium {{ $step === 2 ? 'text-neutral-900' : 'text-neutral-600' }}">Jam Operasional</p>
                        </div>
                    </div>

                    <!-- Step 3 -->
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-caption font-bold {{ $step > 3 ? 'bg-brand-700 text-white' : ($step === 3 ? 'bg-brand-100 text-brand-800 ring-2 ring-brand-700' : 'bg-neutral-100 text-neutral-500') }}">
                            @if ($step > 3)
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>
                            @else
                                3
                            @endif
                        </div>
                        <div class="text-left">
                            <span class="text-xs font-semibold uppercase tracking-wider {{ $step === 3 ? 'text-brand-700' : 'text-neutral-500' }}">Langkah 3</span>
                            <p class="text-caption font-medium {{ $step === 3 ? 'text-neutral-900' : 'text-neutral-600' }}">Layanan Perdana</p>
                        </div>
                    </div>

                    <!-- Step 4 -->
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-caption font-bold {{ $step === 4 ? 'bg-brand-700 text-white' : 'bg-neutral-100 text-neutral-500' }}">
                            4
                        </div>
                        <div class="text-left">
                            <span class="text-xs font-semibold uppercase tracking-wider {{ $step === 4 ? 'text-brand-700' : 'text-neutral-500' }}">Langkah 4</span>
                            <p class="text-caption font-medium {{ $step === 4 ? 'text-neutral-900' : 'text-neutral-600' }}">URL & QR Code</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Status Alert -->
            @if (session('status'))
                <div class="mb-6 rounded-lg bg-emerald-50 p-4 border border-emerald-200 text-sm font-medium text-emerald-800 flex items-center gap-2">
                    <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <span>{{ session('status') }}</span>
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-6 rounded-lg bg-rose-50 p-4 border border-rose-200 text-sm text-rose-800">
                    <p class="font-semibold mb-1">Periksa kembali data yang dimasukkan:</p>
                    <ul class="list-disc list-inside space-y-0.5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Step Cards -->
            <x-card padding="lg" class="shadow-sm border-neutral-200">
                @if ($step === 1)
                    <!-- STEP 1: PROFIL USAHA -->
                    <div class="mb-6">
                        <h2 class="text-h3 font-bold text-neutral-900">1. Data Profil Usaha</h2>
                        <p class="text-caption text-neutral-500">Informasi ini akan ditampilkan kepada pelanggan saat mengakses antrean online.</p>
                    </div>

                    <form method="POST" action="{{ route('merchant.onboarding.step1') }}" class="space-y-5">
                        @csrf

                        <div>
                            <x-form-label for="name" value="Nama Usaha / Toko" :required="true" />
                            <x-input
                                id="name"
                                name="name"
                                type="text"
                                :value="old('name', $business?->name)"
                                required
                                placeholder="Contoh: Barbershop Garasi"
                                :invalid="$errors->has('name')"
                            />
                            <x-input-error :messages="$errors->get('name')" class="mt-1" />
                        </div>

                        <div>
                            <x-form-label for="category_id" value="Kategori Usaha" :required="true" />
                            <x-select id="category_id" name="category_id" required :invalid="$errors->has('category_id')">
                                <option value="">-- Pilih Kategori Usaha --</option>
                                @foreach ($categories as $cat)
                                    <option value="{{ $cat->id }}" @selected(old('category_id', $business?->category_id) == $cat->id)>
                                        {{ $cat->name }}
                                    </option>
                                @endforeach
                            </x-select>
                            <x-input-error :messages="$errors->get('category_id')" class="mt-1" />
                        </div>

                        <div>
                            <x-form-label for="description" value="Deskripsi Singkat (Opsional)" />
                            <x-textarea
                                id="description"
                                name="description"
                                rows="3"
                                placeholder="Contoh: Melayani pangkas rambut pria profesional, cuci rambut, dan perawatan jenggot."
                                :invalid="$errors->has('description')"
                            >{{ old('description', $business?->description) }}</x-textarea>
                            <x-input-error :messages="$errors->get('description')" class="mt-1" />
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <x-form-label for="phone" value="Nomor WhatsApp / Telepon Usaha" :required="true" />
                                <x-input
                                    id="phone"
                                    name="phone"
                                    type="text"
                                    :value="old('phone', $business?->phone)"
                                    required
                                    placeholder="081234567890"
                                    :invalid="$errors->has('phone')"
                                />
                                <x-input-error :messages="$errors->get('phone')" class="mt-1" />
                            </div>

                            <div>
                                <x-form-label for="timezone" value="Zona Waktu Usaha" :required="true" />
                                <x-select id="timezone" name="timezone" required :invalid="$errors->has('timezone')">
                                    <option value="Asia/Makassar" @selected(old('timezone', $business?->timezone ?? 'Asia/Makassar') === 'Asia/Makassar')>
                                        WITA — Asia/Makassar (Default Bali Pilot)
                                    </option>
                                    <option value="Asia/Jakarta" @selected(old('timezone', $business?->timezone) === 'Asia/Jakarta')>
                                        WIB — Asia/Jakarta
                                    </option>
                                    <option value="Asia/Jayapura" @selected(old('timezone', $business?->timezone) === 'Asia/Jayapura')>
                                        WIT — Asia/Jayapura
                                    </option>
                                </x-select>
                                <x-input-error :messages="$errors->get('timezone')" class="mt-1" />
                            </div>
                        </div>

                        <div>
                            <x-form-label for="address" value="Alamat Lengkap Usaha" :required="true" />
                            <x-textarea
                                id="address"
                                name="address"
                                rows="2"
                                required
                                placeholder="Contoh: Jl. Sunset Road No. 88, Seminyak, Kuta, Badung, Bali"
                                :invalid="$errors->has('address')"
                            >{{ old('address', $business?->address) }}</x-textarea>
                            <x-input-error :messages="$errors->get('address')" class="mt-1" />
                        </div>

                        <div class="pt-4 flex justify-end">
                            <x-button type="submit" variant="primary" class="h-11 px-6">
                                Simpan & Lanjut ke Jam Operasional &rarr;
                            </x-button>
                        </div>
                    </form>

                @elseif ($step === 2)
                    <!-- STEP 2: JAM OPERASIONAL -->
                    <div class="mb-6 flex items-center justify-between">
                        <div>
                            <h2 class="text-h3 font-bold text-neutral-900">2. Atur Jam Operasional Mingguan</h2>
                            <p class="text-caption text-neutral-500">Tentukan hari dan jam buka layanan untuk antrean pelanggan.</p>
                        </div>
                        <a href="{{ route('merchant.onboarding', ['step' => 1]) }}" class="text-caption font-semibold text-neutral-600 hover:text-neutral-900">
                            &larr; Kembali ke Profil
                        </a>
                    </div>

                    <form method="POST" action="{{ route('merchant.onboarding.step2') }}" class="space-y-4">
                        @csrf

                        @php
                            $days = [
                                1 => 'Senin',
                                2 => 'Selasa',
                                3 => 'Rabu',
                                4 => 'Kamis',
                                5 => 'Jumat',
                                6 => 'Sabtu',
                                0 => 'Minggu',
                            ];
                        @endphp

                        <div class="border border-neutral-200 rounded-lg divide-y divide-neutral-200 overflow-hidden">
                            @foreach ($days as $dow => $dayName)
                                @php
                                    $existingHour = $hours->firstWhere('day_of_week', $dow);
                                    $openTime = $existingHour ? substr($existingHour->open_time, 0, 5) : '09:00';
                                    $closeTime = $existingHour ? substr($existingHour->close_time, 0, 5) : '21:00';
                                    $isClosed = $existingHour ? (bool) $existingHour->is_closed : ($dow === 0);
                                @endphp
                                <div class="p-3.5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-white hover:bg-neutral-50/50">
                                    <input type="hidden" name="hours[{{ $loop->index }}][day_of_week]" value="{{ $dow }}">
                                    
                                    <div class="w-28 font-medium text-neutral-900">
                                        {{ $dayName }}
                                    </div>

                                    <div class="flex items-center gap-3">
                                        <div class="flex items-center gap-2">
                                            <input
                                                type="time"
                                                name="hours[{{ $loop->index }}][open_time]"
                                                value="{{ old("hours.{$loop->index}.open_time", $openTime) }}"
                                                class="rounded-md border border-neutral-300 text-sm px-2.5 py-1.5 focus:border-brand-700 focus:ring-1 focus:ring-brand-700"
                                            />
                                            <span class="text-neutral-400 text-sm">s/d</span>
                                            <input
                                                type="time"
                                                name="hours[{{ $loop->index }}][close_time]"
                                                value="{{ old("hours.{$loop->index}.close_time", $closeTime) }}"
                                                class="rounded-md border border-neutral-300 text-sm px-2.5 py-1.5 focus:border-brand-700 focus:ring-1 focus:ring-brand-700"
                                            />
                                        </div>

                                        <label class="inline-flex items-center gap-1.5 text-xs text-neutral-600 cursor-pointer ml-2">
                                            <input
                                                type="checkbox"
                                                name="hours[{{ $loop->index }}][is_closed]"
                                                value="1"
                                                @checked(old("hours.{$loop->index}.is_closed", $isClosed))
                                                class="rounded border-neutral-300 text-brand-700 focus:ring-brand-700"
                                            />
                                            Tutup
                                        </label>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="pt-4 flex justify-between items-center">
                            <a href="{{ route('merchant.onboarding', ['step' => 1]) }}" class="text-sm font-medium text-neutral-600 hover:text-neutral-900">
                                &larr; Sebelumnya
                            </a>
                            <x-button type="submit" variant="primary" class="h-11 px-6">
                                Simpan & Lanjut ke Layanan &rarr;
                            </x-button>
                        </div>
                    </form>

                @elseif ($step === 3)
                    <!-- STEP 3: LAYANAN PERDANA -->
                    <div class="mb-6 flex items-center justify-between">
                        <div>
                            <h2 class="text-h3 font-bold text-neutral-900">3. Tambah Layanan Perdana</h2>
                            <p class="text-caption text-neutral-500">Pelanggan akan memilih layanan ini saat mengambil tiket antrean.</p>
                        </div>
                        <a href="{{ route('merchant.onboarding', ['step' => 2]) }}" class="text-caption font-semibold text-neutral-600 hover:text-neutral-900">
                            &larr; Kembali ke Jam
                        </a>
                    </div>

                    <form method="POST" action="{{ route('merchant.onboarding.step3') }}" class="space-y-4">
                        @csrf

                        <div>
                            <x-form-label for="svc_name" value="Nama Layanan" :required="true" />
                            <x-input
                                id="svc_name"
                                name="name"
                                type="text"
                                :value="old('name', 'Pelayanan Standar')"
                                required
                                placeholder="Contoh: Potong Rambut Reguler, Konsultasi Gigi, Servis Motor Ringan"
                                :invalid="$errors->has('name')"
                            />
                            <x-input-error :messages="$errors->get('name')" class="mt-1" />
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <x-form-label for="duration_minutes" value="Estimasi Durasi (Menit)" :required="true" />
                                <x-input
                                    id="duration_minutes"
                                    name="duration_minutes"
                                    type="number"
                                    min="5"
                                    max="480"
                                    :value="old('duration_minutes', 30)"
                                    required
                                    placeholder="30"
                                    :invalid="$errors->has('duration_minutes')"
                                />
                                <x-input-error :messages="$errors->get('duration_minutes')" class="mt-1" />
                            </div>

                            <div>
                                <x-form-label for="price" value="Biaya Layanan (Rp)" :required="true" />
                                <x-input
                                    id="price"
                                    name="price"
                                    type="number"
                                    min="0"
                                    step="1000"
                                    :value="old('price', 45000)"
                                    required
                                    placeholder="45000"
                                    :invalid="$errors->has('price')"
                                />
                                <x-input-error :messages="$errors->get('price')" class="mt-1" />
                            </div>

                            <div>
                                <x-form-label for="daily_quota" value="Kuota Harian (Opsional)" />
                                <x-input
                                    id="daily_quota"
                                    name="daily_quota"
                                    type="number"
                                    min="1"
                                    :value="old('daily_quota', 50)"
                                    placeholder="Kosongkan jika tidak dibatasi"
                                    :invalid="$errors->has('daily_quota')"
                                />
                                <x-input-error :messages="$errors->get('daily_quota')" class="mt-1" />
                            </div>
                        </div>

                        @if ($services->isNotEmpty())
                            <div class="mt-4 p-3 bg-neutral-50 rounded-lg border border-neutral-200">
                                <span class="text-xs font-semibold text-neutral-500 uppercase">Layanan Terdaftar:</span>
                                <ul class="mt-2 space-y-1 text-sm text-neutral-800">
                                    @foreach ($services as $svc)
                                        <li class="flex items-center justify-between">
                                            <span class="font-medium">{{ $svc->name }} ({{ $svc->duration_minutes }} mnt)</span>
                                            <span>Rp {{ number_format($svc->price, 0, ',', '.') }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="pt-4 flex justify-between items-center">
                            <a href="{{ route('merchant.onboarding', ['step' => 2]) }}" class="text-sm font-medium text-neutral-600 hover:text-neutral-900">
                                &larr; Sebelumnya
                            </a>
                            <x-button type="submit" variant="primary" class="h-11 px-6">
                                Simpan & Lanjut ke Preview &rarr;
                            </x-button>
                        </div>
                    </form>

                @elseif ($step === 4)
                    <!-- STEP 4: PREVIEW URL & QR CODE -->
                    <div class="text-center mb-6">
                        <div class="w-12 h-12 bg-emerald-100 text-emerald-700 rounded-full flex items-center justify-center mx-auto mb-3">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>
                        </div>
                        <h2 class="text-h3 font-bold text-neutral-900">Onboarding Siap Selesai!</h2>
                        <p class="text-body text-neutral-600">Bagikan URL atau cetak QR Code berikut agar pelanggan Anda dapat mengambil tiket antrean.</p>
                    </div>

                    <div class="bg-neutral-50 border border-neutral-200 rounded-xl p-6 text-center max-w-md mx-auto mb-6">
                        <!-- QR Code Container -->
                        <div class="bg-white p-4 rounded-lg shadow-xs inline-block border border-neutral-200 mb-4">
                            {!! $qrSvg !!}
                        </div>

                        <div class="text-left bg-white p-3 rounded-lg border border-neutral-200 mb-4">
                            <span class="text-xs font-semibold text-neutral-500 uppercase block mb-1">Tautan Antrean Pelanggan:</span>
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-sm font-mono text-neutral-800 break-all select-all">{{ $publicUrl }}</span>
                            </div>
                        </div>

                        <div class="flex items-center justify-center gap-3">
                            <a
                                href="{{ route('merchant.onboarding.download-qr') }}"
                                class="inline-flex items-center gap-2 px-4 py-2 border border-neutral-300 rounded-lg text-sm font-medium text-neutral-700 bg-white hover:bg-neutral-50 shadow-xs"
                            >
                                <svg class="w-4 h-4 text-neutral-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                                Unduh QR Code (SVG)
                            </a>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('merchant.onboarding.complete') }}" class="pt-4 flex justify-between items-center border-t border-neutral-200">
                        @csrf
                        <a href="{{ route('merchant.onboarding', ['step' => 3]) }}" class="text-sm font-medium text-neutral-600 hover:text-neutral-900">
                            &larr; Ubah Layanan
                        </a>
                        <x-button type="submit" variant="primary" class="h-11 px-8">
                            Selesaikan & Buka Dashboard &rarr;
                        </x-button>
                    </form>
                @endif
            </x-card>
        </div>
    </div>
</x-layouts.base>

