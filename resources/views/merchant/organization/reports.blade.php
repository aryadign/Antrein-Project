<x-layouts.merchant
    title="Laporan Agregat Organisasi"
    heading="Laporan Agregat Organisasi"
    description="Analisis performa antrean lintas cabang di bawah naungan {{ $organization->name }}"
>
    <div class="space-y-6">
        {{-- Flash Status & Error Message --}}
        @if (session('status'))
            <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm font-medium">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="p-4 rounded-xl bg-red-50 border border-red-200 text-red-800 text-sm font-medium space-y-1">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        {{-- Header Navigation Tabs --}}
        <div class="flex items-center gap-2 border-b border-neutral-200 pb-3">
            <a href="{{ route('merchant.organization.edit') }}" class="px-4 py-2 rounded-xl text-xs font-semibold text-neutral-600 hover:text-neutral-950 hover:bg-neutral-100 transition">
                Profil Brand & Organisasi
            </a>
            <a href="{{ route('merchant.organization.reports') }}" class="px-4 py-2 rounded-xl text-xs font-bold bg-brand-50 text-brand-700 border border-brand-200">
                Laporan Agregat Organisasi
            </a>
            <a href="{{ route('merchant.branches.index') }}" class="px-4 py-2 rounded-xl text-xs font-semibold text-neutral-600 hover:text-neutral-950 hover:bg-neutral-100 transition">
                Daftar Cabang ({{ $branches->count() }})
            </a>
        </div>

        {{-- Period Filter Toolbar --}}
        <div class="p-4 bg-white rounded-2xl border border-neutral-200 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex flex-wrap items-center gap-2">
                @php
                    $periods = [
                        'today' => 'Hari Ini',
                        '7_days' => '7 Hari Terakhir',
                        '30_days' => '30 Hari Terakhir',
                    ];
                @endphp
                @foreach ($periods as $key => $label)
                    <a
                        href="{{ route('merchant.organization.reports', ['period' => $key]) }}"
                        class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition {{ $period === $key ? 'bg-brand-700 text-white shadow-xs' : 'bg-neutral-100 text-neutral-700 hover:bg-neutral-200' }}"
                    >
                        {{ $label }}
                    </a>
                @endforeach
            </div>

            {{-- Custom Date Range Form --}}
            <form method="GET" action="{{ route('merchant.organization.reports') }}" class="flex flex-wrap items-center gap-2">
                <input type="hidden" name="period" value="custom">
                <input
                    type="date"
                    name="start_date"
                    value="{{ $startDate }}"
                    class="px-3 py-1.5 rounded-xl border border-neutral-300 text-xs text-neutral-800 focus:border-brand-500 focus:ring-1 focus:ring-brand-500"
                >
                <span class="text-xs text-neutral-400">s/d</span>
                <input
                    type="date"
                    name="end_date"
                    value="{{ $endDate }}"
                    class="px-3 py-1.5 rounded-xl border border-neutral-300 text-xs text-neutral-800 focus:border-brand-500 focus:ring-1 focus:ring-brand-500"
                >
                <button type="submit" class="px-3.5 py-1.5 rounded-xl text-xs font-bold bg-neutral-900 text-white hover:bg-neutral-800 transition">
                    Filter
                </button>
            </form>
        </div>

        {{-- Aggregate Totals Cards --}}
        @php
            $totals = $summary['totals'];
        @endphp
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="p-5 rounded-2xl bg-white border border-neutral-200 shadow-xs">
                <span class="text-xs font-semibold uppercase tracking-wider text-neutral-400">Total Tiket Organisasi</span>
                <div class="mt-2 text-3xl font-extrabold text-neutral-900 tabular-nums">
                    {{ $totals['total_tickets'] }}
                </div>
                <p class="text-[11px] text-neutral-500 mt-1">Gabungan seluruh cabang</p>
            </div>

            <div class="p-5 rounded-2xl bg-white border border-neutral-200 shadow-xs">
                <span class="text-xs font-semibold uppercase tracking-wider text-neutral-400">Tiket Selesai Dilayani</span>
                <div class="mt-2 text-3xl font-extrabold text-emerald-700 tabular-nums">
                    {{ $totals['completed_count'] }}
                </div>
                <p class="text-[11px] text-neutral-500 mt-1">
                    @if ($totals['total_tickets'] > 0)
                        {{ round(($totals['completed_count'] / $totals['total_tickets']) * 100, 1) }}% tingkat penyelesaian
                    @else
                        0% tingkat penyelesaian
                    @endif
                </p>
            </div>

            <div class="p-5 rounded-2xl bg-white border border-neutral-200 shadow-xs">
                <span class="text-xs font-semibold uppercase tracking-wider text-neutral-400">Rata-rata Waktu Tunggu</span>
                <div class="mt-2 text-3xl font-extrabold text-brand-700 tabular-nums">
                    {{ $totals['avg_waiting_minutes'] !== null ? $totals['avg_waiting_minutes'] . ' Menit' : '-' }}
                </div>
                <p class="text-[11px] text-neutral-500 mt-1">Tertimbang per antrean</p>
            </div>

            <div class="p-5 rounded-2xl bg-white border border-neutral-200 shadow-xs">
                <span class="text-xs font-semibold uppercase tracking-wider text-neutral-400">Tiket Dibatalkan / Skip</span>
                <div class="mt-2 text-3xl font-extrabold text-amber-600 tabular-nums">
                    {{ $totals['skipped_count'] + $totals['cancelled_count'] }}
                </div>
                <p class="text-[11px] text-neutral-500 mt-1">
                    {{ $totals['cancelled_count'] }} batal, {{ $totals['skipped_count'] }} dilewati
                </p>
            </div>
        </div>

        {{-- Breakdown Per Cabang Table --}}
        <div class="bg-white rounded-2xl border border-neutral-200 shadow-xs overflow-hidden">
            <div class="px-6 py-4 border-b border-neutral-200 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-neutral-900">Performa Per Cabang</h3>
                    <p class="text-xs text-neutral-500">Perbandingan volume dan efisiensi pelayanan antar cabang</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-neutral-50/75 border-b border-neutral-200 text-neutral-500 font-bold uppercase tracking-wider">
                            <th class="py-3 px-6">Nama Cabang</th>
                            <th class="py-3 px-4 text-center">Status</th>
                            <th class="py-3 px-4 text-right">Total Antrean</th>
                            <th class="py-3 px-4 text-right">Selesai</th>
                            <th class="py-3 px-4 text-right">Skip</th>
                            <th class="py-3 px-4 text-right">Batal</th>
                            <th class="py-3 px-4 text-right">Rata-rata Tunggu</th>
                            <th class="py-3 px-4 text-right">Rata-rata Layanan</th>
                            <th class="py-3 px-6 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100 text-neutral-700">
                        @forelse ($summary['rows'] as $row)
                            <tr class="hover:bg-neutral-50/50 transition">
                                <td class="py-3.5 px-6 font-bold text-neutral-900">
                                    <div class="flex items-center gap-2">
                                        <span>{{ $row['branch_name'] }}</span>
                                        <span class="text-[10px] text-neutral-400 font-normal">(/q/{{ $row['branch_slug'] }})</span>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $row['is_active'] ? 'bg-emerald-50 text-emerald-700' : 'bg-neutral-100 text-neutral-500' }}">
                                        {{ $row['is_active'] ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-right font-extrabold text-neutral-900 tabular-nums">
                                    {{ $row['total_tickets'] }}
                                </td>
                                <td class="py-3.5 px-4 text-right font-semibold text-emerald-700 tabular-nums">
                                    {{ $row['completed_count'] }}
                                </td>
                                <td class="py-3.5 px-4 text-right text-neutral-600 tabular-nums">
                                    {{ $row['skipped_count'] }}
                                </td>
                                <td class="py-3.5 px-4 text-right text-neutral-600 tabular-nums">
                                    {{ $row['cancelled_count'] }}
                                </td>
                                <td class="py-3.5 px-4 text-right text-neutral-800 tabular-nums">
                                    {{ $row['avg_waiting_minutes'] !== null ? $row['avg_waiting_minutes'] . ' mnt' : '-' }}
                                </td>
                                <td class="py-3.5 px-4 text-right text-neutral-800 tabular-nums">
                                    {{ $row['avg_service_minutes'] !== null ? $row['avg_service_minutes'] . ' mnt' : '-' }}
                                </td>
                                <td class="py-3.5 px-6 text-center">
                                    <form method="POST" action="{{ route('merchant.branches.switch') }}" class="inline">
                                        @csrf
                                        <input type="hidden" name="business_id" value="{{ $row['branch_id'] }}">
                                        <button type="submit" class="px-2.5 py-1 text-[11px] font-bold text-brand-700 hover:bg-brand-50 rounded-lg transition">
                                            Buka Laporan Rinci &rarr;
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="py-8 text-center text-neutral-400 font-medium">
                                    Tidak ada data antrean pada periode ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-layouts.merchant>

