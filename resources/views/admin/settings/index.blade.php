<x-layouts.admin 
    title="Pengaturan Platform" 
    heading="Pengaturan Identitas Platform" 
    description="Kelola konfigurasi global AntreIn, identitas branding, informasi kontak bantuan, dan zona waktu standar."
>
    <div class="space-y-6 max-w-4xl" x-data="{ logoPreview: '{{ $settings['logo_url'] ?? '' }}' }">
        {{-- Flash Messages --}}
        @if (session('status'))
            <div class="rounded-xl bg-emerald-50 border border-emerald-200 p-4 text-sm font-medium text-emerald-800 flex items-center justify-between shadow-xs">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <span>{{ session('status') }}</span>
                </div>
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-xl bg-rose-50 border border-rose-200 p-4 text-sm text-rose-800 shadow-xs">
                <p class="font-semibold mb-1">Terjadi kesalahan validasi pada data pengaturan:</p>
                <ul class="list-disc list-inside space-y-0.5 text-xs">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @method('PUT')

            {{-- Identity & Branding Card (M8-27) --}}
            <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden shadow-xs">
                <div class="p-5 border-b border-neutral-200">
                    <h3 class="text-h3 font-bold text-neutral-900">Identitas Platform & Branding</h3>
                    <p class="text-caption text-neutral-500 mt-0.5">Nama layanan dan logo resmi yang tampil pada header publik dan aplikasi.</p>
                </div>

                <div class="p-6 space-y-5">
                    {{-- Logo Upload (M8-29) --}}
                    <div>
                        <label class="block text-caption font-semibold text-neutral-700 mb-2">
                            Logo Resmi Platform
                        </label>
                        <div class="flex flex-col sm:flex-row sm:items-center gap-5">
                            <div class="w-24 h-24 rounded-2xl bg-neutral-100 border border-neutral-200 flex items-center justify-center overflow-hidden shrink-0 shadow-inner">
                                <template x-if="logoPreview">
                                    <img :src="logoPreview" alt="Logo Preview" class="w-full h-full object-contain p-2">
                                </template>
                                <template x-if="!logoPreview">
                                    <div class="text-center p-2">
                                        <svg class="w-8 h-8 text-neutral-400 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        <span class="text-[10px] text-neutral-500 mt-1 block">Belum ada logo</span>
                                    </div>
                                </template>
                            </div>

                            <div class="flex-1 space-y-2">
                                <input 
                                    type="file" 
                                    name="logo" 
                                    id="logo" 
                                    accept="image/png,image/jpeg,image/webp,image/svg+xml"
                                    class="block w-full text-xs text-neutral-600 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-neutral-100 file:text-neutral-800 hover:file:bg-neutral-200 cursor-pointer"
                                    @change="
                                        const file = $event.target.files[0];
                                        if (file) {
                                            const reader = new FileReader();
                                            reader.onload = (e) => { logoPreview = e.target.result; };
                                            reader.readAsDataURL(file);
                                        }
                                    "
                                >
                                <p class="text-[11px] text-neutral-400">
                                    Format: PNG, JPG, WebP, atau SVG. Ukuran maksimal 2MB. Resolusi rasio 1:1 atau lanskap disarankan.
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Platform Name --}}
                    <div>
                        <label for="platform_name" class="block text-caption font-semibold text-neutral-700 mb-1">
                            Nama Platform <span class="text-rose-500">*</span>
                        </label>
                        <input 
                            type="text" 
                            name="platform_name" 
                            id="platform_name" 
                            required 
                            value="{{ old('platform_name', $settings['platform_name']) }}" 
                            class="w-full rounded-lg border-neutral-300 text-sm focus:border-brand-500 focus:ring-brand-500"
                        >
                    </div>

                    {{-- Platform Tagline --}}
                    <div>
                        <label for="platform_tagline" class="block text-caption font-semibold text-neutral-700 mb-1">
                            Slogan (Tagline) Platform
                        </label>
                        <input 
                            type="text" 
                            name="platform_tagline" 
                            id="platform_tagline" 
                            value="{{ old('platform_tagline', $settings['platform_tagline']) }}" 
                            placeholder="Contoh: Solusi Antrean Digital UMKM Terpadu" 
                            class="w-full rounded-lg border-neutral-300 text-sm focus:border-brand-500 focus:ring-brand-500"
                        >
                    </div>
                </div>
            </div>

            {{-- Contact Information & System Defaults Card (M8-28) --}}
            <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden shadow-xs">
                <div class="p-5 border-b border-neutral-200">
                    <h3 class="text-h3 font-bold text-neutral-900">Kontak Bantuan & Standar Wilayah</h3>
                    <p class="text-caption text-neutral-500 mt-0.5">Informasi saluran bantuan resmi dan konfigurasi zona waktu default aplikasi.</p>
                </div>

                <div class="p-6 space-y-5">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        {{-- Contact Email --}}
                        <div>
                            <label for="contact_email" class="block text-caption font-semibold text-neutral-700 mb-1">
                                Email Layanan Pelanggan <span class="text-rose-500">*</span>
                            </label>
                            <input 
                                type="email" 
                                name="contact_email" 
                                id="contact_email" 
                                required 
                                value="{{ old('contact_email', $settings['contact_email']) }}" 
                                class="w-full rounded-lg border-neutral-300 text-sm focus:border-brand-500 focus:ring-brand-500"
                            >
                        </div>

                        {{-- Contact Phone --}}
                        <div>
                            <label for="contact_phone" class="block text-caption font-semibold text-neutral-700 mb-1">
                                Nomor Telepon / WhatsApp Bantuan <span class="text-rose-500">*</span>
                            </label>
                            <input 
                                type="text" 
                                name="contact_phone" 
                                id="contact_phone" 
                                required 
                                value="{{ old('contact_phone', $settings['contact_phone']) }}" 
                                class="w-full rounded-lg border-neutral-300 text-sm focus:border-brand-500 focus:ring-brand-500"
                            >
                        </div>
                    </div>

                    {{-- Default Timezone --}}
                    <div>
                        <label for="default_timezone" class="block text-caption font-semibold text-neutral-700 mb-1">
                            Zona Waktu Baku Platform <span class="text-rose-500">*</span>
                        </label>
                        <select 
                            name="default_timezone" 
                            id="default_timezone" 
                            required 
                            class="w-full sm:w-80 rounded-lg border-neutral-300 text-sm focus:border-brand-500 focus:ring-brand-500"
                        >
                            <option value="Asia/Makassar" {{ old('default_timezone', $settings['default_timezone']) === 'Asia/Makassar' ? 'selected' : '' }}>
                                Asia/Makassar (WITA, UTC+8) — Default Pilot Bali
                            </option>
                            <option value="Asia/Jakarta" {{ old('default_timezone', $settings['default_timezone']) === 'Asia/Jakarta' ? 'selected' : '' }}>
                                Asia/Jakarta (WIB, UTC+7)
                            </option>
                            <option value="Asia/Jayapura" {{ old('default_timezone', $settings['default_timezone']) === 'Asia/Jayapura' ? 'selected' : '' }}>
                                Asia/Jayapura (WIT, UTC+9)
                            </option>
                        </select>
                        <p class="text-[11px] text-neutral-400 mt-1">
                            Zona waktu ini dijadikan acuan dasar bagi pendaftaran gerai UMKM baru sebelum diatur secara spesifik per UMKM (OQ-05).
                        </p>
                    </div>
                </div>

                <div class="bg-neutral-50 px-6 py-4 border-t border-neutral-200 flex justify-end">
                    <button 
                        type="submit" 
                        class="px-5 py-2.5 rounded-lg bg-neutral-900 text-white text-xs font-bold hover:bg-neutral-800 transition shadow-xs flex items-center gap-2"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Simpan Perubahan Pengaturan
                    </button>
                </div>
            </div>
        </form>
    </div>
</x-layouts.admin>

