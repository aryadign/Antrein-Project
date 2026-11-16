@php
    $timezoneLabel = match ($business->timezone) {
        'Asia/Jakarta' => 'WIB',
        'Asia/Jayapura' => 'WIT',
        default => 'WITA',
    };
    $queueStatTiles = [
        ['label' => 'Total Tiket', 'key' => 'total', 'tone' => 'neutral'],
        ['label' => 'Menunggu', 'key' => 'menunggu', 'tone' => 'warning'],
        ['label' => 'Dipanggil', 'key' => 'dipanggil', 'tone' => 'brand'],
        ['label' => 'Dilayani', 'key' => 'sedang_dilayani', 'tone' => 'brand'],
        ['label' => 'Selesai', 'key' => 'selesai', 'tone' => 'success'],
        ['label' => 'Dilewati', 'key' => 'dilewati', 'tone' => 'muted'],
        ['label' => 'Dibatalkan', 'key' => 'dibatalkan', 'tone' => 'danger'],
    ];
@endphp

<x-layouts.admin 
    title="Detail UMKM {{ $business->name }}" 
    heading="Detail Unit Usaha Mitra" 
    description="Informasi profil, konfigurasi layanan, jam buka, dan metrik antrean."
>
    <div class="space-y-5 sm:space-y-6 max-w-5xl mx-auto" x-data="{ suspendModalOpen: false, rejectModalOpen: false }">
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
            <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800 shadow-xs" role="alert">
                <ul class="list-inside list-disc space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Breadcrumb Navigation --}}
        <div>
            <a href="{{ route('admin.businesses.index') }}" class="inline-flex min-h-11 items-center gap-2 text-sm font-semibold text-neutral-600 hover:text-neutral-900 transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                <span>Kembali ke Daftar UMKM</span>
            </a>
        </div>

        {{-- Header Card --}}
        <div class="bg-white rounded-2xl border border-neutral-200 p-4 shadow-xs sm:p-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-neutral-100 pb-5 mb-5">
                <div class="flex min-w-0 items-center gap-4">
                    <div class="w-16 h-16 rounded-xl border border-neutral-200 bg-neutral-100 flex items-center justify-center font-bold text-xl text-brand-700 overflow-hidden shrink-0">
                        @if ($business->logo_path)
                            <img src="{{ $business->getLogoUrl() }}" alt="Logo" class="w-full h-full object-cover">
                        @else
                            {{ strtoupper(substr($business->name, 0, 2)) }}
                        @endif
                    </div>

                    <div class="min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <h1 class="text-h2 font-bold text-neutral-950">{{ $business->name }}</h1>
                            <x-status-badge :status="$business->approval_status" size="sm" />
                            <x-status-badge :status="$business->is_suspended ? 'suspended' : 'aktif'" size="sm" />
                            <x-status-badge :status="$business->status" size="sm" />
                        </div>
                        <dl class="mt-1.5 flex flex-wrap gap-x-3 gap-y-1 text-caption text-neutral-500">
                            <div class="flex items-center gap-1">
                                <dt>Kode Toko:</dt>
                                <dd class="font-mono text-neutral-800">{{ $business->slug }}</dd>
                            </div>
                            <div class="flex items-center gap-1">
                                <dt>Terdaftar:</dt>
                                <dd class="tabular-nums">{{ $business->created_at->format('d F Y') }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>

                {{-- Suspension Action Button --}}
                <div class="shrink-0">
                    <button
                        type="button"
                        @click="suspendModalOpen = true"
                        class="inline-flex h-11 w-full items-center justify-center px-4 rounded-lg sm:w-auto text-xs font-bold text-white transition shadow-xs {{ $business->is_suspended ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-rose-600 hover:bg-rose-700' }}"
                    >
                        {{ $business->is_suspended ? 'Cabut Penangguhan (Pulihkan)' : 'Tangguhkan Akun UMKM' }}
                    </button>
                </div>
            </div>

            {{-- Metadata Grid --}}
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4 lg:gap-4">
                <div class="p-3.5 bg-neutral-50 rounded-xl border border-neutral-200">
                    <span class="text-caption font-semibold text-neutral-500 block">Kategori Usaha</span>
                    <span class="font-bold text-sm text-neutral-900 block mt-0.5">{{ $business->category?->name ?? '—' }}</span>
                </div>

                <div class="p-3.5 bg-neutral-50 rounded-xl border border-neutral-200">
                    <span class="text-caption font-semibold text-neutral-500 block">Pemilik Akun</span>
                    <span class="font-bold text-sm text-neutral-900 block mt-0.5">{{ $business->user?->name ?? '—' }}</span>
                    <span class="text-[11px] text-neutral-500 break-all">{{ $business->user?->email ?? '—' }}</span>
                </div>

                <div class="p-3.5 bg-neutral-50 rounded-xl border border-neutral-200">
                    <span class="text-caption font-semibold text-neutral-500 block">Nomor Kontak</span>
                    <span class="font-bold text-sm text-neutral-900 block mt-0.5 tabular-nums">{{ $business->phone }}</span>
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

        {{-- Verification & Approval Decision Panel --}}
        <section class="rounded-2xl border p-4 shadow-xs sm:p-6 {{ $business->approval_status === 'pending' ? 'border-amber-300 bg-amber-50/50' : ($business->approval_status === 'rejected' ? 'border-rose-300 bg-rose-50/40' : 'border-emerald-200 bg-emerald-50/40') }}">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="space-y-1">
                    <div class="flex items-center gap-2">
                        <h2 class="text-base font-bold text-neutral-950">Status Verifikasi Pendaftaran</h2>
                        <x-status-badge :status="$business->approval_status" size="sm" />
                    </div>
                    @if ($business->approval_status === 'pending')
                        <p class="text-xs text-amber-900">Pendaftaran UMKM ini menunggu keputusan Admin. Pemilik saat ini melihat halaman tunggu.</p>
                    @elseif ($business->approval_status === 'rejected')
                        <p class="text-xs text-rose-900"><strong>Alasan penolakan:</strong> {{ $business->rejection_reason }}</p>
                    @else
                        <p class="text-xs text-emerald-900">
                            UMKM telah disetujui dan tampil untuk pelanggan{{ $business->reviewedBy ? ' oleh '.$business->reviewedBy->name : '' }}{{ $business->reviewed_at ? ' pada '.$business->reviewed_at->format('d M Y H:i') : '' }}.
                        </p>
                    @endif
                </div>

                @if ($business->approval_status !== 'approved')
                    <div class="flex flex-wrap items-center gap-2 shrink-0">
                        <form method="POST" action="{{ route('admin.businesses.approve', $business) }}">
                            @csrf
                            <button type="submit" class="inline-flex h-11 items-center justify-center rounded-lg bg-emerald-600 px-4 text-xs font-bold text-white shadow-xs hover:bg-emerald-700">
                                Terima Pendaftaran (Accept)
                            </button>
                        </form>
                        @if ($business->approval_status === 'pending')
                            <button
                                type="button"
                                @click="rejectModalOpen = true"
                                class="inline-flex h-11 items-center justify-center rounded-lg border border-rose-300 bg-white px-4 text-xs font-bold text-rose-700 hover:bg-rose-50"
                            >
                                Tolak Pendaftaran (Reject)
                            </button>
                        @endif
                    </div>
                @endif
            </div>
        </section>

        {{-- Queue Metrics Summary Grid --}}
        <div>
            <h3 class="text-h3 font-bold text-neutral-900 mb-3">Statistik Antrean Akumulatif UMKM Ini</h3>
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-7">
                @foreach ($queueStatTiles as $tile)
                    <x-stat-tile :label="$tile['label']" :value="number_format($queueStats[$tile['key']])" :tone="$tile['tone']" @class(['col-span-2 sm:col-span-1' => $loop->first]) />
                @endforeach
            </div>
        </div>

        {{-- Operating Hours and Services Layout --}}
        <div class="grid grid-cols-1 gap-5 lg:grid-cols-2 lg:gap-6">
            {{-- Services List --}}
            <div class="bg-white rounded-xl border border-neutral-200 p-4 shadow-xs sm:p-5">
                <div class="flex items-center justify-between mb-4 border-b border-neutral-100 pb-3">
                    <h3 class="text-h3 font-bold text-neutral-900">Daftar Layanan Antrean</h3>
                    <span class="text-caption text-neutral-500">{{ $business->services->count() }} Layanan</span>
                </div>

                @if ($business->services->isEmpty())
                    <p class="text-xs text-neutral-400 py-4 text-center">Belum ada layanan yang didaftarkan.</p>
                @else
                    <div class="divide-y divide-neutral-100">
                        @foreach ($business->services as $srv)
                            <div class="py-3 flex items-start justify-between gap-3">
                                <div class="flex min-w-0 items-start gap-3">
                                    <x-customer.service-thumb :service="$srv" :category="$business->category" class="size-14 shrink-0 rounded-xl border border-neutral-200" />
                                    <div class="min-w-0">
                                        <h4 class="text-sm font-semibold text-neutral-900">{{ $srv->name }}</h4>
                                        @if ($srv->description)
                                            <p class="mt-0.5 line-clamp-2 text-xs text-neutral-500">{{ $srv->description }}</p>
                                        @endif
                                        <div class="mt-1.5 flex flex-wrap gap-1.5 text-[11px] text-neutral-600 tabular-nums">
                                            <span class="rounded bg-neutral-100 px-1.5 py-0.5">Durasi ~{{ $srv->duration_minutes }} mnt</span>
                                            <span class="rounded bg-neutral-100 px-1.5 py-0.5">Kuota {{ $srv->daily_quota ? $srv->daily_quota . ' tiket/hari' : 'tidak terbatas' }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="shrink-0 space-y-1 text-right">
                                    <span class="text-xs font-semibold text-neutral-900 block tabular-nums">Rp {{ number_format($srv->price, 0, ',', '.') }}</span>
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
            <div class="bg-white rounded-xl border border-neutral-200 p-4 shadow-xs sm:p-5">
                <div class="flex items-center justify-between mb-4 border-b border-neutral-100 pb-3">
                    <h3 class="text-h3 font-bold text-neutral-900">Jadwal Operasional Mingguan</h3>
                    <span class="text-caption text-neutral-500">{{ $business->timezone }}</span>
                </div>

                @if ($business->hours->isEmpty())
                    <p class="text-xs text-neutral-400 py-4 text-center">Jadwal operasional belum diatur.</p>
                @else
                    <div class="space-y-2">
                        @foreach ($business->hours as $h)
                            <div class="flex items-center justify-between text-xs py-1.5 border-b border-neutral-50 last:border-b-0">
                                <span class="font-semibold text-neutral-800 w-24">{{ $h->getDayName() }}</span>
                                @if ($h->is_closed)
                                    <span class="text-neutral-400 font-medium">Libur / Tutup</span>
                                @else
                                    <span class="font-mono text-neutral-700 tabular-nums">{{ substr($h->open_time, 0, 5) }} — {{ substr($h->close_time, 0, 5) }} {{ $timezoneLabel }}</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- Recent Audit Activity for this Business --}}
        <div class="bg-white rounded-xl border border-neutral-200 p-4 shadow-xs sm:p-5">
            <h3 class="text-h3 font-bold text-neutral-900 mb-3">Histori Log Aktivitas Terakhir Unit Usaha</h3>
            @if ($recentLogs->isEmpty())
                <p class="text-xs text-neutral-400 py-4 text-center">Belum ada riwayat aktivitas yang tercatat untuk UMKM ini.</p>
            @else
                <div class="divide-y divide-neutral-100">
                    @foreach ($recentLogs as $log)
                        <div class="py-2.5 flex flex-col gap-1 text-xs sm:flex-row sm:items-start sm:justify-between sm:gap-3">
                            <div class="min-w-0">
                                <span class="font-semibold text-neutral-900 block">{{ $log->description }}</span>
                                <div class="mt-1 flex flex-wrap items-center gap-1.5 text-[11px] text-neutral-500">
                                    <span class="rounded bg-neutral-100 px-1.5 py-0.5">Oleh: {{ $log->user?->name ?? 'Sistem' }}</span>
                                    <code class="break-all rounded bg-brand-50 px-1.5 py-0.5 font-mono text-brand-700">{{ $log->action }}</code>
                                </div>
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
            class="fixed inset-0 z-50 overflow-y-auto bg-neutral-950/60 flex items-end justify-center sm:items-center sm:p-4"
            style="display: none;"
            @keydown.escape.window="suspendModalOpen = false"
        >
            <div 
                @click.outside="suspendModalOpen = false"
                class="bg-white rounded-t-2xl sm:rounded-2xl max-w-md w-full p-5 sm:p-6 shadow-2xl border border-neutral-200 relative"
            >
                <div class="flex items-center justify-between gap-3 border-b border-neutral-100 pb-3 mb-4">
                    <h3 class="text-h3 font-bold text-neutral-900">
                        {{ $business->is_suspended ? 'Konfirmasi Pemulihan Akun UMKM' : 'Konfirmasi Penangguhan Akun UMKM' }}
                    </h3>
                    <button type="button" @click="suspendModalOpen = false" class="-mr-2 inline-flex size-11 shrink-0 items-center justify-center rounded-lg text-neutral-400 hover:bg-neutral-100 hover:text-neutral-600" aria-label="Tutup">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
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
                        <label for="suspension-reason" class="block text-caption font-semibold text-neutral-700 mb-1">
                            Alasan Perubahan Status (Opsional, dicatat pada Activity Log)
                        </label>
                        <textarea
                            id="suspension-reason"
                            name="reason"
                            rows="2" 
                            maxlength="500"
                            placeholder="Catatan penangguhan atau pemulihan..."
                            class="w-full rounded-lg border-neutral-300 text-sm focus:border-brand-500 focus:ring-brand-500"
                        ></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-2 pt-3 border-t border-neutral-100 sm:flex sm:justify-end">
                        <button type="button" @click="suspendModalOpen = false" class="inline-flex h-11 items-center justify-center px-4 rounded-lg border border-neutral-300 text-xs font-semibold text-neutral-700 hover:bg-neutral-50 transition">
                            Batal
                        </button>
                        <button 
                            type="submit"
                            class="inline-flex h-11 items-center justify-center px-4 rounded-lg text-xs font-bold text-white transition shadow-xs {{ $business->is_suspended ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-rose-600 hover:bg-rose-700' }}"
                        >
                            {{ $business->is_suspended ? 'Ya, Pulihkan Akun UMKM' : 'Ya, Tangguhkan Akun UMKM' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Reject UMKM Modal --}}
        <div
            x-show="rejectModalOpen"
            class="fixed inset-0 z-50 flex items-end justify-center overflow-y-auto bg-neutral-950/60 sm:items-center sm:p-4"
            style="display: none;"
            @keydown.escape.window="rejectModalOpen = false"
        >
            <div @click.outside="rejectModalOpen = false" class="w-full max-w-md rounded-t-2xl border border-neutral-200 bg-white p-5 shadow-2xl sm:rounded-2xl sm:p-6">
                <div class="mb-4 flex items-center justify-between gap-3 border-b border-neutral-100 pb-3">
                    <h3 class="text-h3 font-bold text-neutral-900">Tolak Pendaftaran UMKM</h3>
                    <button type="button" @click="rejectModalOpen = false" class="-mr-2 inline-flex size-11 items-center justify-center rounded-lg text-neutral-400 hover:bg-neutral-100 hover:text-neutral-600" aria-label="Tutup">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <form action="{{ route('admin.businesses.reject', $business) }}" method="POST" class="space-y-4">
                    @csrf
                    <p class="text-xs text-neutral-600">
                        Berikan alasan penolakan untuk <strong class="text-neutral-900">{{ $business->name }}</strong> agar pemilik UMKM dapat memperbaikinya.
                    </p>
                    <div>
                        <label for="show-rejection-reason" class="mb-1 block text-caption font-semibold text-neutral-700">Alasan Penolakan <span class="text-rose-600">*</span></label>
                        <textarea
                            id="show-rejection-reason"
                            name="rejection_reason"
                            rows="3"
                            required
                            maxlength="500"
                            placeholder="Contoh: Alamat toko kurang lengkap atau nomor telepon belum valid."
                            class="w-full rounded-lg border-neutral-300 text-sm focus:border-brand-500 focus:ring-brand-500"
                        ></textarea>
                    </div>
                    <div class="grid grid-cols-2 gap-2 border-t border-neutral-100 pt-3 sm:flex sm:justify-end">
                        <button type="button" @click="rejectModalOpen = false" class="inline-flex h-11 items-center justify-center rounded-lg border border-neutral-300 px-4 text-xs font-semibold text-neutral-700 hover:bg-neutral-50">
                            Batal
                        </button>
                        <button type="submit" class="inline-flex h-11 items-center justify-center rounded-lg bg-rose-600 px-4 text-xs font-bold text-white shadow-xs hover:bg-rose-700">
                            Tolak Pendaftaran
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-layouts.admin>

