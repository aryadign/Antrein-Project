@php
    $setupSteps = [
        ['done' => $profileComplete, 'label' => $profileComplete ? 'Profil & kontak lengkap' : 'Lengkapi profil usaha', 'href' => route('merchant.settings', ['tab' => 'profile'])],
        ['done' => $hoursConfigured, 'label' => $hoursConfigured ? 'Jam operasional aktif' : 'Atur jam operasional', 'href' => route('merchant.settings', ['tab' => 'hours'])],
        ['done' => $servicesConfigured, 'label' => $servicesConfigured ? 'Layanan terdaftar ('.$services->count().')' : 'Tambahkan layanan', 'href' => route('merchant.services.index')],
    ];
    $setupDoneCount = collect($setupSteps)->where('done', true)->count();
    $kpiCards = [
        ['label' => 'Total Tiket', 'value' => $stats['total'], 'hint' => 'Hari ini', 'icon' => 'ticket', 'gradient' => 'from-slate-800 via-slate-900 to-brand-950 border-slate-700/40'],
        ['label' => 'Menunggu', 'value' => $stats['menunggu'], 'hint' => 'Dalam antrean', 'icon' => 'clock', 'gradient' => 'from-amber-500 via-amber-600 to-orange-700 border-amber-400/30'],
        ['label' => 'Dipanggil', 'value' => $stats['dipanggil'], 'hint' => 'Menuju loket', 'icon' => 'bell', 'gradient' => 'from-blue-600 via-brand-700 to-indigo-800 border-blue-500/30'],
        ['label' => 'Dilayani', 'value' => $stats['sedang_dilayani'], 'hint' => 'Sedang dilayani', 'icon' => 'play', 'gradient' => 'from-indigo-600 via-indigo-700 to-violet-800 border-indigo-500/30'],
        ['label' => 'Selesai', 'value' => $stats['selesai'], 'hint' => 'Tuntas', 'icon' => 'check', 'gradient' => 'from-emerald-600 via-emerald-700 to-teal-800 border-emerald-500/30'],
        ['label' => 'Tidak Hadir', 'value' => ($stats['tidak_hadir'] ?? 0) + ($stats['dilewati'] ?? 0), 'hint' => 'Dilewati / absen', 'icon' => 'skip', 'gradient' => 'from-slate-600 via-slate-700 to-slate-800 border-slate-500/30'],
        ['label' => 'Dibatalkan', 'value' => $stats['dibatalkan'], 'hint' => 'Kuota dilepas', 'icon' => 'x', 'gradient' => 'from-rose-600 via-rose-700 to-red-800 border-rose-500/30'],
    ];
    $sourceLabel = fn (string $source): string => $source === 'online' ? 'Online' : 'Walk-in';
    $sourceClasses = fn (string $source): string => $source === 'online' ? 'bg-blue-50 text-blue-700 ring-blue-600/20' : 'bg-neutral-100 text-neutral-700 ring-neutral-500/20';
    $qrSvgPreview = $isOperator ? app(\App\Services\QrCodeService::class)->generateSvg(route('customer.queue', $business->slug), 180) : null;
@endphp

<x-layouts.merchant
    :title="$isOwner ? 'Beranda Laporan Gabungan UMKM' : 'Dashboard Operasional UMKM'"
    :businessName="$business->name"
    :businessStatus="$business->status"
    :businessSlug="$business->slug"
>
    <div class="space-y-5 sm:space-y-6" x-data="{ walkInModalOpen: false }">
        {{-- Flash Messages --}}
        @if (session('status'))
            <div class="flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-800 shadow-xs" role="status">
                <svg class="w-5 h-5 shrink-0 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <span>{{ session('status') }}</span>
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800 shadow-xs" role="alert">
                <div class="flex items-start gap-2">
                    <svg class="mt-0.5 w-5 h-5 shrink-0 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <div>
                        <p class="mb-1 font-semibold">Perhatian:</p>
                        <ul class="list-inside list-disc space-y-0.5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        {{-- Suspension Notice (visible to both Owner and Operator) --}}
        @if ($business->is_suspended || (($organizationTotals['suspended_branches_count'] ?? 0) > 0))
            <div class="flex items-start gap-3 rounded-xl border border-rose-300 bg-rose-50 p-4 text-rose-900 shadow-xs sm:p-5" role="alert">
                <span class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-rose-600 text-white">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </span>
                <div>
                    <h3 class="text-sm font-bold text-rose-950">Akun Usaha Ditangguhkan (Suspended)</h3>
                    <p class="mt-1 text-xs text-rose-800">
                        @if ($isOwner && ! $business->is_suspended)
                            Terdapat {{ $organizationTotals['suspended_branches_count'] }} cabang dalam organisasi Anda yang sedang ditangguhkan oleh administrator platform.
                        @else
                            Penerimaan tiket antrean baru diblokir oleh administrator platform. Silakan hubungi tim dukungan AntreIn.
                        @endif
                    </p>
                </div>
            </div>
        @endif

        @if ($isOwner && ! empty($orgReport))
            @php
                $isCustomPeriod = $orgReport['period'] === 'custom';
                $periodOptions = [
                    'today' => 'Hari Ini',
                    '7_days' => '7 Hari',
                    '30_days' => '30 Hari',
                ];
                $dateInputClasses = 'h-11 w-full rounded-lg border-neutral-300 px-3 text-sm focus:border-brand-500 focus:ring-brand-500 sm:w-44';
                $orgTotals = $orgReport['totals'];
                $totalSourceTickets = max(1, ($orgTotals['online_count'] ?? 0) + ($orgTotals['walk_in_count'] ?? 0));
                $onlineSharePercent = ($orgTotals['online_count'] + $orgTotals['walk_in_count']) > 0
                    ? (int) round(($orgTotals['online_count'] / $totalSourceTickets) * 100)
                    : 0;
                $canManageOrg = auth()->user()?->canManageOrganization() ?? false;
            @endphp

            {{-- OWNER BERANDA: Page Header (Laporan-style, zero duplicate CTA) --}}
            <div class="flex flex-col gap-4 border-b border-neutral-200 pb-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <h1 class="truncate text-h2 font-bold text-neutral-950">{{ $organization?->name ?? $business->name }}</h1>
                        <span class="inline-flex items-center rounded-full border border-brand-200 bg-brand-50 px-2.5 py-0.5 text-xs font-bold text-brand-700">
                            Ringkasan Gabungan Organisasi
                        </span>
                    </div>
                    <p class="mt-1 text-sm text-neutral-500">
                        Laporan Gabungan Seluruh Cabang — pantau volume tiket, durasi tunggu, dan performa tiap cabang dalam satu tampilan.
                    </p>
                </div>

                <div class="grid grid-cols-2 gap-2 sm:flex sm:flex-wrap sm:items-center sm:gap-2.5">
                    <a
                        href="{{ route('merchant.reports') }}"
                        class="inline-flex h-11 items-center justify-center gap-2 rounded-lg border border-neutral-300 bg-white px-3.5 text-xs sm:text-sm font-semibold text-neutral-800 shadow-xs transition hover:bg-neutral-50"
                    >
                        <svg class="w-4 h-4 text-neutral-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        <span>Detail &amp; Ekspor Cabang</span>
                    </a>
                    <a
                        href="{{ route('merchant.branches.index') }}"
                        class="inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-brand-700 px-4 text-xs sm:text-sm font-semibold text-white shadow-xs transition hover:bg-brand-800"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        <span>Kelola Cabang</span>
                    </a>
                </div>
            </div>

            {{-- Setup Checklist Banner --}}
            @if (! $isSetupComplete)
                <section class="rounded-xl border border-blue-200 bg-linear-to-r from-blue-50 to-indigo-50 p-4 shadow-xs sm:p-5" aria-labelledby="setup-checklist-heading">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h3 id="setup-checklist-heading" class="text-sm font-bold text-blue-950">Checklist Persiapan Cabang Aktif ({{ $business->name }})</h3>
                            <p class="mt-0.5 text-xs text-blue-800">Lengkapi konfigurasi berikut agar seluruh cabang UMKM Anda siap melayani antrean.</p>
                        </div>
                        <div class="flex items-center gap-3 sm:w-48">
                            <div class="h-2 flex-1 overflow-hidden rounded-full bg-white ring-1 ring-blue-200">
                                <div class="h-full rounded-full bg-blue-600" style="width: {{ round($setupDoneCount / 3 * 100) }}%"></div>
                            </div>
                            <span class="shrink-0 text-xs font-bold text-blue-900 tabular-nums">{{ $setupDoneCount }}/3</span>
                        </div>
                    </div>

                    <ol class="mt-4 grid grid-cols-1 gap-2 sm:grid-cols-3 sm:gap-3">
                        @foreach ($setupSteps as $setupStep)
                            <li>
                                <a href="{{ $setupStep['href'] }}" class="flex min-h-12 items-center gap-2.5 rounded-lg border bg-white p-3 transition hover:shadow-xs {{ $setupStep['done'] ? 'border-emerald-300 text-emerald-800' : 'border-amber-300 text-amber-900' }}">
                                    <span class="flex size-6 shrink-0 items-center justify-center rounded-full text-[11px] font-bold {{ $setupStep['done'] ? 'bg-emerald-600 text-white' : 'bg-amber-100 text-amber-800' }}">
                                        @if ($setupStep['done'])
                                            <x-customer.icon name="check" class="size-3.5" />
                                        @else
                                            {{ $loop->iteration }}
                                        @endif
                                    </span>
                                    <span class="flex-1 text-xs font-semibold">{{ $setupStep['label'] }}</span>
                                    @unless ($setupStep['done'])
                                        <x-customer.icon name="arrow-right" class="size-4 shrink-0" />
                                    @endunless
                                </a>
                            </li>
                        @endforeach
                    </ol>
                </section>
            @endif

            {{-- Period Filter Toolbar (Matches Laporan page UI) --}}
            <div x-data="{ showCustom: @js($isCustomPeriod) }" class="rounded-xl border border-neutral-200 bg-white p-3 shadow-xs sm:p-5">
                <form method="GET" action="{{ route('merchant.dashboard') }}" class="space-y-4">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                        {{-- Quick Period Pills --}}
                        <div class="min-w-0">
                            <p class="mb-2 text-caption font-semibold text-neutral-700">Pilih Periode Ringkasan</p>
                            <div class="grid grid-cols-4 gap-1 rounded-xl border border-neutral-200 bg-neutral-100 p-1 sm:inline-flex">
                                @foreach ($periodOptions as $periodValue => $periodText)
                                    <button
                                        type="submit"
                                        name="period"
                                        value="{{ $periodValue }}"
                                        @if ($orgReport['period'] === $periodValue) aria-current="true" @endif
                                        class="inline-flex min-h-10 items-center justify-center rounded-lg px-2 text-xs font-semibold transition sm:px-3.5 {{ $orgReport['period'] === $periodValue ? 'bg-white text-neutral-900 shadow-xs' : 'text-neutral-600 hover:text-neutral-900' }}"
                                    >
                                        {{ $periodText }}@if ($periodValue !== 'today')<span class="hidden sm:inline">&nbsp;Terakhir</span>@endif
                                    </button>
                                @endforeach
                                <button
                                    type="button"
                                    @click="showCustom = ! showCustom"
                                    :aria-expanded="showCustom.toString()"
                                    class="inline-flex min-h-10 items-center justify-center rounded-lg px-2 text-xs font-semibold transition sm:px-3.5 {{ $isCustomPeriod ? 'bg-brand-700 text-white shadow-xs' : 'text-neutral-600 hover:text-neutral-900' }}"
                                >
                                    Kustom
                                </button>
                            </div>
                        </div>

                        {{-- Scope Metadata Pills --}}
                        <div class="flex flex-wrap items-center gap-2 text-caption text-neutral-500">
                            <span class="inline-flex items-center gap-1 rounded-lg border border-neutral-200 bg-neutral-50 px-2.5 py-1">
                                <svg class="w-3.5 h-3.5 text-neutral-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                <strong class="text-neutral-800 tabular-nums">{{ $orgReport['periodLabel'] }}</strong>
                            </span>
                            <span class="inline-flex items-center gap-1 rounded-lg border border-neutral-200 bg-neutral-50 px-2.5 py-1">
                                <svg class="w-3.5 h-3.5 text-neutral-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5"/></svg>
                                <span><strong class="text-emerald-700">{{ $orgTotals['open_branches_count'] }}</strong> / {{ $orgTotals['branches'] }} Cabang Buka</span>
                            </span>
                            <span class="inline-flex items-center gap-1 rounded-lg border border-neutral-200 bg-neutral-50 px-2.5 py-1">
                                <svg class="w-3.5 h-3.5 text-neutral-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <span>Zona Waktu: <strong class="text-neutral-800">{{ $orgReport['timezone'] }}</strong></span>
                            </span>
                        </div>
                    </div>

                    {{-- Custom Date Range Drawer --}}
                    <div
                        x-show="showCustom"
                        @if (! $isCustomPeriod) style="display: none;" @endif
                        class="grid grid-cols-2 gap-3 border-t border-neutral-200 pt-4 sm:flex sm:flex-row sm:items-end"
                    >
                        <input type="hidden" name="period" value="custom" @disabled(! $isCustomPeriod) :disabled="! showCustom">

                        <div class="min-w-0">
                            <label for="owner_start_date" class="mb-1 block text-caption font-semibold text-neutral-700">Tanggal Mulai</label>
                            <input type="date" id="owner_start_date" name="start_date" value="{{ $orgReport['startDate'] }}" required @disabled(! $isCustomPeriod) :disabled="! showCustom" class="{{ $dateInputClasses }}">
                        </div>

                        <div class="min-w-0">
                            <label for="owner_end_date" class="mb-1 block text-caption font-semibold text-neutral-700">Tanggal Selesai</label>
                            <input type="date" id="owner_end_date" name="end_date" value="{{ $orgReport['endDate'] }}" required @disabled(! $isCustomPeriod) :disabled="! showCustom" class="{{ $dateInputClasses }}">
                        </div>

                        <div class="col-span-2 grid grid-cols-2 gap-2 sm:flex">
                            <button type="submit" class="inline-flex h-11 items-center justify-center rounded-lg bg-neutral-900 px-4 text-xs font-bold text-white shadow-xs transition hover:bg-neutral-800">
                                Terapkan Rentang
                            </button>
                            <a href="{{ route('merchant.dashboard') }}" class="inline-flex h-11 items-center justify-center rounded-lg border border-neutral-300 px-3 text-xs font-medium text-neutral-600 transition hover:bg-neutral-50">
                                Reset ke 7 Hari
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            {{-- 6 Key Metrics Cards Strip (Matches Laporan page UI) --}}
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-6">
                <div class="bg-white rounded-xl border border-neutral-200 min-w-0 p-3.5 shadow-xs sm:p-4">
                    <span class="text-caption font-semibold text-neutral-500 block truncate">Total Tiket Dibuat</span>
                    <span class="text-2xl font-extrabold text-neutral-950 tabular-nums block mt-1">
                        {{ number_format($orgTotals['total_tickets']) }}
                    </span>
                    <span class="text-[11px] text-neutral-400 mt-0.5 block truncate">
                        Gabungan {{ $orgTotals['branches'] }} cabang
                    </span>
                </div>

                <div class="bg-emerald-50/40 rounded-xl border border-emerald-200 min-w-0 p-3.5 shadow-xs sm:p-4">
                    <span class="text-caption font-semibold text-emerald-800 block truncate">Selesai Dilayani</span>
                    <span class="text-2xl font-extrabold text-emerald-950 tabular-nums block mt-1">
                        {{ number_format($orgTotals['completed_count']) }}
                    </span>
                    <span class="text-[11px] text-emerald-700 mt-0.5 block truncate">
                        {{ $orgTotals['completion_rate'] }}% tingkat penyelesaian
                    </span>
                </div>

                <div class="bg-orange-50/40 rounded-xl border border-orange-200 min-w-0 p-3.5 shadow-xs sm:p-4">
                    <span class="text-caption font-semibold text-orange-800 block truncate">Dilewati</span>
                    <span class="text-2xl font-extrabold text-orange-950 tabular-nums block mt-1">
                        {{ number_format($orgTotals['skipped_count']) }}
                    </span>
                    <span class="text-[11px] text-orange-700 mt-0.5 block truncate">
                        Pelanggan tidak hadir
                    </span>
                </div>

                <div class="bg-neutral-100/60 rounded-xl border border-neutral-300 min-w-0 p-3.5 shadow-xs sm:p-4">
                    <span class="text-caption font-semibold text-neutral-600 block truncate">Dibatalkan</span>
                    <span class="text-2xl font-extrabold text-neutral-800 tabular-nums block mt-1">
                        {{ number_format($orgTotals['cancelled_count']) }}
                    </span>
                    <span class="text-[11px] text-neutral-500 mt-0.5 block truncate">
                        Batal oleh pihak terkait
                    </span>
                </div>

                <div class="bg-blue-50/40 rounded-xl border border-blue-200 min-w-0 p-3.5 shadow-xs sm:p-4">
                    <span class="text-caption font-semibold text-blue-800 block truncate">Rata-rata Menunggu</span>
                    @if ($orgTotals['avg_waiting_minutes'] !== null)
                        <span class="text-2xl font-extrabold text-blue-950 tabular-nums block mt-1">
                            {{ $orgTotals['avg_waiting_minutes'] }} <span class="text-sm font-bold text-blue-700">mnt</span>
                        </span>
                        <span class="text-[11px] text-blue-700 mt-0.5 block truncate">
                            Dari {{ $orgTotals['waiting_samples'] }} sampel tiket
                        </span>
                    @else
                        <span class="text-sm font-bold text-neutral-500 block mt-2">Belum cukup data</span>
                        <span class="text-[11px] text-neutral-400 mt-0.5 block truncate">Belum ada tiket dilayani</span>
                    @endif
                </div>

                <div class="bg-indigo-50/40 rounded-xl border border-indigo-200 min-w-0 p-3.5 shadow-xs sm:p-4">
                    <span class="text-caption font-semibold text-indigo-800 block truncate">Rata-rata Pelayanan</span>
                    @if ($orgTotals['avg_service_minutes'] !== null)
                        <span class="text-2xl font-extrabold text-indigo-950 tabular-nums block mt-1">
                            {{ $orgTotals['avg_service_minutes'] }} <span class="text-sm font-bold text-indigo-700">mnt</span>
                        </span>
                        <span class="text-[11px] text-indigo-700 mt-0.5 block truncate">
                            Dari {{ $orgTotals['service_samples'] }} tiket selesai
                        </span>
                    @else
                        <span class="text-sm font-bold text-neutral-500 block mt-2">Belum cukup data</span>
                        <span class="text-[11px] text-neutral-400 mt-0.5 block truncate">Belum ada tiket selesai</span>
                    @endif
                </div>
            </div>

            {{-- Peak-Hour Insight Strip (Matches Laporan page UI) --}}
            <div class="rounded-xl p-4 border shadow-xs {{ $orgReport['peakHour']['threshold_met'] ? 'bg-amber-50/70 border-amber-200 text-amber-900' : 'bg-neutral-50 border-neutral-200 text-neutral-700' }}">
                <div class="flex items-start gap-3">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0 {{ $orgReport['peakHour']['threshold_met'] ? 'bg-amber-100 text-amber-700' : 'bg-neutral-200 text-neutral-500' }}">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        @if ($orgReport['peakHour']['threshold_met'])
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1.5">
                                <h4 class="font-bold text-sm text-amber-950">
                                    Jam Ramai Gabungan Teridentifikasi: {{ $orgReport['peakHour']['peak_range'] }}
                                </h4>
                                <span class="inline-flex w-fit items-center px-2 py-0.5 rounded text-xs font-bold bg-amber-200/80 text-amber-900 tabular-nums">
                                    {{ number_format($orgReport['peakHour']['max_count']) }} Tiket dalam 1 Jam
                                </span>
                            </div>
                            <p class="text-caption text-amber-800 mt-0.5">
                                Berdasarkan histori pendaftaran tiket seluruh cabang, rentang jam ini mencatat beban antrean tertinggi di atas ambang {{ $orgReport['peakHour']['threshold'] }} tiket.
                            </p>
                        @else
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1">
                                <h4 class="font-bold text-sm text-neutral-800">
                                    Analisis Jam Ramai Gabungan (Peak Hour)
                                </h4>
                                <span class="text-caption text-neutral-500 font-medium">
                                    Ambang Minimum: {{ $orgReport['peakHour']['threshold'] }} Tiket/Jam
                                </span>
                            </div>
                            <p class="text-caption text-neutral-600 mt-0.5">
                                @if ($orgReport['peakHour']['max_count'] > 0)
                                    Beban antrean tertinggi gabungan tercatat pada jam <strong>{{ $orgReport['peakHour']['top_hour_formatted'] }}</strong> sebanyak <strong>{{ $orgReport['peakHour']['max_count'] }} tiket</strong> pada periode terpilih.
                                @else
                                    Belum ada data tiket yang cukup untuk menganalisis kepadatan jam operasional pada periode terpilih.
                                @endif
                            </p>
                        @endif
                    </div>
                </div>
            </div>

            {{-- 3 Complementary Executive Insights (Gross Revenue, Channel Mix & Satisfaction) --}}
            <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                <div class="rounded-xl border border-emerald-200 bg-emerald-50/40 p-4 shadow-xs">
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-emerald-800">Estimasi Pemasukan Kotor</span>
                        <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold text-emerald-800">Selesai &times; Harga</span>
                    </div>
                    <p class="mt-2 text-2xl font-extrabold text-emerald-950 tabular-nums">
                        Rp {{ number_format($orgTotals['gross_revenue'], 0, ',', '.') }}
                    </p>
                    <p class="mt-1 text-xs text-emerald-800/80">
                        Dari <strong class="tabular-nums">{{ number_format($orgTotals['completed_count']) }}</strong> tiket selesai &times; harga produk/jasa terdaftar.
                    </p>
                    <a
                        href="{{ route('merchant.reports', array_filter(['period' => $orgReport['period'], 'start_date' => $isCustomPeriod ? $orgReport['startDate'] : null, 'end_date' => $isCustomPeriod ? $orgReport['endDate'] : null])) }}"
                        class="mt-1.5 inline-block text-xs font-semibold text-emerald-800 hover:underline"
                    >
                        Rincian Harga Per Layanan &rarr;
                    </a>
                </div>

                <div class="rounded-xl border border-neutral-200 bg-white p-4 shadow-xs">
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-neutral-500">Kanal Kedatangan</span>
                        <span class="text-xs font-bold text-brand-700 tabular-nums">{{ $onlineSharePercent }}% Online</span>
                    </div>
                    <div class="mt-2.5 flex h-2 w-full overflow-hidden rounded-full bg-neutral-200">
                        <div class="h-full bg-brand-600" style="width: {{ $onlineSharePercent }}%"></div>
                    </div>
                    <div class="mt-2 flex items-center justify-between text-xs text-neutral-600">
                        <span>Online: <strong class="text-neutral-900 tabular-nums">{{ number_format($orgTotals['online_count']) }}</strong></span>
                        <span>Walk-in: <strong class="text-neutral-900 tabular-nums">{{ number_format($orgTotals['walk_in_count']) }}</strong></span>
                    </div>
                </div>

                <div class="rounded-xl border border-neutral-200 bg-white p-4 shadow-xs">
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-neutral-500">Kepuasan Pelanggan</span>
                        <span class="inline-flex items-center gap-1 text-lg font-extrabold text-amber-600 tabular-nums">
                            <svg class="size-4 fill-amber-400 text-amber-500" viewBox="0 0 20 20" aria-hidden="true"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                            {{ $orgTotals['avg_rating'] !== null ? number_format($orgTotals['avg_rating'], 1) : '—' }}
                        </span>
                    </div>
                    <p class="mt-2.5 text-xs text-neutral-500">
                        Dihitung dari <strong class="text-neutral-800 tabular-nums">{{ number_format($orgTotals['reviews_count']) }}</strong> ulasan terverifikasi lintas cabang.
                    </p>
                    <a href="{{ route('merchant.reviews.index') }}" class="mt-1 inline-block text-xs font-semibold text-brand-700 hover:underline">
                        Lihat Semua Ulasan &rarr;
                    </a>
                </div>
            </div>

            {{-- Visual Analytics Grid (Matches Laporan page Chart.js cards) --}}
            <div class="grid grid-cols-1 gap-5 lg:grid-cols-3 lg:gap-6">
                <div class="min-w-0 lg:col-span-2 bg-white rounded-xl border border-neutral-200 p-4 shadow-xs sm:p-5">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-h3 font-bold text-neutral-900">Tren Volume Antrean Harian</h3>
                            <p class="text-caption text-neutral-500 mt-0.5">Jumlah tiket gabungan seluruh cabang pada periode terpilih.</p>
                        </div>
                    </div>

                    @if ($orgReport['hasData'])
                        <div class="relative h-64 w-full">
                            <canvas id="queueTrendChart"></canvas>
                        </div>
                    @else
                        <div class="h-64 flex flex-col items-center justify-center text-center p-6 bg-neutral-50/50 rounded-xl border border-neutral-100">
                            <svg class="w-10 h-10 text-neutral-300 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                            <p class="font-semibold text-neutral-700 text-sm">Belum Ada Data Antrean</p>
                            <p class="text-caption text-neutral-400 mt-0.5 max-w-xs">Tidak ada tiket antrean yang dibuat pada periode ini. Coba pilih periode tanggal lain.</p>
                        </div>
                    @endif
                </div>

                <div class="min-w-0 bg-white rounded-xl border border-neutral-200 p-4 shadow-xs sm:p-5">
                    <div class="mb-4">
                        <h3 class="text-h3 font-bold text-neutral-900">Distribusi Status Antrean</h3>
                        <p class="text-caption text-neutral-500 mt-0.5">Komposisi penyelesaian tiket gabungan periode ini.</p>
                    </div>

                    @if ($orgReport['hasData'])
                        <div class="relative h-64 w-full flex items-center justify-center">
                            <canvas id="statusDistributionChart"></canvas>
                        </div>
                    @else
                        <div class="h-64 flex flex-col items-center justify-center text-center p-6 bg-neutral-50/50 rounded-xl border border-neutral-100">
                            <svg class="w-10 h-10 text-neutral-300 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/></svg>
                            <p class="font-semibold text-neutral-700 text-sm">Tidak Ada Tiket</p>
                            <p class="text-caption text-neutral-400 mt-0.5">Grafik distribusi akan muncul setelah terdapat tiket diterbitkan.</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Per-Branch Breakdown Table & Mobile Cards (Matches Laporan page UI) --}}
            <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden shadow-xs">
                <div class="p-4 sm:p-5 border-b border-neutral-200 flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <h3 class="text-h3 font-bold text-neutral-900">Rincian Performa Per Cabang</h3>
                        <p class="text-caption text-neutral-500 mt-0.5">Perbandingan volume, estimasi pemasukan kotor, durasi tunggu, kecepatan layanan, dan rating antar cabang pada periode {{ $orgReport['periodLabel'] }}.</p>
                    </div>
                    <span class="rounded-full bg-neutral-100 px-3 py-1 text-xs font-semibold text-neutral-700">{{ $orgTotals['branches'] }} Cabang</span>
                </div>

                {{-- Desktop Table View --}}
                <div class="hidden lg:block overflow-x-auto">
                    <table class="w-full text-left border-collapse text-sm">
                        <thead>
                            <tr class="border-b border-neutral-200 bg-neutral-50/75 text-neutral-600 font-semibold text-xs tracking-wider uppercase">
                                <th class="py-3.5 px-4">Nama Cabang</th>
                                <th class="py-3.5 px-4">Status</th>
                                <th class="py-3.5 px-4 text-center">Total Tiket</th>
                                <th class="py-3.5 px-4 text-center">Selesai</th>
                                <th class="py-3.5 px-4 text-center">Dilewati</th>
                                <th class="py-3.5 px-4 text-center">Dibatalkan</th>
                                <th class="py-3.5 px-4 text-right">Pemasukan Kotor</th>
                                <th class="py-3.5 px-4 text-right">Rata-rata Tunggu</th>
                                <th class="py-3.5 px-4 text-right">Rata-rata Layanan</th>
                                <th class="py-3.5 px-4 text-center">Rating</th>
                                <th class="py-3.5 px-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-200">
                            @forelse ($orgReport['rows'] as $row)
                                <tr class="hover:bg-neutral-50/50 transition">
                                    <td class="py-3.5 px-4">
                                        <div class="flex items-center gap-1.5">
                                            <span class="font-bold text-neutral-900">{{ $row['branch_name'] }}</span>
                                            @if ($row['branch_id'] === $business->id)
                                                <span class="rounded bg-brand-50 px-1.5 py-0.5 text-[10px] font-bold text-brand-700">Aktif</span>
                                            @endif
                                        </div>
                                        <p class="mt-0.5 text-[11px] text-neutral-500">
                                            {{ $row['area_label'] ? $row['area_label'].' | ' : '' }}{{ $row['active_counters_count'] }} loket | {{ $row['active_services_count'] }} layanan
                                        </p>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <div class="flex flex-wrap items-center gap-1">
                                            @if ($row['is_suspended'])
                                                <x-status-badge status="suspended" size="sm" />
                                            @endif
                                            <x-status-badge :status="$row['status']" size="sm" />
                                        </div>
                                    </td>
                                    <td class="py-3.5 px-4 text-center font-bold text-neutral-900 tabular-nums">{{ number_format($row['total_tickets']) }}</td>
                                    <td class="py-3.5 px-4 text-center font-semibold text-emerald-700 tabular-nums">{{ number_format($row['completed_count']) }}</td>
                                    <td class="py-3.5 px-4 text-center font-medium text-orange-700 tabular-nums">{{ number_format($row['skipped_count']) }}</td>
                                    <td class="py-3.5 px-4 text-center text-neutral-500 tabular-nums">{{ number_format($row['cancelled_count']) }}</td>
                                    <td class="py-3.5 px-4 text-right font-bold text-emerald-800 tabular-nums whitespace-nowrap">
                                        Rp {{ number_format($row['gross_revenue'], 0, ',', '.') }}
                                    </td>
                                    <td class="py-3.5 px-4 text-right font-semibold text-neutral-800 tabular-nums whitespace-nowrap">
                                        {{ $row['avg_waiting_minutes'] !== null ? $row['avg_waiting_minutes'].' mnt' : '—' }}
                                    </td>
                                    <td class="py-3.5 px-4 text-right font-semibold text-neutral-800 tabular-nums whitespace-nowrap">
                                        {{ $row['avg_service_minutes'] !== null ? $row['avg_service_minutes'].' mnt' : '—' }}
                                    </td>
                                    <td class="py-3.5 px-4 text-center tabular-nums whitespace-nowrap">
                                        @if ($row['avg_rating'] !== null)
                                            <span class="font-bold text-amber-700">{{ number_format($row['avg_rating'], 1) }} ★</span>
                                            <span class="text-[11px] text-neutral-400">({{ $row['reviews_count'] }})</span>
                                        @else
                                            <span class="text-xs text-neutral-400">Belum ada</span>
                                        @endif
                                    </td>
                                    <td class="py-3.5 px-4 text-right">
                                        <form method="POST" action="{{ route('merchant.branches.switch') }}">
                                            @csrf
                                            <input type="hidden" name="business_id" value="{{ $row['branch_id'] }}">
                                            <button type="submit" class="inline-flex h-9 items-center whitespace-nowrap rounded-lg border border-brand-200 bg-brand-50 px-3 text-xs font-bold text-brand-700 transition hover:bg-brand-100">
                                                Kelola Cabang Ini &rarr;
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="11" class="py-8 text-center text-caption text-neutral-400">
                                        Belum ada cabang terdaftar dalam organisasi ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Mobile Card View --}}
                <div class="lg:hidden divide-y divide-neutral-200">
                    @forelse ($orgReport['rows'] as $row)
                        <div class="p-4 space-y-3">
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-1.5">
                                        <h4 class="truncate text-sm font-bold text-neutral-950">{{ $row['branch_name'] }}</h4>
                                        @if ($row['branch_id'] === $business->id)
                                            <span class="shrink-0 rounded bg-brand-50 px-1.5 py-0.5 text-[10px] font-bold text-brand-700">Aktif</span>
                                        @endif
                                    </div>
                                    <p class="mt-0.5 text-[11px] text-neutral-500">
                                        {{ $row['area_label'] ? $row['area_label'].' | ' : '' }}{{ $row['active_counters_count'] }} loket | {{ $row['active_services_count'] }} layanan
                                    </p>
                                </div>
                                <div class="flex shrink-0 items-center gap-1">
                                    @if ($row['is_suspended'])
                                        <x-status-badge status="suspended" size="sm" />
                                    @endif
                                    <x-status-badge :status="$row['status']" size="sm" />
                                </div>
                            </div>

                            <div class="grid grid-cols-4 gap-2 text-center text-xs tabular-nums bg-neutral-50/75 p-2 rounded-lg border border-neutral-200/60">
                                <div>
                                    <span class="text-neutral-400 block text-[10px]">Total</span>
                                    <span class="font-bold text-neutral-900">{{ $row['total_tickets'] }}</span>
                                </div>
                                <div>
                                    <span class="text-emerald-600 block text-[10px]">Selesai</span>
                                    <span class="font-bold text-emerald-800">{{ $row['completed_count'] }}</span>
                                </div>
                                <div>
                                    <span class="text-orange-600 block text-[10px]">Lewati</span>
                                    <span class="font-bold text-orange-800">{{ $row['skipped_count'] }}</span>
                                </div>
                                <div>
                                    <span class="text-neutral-400 block text-[10px]">Batal</span>
                                    <span class="font-bold text-neutral-700">{{ $row['cancelled_count'] }}</span>
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center justify-between gap-2 text-xs text-neutral-600">
                                <span>Pemasukan: <strong class="text-emerald-800 tabular-nums">Rp {{ number_format($row['gross_revenue'], 0, ',', '.') }}</strong></span>
                                <span>Tunggu: <strong class="text-neutral-900">{{ $row['avg_waiting_minutes'] !== null ? $row['avg_waiting_minutes'].' mnt' : '—' }}</strong></span>
                                <span>Layanan: <strong class="text-neutral-900">{{ $row['avg_service_minutes'] !== null ? $row['avg_service_minutes'].' mnt' : '—' }}</strong></span>
                                <span>Rating: <strong class="text-amber-700">{{ $row['avg_rating'] !== null ? number_format($row['avg_rating'], 1).' ★' : 'Belum ada' }}</strong></span>
                            </div>

                            <form method="POST" action="{{ route('merchant.branches.switch') }}">
                                @csrf
                                <input type="hidden" name="business_id" value="{{ $row['branch_id'] }}">
                                <button type="submit" class="inline-flex h-10 w-full items-center justify-center rounded-lg border border-brand-200 bg-brand-50 px-3 text-xs font-bold text-brand-700 transition hover:bg-brand-100">
                                    Kelola Cabang Ini &rarr;
                                </button>
                            </form>
                        </div>
                    @empty
                        <div class="py-8 text-center text-caption text-neutral-400">
                            Belum ada cabang terdaftar dalam organisasi ini.
                        </div>
                    @endforelse
                </div>
            </div>
        @else
            {{-- OPERATOR BERANDA: Single-Branch Operational Controls + Layar TV --}}
            @if ($business->status === 'tutup')
                <div class="flex flex-col gap-3 rounded-xl border border-neutral-300 bg-neutral-100 p-4 text-neutral-800 shadow-xs sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-start gap-3">
                        <span class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-neutral-800 text-white">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><rect x="5" y="11" width="14" height="10" rx="2" stroke-width="2"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0v4"/></svg>
                        </span>
                        <div>
                            <h4 class="text-sm font-bold text-neutral-900">Operasional Antrean Sedang TUTUP</h4>
                            <p class="text-xs text-neutral-600">Pelanggan tidak dapat mengambil nomor tiket online hari ini.</p>
                        </div>
                    </div>
                </div>
            @elseif ($business->status === 'jeda')
                <div class="flex flex-col gap-3 rounded-xl border border-amber-300 bg-amber-50 p-4 text-amber-900 shadow-xs sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-start gap-3">
                        <span class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-amber-500 text-white">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </span>
                        <div>
                            <h4 class="text-sm font-bold text-amber-950">Operasional Antrean Sedang DIJEDA</h4>
                            <p class="text-xs text-amber-800">Pendaftaran online baru ditahan sementara. Tiket berjalan tetap dapat dilayani hingga selesai.</p>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Page Header --}}
            <div class="flex flex-col gap-4 border-b border-neutral-200 pb-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <h1 class="truncate text-h2 font-bold text-neutral-950">{{ $business->name }}</h1>
                        <x-status-badge :status="$business->status" size="sm" />
                    </div>
                    <p class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-0.5 text-sm text-neutral-500">
                        <svg class="w-4 h-4 text-neutral-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <span>Hari ini: <strong class="text-neutral-800">{{ \Carbon\Carbon::parse($operatingDate)->translatedFormat('l, d F Y') }}</strong></span>
                        <span class="rounded-md bg-neutral-100 px-1.5 py-0.5 text-[11px] font-medium text-neutral-600">{{ $timezone }}</span>
                    </p>
                </div>

                {{-- Operator Header Actions --}}
                <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">
                    @if ($business->usesStations() && $availableCounters->isNotEmpty())
                        <form method="POST" action="{{ route('merchant.queues.select-counter') }}" class="flex items-center gap-2 rounded-lg border border-neutral-300 bg-neutral-100 p-1">
                            @csrf
                            <label for="header_counter_select" class="flex shrink-0 items-center gap-1 pl-2 text-xs font-bold text-neutral-600">
                                <svg class="w-3.5 h-3.5 text-neutral-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                Loket Saya
                            </label>
                            <select
                                id="header_counter_select"
                                name="counter_id"
                                onchange="this.form.submit()"
                                class="h-10 min-w-0 flex-1 rounded-md border-neutral-300 bg-white py-0 pl-2.5 pr-8 text-sm font-semibold text-neutral-900 shadow-2xs focus:border-brand-500 focus:ring-brand-500"
                            >
                                @foreach ($availableCounters as $counterOption)
                                    <option value="{{ $counterOption->id }}" @selected($selectedCounter?->id === $counterOption->id)>
                                        {{ $counterOption->name }} ({{ $counterOption->code }})
                                    </option>
                                @endforeach
                            </select>
                        </form>
                    @elseif (! $business->usesStations())
                        <span class="inline-flex h-10 items-center gap-1.5 rounded-lg border border-neutral-200 bg-neutral-100 px-3 text-xs font-bold text-neutral-700">
                            <svg class="size-3.5 text-brand-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            1 Station Utama
                        </span>
                    @endif

                    <div class="grid grid-cols-2 gap-2 sm:flex sm:gap-3">
                        <button
                            type="button"
                            @click="walkInModalOpen = true"
                            class="inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-neutral-900 px-4 text-sm font-semibold text-white shadow-xs transition hover:bg-neutral-800 focus:ring-2 focus:ring-neutral-900 focus:ring-offset-2"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            <span>Tambah Walk-in</span>
                        </button>

                        <a
                            href="{{ route('merchant.queues') }}"
                            class="inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-brand-700 px-4 text-sm font-semibold text-white shadow-xs transition hover:bg-brand-800 focus:ring-2 focus:ring-brand-700 focus:ring-offset-2"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                            <span>Meja Antrean</span>
                        </a>

                        <a
                            href="{{ route('merchant.display.index') }}"
                            class="col-span-2 inline-flex h-11 items-center justify-center gap-2 rounded-lg border border-neutral-300 bg-white px-4 text-sm font-semibold text-neutral-800 shadow-xs transition hover:bg-neutral-50 sm:col-span-1"
                        >
                            <svg class="w-4 h-4 text-brand-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            <span>Layar TV</span>
                        </a>
                    </div>
                </div>
            </div>

            {{-- 7 Queue Operational Metric Cards --}}
            <section aria-labelledby="kpi-heading">
                <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                    <h2 id="kpi-heading" class="text-h3 font-bold text-neutral-900">Statistik Antrean Hari Ini ({{ $business->name }})</h2>
                    <span class="rounded-md bg-neutral-100 px-2 py-1 text-xs font-medium text-neutral-600">Nomor terakhir <strong class="text-neutral-900 tabular-nums">#{{ $dailyQueue->current_number }}</strong></span>
                </div>

                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-7">
                    @foreach ($kpiCards as $kpi)
                        <div class="min-w-0 rounded-xl border bg-linear-to-br p-3.5 text-white shadow-sm sm:p-4 {{ $kpi['gradient'] }} {{ $loop->first ? 'col-span-2 sm:col-span-1' : '' }}">
                            <div class="flex items-start justify-between gap-1.5">
                                <span class="truncate text-caption font-semibold text-white/85">{{ $kpi['label'] }}</span>
                                <span class="flex size-6 shrink-0 items-center justify-center rounded-lg bg-white/15 text-white ring-1 ring-white/20" aria-hidden="true">
                                    @switch($kpi['icon'])
                                        @case('clock')
                                            <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            @break
                                        @case('bell')
                                            <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                                            @break
                                        @case('play')
                                            <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                            @break
                                        @case('check')
                                            <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                            @break
                                        @case('skip')
                                            <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            @break
                                        @case('x')
                                            <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                            @break
                                        @default
                                            <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg>
                                    @endswitch
                                </span>
                            </div>
                            <span class="mt-1.5 block text-2xl font-extrabold text-white tabular-nums">{{ $kpi['value'] }}</span>
                            <span class="mt-0.5 block truncate text-[11px] text-white/75">{{ $kpi['hint'] }}</span>
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- Operational Grid: Now Serving & Right Column --}}
            <div class="grid grid-cols-1 gap-5 lg:grid-cols-3 lg:gap-6">
                <div class="min-w-0 space-y-5 lg:col-span-2 lg:space-y-6">
                    {{-- Now Serving Box --}}
                    <section class="rounded-xl border-2 p-4 shadow-xs sm:p-6 {{ $nowServing ? 'border-brand-300 bg-linear-to-b from-brand-50/40 to-white' : 'border-neutral-200 bg-white' }}" aria-labelledby="dashboard-now-serving">
                        <div class="mb-5 flex flex-wrap items-center justify-between gap-2 border-b border-neutral-200 pb-4">
                            <div class="flex min-w-0 items-center gap-2.5">
                                <span class="flex size-8 shrink-0 items-center justify-center rounded-lg {{ $nowServing ? 'bg-brand-700 text-white' : 'bg-neutral-100 text-neutral-500' }}">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
                                </span>
                                <h3 id="dashboard-now-serving" class="text-base font-bold text-neutral-900 sm:text-h3">Sedang Berlangsung (Now Serving)</h3>
                            </div>
                            @if ($nowServing)
                                <x-status-badge :status="$nowServing->status" size="md" />
                            @else
                                <span class="rounded-full bg-neutral-100 px-2.5 py-1 text-xs font-medium text-neutral-600">Standby</span>
                            @endif
                        </div>

                        @if ($nowServing)
                            <div class="text-center">
                                <span class="mb-2 inline-flex items-center gap-1.5 rounded-full bg-brand-100 px-3 py-1 text-xs font-bold text-brand-900">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                    {{ $nowServing->counter?->name ?? $selectedCounter?->name ?? 'Loket 1' }}
                                </span>
                                <span class="mb-1 block text-caption font-semibold uppercase tracking-wider text-neutral-500">Tiket Aktif Loket</span>
                                <div class="text-5xl font-extrabold tracking-tight text-neutral-950 tabular-nums">{{ $nowServing->ticket_number }}</div>

                                <p class="mt-3 text-base font-bold text-neutral-900">{{ $nowServing->customer_name }}</p>
                                <div class="mt-2 flex flex-wrap items-center justify-center gap-2 text-sm">
                                    <span class="rounded-full bg-white px-2.5 py-1 font-medium text-neutral-700 ring-1 ring-neutral-200">{{ $nowServing->service->name }}</span>
                                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {{ $sourceClasses($nowServing->source) }}">{{ $sourceLabel($nowServing->source) }}</span>
                                </div>

                                <div class="mt-5 grid grid-cols-1 gap-2 sm:flex sm:flex-wrap sm:items-center sm:justify-center sm:gap-3">
                                    @if ($nowServing->status === 'dipanggil')
                                        <form method="POST" action="{{ route('merchant.queues.start-service', $nowServing) }}" class="sm:order-2">
                                            @csrf
                                            <button type="submit" class="inline-flex h-11 w-full items-center justify-center gap-1.5 rounded-lg bg-brand-700 px-5 text-sm font-bold text-white shadow-xs transition hover:bg-brand-800">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                Mulai Melayani
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('merchant.queues.recall', $nowServing) }}" class="sm:order-1">
                                            @csrf
                                            <button type="submit" class="inline-flex h-11 w-full items-center justify-center gap-1.5 rounded-lg border border-neutral-300 bg-white px-4 text-sm font-semibold text-neutral-700 shadow-xs transition hover:bg-neutral-50">
                                                <svg class="w-4 h-4 text-neutral-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                                Panggil Ulang
                                            </button>
                                        </form>
                                    @elseif ($nowServing->status === 'sedang_dilayani')
                                        <form method="POST" action="{{ route('merchant.queues.complete', $nowServing) }}">
                                            @csrf
                                            <button type="submit" class="inline-flex h-11 w-full items-center justify-center gap-1.5 rounded-lg bg-emerald-600 px-5 text-sm font-bold text-white shadow-xs transition hover:bg-emerald-700">
                                                <x-customer.icon name="check" class="size-4" />
                                                Selesaikan Pelayanan
                                            </button>
                                        </form>
                                    @endif

                                    <a href="{{ route('merchant.queues') }}" class="inline-flex h-11 items-center justify-center gap-1 px-2 text-sm font-semibold text-brand-700 hover:text-brand-800 sm:order-3">
                                        Buka Kontrol Lengkap &rarr;
                                    </a>
                                </div>
                            </div>
                        @else
                            <div class="py-6 text-center sm:py-8">
                                <div class="mx-auto mb-3 flex size-12 items-center justify-center rounded-full bg-neutral-100 text-neutral-400">
                                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </div>
                                <h4 class="text-sm font-bold text-neutral-800">
                                    {{ $selectedCounter ? $selectedCounter->name.' Siap Melayani' : 'Tidak Ada Antrean Aktif' }}
                                </h4>
                                <p class="mx-auto mt-1 max-w-sm text-xs text-neutral-500">
                                    {{ $selectedCounter ? $selectedCounter->name.' saat ini dalam status standby dan siap memanggil antrean berikutnya.' : 'Loket saat ini tidak sedang melayani pelanggan.' }}
                                </p>

                                @if ($stats['menunggu'] > 0)
                                    <form method="POST" action="{{ route('merchant.queues.call-next') }}" class="mt-4">
                                        @csrf
                                        @if ($selectedCounter)
                                            <input type="hidden" name="counter_id" value="{{ $selectedCounter->id }}">
                                        @endif
                                        <button type="submit" class="inline-flex h-11 w-full items-center justify-center gap-2 rounded-lg bg-brand-700 px-5 text-sm font-bold text-white shadow-md transition hover:bg-brand-800 sm:w-auto">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
                                            Panggil Antrean Berikutnya {{ $selectedCounter ? 'ke '.$selectedCounter->name : '(FIFO)' }}
                                        </button>
                                    </form>
                                @endif
                            </div>
                        @endif
                    </section>

                    {{-- Multi-Counter Status Overview Strip --}}
                    @if ($business->usesStations() && $activeCountersStatus->count() > 1)
                        <section class="rounded-xl border border-neutral-200 bg-white p-4 shadow-xs">
                            <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                                <h4 class="text-xs font-bold uppercase tracking-wider text-neutral-600">Status Operasional Seluruh Loket</h4>
                            </div>
                            <ul class="grid grid-cols-1 gap-2 sm:grid-cols-2 sm:gap-3 md:grid-cols-3">
                                @foreach ($activeCountersStatus as $counterStatus)
                                    @php($counterTicket = $counterStatus->activeTicket)
                                    <li class="flex items-center justify-between gap-2 rounded-lg border p-3 {{ $counterTicket ? 'border-brand-200 bg-brand-50/40' : 'border-neutral-200 bg-neutral-50/50' }}">
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-1.5">
                                                <span class="truncate text-xs font-bold text-neutral-900">{{ $counterStatus->name }}</span>
                                                <span class="rounded border border-neutral-200 bg-white px-1 py-0.5 font-mono text-[10px] text-neutral-500">{{ $counterStatus->code }}</span>
                                            </div>
                                            <p class="mt-1 text-[11px] text-neutral-500">
                                                @if ($counterTicket)
                                                    Melayani <strong class="font-mono text-neutral-900">{{ $counterTicket->ticket_number }}</strong>
                                                @else
                                                    <span class="font-medium text-emerald-700">Standby, siap memanggil</span>
                                                @endif
                                            </p>
                                        </div>
                                        @if ($selectedCounter && $selectedCounter->id === $counterStatus->id)
                                            <span class="shrink-0 rounded-full bg-brand-600 px-2 py-0.5 text-[10px] font-semibold text-white">Loket Anda</span>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </section>
                    @endif

                    {{-- Active Tickets Preview --}}
                    <section class="overflow-hidden rounded-xl border border-neutral-200 bg-white shadow-xs">
                        <div class="flex items-center justify-between gap-3 border-b border-neutral-200 p-4 sm:p-5">
                            <div class="min-w-0">
                                <h3 class="text-h3 font-bold text-neutral-900">Antrean Aktif Berjalan</h3>
                                <p class="text-caption text-neutral-500">Maksimal 5 antrean prioritas teratas.</p>
                            </div>
                            <a href="{{ route('merchant.queues') }}" class="inline-flex h-11 shrink-0 items-center px-2 text-sm font-semibold text-brand-700 hover:text-brand-800">
                                Lihat Semua &rarr;
                            </a>
                        </div>

                        @if ($activeTickets->isEmpty())
                            <div class="p-8 text-center text-sm text-neutral-500">
                                Belum ada tiket aktif menunggu giliran saat ini.
                            </div>
                        @else
                            <ul class="divide-y divide-neutral-100">
                                @foreach ($activeTickets as $ticket)
                                    <li>
                                        <a href="{{ route('merchant.queues.show', $ticket) }}" class="flex items-center gap-3 p-4 transition hover:bg-neutral-50/60">
                                            <span class="flex h-12 min-w-12 shrink-0 items-center justify-center rounded-lg bg-neutral-100 px-2 text-sm font-bold text-neutral-900 tabular-nums">
                                                {{ $ticket->ticket_number }}
                                            </span>
                                            <span class="min-w-0 flex-1">
                                                <span class="block truncate text-sm font-semibold text-neutral-900">{{ $ticket->customer_name }}</span>
                                                <span class="mt-0.5 flex flex-wrap items-center gap-1.5 text-xs text-neutral-500">
                                                    <span class="truncate">{{ $ticket->service->name }}</span>
                                                    @if ($ticket->bookingSlotWindowLabel())
                                                        <span class="rounded bg-brand-50 px-1.5 py-0.5 text-[10px] font-semibold text-brand-700 ring-1 ring-inset ring-brand-200">Slot {{ $ticket->bookingSlotWindowLabel() }}</span>
                                                    @endif
                                                    <span class="rounded px-1.5 py-0.5 text-[10px] font-semibold ring-1 ring-inset {{ $sourceClasses($ticket->source) }}">{{ $sourceLabel($ticket->source) }}</span>
                                                </span>
                                            </span>
                                            <x-status-badge :status="$ticket->status" size="sm" class="shrink-0" />
                                            <svg class="hidden w-5 h-5 shrink-0 text-neutral-400 sm:block" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </section>
                </div>

                {{-- Right Column: Layar TV Shortcut & QR Code --}}
                <div class="min-w-0 space-y-5 lg:space-y-6">
                    {{-- Layar TV (Public Display) Shortcut Card --}}
                    <section class="rounded-xl border border-brand-200 bg-linear-to-br from-slate-900 via-brand-900 to-slate-900 p-4 text-white shadow-xs sm:p-5">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <span class="inline-flex items-center rounded-full bg-white/15 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider text-brand-200">
                                    Monitor Ruang Tunggu
                                </span>
                                <h3 class="mt-2 text-h3 font-bold text-white">Layar TV (Public Display)</h3>
                            </div>
                            <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-white/10 text-white">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            </span>
                        </div>
                        <p class="mt-1.5 text-xs text-slate-300">
                            Aktifkan, buka QR layar monitor, atau rotasi akses TV antrean ruang tunggu secara langsung dari tugas operator.
                        </p>
                        <div class="mt-4 flex items-center justify-between gap-3 rounded-lg bg-white/10 px-3 py-2 text-xs">
                            <span class="text-slate-200">Status Display</span>
                            <span class="font-bold {{ $business->isDisplayActive() ? 'text-emerald-300' : 'text-amber-300' }}">
                                {{ $business->isDisplayActive() ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </div>
                        <a href="{{ route('merchant.display.index') }}" class="mt-4 inline-flex h-11 w-full items-center justify-center gap-2 rounded-lg bg-white px-4 text-sm font-bold text-slate-900 transition hover:bg-brand-50">
                            <span>Kelola Layar TV &rarr;</span>
                        </a>
                    </section>

                    {{-- QR Code Card (QR-only, zero URL copy) --}}
                    <section class="rounded-xl border border-neutral-200 bg-white p-4 shadow-xs sm:p-5">
                        <div class="mb-2 flex items-center justify-between gap-2">
                            <h3 class="text-h3 font-bold text-neutral-900">QR Code Antrean</h3>
                            <a href="{{ route('merchant.queues.qr') }}" class="inline-flex h-11 items-center text-sm font-semibold text-brand-700 hover:text-brand-800">Halaman QR &rarr;</a>
                        </div>
                        <p class="mb-4 text-xs text-neutral-500">Pelanggan memindai QR Code ini untuk membuka toko dan mengambil tiket antrean dengan akun terdaftar.</p>

                        <div class="mx-auto mb-4 flex w-44 items-center justify-center rounded-xl border border-neutral-200 bg-white p-3 shadow-2xs [&_svg]:block [&_svg]:h-auto [&_svg]:w-full">
                            {!! $qrSvgPreview !!}
                        </div>

                        <div class="grid grid-cols-1 gap-2">
                            <a href="{{ route('merchant.onboarding.download-qr') }}" class="inline-flex h-11 w-full items-center justify-center gap-2 rounded-lg bg-brand-700 px-4 text-sm font-semibold text-white transition hover:bg-brand-800">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                <span>Unduh Kode QR (SVG)</span>
                            </a>
                            <a href="{{ route('merchant.queues.qr') }}" class="inline-flex h-11 w-full items-center justify-center gap-2 rounded-lg border border-neutral-300 bg-white px-4 text-sm font-semibold text-neutral-800 transition hover:bg-neutral-50">
                                <span>Halaman Cetak QR</span>
                            </a>
                        </div>
                    </section>
                </div>
            </div>

            <x-merchant.walk-in-modal :business="$business" :services="$services" />
        @endif
    </div>

    <x-merchant.realtime-toasts :business-id="$business->id" />

    @if ($isOwner && ! empty($orgReport) && $orgReport['hasData'])
        @push('scripts')
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    if (typeof window.Chart === 'undefined') {
                        console.warn('Chart.js library is not loaded on window.');
                        return;
                    }

                    const trendCanvas = document.getElementById('queueTrendChart');
                    if (trendCanvas) {
                        new window.Chart(trendCanvas, {
                            type: 'line',
                            data: {
                                labels: @json($orgReport['trendLabels']),
                                datasets: [{
                                    label: 'Tiket Antrean',
                                    data: @json($orgReport['trendValues']),
                                    borderColor: '#1E40AF',
                                    backgroundColor: 'rgba(30, 64, 175, 0.08)',
                                    fill: true,
                                    tension: 0.3,
                                    pointBackgroundColor: '#1E40AF',
                                    pointBorderColor: '#ffffff',
                                    pointBorderWidth: 2,
                                    pointRadius: 4,
                                    borderWidth: 2
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: { display: false },
                                    tooltip: {
                                        padding: 10,
                                        cornerRadius: 8,
                                        displayColors: false
                                    }
                                },
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        ticks: { precision: 0, font: { size: 11 } },
                                        grid: { color: 'rgba(229, 231, 235, 0.6)' }
                                    },
                                    x: {
                                        ticks: { font: { size: 11 } },
                                        grid: { display: false }
                                    }
                                }
                            }
                        });
                    }

                    const statusCanvas = document.getElementById('statusDistributionChart');
                    if (statusCanvas) {
                        new window.Chart(statusCanvas, {
                            type: 'doughnut',
                            data: {
                                labels: ['Menunggu', 'Dipanggil', 'Sedang Dilayani', 'Selesai', 'Dilewati', 'Dibatalkan'],
                                datasets: [{
                                    data: [
                                        {{ $orgReport['statusDistribution']['menunggu'] }},
                                        {{ $orgReport['statusDistribution']['dipanggil'] }},
                                        {{ $orgReport['statusDistribution']['sedang_dilayani'] }},
                                        {{ $orgReport['statusDistribution']['selesai'] }},
                                        {{ $orgReport['statusDistribution']['dilewati'] }},
                                        {{ $orgReport['statusDistribution']['dibatalkan'] }}
                                    ],
                                    backgroundColor: [
                                        '#F59E0B',
                                        '#3B82F6',
                                        '#6366F1',
                                        '#10B981',
                                        '#F97316',
                                        '#94A3B8'
                                    ],
                                    borderWidth: 2,
                                    borderColor: '#ffffff'
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        position: 'bottom',
                                        labels: { boxWidth: 12, font: { size: 11 }, padding: 12 }
                                    },
                                    tooltip: {
                                        padding: 10,
                                        cornerRadius: 8
                                    }
                                },
                                cutout: '65%'
                            }
                        });
                    }
                });
            </script>
        @endpush
    @endif
</x-layouts.merchant>
