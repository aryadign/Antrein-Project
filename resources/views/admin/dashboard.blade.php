@php
    use App\Models\QueueTicket;

    $periods = [
        'today' => 'Hari Ini',
        '7_days' => '7 Hari',
        '30_days' => '30 Hari',
        'this_month' => 'Bulan Ini',
        'all' => 'Semua',
    ];

    $businessTiles = [
        ['label' => 'Menunggu verifikasi', 'value' => $pendingBusinesses, 'hint' => $pendingBusinesses > 0 ? null : 'Tidak ada yang perlu ditinjau', 'tone' => 'warning', 'icon' => 'clock', 'emphasis' => $pendingBusinesses > 0],
        ['label' => 'UMKM aktif', 'value' => $activeBusinesses, 'hint' => 'Disetujui dan beroperasi', 'tone' => 'success', 'icon' => 'check', 'emphasis' => false],
        ['label' => 'Ditangguhkan', 'value' => $suspendedBusinesses, 'hint' => 'Dibekukan oleh admin', 'tone' => $suspendedBusinesses > 0 ? 'danger' : 'muted', 'icon' => 'pause', 'emphasis' => false],
        ['label' => 'Total UMKM', 'value' => $totalBusinesses, 'hint' => 'Semua yang terdaftar', 'tone' => 'neutral', 'icon' => 'store', 'emphasis' => false],
    ];

    $activityTiles = [
        ['label' => 'Total pengguna', 'value' => $totalUsers, 'hint' => 'Semua akun terdaftar', 'tone' => 'brand', 'icon' => 'users'],
        ['label' => 'Total tiket antrean', 'value' => $totalQueues, 'hint' => 'Sejak platform berjalan', 'tone' => 'neutral', 'icon' => 'ticket'],
        ['label' => 'Tiket selesai', 'value' => $completedQueues, 'hint' => 'Berhasil dilayani', 'tone' => 'success', 'icon' => 'check'],
    ];

    /** Chart colours follow the badge tones from QueueTicket::statusPresentation(). */
    $toneChartColors = [
        'warning' => '#F59E0B',
        'info' => '#3B82F6',
        'brand' => '#1E40AF',
        'success' => '#10B981',
        'neutral' => '#94A3B8',
        'caution' => '#F97316',
        'danger' => '#EF4444',
    ];

    $statusLegend = collect($statusDistribution)->map(fn (int $count, string $status): array => [
        'label' => $status === QueueTicket::STATUS_DILEWATI ? 'Dilewati / tidak hadir' : QueueTicket::labelForStatus($status),
        'count' => $count,
        'color' => $toneChartColors[QueueTicket::toneForStatus($status)],
    ])->values();
@endphp

<x-layouts.admin
    title="Dasbor Administrator"
    heading="Dasbor Platform AntreIn"
    description="Tinjau pendaftaran UMKM baru dan pantau aktivitas antrean di seluruh platform."
>
    <div class="space-y-6" x-data="{ rejectModalOpen: false, suspendModalOpen: false, targetBusinessId: null, targetBusinessName: '', currentSuspended: false }">
        @include('admin.partials.flash')

        {{-- UMKM health --}}
        <section aria-labelledby="business-kpi-heading" class="space-y-3">
            <h2 id="business-kpi-heading" class="text-h3 font-semibold text-neutral-900">Status UMKM</h2>
            <div class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
                @foreach ($businessTiles as $tile)
                    <x-stat-tile :label="$tile['label']" :value="number_format($tile['value'])" :hint="$tile['hint']" :tone="$tile['tone']" :icon="$tile['icon']" :emphasis="$tile['emphasis']" />
                @endforeach
            </div>
        </section>

        {{-- Pending UMKM approval queue --}}
        @if ($pendingApprovalBusinesses->isNotEmpty())
            <section class="overflow-hidden rounded-xl border border-amber-200 bg-white shadow-card" aria-labelledby="pending-approval-heading">
                <div class="flex flex-col gap-2 border-b border-amber-100 bg-amber-50/60 px-4 py-3.5 sm:flex-row sm:items-center sm:justify-between sm:px-5">
                    <div>
                        <h2 id="pending-approval-heading" class="text-h3 font-semibold text-balance text-neutral-950">Pendaftaran UMKM menunggu verifikasi</h2>
                        <p class="text-sm text-neutral-600">Periksa data toko sebelum tampil ke pelanggan.</p>
                    </div>
                    <x-button variant="link" size="sm" :href="route('admin.businesses.index', ['status' => 'pending'])" class="self-start sm:self-auto">
                        Lihat semua ({{ $pendingBusinesses }})
                    </x-button>
                </div>

                <ul class="divide-y divide-neutral-100">
                    @foreach ($pendingApprovalBusinesses as $pendingBiz)
                        <li class="flex flex-col gap-3 px-4 py-4 sm:px-5 lg:flex-row lg:items-center lg:justify-between">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <a href="{{ route('admin.businesses.show', $pendingBiz) }}" class="text-sm font-semibold text-neutral-950 hover:text-brand-700 hover:underline">{{ $pendingBiz->name }}</a>
                                    @if ($pendingBiz->category)
                                        <span class="rounded-full bg-neutral-100 px-2 py-0.5 text-xs font-medium text-neutral-700">{{ $pendingBiz->category->name }}</span>
                                    @endif
                                </div>
                                <p class="mt-1 text-sm text-neutral-600">
                                    {{ $pendingBiz->user?->name ?? 'Pemilik belum tercatat' }}
                                    <span class="text-neutral-500">({{ $pendingBiz->user?->email ?? '—' }})</span>
                                </p>
                                <p class="mt-0.5 truncate text-xs text-neutral-500" title="{{ $pendingBiz->address }}">{{ $pendingBiz->address }}</p>
                            </div>

                            <div class="flex shrink-0 flex-wrap items-center gap-2">
                                <x-button variant="ghost" size="sm" :href="route('admin.businesses.show', $pendingBiz)">Tinjau detail</x-button>
                                <x-button
                                    variant="outline"
                                    size="sm"
                                    class="text-rose-700! hover:border-rose-300! hover:bg-rose-50!"
                                    data-business-name="{{ $pendingBiz->name }}"
                                    @click="targetBusinessId = {{ $pendingBiz->id }}; targetBusinessName = $el.dataset.businessName; rejectModalOpen = true;"
                                >
                                    Tolak
                                </x-button>
                                <form method="POST" action="{{ route('admin.businesses.approve', $pendingBiz) }}">
                                    @csrf
                                    <x-button type="submit" size="sm">Setujui</x-button>
                                </form>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        {{-- Users & queue volume --}}
        <section aria-labelledby="activity-kpi-heading" class="space-y-3">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h2 id="activity-kpi-heading" class="text-h3 font-semibold text-neutral-900">Pengguna &amp; antrean</h2>
                    <p class="text-sm text-neutral-600">Grafik di bawah mengikuti periode <strong class="font-semibold text-neutral-900">{{ $periodLabel }}</strong> ({{ $timezone }}).</p>
                </div>
                <nav class="-mx-4 overflow-x-auto px-4 sm:mx-0 sm:px-0" aria-label="Periode analitik">
                    <div class="flex w-max gap-1 rounded-lg border border-neutral-200 bg-neutral-100 p-1">
                        @foreach ($periods as $key => $label)
                            <a
                                href="{{ route('admin.dashboard', ['period' => $key]) }}"
                                @if ($period === $key) aria-current="true" @endif
                                class="inline-flex min-h-9 items-center whitespace-nowrap rounded-md px-3 text-sm font-medium transition-colors duration-150 ease-out {{ $period === $key ? 'bg-white text-brand-700 shadow-card' : 'text-neutral-600 hover:bg-white/60 hover:text-neutral-900' }}"
                            >
                                {{ $label }}
                            </a>
                        @endforeach
                    </div>
                </nav>
            </div>
            <div class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-3">
                @foreach ($activityTiles as $tile)
                    <x-stat-tile :label="$tile['label']" :value="number_format($tile['value'])" :hint="$tile['hint']" :tone="$tile['tone']" :icon="$tile['icon']" @class(['col-span-2 lg:col-span-1' => $loop->last]) />
                @endforeach
            </div>
        </section>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="min-w-0 space-y-6 lg:col-span-2">
                <x-card padding="sm">
                    <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                        <div class="min-w-0">
                            <h3 class="text-h3 font-semibold text-neutral-900">Tren tiket antrean</h3>
                            <p class="text-sm text-pretty text-neutral-600">Tiket dari ponsel dan datang langsung selama {{ $periodLabel }}.</p>
                        </div>
                        <span class="w-fit shrink-0 rounded-full border border-brand-200 bg-brand-50 px-2.5 py-1 text-xs font-semibold tabular-nums text-brand-700">
                            {{ number_format($periodMetrics['tickets_count']) }} tiket
                        </span>
                    </div>
                    <div class="relative h-64">
                        <canvas id="queueTrendChart" role="img" aria-label="Grafik tren tiket antrean"></canvas>
                    </div>
                </x-card>

                <x-card padding="sm">
                    <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                        <div class="min-w-0">
                            <h3 class="text-h3 font-semibold text-neutral-900">Pendaftaran UMKM baru</h3>
                            <p class="text-sm text-pretty text-neutral-600">Mitra UMKM yang mendaftar selama {{ $periodLabel }}.</p>
                        </div>
                        <span class="w-fit shrink-0 rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-semibold tabular-nums text-emerald-800">
                            +{{ number_format($periodMetrics['businesses_count']) }} UMKM
                        </span>
                    </div>
                    <div class="relative h-56">
                        <canvas id="businessTrendChart" role="img" aria-label="Grafik pendaftaran UMKM baru"></canvas>
                    </div>
                </x-card>
            </div>

            <div class="min-w-0 space-y-6">
                <x-card padding="sm">
                    <h3 class="text-h3 font-semibold text-neutral-900">Distribusi status antrean</h3>
                    <p class="mb-3 text-sm text-neutral-600">{{ $periodLabel }}</p>

                    @if ($periodMetrics['tickets_count'] === 0)
                        <p class="py-10 text-center text-sm text-neutral-500">Belum ada tiket antrean tercatat pada periode ini.</p>
                    @else
                        <div class="relative flex h-48 items-center justify-center">
                            <canvas id="statusDistributionChart" role="img" aria-label="Diagram distribusi status antrean"></canvas>
                        </div>

                        <ul class="mt-4 grid grid-cols-1 gap-2 border-t border-neutral-100 pt-3 text-sm sm:grid-cols-2 lg:grid-cols-1" aria-label="Legenda status">
                            @foreach ($statusLegend as $legend)
                                <li class="flex items-center justify-between gap-2">
                                    <span class="flex min-w-0 items-center gap-2 text-neutral-600">
                                        <span class="size-2.5 shrink-0 rounded-sm" style="background-color: {{ $legend['color'] }}" aria-hidden="true"></span>
                                        <span class="truncate">{{ $legend['label'] }}</span>
                                    </span>
                                    <strong class="font-semibold tabular-nums text-neutral-900">{{ number_format($legend['count']) }}</strong>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </x-card>

                <x-card padding="none">
                    <x-slot:header>
                        <h3 class="text-h3 font-semibold text-neutral-900">Aktivitas terakhir</h3>
                        <p class="text-sm text-neutral-600">Log audit terbaru.</p>
                    </x-slot:header>

                    @if ($recentActivities->isEmpty())
                        <p class="px-5 py-8 text-center text-sm text-neutral-500">Belum ada log aktivitas tercatat dalam sistem.</p>
                    @else
                        <ul class="max-h-[32rem] divide-y divide-neutral-100 overflow-y-auto">
                            @foreach ($recentActivities as $act)
                                <li class="px-5 py-3">
                                    <p class="line-clamp-2 text-sm font-medium text-neutral-900">{{ $act->description }}</p>
                                    <div class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-neutral-600">
                                        <span class="font-medium text-neutral-700">{{ $act->user?->name ?? 'Sistem' }}</span>
                                        @if ($act->business)
                                            <span class="max-w-full truncate text-brand-700">{{ $act->business->name }}</span>
                                        @endif
                                        <time datetime="{{ $act->created_at->toIso8601String() }}" class="tabular-nums text-neutral-500">{{ $act->created_at->diffForHumans() }}</time>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </x-card>
            </div>
        </div>

        @include('admin.partials.business-dialogs')
    </div>

    {{-- Chart.js Initialization Script --}}
    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof Chart === 'undefined') {
                                return;
            }

            const chartLabels = @json($chartLabels);
            const queueTrendData = @json($queueTrend);
            const businessTrendData = @json($businessTrend);
            const statusLegend = @json($statusLegend);

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
                        animation: false,
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
                        animation: false,
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
                        labels: statusLegend.map((item) => item.label),
                        datasets: [{
                            data: statusLegend.map((item) => item.count),
                            backgroundColor: statusLegend.map((item) => item.color),
                            borderWidth: 2,
                            borderColor: '#ffffff',
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        animation: false,
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

