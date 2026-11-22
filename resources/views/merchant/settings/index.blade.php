<x-layouts.merchant title="Pengaturan Usaha">
    <div class="space-y-6">
        {{-- Page Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-[1.375rem] leading-[1.875rem] font-bold sm:text-[1.75rem] sm:leading-9 text-neutral-950">Pengaturan Usaha</h1>
                <p class="text-body text-neutral-600 mt-1">Kelola informasi publik, jadwal operasional mingguan, dan status penerimaan antrean.</p>
            </div>
            <x-status-badge :status="$business->is_suspended ? 'suspended' : $business->status" size="lg" class="w-fit" />
        </div>

        {{-- Suspended Notice (If applicable) --}}
        @if ($business->is_suspended)
            <div class="rounded-xl bg-rose-50 border border-rose-300 p-5 text-rose-900 flex items-start gap-3 shadow-card">
                <svg class="w-6 h-6 text-rose-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                <div>
                    <h3 class="font-bold text-sm text-rose-950">Akun Usaha Sedang Dalam Masa Penangguhan (Suspended)</h3>
                    <p class="text-xs text-rose-800 mt-1">
                        Platform administrator telah menangguhkan akun usaha ini. Penerimaan tiket online maupun datang langsung baru diblokir sepenuhnya. Anda tetap dapat menyelesaikan atau membatalkan tiket yang sedang aktif. Silakan hubungi tim dukungan jika membutuhkan klarifikasi lebih lanjut.
                    </p>
                </div>
            </div>
        @endif

        {{-- Notifications --}}
        @if (session('status'))
            <div class="rounded-lg bg-emerald-50 border border-emerald-200 p-4 text-sm font-medium text-emerald-800 flex items-center gap-2">
                <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <span>{{ session('status') }}</span>
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-lg bg-rose-50 border border-rose-200 p-4 text-sm text-rose-800">
                <p class="font-semibold mb-1">Periksa kembali data yang dimasukkan:</p>
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Navigation Tabs: horizontally scrollable pills on mobile --}}
        @php
            $settingsTabs = [
                ['key' => 'profile', 'label' => 'Profil Usaha & Identitas', 'href' => route('merchant.settings', ['tab' => 'profile'])],
                ['key' => 'hours', 'label' => 'Jam Operasional Mingguan', 'href' => route('merchant.settings', ['tab' => 'hours'])],
                ['key' => 'status', 'label' => 'Status Operasional Antrean', 'href' => route('merchant.settings', ['tab' => 'status'])],
                ['key' => 'account', 'label' => 'Akun & Keamanan', 'href' => route('merchant.settings', ['tab' => 'account'])],
                ['key' => 'notifications', 'label' => 'Notifikasi', 'href' => route('merchant.settings', ['tab' => 'notifications'])],
            ];
        @endphp
        <nav class="-mx-4 overflow-x-auto px-4 sm:mx-0 sm:px-0" aria-label="Bagian pengaturan">
            <div class="flex w-max gap-2 rounded-xl border border-neutral-200 bg-white p-1.5 shadow-card sm:w-auto sm:flex-wrap">
                @foreach ($settingsTabs as $settingsTab)
                    <a
                        href="{{ $settingsTab['href'] }}"
                        @class([
                            'inline-flex min-h-11 shrink-0 items-center whitespace-nowrap rounded-lg px-4 text-sm font-semibold transition-colors',
                            'bg-brand-700 text-white shadow-card' => $activeTab === $settingsTab['key'],
                            'text-neutral-600 hover:bg-neutral-100 hover:text-neutral-900' => $activeTab !== $settingsTab['key'],
                        ])
                        @if ($activeTab === $settingsTab['key']) aria-current="page" @endif
                    >
                        {{ $settingsTab['label'] }}
                    </a>
                @endforeach
            </div>
        </nav>

        {{-- TAB CONTENT: PROFIL USAHA --}}
        @if ($activeTab === 'profile')
            <x-card padding="lg">
                <form method="POST" action="{{ route('merchant.settings.profile') }}" enctype="multipart/form-data" class="space-y-6">
                    @csrf
                    @method('PUT')

                    {{-- Logo Section --}}
                    <div
                        x-data="{
                            preview: @js($business->getLogoUrl()),
                            fileName: '',
                            pick(event) {
                                const file = event.target.files[0];
                                if (! file) { return; }
                                this.fileName = file.name;
                                this.preview = URL.createObjectURL(file);
                            },
                        }"
                    >
                        <x-form-label value="Logo Usaha" />
                        <div class="mt-2 flex flex-col gap-4 rounded-xl border border-dashed border-neutral-300 bg-neutral-50/60 p-4 sm:flex-row sm:items-center">
                            <div class="flex size-20 shrink-0 items-center justify-center overflow-hidden rounded-2xl border border-neutral-200 bg-white shadow-card">
                                <img x-show="preview" :src="preview" @if ($business->getLogoUrl()) src="{{ $business->getLogoUrl() }}" @else style="display: none;" @endif alt="Logo Usaha" class="size-full object-cover">
                                <span x-show="! preview" class="text-xl font-bold text-brand-700" @if ($business->getLogoUrl()) style="display: none;" @endif>{{ collect(preg_split('/\s+/', trim($business->name)))->filter()->take(2)->map(fn ($word) => Str::upper(Str::substr($word, 0, 1)))->implode('') }}</span>
                            </div>

                            <div class="flex min-w-0 flex-1 flex-col gap-1">
                                <p class="text-caption text-neutral-600">Maksimal 2 MB. Format JPG, PNG, atau WebP. Gambar persegi disarankan.</p>
                                <p x-show="fileName" x-text="fileName" class="truncate text-caption font-medium text-brand-700" style="display: none;"></p>
                            </div>

                            <div class="grid grid-cols-2 gap-2 sm:flex sm:shrink-0">
                                <label for="logo" class="inline-flex h-11 cursor-pointer items-center justify-center rounded-lg border border-neutral-300 bg-white px-4 text-sm font-semibold text-neutral-800 shadow-card transition hover:bg-neutral-50 focus-within:ring-2 focus-within:ring-brand-500 {{ $business->logo_path ? '' : 'col-span-2' }}">
                                    <span x-text="preview ? 'Ganti Logo' : 'Pilih Logo'">{{ $business->logo_path ? 'Ganti Logo' : 'Pilih Logo' }}</span>
                                    <input type="file" name="logo" id="logo" accept="image/jpeg,image/png,image/webp" class="sr-only" @change="pick($event)" />
                                </label>
                                @if ($business->logo_path)
                                    <button
                                        type="button"
                                        onclick="if (confirm('Hapus logo usaha saat ini?')) document.getElementById('form-delete-logo').submit();"
                                        class="inline-flex h-11 items-center justify-center rounded-lg border border-rose-200 bg-rose-50 px-4 text-sm font-semibold text-rose-700 transition-colors hover:bg-rose-100"
                                    >
                                        Hapus Logo
                                    </button>
                                @endif
                            </div>
                        </div>
                        <x-input-error :messages="$errors->get('logo')" class="mt-1" />
                    </div>

                    {{-- Cover Section --}}
                    <div
                        x-data="{
                            preview: @js($business->coverImageUrl()),
                            fileName: '',
                            pick(event) {
                                const file = event.target.files[0];
                                if (! file) { return; }
                                this.fileName = file.name;
                                this.preview = URL.createObjectURL(file);
                            },
                        }"
                    >
                        <x-form-label value="Foto Sampul Toko" />
                        <div class="mt-2 flex flex-col gap-4 rounded-xl border border-dashed border-neutral-300 bg-neutral-50/60 p-4 sm:flex-row sm:items-center">
                            <div class="flex aspect-[16/9] w-full shrink-0 items-center justify-center overflow-hidden rounded-xl border border-neutral-200 bg-white shadow-card sm:w-40">
                                <img x-show="preview" :src="preview" @if ($business->coverImageUrl()) src="{{ $business->coverImageUrl() }}" @else style="display: none;" @endif alt="Foto sampul toko" class="size-full object-cover">
                                <span x-show="! preview" class="px-3 text-center text-caption text-neutral-500" @if ($business->coverImageUrl()) style="display: none;" @endif>Belum ada foto</span>
                            </div>

                            <div class="flex min-w-0 flex-1 flex-col gap-1">
                                <p class="text-caption text-neutral-600">Tampil di atas halaman toko dan kartu toko pelanggan. Maksimal 4 MB, format JPG, PNG, atau WebP. Foto mendatar (landscape) disarankan.</p>
                                @unless ($business->cover_path)
                                    <p class="text-caption text-neutral-500">Saat ini memakai foto bawaan sesuai kategori.</p>
                                @endunless
                                <p x-show="fileName" x-text="fileName" class="truncate text-caption font-medium text-brand-700" style="display: none;"></p>
                            </div>

                            <div class="grid grid-cols-2 gap-2 sm:flex sm:shrink-0">
                                <label for="cover" class="inline-flex h-11 cursor-pointer items-center justify-center rounded-lg border border-neutral-300 bg-white px-4 text-sm font-semibold text-neutral-800 shadow-card transition hover:bg-neutral-50 focus-within:ring-2 focus-within:ring-brand-500 {{ $business->cover_path ? '' : 'col-span-2' }}">
                                    {{ $business->cover_path ? 'Ganti Foto' : 'Unggah Foto' }}
                                    <input type="file" name="cover" id="cover" accept="image/jpeg,image/png,image/webp" class="sr-only" @change="pick($event)" />
                                </label>
                                @if ($business->cover_path)
                                    <button
                                        type="button"
                                        onclick="if (confirm('Hapus foto sampul dan kembali ke foto bawaan kategori?')) document.getElementById('form-delete-cover').submit();"
                                        class="inline-flex h-11 items-center justify-center rounded-lg border border-rose-200 bg-rose-50 px-4 text-sm font-semibold text-rose-700 transition-colors hover:bg-rose-100"
                                    >
                                        Hapus Foto
                                    </button>
                                @endif
                            </div>
                        </div>
                        <x-input-error :messages="$errors->get('cover')" class="mt-1" />
                    </div>

                    <hr class="border-neutral-200">

                    {{-- Basic Info --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <x-form-label for="name" value="Nama Usaha / Toko" :required="true" />
                            <x-input
                                id="name"
                                name="name"
                                type="text"
                                :value="old('name', $business->name)"
                                required
                                :error="$errors->has('name')"
                            />
                            <x-input-error :messages="$errors->get('name')" class="mt-1" />
                        </div>

                        <div>
                            <x-form-label for="category_id" value="Kategori Usaha" :required="true" />
                            <x-select id="category_id" name="category_id" required :error="$errors->has('category_id')">
                                <option value="">Pilih kategori</option>
                                @foreach ($categories as $cat)
                                    <option value="{{ $cat->id }}" @selected(old('category_id', $business->category_id) == $cat->id)>
                                        {{ $cat->name }}{{ ! $cat->is_active ? ' (Nonaktif)' : '' }}
                                    </option>
                                @endforeach
                            </x-select>
                            <x-input-error :messages="$errors->get('category_id')" class="mt-1" />
                        </div>
                    </div>

                    {{-- Slug URL Section --}}
                    <div>
                        <x-form-label for="slug" value="Slug Publik Usaha (URL Publik)" :required="true" />
                        <div class="mt-1 flex rounded-md shadow-card">
                            <span class="inline-flex shrink-0 items-center rounded-l-md border border-r-0 border-neutral-300 bg-neutral-50 px-3 font-mono text-sm text-neutral-500">
                                <span class="sm:hidden">/q/</span>
                                <span class="hidden sm:inline">{{ url('/q') }}/</span>
                            </span>
                            <input
                                type="text"
                                name="slug"
                                id="slug"
                                value="{{ old('slug', $business->slug) }}"
                                required
                                class="block h-11 w-full min-w-0 flex-1 rounded-none rounded-r-md border border-neutral-300 px-3 font-mono text-sm focus:border-brand-700 focus:ring-brand-700"
                            />
                        </div>
                        <p class="text-caption text-neutral-600 mt-1">
                            Peringatan: Mengubah slug akan mengubah alamat antrean toko Anda. QR code cetak lama perlu diperbarui jika slug diubah.
                        </p>
                        <x-input-error :messages="$errors->get('slug')" class="mt-1" />
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                        <div>
                            <x-form-label for="phone" value="Nomor Telepon / WhatsApp" :required="true" />
                            <x-input
                                id="phone"
                                name="phone"
                                type="text"
                                :value="old('phone', $business->phone)"
                                required
                                :error="$errors->has('phone')"
                            />
                            <x-input-error :messages="$errors->get('phone')" class="mt-1" />
                        </div>

                        <div>
                            <x-form-label for="area" value="Daerah / Area Usaha" />
                            <x-select id="area" name="area" :error="$errors->has('area')">
                                <option value="">Pilih daerah / area (opsional)</option>
                                @foreach ($areas as $areaKey => $areaLabel)
                                    <option value="{{ $areaKey }}" @selected(old('area', $business->area) === $areaKey)>{{ $areaLabel }}</option>
                                @endforeach
                            </x-select>
                            <x-input-error :messages="$errors->get('area')" class="mt-1" />
                        </div>

                        <div>
                            <x-form-label for="timezone" value="Zona Waktu Operasional" :required="true" />
                            <x-select id="timezone" name="timezone" required :error="$errors->has('timezone')">
                                <option value="Asia/Makassar" @selected(old('timezone', $business->timezone) === 'Asia/Makassar')>
                                    WITA (Asia/Makassar)
                                </option>
                                <option value="Asia/Jakarta" @selected(old('timezone', $business->timezone) === 'Asia/Jakarta')>
                                    WIB (Asia/Jakarta)
                                </option>
                                <option value="Asia/Jayapura" @selected(old('timezone', $business->timezone) === 'Asia/Jayapura')>
                                    WIT (Asia/Jayapura)
                                </option>
                            </x-select>
                            <x-input-error :messages="$errors->get('timezone')" class="mt-1" />
                        </div>
                    </div>

                    <div>
                        <x-form-label for="description" value="Deskripsi Singkat Usaha" />
                        <x-textarea id="description" name="description" rows="3">{{ old('description', $business->description) }}</x-textarea>
                        <x-input-error :messages="$errors->get('description')" class="mt-1" />
                    </div>

                    <div>
                        <x-form-label for="address" value="Alamat Lengkap Usaha" />
                        <x-textarea id="address" name="address" rows="2">{{ old('address', $business->address) }}</x-textarea>
                        <x-input-error :messages="$errors->get('address')" class="mt-1" />
                    </div>

                    <div class="pt-4 flex justify-end border-t border-neutral-200">
                        <x-button type="submit" variant="primary" class="h-11 w-full px-6 sm:w-auto">
                            Simpan Perubahan Profil
                        </x-button>
                    </div>
                </form>

                {{-- Hidden Logo Deletion Form --}}
                <form id="form-delete-logo" method="POST" action="{{ route('merchant.settings.logo.remove') }}" class="hidden">
                    @csrf
                    @method('DELETE')
                </form>
                <form id="form-delete-cover" method="POST" action="{{ route('merchant.settings.cover.remove') }}" class="hidden">
                    @csrf
                    @method('DELETE')
                </form>
            </x-card>

        {{-- TAB CONTENT: JAM OPERASIONAL --}}
        @elseif ($activeTab === 'hours')
            <x-card padding="lg">
                <div class="mb-5">
                    <h2 class="text-h3 font-bold text-neutral-900">Jadwal Operasional Mingguan</h2>
                    <p class="text-caption text-neutral-600">Pelanggan hanya dapat mengambil nomor antrean online selama jam operasional aktif pada hari tersebut.</p>
                </div>

                <form method="POST" action="{{ route('merchant.settings.hours') }}" class="space-y-4">
                    @csrf
                    @method('PUT')

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

                    <div class="divide-y divide-neutral-200 overflow-hidden rounded-xl border border-neutral-200">
                        @foreach ($days as $dow => $dayName)
                            @php
                                $existingHour = $hours->firstWhere('day_of_week', $dow);
                                $openTime = old("hours.{$loop->index}.open_time", $existingHour ? substr($existingHour->open_time, 0, 5) : '09:00');
                                $closeTime = old("hours.{$loop->index}.close_time", $existingHour ? substr($existingHour->close_time, 0, 5) : '21:00');
                                $isClosed = (bool) old("hours.{$loop->index}.is_closed", $existingHour ? (bool) $existingHour->is_closed : ($dow === 0));
                            @endphp
                            <div x-data="{ closed: @js($isClosed) }" class="grid grid-cols-[1fr_auto] items-center gap-3 p-3.5 transition-colors sm:grid-cols-[8rem_1fr_auto] sm:p-4" :class="closed ? 'bg-neutral-50' : 'bg-white'">
                                <input type="hidden" name="hours[{{ $loop->index }}][day_of_week]" value="{{ $dow }}">

                                <span class="font-semibold text-neutral-900">{{ $dayName }}</span>

                                <div class="col-span-2 grid grid-cols-[1fr_auto_1fr] items-center gap-2 transition-opacity sm:col-span-1 sm:col-start-2 sm:row-start-1 sm:max-w-sm" :class="closed && 'opacity-40'">
                                    <input
                                        type="time"
                                        name="hours[{{ $loop->index }}][open_time]"
                                        value="{{ $openTime }}"
                                        :disabled="closed"
                                        aria-label="Jam buka {{ $dayName }}"
                                        class="h-11 w-full min-w-0 rounded-lg border border-neutral-300 px-3 text-sm tabular-nums focus:border-brand-700 focus:ring-1 focus:ring-brand-700 disabled:cursor-not-allowed disabled:bg-neutral-100"
                                    />
                                    <span class="text-sm text-neutral-500">s/d</span>
                                    <input
                                        type="time"
                                        name="hours[{{ $loop->index }}][close_time]"
                                        value="{{ $closeTime }}"
                                        :disabled="closed"
                                        aria-label="Jam tutup {{ $dayName }}"
                                        class="h-11 w-full min-w-0 rounded-lg border border-neutral-300 px-3 text-sm tabular-nums focus:border-brand-700 focus:ring-1 focus:ring-brand-700 disabled:cursor-not-allowed disabled:bg-neutral-100"
                                    />
                                </div>

                                <label
                                    class="col-start-2 row-start-1 inline-flex h-11 cursor-pointer items-center gap-2 rounded-lg border px-3 text-sm font-semibold transition sm:col-start-3"
                                    :class="closed ? 'border-neutral-800 bg-neutral-800 text-white' : 'border-neutral-300 bg-white text-neutral-700 hover:bg-neutral-50'"
                                >
                                    <input
                                        type="checkbox"
                                        name="hours[{{ $loop->index }}][is_closed]"
                                        value="1"
                                        x-model="closed"
                                        @checked($isClosed)
                                        class="rounded border-neutral-300 text-brand-700 focus:ring-brand-700"
                                    />
                                    Tutup / Libur
                                </label>
                            </div>
                        @endforeach
                    </div>

                    <div class="pt-4 flex justify-end border-t border-neutral-200">
                        <x-button type="submit" variant="primary" class="h-11 w-full px-6 sm:w-auto">
                            Simpan Jadwal Operasional
                        </x-button>
                    </div>
                </form>
            </x-card>

        {{-- TAB CONTENT: STATUS OPERASIONAL --}}
        @elseif ($activeTab === 'status')
            <x-card padding="lg">
                <div class="mb-5">
                    <h2 class="text-h3 font-bold text-neutral-900">Kontrol Status Operasional Antrean</h2>
                    <p class="text-caption text-neutral-600">Atur status penerimaan antrean secara langsung. Status ini memengaruhi apakah pelanggan dapat mengambil tiket baru.</p>
                </div>

                <form method="POST" action="{{ route('merchant.settings.status') }}" class="space-y-4">
                    @csrf
                    @method('PATCH')

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        {{-- STATUS BUKA --}}
                        <label class="relative flex flex-col p-4 sm:p-5 border-2 rounded-xl cursor-pointer transition-all has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-brand-500 {{ $business->status === 'buka' ? 'border-emerald-600 bg-emerald-50/50 shadow-card' : 'border-neutral-200 bg-white hover:border-neutral-300' }}">
                            <div class="flex items-center justify-between mb-3">
                                <span class="inline-flex items-center gap-2 text-sm font-bold text-emerald-800">
                                    <span class="flex size-8 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></span>
                                    BUKA
                                </span>
                                <input
                                    type="radio"
                                    name="status"
                                    value="buka"
                                    @checked($business->status === 'buka')
                                    class="size-5 text-emerald-600 focus:ring-emerald-500"
                                    {{ $business->is_suspended ? 'disabled' : '' }}
                                />
                            </div>
                            <p class="text-xs text-neutral-600 leading-relaxed">
                                Antrean dibuka secara penuh. Pelanggan dapat mendaftar antrean online dan tiket datang langsung dapat diterbitkan.
                            </p>
                        </label>

                        {{-- STATUS JEDA SEMENTARA --}}
                        <label class="relative flex flex-col p-4 sm:p-5 border-2 rounded-xl cursor-pointer transition-all has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-brand-500 {{ $business->status === 'jeda' ? 'border-amber-600 bg-amber-50/50 shadow-card' : 'border-neutral-200 bg-white hover:border-neutral-300' }}">
                            <div class="flex items-center justify-between mb-3">
                                <span class="inline-flex items-center gap-2 text-sm font-bold text-amber-800">
                                    <span class="flex size-8 items-center justify-center rounded-lg bg-amber-100 text-amber-700"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></span>
                                    JEDA SEMENTARA
                                </span>
                                <input
                                    type="radio"
                                    name="status"
                                    value="jeda"
                                    @checked($business->status === 'jeda')
                                    class="size-5 text-amber-600 focus:ring-amber-500"
                                    {{ $business->is_suspended ? 'disabled' : '' }}
                                />
                            </div>
                            <p class="text-xs text-neutral-600 leading-relaxed">
                                Antrean online baru ditahan sementara (misal saat istirahat/padat). Tiket yang sudah terdaftar tetap dapat dipanggil dan diselesaikan.
                            </p>
                        </label>

                        {{-- STATUS TUTUP --}}
                        <label class="relative flex flex-col p-4 sm:p-5 border-2 rounded-xl cursor-pointer transition-all has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-brand-500 {{ $business->status === 'tutup' ? 'border-rose-600 bg-rose-50/50 shadow-card' : 'border-neutral-200 bg-white hover:border-neutral-300' }}">
                            <div class="flex items-center justify-between mb-3">
                                <span class="inline-flex items-center gap-2 text-sm font-bold text-rose-800">
                                    <span class="flex size-8 items-center justify-center rounded-lg bg-rose-100 text-rose-700"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><rect x="5" y="11" width="14" height="10" rx="2" stroke-width="2"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0v4"/></svg></span>
                                    TUTUP
                                </span>
                                <input
                                    type="radio"
                                    name="status"
                                    value="tutup"
                                    @checked($business->status === 'tutup')
                                    class="size-5 text-rose-600 focus:ring-rose-500"
                                    {{ $business->is_suspended ? 'disabled' : '' }}
                                />
                            </div>
                            <p class="text-xs text-neutral-600 leading-relaxed">
                                Operasional antrean ditutup. Tidak menerima tiket baru. Tiket aktif hari ini masih dapat diselesaikan oleh operator.
                            </p>
                        </label>
                    </div>

                    <div class="pt-4 flex justify-end border-t border-neutral-200">
                        <x-button type="submit" variant="primary" class="h-11 w-full px-6 sm:w-auto" :disabled="$business->is_suspended">
                            Terapkan Status Operasional
                        </x-button>
                    </div>
                </form>
            </x-card>
        @endif

        {{-- TAB CONTENT: PENGATURAN AKUN (FR-SET-04, FR-SET-05) --}}
        @if ($activeTab === 'account')
            <x-card padding="lg">
                <x-slot name="header">
                    <div>
                        <h2 class="text-h3 font-bold text-neutral-900">Pengaturan Akun Pengguna</h2>
                        <p class="text-caption text-neutral-600 mt-1">Perbarui nama pengguna, alamat email login, dan kata sandi pemilik usaha.</p>
                    </div>
                </x-slot>

                <form method="POST" action="{{ route('merchant.settings.account') }}" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <x-form-label for="name" value="Nama Lengkap Pemilik" required />
                            <x-input id="name" name="name" type="text" :value="old('name', auth()->user()->name)" required :error="$errors->has('name')" />
                            <x-input-error :messages="$errors->get('name')" class="mt-1" />
                        </div>

                        <div>
                            <x-form-label for="email" value="Alamat Email Login" required />
                            <x-input id="email" name="email" type="email" :value="old('email', auth()->user()->email)" required autocomplete="username" :error="$errors->has('email')" />
                            <x-input-error :messages="$errors->get('email')" class="mt-1" />
                        </div>
                    </div>

                    <div class="border-t border-neutral-200 pt-6">
                        <h3 class="text-h3 font-bold text-neutral-900">Ubah Password</h3>
                        <p class="text-caption text-neutral-600 mt-1">Kosongkan jika Anda tidak bermaksud mengubah kata sandi saat ini.</p>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mt-4">
                            <div>
                                <x-form-label for="current_password" value="Password Saat Ini" />
                                <x-password-input id="current_password" name="current_password" autocomplete="current-password" placeholder="Kata sandi saat ini" :error="$errors->has('current_password')" />
                                <x-input-error :messages="$errors->get('current_password')" class="mt-1" />
                            </div>

                            <div>
                                <x-form-label for="password" value="Password Baru" />
                                <x-password-input id="password" name="password" autocomplete="new-password" placeholder="Minimal 8 karakter" :error="$errors->has('password')" />
                                <x-input-error :messages="$errors->get('password')" class="mt-1" />
                            </div>

                            <div>
                                <x-form-label for="password_confirmation" value="Ulangi Password Baru" />
                                <x-password-input id="password_confirmation" name="password_confirmation" autocomplete="new-password" placeholder="Ulangi password baru" />
                                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1" />
                            </div>
                        </div>
                    </div>

                    <div class="border-t border-neutral-200 pt-6">
                        <h3 class="text-h3 font-bold text-neutral-900">Preferensi Notifikasi Antrean (FR-SET-05)</h3>
                        <p class="text-caption text-neutral-600 mt-1">Kanal pengiriman status antrean kepada pengguna dan pelanggan.</p>

                        <div class="space-y-3 mt-4">
                            <div class="flex flex-col gap-2 rounded-xl border border-neutral-200 bg-neutral-50 p-3.5 sm:flex-row sm:items-center sm:justify-between">
                                <div class="flex items-start gap-3">
                                    <span class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700"><x-customer.icon name="refresh" class="size-4" /></span>
                                    <div>
                                        <p class="text-sm font-semibold text-neutral-900">Web Polling Real-time (In-App)</p>
                                        <p class="text-xs text-neutral-500">Pembaruan berkala di halaman publik pelanggan dan console operasional.</p>
                                    </div>
                                </div>
                                <span class="w-fit shrink-0 rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-700">Aktif</span>
                            </div>

                            <div class="flex flex-col gap-2 rounded-xl border border-neutral-200 bg-neutral-50 p-3.5 opacity-70 sm:flex-row sm:items-center sm:justify-between">
                                <div class="flex items-start gap-3">
                                    <span class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-neutral-200 text-neutral-600"><x-customer.icon name="message" class="size-4" /></span>
                                    <div>
                                        <p class="text-sm font-semibold text-neutral-900">Notifikasi WhatsApp & SMS Gateway</p>
                                        <p class="text-xs text-neutral-500">Pengiriman pengingat antrean via WhatsApp Business API.</p>
                                    </div>
                                </div>
                                <span class="w-fit shrink-0 rounded-full bg-neutral-200 px-2.5 py-1 text-xs font-semibold text-neutral-600">Belum Didukung di MVP</span>
                            </div>
                        </div>
                    </div>

                    <div class="pt-4 flex justify-end border-t border-neutral-200">
                        <x-button type="submit" variant="primary" class="h-11 w-full px-6 sm:w-auto">
                            Simpan Perubahan Akun
                        </x-button>
                    </div>
                </form>
            </x-card>
        @endif

        {{-- TAB CONTENT: NOTIFIKASI --}}
        @if ($activeTab === 'notifications')
            <div class="space-y-6" x-data="merchantPushManager({
                vapidPublicKey: '{{ config('webpush.vapid.public_key') }}',
                subscribeUrl: '{{ route('merchant.settings.push.subscribe') }}',
                unsubscribeUrl: '{{ route('merchant.settings.push.unsubscribe') }}',
                csrfToken: '{{ csrf_token() }}'
            })" x-init="checkSubscription()">
                {{-- Notification Preferences Form --}}
                <x-card padding="lg">
                    <form method="POST" action="{{ route('merchant.settings.notifications.update') }}" class="space-y-6">
                        @csrf
                        @method('PUT')

                        <div>
                            <h2 class="text-h3 font-bold text-neutral-900">Preferensi Notifikasi Antrean</h2>
                            <p class="text-body text-neutral-600 mt-1">
                                Tentukan saluran pemberitahuan yang ingin Anda terima saat pelanggan mendaftar antrean online baru di bisnis Anda.
                            </p>
                        </div>

                        <div class="space-y-4 pt-2">
                            {{-- Email Notification Toggle --}}
                            <label class="flex items-start gap-3 p-4 rounded-xl border border-neutral-200 bg-neutral-50/50 hover:bg-neutral-50 cursor-pointer transition">
                                <input
                                    type="checkbox"
                                    name="email_on_new_online_ticket"
                                    value="1"
                                    {{ old('email_on_new_online_ticket', $notificationPreference?->email_on_new_online_ticket ?? true) ? 'checked' : '' }}
                                    class="mt-1 h-4 w-4 rounded border-neutral-300 text-brand-600 focus:ring-brand-500"
                                >
                                <div class="flex-1">
                                    <div class="text-sm font-bold text-neutral-900 flex items-center gap-2">
                                        <span>Notifikasi email untuk tiket baru dari ponsel</span>
                                        <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-brand-50 text-brand-700 border border-brand-200">Email</span>
                                    </div>
                                    <p class="text-xs text-neutral-600 mt-1">
                                        Kirimkan ringkasan instan ke alamat email akun Anda (<strong>{{ $user->email }}</strong>) setiap kali ada pelanggan yang mengambil tiket online.
                                    </p>
                                </div>
                            </label>

                            {{-- Browser Push Notification Toggle --}}
                            <label class="flex items-start gap-3 p-4 rounded-xl border border-neutral-200 bg-neutral-50/50 hover:bg-neutral-50 cursor-pointer transition">
                                <input
                                    type="checkbox"
                                    name="browser_on_new_online_ticket"
                                    value="1"
                                    {{ old('browser_on_new_online_ticket', $notificationPreference?->browser_on_new_online_ticket ?? true) ? 'checked' : '' }}
                                    class="mt-1 h-4 w-4 rounded border-neutral-300 text-brand-600 focus:ring-brand-500"
                                >
                                <div class="flex-1">
                                    <div class="text-sm font-bold text-neutral-900 flex items-center gap-2">
                                        <span>Web Push Notification (Browser / Desktop)</span>
                                        <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">Browser Push</span>
                                    </div>
                                    <p class="text-xs text-neutral-600 mt-1">
                                        Izinkan pengiriman pemberitahuan web push ke peramban aktif staf ketika tiket online baru diterbitkan oleh pelanggan.
                                    </p>
                                </div>
                            </label>
                        </div>

                        <div class="pt-4 flex justify-end border-t border-neutral-200">
                            <x-button type="submit" variant="primary" class="h-11 w-full px-6 sm:w-auto">
                                Simpan Preferensi
                            </x-button>
                        </div>
                    </form>
                </x-card>

                {{-- Device Push Subscription Management --}}
                {{-- Hidden until VAPID keys are configured on the server (Pre-M18 P0-03) --}}
                @if (! empty(config('webpush.vapid.public_key')))
                <x-card padding="lg">
                    <div class="space-y-4">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between sm:gap-4">
                            <div>
                                <h3 class="text-h3 font-bold text-neutral-900">Aktivasi Notifikasi di Peramban Ini</h3>
                                <p class="text-body text-neutral-600 mt-1">
                                    Daftarkan peramban/perangkat ini agar dapat menerima bunyi dan popup notifikasi tiket secara langsung meskipun jendela AntreIn sedang tertutup atau diminimalkan.
                                </p>
                            </div>
                            <div>
                                <template x-if="isSubscribed">
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                        <x-customer.icon name="check" class="size-3.5" />
                                        Peramban Terhubung
                                    </span>
                                </template>
                                <template x-if="!isSubscribed && isSupported">
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-neutral-100 text-neutral-700 border border-neutral-200">
                                        <x-customer.icon name="bell" class="size-3.5" />
                                        Belum Diaktifkan
                                    </span>
                                </template>
                            </div>
                        </div>

                        {{-- Unsupported Notice --}}
                        <div x-show="!isSupported" class="p-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-900 text-xs">
                            <p class="font-semibold">Peramban Anda belum mendukung Web Push Notifications.</p>
                            <p class="mt-0.5">Gunakan peramban modern seperti Google Chrome, Mozilla Firefox, Microsoft Edge, atau Safari 16.4+ untuk menerima notifikasi di latar belakang.</p>
                        </div>

                        {{-- Blocked Permission Notice --}}
                        <div x-show="permission === 'denied'" class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-900 text-xs">
                            <p class="font-semibold">Izin notifikasi diblokir oleh peramban.</p>
                            <p class="mt-0.5">Silakan buka pengaturan privasi / perizinan situs peramban Anda untuk mengizinkan AntreIn mengirimkan notifikasi.</p>
                        </div>

                        {{-- Action Controls --}}
                        <div x-show="isSupported && permission !== 'denied'" class="pt-2 flex flex-wrap items-center gap-3">
                            <button
                                type="button"
                                x-show="!isSubscribed"
                                @click="subscribe()"
                                :disabled="isLoading"
                                class="inline-flex h-11 w-full items-center justify-center gap-2 px-5 rounded-xl sm:w-auto bg-brand-700 hover:bg-brand-800 text-white font-semibold text-sm transition shadow-card disabled:opacity-50"
                            >
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                                <span x-text="isLoading ? 'Memproses...' : 'Aktifkan Notifikasi di Peramban Ini'"></span>
                            </button>

                            <button
                                type="button"
                                x-show="isSubscribed"
                                @click="unsubscribe()"
                                :disabled="isLoading"
                                class="inline-flex h-11 w-full items-center justify-center gap-2 px-5 rounded-xl sm:w-auto bg-neutral-100 hover:bg-neutral-200 text-neutral-800 font-semibold text-sm transition border border-neutral-300 disabled:opacity-50"
                            >
                                <svg class="w-4 h-4 text-neutral-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                <span x-text="isLoading ? 'Memproses...' : 'Nonaktifkan Notifikasi Peramban'"></span>
                            </button>

                            <span x-show="message" class="text-xs font-semibold" :class="isError ? 'text-rose-600' : 'text-emerald-700'" x-text="message"></span>
                        </div>
                    </div>
                </x-card>
                @else
                <x-card padding="lg">
                    <div class="p-4 rounded-xl bg-neutral-50 border border-neutral-200 text-xs text-neutral-600">
                        <p class="font-semibold text-neutral-800">Notifikasi Peramban Belum Tersedia</p>
                        <p class="mt-0.5">
                            Fitur web push belum dikonfigurasi pada server ini. Jalankan
                            <code class="px-1 py-0.5 rounded bg-neutral-200 text-neutral-700">php artisan webpush:vapid</code>
                            lalu atur <code>VAPID_PUBLIC_KEY</code> / <code>VAPID_PRIVATE_KEY</code> pada environment aplikasi untuk mengaktifkan notifikasi peramban. Preferensi email tetap berfungsi normal.
                        </p>
                    </div>
                </x-card>
                @endif
            </div>

            @push('scripts')
            <script>
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

                function merchantPushManager(config) {
                    return {
                        vapidPublicKey: config.vapidPublicKey,
                        vapidConfigured: (config.vapidPublicKey || '').length > 0,
                        subscribeUrl: config.subscribeUrl,
                        unsubscribeUrl: config.unsubscribeUrl,
                        csrfToken: config.csrfToken,
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
                                console.warn('Push check error:', e);
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
                                    this.message = 'Izin notifikasi tidak diberikan oleh pengguna.';
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
                                const response = await fetch(this.subscribeUrl, {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': this.csrfToken,
                                        'Accept': 'application/json',
                                        'X-Requested-With': 'XMLHttpRequest'
                                    },
                                    body: JSON.stringify({
                                        endpoint: subscription.endpoint,
                                        public_key: subJson.keys?.p256dh,
                                        auth_token: subJson.keys?.auth,
                                        content_encoding: (PushManager.supportedContentEncodings || ['aesgcm'])[0]
                                    })
                                });

                                const result = await response.json();
                                if (response.ok && result.success) {
                                    this.isSubscribed = true;
                                    this.message = 'Peramban berhasil terhubung dengan notifikasi push!';
                                } else {
                                    this.isError = true;
                                    this.message = result.message || 'Gagal menyimpan langganan notifikasi.';
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

                                if (subscription) {
                                    await fetch(this.unsubscribeUrl, {
                                        method: 'DELETE',
                                        headers: {
                                            'Content-Type': 'application/json',
                                            'X-CSRF-TOKEN': this.csrfToken,
                                            'Accept': 'application/json',
                                            'X-Requested-With': 'XMLHttpRequest'
                                        },
                                        body: JSON.stringify({
                                            endpoint: subscription.endpoint
                                        })
                                    });

                                    await subscription.unsubscribe();
                                }

                                this.isSubscribed = false;
                                this.message = 'Notifikasi di peramban ini telah dinonaktifkan.';
                            } catch (err) {
                                this.isError = true;
                                this.message = 'Gagal menonaktifkan notifikasi: ' + err.message;
                            } finally {
                                this.isLoading = false;
                            }
                        }
                    };
                }
            </script>
            @endpush
        @endif
    </div>
</x-layouts.merchant>

