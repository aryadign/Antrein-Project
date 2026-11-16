<x-layouts.admin title="Manajemen Kategori UMKM">
    <div class="space-y-5 sm:space-y-6">
        {{-- Page Header & Action --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="min-w-0">
                <h1 class="text-h2 font-bold text-neutral-950">Kategori UMKM</h1>
                <p class="mt-1 text-body text-neutral-600">Kelola taksonomi dan klasifikasi jenis usaha UMKM di platform AntreIn.</p>
            </div>
            <button
                type="button"
                onclick="document.getElementById('modal-add-category').showModal()"
                class="inline-flex h-11 w-full shrink-0 items-center justify-center gap-2 rounded-lg bg-brand-700 px-4 text-sm font-bold text-white shadow-sm transition hover:bg-brand-800 sm:w-auto"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Tambah Kategori
            </button>
        </div>

        {{-- Alert Notifications --}}
        @if (session('status'))
            <div class="flex items-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-800" role="status">
                <svg class="w-5 h-5 shrink-0 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <span>{{ session('status') }}</span>
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-lg border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800" role="alert">
                <p class="mb-1 font-semibold">Terjadi kesalahan pada data kategori:</p>
                <ul class="list-inside list-disc space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Stats --}}
        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            <x-stat-tile label="Total Kategori" :value="$stats['total']" />
            <x-stat-tile label="Kategori Aktif" :value="$stats['active']" hint="Tersedia untuk UMKM" tone="success" />
            <x-stat-tile label="Kategori Nonaktif" :value="$stats['inactive']" hint="Disembunyikan" tone="muted" />
            <x-stat-tile label="Total UMKM Terhubung" :value="$stats['total_businesses']" tone="brand" />
        </div>

        {{-- Search & Filter --}}
        <form method="GET" action="{{ route('admin.categories.index') }}" class="flex flex-col gap-2 rounded-xl border border-neutral-200 bg-white p-3 shadow-xs sm:flex-row sm:items-center sm:gap-3">
            <label for="category-search" class="sr-only">Cari kategori</label>
            <input
                id="category-search"
                type="search"
                name="search"
                value="{{ $search }}"
                placeholder="Cari nama kategori, slug, atau deskripsi..."
                class="h-11 w-full min-w-0 flex-1 rounded-lg border-neutral-300 px-3 text-sm focus:border-brand-500 focus:ring-brand-500"
            >
            <div class="grid grid-cols-[minmax(0,1fr)_auto] gap-2 sm:flex">
                <label for="category-status" class="sr-only">Status kategori</label>
                <select id="category-status" name="status" onchange="this.form.submit()" class="h-11 min-w-0 rounded-lg border-neutral-300 text-sm focus:border-brand-500 focus:ring-brand-500 sm:w-44">
                    <option value="">Semua Status</option>
                    <option value="active" @selected($status === 'active')>Aktif Saja</option>
                    <option value="inactive" @selected($status === 'inactive')>Nonaktif Saja</option>
                </select>
                <button type="submit" class="inline-flex h-11 items-center justify-center rounded-lg border border-neutral-300 bg-white px-4 text-sm font-semibold text-neutral-700 transition hover:bg-neutral-50">Filter</button>
                @if ($search || $status)
                    <a href="{{ route('admin.categories.index') }}" class="col-span-2 inline-flex h-11 items-center justify-center rounded-lg px-3 text-sm font-semibold text-neutral-600 transition hover:bg-neutral-100 sm:col-span-1">
                        Reset
                    </a>
                @endif
            </div>
        </form>

        @if ($categories->isEmpty())
            <x-empty-state
                title="Tidak Ada Kategori Ditemukan"
                description="Belum ada data kategori yang sesuai dengan kata kunci pencarian Anda."
            />
        @else
            {{-- Mobile: card list --}}
            <ul class="space-y-3 md:hidden" aria-label="Daftar kategori">
                @foreach ($categories as $cat)
                    <li class="rounded-xl border border-neutral-200 bg-white p-4 shadow-xs">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <p class="font-bold text-neutral-900">{{ $cat->name }}</p>
                                <p class="truncate font-mono text-xs text-neutral-500">{{ $cat->slug }}</p>
                            </div>
                            <x-status-badge :status="$cat->is_active ? 'aktif' : 'nonaktif'" size="sm" class="shrink-0" />
                        </div>
                        @if ($cat->description)
                            <p class="mt-2 line-clamp-2 text-xs text-neutral-600">{{ $cat->description }}</p>
                        @endif
                        <p class="mt-2 text-xs text-neutral-500"><strong class="text-neutral-800 tabular-nums">{{ $cat->businesses_count }}</strong> UMKM terdaftar</p>

                        <div class="mt-3 grid grid-cols-3 gap-2">
                            <form method="POST" action="{{ route('admin.categories.toggle-status', $cat) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="inline-flex h-11 w-full items-center justify-center rounded-lg border px-2 text-xs font-semibold transition {{ $cat->is_active ? 'border-neutral-300 text-neutral-700 hover:bg-neutral-100' : 'border-emerald-300 bg-emerald-50 text-emerald-700 hover:bg-emerald-100' }}">
                                    {{ $cat->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                </button>
                            </form>
                            <button type="button" onclick="openEditCategoryModal({{ json_encode($cat) }})" class="inline-flex h-11 items-center justify-center rounded-lg border border-neutral-300 px-2 text-xs font-semibold text-neutral-700 transition hover:bg-neutral-100">
                                Edit
                            </button>
                            @if ($cat->businesses_count === 0)
                                <form method="POST" action="{{ route('admin.categories.destroy', $cat) }}" onsubmit="return confirm('Apakah Anda yakin ingin menghapus kategori ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="inline-flex h-11 w-full items-center justify-center rounded-lg border border-rose-200 px-2 text-xs font-semibold text-rose-700 transition hover:bg-rose-50">Hapus</button>
                                </form>
                            @else
                                <span class="inline-flex h-11 items-center justify-center rounded-lg bg-neutral-50 px-2 text-xs text-neutral-400" title="Kategori sedang digunakan oleh UMKM dan tidak dapat dihapus">Terkait</span>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>

            {{-- Tablet & desktop: table --}}
            <div class="hidden md:block">
                <x-table>
                    <x-slot:header>
                        <tr>
                            <x-table.th>Nama Kategori</x-table.th>
                            <x-table.th>Slug Publik</x-table.th>
                            <x-table.th class="hidden lg:table-cell">Deskripsi</x-table.th>
                            <x-table.th class="text-center">UMKM Terdaftar</x-table.th>
                            <x-table.th class="text-center">Status</x-table.th>
                            <x-table.th class="text-right">Aksi</x-table.th>
                        </tr>
                    </x-slot:header>

                    @foreach ($categories as $cat)
                        <tr class="transition-colors hover:bg-neutral-50/60">
                            <x-table.td class="font-semibold text-neutral-900">{{ $cat->name }}</x-table.td>
                            <x-table.td class="font-mono text-xs text-neutral-600">{{ $cat->slug }}</x-table.td>
                            <x-table.td class="hidden max-w-xs truncate text-neutral-600 lg:table-cell">{{ $cat->description ?: '-' }}</x-table.td>
                            <x-table.td class="text-center font-medium">
                                <span class="inline-flex items-center rounded-full bg-neutral-100 px-2 py-0.5 text-xs font-semibold text-neutral-800 tabular-nums">{{ $cat->businesses_count }} UMKM</span>
                            </x-table.td>
                            <x-table.td class="text-center">
                                <x-status-badge :status="$cat->is_active ? 'aktif' : 'nonaktif'" size="sm" />
                            </x-table.td>
                            <x-table.td class="text-right whitespace-nowrap">
                                <div class="inline-flex items-center gap-1.5">
                                    <form method="POST" action="{{ route('admin.categories.toggle-status', $cat) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button
                                            type="submit"
                                            class="inline-flex h-9 items-center rounded-md border px-2.5 text-xs font-semibold transition-colors {{ $cat->is_active ? 'border-neutral-300 text-neutral-700 hover:bg-neutral-100' : 'border-emerald-300 bg-emerald-50 text-emerald-700 hover:bg-emerald-100' }}"
                                            title="{{ $cat->is_active ? 'Nonaktifkan Kategori' : 'Aktifkan Kategori' }}"
                                        >
                                            {{ $cat->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                        </button>
                                    </form>
                                    <button type="button" onclick="openEditCategoryModal({{ json_encode($cat) }})" class="inline-flex h-9 items-center rounded-md border border-neutral-300 px-2.5 text-xs font-semibold text-neutral-700 transition-colors hover:bg-neutral-100">
                                        Edit
                                    </button>
                                    @if ($cat->businesses_count === 0)
                                        <form method="POST" action="{{ route('admin.categories.destroy', $cat) }}" onsubmit="return confirm('Apakah Anda yakin ingin menghapus kategori ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-flex h-9 items-center rounded-md border border-rose-200 px-2.5 text-xs font-semibold text-rose-700 transition-colors hover:bg-rose-50" title="Hapus Kategori">Hapus</button>
                                        </form>
                                    @else
                                        <span class="inline-flex h-9 items-center px-2 text-xs text-neutral-400" title="Kategori sedang digunakan oleh UMKM dan tidak dapat dihapus">Terkait</span>
                                    @endif
                                </div>
                            </x-table.td>
                        </tr>
                    @endforeach
                </x-table>
            </div>

            @if ($categories->hasPages())
                <div>{{ $categories->links() }}</div>
            @endif
        @endif
    </div>

    {{-- Modal: Add Category --}}
    <dialog id="modal-add-category" class="m-auto max-h-[92dvh] w-[calc(100%-1.5rem)] max-w-lg rounded-2xl p-0 shadow-xl backdrop:bg-neutral-900/50 max-sm:mb-0 max-sm:rounded-b-none">
        <div class="bg-white">
            <div class="flex items-center justify-between gap-3 border-b border-neutral-200 py-2 pl-5 pr-2 sm:pl-6">
                <h3 class="text-h3 font-bold text-neutral-900">Tambah Kategori Baru</h3>
                <button type="button" onclick="document.getElementById('modal-add-category').close()" class="inline-flex size-11 shrink-0 items-center justify-center rounded-lg text-neutral-400 transition hover:bg-neutral-100 hover:text-neutral-600" aria-label="Tutup">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form method="POST" action="{{ route('admin.categories.store') }}" class="space-y-4 p-5 sm:p-6">
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

                <div class="grid grid-cols-2 gap-3 border-t border-neutral-200 pt-4 sm:flex sm:justify-end">
                    <x-button type="button" variant="secondary" class="h-11" onclick="document.getElementById('modal-add-category').close()">
                        Batal
                    </x-button>
                    <x-button type="submit" variant="primary" class="h-11">
                        Simpan Kategori
                    </x-button>
                </div>
            </form>
        </div>
    </dialog>

    {{-- Modal: Edit Category --}}
    <dialog id="modal-edit-category" class="m-auto max-h-[92dvh] w-[calc(100%-1.5rem)] max-w-lg rounded-2xl p-0 shadow-xl backdrop:bg-neutral-900/50 max-sm:mb-0 max-sm:rounded-b-none">
        <div class="bg-white">
            <div class="flex items-center justify-between gap-3 border-b border-neutral-200 py-2 pl-5 pr-2 sm:pl-6">
                <h3 class="text-h3 font-bold text-neutral-900">Edit Kategori UMKM</h3>
                <button type="button" onclick="document.getElementById('modal-edit-category').close()" class="inline-flex size-11 shrink-0 items-center justify-center rounded-lg text-neutral-400 transition hover:bg-neutral-100 hover:text-neutral-600" aria-label="Tutup">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form id="form-edit-category" method="POST" action="" class="space-y-4 p-5 sm:p-6">
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

                <div class="grid grid-cols-2 gap-3 border-t border-neutral-200 pt-4 sm:flex sm:justify-end">
                    <x-button type="button" variant="secondary" class="h-11" onclick="document.getElementById('modal-edit-category').close()">
                        Batal
                    </x-button>
                    <x-button type="submit" variant="primary" class="h-11">
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

