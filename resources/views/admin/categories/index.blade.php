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

    $fallbackAssets = [
        'icons' => [
            'barbershop' => asset('images/icons/scissors.svg'),
            'salon' => asset('images/icons/sparkles.svg'),
            'klinik' => asset('images/icons/stethoscope.svg'),
            'kuliner_coffee' => asset('images/figma/coffee.svg'),
            'kuliner' => asset('images/figma/utensils.svg'),
            'jasa' => asset('images/icons/wrench.svg'),
            'umum' => asset('images/figma/store.svg'),
        ],
        'covers' => [
            'barbershop' => asset('images/stores/barbershop.jpg'),
            'klinik' => asset('images/stores/klinik.jpg'),
            'kuliner_coffee' => asset('images/stores/kopi.jpg'),
            'kuliner' => asset('images/stores/restoran.jpg'),
        ],
    ];
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
            <x-button class="w-full shrink-0 sm:w-auto" onclick="openAddCategoryModal()">
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
                    @php
                        ['background' => $catBg, 'tint' => $catTint] = $cat->customerPalette();
                        $catIconUrl = asset($cat->iconPath());
                        $catPayload = [
                            'id' => $cat->id,
                            'name' => $cat->name,
                            'slug' => $cat->slug,
                            'description' => $cat->description,
                            'is_active' => $cat->is_active,
                            'icon' => $cat->icon,
                            'color_theme' => $cat->color_theme,
                            'cover_url' => $cat->cover_path ? $cat->coverImageUrl() : null,
                            'update_url' => route('admin.categories.update', $cat),
                        ];
                        $deletePayload = [
                            'id' => $cat->id,
                            'name' => $cat->name,
                            'businesses_count' => $cat->businesses_count,
                            'delete_url' => route('admin.categories.destroy', $cat),
                        ];
                    @endphp
                    <li class="p-4">
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex min-w-0 items-center gap-3">
                                <span class="flex size-10 shrink-0 items-center justify-center rounded-lg {{ $catBg }}">
                                    <span class="size-5 {{ $catTint }}" style="mask: url('{{ $catIconUrl }}') center / contain no-repeat; -webkit-mask: url('{{ $catIconUrl }}') center / contain no-repeat;" aria-hidden="true"></span>
                                </span>
                                <div class="min-w-0">
                                    <p class="font-bold text-neutral-900">{{ $cat->name }}</p>
                                    <p class="truncate text-xs text-neutral-600">{{ $cat->slug }}</p>
                                </div>
                            </div>
                            <x-status-badge :status="$cat->is_active ? 'aktif' : 'nonaktif'" size="sm" class="shrink-0" />
                        </div>
                        @if ($cat->description)
                            <p class="mt-2 line-clamp-2 text-xs text-neutral-600">{{ $cat->description }}</p>
                        @endif
                        <div class="mt-2 flex flex-wrap items-center gap-2 text-xs text-neutral-600">
                            <span><strong class="text-neutral-800 tabular-nums">{{ $cat->businesses_count }}</strong> UMKM terdaftar</span>
                            @if ($cat->cover_path)
                                <span class="rounded-full bg-neutral-100 px-2 py-0.5 font-medium text-neutral-700">Foto sampul khusus</span>
                            @endif
                        </div>

                        <div class="mt-3 grid grid-cols-3 gap-2">
                            <form method="POST" action="{{ route('admin.categories.toggle-status', $cat) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="inline-flex h-11 w-full items-center justify-center rounded-lg border px-2 text-xs font-semibold transition {{ $cat->is_active ? 'border-neutral-300 text-neutral-700 hover:bg-neutral-100' : 'border-emerald-300 bg-emerald-50 text-emerald-700 hover:bg-emerald-100' }}">
                                    {{ $cat->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                </button>
                            </form>
                            <button type="button" onclick='openEditCategoryModal(@json($catPayload))' class="inline-flex h-11 items-center justify-center rounded-lg border border-neutral-300 px-2 text-xs font-semibold text-neutral-700 transition hover:bg-neutral-100">
                                Ubah
                            </button>
                            @if ($cat->businesses_count === 0)
                                <form method="POST" action="{{ route('admin.categories.destroy', $cat) }}" onsubmit="return confirm(@js('Hapus kategori '.$cat->name.'? Tindakan ini tidak dapat dibatalkan.'))">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="inline-flex h-11 w-full items-center justify-center rounded-lg border border-rose-200 px-2 text-xs font-semibold text-rose-700 transition hover:bg-rose-50">Hapus</button>
                                </form>
                            @else
                                <button
                                    type="button"
                                    onclick='openReassignDeleteModal(@json($deletePayload))'
                                    class="inline-flex h-11 w-full items-center justify-center rounded-lg border border-rose-200 px-2 text-xs font-semibold text-rose-700 transition hover:bg-rose-50"
                                    title="Pindahkan UMKM ke kategori lain lalu hapus"
                                >
                                    Hapus
                                </button>
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
                        @php
                            ['background' => $catBg, 'tint' => $catTint] = $cat->customerPalette();
                            $catIconUrl = asset($cat->iconPath());
                            $catPayload = [
                                'id' => $cat->id,
                                'name' => $cat->name,
                                'slug' => $cat->slug,
                                'description' => $cat->description,
                                'is_active' => $cat->is_active,
                                'icon' => $cat->icon,
                                'color_theme' => $cat->color_theme,
                                'cover_url' => $cat->cover_path ? $cat->coverImageUrl() : null,
                                'update_url' => route('admin.categories.update', $cat),
                            ];
                            $deletePayload = [
                                'id' => $cat->id,
                                'name' => $cat->name,
                                'businesses_count' => $cat->businesses_count,
                                'delete_url' => route('admin.categories.destroy', $cat),
                            ];
                        @endphp
                        <tr>
                            <x-table.td>
                                <div class="flex items-center gap-3">
                                    <span class="flex size-10 shrink-0 items-center justify-center rounded-lg {{ $catBg }}">
                                        <span class="size-5 {{ $catTint }}" style="mask: url('{{ $catIconUrl }}') center / contain no-repeat; -webkit-mask: url('{{ $catIconUrl }}') center / contain no-repeat;" aria-hidden="true"></span>
                                    </span>
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2">
                                            <p class="font-semibold text-neutral-950">{{ $cat->name }}</p>
                                            @if ($cat->cover_path)
                                                <span class="rounded-full bg-neutral-100 px-2 py-0.5 text-xs font-medium text-neutral-700">Foto khusus</span>
                                            @endif
                                        </div>
                                        <p class="text-xs text-neutral-600">{{ $cat->slug }}</p>
                                    </div>
                                </div>
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
                                    <button type="button" onclick='openEditCategoryModal(@json($catPayload))' class="inline-flex h-9 items-center rounded-lg border border-neutral-300 bg-white px-3 text-sm font-semibold text-neutral-800 shadow-card transition-colors hover:bg-neutral-50">
                                        Ubah
                                    </button>
                                    @if ($cat->businesses_count === 0)
                                        <form method="POST" action="{{ route('admin.categories.destroy', $cat) }}" onsubmit="return confirm(@js('Hapus kategori '.$cat->name.'? Tindakan ini tidak dapat dibatalkan.'))">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-flex h-9 items-center rounded-lg border border-rose-200 bg-white px-3 text-sm font-semibold text-rose-700 transition-colors hover:bg-rose-50">Hapus</button>
                                        </form>
                                    @else
                                        <button
                                            type="button"
                                            onclick='openReassignDeleteModal(@json($deletePayload))'
                                            class="inline-flex h-9 items-center rounded-lg border border-rose-200 bg-white px-3 text-sm font-semibold text-rose-700 transition-colors hover:bg-rose-50"
                                            title="Pindahkan UMKM ke kategori lain lalu hapus"
                                        >
                                            Hapus
                                        </button>
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
    <dialog id="modal-add-category" class="m-auto max-h-[92dvh] w-[calc(100%-1.5rem)] max-w-2xl overflow-clip rounded-2xl p-0 shadow-overlay backdrop:bg-neutral-950/50 max-sm:mb-0 max-sm:rounded-b-none">
        <div
            class="flex max-h-[92dvh] flex-col bg-white"
            x-data="categoryAppearanceForm()"
            @reset-add-category.window="reset()"
        >
            <div class="flex shrink-0 items-center justify-between gap-3 border-b border-neutral-200 py-2 pl-5 pr-2 sm:pl-6">
                <h3 class="text-h3 font-semibold text-neutral-900">Tambah kategori</h3>
                <button type="button" onclick="document.getElementById('modal-add-category').close()" class="inline-flex size-11 shrink-0 items-center justify-center rounded-lg text-neutral-500 transition hover:bg-neutral-100 hover:text-neutral-700" aria-label="Tutup">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form method="POST" action="{{ route('admin.categories.store') }}" enctype="multipart/form-data" class="flex min-h-0 flex-1 flex-col">
                @csrf

                <div class="flex-1 space-y-4 overflow-y-auto p-5 sm:p-6">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <x-form-label for="add_name" value="Nama kategori" :required="true" />
                            <x-input id="add_name" name="name" x-model="name" required placeholder="Contoh: Barbershop & Salon" />
                        </div>

                        <div>
                            <x-form-label for="add_slug" value="Slug (opsional)" />
                            <x-input id="add_slug" name="slug" x-model="slug" placeholder="Kosongkan untuk dibuat otomatis" />
                        </div>
                    </div>

                    <div>
                        <x-form-label for="add_description" value="Deskripsi singkat (opsional)" />
                        <x-textarea id="add_description" name="description" rows="2" placeholder="Contoh: Potong rambut, perawatan wajah, dan salon." />
                    </div>

                    @include('admin.categories._appearance-fields', [
                        'prefix' => 'add',
                        'categoryIcons' => $categoryIcons,
                        'colorThemes' => $colorThemes,
                        'allowRemoveCover' => false,
                    ])

                    <div class="flex items-center gap-2 pt-2">
                        <x-checkbox id="add_is_active" name="is_active" value="1" :checked="true" label="Langsung tampilkan kategori ini" />
                    </div>
                </div>

                <div class="grid shrink-0 grid-cols-2 gap-3 border-t border-neutral-200 bg-white px-5 py-4 sm:flex sm:justify-end sm:px-6">
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
    <dialog id="modal-edit-category" class="m-auto max-h-[92dvh] w-[calc(100%-1.5rem)] max-w-2xl overflow-clip rounded-2xl p-0 shadow-overlay backdrop:bg-neutral-950/50 max-sm:mb-0 max-sm:rounded-b-none">
        <div
            class="flex max-h-[92dvh] flex-col bg-white"
            x-data="categoryAppearanceForm()"
            @load-edit-category.window="load($event.detail)"
        >
            <div class="flex shrink-0 items-center justify-between gap-3 border-b border-neutral-200 py-2 pl-5 pr-2 sm:pl-6">
                <h3 class="text-h3 font-semibold text-neutral-900">Ubah kategori</h3>
                <button type="button" onclick="document.getElementById('modal-edit-category').close()" class="inline-flex size-11 shrink-0 items-center justify-center rounded-lg text-neutral-500 transition hover:bg-neutral-100 hover:text-neutral-700" aria-label="Tutup">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form id="form-edit-category" method="POST" action="" enctype="multipart/form-data" class="flex min-h-0 flex-1 flex-col">
                @csrf
                @method('PUT')

                <div class="flex-1 space-y-4 overflow-y-auto p-5 sm:p-6">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <x-form-label for="edit_name" value="Nama kategori" :required="true" />
                            <x-input id="edit_name" name="name" x-model="name" required />
                        </div>

                        <div>
                            <x-form-label for="edit_slug" value="Slug" :required="true" />
                            <x-input id="edit_slug" name="slug" x-model="slug" required />
                        </div>
                    </div>

                    <div>
                        <x-form-label for="edit_description" value="Deskripsi singkat (opsional)" />
                        <x-textarea id="edit_description" name="description" rows="2" />
                    </div>

                    @include('admin.categories._appearance-fields', [
                        'prefix' => 'edit',
                        'categoryIcons' => $categoryIcons,
                        'colorThemes' => $colorThemes,
                        'allowRemoveCover' => true,
                    ])

                    <div class="flex items-center gap-2 pt-2">
                        <x-checkbox id="edit_is_active" name="is_active" value="1" label="Kategori aktif" />
                    </div>
                </div>

                <div class="grid shrink-0 grid-cols-2 gap-3 border-t border-neutral-200 bg-white px-5 py-4 sm:flex sm:justify-end sm:px-6">
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

    {{-- Modal: Reassign & Delete Category --}}
    <dialog id="modal-delete-category" class="m-auto max-h-[92dvh] w-[calc(100%-1.5rem)] max-w-lg overflow-clip rounded-2xl p-0 shadow-overlay backdrop:bg-neutral-950/50 max-sm:mb-0 max-sm:rounded-b-none">
        <div
            class="flex max-h-[92dvh] flex-col bg-white"
            x-data="categoryReassignDeleteModal()"
            @load-delete-category.window="load($event.detail)"
        >
            <div class="flex shrink-0 items-center justify-between gap-3 border-b border-neutral-200 py-2 pl-5 pr-2 sm:pl-6">
                <h3 class="text-h3 font-semibold text-neutral-900">Pindahkan &amp; Hapus Kategori</h3>
                <button type="button" onclick="document.getElementById('modal-delete-category').close()" class="inline-flex size-11 shrink-0 items-center justify-center rounded-lg text-neutral-500 transition hover:bg-neutral-100 hover:text-neutral-700" aria-label="Tutup">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form method="POST" :action="deleteUrl" class="flex min-h-0 flex-1 flex-col">
                @csrf
                @method('DELETE')

                <div class="flex-1 space-y-4 overflow-y-auto p-5 sm:p-6">
                    <div class="rounded-xl border border-amber-200 bg-amber-50 p-3.5 text-sm text-amber-900">
                        <p class="font-semibold">Kategori ini masih dipakai oleh <span class="tabular-nums" x-text="businessesCount"></span> UMKM</p>
                        <p class="mt-1 text-xs leading-5 text-amber-800">
                            Semua UMKM di kategori <strong x-text="name"></strong> akan otomatis dipindahkan ke kategori pengganti sebelum kategori ini dihapus permanen.
                        </p>
                    </div>

                    <template x-if="options().length > 0">
                        <div>
                            <x-form-label for="delete_replacement_category_id" value="Pindahkan UMKM ke kategori" :required="true" />
                            <x-select id="delete_replacement_category_id" name="replacement_category_id" x-model="replacementId" required>
                                <option value="">Pilih kategori pengganti</option>
                                <template x-for="opt in options()" :key="opt.id">
                                    <option :value="opt.id" x-text="opt.is_active ? opt.name : (opt.name + ' (Nonaktif)')"></option>
                                </template>
                            </x-select>
                        </div>
                    </template>

                    <template x-if="options().length === 0">
                        <div class="rounded-xl border border-rose-200 bg-rose-50 p-3.5 text-xs leading-5 text-rose-800">
                            Belum ada kategori lain yang tersedia sebagai tujuan pemindahan. Tambahkan kategori baru terlebih dahulu sebelum menghapus kategori ini.
                        </div>
                    </template>
                </div>

                <div class="grid shrink-0 grid-cols-2 gap-3 border-t border-neutral-200 bg-white px-5 py-4 sm:flex sm:justify-end sm:px-6">
                    <x-button type="button" variant="secondary" class="h-11" onclick="document.getElementById('modal-delete-category').close()">
                        Batal
                    </x-button>
                    <button
                        type="submit"
                        :disabled="options().length === 0 || ! replacementId"
                        class="inline-flex h-11 items-center justify-center rounded-lg bg-rose-600 px-4 text-sm font-semibold text-white shadow-card transition hover:bg-rose-700 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        Pindahkan &amp; Hapus
                    </button>
                </div>
            </form>
        </div>
    </dialog>

    <script>
        window.__categoryAppearanceConfig = {
            icons: @js($categoryIcons),
            themes: @js($colorThemes),
            fallbacks: @js($fallbackAssets),
            allCategories: @js($allCategories),
        };

        function categoryAppearanceForm() {
            const config = window.__categoryAppearanceConfig;

            return {
                name: '',
                slug: '',
                icon: '',
                colorTheme: '',
                savedCoverUrl: '',
                previewCoverUrl: '',
                coverFileName: '',
                removeCover: false,

                reset() {
                    this.revokePreview();
                    this.name = '';
                    this.slug = '';
                    this.icon = '';
                    this.colorTheme = '';
                    this.savedCoverUrl = '';
                    this.coverFileName = '';
                    this.removeCover = false;
                    if (this.$refs.coverInput) {
                        this.$refs.coverInput.value = '';
                    }
                },

                load(category) {
                    this.revokePreview();
                    this.name = category.name || '';
                    this.slug = category.slug || '';
                    this.icon = category.icon || '';
                    this.colorTheme = category.color_theme || '';
                    this.savedCoverUrl = category.cover_url || '';
                    this.coverFileName = '';
                    this.removeCover = false;
                    if (this.$refs.coverInput) {
                        this.$refs.coverInput.value = '';
                    }
                },

                revokePreview() {
                    if (this.previewCoverUrl) {
                        URL.revokeObjectURL(this.previewCoverUrl);
                        this.previewCoverUrl = '';
                    }
                },

                effectiveSlug() {
                    const source = (this.slug || this.name || '').toLowerCase().trim();
                    return source.replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
                },

                guessedTheme() {
                    const slug = this.effectiveSlug();
                    const hasAny = (keywords) => keywords.some((word) => slug.includes(word));

                    if (hasAny(['barber', 'pangkas', 'rambut'])) {
                        return 'barbershop';
                    }
                    if (hasAny(['salon', 'kecantikan', 'spa'])) {
                        return 'salon';
                    }
                    if (hasAny(['klinik', 'dokter', 'kesehatan'])) {
                        return 'klinik';
                    }
                    if (hasAny(['kuliner', 'kafe', 'kopi', 'makan', 'minuman', 'resto'])) {
                        return 'kuliner';
                    }
                    if (hasAny(['jasa', 'servis', 'service', 'elektronik', 'bengkel', 'otomotif', 'motor', 'mobil', 'laundry'])) {
                        return 'jasa';
                    }
                    return 'umum';
                },

                activeThemeKey() {
                    if (this.colorTheme && config.themes[this.colorTheme]) {
                        return this.colorTheme;
                    }
                    return this.guessedTheme();
                },

                activePalette() {
                    return config.themes[this.activeThemeKey()] || config.themes.umum;
                },

                guessedIconUrl() {
                    const guessed = this.guessedTheme();
                    const slug = this.effectiveSlug();

                    if (guessed === 'kuliner') {
                        return (slug.includes('kopi') || slug.includes('kafe')) && ! slug.includes('kuliner')
                            ? config.fallbacks.icons.kuliner_coffee
                            : config.fallbacks.icons.kuliner;
                    }

                    return config.fallbacks.icons[guessed] || config.fallbacks.icons.umum;
                },

                activeIconUrl() {
                    if (this.icon && config.icons[this.icon]) {
                        return config.icons[this.icon].url;
                    }
                    return this.guessedIconUrl();
                },

                guessedStockCoverUrl() {
                    const guessed = this.guessedTheme();
                    const slug = this.effectiveSlug();

                    if (guessed === 'barbershop') {
                        return config.fallbacks.covers.barbershop;
                    }
                    if (guessed === 'klinik') {
                        return config.fallbacks.covers.klinik;
                    }
                    if (guessed === 'kuliner') {
                        return (slug.includes('kopi') || slug.includes('kafe'))
                            ? config.fallbacks.covers.kuliner_coffee
                            : config.fallbacks.covers.kuliner;
                    }
                    return '';
                },

                hasCustomCover() {
                    return Boolean(this.previewCoverUrl || (this.savedCoverUrl && ! this.removeCover));
                },

                activeCoverUrl() {
                    if (this.previewCoverUrl) {
                        return this.previewCoverUrl;
                    }
                    if (this.savedCoverUrl && ! this.removeCover) {
                        return this.savedCoverUrl;
                    }
                    return this.guessedStockCoverUrl();
                },

                shortName() {
                    const cleaned = (this.name || '').trim();
                    if (! cleaned) {
                        return 'Nama Kategori';
                    }
                    return cleaned.split(' &')[0] || cleaned;
                },

                previewInitials() {
                    const words = this.shortName().split(/\s+/).filter(Boolean).slice(0, 2);
                    return words.map((word) => word[0].toUpperCase()).join('') || 'AK';
                },

                maskStyle(url) {
                    return `mask: url('${url}') center / contain no-repeat; -webkit-mask: url('${url}') center / contain no-repeat;`;
                },

                pickCover(event) {
                    const file = event.target.files[0];
                    if (! file) {
                        return;
                    }
                    this.revokePreview();
                    this.coverFileName = file.name;
                    this.previewCoverUrl = URL.createObjectURL(file);
                    this.removeCover = false;
                },

                clearCover() {
                    this.revokePreview();
                    this.coverFileName = '';
                    if (this.$refs.coverInput) {
                        this.$refs.coverInput.value = '';
                    }
                    if (this.savedCoverUrl) {
                        this.removeCover = true;
                    }
                },
            };
        }

        function categoryReassignDeleteModal() {
            const config = window.__categoryAppearanceConfig;

            return {
                id: null,
                name: '',
                businessesCount: 0,
                deleteUrl: '',
                replacementId: '',

                load(category) {
                    this.id = category.id;
                    this.name = category.name || '';
                    this.businessesCount = Number(category.businesses_count || 0);
                    this.deleteUrl = category.delete_url || `/admin/categories/${category.id}`;
                    this.replacementId = '';
                },

                options() {
                    return (config.allCategories || []).filter((item) => item.id !== this.id);
                },
            };
        }

        function openAddCategoryModal() {
            const form = document.querySelector('#modal-add-category form');
            if (form) {
                form.reset();
            }
            window.dispatchEvent(new CustomEvent('reset-add-category'));
            document.getElementById('modal-add-category').showModal();
        }

        function openEditCategoryModal(category) {
            const form = document.getElementById('form-edit-category');
            form.action = category.update_url || `/admin/categories/${category.id}`;
            document.getElementById('edit_name').value = category.name;
            document.getElementById('edit_slug').value = category.slug;
            document.getElementById('edit_description').value = category.description || '';
            document.getElementById('edit_is_active').checked = !!category.is_active;

            window.dispatchEvent(new CustomEvent('load-edit-category', { detail: category }));
            document.getElementById('modal-edit-category').showModal();
        }

        function openReassignDeleteModal(category) {
            window.dispatchEvent(new CustomEvent('load-delete-category', { detail: category }));
            document.getElementById('modal-delete-category').showModal();
        }
    </script>
</x-layouts.admin>

