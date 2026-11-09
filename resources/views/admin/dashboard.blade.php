<x-layouts.admin 
    title="Dashboard Administrator" 
    heading="Dashboard Platform AntreIn" 
    description="Ringkasan metrik kesehatan sistem, pertumbuhan UMKM, dan aktivitas antrean lintas usaha."
>
    <div class="space-y-6" x-data="{ period: '{{ $period }}' }">
        {{-- Flash Messages --}}
        @if (session('status'))
            <div class="rounded-xl bg-emerald-50 border border-emerald-200 p-4 text-sm font-medium text-emerald-800 flex items-center justify-between shadow-xs">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <span>{{ session('status') }}</span>
                </div>
            </div>
        @endif

        {{-- Period Filter Toolbar (M8-03) --}}
        <div class="bg-white rounded-xl border border-neutral-200 p-4 shadow-xs flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="flex items-center gap-2 text-sm text-neutral-600">
                <svg class="w-5 h-5 text-neutral-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                <span>Filter Periode Analitik: <strong class="text-neutral-900 font-semibold">{{ $periodLabel }}</strong> ({{ $timezone }})</span>
            </div>

            <div class="flex flex-wrap items-center gap-1.5 bg-neutral-100 p-1 rounded-lg">
                @php
                    $periods = [
                        'today' => 'Hari Ini',
                        '7_days' => '7 Hari',
                        '30_days' => '30 Hari',
                        'this_month' => 'Bulan Ini',
                        'all' => 'Semua',
                    ];
                @endphp
                @foreach ($periods as $key => $label)
                    <a 
                        href="{{ route('admin.dashboard', ['period' => $key]) }}" 
                        class="px-3 py-1.5 text-xs font-semibold rounded-md transition {{ $period === $key ? 'bg-white text-brand-700 shadow-xs' : 'text-neutral-600 hover:text-neutral-900' }}"
                    >
                        {{ $label }}
                    </a>
                @endforeach
            </div>
        </div>

        {{-- 6 Primary Platform Metric Cards (M8-02) --}}
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
            {{-- Total UMKM --}}
            <div class="bg-white rounded-xl border border-neutral-200 p-5 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-caption font-semibold text-neutral-500 truncate">Total UMKM</span>
                    <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                </div>
                <div class="text-2xl sm:text-3xl font-extrabold text-neutral-950 tabular-nums mt-2">
                    {{ number_format($totalBusinesses) }}
                </div>
                <p class="text-[11px] text-neutral-500 mt-1">Terdaftar di platform</p>
            </div>

            {{-- UMKM Aktif --}}
            <div class="bg-emerald-50/50 rounded-xl border border-emerald-200 p-5 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-caption font-semibold text-emerald-800 truncate">UMKM Aktif</span>
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                </div>
                <div class="text-2xl sm:text-3xl font-extrabold text-emerald-950 tabular-nums mt-2">
                    {{ number_format($activeBusinesses) }}
                </div>
                <p class="text-[11px] text-emerald-700 mt-1">Normal / tidak disuspend</p>
            </div>

            {{-- UMKM Ditangguhkan --}}
            <div class="bg-rose-50/50 rounded-xl border border-rose-200 p-5 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-caption font-semibold text-rose-800 truncate">Ditangguhkan</span>
                    <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                </div>
                <div class="text-2xl sm:text-3xl font-extrabold text-rose-950 tabular-nums mt-2">
                    {{ number_format($suspendedBusinesses) }}
                </div>
                <p class="text-[11px] text-rose-700 mt-1">Platform suspension</p>
            </div>

            {{-- Total Pengguna --}}
            <div class="bg-white rounded-xl border border-neutral-200 p-5 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-caption font-semibold text-neutral-500 truncate">Total Pengguna</span>
                    <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                </div>
                <div class="text-2xl sm:text-3xl font-extrabold text-neutral-950 tabular-nums mt-2">
                    {{ number_format($totalUsers) }}
                </div>
                <p class="text-[11px] text-neutral-500 mt-1">Admin & Pemilik UMKM</p>
            </div>

            {{-- Total Antrean --}}
            <div class="bg-white rounded-xl border border-neutral-200 p-5 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-caption font-semibold text-neutral-500 truncate">Total Antrean</span>
                    <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                </div>
                <div class="text-2xl sm:text-3xl font-extrabold text-neutral-950 tabular-nums mt-2">
                    {{ number_format($totalQueues) }}
                </div>
                <p class="text-[11px] text-neutral-500 mt-1">Semua tiket dibuat</p>
            </div>

            {{-- Tiket Selesai --}}
            <div class="bg-blue-50/50 rounded-xl border border-blue-200 p-5 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-caption font-semibold text-blue-800 truncate">Tiket Selesai</span>
                    <span class="w-2 h-2 rounded-full bg-blue-600"></span>
                </div>
                <div class="text-2xl sm:text-3xl font-extrabold text-blue-950 tabular-nums mt-2">
                    {{ number_format($completedQueues) }}
                </div>
                <p class="text-[11px] text-blue-700 mt-1">Sukses dilayani</p>
            </div>
        </div>

        {{-- Charts Section (M8-04, M8-05) --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Left 2 Cols: Trend Charts (Pendaftaran UMKM & Antrean) --}}
            <div class="lg:col-span-2 space-y-6">
                {{-- Chart A: Tren Antrean Platform --}}
                <div class="bg-white rounded-xl border border-neutral-200 p-5 shadow-xs">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-h3 font-bold text-neutral-900">Tren Pembuatan Tiket Antrean</h3>
                            <p class="text-caption text-neutral-500">Jumlah tiket online dan walk-in yang diterbitkan selama {{ $periodLabel }}.</p>
                        </div>
                        <span class="text-caption font-bold px-2.5 py-1 rounded-full bg-blue-50 text-brand-700 border border-blue-200">
                            {{ $periodMetrics['tickets_count'] }} Tiket
                        </span>
                    </div>

                    <div class="h-64 relative">
                        <canvas id="queueTrendChart"></canvas>
                    </div>
                </div>

                {{-- Chart B: Tren Pendaftaran UMKM --}}
                <div class="bg-white rounded-xl border border-neutral-200 p-5 shadow-xs">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-h3 font-bold text-neutral-900">Pertumbuhan Pendaftaran UMKM</h3>
                            <p class="text-caption text-neutral-500">Penambahan mitra UMKM baru pada platform selama {{ $periodLabel }}.</p>
                        </div>
                        <span class="text-caption font-bold px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">
                            +{{ $periodMetrics['businesses_count'] }} UMKM Baru
                        </span>
                    </div>

                    <div class="h-56 relative">
                        <canvas id="businessTrendChart"></canvas>
                    </div>
                </div>
            </div>

            {{-- Right 1 Col: Status Distribution & Recent Activities --}}
            <div class="space-y-6">
                {{-- Chart C: Distribusi Status Antrean (Doughnut) --}}
                <div class="bg-white rounded-xl border border-neutral-200 p-5 shadow-xs">
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

                        <div class="grid grid-cols-2 gap-2 mt-4 text-caption border-t border-neutral-100 pt-3">
                            <div class="flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                                <span class="text-neutral-600">Menunggu: <strong>{{ $statusDistribution['menunggu'] }}</strong></span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span>
                                <span class="text-neutral-600">Dipanggil: <strong>{{ $statusDistribution['dipanggil'] }}</strong></span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-indigo-500"></span>
                                <span class="text-neutral-600">Dilayani: <strong>{{ $statusDistribution['sedang_dilayani'] }}</strong></span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                                <span class="text-neutral-600">Selesai: <strong>{{ $statusDistribution['selesai'] }}</strong></span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-neutral-400"></span>
                                <span class="text-neutral-600">Dilewati: <strong>{{ $statusDistribution['dilewati'] }}</strong></span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>
                                <span class="text-neutral-600">Dibatalkan: <strong>{{ $statusDistribution['dibatalkan'] }}</strong></span>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Recent Activities Log (M8-06) --}}
                <div class="bg-white rounded-xl border border-neutral-200 p-5 shadow-xs">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-h3 font-bold text-neutral-900">Aktivitas Terakhir</h3>
                        <span class="text-xs text-neutral-400">Audit Platform</span>
                    </div>

                    @if ($recentActivities->isEmpty())
                        <div class="py-8 text-center text-neutral-400 text-xs">
                            Belum ada log aktivitas tercatat dalam sistem.
                        </div>
                    @else
                        <div class="space-y-3.5 divide-y divide-neutral-100">
                            @foreach ($recentActivities as $act)
                                <div class="pt-2.5 first:pt-0">
                                    <div class="flex items-start justify-between gap-2">
                                        <p class="text-xs font-semibold text-neutral-900 line-clamp-2">
                                            {{ $act->description }}
                                        </p>
                                    </div>
                                    <div class="flex items-center gap-2 text-[11px] text-neutral-500 mt-1">
                                        <span class="font-medium text-neutral-700">{{ $act->user?->name ?? 'Sistem' }}</span>
                                        @if ($act->business)
                                            <span>•</span>
                                            <span class="truncate text-brand-700">{{ $act->business->name }}</span>
                                        @endif
                                        <span>•</span>
                                        <span class="text-neutral-400 tabular-nums">{{ $act->created_at->diffForHumans() }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
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

