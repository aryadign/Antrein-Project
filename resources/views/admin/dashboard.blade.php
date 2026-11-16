@php
    $periods = [
        'today' => 'Hari Ini',
        '7_days' => '7 Hari',
        '30_days' => '30 Hari',
        'this_month' => 'Bulan Ini',
        'all' => 'Semua',
    ];
    $kpiIconPaths = [
        'store' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
        'clock' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
        'check' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
        'ban' => 'M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636',
        'users' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
        'ticket' => 'M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z',
        'badge' => 'M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z',
    ];
    $kpiCards = [
        ['label' => 'Total UMKM', 'value' => $totalBusinesses, 'hint' => 'Terdaftar di platform', 'icon' => 'store', 'tile' => 'bg-blue-50 text-blue-700', 'value_tone' => 'text-neutral-950'],
        ['label' => 'UMKM Aktif', 'value' => $activeBusinesses, 'hint' => 'Disetujui & aktif', 'icon' => 'check', 'tile' => 'bg-emerald-50 text-emerald-700', 'value_tone' => 'text-emerald-800'],
        ['label' => 'Menunggu Verifikasi', 'value' => $pendingBusinesses, 'hint' => 'Butuh review admin', 'icon' => 'clock', 'tile' => 'bg-amber-50 text-amber-700', 'value_tone' => 'text-amber-800'],
        ['label' => 'Ditangguhkan', 'value' => $suspendedBusinesses, 'hint' => 'Platform suspension', 'icon' => 'ban', 'tile' => 'bg-rose-50 text-rose-700', 'value_tone' => 'text-rose-800'],
        ['label' => 'Total Pengguna', 'value' => $totalUsers, 'hint' => 'Semua akun terdaftar', 'icon' => 'users', 'tile' => 'bg-indigo-50 text-indigo-700', 'value_tone' => 'text-neutral-950'],
        ['label' => 'Total Antrean', 'value' => $totalQueues, 'hint' => 'Semua tiket dibuat', 'icon' => 'ticket', 'tile' => 'bg-amber-50 text-amber-700', 'value_tone' => 'text-neutral-950'],
        ['label' => 'Tiket Selesai', 'value' => $completedQueues, 'hint' => 'Sukses dilayani', 'icon' => 'badge', 'tile' => 'bg-brand-50 text-brand-700', 'value_tone' => 'text-brand-800'],
    ];
    $statusLegend = [
        ['label' => 'Menunggu', 'key' => 'menunggu', 'swatch' => 'bg-amber-500'],
        ['label' => 'Dipanggil', 'key' => 'dipanggil', 'swatch' => 'bg-blue-500'],
        ['label' => 'Dilayani', 'key' => 'sedang_dilayani', 'swatch' => 'bg-indigo-500'],
        ['label' => 'Selesai', 'key' => 'selesai', 'swatch' => 'bg-emerald-500'],
        ['label' => 'Dilewati', 'key' => 'dilewati', 'swatch' => 'bg-neutral-400'],
        ['label' => 'Dibatalkan', 'key' => 'dibatalkan', 'swatch' => 'bg-rose-500'],
    ];
@endphp

<x-layouts.admin
    title="Dashboard Administrator"
    heading="Dashboard Platform AntreIn"
    description="Ringkasan metrik kesehatan sistem, verifikasi pendaftaran UMKM, dan aktivitas antrean lintas usaha."
>
    <div class="space-y-5 sm:space-y-6" x-data="{ rejectModalOpen: false, rejectTargetId: null, rejectTargetName: '' }">
        {{-- Flash Messages --}}
        @if (session('status'))
            <div class="flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-800 shadow-xs" role="status">
                <svg class="w-5 h-5 shrink-0 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <span>{{ session('status') }}</span>
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

        {{-- Period Filter Toolbar (M8-03) --}}
        <div class="flex flex-col gap-3 rounded-xl border border-neutral-200 bg-white p-3 shadow-xs sm:p-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-start gap-2 text-sm text-neutral-600">
                <svg class="mt-0.5 w-5 h-5 shrink-0 text-neutral-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                <span>Filter Periode Analitik: <strong class="font-semibold text-neutral-900">{{ $periodLabel }}</strong> ({{ $timezone }})</span>
            </div>

            <nav class="-mx-3 overflow-x-auto px-3 sm:mx-0 sm:px-0" aria-label="Periode analitik">
                <div class="flex w-max gap-1 rounded-lg bg-neutral-100 p-1">
                    @foreach ($periods as $key => $label)
                        <a
                            href="{{ route('admin.dashboard', ['period' => $key]) }}"
                            @if ($period === $key) aria-current="true" @endif
                            class="inline-flex min-h-10 items-center whitespace-nowrap rounded-md px-3 text-xs font-semibold transition {{ $period === $key ? 'bg-white text-brand-700 shadow-xs' : 'text-neutral-600 hover:text-neutral-900' }}"
                        >
                            {{ $label }}
                        </a>
                    @endforeach
                </div>
            </nav>
        </div>

        {{-- Primary Platform Metric Cards (M8-02) --}}
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 sm:gap-4 2xl:grid-cols-7">
            @foreach ($kpiCards as $card)
                <div class="min-w-0 rounded-xl border border-neutral-200 bg-white p-3.5 shadow-xs sm:p-4">
                    <div class="flex items-center justify-between gap-2">
                        <span class="truncate text-caption font-semibold text-neutral-500">{{ $card['label'] }}</span>
                        <span class="flex size-8 shrink-0 items-center justify-center rounded-lg {{ $card['tile'] }}">
                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $kpiIconPaths[$card['icon']] }}"/></svg>
                        </span>
                    </div>
                    <div class="mt-2 text-2xl font-extrabold tabular-nums sm:text-3xl {{ $card['value_tone'] }}">
                        {{ number_format($card['value']) }}
                    </div>
                    <p class="mt-1 truncate text-[11px] text-neutral-500">{{ $card['hint'] }}</p>
                </div>
            @endforeach
        </div>

        {{-- Pending UMKM Approval Queue --}}
        @if ($pendingApprovalBusinesses->isNotEmpty())
            <section class="overflow-hidden rounded-xl border border-amber-200 bg-white shadow-xs" aria-labelledby="pending-approval-heading">
                <div class="flex flex-col gap-2 border-b border-amber-100 bg-amber-50/70 p-4 sm:flex-row sm:items-center sm:justify-between sm:p-5">
                    <div>
                        <h2 id="pending-approval-heading" class="text-base font-bold text-amber-950">Verifikasi Pendaftaran UMKM Baru</h2>
                        <p class="text-xs text-amber-800">Tinjau data toko sebelum ditampilkan ke pelanggan (Accept / Reject).</p>
                    </div>
                    <a href="{{ route('admin.businesses.index', ['status' => 'pending']) }}" class="text-xs font-bold text-brand-700 hover:underline">
                        Lihat Semua Menunggu ({{ $pendingBusinesses }}) &rarr;
                    </a>
                </div>

                <ul class="divide-y divide-neutral-100">
                    @foreach ($pendingApprovalBusinesses as $pendingBiz)
                        <li class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between sm:px-5">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <a href="{{ route('admin.businesses.show', $pendingBiz) }}" class="text-sm font-bold text-neutral-950 hover:text-brand-700">{{ $pendingBiz->name }}</a>
                                    <x-status-badge status="pending" size="sm" />
                                    @if ($pendingBiz->category)
                                        <span class="rounded-full bg-neutral-100 px-2 py-0.5 text-[11px] font-medium text-neutral-700">{{ $pendingBiz->category->name }}</span>
                                    @endif
                                </div>
                                <p class="mt-1 text-xs text-neutral-600">
                                    Pemilik: <strong>{{ $pendingBiz->user?->name ?? '—' }}</strong> ({{ $pendingBiz->user?->email ?? '—' }}) &middot; {{ $pendingBiz->address }}
                                </p>
                            </div>

                            <div class="flex flex-wrap items-center gap-2 shrink-0">
                                <a href="{{ route('admin.businesses.show', $pendingBiz) }}" class="inline-flex h-10 items-center justify-center rounded-lg border border-neutral-300 bg-white px-3 text-xs font-semibold text-neutral-700 hover:bg-neutral-50">
                                    Detail
                                </a>
                                <form method="POST" action="{{ route('admin.businesses.approve', $pendingBiz) }}">
                                    @csrf
                                    <button type="submit" class="inline-flex h-10 items-center justify-center rounded-lg bg-emerald-600 px-3.5 text-xs font-bold text-white shadow-xs hover:bg-emerald-700">
                                        Terima (Accept)
                                    </button>
                                </form>
                                <button
                                    type="button"
                                    @click="rejectTargetId = {{ $pendingBiz->id }}; rejectTargetName = @js($pendingBiz->name); rejectModalOpen = true;"
                                    class="inline-flex h-10 items-center justify-center rounded-lg border border-rose-200 bg-rose-50 px-3.5 text-xs font-bold text-rose-700 hover:bg-rose-100"
                                >
                                    Tolak (Reject)
                                </button>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        {{-- Charts Section (M8-04, M8-05) --}}
        <div class="grid grid-cols-1 gap-5 lg:grid-cols-3 lg:gap-6">
            {{-- Left 2 Cols: Trend Charts (Pendaftaran UMKM & Antrean) --}}
            <div class="min-w-0 space-y-5 lg:col-span-2 lg:space-y-6">
                {{-- Chart A: Tren Antrean Platform --}}
                <div class="bg-white rounded-xl border border-neutral-200 p-4 shadow-xs sm:p-5">
                    <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                        <div class="min-w-0">
                            <h3 class="text-h3 font-bold text-neutral-900">Tren Pembuatan Tiket Antrean</h3>
                            <p class="text-caption text-neutral-500">Jumlah tiket online dan walk-in yang diterbitkan selama {{ $periodLabel }}.</p>
                        </div>
                        <span class="w-fit shrink-0 text-caption font-bold px-2.5 py-1 rounded-full tabular-nums bg-blue-50 text-brand-700 border border-blue-200">
                            {{ $periodMetrics['tickets_count'] }} Tiket
                        </span>
                    </div>

                    <div class="h-64 relative">
                        <canvas id="queueTrendChart"></canvas>
                    </div>
                </div>

                {{-- Chart B: Tren Pendaftaran UMKM --}}
                <div class="bg-white rounded-xl border border-neutral-200 p-4 shadow-xs sm:p-5">
                    <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                        <div class="min-w-0">
                            <h3 class="text-h3 font-bold text-neutral-900">Pertumbuhan Pendaftaran UMKM</h3>
                            <p class="text-caption text-neutral-500">Penambahan mitra UMKM baru pada platform selama {{ $periodLabel }}.</p>
                        </div>
                        <span class="w-fit shrink-0 text-caption font-bold px-2.5 py-1 rounded-full tabular-nums bg-emerald-50 text-emerald-700 border border-emerald-200">
                            +{{ $periodMetrics['businesses_count'] }} UMKM Baru
                        </span>
                    </div>

                    <div class="h-56 relative">
                        <canvas id="businessTrendChart"></canvas>
                    </div>
                </div>
            </div>

            {{-- Right 1 Col: Status Distribution & Recent Activities --}}
            <div class="min-w-0 space-y-5 lg:space-y-6">
                {{-- Chart C: Distribusi Status Antrean (Doughnut) --}}
                <div class="bg-white rounded-xl border border-neutral-200 p-4 shadow-xs sm:p-5">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-h3 font-bold text-neutral-900">Distribusi Status Antrean</h3>
                        <span class="text-caption text-neutral-500">Periode Terpilih</span>
                    </div>

                    @if ($periodMetrics['tickets_count'] === 0)
                        <div class="py-12 text-center text-neutral-400 text-xs">
                            <svg class="w-10 h-10 mx-auto text-neutral-300 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/></svg>
                            Belum ada tiket antrean tercatat pada periode ini.
                        </div>
                    @else
                        <div class="h-48 relative flex items-center justify-center">
                            <canvas id="statusDistributionChart"></canvas>
                        </div>

                        <ul class="mt-4 grid grid-cols-2 gap-2 border-t border-neutral-100 pt-3 text-caption" aria-label="Legenda status">
                            @foreach ($statusLegend as $legend)
                                <li class="flex items-center gap-2">
                                    <span class="size-2.5 shrink-0 rounded-sm {{ $legend['swatch'] }}" aria-hidden="true"></span>
                                    <span class="text-neutral-600">{{ $legend['label'] }}: <strong class="tabular-nums">{{ $statusDistribution[$legend['key']] }}</strong></span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                {{-- Recent Activities Log (M8-06) --}}
                <div class="bg-white rounded-xl border border-neutral-200 p-4 shadow-xs sm:p-5">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-h3 font-bold text-neutral-900">Aktivitas Terakhir</h3>
                        <span class="text-xs text-neutral-400">Audit Platform</span>
                    </div>

                    @if ($recentActivities->isEmpty())
                        <div class="py-8 text-center text-neutral-400 text-xs">
                            Belum ada log aktivitas tercatat dalam sistem.
                        </div>
                    @else
                        <ul class="divide-y divide-neutral-100">
                            @foreach ($recentActivities as $act)
                                <li class="py-2.5 first:pt-0 last:pb-0">
                                    <p class="line-clamp-2 text-xs font-semibold text-neutral-900">{{ $act->description }}</p>
                                    <div class="mt-1.5 flex flex-wrap items-center gap-1.5 text-[11px] text-neutral-500">
                                        <span class="inline-flex items-center rounded bg-neutral-100 px-1.5 py-0.5 font-medium text-neutral-700">{{ $act->user?->name ?? 'Sistem' }}</span>
                                        @if ($act->business)
                                            <span class="inline-flex max-w-full items-center truncate rounded bg-brand-50 px-1.5 py-0.5 text-brand-700">{{ $act->business->name }}</span>
                                        @endif
                                        <time datetime="{{ $act->created_at->toIso8601String() }}" class="tabular-nums text-neutral-400">{{ $act->created_at->diffForHumans() }}</time>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
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

                <form :action="'{{ url('/admin/businesses') }}/' + rejectTargetId + '/reject'" method="POST" class="space-y-4">
                    @csrf
                    <p class="text-xs text-neutral-600">
                        Berikan alasan penolakan untuk <strong class="text-neutral-900" x-text="rejectTargetName"></strong> agar pemilik UMKM dapat memperbaikinya.
                    </p>
                    <div>
                        <label for="dashboard-rejection-reason" class="mb-1 block text-caption font-semibold text-neutral-700">Alasan Penolakan <span class="text-rose-600">*</span></label>
                        <textarea
                            id="dashboard-rejection-reason"
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

    {{-- Chart.js Initialization Script --}}
    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof Chart === 'undefined') {
                console.warn('Chart.js not loaded');
                return;
            }

            const chartLabels = @json($chartLabels);
            const queueTrendData = @json($queueTrend);
            const businessTrendData = @json($businessTrend);
            const statusDist = @json($statusDistribution);

            // 1. Queue Trend Chart
            const queueCtx = document.getElementById('queueTrendChart');
            if (queueCtx) {
                new Chart(queueCtx, {
                    type: 'line',
                    data: {
                        labels: chartLabels,
                        datasets: [{
                            label: 'Tiket Antrean Dibuat',
                            data: queueTrendData,
                            borderColor: '#1E40AF',
                            backgroundColor: 'rgba(30, 64, 175, 0.08)',
                            fill: true,
                            tension: 0.35,
                            borderWidth: 2.5,
                            pointRadius: 3,
                            pointHoverRadius: 6,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: { precision: 0 }
                            }
                        }
                    }
                });
            }

            // 2. Business Registration Trend Chart
            const bizCtx = document.getElementById('businessTrendChart');
            if (bizCtx) {
                new Chart(bizCtx, {
                    type: 'bar',
                    data: {
                        labels: chartLabels,
                        datasets: [{
                            label: 'UMKM Baru',
                            data: businessTrendData,
                            backgroundColor: '#10B981',
                            borderRadius: 4,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: { precision: 0 }
                            }
                        }
                    }
                });
            }

            // 3. Status Distribution Doughnut Chart
            const distCtx = document.getElementById('statusDistributionChart');
            if (distCtx) {
                new Chart(distCtx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Menunggu', 'Dipanggil', 'Dilayani', 'Selesai', 'Dilewati', 'Dibatalkan'],
                        datasets: [{
                            data: [
                                statusDist.menunggu,
                                statusDist.dipanggil,
                                statusDist.sedang_dilayani,
                                statusDist.selesai,
                                statusDist.dilewati,
                                statusDist.dibatalkan,
                            ],
                            backgroundColor: [
                                '#F59E0B',
                                '#3B82F6',
                                '#6366F1',
                                '#10B981',
                                '#9CA3AF',
                                '#EF4444',
                            ],
                            borderWidth: 2,
                            borderColor: '#ffffff',
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false }
                        },
                        cutout: '68%',
                    }
                });
            }
        });
    </script>
    @endpush
</x-layouts.admin>

