<x-layouts.merchant
    title="Pengaturan Organisasi"
    heading="Pengaturan Organisasi & Brand"
    description="Kelola profil payung usaha (brand) yang menaungi seluruh cabang operasional"
>
    <div class="max-w-4xl space-y-6">
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

        {{-- Header Navigation Tabs --}}
        <x-merchant.organization-tabs active="profile" :branches-count="$branchesCount" />

        {{-- Brand Overview Stats --}}
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
            <x-stat-tile label="Total Cabang" :value="$branchesCount" hint="Cabang dalam satu brand" />
            <x-stat-tile label="Cabang Aktif" :value="$activeBranchesCount" hint="Menerima antrean hari ini" tone="success" />
            <div class="col-span-2 min-w-0 rounded-xl border border-neutral-200 bg-white p-3.5 shadow-xs sm:col-span-1 sm:p-4">
                <p class="truncate text-caption font-semibold text-neutral-500">Cabang Aktif Sesi Ini</p>
                <p class="mt-1 truncate text-base font-bold text-brand-700 sm:text-lg">{{ $activeBranch?->name ?? 'Belum dipilih' }}</p>
                <p class="mt-0.5 truncate text-[11px] text-neutral-500">Konteks dashboard operasional</p>
            </div>
        </div>

        {{-- Organization Settings Form --}}
        <div class="bg-white rounded-2xl border border-neutral-200 shadow-xs overflow-hidden">
            <form method="POST" action="{{ route('merchant.organization.update') }}" class="space-y-6 p-4 sm:p-8">
                @csrf
                @method('PUT')

                <div class="space-y-4">
                    {{-- Organization Name --}}
                    <div>
                        <label for="name" class="block text-xs font-bold uppercase tracking-wider text-neutral-700 mb-1.5">
                            Nama Organisasi / Brand Utama <span class="text-danger-600">*</span>
                        </label>
                        <input
                            type="text"
                            id="name"
                            name="name"
                            value="{{ old('name', $organization->name) }}"
                            required
                            placeholder="Contoh: Garasi Barbershop Group"
                            class="min-h-11 w-full px-4 py-2.5 rounded-xl border border-neutral-300 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 text-sm text-neutral-900 placeholder:text-neutral-400 transition"
                        >
                    </div>

                    {{-- Timezone --}}
                    <div>
                        <label for="timezone" class="block text-xs font-bold uppercase tracking-wider text-neutral-700 mb-1.5">
                            Zona Waktu Default Organisasi <span class="text-danger-600">*</span>
                        </label>
                        <select
                            id="timezone"
                            name="timezone"
                            required
                            class="min-h-11 w-full px-4 py-2.5 rounded-xl border border-neutral-300 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 text-sm text-neutral-900 transition bg-white"
                        >
                            @php
                                $tz = old('timezone', $organization->timezone ?: 'Asia/Makassar');
                            @endphp
                            <option value="Asia/Jakarta" {{ $tz === 'Asia/Jakarta' ? 'selected' : '' }}>WIB — Asia/Jakarta (UTC+7)</option>
                            <option value="Asia/Makassar" {{ $tz === 'Asia/Makassar' ? 'selected' : '' }}>WITA — Asia/Makassar (UTC+8)</option>
                            <option value="Asia/Jayapura" {{ $tz === 'Asia/Jayapura' ? 'selected' : '' }}>WIT — Asia/Jayapura (UTC+9)</option>
                        </select>
                        <p class="text-[11px] text-neutral-400 mt-1">Digunakan sebagai acuan waktu default untuk pembuatan cabang baru dan laporan agregat.</p>
                    </div>

                    {{-- Description --}}
                    <div>
                        <label for="description" class="block text-xs font-bold uppercase tracking-wider text-neutral-700 mb-1.5">
                            Deskripsi / Visi Usaha (Opsional)
                        </label>
                        <textarea
                            id="description"
                            name="description"
                            rows="3"
                            placeholder="Deskripsi singkat mengenai grup atau jaringan usaha Anda"
                            class="min-h-11 w-full px-4 py-2.5 rounded-xl border border-neutral-300 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 text-sm text-neutral-900 placeholder:text-neutral-400 transition"
                        >{{ old('description', $organization->description) }}</textarea>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="flex flex-col-reverse gap-2 border-t border-neutral-200 pt-6 sm:flex-row sm:items-center sm:justify-end sm:gap-3">
                    <button type="submit" class="inline-flex h-11 items-center justify-center px-6 rounded-xl text-sm font-bold text-white bg-brand-700 hover:bg-brand-800 shadow-md shadow-brand-700/20 transition">
                        Simpan Perubahan Organisasi
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.merchant>

