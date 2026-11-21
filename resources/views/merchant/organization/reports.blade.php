@php
    $periods = [
        'today' => 'Hari Ini',
        '7_days' => '7 Hari Terakhir',
        '30_days' => '30 Hari Terakhir',
    ];
    $totals = $summary['totals'];
    $completionRate = $totals['total_tickets'] > 0 ? round(($totals['completed_count'] / $totals['total_tickets']) * 100, 1) : 0;
@endphp

<x-layouts.merchant
    title="Laporan Agregat Organisasi"
    heading="Laporan Agregat Organisasi"
    description="Analisis performa antrean lintas cabang di bawah naungan {{ $organization->name }}"
>
    <div class="space-y-5 sm:space-y-6">
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
        <x-merchant.organization-tabs active="reports" :branches-count="$branches->count()" />

        {{-- Period Filter Toolbar --}}
        <div class="flex flex-col gap-3 rounded-xl border border-neutral-200 bg-white p-3 shadow-card sm:p-4 lg:flex-row lg:items-center lg:justify-between lg:gap-4">
            <div class="grid grid-cols-3 gap-1 rounded-xl border border-neutral-200 bg-neutral-100 p-1 sm:inline-flex sm:w-fit">
                @foreach ($periods as $key => $label)
                    <a
                        href="{{ route('merchant.organization.reports', ['period' => $key]) }}"
                        @if ($period === $key) aria-current="true" @endif
                        class="inline-flex min-h-10 items-center justify-center rounded-lg px-2 text-center text-xs font-bold transition sm:px-3.5 {{ $period === $key ? 'bg-white text-neutral-900 shadow-card' : 'text-neutral-600 hover:text-neutral-900' }}"
                    >
                        {{ $label }}
                    </a>
                @endforeach
            </div>

            {{-- Custom Date Range Form --}}
            <form method="GET" action="{{ route('merchant.organization.reports') }}" class="grid grid-cols-2 gap-2 sm:flex sm:flex-wrap sm:items-center">
                <input type="hidden" name="period" value="custom">
                <label for="org_start_date" class="sr-only">Tanggal mulai</label>
                <input
                    id="org_start_date"
                    type="date"
                    name="start_date"
                    value="{{ $startDate }}"
                    class="h-11 min-w-0 rounded-xl border border-neutral-300 px-3 text-sm text-neutral-800 focus:border-brand-500 focus:ring-1 focus:ring-brand-500"
                >
                <label for="org_end_date" class="sr-only">Tanggal selesai</label>
                <input
                    id="org_end_date"
                    type="date"
                    name="end_date"
                    value="{{ $endDate }}"
                    class="h-11 min-w-0 rounded-xl border border-neutral-300 px-3 text-sm text-neutral-800 focus:border-brand-500 focus:ring-1 focus:ring-brand-500"
                >
                <button type="submit" class="col-span-2 inline-flex h-11 items-center justify-center rounded-xl bg-neutral-900 px-4 text-xs font-bold text-white transition hover:bg-neutral-800">
                    Terapkan Rentang
                </button>
            </form>
        </div>

        {{-- Aggregate Totals Cards --}}
        <div class="grid grid-cols-2 gap-3 lg:grid-cols-5">
            <x-stat-tile label="Total Tiket Organisasi" :value="number_format($totals['total_tickets'])" hint="Gabungan seluruh cabang" />
            <x-stat-tile label="Tiket Selesai Dilayani" :value="number_format($totals['completed_count'])" :hint="$completionRate.'% tingkat penyelesaian'" tone="success" />
            <x-stat-tile label="Estimasi Pemasukan Kotor" :value="'Rp '.number_format($totals['gross_revenue'] ?? 0, 0, ',', '.')" hint="Tiket selesai × harga layanan" tone="success" />
            <x-stat-tile label="Rata-rata Waktu Tunggu" :value="$totals['avg_waiting_minutes'] !== null ? $totals['avg_waiting_minutes'].' mnt' : '-'" hint="Tertimbang per antrean" tone="brand" />
            <x-stat-tile label="Tiket Dibatalkan / Skip" :value="number_format($totals['skipped_count'] + $totals['cancelled_count'])" :hint="$totals['cancelled_count'].' batal, '.$totals['skipped_count'].' dilewati'" tone="warning" />
        </div>

        {{-- Breakdown Per Branch --}}
        <section class="space-y-3">
            <div>
                <h3 class="text-sm font-bold text-neutral-900">Performa Per Cabang</h3>
                <p class="text-xs text-neutral-500">Perbandingan volume, estimasi pemasukan kotor, dan efisiensi pelayanan antar cabang</p>
            </div>

            @if (empty($summary['rows']))
                <p class="rounded-xl border border-dashed border-neutral-300 bg-white px-4 py-8 text-center text-sm font-medium text-neutral-500">
                    Tidak ada data antrean pada periode ini.
                </p>
            @else
                {{-- Mobile: card list --}}
                <ul class="space-y-3 lg:hidden" aria-label="Performa per cabang">
                    @foreach ($summary['rows'] as $row)
                        <li class="rounded-xl border border-neutral-200 bg-white p-4 shadow-card">
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <p class="truncate font-bold text-neutral-900">{{ $row['branch_name'] }}</p>
                                    <p class="truncate font-mono text-xs text-neutral-500">/q/{{ $row['branch_slug'] }}</p>
                                </div>
                                <x-status-badge :status="$row['is_active'] ? 'aktif' : 'nonaktif'" size="sm" />
                            </div>
                            <dl class="mt-3 grid grid-cols-4 gap-2 rounded-lg border border-neutral-200/60 bg-neutral-50/75 p-2 text-center text-xs tabular-nums">
                                <div><dt class="text-xs text-neutral-500">Total</dt><dd class="font-bold text-neutral-900">{{ $row['total_tickets'] }}</dd></div>
                                <div><dt class="text-xs text-emerald-600">Selesai</dt><dd class="font-bold text-emerald-800">{{ $row['completed_count'] }}</dd></div>
                                <div><dt class="text-xs text-orange-600">Skip</dt><dd class="font-bold text-orange-800">{{ $row['skipped_count'] }}</dd></div>
                                <div><dt class="text-xs text-neutral-500">Batal</dt><dd class="font-bold text-neutral-700">{{ $row['cancelled_count'] }}</dd></div>
                            </dl>
                            <p class="mt-2 text-xs text-neutral-600 tabular-nums">
                                Pemasukan <strong class="text-emerald-800">Rp {{ number_format($row['gross_revenue'] ?? 0, 0, ',', '.') }}</strong>,
                                tunggu <strong class="text-neutral-900">{{ $row['avg_waiting_minutes'] !== null ? $row['avg_waiting_minutes'].' mnt' : '-' }}</strong>,
                                layanan <strong class="text-neutral-900">{{ $row['avg_service_minutes'] !== null ? $row['avg_service_minutes'].' mnt' : '-' }}</strong>
                            </p>
                            <form method="POST" action="{{ route('merchant.branches.switch') }}" class="mt-3">
                                @csrf
                                <input type="hidden" name="business_id" value="{{ $row['branch_id'] }}">
                                <button type="submit" class="inline-flex h-11 w-full items-center justify-center rounded-lg bg-brand-50 px-3 text-xs font-bold text-brand-700 transition hover:bg-brand-100">
                                    Buka Laporan Rinci &rarr;
                                </button>
                            </form>
                        </li>
                    @endforeach
                </ul>

                {{-- Desktop: table --}}
                <div class="hidden overflow-x-auto rounded-xl border border-neutral-200 bg-white shadow-card lg:block">
                    <table class="w-full border-collapse text-left text-xs">
                        <thead>
                            <tr class="border-b border-neutral-200 bg-neutral-50/75 font-bold uppercase tracking-wider text-neutral-500">
                                <th class="px-5 py-3">Nama Cabang</th>
                                <th class="px-4 py-3 text-center">Status</th>
                                <th class="px-4 py-3 text-right">Total Antrean</th>
                                <th class="px-4 py-3 text-right">Selesai</th>
                                <th class="px-4 py-3 text-right">Skip</th>
                                <th class="px-4 py-3 text-right">Batal</th>
                                <th class="px-4 py-3 text-right">Pemasukan Kotor</th>
                                <th class="px-4 py-3 text-right">Rata-rata Tunggu</th>
                                <th class="px-4 py-3 text-right">Rata-rata Layanan</th>
                                <th class="px-5 py-3 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-100 text-neutral-700">
                            @foreach ($summary['rows'] as $row)
                                <tr class="transition hover:bg-neutral-50/50">
                                    <td class="px-5 py-3.5">
                                        <p class="font-bold text-neutral-900">{{ $row['branch_name'] }}</p>
                                        <p class="font-mono text-xs text-neutral-500">/q/{{ $row['branch_slug'] }}</p>
                                    </td>
                                    <td class="px-4 py-3.5 text-center">
                                        <x-status-badge :status="$row['is_active'] ? 'aktif' : 'nonaktif'" size="sm" />
                                    </td>
                                    <td class="px-4 py-3.5 text-right font-extrabold text-neutral-900 tabular-nums">{{ $row['total_tickets'] }}</td>
                                    <td class="px-4 py-3.5 text-right font-semibold text-emerald-700 tabular-nums">{{ $row['completed_count'] }}</td>
                                    <td class="px-4 py-3.5 text-right text-neutral-600 tabular-nums">{{ $row['skipped_count'] }}</td>
                                    <td class="px-4 py-3.5 text-right text-neutral-600 tabular-nums">{{ $row['cancelled_count'] }}</td>
                                    <td class="whitespace-nowrap px-4 py-3.5 text-right font-bold text-emerald-800 tabular-nums">Rp {{ number_format($row['gross_revenue'] ?? 0, 0, ',', '.') }}</td>
                                    <td class="whitespace-nowrap px-4 py-3.5 text-right text-neutral-800 tabular-nums">{{ $row['avg_waiting_minutes'] !== null ? $row['avg_waiting_minutes'].' mnt' : '-' }}</td>
                                    <td class="whitespace-nowrap px-4 py-3.5 text-right text-neutral-800 tabular-nums">{{ $row['avg_service_minutes'] !== null ? $row['avg_service_minutes'].' mnt' : '-' }}</td>
                                    <td class="px-5 py-3.5 text-center">
                                        <form method="POST" action="{{ route('merchant.branches.switch') }}">
                                            @csrf
                                            <input type="hidden" name="business_id" value="{{ $row['branch_id'] }}">
                                            <button type="submit" class="inline-flex h-9 items-center whitespace-nowrap rounded-lg px-2.5 text-xs font-bold text-brand-700 transition hover:bg-brand-50">
                                                Buka Laporan Rinci &rarr;
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>
</x-layouts.merchant>
