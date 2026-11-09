<x-layouts.merchant title="Pengaturan Usaha — AntreIn">
    <div class="space-y-6">
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-h2 font-bold text-neutral-950">Pengaturan Usaha</h1>
                <p class="text-body text-neutral-600 mt-1">Kelola informasi publik, jadwal operasional mingguan, dan status penerimaan antrean.</p>
            </div>
            <!-- Quick Status Badge in Header -->
            <div>
                @if ($business->is_suspended)
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-rose-100 text-rose-800 border border-rose-300">
                        <span class="w-2 h-2 rounded-full bg-rose-600 animate-pulse"></span>
                        SUSPENDED
                    </span>
                @elseif ($business->status === 'buka')
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                        <span class="w-2 h-2 rounded-full bg-emerald-600"></span>
                        OPERASIONAL BUKA
                    </span>
                @elseif ($business->status === 'jeda')
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-300">
                        <span class="w-2 h-2 rounded-full bg-amber-600"></span>
                        JEDA SEMENTARA
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-neutral-100 text-neutral-700 border border-neutral-300">
                        <span class="w-2 h-2 rounded-full bg-neutral-400"></span>
                        OPERASIONAL TUTUP
                    </span>
                @endif
            </div>
        </div>

        <!-- Suspended Notice (If applicable) -->
        @if ($business->is_suspended)
            <div class="rounded-xl bg-rose-50 border border-rose-300 p-5 text-rose-900 flex items-start gap-3 shadow-xs">
                <svg class="w-6 h-6 text-rose-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                <div>
                    <h3 class="font-bold text-sm text-rose-950">Akun Usaha Sedang Dalam Masa Penangguhan (Suspended)</h3>
                    <p class="text-xs text-rose-800 mt-1">
                        Platform administrator telah menangguhkan akun usaha ini. Penerimaan tiket online maupun walk-in baru diblokir sepenuhnya. Anda tetap dapat menyelesaikan atau membatalkan tiket yang sedang aktif. Silakan hubungi tim dukungan jika membutuhkan klarifikasi lebih lanjut.
                    </p>
                </div>
            </div>
        @endif

        <!-- Notifications -->
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

        <!-- Navigation Tabs -->
        <div class="border-b border-neutral-200">
            <nav class="flex space-x-6">
                <a
                    href="{{ route('merchant.settings', ['tab' => 'profile']) }}"
                    class="pb-3 px-1 border-b-2 font-medium text-sm transition-colors {{ $activeTab === 'profile' ? 'border-brand-700 text-brand-700 font-bold' : 'border-transparent text-neutral-500 hover:text-neutral-700 hover:border-neutral-300' }}"
                >
                    Profil Usaha & Identitas
                </a>
                <a
                    href="{{ route('merchant.settings', ['tab' => 'hours']) }}"
                    class="pb-3 px-1 border-b-2 font-medium text-sm transition-colors {{ $activeTab === 'hours' ? 'border-brand-700 text-brand-700 font-bold' : 'border-transparent text-neutral-500 hover:text-neutral-700 hover:border-neutral-300' }}"
                >
                    Jam Operasional Mingguan
                </a>
                <a
                    href="{{ route('merchant.settings', ['tab' => 'status']) }}"
                    class="pb-3 px-1 border-b-2 font-medium text-sm transition-colors {{ $activeTab === 'status' ? 'border-brand-700 text-brand-700 font-bold' : 'border-transparent text-neutral-500 hover:text-neutral-700 hover:border-neutral-300' }}"
                >
                    Status Operasional Antrean
                </a>
                <a
                    href="{{ route('merchant.settings', ['tab' => 'account']) }}"
                    class="pb-3 px-1 border-b-2 font-medium text-sm transition-colors {{ $activeTab === 'account' ? 'border-brand-700 text-brand-700 font-bold' : 'border-transparent text-neutral-500 hover:text-neutral-700 hover:border-neutral-300' }}"
                >
                    Akun & Keamanan
                </a>
            </nav>
        </div>

        <!-- TAB CONTENT: PROFIL USAHA -->
        @if ($activeTab === 'profile')
            <x-card padding="lg">
                <form method="POST" action="{{ route('merchant.settings.profile') }}" enctype="multipart/form-data" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <!-- Logo Section -->
                    <div>
                        <x-form-label value="Logo Usaha" />
                        <div class="flex flex-col sm:flex-row sm:items-center gap-5 mt-2">
                            <div class="w-24 h-24 rounded-xl border border-neutral-300 bg-neutral-100 flex items-center justify-center overflow-hidden shrink-0 shadow-xs">
                                @if ($business->logo_path)
                                    <img src="{{ $business->getLogoUrl() }}" alt="Logo Usaha" class="w-full h-full object-cover">
                                @else
                                    <svg class="w-10 h-10 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                @endif
                            </div>

                            <div class="space-y-2 flex-1">
                                <input
                                    type="file"
                                    name="logo"
                                    id="logo"
                                    accept="image/jpeg,image/png,image/webp"
                                    class="block w-full text-xs text-neutral-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100"
                                />
                                <p class="text-caption text-neutral-500">Maksimal 2MB. Format: JPG, PNG, atau WebP. Gambar persegi disarankan.</p>
                            </div>

                            @if ($business->logo_path)
                                <div>
                                    <button
                                        type="button"
                                        onclick="if(confirm('Hapus logo usaha saat ini?')) document.getElementById('form-delete-logo').submit();"
                                        class="text-xs px-3 py-2 border border-rose-200 text-rose-700 rounded-lg bg-rose-50 hover:bg-rose-100 font-medium transition-colors"
                                    >
                                        Hapus Logo
                                    </button>
                                </div>
                            @endif
                        </div>
                    </div>

                    <hr class="border-neutral-200">

                    <!-- Basic Info -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <x-form-label for="name" value="Nama Usaha / Toko" :required="true" />
                            <x-input
                                id="name"
                                name="name"
                                type="text"
                                :value="old('name', $business->name)"
                                required
                                :invalid="$errors->has('name')"
                            />
                            <x-input-error :messages="$errors->get('name')" class="mt-1" />
                        </div>

                        <div>
                            <x-form-label for="category_id" value="Kategori Usaha" :required="true" />
                            <x-select id="category_id" name="category_id" required :invalid="$errors->has('category_id')">
                                <option value="">-- Pilih Kategori --</option>
                                @foreach ($categories as $cat)
                                    <option value="{{ $cat->id }}" @selected(old('category_id', $business->category_id) == $cat->id)>
                                        {{ $cat->name }} {{ !$cat->is_active ? '(Nonaktif)' : '' }}
                                    </option>
                                @endforeach
                            </x-select>
                            <x-input-error :messages="$errors->get('category_id')" class="mt-1" />
                        </div>
                    </div>

                    <!-- Slug URL Section -->
                    <div>
                        <x-form-label for="slug" value="Slug Publik Usaha (URL Publik)" :required="true" />
                        <div class="flex rounded-md shadow-xs mt-1">
                            <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-neutral-300 bg-neutral-50 text-neutral-500 text-sm font-mono">
                                {{ url('/q') }}/
                            </span>
                            <input
                                type="text"
                                name="slug"
                                id="slug"
                                value="{{ old('slug', $business->slug) }}"
                                required
                                class="flex-1 min-w-0 block w-full px-3 py-2 rounded-none rounded-r-md border border-neutral-300 text-sm font-mono focus:ring-brand-700 focus:border-brand-700"
                            />
                        </div>
                        <p class="text-caption text-neutral-500 mt-1">
                            Peringatan: Mengubah slug akan mengubah tautan antrean publik usaha Anda. QR code cetak lama perlu diperbarui jika slug diubah.
                        </p>
                        <x-input-error :messages="$errors->get('slug')" class="mt-1" />
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <x-form-label for="phone" value="Nomor Telepon / WhatsApp" :required="true" />
                            <x-input
                                id="phone"
                                name="phone"
                                type="text"
                                :value="old('phone', $business->phone)"
                                required
                                :invalid="$errors->has('phone')"
                            />
                            <x-input-error :messages="$errors->get('phone')" class="mt-1" />
                        </div>

                        <div>
                            <x-form-label for="timezone" value="Zona Waktu Operasional" :required="true" />
                            <x-select id="timezone" name="timezone" required :invalid="$errors->has('timezone')">
                                <option value="Asia/Makassar" @selected(old('timezone', $business->timezone) === 'Asia/Makassar')>
                                    WITA — Asia/Makassar (Default Pilot Bali)
                                </option>
                                <option value="Asia/Jakarta" @selected(old('timezone', $business->timezone) === 'Asia/Jakarta')>
                                    WIB — Asia/Jakarta
                                </option>
                                <option value="Asia/Jayapura" @selected(old('timezone', $business->timezone) === 'Asia/Jayapura')>
                                    WIT — Asia/Jayapura
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
                        <x-button type="submit" variant="primary" class="px-6 h-11">
                            Simpan Perubahan Profil
                        </x-button>
                    </div>
                </form>

                <!-- Hidden Logo Deletion Form -->
                <form id="form-delete-logo" method="POST" action="{{ route('merchant.settings.logo.remove') }}" class="hidden">
                    @csrf
                    @method('DELETE')
                </form>
            </x-card>

        <!-- TAB CONTENT: JAM OPERASIONAL -->
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

                    <div class="border border-neutral-200 rounded-lg divide-y divide-neutral-200 overflow-hidden">
                        @foreach ($days as $dow => $dayName)
                            @php
                                $existingHour = $hours->firstWhere('day_of_week', $dow);
                                $openTime = $existingHour ? substr($existingHour->open_time, 0, 5) : '09:00';
                                $closeTime = $existingHour ? substr($existingHour->close_time, 0, 5) : '21:00';
                                $isClosed = $existingHour ? (bool) $existingHour->is_closed : ($dow === 0);
                            @endphp
                            <div class="p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-white hover:bg-neutral-50/50">
                                <input type="hidden" name="hours[{{ $loop->index }}][day_of_week]" value="{{ $dow }}">
                                
                                <div class="w-32 font-semibold text-neutral-900">
                                    {{ $dayName }}
                                </div>

                                <div class="flex items-center gap-4">
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

                                    <label class="inline-flex items-center gap-2 text-xs font-medium text-neutral-700 cursor-pointer ml-3">
                                        <input
                                            type="checkbox"
                                            name="hours[{{ $loop->index }}][is_closed]"
                                            value="1"
                                            @checked(old("hours.{$loop->index}.is_closed", $isClosed))
                                            class="rounded border-neutral-300 text-brand-700 focus:ring-brand-700"
                                        />
                                        Tutup / Libur
                                    </label>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="pt-4 flex justify-end border-t border-neutral-200">
                        <x-button type="submit" variant="primary" class="px-6 h-11">
                            Simpan Jadwal Operasional
                        </x-button>
                    </div>
                </form>
            </x-card>

        <!-- TAB CONTENT: STATUS OPERASIONAL -->
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
                        <!-- STATUS BUKA -->
                        <label class="relative flex flex-col p-5 border-2 rounded-xl cursor-pointer transition-all {{ $business->status === 'buka' ? 'border-emerald-600 bg-emerald-50/50 shadow-xs' : 'border-neutral-200 bg-white hover:border-neutral-300' }}">
                            <div class="flex items-center justify-between mb-3">
                                <span class="inline-flex items-center gap-1.5 text-sm font-bold text-emerald-800">
                                    <span class="w-3 h-3 rounded-full bg-emerald-600"></span>
                                    BUKA
                                </span>
                                <input
                                    type="radio"
                                    name="status"
                                    value="buka"
                                    @checked($business->status === 'buka')
                                    class="text-emerald-600 focus:ring-emerald-500"
                                    {{ $business->is_suspended ? 'disabled' : '' }}
                                />
                            </div>
                            <p class="text-xs text-neutral-600 leading-relaxed">
                                Antrean dibuka secara penuh. Pelanggan dapat mendaftar antrean online dan tiket walk-in dapat diterbitkan.
                            </p>
                        </label>

                        <!-- STATUS JEDA SEMENTARA -->
                        <label class="relative flex flex-col p-5 border-2 rounded-xl cursor-pointer transition-all {{ $business->status === 'jeda' ? 'border-amber-600 bg-amber-50/50 shadow-xs' : 'border-neutral-200 bg-white hover:border-neutral-300' }}">
                            <div class="flex items-center justify-between mb-3">
                                <span class="inline-flex items-center gap-1.5 text-sm font-bold text-amber-800">
                                    <span class="w-3 h-3 rounded-full bg-amber-600"></span>
                                    JEDA SEMENTARA
                                </span>
                                <input
                                    type="radio"
                                    name="status"
                                    value="jeda"
                                    @checked($business->status === 'jeda')
                                    class="text-amber-600 focus:ring-amber-500"
                                    {{ $business->is_suspended ? 'disabled' : '' }}
                                />
                            </div>
                            <p class="text-xs text-neutral-600 leading-relaxed">
                                Antrean online baru ditahan sementara (misal saat istirahat/padat). Tiket yang sudah terdaftar tetap dapat dipanggil dan diselesaikan.
                            </p>
                        </label>

                        <!-- STATUS TUTUP -->
                        <label class="relative flex flex-col p-5 border-2 rounded-xl cursor-pointer transition-all {{ $business->status === 'tutup' ? 'border-rose-600 bg-rose-50/50 shadow-xs' : 'border-neutral-200 bg-white hover:border-neutral-300' }}">
                            <div class="flex items-center justify-between mb-3">
                                <span class="inline-flex items-center gap-1.5 text-sm font-bold text-rose-800">
                                    <span class="w-3 h-3 rounded-full bg-rose-600"></span>
                                    TUTUP
                                </span>
                                <input
                                    type="radio"
                                    name="status"
                                    value="tutup"
                                    @checked($business->status === 'tutup')
                                    class="text-rose-600 focus:ring-rose-500"
                                    {{ $business->is_suspended ? 'disabled' : '' }}
                                />
                            </div>
                            <p class="text-xs text-neutral-600 leading-relaxed">
                                Operasional antrean ditutup. Tidak menerima tiket baru. Tiket aktif hari ini masih dapat diselesaikan oleh operator.
                            </p>
                        </label>
                    </div>

                    <div class="pt-4 flex justify-end border-t border-neutral-200">
                        <x-button type="submit" variant="primary" class="px-6 h-11" :disabled="$business->is_suspended">
                            Terapkan Status Operasional
                        </x-button>
                    </div>
                </form>
            </x-card>
        @endif

        <!-- TAB CONTENT: PENGATURAN AKUN (FR-SET-04, FR-SET-05) -->
        @if ($activeTab === 'account')
            <x-card padding="lg">
                <x-slot name="header">
                    <div>
                        <h2 class="text-h3 font-bold text-neutral-900">Pengaturan Akun Pengguna</h2>
                        <p class="text-caption text-neutral-500 mt-1">Perbarui nama pengguna, alamat email login, dan kata sandi pemilik usaha.</p>
                    </div>
                </x-slot>

                <form method="POST" action="{{ route('merchant.settings.account') }}" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <x-form-label for="name" value="Nama Lengkap Pemilik" required />
                            <x-input id="name" name="name" type="text" :value="old('name', auth()->user()->name)" required class="mt-1.5" />
                            <x-input-error :messages="$errors->get('name')" class="mt-1" />
                        </div>

                        <div>
                            <x-form-label for="email" value="Alamat Email Login" required />
                            <x-input id="email" name="email" type="email" :value="old('email', auth()->user()->email)" required class="mt-1.5" />
                            <x-input-error :messages="$errors->get('email')" class="mt-1" />
                        </div>
                    </div>

                    <div class="border-t border-neutral-200 pt-6">
                        <h3 class="text-h3 font-bold text-neutral-900">Ubah Password</h3>
                        <p class="text-caption text-neutral-500 mt-1">Kosongkan jika Anda tidak bermaksud mengubah kata sandi saat ini.</p>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 mt-4">
                            <div>
                                <x-form-label for="current_password" value="Password Saat Ini" />
                                <x-input id="current_password" name="current_password" type="password" placeholder="••••••••" class="mt-1.5" />
                                <x-input-error :messages="$errors->get('current_password')" class="mt-1" />
                            </div>

                            <div>
                                <x-form-label for="password" value="Password Baru" />
                                <x-input id="password" name="password" type="password" placeholder="Minimal 8 karakter" class="mt-1.5" />
                                <x-input-error :messages="$errors->get('password')" class="mt-1" />
                            </div>

                            <div>
                                <x-form-label for="password_confirmation" value="Ulangi Password Baru" />
                                <x-input id="password_confirmation" name="password_confirmation" type="password" placeholder="••••••••" class="mt-1.5" />
                                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1" />
                            </div>
                        </div>
                    </div>

                    <div class="border-t border-neutral-200 pt-6">
                        <h3 class="text-h3 font-bold text-neutral-900">Preferensi Notifikasi Antrean (FR-SET-05)</h3>
                        <p class="text-caption text-neutral-500 mt-1">Kanal pengiriman status antrean kepada pengguna dan pelanggan.</p>

                        <div class="space-y-3 mt-4">
                            <div class="p-3.5 bg-neutral-50 rounded-xl border border-neutral-200 flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                                    <div>
                                        <p class="text-sm font-semibold text-neutral-900">Web Polling Real-time (In-App)</p>
                                        <p class="text-xs text-neutral-500">Pembaruan berkala di halaman publik pelanggan dan console operasional.</p>
                                    </div>
                                </div>
                                <span class="text-xs font-bold text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded-full border border-emerald-200">Aktif</span>
                            </div>

                            <div class="p-3.5 bg-neutral-50 rounded-xl border border-neutral-200 flex items-center justify-between opacity-60">
                                <div class="flex items-center gap-3">
                                    <span class="w-2.5 h-2.5 rounded-full bg-neutral-400"></span>
                                    <div>
                                        <p class="text-sm font-semibold text-neutral-900">Notifikasi WhatsApp & SMS Gateway</p>
                                        <p class="text-xs text-neutral-500">Pengiriman pengingat antrean via WhatsApp Business API.</p>
                                    </div>
                                </div>
                                <span class="text-xs font-semibold text-neutral-500 bg-neutral-200 px-2.5 py-1 rounded-full">Belum Didukung di MVP</span>
                            </div>
                        </div>
                    </div>

                    <div class="pt-4 flex justify-end border-t border-neutral-200">
                        <x-button type="submit" variant="primary" class="px-6 h-11">
                            Simpan Perubahan Akun
                        </x-button>
                    </div>
                </form>
            </x-card>
        @endif
    </div>
</x-layouts.merchant>

