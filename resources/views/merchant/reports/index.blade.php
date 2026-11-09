<x-layouts.merchant 
    title="Laporan & Analitik Antrean — AntreIn" 
    heading="Laporan Performa Antrean" 
    description="Analisis mendalam arus antrean, kecepatan pelayanan, dan evaluasi kinerja layanan gerai Anda."
>
    <div class="space-y-6" x-data="{ 
        period: '{{ $report['period'] }}',
        showCustom: {{ $report['period'] === 'custom' ? 'true' : 'false' }}
    }">
        {{-- Date Range Validation Alert --}}
        @if ($errors->has('date_range'))
            <div class="rounded-xl bg-rose-50 border border-rose-200 p-4 text-sm font-medium text-rose-800 flex items-center justify-between shadow-xs">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-rose-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                    <span>{{ $errors->first('date_range') }}</span>
                </div>
            </div>
        @endif

        {{-- Filter & Period Selector Bar (M9-01, M9-10) --}}
        <div class="bg-white rounded-xl border border-neutral-200 p-4 sm:p-5 shadow-xs">
            <form method="GET" action="{{ route('merchant.reports') }}" class="space-y-4">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    {{-- Quick Period Pills --}}
                    <div>
                        <label class="block text-caption font-semibold text-neutral-700 mb-2">Pilih Periode Laporan</label>
                        <div class="inline-flex flex-wrap p-1 rounded-xl bg-neutral-100 border border-neutral-200 gap-1">
                            <button 
                                type="submit" 
                                name="period" 
                                value="today"
                                class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition {{ $report['period'] === 'today' ? 'bg-white text-neutral-900 shadow-xs' : 'text-neutral-600 hover:text-neutral-900' }}"
                            >
                                Hari Ini
                            </button>

                            <button 
                                type="submit" 
                                name="period" 
                                value="7_days"
                                class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition {{ $report['period'] === '7_days' ? 'bg-white text-neutral-900 shadow-xs' : 'text-neutral-600 hover:text-neutral-900' }}"
                            >
                                7 Hari Terakhir
                            </button>

                            <button 
                                type="submit" 
                                name="period" 
                                value="30_days"
                                class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition {{ $report['period'] === '30_days' ? 'bg-white text-neutral-900 shadow-xs' : 'text-neutral-600 hover:text-neutral-900' }}"
                            >
                                30 Hari Terakhir
                            </button>

                            <button 
                                type="button" 
                                @click="showCustom = !showCustom"
                                class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition {{ $report['period'] === 'custom' ? 'bg-brand-600 text-white shadow-xs' : 'text-neutral-600 hover:text-neutral-900' }}"
                            >
                                Rentang Kustom...
                            </button>
                        </div>
                    </div>

                    {{-- Active Scope Metadata (M9-10) --}}
                    <div class="flex flex-col sm:flex-row sm:items-center gap-2 text-caption text-neutral-500">
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-neutral-50 border border-neutral-200">
                            <svg class="w-3.5 h-3.5 text-neutral-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            <strong class="text-neutral-800">{{ $report['periodLabel'] }}</strong>
                        </span>

                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-neutral-50 border border-neutral-200">
                            <svg class="w-3.5 h-3.5 text-neutral-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span>Zona Waktu: <strong class="text-neutral-800">{{ $report['timezone'] }}</strong></span>
                        </span>
                    </div>
                </div>

                {{-- Custom Date Range Expansion Drawer --}}
                <div x-show="showCustom" x-cloak class="pt-4 border-t border-neutral-200 flex flex-col sm:flex-row sm:items-end gap-3">
                    <input type="hidden" name="period" value="custom">

                    <div>
                        <label for="start_date" class="block text-caption font-semibold text-neutral-700 mb-1">Tanggal Mulai</label>
                        <input 
                            type="date" 
                            id="start_date" 
                            name="start_date" 
                            value="{{ $report['startDate'] }}" 
                            required 
                            class="rounded-lg border-neutral-300 text-xs focus:border-brand-500 focus:ring-brand-500 py-1.5 px-3"
                        >
                    </div>

                    <div>
                        <label for="end_date" class="block text-caption font-semibold text-neutral-700 mb-1">Tanggal Selesai</label>
                        <input 
                            type="date" 
                            id="end_date" 
                            name="end_date" 
                            value="{{ $report['endDate'] }}" 
                            required 
                            class="rounded-lg border-neutral-300 text-xs focus:border-brand-500 focus:ring-brand-500 py-1.5 px-3"
                        >
                    </div>

                    <div class="flex items-center gap-2">
                        <button type="submit" class="px-4 py-2 rounded-lg bg-neutral-900 text-white text-xs font-bold hover:bg-neutral-800 transition shadow-xs">
                            Terapkan Rentang
                        </button>
                        <a href="{{ route('merchant.reports') }}" class="px-3 py-2 rounded-lg border border-neutral-300 text-neutral-600 text-xs font-medium hover:bg-neutral-50 transition">
                            Reset ke 7 Hari
                        </a>
                    </div>
                </div>
            </form>
        </div>

        {{-- Key Metrics Cards Strip (M9-02, M9-03, M9-04, M9-05, M9-11) --}}
        <div class="grid grid-cols-2 lg:grid-cols-6 gap-3.5">
            {{-- Total Tickets (M9-02) --}}
            <div class="bg-white rounded-xl border border-neutral-200 p-4 shadow-xs col-span-2 sm:col-span-1">
                <span class="text-caption font-semibold text-neutral-500 block truncate" title="Total seluruh tiket yang dibuat dalam periode terpilih">
                    Total Tiket Dibuat
                </span>
                <span class="text-2xl font-extrabold text-neutral-950 tabular-nums block mt-1">
                    {{ number_format($report['totalTickets']) }}
                </span>
                <span class="text-[11px] text-neutral-400 mt-0.5 block truncate">
                    Semua kanal & status
                </span>
            </div>

            {{-- Completed (M9-03) --}}
            <div class="bg-emerald-50/40 rounded-xl border border-emerald-200 p-4 shadow-xs">
                <span class="text-caption font-semibold text-emerald-800 block truncate">Selesai Dilayani</span>
                <span class="text-2xl font-extrabold text-emerald-950 tabular-nums block mt-1">
                    {{ number_format($report['completedCount']) }}
                </span>
                <span class="text-[11px] text-emerald-700 mt-0.5 block truncate">
                    Pelayanan tuntas
                </span>
            </div>

            {{-- Skipped (M9-03) --}}
            <div class="bg-orange-50/40 rounded-xl border border-orange-200 p-4 shadow-xs">
                <span class="text-caption font-semibold text-orange-800 block truncate">Dilewati</span>
                <span class="text-2xl font-extrabold text-orange-950 tabular-nums block mt-1">
                    {{ number_format($report['skippedCount']) }}
                </span>
                <span class="text-[11px] text-orange-700 mt-0.5 block truncate">
                    Pelanggan tidak hadir
                </span>
            </div>

            {{-- Cancelled (M9-03) --}}
            <div class="bg-neutral-100/60 rounded-xl border border-neutral-300 p-4 shadow-xs">
                <span class="text-caption font-semibold text-neutral-600 block truncate">Dibatalkan</span>
                <span class="text-2xl font-extrabold text-neutral-800 tabular-nums block mt-1">
                    {{ number_format($report['cancelledCount']) }}
                </span>
                <span class="text-[11px] text-neutral-500 mt-0.5 block truncate">
                    Batal oleh pihak terkait
                </span>
            </div>

            {{-- Average Waiting Time (M9-04, M9-11) --}}
            <div class="bg-blue-50/40 rounded-xl border border-blue-200 p-4 shadow-xs col-span-2 sm:col-span-1">
                <span class="text-caption font-semibold text-blue-800 block truncate" title="Dihitung dari tiket dibuat hingga mulai dilayani">
                    Rata-rata Menunggu
                </span>
                @if ($report['avgWaitingMinutes'] !== null)
                    <span class="text-2xl font-extrabold text-blue-950 tabular-nums block mt-1">
                        {{ $report['avgWaitingMinutes'] }} <span class="text-sm font-bold text-blue-700">mnt</span>
                    </span>
                    <span class="text-[11px] text-blue-700 mt-0.5 block truncate">
                        Dari {{ $report['waitingSamples'] }} sampel tiket
                    </span>
                @else
                    <span class="text-sm font-bold text-neutral-500 block mt-2">
                        Belum cukup data
                    </span>
                    <span class="text-[11px] text-neutral-400 mt-0.5 block truncate">
                        Belum ada tiket dilayani
                    </span>
                @endif
            </div>

            {{-- Average Service Duration (M9-05, M9-11) --}}
            <div class="bg-indigo-50/40 rounded-xl border border-indigo-200 p-4 shadow-xs col-span-2 sm:col-span-1">
                <span class="text-caption font-semibold text-indigo-800 block truncate" title="Dihitung dari mulai dilayani hingga selesai pada tiket selesai">
                    Rata-rata Pelayanan
                </span>
                @if ($report['avgServiceMinutes'] !== null)
                    <span class="text-2xl font-extrabold text-indigo-950 tabular-nums block mt-1">
                        {{ $report['avgServiceMinutes'] }} <span class="text-sm font-bold text-indigo-700">mnt</span>
                    </span>
                    <span class="text-[11px] text-indigo-700 mt-0.5 block truncate">
                        Dari {{ $report['serviceSamples'] }} tiket selesai
                    </span>
                @else
                    <span class="text-sm font-bold text-neutral-500 block mt-2">
                        Belum cukup data
                    </span>
                    <span class="text-[11px] text-neutral-400 mt-0.5 block truncate">
                        Belum ada tiket selesai
                    </span>
                @endif
            </div>
        </div>

        {{-- Peak-Hour Insight Strip (M9-09 & OQ-15) --}}
        <div class="rounded-xl p-4 border shadow-xs {{ $report['peakHour']['threshold_met'] ? 'bg-amber-50/70 border-amber-200 text-amber-900' : 'bg-neutral-50 border-neutral-200 text-neutral-700' }}">
            <div class="flex items-start gap-3">
                <div class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0 {{ $report['peakHour']['threshold_met'] ? 'bg-amber-100 text-amber-700' : 'bg-neutral-200 text-neutral-500' }}">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div class="min-w-0 flex-1">
                    @if ($report['peakHour']['threshold_met'])
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1">
                            <h4 class="font-bold text-sm text-amber-950">
                                Jam Ramai Teridentifikasi: {{ $report['peakHour']['peak_range'] }}
                            </h4>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-amber-200/80 text-amber-900">
                                {{ number_format($report['peakHour']['max_count']) }} Tiket dalam 1 Jam
                            </span>
                        </div>
                        <p class="text-caption text-amber-800 mt-0.5">
                            Berdasarkan histori pendaftaran tiket non-dibatalkan, jam ini mencatat beban antrean tertinggi di atas ambang {{ $report['peakHour']['threshold'] }} tiket. Siapkan kapasitas pelayanan ekstra pada rentang jam tersebut.
                        </p>
                    @else
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1">
                            <h4 class="font-bold text-sm text-neutral-800">
                                Analisis Jam Ramai (Peak Hour)
                            </h4>
                            <span class="text-caption text-neutral-500 font-medium">
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
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Line Chart: Daily Queue Volume Trend (M9-06) --}}
            <div class="lg:col-span-2 bg-white rounded-xl border border-neutral-200 p-5 shadow-xs">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-h3 font-bold text-neutral-900">Tren Volume Antrean Harian</h3>
                        <p class="text-caption text-neutral-500 mt-0.5">Jumlah tiket yang diterbitkan setiap hari pada periode terpilih.</p>
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
                        <p class="text-caption text-neutral-400 mt-0.5 max-w-xs">Tidak ada tiket antrean yang dibuat pada periode ini. Coba pilih periode tanggal lain.</p>
                    </div>
                @endif
            </div>

            {{-- Donut Chart: Status Distribution (M9-07) --}}
            <div class="bg-white rounded-xl border border-neutral-200 p-5 shadow-xs">
                <div class="mb-4">
                    <h3 class="text-h3 font-bold text-neutral-900">Distribusi Status Antrean</h3>
                    <p class="text-caption text-neutral-500 mt-0.5">Komposisi penyelesaian tiket pada periode.</p>
                </div>

                @if ($report['hasData'])
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

        {{-- Per-Service Breakdown Table (M9-08) --}}
        <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden shadow-xs">
            <div class="p-4 sm:p-5 border-b border-neutral-200">
                <h3 class="text-h3 font-bold text-neutral-900">Performa Antrean Berdasarkan Layanan</h3>
                <p class="text-caption text-neutral-500 mt-0.5">Rincian volume dan efisiensi waktu pelayanan untuk setiap jenis layanan gerai.</p>
            </div>

            {{-- Desktop Table View --}}
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="border-b border-neutral-200 bg-neutral-50/75 text-neutral-600 font-semibold text-xs tracking-wider uppercase">
                            <th class="py-3.5 px-4">Nama Layanan</th>
                            <th class="py-3.5 px-4">Status Layanan</th>
                            <th class="py-3.5 px-4 text-center">Total Tiket</th>
                            <th class="py-3.5 px-4 text-center">Selesai</th>
                            <th class="py-3.5 px-4 text-center">Dilewati</th>
                            <th class="py-3.5 px-4 text-center">Dibatalkan</th>
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

                                <td class="py-3.5 px-4 text-right font-semibold text-neutral-900 tabular-nums">
                                    @if ($svc['avg_service_minutes'] !== null)
                                        {{ $svc['avg_service_minutes'] }} <span class="text-xs font-normal text-neutral-500">mnt</span>
                                    @else
                                        <span class="text-xs text-neutral-400 font-normal">Belum cukup data</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-8 text-center text-caption text-neutral-400">
                                    Belum ada layanan terdaftar pada unit usaha ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Mobile Card View (M8/M9 Responsive) --}}
            <div class="md:hidden divide-y divide-neutral-200">
                @forelse ($report['servicesBreakdown'] as $svc)
                    <div class="p-4 space-y-2.5">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <h4 class="font-bold text-neutral-900 text-sm">{{ $svc['service_name'] }}</h4>
                                <span class="text-caption text-neutral-500">Rata-rata: <strong>{{ $svc['avg_service_minutes'] !== null ? $svc['avg_service_minutes'] . ' mnt' : 'Belum cukup data' }}</strong></span>
                            </div>

                            @if ($svc['is_active'])
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    Aktif
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-neutral-100 text-neutral-600 border border-neutral-200">
                                    Nonaktif
                                </span>
                            @endif
                        </div>

                        <div class="grid grid-cols-4 gap-2 text-center text-xs bg-neutral-50/75 p-2 rounded-lg border border-neutral-200/60">
                            <div>
                                <span class="text-neutral-400 block text-[10px]">Total</span>
                                <span class="font-bold text-neutral-900">{{ $svc['total_tickets'] }}</span>
                            </div>
                            <div>
                                <span class="text-emerald-600 block text-[10px]">Selesai</span>
                                <span class="font-bold text-emerald-800">{{ $svc['completed_tickets'] }}</span>
                            </div>
                            <div>
                                <span class="text-orange-600 block text-[10px]">Lewati</span>
                                <span class="font-bold text-orange-800">{{ $svc['skipped_tickets'] }}</span>
                            </div>
                            <div>
                                <span class="text-neutral-400 block text-[10px]">Batal</span>
                                <span class="font-bold text-neutral-700">{{ $svc['cancelled_tickets'] }}</span>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="py-8 text-center text-caption text-neutral-400">
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

