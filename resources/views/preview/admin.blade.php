<x-layouts.admin title="Dashboard Platform" heading="Platform Governance & Monitoring" description="Ringkasan ekosistem UMKM dan aktivitas antrean terpusat di seluruh Indonesia.">
    {{-- Metric Stat Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
        <x-stat-card title="Total UMKM Terdaftar" value="128" description="12 UMKM baru minggu ini" trend="+10%" trendDirection="up" />
        <x-stat-card title="Antrean Hari Ini" value="1,420" description="Di seluruh unit usaha aktif" trend="+18%" trendDirection="up" />
        <x-stat-card title="Rata-rata Waktu Layan" value="16.4 m" description="Target SLA: 20 menit" />
        <x-stat-card title="Tingkat Kehadiran" value="94.2%" description="5.8% no-show / dilewati" trend="+2.1%" trendDirection="up" />
    </div>

    {{-- UMKM Overview & Live Monitor Table --}}
    <div class="space-y-6">
        <x-card padding="none">
            <x-slot name="header">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h3 class="text-h3 font-bold text-neutral-900">Monitoring Antrean Lintas UMKM (Mode Baca-Saja)</h3>
                        <p class="text-caption text-neutral-500">Nomor telepon pelanggan disamarkan otomatis sesuai prinsip privasi (UU PDP).</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <x-input placeholder="Cari UMKM atau tiket..." class="w-64" />
                        <x-button variant="outline" size="sm">Filter</x-button>
                    </div>
                </div>
            </x-slot>

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

                <tr>
                    <x-table.td>
                        <div class="font-bold text-neutral-900">Barbershop Garasi</div>
                        <div class="text-caption text-neutral-500">Denpasar, Bali • Kategori Barbershop</div>
                    </x-table.td>
                    <x-table.td><span class="text-caption font-medium text-neutral-700">WITA (Asia/Makassar)</span></x-table.td>
                    <x-table.td><span class="font-bold text-brand-700 tabular-nums">A-014</span> (Made Aryawan)</x-table.td>
                    <x-table.td><span class="font-bold tabular-nums">4 antrean</span></x-table.td>
                    <x-table.td><x-status-badge status="buka" size="sm" /></x-table.td>
                    <x-table.td><x-status-badge status="aktif" size="sm" /></x-table.td>
                    <x-table.td align="right">
                        <x-button size="sm" variant="ghost">Audit Detail</x-button>
                    </x-table.td>
                </tr>

                <tr>
                    <x-table.td>
                        <div class="font-bold text-neutral-900">Klinik Gigi Sehat Harmoni</div>
                        <div class="text-caption text-neutral-500">Kuta, Badung • Kesehatan</div>
                    </x-table.td>
                    <x-table.td><span class="text-caption font-medium text-neutral-700">WITA (Asia/Makassar)</span></x-table.td>
                    <x-table.td><span class="font-bold text-brand-700 tabular-nums">B-008</span> (Siti ********)</x-table.td>
                    <x-table.td><span class="font-bold tabular-nums">2 antrean</span></x-table.td>
                    <x-table.td><x-status-badge status="jeda" size="sm" /></x-table.td>
                    <x-table.td><x-status-badge status="aktif" size="sm" /></x-table.td>
                    <x-table.td align="right">
                        <x-button size="sm" variant="ghost">Audit Detail</x-button>
                    </x-table.td>
                </tr>

                <tr>
                    <x-table.td>
                        <div class="font-bold text-neutral-900">Salon Cantika Ubud</div>
                        <div class="text-caption text-neutral-500">Gianyar, Bali • Salon Kecantikan</div>
                    </x-table.td>
                    <x-table.td><span class="text-caption font-medium text-neutral-700">WITA (Asia/Makassar)</span></x-table.td>
                    <x-table.td><span class="text-neutral-400">—</span></x-table.td>
                    <x-table.td><span class="tabular-nums text-neutral-500">0 antrean</span></x-table.td>
                    <x-table.td><x-status-badge status="tutup" size="sm" /></x-table.td>
                    <x-table.td><x-status-badge status="aktif" size="sm" /></x-table.td>
                    <x-table.td align="right">
                        <x-button size="sm" variant="ghost">Audit Detail</x-button>
                    </x-table.td>
                </tr>
            </x-table>
        </x-card>
    </div>
</x-layouts.admin>

