@php
    $statusTabs = collect([
        ['key' => null, 'label' => 'Semua', 'count' => $stats['total']],
        ['key' => 'active', 'label' => 'Aktif', 'count' => $stats['active']],
        ['key' => 'inactive', 'label' => 'Nonaktif', 'count' => $stats['inactive']],
    ])->map(fn (array $tab): array => [
        'label' => $tab['label'],
        'count' => $tab['count'],
        'active' => ($status ?: null) === $tab['key'],
        'href' => route('admin.categories.index', array_filter(['status' => $tab['key'], 'search' => $search])),
    ])->all();
@endphp

<x-layouts.admin
    title="Manajemen Kategori UMKM"
    heading="Kategori UMKM"
    description="Kelompokkan jenis usaha agar pelanggan mudah menemukan UMKM. {{ number_format($stats['total_businesses']) }} UMKM terhubung ke kategori."
>
    <div class="space-y-5">
        @include('admin.partials.flash')

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            @include('admin.partials.filter-tabs', ['tabs' => $statusTabs, 'label' => 'Filter status kategori'])
            <x-button class="w-full shrink-0 sm:w-auto" onclick="document.getElementById('modal-add-category').showModal()">
                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Tambah kategori
            </x-button>
        </div>

        <form method="GET" action="{{ route('admin.categories.index') }}" class="flex flex-col gap-3 rounded-xl border border-neutral-200/80 bg-white p-3 shadow-card sm:p-4 lg:flex-row lg:items-end">
            @if ($status)
                <input type="hidden" name="status" value="{{ $status }}">
            @endif
            <div class="min-w-0 flex-1">
                <label for="category-search" class="mb-1.5 block text-sm font-medium text-neutral-800">Cari kategori</label>
                <x-input id="category-search" type="search" name="search" :value="$search" placeholder="Nama, slug, atau deskripsi">
                    <x-slot:icon>
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </x-slot:icon>
                </x-input>
            </div>
            <div class="flex gap-2 *:flex-1 sm:*:flex-none">
                <x-button type="submit">Terapkan</x-button>
                @if ($search)
                    <x-button variant="ghost" :href="route('admin.categories.index', array_filter(['status' => $status]))">Hapus filter</x-button>
                @endif
            </div>
        </form>

        @if ($categories->isEmpty())
            <x-empty-state
                title="Tidak ada kategori ditemukan"
                :description="$search || $status ? 'Tidak ada kategori yang cocok dengan filter ini.' : 'Tambahkan kategori pertama agar UMKM dapat memilih jenis usahanya.'"
            />
        @else
            {{-- Mobile: card list --}}
            <ul class="divide-y divide-neutral-100 overflow-hidden rounded-xl border border-neutral-200/80 bg-white shadow-card md:hidden" aria-label="Daftar kategori">
                @foreach ($categories as $cat)
                    <li class="p-4">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <p class="font-bold text-neutral-900">{{ $cat->name }}</p>
                                <p class="truncate text-xs text-neutral-600">{{ $cat->slug }}</p>
                            </div>
                            <x-status-badge :status="$cat->is_active ? 'aktif' : 'nonaktif'" size="sm" class="shrink-0" />
                        </div>
                        @if ($cat->description)
                            <p class="mt-2 line-clamp-2 text-xs text-neutral-600">{{ $cat->description }}</p>
                        @endif
                        <p class="mt-2 text-xs text-neutral-600"><strong class="text-neutral-800 tabular-nums">{{ $cat->businesses_count }}</strong> UMKM terdaftar</p>

                        <div class="mt-3 grid grid-cols-3 gap-2">
                            <form method="POST" action="{{ route('admin.categories.toggle-status', $cat) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="inline-flex h-11 w-full items-center justify-center rounded-lg border px-2 text-xs font-semibold transition {{ $cat->is_active ? 'border-neutral-300 text-neutral-700 hover:bg-neutral-100' : 'border-emerald-300 bg-emerald-50 text-emerald-700 hover:bg-emerald-100' }}">
                                    {{ $cat->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                </button>
                            </form>
                            <button type="button" onclick="openEditCategoryModal({{ json_encode($cat) }})" class="inline-flex h-11 items-center justify-center rounded-lg border border-neutral-300 px-2 text-xs font-semibold text-neutral-700 transition hover:bg-neutral-100">
                                Ubah
                            </button>
                            @if ($cat->businesses_count === 0)
                                <form method="POST" action="{{ route('admin.categories.destroy', $cat) }}" onsubmit="return confirm(@js('Hapus kategori '.$cat->name.'? Tindakan ini tidak dapat dibatalkan.'))">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="inline-flex h-11 w-full items-center justify-center rounded-lg border border-rose-200 px-2 text-xs font-semibold text-rose-700 transition hover:bg-rose-50">Hapus</button>
                                </form>
                            @else
                                <span class="inline-flex h-11 items-center justify-center rounded-lg bg-neutral-50 px-2 text-xs text-neutral-600" title="Masih dipakai UMKM, tidak dapat dihapus">Dipakai</span>
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
                            <x-table.th>Kategori</x-table.th>
                            <x-table.th class="hidden xl:table-cell">Deskripsi</x-table.th>
                            <x-table.th align="right">UMKM</x-table.th>
                            <x-table.th>Status</x-table.th>
                            <x-table.th align="right">Aksi</x-table.th>
                        </tr>
                    </x-slot:header>

                    @foreach ($categories as $cat)
                        <tr>
                            <x-table.td>
                                <p class="font-semibold text-neutral-950">{{ $cat->name }}</p>
                                <p class="text-xs text-neutral-600">{{ $cat->slug }}</p>
                            </x-table.td>
                            <x-table.td class="hidden text-sm text-neutral-600 xl:table-cell"><span class="block w-56 truncate" title="{{ $cat->description }}">{{ $cat->description ?: 'Belum ada deskripsi' }}</span></x-table.td>
                            <x-table.td align="right">{{ number_format($cat->businesses_count) }}</x-table.td>
                            <x-table.td>
                                <x-status-badge :status="$cat->is_active ? 'aktif' : 'nonaktif'" size="sm" />
                            </x-table.td>
                            <x-table.td align="right">
                                <div class="inline-flex items-center justify-end gap-2">
                                    <form method="POST" action="{{ route('admin.categories.toggle-status', $cat) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button
                                            type="submit"
                                            class="inline-flex h-9 items-center rounded-lg px-3 text-sm font-semibold text-neutral-700 transition-colors hover:bg-neutral-100"
                                            title="{{ $cat->is_active ? 'Nonaktifkan Kategori' : 'Aktifkan Kategori' }}"
                                        >
                                            {{ $cat->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                        </button>
                                    </form>
                                    <button type="button" onclick="openEditCategoryModal({{ json_encode($cat) }})" class="inline-flex h-9 items-center rounded-lg border border-neutral-300 bg-white px-3 text-sm font-semibold text-neutral-800 shadow-card transition-colors hover:bg-neutral-50">
                                        Ubah
                                    </button>
                                    @if ($cat->businesses_count === 0)
                                        <form method="POST" action="{{ route('admin.categories.destroy', $cat) }}" onsubmit="return confirm(@js('Hapus kategori '.$cat->name.'? Tindakan ini tidak dapat dibatalkan.'))">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-flex h-9 items-center rounded-lg border border-rose-200 bg-white px-3 text-sm font-semibold text-rose-700 transition-colors hover:bg-rose-50">Hapus</button>
                                        </form>
                                    @else
                                        <span class="inline-flex h-9 items-center px-3 text-sm text-neutral-600" title="Masih dipakai UMKM, tidak dapat dihapus">Dipakai</span>
                                    @endif
                                </div>
                            </x-table.td>
                        </tr>
                    @endforeach
                </x-table>
            </div>

            @if ($categories->hasPages())
                <x-pagination :paginator="$categories" />
            @endif
        @endif
    </div>

    {{-- Modal: Add Category --}}
    <dialog id="modal-add-category" class="m-auto max-h-[92dvh] w-[calc(100%-1.5rem)] max-w-lg rounded-2xl p-0 shadow-overlay backdrop:bg-neutral-950/50 max-sm:mb-0 max-sm:rounded-b-none">
        <div class="bg-white">
            <div class="flex items-center justify-between gap-3 border-b border-neutral-200 py-2 pl-5 pr-2 sm:pl-6">
                <h3 class="text-h3 font-semibold text-neutral-900">Tambah kategori</h3>
                <button type="button" onclick="document.getElementById('modal-add-category').close()" class="inline-flex size-11 shrink-0 items-center justify-center rounded-lg text-neutral-500 transition hover:bg-neutral-100 hover:text-neutral-700" aria-label="Tutup">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form method="POST" action="{{ route('admin.categories.store') }}" class="space-y-4 p-5 sm:p-6">
                @csrf

                <div>
                    <x-form-label for="add_name" value="Nama kategori" :required="true" />
                    <x-input id="add_name" name="name" required placeholder="Contoh: Barbershop & Salon" />
                </div>

                <div>
                    <x-form-label for="add_slug" value="Slug (opsional)" />
                    <x-input id="add_slug" name="slug" placeholder="Kosongkan untuk dibuat otomatis" />
                </div>

                <div>
                    <x-form-label for="add_description" value="Deskripsi singkat (opsional)" />
                    <x-textarea id="add_description" name="description" rows="3" placeholder="Contoh: Potong rambut, perawatan wajah, dan salon." />
                </div>

                <div class="flex items-center gap-2 pt-2">
                    <x-checkbox id="add_is_active" name="is_active" value="1" :checked="true" label="Langsung tampilkan kategori ini" />
                </div>

                <div class="grid grid-cols-2 gap-3 border-t border-neutral-200 pt-4 sm:flex sm:justify-end">
                    <x-button type="button" variant="secondary" class="h-11" onclick="document.getElementById('modal-add-category').close()">
                        Batal
                    </x-button>
                    <x-button type="submit" variant="primary" class="h-11">
                        Simpan kategori
                    </x-button>
                </div>
            </form>
        </div>
    </dialog>

    {{-- Modal: Edit Category --}}
    <dialog id="modal-edit-category" class="m-auto max-h-[92dvh] w-[calc(100%-1.5rem)] max-w-lg rounded-2xl p-0 shadow-overlay backdrop:bg-neutral-950/50 max-sm:mb-0 max-sm:rounded-b-none">
        <div class="bg-white">
            <div class="flex items-center justify-between gap-3 border-b border-neutral-200 py-2 pl-5 pr-2 sm:pl-6">
                <h3 class="text-h3 font-semibold text-neutral-900">Ubah kategori</h3>
                <button type="button" onclick="document.getElementById('modal-edit-category').close()" class="inline-flex size-11 shrink-0 items-center justify-center rounded-lg text-neutral-500 transition hover:bg-neutral-100 hover:text-neutral-700" aria-label="Tutup">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form id="form-edit-category" method="POST" action="" class="space-y-4 p-5 sm:p-6">
                @csrf
                @method('PUT')

                <div>
                    <x-form-label for="edit_name" value="Nama kategori" :required="true" />
                    <x-input id="edit_name" name="name" required />
                </div>

                <div>
                    <x-form-label for="edit_slug" value="Slug" :required="true" />
                    <x-input id="edit_slug" name="slug" required />
                </div>

                <div>
                    <x-form-label for="edit_description" value="Deskripsi singkat (opsional)" />
                    <x-textarea id="edit_description" name="description" rows="3" />
                </div>

                <div class="flex items-center gap-2 pt-2">
                    <x-checkbox id="edit_is_active" name="is_active" value="1" label="Kategori aktif" />
                </div>

                <div class="grid grid-cols-2 gap-3 border-t border-neutral-200 pt-4 sm:flex sm:justify-end">
                    <x-button type="button" variant="secondary" class="h-11" onclick="document.getElementById('modal-edit-category').close()">
                        Batal
                    </x-button>
                    <x-button type="submit" variant="primary" class="h-11">
                        Simpan perubahan
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

