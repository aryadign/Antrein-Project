<x-layouts.merchant
    :title="$isEdit ? 'Ubah Cabang — ' . $branch->name : 'Tambah Cabang Baru'"
    :heading="$isEdit ? 'Ubah Cabang: ' . $branch->name : 'Tambah Cabang Baru'"
    :description="$isEdit ? 'Perbarui informasi dan lokasi cabang usaha' : 'Buka cabang baru di bawah naungan organisasi ' . ($organization->name ?? 'Anda')"
>
    <div class="max-w-3xl space-y-6">
        {{-- Flash Status Message --}}
        @if (session('status'))
            <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm font-medium">
                {{ session('status') }}
            </div>
        @endif

        {{-- Error Summary Alert --}}
        @if ($errors->any())
            <div class="p-4 rounded-xl bg-red-50 border border-red-200 text-red-800 text-sm font-medium space-y-1">
                <div class="font-bold">Terjadi kesalahan pada input data:</div>
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="bg-white rounded-2xl border border-neutral-200 shadow-xs overflow-hidden">
            <form method="POST" action="{{ $isEdit ? route('merchant.branches.update', $branch) : route('merchant.branches.store') }}" class="p-6 sm:p-8 space-y-6">
                @csrf
                @if ($isEdit)
                    @method('PUT')
                @endif

                {{-- Informational Notice for New Branch Defaults --}}
                @if (! $isEdit && $sourceBranch)
                    <div class="p-4 rounded-xl bg-brand-50 border border-brand-200 text-brand-900 text-xs leading-relaxed flex items-start gap-3">
                        <svg class="w-5 h-5 text-brand-700 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <div>
                            <span class="font-bold">Salin Konfigurasi Otomatis:</span>
                            Jam operasional dan daftar layanan dari cabang rujukan (<span class="font-semibold">{{ $sourceBranch->name }}</span>) akan disalin secara otomatis ke cabang baru ini agar langsung siap digunakan. Anda dapat menyesuaikannya kapan saja setelah cabang dibuat.
                        </div>
                    </div>
                @endif

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    {{-- Branch Name --}}
                    <div class="sm:col-span-2">
                        <label for="name" class="block text-xs font-bold uppercase tracking-wider text-neutral-700 mb-1.5">
                            Nama Cabang <span class="text-danger-600">*</span>
                        </label>
                        <input
                            type="text"
                            id="name"
                            name="name"
                            value="{{ old('name', $branch->name) }}"
                            required
                            placeholder="Contoh: Barbershop Garasi - Cabang Antang"
                            class="w-full px-4 py-2.5 rounded-xl border border-neutral-300 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 text-sm text-neutral-900 placeholder:text-neutral-400 transition"
                        >
                    </div>

                    {{-- Public Slug (Optional) --}}
                    <div class="sm:col-span-2">
                        <label for="slug" class="block text-xs font-bold uppercase tracking-wider text-neutral-700 mb-1.5">
                            Slug URL Publik (Opsional)
                        </label>
                        <div class="flex rounded-xl shadow-xs">
                            <span class="inline-flex items-center px-4 rounded-l-xl border border-r-0 border-neutral-300 bg-neutral-50 text-neutral-500 text-xs">
                                /q/
                            </span>
                            <input
                                type="text"
                                id="slug"
                                name="slug"
                                value="{{ old('slug', $branch->slug) }}"
                                placeholder="barbershop-garasi-antang"
                                class="flex-1 min-w-0 block w-full px-4 py-2.5 rounded-none rounded-r-xl border border-neutral-300 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 text-sm text-neutral-900 placeholder:text-neutral-400 transition"
                            >
                        </div>
                        <p class="text-[11px] text-neutral-400 mt-1">Kosongkan jika ingin dibuatkan secara otomatis dari nama cabang.</p>
                    </div>

                    {{-- Category --}}
                    <div>
                        <label for="category_id" class="block text-xs font-bold uppercase tracking-wider text-neutral-700 mb-1.5">
                            Kategori Usaha <span class="text-danger-600">*</span>
                        </label>
                        <select
                            id="category_id"
                            name="category_id"
                            required
                            class="w-full px-4 py-2.5 rounded-xl border border-neutral-300 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 text-sm text-neutral-900 transition bg-white"
                        >
                            <option value="">-- Pilih Kategori --</option>
                            @foreach ($categories as $cat)
                                <option value="{{ $cat->id }}" {{ (string) old('category_id', $branch->category_id) === (string) $cat->id ? 'selected' : '' }}>
                                    {{ $cat->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Timezone --}}
                    <div>
                        <label for="timezone" class="block text-xs font-bold uppercase tracking-wider text-neutral-700 mb-1.5">
                            Zona Waktu <span class="text-danger-600">*</span>
                        </label>
                        <select
                            id="timezone"
                            name="timezone"
                            required
                            class="w-full px-4 py-2.5 rounded-xl border border-neutral-300 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 text-sm text-neutral-900 transition bg-white"
                        >
                            @php
                                $currentTimezone = old('timezone', $branch->timezone ?: ($organization->timezone ?? 'Asia/Makassar'));
                            @endphp
                            <option value="Asia/Jakarta" {{ $currentTimezone === 'Asia/Jakarta' ? 'selected' : '' }}>WIB — Asia/Jakarta (UTC+7)</option>
                            <option value="Asia/Makassar" {{ $currentTimezone === 'Asia/Makassar' ? 'selected' : '' }}>WITA — Asia/Makassar (UTC+8)</option>
                            <option value="Asia/Jayapura" {{ $currentTimezone === 'Asia/Jayapura' ? 'selected' : '' }}>WIT — Asia/Jayapura (UTC+9)</option>
                        </select>
                    </div>

                    {{-- Phone --}}
                    <div class="sm:col-span-2">
                        <label for="phone" class="block text-xs font-bold uppercase tracking-wider text-neutral-700 mb-1.5">
                            Nomor Telepon / WhatsApp <span class="text-danger-600">*</span>
                        </label>
                        <input
                            type="text"
                            id="phone"
                            name="phone"
                            value="{{ old('phone', $branch->phone) }}"
                            required
                            placeholder="081234567890"
                            class="w-full px-4 py-2.5 rounded-xl border border-neutral-300 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 text-sm text-neutral-900 placeholder:text-neutral-400 transition"
                        >
                    </div>

                    {{-- Address --}}
                    <div class="sm:col-span-2">
                        <label for="address" class="block text-xs font-bold uppercase tracking-wider text-neutral-700 mb-1.5">
                            Alamat Lengkap Cabang <span class="text-danger-600">*</span>
                        </label>
                        <textarea
                            id="address"
                            name="address"
                            rows="2"
                            required
                            placeholder="Jl. Perintis Kemerdekaan KM 9, Tamalanrea, Makassar"
                            class="w-full px-4 py-2.5 rounded-xl border border-neutral-300 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 text-sm text-neutral-900 placeholder:text-neutral-400 transition"
                        >{{ old('address', $branch->address) }}</textarea>
                    </div>

                    {{-- Description --}}
                    <div class="sm:col-span-2">
                        <label for="description" class="block text-xs font-bold uppercase tracking-wider text-neutral-700 mb-1.5">
                            Deskripsi Singkat (Opsional)
                        </label>
                        <textarea
                            id="description"
                            name="description"
                            rows="2"
                            placeholder="Keterangan lokasi, petunjuk arah, atau fasilitas khusus cabang"
                            class="w-full px-4 py-2.5 rounded-xl border border-neutral-300 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 text-sm text-neutral-900 placeholder:text-neutral-400 transition"
                        >{{ old('description', $branch->description) }}</textarea>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="flex items-center justify-end gap-3 pt-6 border-t border-neutral-200">
                    <a href="{{ route('merchant.branches.index') }}" class="px-5 py-2.5 text-xs font-bold text-neutral-700 hover:text-neutral-950 transition">
                        Batal
                    </a>
                    <button type="submit" class="inline-flex items-center justify-center px-6 py-2.5 rounded-xl text-xs font-bold text-white bg-brand-700 hover:bg-brand-800 shadow-md shadow-brand-700/20 transition">
                        {{ $isEdit ? 'Simpan Perubahan Cabang' : 'Buat Cabang Sekarang' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.merchant>

