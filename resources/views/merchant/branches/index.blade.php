<x-layouts.merchant
    title="Manajemen Cabang"
    heading="Manajemen Cabang"
    description="Kelola seluruh unit operasional dan cabang usaha dalam organisasi Anda"
>
    <div class="space-y-5 sm:space-y-6">
        {{-- Flash Status Message --}}
        @if (session('status'))
            <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm font-medium flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>{{ session('status') }}</span>
                </div>
                <button type="button" onclick="this.parentElement.remove()" class="-my-2 -mr-2 inline-flex size-11 shrink-0 items-center justify-center rounded-lg text-lg text-emerald-500 hover:bg-emerald-100 hover:text-emerald-700" aria-label="Tutup pesan">&times;</button>
            </div>
        @endif

        {{-- Error Alert --}}
        @if ($errors->any())
            <div class="p-4 rounded-xl bg-red-50 border border-red-200 text-red-800 text-sm font-medium space-y-1">
                @foreach ($errors->all() as $error)
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-red-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>{{ $error }}</span>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Top Summary Stats --}}
        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            <x-stat-tile label="Total Cabang" :value="$totalBranches" hint="Unit terdaftar dalam brand" />
            <x-stat-tile label="Cabang Aktif" :value="$activeBranches" hint="Menerima operasional antrean" tone="success" />
            <x-stat-tile label="Total Layanan" :value="$totalServices" hint="Layanan di semua cabang" />
            <x-stat-tile label="Total Loket" :value="$totalCounters" hint="Loket pelayanan aktif" />
        </div>

        {{-- Actions Bar --}}
        <div class="flex flex-col gap-3 rounded-xl border border-neutral-200 bg-white p-4 shadow-xs sm:flex-row sm:items-center sm:justify-between sm:gap-4">
            <div>
                <h3 class="text-sm font-bold text-neutral-900">Daftar Cabang</h3>
                <p class="text-xs text-neutral-500">
                    Pilih cabang untuk beralih konteks operasional dashboard, antrean, dan loket.
                </p>
            </div>

            <div class="flex w-full flex-wrap items-center gap-2 sm:w-auto">
                @if ($canManage)
                    <a href="{{ route('merchant.organization.edit') }}" class="inline-flex h-11 w-full items-center justify-center gap-2 rounded-xl border border-neutral-300 bg-white px-4 text-sm font-semibold text-neutral-800 transition hover:bg-neutral-50 sm:w-auto">
                        <span>Identitas Brand</span>
                    </a>
                    @if ($totalBranches > 1)
                        <form method="POST" action="{{ route('merchant.branches.switch') }}" class="w-full sm:w-auto">
                            @csrf
                            <input type="hidden" name="business_id" value="all">
                            <button type="submit" class="inline-flex h-11 w-full items-center justify-center gap-2 rounded-xl border border-brand-200 bg-brand-50 px-4 text-sm font-bold text-brand-700 transition hover:bg-brand-100 sm:w-auto">
                                Ringkasan di Beranda
                            </button>
                        </form>
                    @endif
                    <a href="{{ route('merchant.branches.create') }}" class="inline-flex h-11 w-full items-center justify-center gap-2 rounded-xl bg-brand-700 px-4 text-sm font-bold text-white shadow-sm transition hover:bg-brand-800 sm:w-auto">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>Tambah Cabang Baru</span>
                    </a>
                @endif
            </div>
        </div>

        {{-- Branches Grid --}}
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3 lg:gap-6">
            @forelse ($branches as $branch)
                @php
                    $isCurrentActive = $branch->id === $activeBranchId;
                @endphp
                <div class="rounded-2xl bg-white border {{ $isCurrentActive ? 'border-brand-500 ring-2 ring-brand-500/20' : 'border-neutral-200' }} shadow-xs p-4 sm:p-6 flex min-w-0 flex-col justify-between transition hover:shadow-md">
                    <div>
                        {{-- Header Row: Badge & Current active indicator --}}
                        <div class="flex items-start justify-between gap-2 mb-3">
                            <div class="flex flex-wrap items-center gap-1">
                                <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider {{ $branch->is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-neutral-100 text-neutral-600 border border-neutral-200' }}">
                                    {{ $branch->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                                <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-neutral-100 text-neutral-700">
                                    {{ $branch->status }}
                                </span>
                                @if ($branch->areaLabel())
                                    <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-brand-50 text-brand-700 border border-brand-200">
                                        {{ $branch->areaLabel() }}
                                    </span>
                                @endif
                            </div>

                            @if ($isCurrentActive)
                                <span class="inline-flex shrink-0 items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-bold bg-brand-50 text-brand-700 border border-brand-200">
                                    <x-customer.icon name="check" class="size-3" />
                                    <span>Cabang Aktif</span>
                                </span>
                            @endif
                        </div>

                        {{-- Branch Name & Category --}}
                        <h4 class="text-base font-bold text-neutral-900 line-clamp-1">{{ $branch->name }}</h4>
                        <p class="text-xs text-neutral-500 mt-0.5 font-medium">{{ $branch->category?->name ?? 'Kategori Umum' }}</p>

                        {{-- Address & Phone --}}
                        <div class="mt-4 space-y-1.5 text-xs text-neutral-600">
                            <div class="flex items-start gap-2">
                                <svg class="w-4 h-4 text-neutral-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <span class="line-clamp-2">{{ $branch->address }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-neutral-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                </svg>
                                <span>{{ $branch->phone }}</span>
                            </div>
                        </div>

                        {{-- Metadata Metrics --}}
                        <div class="grid grid-cols-3 gap-2 py-3 mt-4 border-y border-neutral-100 text-center">
                            <div>
                                <span class="block text-sm font-bold text-neutral-900 tabular-nums">{{ $branch->counters_count }}</span>
                                <span class="text-[10px] text-neutral-400 font-semibold uppercase">Loket</span>
                            </div>
                            <div>
                                <span class="block text-sm font-bold text-neutral-900 tabular-nums">{{ $branch->services_count }}</span>
                                <span class="text-[10px] text-neutral-400 font-semibold uppercase">Layanan</span>
                            </div>
                            <div>
                                <span class="block text-sm font-bold text-neutral-900 tabular-nums">{{ $branch->memberships_count }}</span>
                                <span class="text-[10px] text-neutral-400 font-semibold uppercase">Staf</span>
                            </div>
                        </div>
                    </div>

                    {{-- Actions Footer --}}
                    <div class="mt-5 pt-2 flex flex-col gap-2">
                        @if (! $isCurrentActive)
                            <form method="POST" action="{{ route('merchant.branches.switch') }}">
                                @csrf
                                <input type="hidden" name="business_id" value="{{ $branch->id }}">
                                <button type="submit" class="inline-flex h-11 w-full items-center justify-center gap-2 rounded-xl bg-brand-50 px-3.5 text-xs font-bold text-brand-700 transition hover:bg-brand-100">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                    </svg>
                                    <span>Pilih Cabang Ini</span>
                                </button>
                            </form>
                        @else
                            <div class="flex h-11 items-center justify-center rounded-xl border border-brand-100 bg-brand-50/50 text-xs font-bold text-brand-700">
                                Sedang Dioperasikan
                            </div>
                        @endif

                        <div class="flex items-center justify-between gap-2 pt-1">
                            <a href="{{ route('customer.queue', $branch->slug) }}" target="_blank" rel="noopener" class="inline-flex min-h-11 items-center gap-1 text-xs font-semibold text-neutral-500 hover:text-brand-700">
                                <span>Lihat Antrean</span>
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                            </a>

                            @if ($canManage)
                                <div class="flex items-center gap-1">
                                    <a href="{{ route('merchant.branches.edit', $branch) }}" class="inline-flex min-h-11 items-center rounded-lg px-2.5 text-xs font-semibold text-neutral-600 hover:bg-neutral-100 hover:text-neutral-900">
                                        Ubah
                                    </a>

                                    <form method="POST" action="{{ route('merchant.branches.toggle-active', $branch) }}" onsubmit="return confirm('Apakah Anda yakin ingin mengubah status aktif cabang ini?')">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="inline-flex min-h-11 items-center rounded-lg px-2.5 text-xs font-semibold hover:bg-neutral-100 {{ $branch->is_active ? 'text-danger-600 hover:text-danger-700' : 'text-emerald-600 hover:text-emerald-700' }}">
                                            {{ $branch->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                        </button>
                                    </form>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full py-12 text-center bg-white rounded-2xl border border-neutral-200">
                    <p class="text-sm font-semibold text-neutral-500">Belum ada cabang terdaftar.</p>
                </div>
            @endforelse
        </div>

        {{-- Pagination --}}
        @if ($branches->hasPages())
            <div class="rounded-xl border border-neutral-200 bg-white p-4">
                {{ $branches->links() }}
            </div>
        @endif
    </div>
</x-layouts.merchant>

