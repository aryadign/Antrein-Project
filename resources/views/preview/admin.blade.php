<x-layouts.admin title="Dashboard Platform" heading="Platform Governance & Monitoring" description="Ringkasan ekosistem UMKM dan aktivitas antrean terpusat di seluruh Indonesia.">
    @php
        $previewBusinesses = [
            ['name' => 'Barbershop Garasi', 'city' => 'Denpasar, Bali', 'category' => 'Barbershop', 'serving' => 'A-014', 'servingName' => 'Made Aryawan', 'waiting' => 4, 'status' => 'buka'],
            ['name' => 'Klinik Gigi Sehat Harmoni', 'city' => 'Kuta, Badung', 'category' => 'Kesehatan', 'serving' => 'B-008', 'servingName' => 'Siti ********', 'waiting' => 2, 'status' => 'jeda'],
            ['name' => 'Salon Cantika Ubud', 'city' => 'Gianyar, Bali', 'category' => 'Salon Kecantikan', 'serving' => null, 'servingName' => null, 'waiting' => 0, 'status' => 'tutup'],
        ];
    @endphp

    {{-- Metric stat cards --}}
    <div class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-2 sm:gap-5 lg:grid-cols-4">
        <x-stat-card title="Total UMKM Terdaftar" value="128" description="12 UMKM baru minggu ini" trend="+10%" trendDirection="up">
            <x-slot name="icon">
                <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            </x-slot>
        </x-stat-card>
        <x-stat-card title="Antrean Hari Ini" value="1,420" description="Di seluruh unit usaha aktif" trend="+18%" trendDirection="up">
            <x-slot name="icon">
                <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg>
            </x-slot>
        </x-stat-card>
        <x-stat-card title="Rata-rata Waktu Layan" value="16.4 m" description="Target SLA: 20 menit">
            <x-slot name="icon">
                <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </x-slot>
        </x-stat-card>
        <x-stat-card title="Tingkat Kehadiran" value="94.2%" description="5.8% no-show / dilewati" trend="+2.1%" trendDirection="up">
            <x-slot name="icon">
                <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </x-slot>
        </x-stat-card>
    </div>

    {{-- Cross-UMKM read-only monitor --}}
    <x-card padding="none">
        <x-slot name="header">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="min-w-0">
                    <h3 class="text-h3 font-bold text-neutral-900">Monitoring Antrean Lintas UMKM (Mode Baca-Saja)</h3>
                    <p class="text-caption text-neutral-500">Nomor telepon pelanggan disamarkan otomatis sesuai prinsip privasi (UU PDP).</p>
                </div>
                <div class="flex w-full items-center gap-2 lg:w-auto">
                    <x-input placeholder="Cari UMKM atau tiket..." aria-label="Cari UMKM atau tiket" class="min-h-11 w-full lg:w-64" />
                    <x-button variant="outline" class="h-11 shrink-0">Filter</x-button>
                </div>
            </div>
        </x-slot>

        {{-- Mobile cards --}}
        <ul class="divide-y divide-neutral-100 md:hidden">
            @foreach ($previewBusinesses as $previewBusiness)
                <li class="space-y-3 p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="truncate font-bold text-neutral-900">{{ $previewBusiness['name'] }}</p>
                            <p class="text-caption text-neutral-500">{{ $previewBusiness['city'] }}</p>
                        </div>
                        <x-status-badge :status="$previewBusiness['status']" size="sm" />
                    </div>
                    <div class="flex flex-wrap gap-2 text-caption">
                        <span class="rounded-md bg-neutral-100 px-2 py-0.5 font-medium text-neutral-700">{{ $previewBusiness['category'] }}</span>
                        <span class="rounded-md bg-neutral-100 px-2 py-0.5 font-medium text-neutral-700">WITA (Asia/Makassar)</span>
                        <span class="rounded-md bg-brand-50 px-2 py-0.5 font-bold text-brand-700 tabular-nums">{{ $previewBusiness['waiting'] }} menunggu</span>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-caption text-neutral-600">
                            @if ($previewBusiness['serving'])
                                Melayani <span class="font-bold text-brand-700 tabular-nums">{{ $previewBusiness['serving'] }}</span>
                            @else
                                Tidak ada tiket aktif
                            @endif
                        </p>
                        <x-button variant="outline" class="h-11">Audit Detail</x-button>
                    </div>
                </li>
            @endforeach
        </ul>

        {{-- Desktop table --}}
        <div class="hidden md:block">
            <x-table>
                <x-slot name="header">
                    <tr>
                        <x-table.th>Nama UMKM</x-table.th>
                        <x-table.th>Zona Waktu</x-table.th>
                        <x-table.th>Sedang Melayani</x-table.th>
                        <x-table.th>Antrean Menunggu</x-table.th>
                        <x-table.th>Status Operasional</x-table.th>
                        <x-table.th>Status Akun</x-table.th>
                        <x-table.th align="right">Aksi Tata Kelola</x-table.th>
                    </tr>
                </x-slot>

                @foreach ($previewBusinesses as $previewBusiness)
                    <tr>
                        <x-table.td>
                            <div class="font-bold text-neutral-900">{{ $previewBusiness['name'] }}</div>
                            <div class="mt-1 flex flex-wrap gap-1.5 text-caption">
                                <span class="text-neutral-500">{{ $previewBusiness['city'] }}</span>
                                <span class="rounded bg-neutral-100 px-1.5 font-medium text-neutral-600">{{ $previewBusiness['category'] }}</span>
                            </div>
                        </x-table.td>
                        <x-table.td><span class="text-caption font-medium text-neutral-700">WITA (Asia/Makassar)</span></x-table.td>
                        <x-table.td>
                            @if ($previewBusiness['serving'])
                                <span class="font-bold text-brand-700 tabular-nums">{{ $previewBusiness['serving'] }}</span> ({{ $previewBusiness['servingName'] }})
                            @else
                                <span class="text-neutral-400">—</span>
                            @endif
                        </x-table.td>
                        <x-table.td><span @class(['tabular-nums', 'font-bold' => $previewBusiness['waiting'] > 0, 'text-neutral-500' => $previewBusiness['waiting'] === 0])>{{ $previewBusiness['waiting'] }} antrean</span></x-table.td>
                        <x-table.td><x-status-badge :status="$previewBusiness['status']" size="sm" /></x-table.td>
                        <x-table.td><x-status-badge status="aktif" size="sm" /></x-table.td>
                        <x-table.td align="right">
                            <x-button size="sm" variant="ghost">Audit Detail</x-button>
                        </x-table.td>
                    </tr>
                @endforeach
            </x-table>
        </div>
    </x-card>
</x-layouts.admin>
