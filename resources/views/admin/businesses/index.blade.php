<x-layouts.admin 
    title="Manajemen UMKM" 
    heading="Manajemen UMKM Platform" 
    description="Kelola mitra UMKM terdaftar, pantau kepatuhan operasional, dan kelola penangguhan akun."
>
    <div class="space-y-6" x-data="{ suspendModalOpen: false, targetBusinessId: null, targetBusinessName: '', currentSuspended: false }">
        {{-- Flash Messages --}}
        @if (session('status'))
            <div class="rounded-xl bg-emerald-50 border border-emerald-200 p-4 text-sm font-medium text-emerald-800 flex items-center justify-between shadow-xs">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <span>{{ session('status') }}</span>
                </div>
            </div>
        @endif

        {{-- Metrics Summary Strip --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-white rounded-xl border border-neutral-200 p-4 shadow-xs">
                <span class="text-caption font-semibold text-neutral-500 block truncate">Total UMKM</span>
                <span class="text-2xl font-extrabold text-neutral-950 tabular-nums block mt-1">{{ number_format($stats['total']) }}</span>
                <span class="text-[11px] text-neutral-400 mt-0.5 block">Semua akun terdaftar</span>
            </div>

            <div class="bg-emerald-50/50 rounded-xl border border-emerald-200 p-4 shadow-xs">
                <span class="text-caption font-semibold text-emerald-800 block truncate">UMKM Aktif</span>
                <span class="text-2xl font-extrabold text-emerald-950 tabular-nums block mt-1">{{ number_format($stats['active']) }}</span>
                <span class="text-[11px] text-emerald-700 mt-0.5 block">Dapat menerima antrean</span>
            </div>

            <div class="bg-rose-50/50 rounded-xl border border-rose-200 p-4 shadow-xs">
                <span class="text-caption font-semibold text-rose-800 block truncate">UMKM Ditangguhkan (Suspended)</span>
                <span class="text-2xl font-extrabold text-rose-950 tabular-nums block mt-1">{{ number_format($stats['suspended']) }}</span>
                <span class="text-[11px] text-rose-700 mt-0.5 block">Pendaftaran tiket diblokir</span>
            </div>
        </div>

        {{-- Search and Multi-Filter Bar (M8-08) --}}
        <div class="bg-white rounded-xl border border-neutral-200 p-4 shadow-xs">
            <form method="GET" action="{{ route('admin.businesses.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 items-end">
                {{-- Search Box --}}
                <div class="lg:col-span-2">
                    <label class="block text-caption font-semibold text-neutral-700 mb-1">Cari Nama UMKM / Pemilik / Email</label>
                    <div class="relative">
                        <input 
                            type="text" 
                            name="search" 
                            value="{{ $search }}" 
                            placeholder="Ketik nama usaha atau pemilik..." 
                            class="w-full pl-9 pr-3 py-2 rounded-lg border-neutral-300 text-sm focus:border-brand-500 focus:ring-brand-500"
                        >
                        <svg class="w-4 h-4 text-neutral-400 absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                </div>

                {{-- Category Filter --}}
                <div>
                    <label class="block text-caption font-semibold text-neutral-700 mb-1">Kategori</label>
                    <select name="category_id" class="w-full rounded-lg border-neutral-300 text-sm focus:border-brand-500 focus:ring-brand-500">
                        <option value="">Semua Kategori</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}" {{ $categoryId == $cat->id ? 'selected' : '' }}>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Suspension Status Filter --}}
                <div>
                    <label class="block text-caption font-semibold text-neutral-700 mb-1">Status Penangguhan</label>
                    <select name="status" class="w-full rounded-lg border-neutral-300 text-sm focus:border-brand-500 focus:ring-brand-500">
                        <option value="">Semua Status</option>
                        <option value="active" {{ $status === 'active' ? 'selected' : '' }}>Normal / Aktif</option>
                        <option value="suspended" {{ $status === 'suspended' ? 'selected' : '' }}>Ditangguhkan (Suspended)</option>
                    </select>
                </div>

                {{-- Action Filter Buttons --}}
                <div class="sm:col-span-2 lg:col-span-4 flex items-center justify-between pt-2 border-t border-neutral-100">
                    <div class="flex items-center gap-2">
                        <button type="submit" class="px-4 py-2 rounded-lg bg-neutral-900 text-white text-xs font-bold hover:bg-neutral-800 transition shadow-xs">
                            Terapkan Filter
                        </button>
                        <a href="{{ route('admin.businesses.index') }}" class="px-3 py-2 rounded-lg border border-neutral-300 text-neutral-700 text-xs font-medium hover:bg-neutral-50 transition">
                            Reset
                        </a>
                    </div>

                    <span class="text-caption text-neutral-500">
                        Menampilkan <strong>{{ $businesses->total() }}</strong> UMKM
                    </span>
                </div>
            </form>
        </div>

        {{-- Business Listing (M8-08, M8-09, M8-30, M8-33) --}}
        <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden shadow-xs">
            <div class="p-4 sm:p-5 border-b border-neutral-200 flex items-center justify-between">
                <div>
                    <h3 class="text-h3 font-bold text-neutral-900">Daftar UMKM</h3>
                    <p class="text-caption text-neutral-500">Data seluruh unit usaha yang terdaftar pada sistem AntreIn.</p>
                </div>
            </div>

            @if ($businesses->isEmpty())
                <div class="p-12 text-center">
                    <div class="w-12 h-12 rounded-full bg-neutral-100 text-neutral-400 mx-auto flex items-center justify-center mb-3">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    </div>
                    <h3 class="text-h3 font-semibold text-neutral-900">Tidak Ada Data UMKM Ditemukan</h3>
                    <p class="text-caption text-neutral-500 mt-1 max-w-sm mx-auto">
                        @if ($search || $categoryId || $status)
                            Tidak ada unit usaha yang sesuai dengan filter pencarian.
                        @else
                            Belum ada unit usaha UMKM yang terdaftar di platform.
                        @endif
                    </p>
                </div>
            @else
                {{-- Desktop Table View --}}
                <div class="hidden md:block overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-neutral-200 bg-neutral-50/70 text-caption font-semibold text-neutral-600">
                                <th class="py-3 px-4">Nama Usaha</th>
                                <th class="py-3 px-4">Pemilik Akun</th>
                                <th class="py-3 px-4">Kategori</th>
                                <th class="py-3 px-4 text-center">Layanan</th>
                                <th class="py-3 px-4 text-center">Tiket</th>
                                <th class="py-3 px-4">Terdaftar</th>
                                <th class="py-3 px-4">Status Platform</th>
                                <th class="py-3 px-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-100 text-body">
                            @foreach ($businesses as $b)
                                <tr class="hover:bg-neutral-50/80 transition {{ $b->is_suspended ? 'bg-rose-50/30' : '' }}">
                                    <td class="py-3.5 px-4">
                                        <a href="{{ route('admin.businesses.show', $b) }}" class="font-bold text-neutral-950 hover:text-brand-700 block">
                                            {{ $b->name }}
                                        </a>
                                        <span class="text-[11px] text-neutral-400 font-mono">/q/{{ $b->slug }}</span>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <div class="font-medium text-neutral-900">{{ $b->user?->name ?? '—' }}</div>
                                        <div class="text-[11px] text-neutral-500">{{ $b->user?->email ?? '—' }}</div>
                                    </td>
                                    <td class="py-3.5 px-4 text-caption text-neutral-600">
                                        {{ $b->category?->name ?? 'Tanpa Kategori' }}
                                    </td>
                                    <td class="py-3.5 px-4 text-center font-semibold text-neutral-800 tabular-nums text-xs">
                                        {{ $b->services_count }}
                                    </td>
                                    <td class="py-3.5 px-4 text-center font-semibold text-neutral-800 tabular-nums text-xs">
                                        {{ $b->queue_tickets_count }}
                                    </td>
                                    <td class="py-3.5 px-4 text-caption text-neutral-500 tabular-nums">
                                        {{ $b->created_at->format('d/m/Y') }}
                                    </td>
                                    <td class="py-3.5 px-4">
                                        @if ($b->is_suspended)
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-rose-100 text-rose-800 border border-rose-300">
                                                <span class="w-1.5 h-1.5 rounded-full bg-rose-600"></span>
                                                Suspended
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                                Aktif Normal
                                            </span>
                                        @endif
                                    </td>
                                    <td class="py-3.5 px-4 text-right">
                                        <div class="inline-flex items-center gap-2 justify-end">
                                            <a href="{{ route('admin.businesses.show', $b) }}" class="px-2.5 py-1 rounded text-xs font-semibold text-neutral-700 hover:text-neutral-950 hover:bg-neutral-100 transition">
                                                Detail
                                            </a>

                                            <button 
                                                type="button" 
                                                @click="targetBusinessId = {{ $b->id }}; targetBusinessName = '{{ addslashes($b->name) }}'; currentSuspended = {{ $b->is_suspended ? 'true' : 'false' }}; suspendModalOpen = true;"
                                                class="px-2 py-1 rounded text-xs font-semibold {{ $b->is_suspended ? 'text-emerald-700 hover:bg-emerald-50' : 'text-rose-600 hover:bg-rose-50' }} transition"
                                            >
                                                {{ $b->is_suspended ? 'Pulihkan' : 'Tangguhkan' }}
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Mobile Cards View (M8-32, M8-33) --}}
                <div class="md:hidden divide-y divide-neutral-100">
                    @foreach ($businesses as $b)
                        <div class="p-4 space-y-3 {{ $b->is_suspended ? 'bg-rose-50/20' : '' }}">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <h4 class="text-sm font-bold text-neutral-950">{{ $b->name }}</h4>
                                    <p class="text-xs text-neutral-500 mt-0.5">Pemilik: {{ $b->user?->name ?? '—' }} ({{ $b->user?->email ?? '—' }})</p>
                                </div>
                                @if ($b->is_suspended)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800 border border-rose-300 shrink-0">
                                        Suspended
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 shrink-0">
                                        Aktif
                                    </span>
                                @endif
                            </div>

                            <div class="grid grid-cols-2 gap-2 text-xs text-neutral-600 bg-neutral-50 p-2.5 rounded-lg border border-neutral-200">
                                <div>Kategori: <strong class="text-neutral-900 block">{{ $b->category?->name ?? '—' }}</strong></div>
                                <div>Terdaftar: <strong class="text-neutral-900 block">{{ $b->created_at->format('d M Y') }}</strong></div>
                                <div>Layanan: <strong class="text-neutral-900">{{ $b->services_count }}</strong></div>
                                <div>Total Tiket: <strong class="text-neutral-900">{{ $b->queue_tickets_count }}</strong></div>
                            </div>

                            <div class="flex items-center justify-between pt-2 border-t border-neutral-100">
                                <a href="{{ route('admin.businesses.show', $b) }}" class="text-xs font-semibold text-brand-700">
                                    Lihat Detail &rarr;
                                </a>

                                <button 
                                    type="button" 
                                    @click="targetBusinessId = {{ $b->id }}; targetBusinessName = '{{ addslashes($b->name) }}'; currentSuspended = {{ $b->is_suspended ? 'true' : 'false' }}; suspendModalOpen = true;"
                                    class="px-2.5 py-1 rounded text-xs font-semibold {{ $b->is_suspended ? 'bg-emerald-50 text-emerald-800' : 'bg-rose-50 text-rose-800' }}"
                                >
                                    {{ $b->is_suspended ? 'Pulihkan Akun' : 'Tangguhkan' }}
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Pagination --}}
                <div class="p-4 border-t border-neutral-200">
                    {{ $businesses->links() }}
                </div>
            @endif
        </div>

        {{-- Suspension / Reactivation Modal (M8-11) --}}
        <div 
            x-show="suspendModalOpen" 
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-50 overflow-y-auto bg-neutral-950/60 flex items-center justify-center p-4"
            style="display: none;"
            @keydown.escape.window="suspendModalOpen = false"
        >
            <div 
                @click.outside="suspendModalOpen = false"
                class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-neutral-200 relative"
            >
                <div class="flex items-center justify-between border-b border-neutral-100 pb-3 mb-4">
                    <h3 class="text-h3 font-bold text-neutral-900">
                        <span x-text="currentSuspended ? 'Konfirmasi Pemulihan Akun UMKM' : 'Konfirmasi Penangguhan Akun UMKM'"></span>
                    </h3>
                    <button @click="suspendModalOpen = false" class="text-neutral-400 hover:text-neutral-600 p-1">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 18l12 12"/></svg>
                    </button>
                </div>

                <form :action="'{{ url('/admin/businesses') }}/' + targetBusinessId + '/toggle-suspension'" method="POST" class="space-y-4">
                    @csrf
                    @method('PATCH')

                    <p class="text-xs text-neutral-600">
                        Anda akan mengubah status penangguhan platform untuk unit usaha: <strong class="text-neutral-900" x-text="targetBusinessName"></strong>.
                    </p>

                    <div x-show="!currentSuspended" class="p-3 bg-rose-50 border border-rose-200 rounded-lg text-xs text-rose-800">
                        <strong>Perhatian:</strong> Penangguhan akun akan memblokir pelanggan dalam mengambil nomor tiket baru. Tiket aktif yang sedang berjalan tetap dapat diselesaikan oleh pemilik usaha sesuai keputusan OQ-07.
                    </div>

                    <div>
                        <label class="block text-caption font-semibold text-neutral-700 mb-1">
                            Alasan Perubahan Status (Opsional, dicatat pada Activity Log)
                        </label>
                        <textarea 
                            name="reason" 
                            rows="2" 
                            maxlength="500"
                            placeholder="Catatan pelanggaran atau alasan pemulihan..."
                            class="w-full rounded-lg border-neutral-300 text-sm focus:border-brand-500 focus:ring-brand-500"
                        ></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-neutral-100">
                        <button type="button" @click="suspendModalOpen = false" class="px-4 py-2 rounded-lg border border-neutral-300 text-xs font-semibold text-neutral-700 hover:bg-neutral-50 transition">
                            Batal
                        </button>
                        <button 
                            type="submit" 
                            class="px-4 py-2 rounded-lg text-xs font-bold text-white transition shadow-xs"
                            :class="currentSuspended ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-rose-600 hover:bg-rose-700'"
                        >
                            <span x-text="currentSuspended ? 'Ya, Pulihkan Akun UMKM' : 'Ya, Tangguhkan Akun UMKM'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-layouts.admin>

