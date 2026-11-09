<x-layouts.admin 
    title="Monitoring Antrean Platform" 
    heading="Monitoring Antrean Lintas-UMKM" 
    description="Pantau arus tiket antrean di seluruh mitra UMKM secara realtime dalam mode pengawasan (read-only)."
>
    <div class="space-y-6">
        {{-- Read-Only Notice Banner (M8-25) --}}
        <div class="rounded-xl bg-blue-50/75 border border-blue-200 p-4 text-xs text-blue-900 flex items-start gap-3 shadow-xs">
            <svg class="w-5 h-5 text-blue-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <div>
                <strong class="font-bold text-blue-950 block">Mode Pengawasan Global (Read-Only)</strong>
                <p class="mt-0.5 text-blue-800">
                    Sesuai spesifikasi arsitektur AntreIn, Administrator memiliki akses pemantauan lintas-gerai untuk analitik dan audit tanpa wewenang mengubah status operasional tiket. Seluruh panggilan dan penyelesaian tiket tetap berada di bawah kendali operasional UMKM terkait. Nomor telepon pelanggan disensor demi perlindungan privasi (OQ-14).
                </p>
            </div>
        </div>

        {{-- Metrics Summary Strip (M8-22) --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-2.5">
            <div class="bg-white rounded-xl border border-neutral-200 p-3 shadow-xs">
                <span class="text-[11px] font-semibold text-neutral-500 block truncate">Total Tiket</span>
                <span class="text-xl font-extrabold text-neutral-950 tabular-nums block mt-0.5">{{ number_format($stats['total']) }}</span>
            </div>

            <div class="bg-amber-50/50 rounded-xl border border-amber-200 p-3 shadow-xs">
                <span class="text-[11px] font-semibold text-amber-800 block truncate">Menunggu</span>
                <span class="text-xl font-extrabold text-amber-950 tabular-nums block mt-0.5">{{ number_format($stats['menunggu']) }}</span>
            </div>

            <div class="bg-blue-50/50 rounded-xl border border-blue-200 p-3 shadow-xs">
                <span class="text-[11px] font-semibold text-blue-800 block truncate">Dipanggil</span>
                <span class="text-xl font-extrabold text-blue-950 tabular-nums block mt-0.5">{{ number_format($stats['dipanggil']) }}</span>
            </div>

            <div class="bg-indigo-50/50 rounded-xl border border-indigo-200 p-3 shadow-xs">
                <span class="text-[11px] font-semibold text-indigo-800 block truncate">Sedang Dilayani</span>
                <span class="text-xl font-extrabold text-indigo-950 tabular-nums block mt-0.5">{{ number_format($stats['sedang_dilayani']) }}</span>
            </div>

            <div class="bg-emerald-50/50 rounded-xl border border-emerald-200 p-3 shadow-xs">
                <span class="text-[11px] font-semibold text-emerald-800 block truncate">Selesai</span>
                <span class="text-xl font-extrabold text-emerald-950 tabular-nums block mt-0.5">{{ number_format($stats['selesai']) }}</span>
            </div>

            <div class="bg-orange-50/50 rounded-xl border border-orange-200 p-3 shadow-xs">
                <span class="text-[11px] font-semibold text-orange-800 block truncate">Dilewati</span>
                <span class="text-xl font-extrabold text-orange-950 tabular-nums block mt-0.5">{{ number_format($stats['dilewati']) }}</span>
            </div>

            <div class="bg-neutral-100/60 rounded-xl border border-neutral-300 p-3 shadow-xs col-span-2 sm:col-span-1">
                <span class="text-[11px] font-semibold text-neutral-600 block truncate">Dibatalkan</span>
                <span class="text-xl font-extrabold text-neutral-800 tabular-nums block mt-0.5">{{ number_format($stats['dibatalkan']) }}</span>
            </div>
        </div>

        {{-- Multi-Filter Bar (M8-23) --}}
        <div class="bg-white rounded-xl border border-neutral-200 p-4 shadow-xs">
            <form method="GET" action="{{ route('admin.queues.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 items-end">
                {{-- Search Box --}}
                <div class="lg:col-span-2">
                    <label class="block text-caption font-semibold text-neutral-700 mb-1">Cari No Tiket / Pelanggan / UMKM</label>
                    <div class="relative">
                        <input 
                            type="text" 
                            name="search" 
                            value="{{ $search }}" 
                            placeholder="Contoh: A-001, Budi, Warung Kopi..." 
                            class="w-full pl-9 pr-3 py-2 rounded-lg border-neutral-300 text-sm focus:border-brand-500 focus:ring-brand-500"
                        >
                        <svg class="w-4 h-4 text-neutral-400 absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                </div>

                {{-- Business Filter --}}
                <div>
                    <label class="block text-caption font-semibold text-neutral-700 mb-1">Gerai UMKM</label>
                    <select name="business_id" class="w-full rounded-lg border-neutral-300 text-sm focus:border-brand-500 focus:ring-brand-500">
                        <option value="">Semua UMKM</option>
                        @foreach ($businesses as $b)
                            <option value="{{ $b->id }}" {{ $businessId == $b->id ? 'selected' : '' }}>
                                {{ $b->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Status Filter --}}
                <div>
                    <label class="block text-caption font-semibold text-neutral-700 mb-1">Status Tiket</label>
                    <select name="status" class="w-full rounded-lg border-neutral-300 text-sm focus:border-brand-500 focus:ring-brand-500">
                        <option value="">Semua Status</option>
                        <option value="menunggu" {{ $status === 'menunggu' ? 'selected' : '' }}>Menunggu</option>
                        <option value="dipanggil" {{ $status === 'dipanggil' ? 'selected' : '' }}>Dipanggil</option>
                        <option value="sedang_dilayani" {{ $status === 'sedang_dilayani' ? 'selected' : '' }}>Sedang Dilayani</option>
                        <option value="selesai" {{ $status === 'selesai' ? 'selected' : '' }}>Selesai</option>
                        <option value="dilewati" {{ $status === 'dilewati' ? 'selected' : '' }}>Dilewati</option>
                        <option value="dibatalkan" {{ $status === 'dibatalkan' ? 'selected' : '' }}>Dibatalkan</option>
                    </select>
                </div>

                {{-- Source Filter --}}
                <div>
                    <label class="block text-caption font-semibold text-neutral-700 mb-1">Sumber (Kanal)</label>
                    <select name="source" class="w-full rounded-lg border-neutral-300 text-sm focus:border-brand-500 focus:ring-brand-500">
                        <option value="">Semua Kanal</option>
                        <option value="online" {{ $source === 'online' ? 'selected' : '' }}>Online (Web)</option>
                        <option value="walk_in" {{ $source === 'walk_in' ? 'selected' : '' }}>Walk-In (Kasir)</option>
                    </select>
                </div>

                {{-- Date Filter & Action Buttons --}}
                <div class="sm:col-span-2 lg:col-span-5 flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-2 border-t border-neutral-100">
                    <div class="flex items-center gap-2">
                        <div class="w-44">
                            <input 
                                type="date" 
                                name="date" 
                                value="{{ $date }}" 
                                class="w-full py-1.5 px-3 rounded-lg border-neutral-300 text-xs focus:border-brand-500 focus:ring-brand-500"
                            >
                        </div>
                        <button type="submit" class="px-4 py-2 rounded-lg bg-neutral-900 text-white text-xs font-bold hover:bg-neutral-800 transition shadow-xs">
                            Filter
                        </button>
                        @if ($search || $businessId || $status || $source || $date)
                            <a href="{{ route('admin.queues.index') }}" class="px-3 py-2 rounded-lg border border-neutral-300 text-neutral-700 text-xs font-medium hover:bg-neutral-50 transition">
                                Reset
                            </a>
                        @endif
                    </div>

                    <span class="text-caption text-neutral-500">
                        Total <strong>{{ $tickets->total() }}</strong> tiket terpantau
                    </span>
                </div>
            </form>
        </div>

        {{-- Queue Table (M8-21, M8-26, M8-30, M8-33) --}}
        <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden shadow-xs">
            <div class="p-4 sm:p-5 border-b border-neutral-200 flex items-center justify-between">
                <div>
                    <h3 class="text-h3 font-bold text-neutral-900">Arus Antrean Lintas UMKM</h3>
                    <p class="text-caption text-neutral-500 mt-0.5">Daftar tiket terkini dari semua gerai UMKM mitra.</p>
                </div>
            </div>

            {{-- Desktop Table View --}}
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="border-b border-neutral-200 bg-neutral-50/75 text-neutral-600 font-semibold text-xs tracking-wider uppercase">
                            <th class="py-3.5 px-4">No. Tiket</th>
                            <th class="py-3.5 px-4">Gerai UMKM</th>
                            <th class="py-3.5 px-4">Layanan</th>
                            <th class="py-3.5 px-4">Pelanggan</th>
                            <th class="py-3.5 px-4">Kanal</th>
                            <th class="py-3.5 px-4">Status</th>
                            <th class="py-3.5 px-4">Waktu Dibuat</th>
                            <th class="py-3.5 px-4 text-right">Detail</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-200">
                        @forelse ($tickets as $ticket)
                            <tr class="hover:bg-neutral-50/50 transition">
                                {{-- Ticket Number --}}
                                <td class="py-3.5 px-4 font-mono font-bold text-neutral-900">
                                    <span class="px-2 py-1 rounded bg-neutral-100 border border-neutral-200">
                                        {{ $ticket->ticket_number }}
                                    </span>
                                </td>

                                {{-- Business Name --}}
                                <td class="py-3.5 px-4">
                                    @if ($ticket->business)
                                        <a href="{{ route('admin.businesses.show', $ticket->business) }}" class="font-semibold text-neutral-900 hover:text-brand-600 hover:underline block truncate max-w-[160px]">
                                            {{ $ticket->business->name }}
                                        </a>
                                    @else
                                        <span class="text-neutral-400">—</span>
                                    @endif
                                </td>

                                {{-- Service Name --}}
                                <td class="py-3.5 px-4 text-neutral-700">
                                    <span class="truncate block max-w-[140px]" title="{{ $ticket->service?->name }}">
                                        {{ $ticket->service?->name ?? '—' }}
                                    </span>
                                </td>

                                {{-- Customer Name & Masked Phone (OQ-14, M8-26) --}}
                                <td class="py-3.5 px-4">
                                    <span class="font-medium text-neutral-900 block truncate max-w-[140px]">{{ $ticket->customer_name }}</span>
                                    <span class="text-caption font-mono text-neutral-400 block">
                                        {{ \App\Http\Controllers\Admin\QueueMonitoringController::maskPhoneNumber($ticket->customer_phone) }}
                                    </span>
                                </td>

                                {{-- Source --}}
                                <td class="py-3.5 px-4">
                                    @if ($ticket->source === 'online')
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-sky-50 text-sky-700 border border-sky-200">
                                            Online
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-neutral-100 text-neutral-700 border border-neutral-200">
                                            Walk-in
                                        </span>
                                    @endif
                                </td>

                                {{-- Status Badge --}}
                                <td class="py-3.5 px-4">
                                    @switch($ticket->status)
                                        @case('menunggu')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                                Menunggu
                                            </span>
                                            @break
                                        @case('dipanggil')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200">
                                                Dipanggil
                                            </span>
                                            @break
                                        @case('sedang_dilayani')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200">
                                                Dilayani
                                            </span>
                                            @break
                                        @case('selesai')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                Selesai
                                            </span>
                                            @break
                                        @case('dilewati')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-orange-50 text-orange-700 border border-orange-200">
                                                Dilewati
                                            </span>
                                            @break
                                        @case('dibatalkan')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-neutral-100 text-neutral-600 border border-neutral-200">
                                                Dibatalkan
                                            </span>
                                            @break
                                    @endswitch
                                </td>

                                {{-- Created At --}}
                                <td class="py-3.5 px-4 text-caption text-neutral-500 whitespace-nowrap">
                                    {{ $ticket->created_at->format('d M, H:i') }}
                                </td>

                                {{-- View Detail Action --}}
                                <td class="py-3.5 px-4 text-right">
                                    <a 
                                        href="{{ route('admin.queues.show', $ticket) }}" 
                                        class="p-1.5 rounded-lg text-neutral-600 hover:text-neutral-900 hover:bg-neutral-100 transition inline-block"
                                        title="Pantau Detail Tiket"
                                    >
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-12 text-center">
                                    <div class="max-w-xs mx-auto text-neutral-400">
                                        <svg class="w-10 h-10 mx-auto text-neutral-300 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                        <p class="font-semibold text-neutral-700">Tidak ada tiket antrean ditemukan</p>
                                        <p class="text-caption text-neutral-500 mt-1">Coba ubah filter status, gerai UMKM, atau tanggal antrean.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Mobile Card View (M8-33) --}}
            <div class="md:hidden divide-y divide-neutral-200">
                @forelse ($tickets as $ticket)
                    <div class="p-4 space-y-3">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <span class="font-mono font-bold text-base text-neutral-900 bg-neutral-100 px-2.5 py-1 rounded border border-neutral-200 inline-block">
                                    {{ $ticket->ticket_number }}
                                </span>
                                <h4 class="font-semibold text-neutral-900 mt-2">{{ $ticket->customer_name }}</h4>
                                <p class="text-caption font-mono text-neutral-400">
                                    {{ \App\Http\Controllers\Admin\QueueMonitoringController::maskPhoneNumber($ticket->customer_phone) }}
                                </p>
                            </div>

                            <div class="text-right shrink-0">
                                @switch($ticket->status)
                                    @case('menunggu')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">Menunggu</span>
                                        @break
                                    @case('dipanggil')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200">Dipanggil</span>
                                        @break
                                    @case('sedang_dilayani')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200">Dilayani</span>
                                        @break
                                    @case('selesai')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">Selesai</span>
                                        @break
                                    @case('dilewati')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-orange-50 text-orange-700 border border-orange-200">Dilewati</span>
                                        @break
                                    @case('dibatalkan')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-neutral-100 text-neutral-600 border border-neutral-200">Dibatalkan</span>
                                        @break
                                @endswitch

                                <span class="block text-[11px] text-neutral-400 mt-1">
                                    {{ $ticket->source === 'online' ? 'Online' : 'Walk-in' }}
                                </span>
                            </div>
                        </div>

                        <div class="text-caption text-neutral-600 space-y-1 bg-neutral-50/75 p-2.5 rounded-lg border border-neutral-200/60">
                            <div class="flex justify-between">
                                <span class="text-neutral-500">UMKM:</span>
                                <span class="font-semibold text-neutral-900 truncate max-w-[180px]">{{ $ticket->business?->name ?? '—' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-neutral-500">Layanan:</span>
                                <span class="font-medium text-neutral-800 truncate max-w-[180px]">{{ $ticket->service?->name ?? '—' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-neutral-500">Waktu:</span>
                                <span>{{ $ticket->created_at->format('d M Y, H:i') }}</span>
                            </div>
                        </div>

                        <div class="flex justify-end pt-1">
                            <a 
                                href="{{ route('admin.queues.show', $ticket) }}" 
                                class="px-3 py-1.5 rounded-lg border border-neutral-300 text-neutral-700 text-xs font-medium hover:bg-neutral-50 transition"
                            >
                                Pantau Detail Tiket
                            </a>
                        </div>
                    </div>
                @empty
                    <div class="py-8 text-center text-neutral-400">
                        <p class="font-semibold text-neutral-700">Tidak ada tiket antrean ditemukan</p>
                    </div>
                @endforelse
            </div>

            {{-- Pagination Links --}}
            @if ($tickets->hasPages())
                <div class="p-4 border-t border-neutral-200 bg-neutral-50/50">
                    {{ $tickets->links() }}
                </div>
            @endif
        </div>
    </div>
</x-layouts.admin>

