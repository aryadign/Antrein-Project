<x-layouts.customer 
    title="Ambil Antrean — Barbershop Garasi"
    :business="[
        'name' => 'Barbershop Garasi',
        'category' => 'Barbershop & Grooming Pria',
        'status' => 'buka'
    ]"
>
    {{-- Responsive Layout: Desktop 2 Columns, Mobile/Tablet Single/Adaptive Column --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        
        {{-- Left Column (Desktop 4 cols): UMKM Profile & Hours --}}
        <div class="lg:col-span-5 space-y-6">
            <x-card padding="lg">
                <div class="flex items-start gap-4">
                    <div class="w-14 h-14 rounded-xl bg-brand-700 text-white font-extrabold text-2xl flex items-center justify-center shrink-0 shadow-sm">
                        BG
                    </div>
                    <div>
                        <span class="text-caption font-semibold uppercase tracking-wider text-brand-700 bg-brand-50 px-2 py-0.5 rounded border border-brand-200">
                            Barbershop & Grooming
                        </span>
                        <h1 class="text-h2 font-extrabold text-neutral-950 mt-1">Barbershop Garasi</h1>
                        <p class="text-body text-neutral-500 mt-0.5">Potong rambut modern pria dengan layanan ramah dan profesional.</p>
                    </div>
                </div>

                <div class="border-t border-neutral-200 pt-5 mt-6 space-y-3.5 text-body">
                    <div class="flex items-start gap-3 text-neutral-700">
                        <svg class="w-5 h-5 text-neutral-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <span>Jl. Teuku Umar No. 88, Denpasar Barat, Denpasar, Bali</span>
                    </div>

                    <div class="flex items-center gap-3 text-neutral-700">
                        <svg class="w-5 h-5 text-neutral-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>Jam Buka: <span class="font-medium text-neutral-900">10:00 – 21:00 WITA</span></span>
                    </div>

                    <div class="flex items-center gap-3 text-neutral-700">
                        <svg class="w-5 h-5 text-neutral-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                        <span>0812-3456-7890</span>
                    </div>
                </div>

                <div class="mt-6 pt-5 border-t border-neutral-200">
                    <div class="p-3 bg-brand-50 rounded-xl border border-brand-200 flex items-center justify-between">
                        <div class="text-caption font-medium text-brand-800">
                            Antrean Sedang Berjalan:
                        </div>
                        <div class="font-bold text-brand-700 text-lg tabular-nums">
                            A-014
                        </div>
                    </div>
                </div>
            </x-card>
        </div>

        {{-- Right Column (Desktop 7 cols): Service Catalog & Registration --}}
        <div class="lg:col-span-7 space-y-6">
            {{-- Service Selection Section --}}
            <div>
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-h3 font-bold text-neutral-950">Layanan & Pendaftaran Antrean</h2>
                        <p class="text-body text-neutral-500">Pilih salah satu layanan untuk mengambil nomor antrean hari ini.</p>
                    </div>
                    <span class="text-caption text-neutral-500">Pendaftaran Tanpa Login</span>
                </div>

                <div class="space-y-4">
                    {{-- Service 1 --}}
                    <x-card class="hover:border-brand-500 transition-all duration-150">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                            <div>
                                <div class="flex items-center gap-2">
                                    <h3 class="text-h3 font-bold text-neutral-900">Potong Rambut Dewasa</h3>
                                    <span class="text-caption px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 font-medium">Sisa 12 kuota</span>
                                </div>
                                <p class="text-body text-neutral-600 mt-1">Cuci rambut, potong model pria, pijat kepala ringan, dan pomade styling.</p>
                                <div class="mt-2.5 flex items-center gap-4 text-caption text-neutral-500">
                                    <span class="font-bold text-neutral-900 text-body">Rp 45.000</span>
                                    <span>•</span>
                                    <span>Durasi ±30 menit</span>
                                </div>
                            </div>
                            
                            <div class="sm:shrink-0">
                                <x-button variant="primary" class="w-full sm:w-auto" @click="$dispatch('open-modal', 'modal-take-queue')">
                                    Ambil Antrean
                                </x-button>
                            </div>
                        </div>
                    </x-card>

                    {{-- Service 2 --}}
                    <x-card class="hover:border-brand-500 transition-all duration-150">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                            <div>
                                <div class="flex items-center gap-2">
                                    <h3 class="text-h3 font-bold text-neutral-900">Cukur Jenggot & Kumis</h3>
                                    <span class="text-caption px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 font-medium">Sisa 8 kuota</span>
                                </div>
                                <p class="text-body text-neutral-600 mt-1">Shaving hangat dengan razor higienis dan aftershave soothing balm.</p>
                                <div class="mt-2.5 flex items-center gap-4 text-caption text-neutral-500">
                                    <span class="font-bold text-neutral-900 text-body">Rp 25.000</span>
                                    <span>•</span>
                                    <span>Durasi ±15 menit</span>
                                </div>
                            </div>
                            
                            <div class="sm:shrink-0">
                                <x-button variant="primary" class="w-full sm:w-auto" @click="$dispatch('open-modal', 'modal-take-queue')">
                                    Ambil Antrean
                                </x-button>
                            </div>
                        </div>
                    </x-card>

                    {{-- Service 3 (Quota Full Example) --}}
                    <x-card class="bg-neutral-50/70 border-neutral-200">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 opacity-75">
                            <div>
                                <div class="flex items-center gap-2">
                                    <h3 class="text-h3 font-bold text-neutral-700">Paket Grooming Lengkap</h3>
                                    <span class="text-caption px-2 py-0.5 rounded bg-rose-100 text-rose-700 font-medium">Kuota Penuh</span>
                                </div>
                                <p class="text-body text-neutral-500 mt-1">Potong rambut + Shaving + Creambath herbal + Pijat relaksasi bahu.</p>
                                <div class="mt-2.5 flex items-center gap-4 text-caption text-neutral-500">
                                    <span class="font-bold text-neutral-700 text-body">Rp 70.000</span>
                                    <span>•</span>
                                    <span>Durasi ±45 menit</span>
                                </div>
                            </div>
                            
                            <div class="sm:shrink-0">
                                <x-button variant="secondary" :disabled="true" class="w-full sm:w-auto">
                                    Kuota Habis
                                </x-button>
                            </div>
                        </div>
                    </x-card>
                </div>
            </div>

            {{-- Live Tracking Preview Component (Simulated Customer Ticket Monitor) --}}
            <div class="border-t border-neutral-200 pt-8">
                <div class="mb-4">
                    <span class="text-caption font-semibold tracking-wider uppercase text-brand-700 bg-brand-50 px-2 py-0.5 rounded border border-brand-200">
                        Simulasi Halaman Pemantauan Tiket
                    </span>
                    <h2 class="text-h3 font-bold text-neutral-950 mt-1">Tampilan Pelanggan Saat Menunggu</h2>
                </div>

                <x-card padding="lg" class="border-brand-200 shadow-md">
                    <div class="text-center py-4">
                        <x-status-badge status="menunggu" size="md" />

                        <div class="my-6">
                            <span class="text-caption text-neutral-500 font-semibold tracking-wider uppercase block">Nomor Antrean Anda</span>
                            <x-ticket-number number="A-018" size="hero" service="Potong Rambut Dewasa" />
                        </div>

                        <div class="grid grid-cols-2 gap-4 max-w-sm mx-auto p-4 bg-neutral-50 rounded-xl border border-neutral-200 mb-6">
                            <div>
                                <span class="text-caption text-neutral-500 block">Sedang Dilayani</span>
                                <span class="text-h3 font-bold text-neutral-900 tabular-nums">A-014</span>
                            </div>
                            <div>
                                <span class="text-caption text-neutral-500 block">Antrean Di Depan</span>
                                <span class="text-h3 font-bold text-brand-700 tabular-nums">3 Orang</span>
                            </div>
                        </div>

                        <p class="text-caption text-neutral-500 max-w-md mx-auto">
                            Halaman ini diperbarui secara otomatis melalui live polling. Harap hadir di lokasi usaha saat antrean mendekati giliran Anda.
                        </p>
                    </div>
                </x-card>
            </div>
        </div>
    </div>

    {{-- Take Queue Modal Form Preview --}}
    <x-modal name="modal-take-queue" title="Ambil Antrean Online">
        <form class="space-y-4">
            <div>
                <x-form-label for="cust_name" value="Nama Lengkap / Panggilan" :required="true" />
                <x-input id="cust_name" placeholder="Contoh: Budi Santoso" />
                <p class="text-caption text-neutral-500 mt-1">Nama ini akan dipanggil oleh kasir/operator saat giliran tiba.</p>
            </div>

            <div>
                <x-form-label for="cust_phone" value="Nomor WhatsApp (Opsional)" />
                <x-input id="cust_phone" placeholder="Contoh: 081234567890" />
                <p class="text-caption text-neutral-500 mt-1">Digunakan untuk verifikasi meja kasir di lokasi.</p>
            </div>

            <div class="p-3 bg-brand-50 rounded-xl border border-brand-200 text-caption text-brand-800">
                Layanan dipilih: <span class="font-bold">Potong Rambut Dewasa (Rp 45.000)</span>
            </div>
        </form>

        <x-slot name="footer">
            <x-button variant="secondary" @click="$dispatch('close-modal', 'modal-take-queue')">Batal</x-button>
            <x-button variant="primary" @click="$dispatch('close-modal', 'modal-take-queue'); $dispatch('notify', { type: 'success', text: 'Nomor antrean berhasil diambil!' })">
                Dapatkan Nomor Antrean
            </x-button>
        </x-slot>
    </x-modal>
</x-layouts.customer>

