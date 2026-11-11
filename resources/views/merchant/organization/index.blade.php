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
        <div class="flex items-center gap-2 border-b border-neutral-200 pb-3">
            <a href="{{ route('merchant.organization.edit') }}" class="px-4 py-2 rounded-xl text-xs font-bold bg-brand-50 text-brand-700 border border-brand-200">
                Profil Brand & Organisasi
            </a>
            <a href="{{ route('merchant.organization.reports') }}" class="px-4 py-2 rounded-xl text-xs font-semibold text-neutral-600 hover:text-neutral-950 hover:bg-neutral-100 transition">
                Laporan Agregat Organisasi
            </a>
            <a href="{{ route('merchant.branches.index') }}" class="px-4 py-2 rounded-xl text-xs font-semibold text-neutral-600 hover:text-neutral-950 hover:bg-neutral-100 transition">
                Daftar Cabang ({{ $branchesCount }})
            </a>
        </div>

        {{-- Brand Overview Stats --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="p-5 rounded-2xl bg-white border border-neutral-200 shadow-xs">
                <span class="text-xs font-semibold uppercase tracking-wider text-neutral-400">Total Cabang</span>
                <div class="mt-2 text-2xl font-black text-neutral-900 tabular-nums">{{ $branchesCount }}</div>
                <p class="text-[11px] text-neutral-500 mt-0.5">Cabang dalam satu brand</p>
            </div>
            <div class="p-5 rounded-2xl bg-white border border-neutral-200 shadow-xs">
                <span class="text-xs font-semibold uppercase tracking-wider text-neutral-400">Cabang Aktif</span>
                <div class="mt-2 text-2xl font-black text-emerald-700 tabular-nums">{{ $activeBranchesCount }}</div>
                <p class="text-[11px] text-neutral-500 mt-0.5">Menerima antrean hari ini</p>
            </div>
            <div class="p-5 rounded-2xl bg-white border border-neutral-200 shadow-xs">
                <span class="text-xs font-semibold uppercase tracking-wider text-neutral-400">Cabang Aktif Sesi Ini</span>
                <div class="mt-2 text-base font-bold text-brand-700 truncate">{{ $activeBranch?->name ?? 'Belum dipilih' }}</div>
                <p class="text-[11px] text-neutral-500 mt-0.5">Konteks dashboard operasional</p>
            </div>
        </div>

        {{-- Organization Settings Form --}}
        <div class="bg-white rounded-2xl border border-neutral-200 shadow-xs overflow-hidden">
            <form method="POST" action="{{ route('merchant.organization.update') }}" class="p-6 sm:p-8 space-y-6">
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
                            class="w-full px-4 py-2.5 rounded-xl border border-neutral-300 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 text-sm text-neutral-900 placeholder:text-neutral-400 transition"
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
                            class="w-full px-4 py-2.5 rounded-xl border border-neutral-300 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 text-sm text-neutral-900 transition bg-white"
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
                            class="w-full px-4 py-2.5 rounded-xl border border-neutral-300 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 text-sm text-neutral-900 placeholder:text-neutral-400 transition"
                        >{{ old('description', $organization->description) }}</textarea>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="flex items-center justify-end gap-3 pt-6 border-t border-neutral-200">
                    <button type="submit" class="inline-flex items-center justify-center px-6 py-2.5 rounded-xl text-xs font-bold text-white bg-brand-700 hover:bg-brand-800 shadow-md shadow-brand-700/20 transition">
                        Simpan Perubahan Organisasi
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.merchant>

