<x-layouts.base title="Design System Showcase — AntreIn">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        {{-- Header --}}
        <div class="border-b border-neutral-200 pb-6 mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <span class="text-caption font-semibold tracking-wider uppercase text-brand-700 bg-brand-50 px-2.5 py-1 rounded-full border border-brand-200">
                    AntreIn Design Tokens & Reusable Component Library
                </span>
                <h1 class="text-display font-extrabold text-neutral-950 mt-2">Design System Testbed</h1>
                <p class="text-body text-neutral-500 mt-1">Verifikasi visual token warna, tipografi, radius, status badge, dan komponen antarmuka.</p>
            </div>
            <div class="flex items-center gap-2">
                <x-button variant="outline" href="/preview/admin">Layout Admin</x-button>
                <x-button variant="outline" href="/preview/merchant">Layout UMKM</x-button>
                <x-button variant="primary" href="/preview/customer">Layout Pelanggan</x-button>
            </div>
        </div>

        {{-- Section 1: Color Tokens & Status Badges --}}
        <div class="space-y-8">
            <x-card>
                <x-slot name="header">
                    <h2 class="text-h3 font-bold text-neutral-900">1. Queue Status & Operational Badges</h2>
                </x-slot>
                
                <div class="space-y-6">
                    <div>
                        <h4 class="text-label text-neutral-600 mb-2 font-medium">A. Queue Ticket Lifecycle Statuses</h4>
                        <div class="flex flex-wrap gap-2.5 items-center">
                            <x-status-badge status="menunggu" />
                            <x-status-badge status="dipanggil" />
                            <x-status-badge status="sedang_dilayani" />
                            <x-status-badge status="selesai" />
                            <x-status-badge status="dilewati" />
                            <x-status-badge status="dibatalkan" />
                        </div>
                    </div>

                    <div class="border-t border-neutral-100 pt-4">
                        <h4 class="text-label text-neutral-600 mb-2 font-medium">B. Business Operational Statuses</h4>
                        <div class="flex flex-wrap gap-2.5 items-center">
                            <x-status-badge status="buka" />
                            <x-status-badge status="jeda" />
                            <x-status-badge status="tutup" />
                        </div>
                    </div>

                    <div class="border-t border-neutral-100 pt-4">
                        <h4 class="text-label text-neutral-600 mb-2 font-medium">C. Platform Account Statuses</h4>
                        <div class="flex flex-wrap gap-2.5 items-center">
                            <x-status-badge status="aktif" />
                            <x-status-badge status="nonaktif" />
                            <x-status-badge status="suspend" />
                        </div>
                    </div>
                </div>
            </x-card>

            {{-- Section 2: Buttons & States --}}
            <x-card>
                <x-slot name="header">
                    <h2 class="text-h3 font-bold text-neutral-900">2. Buttons & Variants</h2>
                </x-slot>

                <div class="space-y-6">
                    <div>
                        <h4 class="text-label text-neutral-600 mb-2 font-medium">A. Button Variants</h4>
                        <div class="flex flex-wrap gap-3 items-center">
                            <x-button variant="primary">Primary Button</x-button>
                            <x-button variant="secondary">Secondary Button</x-button>
                            <x-button variant="outline">Outline Button</x-button>
                            <x-button variant="ghost">Ghost Button</x-button>
                            <x-button variant="danger">Destructive Button</x-button>
                            <x-button variant="link">Link Button</x-button>
                        </div>
                    </div>

                    <div class="border-t border-neutral-100 pt-4">
                        <h4 class="text-label text-neutral-600 mb-2 font-medium">B. Sizes & Loading State</h4>
                        <div class="flex flex-wrap gap-3 items-center">
                            <x-button size="sm">Small (32px)</x-button>
                            <x-button size="md">Medium (40px)</x-button>
                            <x-button size="lg">Large (48px)</x-button>
                            <x-button variant="primary" :loading="true">Loading...</x-button>
                            <x-button variant="primary" :disabled="true">Disabled</x-button>
                        </div>
                    </div>

                    <div class="border-t border-neutral-100 pt-4">
                        <h4 class="text-label text-neutral-600 mb-2 font-medium">C. Icon Buttons</h4>
                        <div class="flex flex-wrap gap-2 items-center">
                            <x-icon-button variant="primary" label="Panggil">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
                            </x-icon-button>
                            <x-icon-button variant="outline" label="Edit">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                            </x-icon-button>
                            <x-icon-button variant="danger" label="Hapus">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </x-icon-button>
                        </div>
                    </div>
                </div>
            </x-card>

            {{-- Section 3: Form Inputs --}}
            <x-card>
                <x-slot name="header">
                    <h2 class="text-h3 font-bold text-neutral-900">3. Form Components</h2>
                </x-slot>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <x-form-label for="sample_name" value="Nama Lengkap" :required="true" />
                        <x-input id="sample_name" placeholder="Contoh: Budi Santoso" />
                    </div>

                    <div>
                        <x-form-label for="sample_phone" value="Nomor WhatsApp (Opsional)" />
                        <x-input id="sample_phone" placeholder="081234567890" />
                    </div>

                    <div>
                        <x-form-label for="sample_service" value="Pilih Layanan" :required="true" />
                        <x-select id="sample_service" placeholder="-- Pilih Layanan --">
                            <option value="1">Potong Rambut Dewasa (Rp 45.000, 30 mnt)</option>
                            <option value="2">Cukur Jenggot & Kumis (Rp 25.000, 15 mnt)</option>
                            <option value="3">Paket Lengkap Grooming (Rp 70.000, 45 mnt)</option>
                        </x-select>
                    </div>

                    <div>
                        <x-form-label for="sample_err" value="Input dengan Error Validasi" :required="true" />
                        <x-input id="sample_err" value="Nilai tidak valid" :error="true" />
                        <x-input-error message="Format isian tidak sesuai dengan ketentuan." />
                    </div>

                    <div class="md:col-span-2">
                        <x-form-label for="sample_notes" value="Catatan Tambahan" />
                        <x-textarea id="sample_notes" placeholder="Tulis instruksi khusus jika ada...">Pelanggan minta potong rapi untuk acara wisuda.</x-textarea>
                    </div>

                    <div class="flex flex-col gap-3">
                        <x-checkbox label="Saya menyetujui syarat & ketentuan antrean digital" description="Nomor antrean hanya berlaku pada hari operasional ini." />
                    </div>

                    <div>
                        <x-toggle label="Buka Pendaftaran Online" description="Izinkan pelanggan umum mengambil tiket dari website." :checked="true" />
                    </div>
                </div>
            </x-card>

            {{-- Section 4: Data Display, Metrics & Ticket Indicators --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <x-stat-card title="Total Antrean Hari Ini" value="48" description="Dari kuota 60 tiket" trend="+12% vs kemarin" trendDirection="up" />
                <x-stat-card title="Sedang Menunggu" value="6" description="Waktu tunggu rata-rata 18 menit" />
                <x-stat-card title="Telah Selesai" value="39" description="Tingkat kehadiran 95%" trend="+5%" trendDirection="up" />
            </div>

            {{-- Section 5: Ticket Indicator Showcase --}}
            <x-card>
                <x-slot name="header">
                    <h2 class="text-h3 font-bold text-neutral-900">4. Queue Ticket Number Indicators</h2>
                </x-slot>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-8 py-4 text-center">
                    <div class="p-4 bg-neutral-50 rounded-xl border border-neutral-200">
                        <span class="text-caption text-neutral-500 font-medium block mb-2">Display Hero (Pelanggan)</span>
                        <x-ticket-number number="A-015" size="hero" service="Potong Rambut Dewasa" source="online" />
                    </div>

                    <div class="p-4 bg-brand-50/50 rounded-xl border border-brand-200">
                        <span class="text-caption text-brand-700 font-semibold block mb-2">Now Serving Card (UMKM)</span>
                        <x-ticket-number number="A-014" size="lg" service="Paket Grooming" source="walk_in" />
                    </div>

                    <div class="p-4 bg-neutral-50 rounded-xl border border-neutral-200">
                        <span class="text-caption text-neutral-500 font-medium block mb-2">Compact (Tabel Antrean)</span>
                        <x-ticket-number number="A-016" size="default" service="Cukur Jenggot" source="online" />
                    </div>
                </div>
            </x-card>

            {{-- Section 6: Data Table Component --}}
            <x-card padding="none">
                <x-slot name="header">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-h3 font-bold text-neutral-900">5. Data Table Component</h2>
                            <p class="text-caption text-neutral-500">Antrean Aktif Hari Ini (Asia/Makassar, WITA)</p>
                        </div>
                        <x-button size="sm" variant="outline">Ekspor Data</x-button>
                    </div>
                </x-slot>

                <x-table>
                    <x-slot name="header">
                        <tr>
                            <x-table.th>No. Tiket</x-table.th>
                            <x-table.th>Nama Pelanggan</x-table.th>
                            <x-table.th>Layanan</x-table.th>
                            <x-table.th>Sumber</x-table.th>
                            <x-table.th>Waktu Ambil</x-table.th>
                            <x-table.th>Status</x-table.th>
                            <x-table.th align="right">Aksi</x-table.th>
                        </tr>
                    </x-slot>

                    <tr>
                        <x-table.td><span class="font-bold tabular-nums text-neutral-900">A-014</span></x-table.td>
                        <x-table.td><span class="font-medium">Made Aryawan</span></x-table.td>
                        <x-table.td>Potong Rambut Dewasa</x-table.td>
                        <x-table.td><span class="text-xs px-2 py-0.5 rounded bg-blue-50 text-blue-700 font-medium">Online</span></x-table.td>
                        <x-table.td><span class="tabular-nums text-neutral-500">14:15 WITA</span></x-table.td>
                        <x-table.td><x-status-badge status="sedang_dilayani" size="sm" /></x-table.td>
                        <x-table.td align="right">
                            <x-button size="sm" variant="primary">Selesaikan</x-button>
                        </x-table.td>
                    </tr>

                    <tr>
                        <x-table.td><span class="font-bold tabular-nums text-neutral-900">A-015</span></x-table.td>
                        <x-table.td><span class="font-medium">Wayan Sukadana</span></x-table.td>
                        <x-table.td>Paket Grooming</x-table.td>
                        <x-table.td><span class="text-xs px-2 py-0.5 rounded bg-neutral-100 text-neutral-700 font-medium">Walk-in</span></x-table.td>
                        <x-table.td><span class="tabular-nums text-neutral-500">14:30 WITA</span></x-table.td>
                        <x-table.td><x-status-badge status="dipanggil" size="sm" /></x-table.td>
                        <x-table.td align="right">
                            <div class="flex items-center justify-end gap-1.5">
                                <x-button size="sm" variant="outline">Panggil Ulang</x-button>
                                <x-button size="sm" variant="primary">Mulai</x-button>
                            </div>
                        </x-table.td>
                    </tr>

                    <tr>
                        <x-table.td><span class="font-bold tabular-nums text-neutral-900">A-016</span></x-table.td>
                        <x-table.td><span class="font-medium">Ketut Darmawan</span></x-table.td>
                        <x-table.td>Cukur Jenggot</x-table.td>
                        <x-table.td><span class="text-xs px-2 py-0.5 rounded bg-blue-50 text-blue-700 font-medium">Online</span></x-table.td>
                        <x-table.td><span class="tabular-nums text-neutral-500">14:45 WITA</span></x-table.td>
                        <x-table.td><x-status-badge status="menunggu" size="sm" /></x-table.td>
                        <x-table.td align="right">
                            <x-button size="sm" variant="ghost">Detail</x-button>
                        </x-table.td>
                    </tr>
                </x-table>
            </x-card>

            {{-- Section 7: Overlays, Modals & Toast Triggers --}}
            <x-card x-data>
                <x-slot name="header">
                    <h2 class="text-h3 font-bold text-neutral-900">6. Overlays, Modals & Notifications</h2>
                </x-slot>

                <div class="flex flex-wrap gap-3 items-center">
                    <x-button variant="primary" @click="$dispatch('open-modal', 'demo-modal')">
                        Buka Modal Konfirmasi
                    </x-button>

                    <x-button variant="outline" @click="$dispatch('notify', { type: 'success', text: 'Tiket A-017 berhasil dibuat!' })">
                        Picu Toast Sukses
                    </x-button>

                    <x-button variant="outline" @click="$dispatch('notify', { type: 'danger', text: 'Kuota layanan telah habis untuk hari ini.' })">
                        Picu Toast Error
                    </x-button>

                    <x-button variant="outline" @click="$dispatch('notify', { type: 'warning', text: 'Nomor antrean Anda akan segera dipanggil!' })">
                        Picu Toast Peringatan
                    </x-button>
                </div>

                {{-- Demo Modal --}}
                <x-modal name="demo-modal" title="Konfirmasi Pemanggilan Antrean">
                    <p class="text-body text-neutral-600">
                        Apakah Anda yakin ingin memanggil tiket antrean nomor <span class="font-bold text-neutral-900">A-015</span> atas nama <span class="font-medium text-neutral-900">Wayan Sukadana</span>?
                    </p>

                    <x-slot name="footer">
                        <x-button variant="secondary" @click="$dispatch('close-modal', 'demo-modal')">Batal</x-button>
                        <x-button variant="primary" @click="$dispatch('close-modal', 'demo-modal'); $dispatch('notify', { type: 'info', text: 'Tiket A-015 sedang dipanggil.' })">
                            Panggil Sekarang
                        </x-button>
                    </x-slot>
                </x-modal>
            </x-card>

            {{-- Section 8: Empty State & Skeletons --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h3 class="text-label text-neutral-600 mb-2 font-medium">Empty State Pattern</h3>
                    <x-empty-state title="Antrean Belum Dibuka" description="Merchant belum membuka sesi antrean hari ini. Pendaftaran akan dibuka pada jam operasional.">
                        <x-button size="sm" variant="outline">Periksa Jadwal Buka</x-button>
                    </x-empty-state>
                </div>

                <div>
                    <h3 class="text-label text-neutral-600 mb-2 font-medium">Loading Skeletons</h3>
                    <div class="p-6 bg-white rounded-xl border border-neutral-200 space-y-4">
                        <x-skeleton variant="text" height="1.5rem" width="60%" />
                        <x-skeleton variant="text" height="1rem" width="90%" />
                        <x-skeleton variant="text" height="1rem" width="75%" />
                        <div class="flex gap-3 pt-2">
                            <x-skeleton variant="button" />
                            <x-skeleton variant="button" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.base>

