<x-layouts.admin title="Manajemen Kategori UMKM — AntreIn">
    <div class="space-y-6">
        <!-- Page Header & Action -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-h2 font-bold text-neutral-950">Kategori UMKM</h1>
                <p class="text-body text-neutral-600 mt-1">Kelola taksonomi dan klasifikasi jenis usaha UMKM di platform AntreIn.</p>
            </div>
            <div>
                <x-button variant="primary" onclick="document.getElementById('modal-add-category').showModal()" class="flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Tambah Kategori
                </x-button>
            </div>
        </div>

        <!-- Alert Notifications -->
        @if (session('status'))
            <div class="rounded-lg bg-emerald-50 border border-emerald-200 p-4 text-sm font-medium text-emerald-800 flex items-center gap-2">
                <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <span>{{ session('status') }}</span>
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-lg bg-rose-50 border border-rose-200 p-4 text-sm text-rose-800">
                <p class="font-semibold mb-1">Terjadi kesalahan pada data kategori:</p>
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Stats Grid -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <x-stat-card label="Total Kategori" :value="$stats['total']" icon="tag" />
            <x-stat-card label="Kategori Aktif" :value="$stats['active']" trend="Tersedia untuk UMKM" icon="check" />
            <x-stat-card label="Kategori Nonaktif" :value="$stats['inactive']" trend="Disembunyikan" icon="clock" />
            <x-stat-card label="Total UMKM Terhubung" :value="$stats['total_businesses']" icon="store" />
        </div>

        <!-- Search & Filter Bar -->
        <x-card padding="md">
            <form method="GET" action="{{ route('admin.categories.index') }}" class="flex flex-col sm:flex-row gap-3">
                <div class="flex-1">
                    <x-input
                        name="search"
                        placeholder="Cari nama kategori, slug, atau deskripsi..."
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
                        <a href="{{ route('admin.categories.index') }}" class="inline-flex items-center px-3 py-2 border border-neutral-300 rounded-lg text-sm font-medium text-neutral-600 bg-white hover:bg-neutral-50">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </x-card>

        <!-- Category Table -->
        <x-card padding="none" class="overflow-hidden">
            <div class="overflow-x-auto">
                <x-table>
                    <x-slot:header>
                        <tr>
                            <x-table.th>Nama Kategori</x-table.th>
                            <x-table.th>Slug Publik</x-table.th>
                            <x-table.th>Deskripsi</x-table.th>
                            <x-table.th class="text-center">UMKM Terdaftar</x-table.th>
                            <x-table.th class="text-center">Status</x-table.th>
                            <x-table.th class="text-right">Aksi</x-table.th>
                        </tr>
                    </x-slot:header>

                    @forelse ($categories as $cat)
                        <tr class="hover:bg-neutral-50/60 transition-colors">
                            <x-table.td class="font-semibold text-neutral-900">
                                {{ $cat->name }}
                            </x-table.td>
                            <x-table.td class="font-mono text-xs text-neutral-600">
                                {{ $cat->slug }}
                            </x-table.td>
                            <x-table.td class="text-neutral-600 max-w-xs truncate">
                                {{ $cat->description ?: '-' }}
                            </x-table.td>
                            <x-table.td class="text-center font-medium">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-neutral-100 text-neutral-800">
                                    {{ $cat->businesses_count }} UMKM
                                </span>
                            </x-table.td>
                            <x-table.td class="text-center">
                                @if ($cat->is_active)
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
                                    <form method="POST" action="{{ route('admin.categories.toggle-status', $cat) }}" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <button
                                            type="submit"
                                            class="text-xs px-2.5 py-1 rounded-md border font-medium transition-colors {{ $cat->is_active ? 'border-neutral-300 text-neutral-700 hover:bg-neutral-100' : 'border-emerald-300 text-emerald-700 bg-emerald-50 hover:bg-emerald-100' }}"
                                            title="{{ $cat->is_active ? 'Nonaktifkan Kategori' : 'Aktifkan Kategori' }}"
                                        >
                                            {{ $cat->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                        </button>
                                    </form>

                                    <!-- Edit Trigger Button -->
                                    <button
                                        type="button"
                                        onclick="openEditCategoryModal({{ json_encode($cat) }})"
                                        class="text-xs px-2.5 py-1 rounded-md border border-neutral-300 text-neutral-700 hover:bg-neutral-100 font-medium transition-colors"
                                    >
                                        Edit
                                    </button>

                                    <!-- Delete Button (Only if 0 businesses) -->
                                    @if ($cat->businesses_count === 0)
                                        <form method="POST" action="{{ route('admin.categories.destroy', $cat) }}" class="inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus kategori ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button
                                                type="submit"
                                                class="text-xs px-2.5 py-1 rounded-md border border-rose-200 text-rose-700 hover:bg-rose-50 font-medium transition-colors"
                                                title="Hapus Kategori"
                                            >
                                                Hapus
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-xs px-2 py-1 text-neutral-400 cursor-not-allowed" title="Kategori sedang digunakan oleh UMKM dan tidak dapat dihapus">
                                            Terkait
                                        </span>
                                    @endif
                                </div>
                            </x-table.td>
                        </tr>
                    @empty
                        <tr>
                            <x-table.td colspan="6" class="text-center py-10">
                                <x-empty-state
                                    title="Tidak Ada Kategori Ditemukan"
                                    description="Belum ada data kategori yang sesuai dengan kata kunci pencarian Anda."
                                    icon="tag"
                                />
                            </x-table.td>
                        </tr>
                    @endforelse
                </x-table>
            </div>

            @if ($categories->hasPages())
                <div class="p-4 border-t border-neutral-200">
                    {{ $categories->links() }}
                </div>
            @endif
        </x-card>
    </div>

    <!-- MODAL ADD CATEGORY -->
    <dialog id="modal-add-category" class="rounded-xl shadow-xl p-0 w-full max-w-lg backdrop:bg-neutral-900/50">
        <div class="bg-white rounded-xl overflow-hidden border border-neutral-200">
            <div class="px-6 py-4 border-b border-neutral-200 flex items-center justify-between">
                <h3 class="text-h3 font-bold text-neutral-900">Tambah Kategori Baru</h3>
                <button type="button" onclick="document.getElementById('modal-add-category').close()" class="text-neutral-400 hover:text-neutral-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form method="POST" action="{{ route('admin.categories.store') }}" class="p-6 space-y-4">
                @csrf

                <div>
                    <x-form-label for="add_name" value="Nama Kategori" :required="true" />
                    <x-input id="add_name" name="name" required placeholder="Contoh: Barbershop & Salon" />
                </div>

                <div>
                    <x-form-label for="add_slug" value="Slug Kategori (Opsional)" />
                    <x-input id="add_slug" name="slug" placeholder="Otomatis digenerate jika kosong (contoh: barbershop-salon)" />
                </div>

                <div>
                    <x-form-label for="add_description" value="Deskripsi Singkat (Opsional)" />
                    <x-textarea id="add_description" name="description" rows="3" placeholder="Penjelasan singkat mengenai kategori usaha ini..." />
                </div>

                <div class="flex items-center gap-2 pt-2">
                    <x-checkbox id="add_is_active" name="is_active" value="1" :checked="true" label="Aktifkan kategori ini sekarang" />
                </div>

                <div class="pt-4 flex justify-end gap-3 border-t border-neutral-200">
                    <x-button type="button" variant="secondary" onclick="document.getElementById('modal-add-category').close()">
                        Batal
                    </x-button>
                    <x-button type="submit" variant="primary">
                        Simpan Kategori
                    </x-button>
                </div>
            </form>
        </div>
    </dialog>

    <!-- MODAL EDIT CATEGORY -->
    <dialog id="modal-edit-category" class="rounded-xl shadow-xl p-0 w-full max-w-lg backdrop:bg-neutral-900/50">
        <div class="bg-white rounded-xl overflow-hidden border border-neutral-200">
            <div class="px-6 py-4 border-b border-neutral-200 flex items-center justify-between">
                <h3 class="text-h3 font-bold text-neutral-900">Edit Kategori UMKM</h3>
                <button type="button" onclick="document.getElementById('modal-edit-category').close()" class="text-neutral-400 hover:text-neutral-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form id="form-edit-category" method="POST" action="" class="p-6 space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <x-form-label for="edit_name" value="Nama Kategori" :required="true" />
                    <x-input id="edit_name" name="name" required />
                </div>

                <div>
                    <x-form-label for="edit_slug" value="Slug Kategori" :required="true" />
                    <x-input id="edit_slug" name="slug" required />
                </div>

                <div>
                    <x-form-label for="edit_description" value="Deskripsi Singkat (Opsional)" />
                    <x-textarea id="edit_description" name="description" rows="3" />
                </div>

                <div class="flex items-center gap-2 pt-2">
                    <x-checkbox id="edit_is_active" name="is_active" value="1" label="Kategori Aktif" />
                </div>

                <div class="pt-4 flex justify-end gap-3 border-t border-neutral-200">
                    <x-button type="button" variant="secondary" onclick="document.getElementById('modal-edit-category').close()">
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
        function openEditCategoryModal(category) {
            const form = document.getElementById('form-edit-category');
            form.action = `/admin/categories/${category.id}`;
            document.getElementById('edit_name').value = category.name;
            document.getElementById('edit_slug').value = category.slug;
            document.getElementById('edit_description').value = category.description || '';
            document.getElementById('edit_is_active').checked = !!category.is_active;

            document.getElementById('modal-edit-category').showModal();
        }
    </script>
</x-layouts.admin>
