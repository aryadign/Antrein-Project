<x-layouts.admin 
    title="Detail UMKM {{ $business->name }}" 
    heading="Detail Unit Usaha Mitra" 
    description="Informasi profil, konfigurasi layanan, jam buka, dan metrik antrean."
>
    <div class="space-y-6 max-w-5xl mx-auto" x-data="{ suspendModalOpen: false }">
        {{-- Flash Messages --}}
        @if (session('status'))
            <div class="rounded-xl bg-emerald-50 border border-emerald-200 p-4 text-sm font-medium text-emerald-800 flex items-center justify-between shadow-xs">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <span>{{ session('status') }}</span>
                </div>
            </div>
        @endif

        {{-- Breadcrumb Navigation --}}
        <div>
            <a href="{{ route('admin.businesses.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-neutral-600 hover:text-neutral-900 transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                <span>Kembali ke Daftar UMKM</span>
            </a>
        </div>

        {{-- Header Card --}}
        <div class="bg-white rounded-2xl border border-neutral-200 p-6 shadow-xs">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-neutral-100 pb-5 mb-5">
                <div class="flex items-center gap-4">
                    <div class="w-16 h-16 rounded-xl border border-neutral-200 bg-neutral-100 flex items-center justify-center font-bold text-xl text-brand-700 overflow-hidden shrink-0">
                        @if ($business->logo_path)
                            <img src="{{ $business->getLogoUrl() }}" alt="Logo" class="w-full h-full object-cover">
                        @else
                            {{ strtoupper(substr($business->name, 0, 2)) }}
                        @endif
                    </div>

                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <h1 class="text-h2 font-bold text-neutral-950">{{ $business->name }}</h1>
                            @if ($business->is_suspended)
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-100 text-rose-800 border border-rose-300">
                                    SUSPENDED
                                </span>
                            @else
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    AKTIF NORMAL
                                </span>
                            @endif

                            <x-status-badge :status="$business->status" size="sm" />
                        </div>
                        <p class="text-caption text-neutral-500 mt-1">
                            Tautan Publik: <a href="{{ url('/q/' . $business->slug) }}" target="_blank" class="text-brand-700 hover:underline font-mono">/q/{{ $business->slug }}</a> • Terdaftar: {{ $business->created_at->format('d F Y') }}
                        </p>
                    </div>
                </div>

                {{-- Suspension Action Button --}}
                <div class="flex items-center gap-3">
                    <button 
                        type="button" 
                        @click="suspendModalOpen = true"
                        class="px-4 py-2 rounded-lg text-xs font-bold text-white transition shadow-xs {{ $business->is_suspended ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-rose-600 hover:bg-rose-700' }}"
                    >
                        {{ $business->is_suspended ? 'Cabut Penangguhan (Pulihkan)' : 'Tangguhkan Akun UMKM' }}
                    </button>
                </div>
            </div>

            {{-- Metadata Grid --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                <div class="p-3.5 bg-neutral-50 rounded-xl border border-neutral-200">
                    <span class="text-caption font-semibold text-neutral-500 block">Kategori Usaha</span>
                    <span class="font-bold text-sm text-neutral-900 block mt-0.5">{{ $business->category?->name ?? '—' }}</span>
                </div>

                <div class="p-3.5 bg-neutral-50 rounded-xl border border-neutral-200">
                    <span class="text-caption font-semibold text-neutral-500 block">Pemilik Akun</span>
                    <span class="font-bold text-sm text-neutral-900 block mt-0.5">{{ $business->user?->name ?? '—' }}</span>
                    <span class="text-[11px] text-neutral-500">{{ $business->user?->email ?? '—' }}</span>
                </div>

                <div class="p-3.5 bg-neutral-50 rounded-xl border border-neutral-200">
                    <span class="text-caption font-semibold text-neutral-500 block">Nomor Kontak</span>
                    <span class="font-bold text-sm text-neutral-900 block mt-0.5">{{ $business->phone }}</span>
                </div>

                <div class="p-3.5 bg-neutral-50 rounded-xl border border-neutral-200">
                    <span class="text-caption font-semibold text-neutral-500 block">Zona Waktu</span>
                    <span class="font-bold text-sm text-neutral-900 block mt-0.5">{{ $business->timezone }}</span>
                </div>
            </div>

            @if ($business->address)
                <div class="mt-4 p-3.5 bg-neutral-50 rounded-xl border border-neutral-200 text-xs text-neutral-700">
                    <strong class="font-semibold text-neutral-900">Alamat Lokasi:</strong> {{ $business->address }}
                </div>
            @endif

            @if ($business->description)
                <div class="mt-3 p-3.5 bg-neutral-50 rounded-xl border border-neutral-200 text-xs text-neutral-700">
                    <strong class="font-semibold text-neutral-900">Deskripsi Usaha:</strong> {{ $business->description }}
                </div>
            @endif
        </div>

        {{-- Queue Metrics Summary Grid --}}
        <div>
            <h3 class="text-h3 font-bold text-neutral-900 mb-3">Statistik Antrean Akumulatif UMKM Ini</h3>
            <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-3">
                <div class="bg-white rounded-xl border border-neutral-200 p-4 shadow-xs">
                    <span class="text-caption font-semibold text-neutral-500 block">Total Tiket</span>
                    <span class="text-2xl font-extrabold text-neutral-950 tabular-nums block mt-1">{{ $queueStats['total'] }}</span>
                </div>
                <div class="bg-amber-50/50 rounded-xl border border-amber-200 p-4 shadow-xs">
                    <span class="text-caption font-semibold text-amber-800 block">Menunggu</span>
                    <span class="text-2xl font-extrabold text-amber-950 tabular-nums block mt-1">{{ $queueStats['menunggu'] }}</span>
                </div>
                <div class="bg-blue-50/50 rounded-xl border border-blue-200 p-4 shadow-xs">
                    <span class="text-caption font-semibold text-blue-800 block">Dipanggil</span>
                    <span class="text-2xl font-extrabold text-blue-950 tabular-nums block mt-1">{{ $queueStats['dipanggil'] }}</span>
                </div>
                <div class="bg-indigo-50/50 rounded-xl border border-indigo-200 p-4 shadow-xs">
                    <span class="text-caption font-semibold text-indigo-800 block">Dilayani</span>
                    <span class="text-2xl font-extrabold text-indigo-950 tabular-nums block mt-1">{{ $queueStats['sedang_dilayani'] }}</span>
                </div>
                <div class="bg-emerald-50/50 rounded-xl border border-emerald-200 p-4 shadow-xs">
                    <span class="text-caption font-semibold text-emerald-800 block">Selesai</span>
                    <span class="text-2xl font-extrabold text-emerald-950 tabular-nums block mt-1">{{ $queueStats['selesai'] }}</span>
                </div>
                <div class="bg-neutral-50 rounded-xl border border-neutral-200 p-4 shadow-xs">
                    <span class="text-caption font-semibold text-neutral-600 block">Dilewati</span>
                    <span class="text-2xl font-extrabold text-neutral-800 tabular-nums block mt-1">{{ $queueStats['dilewati'] }}</span>
                </div>
                <div class="bg-rose-50/50 rounded-xl border border-rose-200 p-4 shadow-xs">
                    <span class="text-caption font-semibold text-rose-800 block">Dibatalkan</span>
                    <span class="text-2xl font-extrabold text-rose-950 tabular-nums block mt-1">{{ $queueStats['dibatalkan'] }}</span>
                </div>
            </div>
        </div>

        {{-- Operating Hours and Services Layout --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Services List --}}
            <div class="bg-white rounded-xl border border-neutral-200 p-5 shadow-xs">
                <div class="flex items-center justify-between mb-4 border-b border-neutral-100 pb-3">
                    <h3 class="text-h3 font-bold text-neutral-900">Daftar Layanan Antrean</h3>
                    <span class="text-caption text-neutral-500">{{ $business->services->count() }} Layanan</span>
                </div>

                @if ($business->services->isEmpty())
                    <p class="text-xs text-neutral-400 py-4 text-center">Belum ada layanan yang didaftarkan.</p>
                @else
                    <div class="divide-y divide-neutral-100">
                        @foreach ($business->services as $srv)
                            <div class="py-3 flex items-center justify-between">
                                <div>
                                    <h4 class="text-sm font-semibold text-neutral-900">{{ $srv->name }}</h4>
                                    <p class="text-xs text-neutral-500">
                                        Durasi: ~{{ $srv->duration_minutes }} mnt • Kuota harian: {{ $srv->daily_quota ? $srv->daily_quota . ' tiket' : 'Tidak terbatas' }}
                                    </p>
                                </div>
                                <div class="text-right">
                                    <span class="text-xs font-semibold text-neutral-900 block">Rp {{ number_format($srv->price, 0, ',', '.') }}</span>
                                    <span class="text-[10px] px-2 py-0.5 rounded-full font-bold {{ $srv->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-neutral-100 text-neutral-500' }}">
                                        {{ $srv->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Operating Hours --}}
            <div class="bg-white rounded-xl border border-neutral-200 p-5 shadow-xs">
                <div class="flex items-center justify-between mb-4 border-b border-neutral-100 pb-3">
                    <h3 class="text-h3 font-bold text-neutral-900">Jadwal Operasional Mingguan</h3>
                    <span class="text-caption text-neutral-500">{{ $business->timezone }}</span>
                </div>

                @if ($business->hours->isEmpty())
                    <p class="text-xs text-neutral-400 py-4 text-center">Jadwal operasional belum diatur.</p>
                @else
                    <div class="space-y-2">
                        @php
                            $dayNames = [
                                1 => 'Senin',
                                2 => 'Selasa',
                                3 => 'Rabu',
                                4 => 'Kamis',
                                5 => 'Jumat',
                                6 => 'Sabtu',
                                7 => 'Minggu',
                            ];
                        @endphp
                        @foreach ($business->hours as $h)
                            <div class="flex items-center justify-between text-xs py-1.5 border-b border-neutral-50 last:border-b-0">
                                <span class="font-semibold text-neutral-800 w-24">{{ $dayNames[$h->day_of_week] ?? 'Hari ' . $h->day_of_week }}</span>
                                @if ($h->is_closed)
                                    <span class="text-neutral-400 font-medium">Libur / Tutup</span>
                                @else
                                    <span class="font-mono text-neutral-700 tabular-nums">{{ substr($h->open_time, 0, 5) }} — {{ substr($h->close_time, 0, 5) }} WITA</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- Recent Audit Activity for this Business --}}
        <div class="bg-white rounded-xl border border-neutral-200 p-5 shadow-xs">
            <h3 class="text-h3 font-bold text-neutral-900 mb-3">Histori Log Aktivitas Terakhir Unit Usaha</h3>
            @if ($recentLogs->isEmpty())
                <p class="text-xs text-neutral-400 py-4 text-center">Belum ada riwayat aktivitas yang tercatat untuk UMKM ini.</p>
            @else
                <div class="divide-y divide-neutral-100">
                    @foreach ($recentLogs as $log)
                        <div class="py-2.5 flex items-start justify-between gap-3 text-xs">
                            <div>
                                <span class="font-semibold text-neutral-900 block">{{ $log->description }}</span>
                                <span class="text-neutral-500 text-[11px]">Oleh: {{ $log->user?->name ?? 'Sistem' }} • Aksi: <code class="text-brand-700 font-mono">{{ $log->action }}</code></span>
                            </div>
                            <span class="text-neutral-400 tabular-nums shrink-0 text-[11px]">{{ $log->created_at->format('d/m/Y H:i') }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Suspension Modal --}}
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
                        {{ $business->is_suspended ? 'Konfirmasi Pemulihan Akun UMKM' : 'Konfirmasi Penangguhan Akun UMKM' }}
                    </h3>
                    <button @click="suspendModalOpen = false" class="text-neutral-400 hover:text-neutral-600 p-1">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 18l12 12"/></svg>
                    </button>
                </div>

                <form action="{{ route('admin.businesses.toggle-suspension', $business) }}" method="POST" class="space-y-4">
                    @csrf
                    @method('PATCH')

                    <p class="text-xs text-neutral-600">
                        Anda akan mengubah status penangguhan akun: <strong class="text-neutral-900">{{ $business->name }}</strong>.
                    </p>

                    @if (! $business->is_suspended)
                        <div class="p-3 bg-rose-50 border border-rose-200 rounded-lg text-xs text-rose-800">
                            <strong>Perhatian:</strong> Penangguhan akun memblokir pendaftaran tiket baru. Tiket aktif yang sedang berjalan tetap dapat diselesaikan sesuai keputusan OQ-07.
                        </div>
                    @endif

                    <div>
                        <label class="block text-caption font-semibold text-neutral-700 mb-1">
                            Alasan Perubahan Status (Opsional, dicatat pada Activity Log)
                        </label>
                        <textarea 
                            name="reason" 
                            rows="2" 
                            maxlength="500"
                            placeholder="Catatan penangguhan atau pemulihan..."
                            class="w-full rounded-lg border-neutral-300 text-sm focus:border-brand-500 focus:ring-brand-500"
                        ></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-neutral-100">
                        <button type="button" @click="suspendModalOpen = false" class="px-4 py-2 rounded-lg border border-neutral-300 text-xs font-semibold text-neutral-700 hover:bg-neutral-50 transition">
                            Batal
                        </button>
                        <button 
                            type="submit" 
                            class="px-4 py-2 rounded-lg text-xs font-bold text-white transition shadow-xs {{ $business->is_suspended ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-rose-600 hover:bg-rose-700' }}"
                        >
                            {{ $business->is_suspended ? 'Ya, Pulihkan Akun UMKM' : 'Ya, Tangguhkan Akun UMKM' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-layouts.admin>

