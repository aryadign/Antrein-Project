@php
    $isCustomPeriod = $report['period'] === 'custom';
    $periodOptions = [
        'today' => 'Hari Ini',
        '7_days' => '7 Hari',
        '30_days' => '30 Hari',
    ];
    $dateInputClasses = 'border h-11 w-full rounded-lg border-neutral-300 px-3 text-sm focus:border-brand-500 focus:ring-brand-500 sm:w-44';
@endphp

<x-layouts.merchant
    title="Laporan & Analitik Antrean"
    heading="Laporan Performa Cabang"
    description="Analisis rinci layanan, kecepatan pelayanan, dan ekspor data CSV/PDF untuk cabang aktif ({{ auth()->user()?->business?->name ?? 'Gerai Anda' }})."
>
    <div class="space-y-5 sm:space-y-6" x-data="{ showCustom: @js($isCustomPeriod) }">
        @if (auth()->user()?->isOwner() && (auth()->user()?->accessibleBusinesses()->count() ?? 0) > 1)
            <div class="flex flex-wrap items-center justify-between gap-2 rounded-xl border border-brand-200 bg-brand-50/60 px-4 py-3 text-xs text-brand-900">
                <span>Menampilkan rincian layanan &amp; ekspor untuk cabang aktif <strong>{{ auth()->user()?->business?->name }}</strong>. Gunakan pemilih cabang di atas untuk mengganti cabang.</span>
                <a href="{{ route('merchant.dashboard') }}" class="font-bold text-brand-700 hover:underline whitespace-nowrap">Lihat Ringkasan Semua Cabang &rarr;</a>
            </div>
        @endif

        {{-- Date Range Validation Alert --}}
        @if ($errors->has('date_range'))
            <div class="flex items-center gap-2 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm font-medium text-rose-800 shadow-card" role="alert">
                <svg class="w-5 h-5 shrink-0 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                <span>{{ $errors->first('date_range') }}</span>
            </div>
        @endif

        {{-- Filter & Period Selector Bar (M9-01, M9-10) --}}
        <div class="rounded-xl border border-neutral-200 bg-white p-3 shadow-card sm:p-5">
            <form method="GET" action="{{ route('merchant.reports') }}" class="space-y-4">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    {{-- Quick Period Pills --}}
                    <div class="min-w-0">
                        <p class="mb-2 text-caption font-semibold text-neutral-700">Pilih Periode Laporan</p>
                        <div class="grid grid-cols-4 gap-1 rounded-xl border border-neutral-200 bg-neutral-100 p-1 sm:inline-flex">
                            @foreach ($periodOptions as $periodValue => $periodText)
                                <button
                                    type="submit"
                                    name="period"
                                    value="{{ $periodValue }}"
                                    @if ($report['period'] === $periodValue) aria-current="true" @endif
                                    class="inline-flex min-h-10 items-center justify-center rounded-lg px-2 text-xs font-semibold transition sm:px-3.5 {{ $report['period'] === $periodValue ? 'bg-white text-neutral-900 shadow-card' : 'text-neutral-600 hover:text-neutral-900' }}"
                                >
                                    {{ $periodText }}@if ($periodValue !== 'today')<span class="hidden sm:inline">&nbsp;Terakhir</span>@endif
                                </button>
                            @endforeach
                            <button
                                type="button"
                                @click="showCustom = ! showCustom"
                                :aria-expanded="showCustom.toString()"
                                class="inline-flex min-h-10 items-center justify-center rounded-lg px-2 text-xs font-semibold transition sm:px-3.5 {{ $isCustomPeriod ? 'bg-brand-700 text-white shadow-card' : 'text-neutral-600 hover:text-neutral-900' }}"
                            >
                                Kustom
                            </button>
                        </div>
                    </div>

                    {{-- Active Scope Metadata & Export Buttons (M9-10, M11: P1-06 & P1-07) --}}
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between lg:justify-end">
                        <div class="flex flex-wrap items-center gap-2 text-caption text-neutral-600">
                            <span class="inline-flex items-center gap-1 rounded-lg border border-neutral-200 bg-neutral-50 px-2.5 py-1">
                                <svg class="w-3.5 h-3.5 text-neutral-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                <strong class="text-neutral-800 tabular-nums">{{ $report['periodLabel'] }}</strong>
                            </span>
                            <span class="inline-flex items-center gap-1 rounded-lg border border-neutral-200 bg-neutral-50 px-2.5 py-1">
                                <svg class="w-3.5 h-3.5 text-neutral-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <span>Zona Waktu: <strong class="text-neutral-800">{{ $report['timezone'] }}</strong></span>
                            </span>
                        </div>

                        {{-- Export Buttons (P1-06 & P1-07) --}}
                        <div class="grid grid-cols-2 gap-2 sm:flex">
                            <a
                                href="{{ route('merchant.reports.export.csv', request()->query()) }}"
                                class="inline-flex h-11 items-center justify-center gap-1.5 rounded-lg border border-neutral-200 bg-white px-3 text-xs font-semibold text-neutral-700 shadow-card transition hover:bg-neutral-50 focus:outline-hidden focus:ring-2 focus:ring-brand-500"
                                title="Unduh laporan antrean format spreadsheet CSV"
                            >
                                <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                <span>Ekspor CSV</span>
                            </a>
                            <a
                                href="{{ route('merchant.reports.export.pdf', request()->query()) }}"
                                class="inline-flex h-11 items-center justify-center gap-1.5 rounded-lg border border-rose-200 bg-rose-50/60 px-3 text-xs font-semibold text-rose-800 shadow-card transition hover:bg-rose-50 focus:outline-hidden focus:ring-2 focus:ring-rose-500"
                                title="Unduh laporan performa antrean format dokumen PDF"
                                target="_blank"
                                rel="noopener"
                            >
                                <svg class="w-4 h-4 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                <span>Ekspor PDF</span>
                            </a>
                        </div>
                    </div>
                </div>

                {{-- Custom Date Range Drawer: inputs are disabled while hidden so quick-period buttons are not overridden. --}}
                <div
                    x-show="showCustom"
                    @if (! $isCustomPeriod) style="display: none;" @endif
                    class="grid grid-cols-2 gap-3 border-t border-neutral-200 pt-4 sm:flex sm:flex-row sm:items-end"
                >
                    <input type="hidden" name="period" value="custom" @disabled(! $isCustomPeriod) :disabled="! showCustom">

                    <div class="min-w-0">
                        <label for="start_date" class="mb-1 block text-caption font-semibold text-neutral-700">Tanggal Mulai</label>
                        <input type="date" id="start_date" name="start_date" value="{{ $report['startDate'] }}" required @disabled(! $isCustomPeriod) :disabled="! showCustom" class="{{ $dateInputClasses }}">
                    </div>

                    <div class="min-w-0">
                        <label for="end_date" class="mb-1 block text-caption font-semibold text-neutral-700">Tanggal Selesai</label>
                        <input type="date" id="end_date" name="end_date" value="{{ $report['endDate'] }}" required @disabled(! $isCustomPeriod) :disabled="! showCustom" class="{{ $dateInputClasses }}">
                    </div>

                    <div class="col-span-2 grid grid-cols-2 gap-2 sm:flex">
                        <button type="submit" class="inline-flex h-11 items-center justify-center rounded-lg bg-neutral-900 px-4 text-xs font-bold text-white shadow-card transition hover:bg-neutral-800">
                            Terapkan Rentang
                        </button>
                        <a href="{{ route('merchant.reports') }}" class="inline-flex h-11 items-center justify-center rounded-lg border border-neutral-300 px-3 text-xs font-medium text-neutral-600 transition hover:bg-neutral-50">
                            Reset ke 7 Hari
                        </a>
                    </div>
                </div>
            </form>
        </div>

        {{-- Key metrics: calm tiles, one emphasis (completed customers) --}}
        @php
            $reportKpis = [
                ['label' => 'Total tiket', 'value' => number_format($report['totalTickets']), 'hint' => 'Dibuat pada periode ini', 'tone' => 'neutral', 'icon' => 'ticket', 'emphasis' => false],
                ['label' => 'Selesai dilayani', 'value' => number_format($report['completedCount']), 'hint' => $report['totalTickets'] > 0 ? round($report['completedCount'] / $report['totalTickets'] * 100).'% dari total tiket' : 'Belum ada tiket', 'tone' => 'success', 'icon' => 'check', 'emphasis' => true],
                ['label' => 'Dilewati', 'value' => number_format($report['skippedCount']), 'hint' => 'Tidak hadir saat dipanggil', 'tone' => 'warning', 'icon' => 'alert', 'emphasis' => false],
                ['label' => 'Dibatalkan', 'value' => number_format($report['cancelledCount']), 'hint' => 'Oleh pelanggan atau loket', 'tone' => 'muted', 'icon' => 'pause', 'emphasis' => false],
                ['label' => 'Rata-rata menunggu', 'value' => $report['avgWaitingMinutes'] !== null ? $report['avgWaitingMinutes'].' mnt' : 'Belum ada data', 'hint' => $report['avgWaitingMinutes'] !== null ? 'Dari '.$report['waitingSamples'].' tiket, dibuat sampai mulai dilayani' : 'Belum ada tiket dilayani', 'tone' => 'brand', 'icon' => 'clock', 'emphasis' => false],
                ['label' => 'Rata-rata pelayanan', 'value' => $report['avgServiceMinutes'] !== null ? $report['avgServiceMinutes'].' mnt' : 'Belum ada data', 'hint' => $report['avgServiceMinutes'] !== null ? 'Dari '.$report['serviceSamples'].' tiket selesai' : 'Belum ada tiket selesai', 'tone' => 'brand', 'icon' => 'activity', 'emphasis' => false],
            ];
        @endphp
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-6">
            @foreach ($reportKpis as $kpi)
                <x-stat-tile :label="$kpi['label']" :value="$kpi['value']" :hint="$kpi['hint']" :tone="$kpi['tone']" :icon="$kpi['icon']" :emphasis="$kpi['emphasis']" />
            @endforeach
        </div>

        {{-- Gross Revenue Summary Card (Completed Tickets × Configured Service Price) --}}
        <div class="rounded-xl border border-emerald-200 bg-emerald-50/50 p-4 shadow-card sm:p-5">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-emerald-800">Estimasi Pemasukan Kotor</span>
                        <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-bold text-emerald-800">Tiket Selesai &times; Harga Layanan</span>
                    </div>
                    <p class="mt-1.5 text-2xl font-extrabold text-emerald-950 tabular-nums sm:text-3xl">
                        Rp {{ number_format($report['grossRevenue'], 0, ',', '.') }}
                    </p>
                    <p class="mt-1 text-xs text-emerald-800/90">
                        Dihitung dari <strong class="tabular-nums">{{ number_format($report['completedCount']) }}</strong> tiket selesai dikalikan harga produk/jasa yang ditentukan UMKM.
                        @if ($report['completedCount'] > 0)
                            <span class="hidden sm:inline"> Rata-rata <strong class="tabular-nums">Rp {{ number_format(round($report['grossRevenue'] / $report['completedCount']), 0, ',', '.') }}</strong> / tiket selesai.</span>
                        @endif
                    </p>
                </div>

                <div class="shrink-0">
                    <a
                        href="{{ route('merchant.services.index') }}"
                        class="inline-flex h-10 items-center justify-center rounded-lg border border-emerald-300 bg-white px-3.5 text-xs font-bold text-emerald-800 shadow-card transition hover:bg-emerald-50"
                    >
                        Atur Harga Layanan &rarr;
                    </a>
                </div>
            </div>
        </div>

        {{-- Peak-Hour Insight Strip (M9-09 & OQ-15) --}}
        <div class="rounded-xl p-4 border shadow-card {{ $report['peakHour']['threshold_met'] ? 'bg-amber-50/70 border-amber-200 text-amber-900' : 'bg-neutral-50 border-neutral-200 text-neutral-700' }}">
            <div class="flex items-start gap-3">
                <div class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0 {{ $report['peakHour']['threshold_met'] ? 'bg-amber-100 text-amber-700' : 'bg-neutral-200 text-neutral-500' }}">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div class="min-w-0 flex-1">
                    @if ($report['peakHour']['threshold_met'])
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1.5">
                            <h3 class="font-bold text-sm text-amber-950">
                                Jam Ramai Teridentifikasi: {{ $report['peakHour']['peak_range'] }}
                            </h3>
                            <span class="inline-flex w-fit items-center px-2 py-0.5 rounded text-xs font-bold bg-amber-200/80 text-amber-900 tabular-nums">
                                {{ number_format($report['peakHour']['max_count']) }} Tiket dalam 1 Jam
                            </span>
                        </div>
                        <p class="text-caption text-amber-800 mt-0.5">
                            Berdasarkan histori pendaftaran tiket non-dibatalkan, jam ini mencatat beban antrean tertinggi di atas ambang {{ $report['peakHour']['threshold'] }} tiket. Siapkan kapasitas pelayanan ekstra pada rentang jam tersebut.
                        </p>
                    @else
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1">
                            <h3 class="font-bold text-sm text-neutral-800">
                                Analisis Jam Ramai 
                            </h3>
                            <span class="text-caption text-neutral-600 font-medium">
                                Ambang Minimum: {{ $report['peakHour']['threshold'] }} Tiket/Jam (OQ-15)
                            </span>
                        </div>
                        <p class="text-caption text-neutral-600 mt-0.5">
                            @if ($report['peakHour']['max_count'] > 0)
                                Beban antrean tertinggi saat ini tercatat pada jam <strong>{{ $report['peakHour']['top_hour_formatted'] }}</strong> sebanyak <strong>{{ $report['peakHour']['max_count'] }} tiket</strong>. Sesuai pedoman arsitektur AntreIn, jam ramai baru ditetapkan resmi apabila volume antrean mencapai minimal 50 tiket/jam guna menjaga akurasi kesimpulan.
                            @else
                                Belum ada data tiket yang cukup untuk menganalisis kepadatan jam operasional pada periode terpilih.
                            @endif
                        </p>
                    @endif
                </div>
            </div>
        </div>

        {{-- Visual Analytics Grid (M9-06, M9-07, M9-11) --}}
        <div class="grid grid-cols-1 gap-5 lg:grid-cols-3 lg:gap-6">
            {{-- Line Chart: Daily Queue Volume Trend (M9-06) --}}
            <div class="min-w-0 lg:col-span-2 bg-white rounded-xl border border-neutral-200 p-4 shadow-card sm:p-5">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-h3 font-bold text-neutral-900">Tren Volume Antrean Harian</h2>
                        <p class="text-caption text-neutral-600 mt-0.5">Jumlah tiket yang diterbitkan setiap hari pada periode terpilih.</p>
                    </div>
                </div>

                @if ($report['hasData'])
                    <div class="relative h-64 w-full">
                        <canvas id="queueTrendChart"></canvas>
                    </div>
                @else
                    <div class="h-64 flex flex-col items-center justify-center text-center p-6 bg-neutral-50/50 rounded-xl border border-neutral-100">
                        <svg class="w-10 h-10 text-neutral-300 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                        <p class="font-semibold text-neutral-700 text-sm">Belum Ada Data Antrean</p>
                        <p class="text-caption text-neutral-600 mt-0.5 max-w-xs">Tidak ada tiket antrean yang dibuat pada periode ini. Coba pilih periode tanggal lain.</p>
                    </div>
                @endif
            </div>

            {{-- Donut Chart: Status Distribution (M9-07) --}}
            <div class="min-w-0 bg-white rounded-xl border border-neutral-200 p-4 shadow-card sm:p-5">
                <div class="mb-4">
                    <h2 class="text-h3 font-bold text-neutral-900">Distribusi Status Antrean</h2>
                    <p class="text-caption text-neutral-600 mt-0.5">Komposisi penyelesaian tiket pada periode.</p>
                </div>

                @if ($report['hasData'])
                    <div class="relative h-64 w-full flex items-center justify-center">
                        <canvas id="statusDistributionChart"></canvas>
                    </div>
                @else
                    <div class="h-64 flex flex-col items-center justify-center text-center p-6 bg-neutral-50/50 rounded-xl border border-neutral-100">
                        <svg class="w-10 h-10 text-neutral-300 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/></svg>
                        <p class="font-semibold text-neutral-700 text-sm">Tidak Ada Tiket</p>
                        <p class="text-caption text-neutral-600 mt-0.5">Grafik distribusi akan muncul setelah terdapat tiket diterbitkan.</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Per-Service Breakdown Table (M9-08) --}}
        <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden shadow-card">
            <div class="p-4 sm:p-5 border-b border-neutral-200 flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h2 class="text-h3 font-bold text-neutral-900">Performa Antrean Berdasarkan Layanan</h2>
                    <p class="text-caption text-neutral-600 mt-0.5">Rincian volume, harga layanan, estimasi pemasukan kotor, dan efisiensi waktu pelayanan untuk setiap jenis layanan gerai.</p>
                </div>
                <a href="{{ route('merchant.services.index') }}" class="text-xs font-semibold text-brand-700 hover:underline whitespace-nowrap">
                    Kelola Harga Layanan &rarr;
                </a>
            </div>

            {{-- Desktop Table View --}}
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="border-b border-neutral-200 bg-neutral-50/75 text-neutral-600 font-semibold text-xs tracking-wider uppercase">
                            <th class="py-3.5 px-4">Nama Layanan</th>
                            <th class="py-3.5 px-4">Status Layanan</th>
                            <th class="py-3.5 px-4 text-right">Harga Satuan</th>
                            <th class="py-3.5 px-4 text-center">Total Tiket</th>
                            <th class="py-3.5 px-4 text-center">Selesai</th>
                            <th class="py-3.5 px-4 text-center">Dilewati</th>
                            <th class="py-3.5 px-4 text-center">Dibatalkan</th>
                            <th class="py-3.5 px-4 text-right">Pemasukan Kotor</th>
                            <th class="py-3.5 px-4 text-right">Rata-rata Pelayanan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-200">
                        @forelse ($report['servicesBreakdown'] as $svc)
                            <tr class="hover:bg-neutral-50/50 transition">
                                <td class="py-3.5 px-4 font-bold text-neutral-900">
                                    {{ $svc['service_name'] }}
                                </td>

                                <td class="py-3.5 px-4">
                                    @if ($svc['is_active'])
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            Aktif
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-neutral-100 text-neutral-600 border border-neutral-200" title="Layanan telah dinonaktifkan namun memiliki data historis">
                                            Nonaktif
                                        </span>
                                    @endif
                                </td>

                                <td class="py-3.5 px-4 text-right text-neutral-700 font-medium tabular-nums whitespace-nowrap">
                                    @if ($svc['unit_price'] > 0)
                                        Rp {{ number_format($svc['unit_price'], 0, ',', '.') }}
                                    @else
                                        <span class="text-xs text-neutral-500 font-normal">Rp 0 (Gratis)</span>
                                    @endif
                                </td>

                                <td class="py-3.5 px-4 text-center font-bold text-neutral-900 tabular-nums">
                                    {{ number_format($svc['total_tickets']) }}
                                </td>

                                <td class="py-3.5 px-4 text-center text-emerald-700 font-semibold tabular-nums">
                                    {{ number_format($svc['completed_tickets']) }}
                                </td>

                                <td class="py-3.5 px-4 text-center text-orange-700 font-medium tabular-nums">
                                    {{ number_format($svc['skipped_tickets']) }}
                                </td>

                                <td class="py-3.5 px-4 text-center text-neutral-500 tabular-nums">
                                    {{ number_format($svc['cancelled_tickets']) }}
                                </td>

                                <td class="py-3.5 px-4 text-right font-bold text-emerald-800 tabular-nums whitespace-nowrap">
                                    Rp {{ number_format($svc['gross_revenue'], 0, ',', '.') }}
                                </td>

                                <td class="py-3.5 px-4 text-right font-semibold text-neutral-900 tabular-nums">
                                    @if ($svc['avg_service_minutes'] !== null)
                                        {{ $svc['avg_service_minutes'] }} <span class="text-xs font-normal text-neutral-500">mnt</span>
                                    @else
                                        <span class="text-xs text-neutral-500 font-normal">Belum cukup data</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="py-8 text-center text-caption text-neutral-600">
                                    Belum ada layanan terdaftar pada unit usaha ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if ($report['servicesBreakdown']->isNotEmpty())
                        <tfoot>
                            <tr class="border-t-2 border-neutral-200 bg-emerald-50/40 font-bold text-neutral-900">
                                <td colspan="4" class="py-3.5 px-4 text-xs uppercase tracking-wider text-neutral-600">
                                    Total Estimasi Pemasukan Kotor
                                </td>
                                <td class="py-3.5 px-4 text-center text-emerald-800 tabular-nums">
                                    {{ number_format($report['completedCount']) }}
                                </td>
                                <td colspan="2" class="py-3.5 px-4"></td>
                                <td class="py-3.5 px-4 text-right text-emerald-900 tabular-nums whitespace-nowrap">
                                    Rp {{ number_format($report['grossRevenue'], 0, ',', '.') }}
                                </td>
                                <td class="py-3.5 px-4"></td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>

            {{-- Mobile Card View (M8/M9 Responsive) --}}
            <div class="md:hidden divide-y divide-neutral-200">
                @forelse ($report['servicesBreakdown'] as $svc)
                    <div class="p-4 space-y-2.5">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <h3 class="font-bold text-neutral-900 text-sm">{{ $svc['service_name'] }}</h3>
                                <span class="text-caption text-neutral-600">
                                    Harga: <strong class="text-neutral-800 tabular-nums">Rp {{ number_format($svc['unit_price'], 0, ',', '.') }}</strong>
                                     Rata-rata: <strong>{{ $svc['avg_service_minutes'] !== null ? $svc['avg_service_minutes'] . ' mnt' : 'Belum cukup data' }}</strong>
                                </span>
                            </div>

                            @if ($svc['is_active'])
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    Aktif
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-neutral-100 text-neutral-600 border border-neutral-200">
                                    Nonaktif
                                </span>
                            @endif
                        </div>

                        <div class="grid grid-cols-4 gap-2 text-center text-xs tabular-nums bg-neutral-50/75 p-2 rounded-lg border border-neutral-200/60">
                            <div>
                                <span class="text-neutral-500 block text-xs">Total</span>
                                <span class="font-bold text-neutral-900">{{ $svc['total_tickets'] }}</span>
                            </div>
                            <div>
                                <span class="text-emerald-600 block text-xs">Selesai</span>
                                <span class="font-bold text-emerald-800">{{ $svc['completed_tickets'] }}</span>
                            </div>
                            <div>
                                <span class="text-orange-600 block text-xs">Lewati</span>
                                <span class="font-bold text-orange-800">{{ $svc['skipped_tickets'] }}</span>
                            </div>
                            <div>
                                <span class="text-neutral-500 block text-xs">Batal</span>
                                <span class="font-bold text-neutral-700">{{ $svc['cancelled_tickets'] }}</span>
                            </div>
                        </div>

                        <div class="flex items-center justify-between rounded-lg bg-emerald-50/60 px-3 py-2 text-xs border border-emerald-100">
                            <span class="font-medium text-emerald-800">Pemasukan Kotor</span>
                            <strong class="font-bold text-emerald-950 tabular-nums">Rp {{ number_format($svc['gross_revenue'], 0, ',', '.') }}</strong>
                        </div>
                    </div>
                @empty
                    <div class="py-8 text-center text-caption text-neutral-600">
                        Belum ada layanan terdaftar.
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                if (typeof window.Chart === 'undefined') {
                    console.warn('Chart.js library is not loaded on window.');
                    return;
                }

                @if ($report['hasData'])
                    // 1. Queue Volume Daily Trend Line Chart
                    const trendCanvas = document.getElementById('queueTrendChart');
                    if (trendCanvas) {
                        new window.Chart(trendCanvas, {
                            type: 'line',
                            data: {
                                labels: @json($report['trendLabels']),
                                datasets: [{
                                    label: 'Tiket Antrean',
                                    data: @json($report['trendValues']),
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

                    // 2. Status Distribution Donut Chart
                    const statusCanvas = document.getElementById('statusDistributionChart');
                    if (statusCanvas) {
                        new window.Chart(statusCanvas, {
                            type: 'doughnut',
                            data: {
                                labels: ['Menunggu', 'Dipanggil', 'Sedang Dilayani', 'Selesai', 'Dilewati', 'Dibatalkan'],
                                datasets: [{
                                    data: [
                                        {{ $report['statusDistribution']['menunggu'] }},
                                        {{ $report['statusDistribution']['dipanggil'] }},
                                        {{ $report['statusDistribution']['sedang_dilayani'] }},
                                        {{ $report['statusDistribution']['selesai'] }},
                                        {{ $report['statusDistribution']['dilewati'] }},
                                        {{ $report['statusDistribution']['dibatalkan'] }}
                                    ],
                                    backgroundColor: [
                                        '#F59E0B', // Menunggu (Amber)
                                        '#3B82F6', // Dipanggil (Blue)
                                        '#6366F1', // Sedang Dilayani (Indigo)
                                        '#10B981', // Selesai (Emerald)
                                        '#F97316', // Dilewati (Orange)
                                        '#94A3B8'  // Dibatalkan (Slate)
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
                @endif
            });
        </script>
    @endpush
</x-layouts.merchant>

