<x-layouts.merchant 
    title="Meja Operasional Antrean" 
    heading="Console Antrean Operasional" 
    description="Kelola antrean pelanggan secara berurutan (FIFO) dalam satu loket terintegrasi."
    businessName="Barbershop Garasi"
    businessStatus="buka"
    businessSlug="barbershop-garasi"
>
    {{-- Top Action Bar & Quick Metrics --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-6">
        <x-stat-card title="Menunggu Giliran" value="4" description="Estimasi tunggu ~45 menit" />
        <x-stat-card title="Total Dilayani Hari Ini" value="18" description="Dari 30 kuota harian" trend="+20%" trendDirection="up" />
        <x-stat-card title="Waktu Layan Rata-rata" value="22 mnt" description="Per pelanggan selesai" />
    </div>

    {{-- Main Operational Arena --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Left 2 Columns: Now Serving Card (Most Dominant Element) --}}
        <div class="lg:col-span-2 space-y-6">
            <x-card padding="lg" class="border-2 border-brand-200 bg-gradient-to-b from-brand-50/40 to-white">
                <div class="flex items-center justify-between border-b border-neutral-200 pb-4 mb-6">
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-brand-600 animate-ping"></span>
                        <h3 class="text-h3 font-bold text-neutral-900 tracking-tight">Sedang Berlangsung (Now Serving)</h3>
                    </div>
                    <x-status-badge status="sedang_dilayani" size="md" />
                </div>

                {{-- Dominant Ticket Identity --}}
                <div class="py-6 text-center">
                    <span class="text-caption text-neutral-500 font-semibold tracking-wider uppercase block mb-1">Nomor Antrean Dipanggil</span>
                    <x-ticket-number number="A-014" size="hero" service="Potong Rambut Dewasa (30 mnt)" source="online" />
                    
                    <div class="mt-4 flex items-center justify-center gap-4 text-body text-neutral-700">
                        <span class="font-bold text-neutral-900">Made Aryawan</span>
                        <span>•</span>
                        <span class="text-neutral-500">0812-3456-7890</span>
                    </div>

                    <div class="mt-2 inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-50 text-brand-700 text-caption font-medium border border-blue-200">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Durasi Pelayanan Berjalan: <span class="font-bold tabular-nums">14:22</span>
                    </div>
                </div>

                {{-- Operational Lifecycle Buttons --}}
                <div class="border-t border-neutral-200 pt-6 mt-4 flex flex-wrap items-center justify-center sm:justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <x-button variant="outline" size="md">
                            <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
                            Panggil Ulang
                        </x-button>
                        <x-button variant="ghost" size="md" class="text-neutral-600 hover:text-danger-700">
                            Lewati
                        </x-button>
                    </div>

                    <div class="flex items-center gap-3">
                        <x-button variant="primary" size="lg" class="px-8 shadow-md">
                            <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Selesaikan Pelayanan
                        </x-button>
                    </div>
                </div>
            </x-card>

            {{-- FIFO Queue Stream Table --}}
            <x-card padding="none">
                <x-slot name="header">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-h3 font-bold text-neutral-900">Daftar Tunggu Antrean (FIFO)</h3>
                            <p class="text-caption text-neutral-500">Urutan pemanggilan berikutnya berdasarkan waktu pengambilan tiket.</p>
                        </div>
                        <span class="text-caption font-bold bg-neutral-100 text-neutral-700 px-2.5 py-1 rounded-full">
                            4 Menunggu
                        </span>
                    </div>
                </x-slot>

                <x-table>
                    <x-slot name="header">
                        <tr>
                            <x-table.th>No. Tiket</x-table.th>
                            <x-table.th>Pelanggan</x-table.th>
                            <x-table.th>Layanan</x-table.th>
                            <x-table.th>Sumber</x-table.th>
                            <x-table.th>Status</x-table.th>
                            <x-table.th align="right">Aksi</x-table.th>
                        </tr>
                    </x-slot>

                    <tr>
                        <x-table.td><span class="font-bold tabular-nums text-neutral-900 text-lg">A-015</span></x-table.td>
                        <x-table.td>
                            <div class="font-medium text-neutral-900">Wayan Sukadana</div>
                            <div class="text-[11px] text-neutral-500">0819-8765-4321</div>
                        </x-table.td>
                        <x-table.td>Paket Grooming</x-table.td>
                        <x-table.td><span class="text-xs px-2 py-0.5 rounded bg-neutral-100 text-neutral-700 font-medium">Walk-in</span></x-table.td>
                        <x-table.td><x-status-badge status="menunggu" size="sm" /></x-table.td>
                        <x-table.td align="right">
                            <x-button size="sm" variant="primary">Panggil</x-button>
                        </x-table.td>
                    </tr>

                    <tr>
                        <x-table.td><span class="font-bold tabular-nums text-neutral-900 text-lg">A-016</span></x-table.td>
                        <x-table.td>
                            <div class="font-medium text-neutral-900">Ketut Darmawan</div>
                            <div class="text-[11px] text-neutral-500">0813-1122-3344</div>
                        </x-table.td>
                        <x-table.td>Cukur Jenggot & Kumis</x-table.td>
                        <x-table.td><span class="text-xs px-2 py-0.5 rounded bg-blue-50 text-blue-700 font-medium">Online</span></x-table.td>
                        <x-table.td><x-status-badge status="menunggu" size="sm" /></x-table.td>
                        <x-table.td align="right">
                            <x-button size="sm" variant="ghost">Batalkan</x-button>
                        </x-table.td>
                    </tr>
                </x-table>
            </x-card>
        </div>

        {{-- Right Column: Operator Controls & Walk-In Intake --}}
        <div class="space-y-6">
            {{-- Quick Walk-In Intake Card --}}
            <x-card>
                <x-slot name="header">
                    <h3 class="text-h3 font-bold text-neutral-900">Tambah Antrean Manual</h3>
                </x-slot>

                <p class="text-body text-neutral-600 mb-4">
                    Catat pelanggan walk-in yang hadir langsung di lokasi usaha untuk digabungkan dalam antrean FIFO.
                </p>

                <x-button variant="primary" class="w-full h-11" @click="$dispatch('open-modal', 'modal-walkin')">
                    <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Tambah Antrean Walk-in
                </x-button>
            </x-card>

            {{-- Operational Status Switch Card --}}
            <x-card>
                <x-slot name="header">
                    <h3 class="text-h3 font-bold text-neutral-900">Pengaturan Sesi Hari Ini</h3>
                </x-slot>

                <div class="space-y-4">
                    <div>
                        <span class="text-label text-neutral-700 font-medium block mb-2">Ubah Status Operasional:</span>
                        <div class="grid grid-cols-3 gap-2">
                            <button class="py-2 text-center rounded-lg border-2 border-emerald-500 bg-emerald-50 text-emerald-800 font-bold text-caption transition">
                                Buka
                            </button>
                            <button class="py-2 text-center rounded-lg border border-neutral-300 bg-white text-neutral-700 font-medium text-caption hover:bg-neutral-50 transition">
                                Jeda
                            </button>
                            <button class="py-2 text-center rounded-lg border border-neutral-300 bg-white text-neutral-700 font-medium text-caption hover:bg-neutral-50 transition">
                                Tutup
                            </button>
                        </div>
                    </div>

                    <div class="text-caption text-neutral-500 pt-2 border-t border-neutral-100">
                        <span class="font-medium text-neutral-700">Zona Waktu:</span> WITA (Asia/Makassar)<br>
                        <span class="font-medium text-neutral-700">Reset Harian:</span> Pukul 00:00 WITA
                    </div>
                </div>
            </x-card>
        </div>
    </div>

    {{-- Walk-In Modal --}}
    <x-modal name="modal-walkin" title="Tambah Antrean Walk-in">
        <form class="space-y-4">
            <div>
                <x-form-label for="walkin_name" value="Nama Pelanggan" :required="true" />
                <x-input id="walkin_name" placeholder="Contoh: Pak Nyoman" />
            </div>

            <div>
                <x-form-label for="walkin_service" value="Pilih Layanan" :required="true" />
                <x-select id="walkin_service">
                    <option value="1">Potong Rambut Dewasa (Rp 45.000, 30 mnt)</option>
                    <option value="2">Cukur Jenggot & Kumis (Rp 25.000, 15 mnt)</option>
                    <option value="3">Paket Grooming Lengkap (Rp 70.000, 45 mnt)</option>
                </x-select>
            </div>

            <div>
                <x-form-label for="walkin_phone" value="Nomor Telepon (Opsional)" />
                <x-input id="walkin_phone" placeholder="08..." />
            </div>

            <div>
                <x-form-label for="walkin_notes" value="Catatan Meja Operator (Opsional)" />
                <x-textarea id="walkin_notes" placeholder="Catatan preferensi atau instruksi..."></x-textarea>
            </div>
        </form>

        <x-slot name="footer">
            <x-button variant="secondary" @click="$dispatch('close-modal', 'modal-walkin')">Batal</x-button>
            <x-button variant="primary" @click="$dispatch('close-modal', 'modal-walkin'); $dispatch('notify', { type: 'success', text: 'Tiket walk-in A-017 berhasil ditambahkan!' })">
                Terbitkan Tiket
            </x-button>
        </x-slot>
    </x-modal>
</x-layouts.merchant>

