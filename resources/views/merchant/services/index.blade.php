<x-layouts.merchant title="Manajemen Layanan — AntreIn">
    <div class="space-y-6">
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-h2 font-bold text-neutral-950">Layanan Usaha</h1>
                <p class="text-body text-neutral-600 mt-1">Daftar layanan yang dapat dipilih pelanggan saat mengambil tiket antrean.</p>
            </div>
            <div>
                <x-button variant="primary" onclick="document.getElementById('modal-add-service').showModal()" class="flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Tambah Layanan
                </x-button>
            </div>
        </div>

        <!-- Notifications -->
        @if (session('status'))
            <div class="rounded-lg bg-emerald-50 border border-emerald-200 p-4 text-sm font-medium text-emerald-800 flex items-center gap-2">
                <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <span>{{ session('status') }}</span>
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-lg bg-rose-50 border border-rose-200 p-4 text-sm text-rose-800">
                <p class="font-semibold mb-1">Periksa kembali data layanan yang dimasukkan:</p>
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Stats Grid -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <x-stat-card label="Total Layanan" :value="$stats['total']" icon="scissors" />
            <x-stat-card label="Layanan Aktif" :value="$stats['active']" trend="Tersedia di antrean" icon="check" />
            <x-stat-card label="Layanan Nonaktif" :value="$stats['inactive']" trend="Disembunyikan" icon="clock" />
            <x-stat-card label="Rata-rata Durasi" :value="$stats['avg_duration'] . ' mnt'" icon="clock" />
        </div>

        <!-- Search & Filter Bar -->
        <x-card padding="md">
            <form method="GET" action="{{ route('merchant.services.index') }}" class="flex flex-col sm:flex-row gap-3">
                <div class="flex-1">
                    <x-input
                        name="search"
                        placeholder="Cari nama atau deskripsi layanan..."
                        :value="$search"
                    />
                </div>
                <div class="w-full sm:w-48">
                    <x-select name="status" onchange="this.form.submit()">
                        <option value="">Semua Status</option>
                        <option value="active" @selected($status === 'active')>Aktif Saja</option>
                        <option value="inactive" @selected($status === 'inactive')>Nonaktif Saja</option>
                    </x-select>
                </div>
                <div class="flex gap-2">
                    <x-button type="submit" variant="secondary">Filter</x-button>
                    @if ($search || $status)
                        <a href="{{ route('merchant.services.index') }}" class="inline-flex items-center px-3 py-2 border border-neutral-300 rounded-lg text-sm font-medium text-neutral-600 bg-white hover:bg-neutral-50">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </x-card>

        <!-- Services Table -->
        <x-card padding="none" class="overflow-hidden">
            <div class="overflow-x-auto">
                <x-table>
                    <x-slot:header>
                        <tr>
                            <x-table.th>Layanan</x-table.th>
                            <x-table.th class="text-center">Durasi</x-table.th>
                            <x-table.th class="text-right">Harga</x-table.th>
                            <x-table.th class="text-center">Kuota Harian</x-table.th>
                            <x-table.th class="text-center">Status</x-table.th>
                            <x-table.th class="text-right">Aksi</x-table.th>
                        </tr>
                    </x-slot:header>

                    @forelse ($services as $svc)
                        <tr class="hover:bg-neutral-50/60 transition-colors">
                            <x-table.td>
                                <div class="font-bold text-neutral-900">{{ $svc->name }}</div>
                                @if ($svc->description)
                                    <p class="text-xs text-neutral-500 mt-0.5 max-w-sm truncate">{{ $svc->description }}</p>
                                @endif
                            </x-table.td>
                            <x-table.td class="text-center whitespace-nowrap">
                                <span class="inline-flex items-center gap-1 text-xs font-semibold text-neutral-700 bg-neutral-100 px-2 py-0.5 rounded-full">
                                    <svg class="w-3 h-3 text-neutral-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    {{ $svc->duration_minutes }} menit
                                </span>
                            </x-table.td>
                            <x-table.td class="text-right whitespace-nowrap font-medium text-neutral-900">
                                @if ($svc->price > 0)
                                    Rp {{ number_format($svc->price, 0, ',', '.') }}
                                @else
                                    <span class="text-emerald-700 font-semibold">Gratis</span>
                                @endif
                            </x-table.td>
                            <x-table.td class="text-center whitespace-nowrap">
                                @if ($svc->daily_quota)
                                    <span class="text-xs font-semibold text-neutral-800 bg-neutral-100 px-2.5 py-0.5 rounded-full">
                                        {{ $svc->daily_quota }} tiket/hari
                                    </span>
                                @else
                                    <span class="text-xs text-neutral-400">Tidak dibatasi</span>
                                @endif
                            </x-table.td>
                            <x-table.td class="text-center whitespace-nowrap">
                                @if ($svc->is_active)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">
                                        Aktif
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-neutral-100 text-neutral-600">
                                        Nonaktif
                                    </span>
                                @endif
                            </x-table.td>
                            <x-table.td class="text-right whitespace-nowrap">
                                <div class="inline-flex items-center gap-1.5">
                                    <!-- Toggle Status Button -->
                                    <form method="POST" action="{{ route('merchant.services.toggle-status', $svc) }}" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <button
                                            type="submit"
                                            class="text-xs px-2.5 py-1 rounded-md border font-medium transition-colors {{ $svc->is_active ? 'border-neutral-300 text-neutral-700 hover:bg-neutral-100' : 'border-emerald-300 text-emerald-700 bg-emerald-50 hover:bg-emerald-100' }}"
                                            title="{{ $svc->is_active ? 'Sembunyikan dari pilihan tiket baru' : 'Aktifkan layanan ini' }}"
                                        >
                                            {{ $svc->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                        </button>
                                    </form>

                                    <!-- Edit Trigger Button -->
                                    <button
                                        type="button"
                                        onclick="openEditServiceModal({{ json_encode($svc) }})"
                                        class="text-xs px-2.5 py-1 rounded-md border border-neutral-300 text-neutral-700 hover:bg-neutral-100 font-medium transition-colors"
                                    >
                                        Edit
                                    </button>

                                    <!-- Delete Button (Soft delete) -->
                                    <form method="POST" action="{{ route('merchant.services.destroy', $svc) }}" class="inline" onsubmit="return confirm('Hapus layanan ini? (Riwayat tiket terdahulu tetap disimpan aman di sistem)')">
                                        @csrf
                                        @method('DELETE')
                                        <button
                                            type="submit"
                                            class="text-xs px-2.5 py-1 rounded-md border border-rose-200 text-rose-700 hover:bg-rose-50 font-medium transition-colors"
                                            title="Hapus Layanan"
                                        >
                                            Hapus
                                        </button>
                                    </form>
                                </div>
                            </x-table.td>
                        </tr>
                    @empty
                        <tr>
                            <x-table.td colspan="6" class="text-center py-12">
                                <x-empty-state
                                    title="Belum Ada Layanan"
                                    description="Tambahkan layanan yang ditawarkan usaha Anda agar pelanggan dapat memilih jenis layanan saat mengambil nomor antrean."
                                    icon="scissors"
                                />
                            </x-table.td>
                        </tr>
                    @endforelse
                </x-table>
            </div>

            @if ($services->hasPages())
                <div class="p-4 border-t border-neutral-200">
                    {{ $services->links() }}
                </div>
            @endif
        </x-card>
    </div>

    <!-- MODAL ADD SERVICE -->
    <dialog id="modal-add-service" class="rounded-xl shadow-xl p-0 w-full max-w-lg backdrop:bg-neutral-900/50">
        <div class="bg-white rounded-xl overflow-hidden border border-neutral-200">
            <div class="px-6 py-4 border-b border-neutral-200 flex items-center justify-between">
                <h3 class="text-h3 font-bold text-neutral-900">Tambah Layanan Baru</h3>
                <button type="button" onclick="document.getElementById('modal-add-service').close()" class="text-neutral-400 hover:text-neutral-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form method="POST" action="{{ route('merchant.services.store') }}" class="p-6 space-y-4">
                @csrf

                <div>
                    <x-form-label for="add_svc_name" value="Nama Layanan" :required="true" />
                    <x-input id="add_svc_name" name="name" required placeholder="Contoh: Potong Rambut Reguler" />
                </div>

                <div>
                    <x-form-label for="add_svc_description" value="Deskripsi Layanan (Opsional)" />
                    <x-textarea id="add_svc_description" name="description" rows="2" placeholder="Penjelasan mengenai cakupan pelayanan ini..." />
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-form-label for="add_svc_duration" value="Estimasi Durasi (Menit)" :required="true" />
                        <x-input id="add_svc_duration" name="duration_minutes" type="number" min="1" max="480" value="30" required />
                    </div>

                    <div>
                        <x-form-label for="add_svc_price" value="Biaya / Tarif (Rp)" />
                        <x-input id="add_svc_price" name="price" type="number" min="0" step="1000" value="0" placeholder="0 jika gratis" />
                    </div>
                </div>

                <div>
                    <x-form-label for="add_svc_quota" value="Kuota Harian Maksimal (Opsional)" />
                    <x-input id="add_svc_quota" name="daily_quota" type="number" min="1" placeholder="Kosongkan jika kuota tidak dibatasi" />
                    <p class="text-caption text-neutral-500 mt-1">Jika diatur, antrean untuk layanan ini akan ditutup otomatis ketika kuota harian tercapai.</p>
                </div>

                <div class="flex items-center gap-2 pt-2">
                    <x-checkbox id="add_svc_is_active" name="is_active" value="1" :checked="true" label="Aktifkan layanan ini sekarang" />
                </div>

                <div class="pt-4 flex justify-end gap-3 border-t border-neutral-200">
                    <x-button type="button" variant="secondary" onclick="document.getElementById('modal-add-service').close()">
                        Batal
                    </x-button>
                    <x-button type="submit" variant="primary">
                        Simpan Layanan
                    </x-button>
                </div>
            </form>
        </div>
    </dialog>

    <!-- MODAL EDIT SERVICE -->
    <dialog id="modal-edit-service" class="rounded-xl shadow-xl p-0 w-full max-w-lg backdrop:bg-neutral-900/50">
        <div class="bg-white rounded-xl overflow-hidden border border-neutral-200">
            <div class="px-6 py-4 border-b border-neutral-200 flex items-center justify-between">
                <h3 class="text-h3 font-bold text-neutral-900">Edit Layanan</h3>
                <button type="button" onclick="document.getElementById('modal-edit-service').close()" class="text-neutral-400 hover:text-neutral-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form id="form-edit-service" method="POST" action="" class="p-6 space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <x-form-label for="edit_svc_name" value="Nama Layanan" :required="true" />
                    <x-input id="edit_svc_name" name="name" required />
                </div>

                <div>
                    <x-form-label for="edit_svc_description" value="Deskripsi Layanan (Opsional)" />
                    <x-textarea id="edit_svc_description" name="description" rows="2" />
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-form-label for="edit_svc_duration" value="Estimasi Durasi (Menit)" :required="true" />
                        <x-input id="edit_svc_duration" name="duration_minutes" type="number" min="1" max="480" required />
                    </div>

                    <div>
                        <x-form-label for="edit_svc_price" value="Biaya / Tarif (Rp)" />
                        <x-input id="edit_svc_price" name="price" type="number" min="0" step="1000" />
                    </div>
                </div>

                <div>
                    <x-form-label for="edit_svc_quota" value="Kuota Harian Maksimal (Opsional)" />
                    <x-input id="edit_svc_quota" name="daily_quota" type="number" min="1" />
                </div>

                <div class="flex items-center gap-2 pt-2">
                    <x-checkbox id="edit_svc_is_active" name="is_active" value="1" label="Layanan Aktif" />
                </div>

                <div class="pt-4 flex justify-end gap-3 border-t border-neutral-200">
                    <x-button type="button" variant="secondary" onclick="document.getElementById('modal-edit-service').close()">
                        Batal
                    </x-button>
                    <x-button type="submit" variant="primary">
                        Simpan Perubahan
                    </x-button>
                </div>
            </form>
        </div>
    </dialog>

    <script>
        function openEditServiceModal(service) {
            const form = document.getElementById('form-edit-service');
            form.action = `/umkm/services/${service.id}`;
            document.getElementById('edit_svc_name').value = service.name;
            document.getElementById('edit_svc_description').value = service.description || '';
            document.getElementById('edit_svc_duration').value = service.duration_minutes;
            document.getElementById('edit_svc_price').value = Math.round(service.price);
            document.getElementById('edit_svc_quota').value = service.daily_quota || '';
            document.getElementById('edit_svc_is_active').checked = !!service.is_active;

            document.getElementById('modal-edit-service').showModal();
        }
    </script>
</x-layouts.merchant>

