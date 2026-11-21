<x-layouts.customer title="Cari Toko & Antrean" activeNav="beranda">
    @php
        $storeData = $businesses->map(fn ($business) => [
            'id' => $business->id,
            'title' => $business->name,
            'name' => Str::lower($business->name.' '.$business->category?->name.' '.$business->address.' '.$business->area),
            'category' => $business->category?->slug,
            'area' => $business->area ?? '',
            'open' => $business->isOpen(),
            'waiting' => (int) ($business->waiting_count ?? 0),
            'rating' => $business->customerRating(),
        ])->values();

        $categoryChips = collect([[
            'slug' => 'semua',
            'label' => 'Semua',
            'icon' => 'images/figma/layout-grid.svg',
            'tint' => 'bg-brand-700',
            'bg' => 'bg-brand-50',
        ]])->concat($categories->map(function ($category) {
            ['tint' => $tint, 'background' => $bg] = $category->customerPalette();

            return [
                'slug' => $category->slug,
                'label' => $category->shortName(),
                'icon' => $category->iconPath(),
                'tint' => $tint,
                'bg' => $bg,
            ];
        }));
    @endphp

    <div
        x-data="{
            category: @js($selectedCategory),
            area: @js($selectedArea),
            sort: @js($selectedSort),
            query: @js((string) request('q', '')),
            stores: @js($storeData),
            matches(store) {
                const term = this.query.trim().toLowerCase();
                return (this.category === 'semua' || store.category === this.category)
                    && (this.area === 'semua' || store.area === this.area)
                    && (term === '' || store.name.includes(term));
            },
            isVisible(id) {
                return this.matches(this.stores.find((store) => store.id === id));
            },
            get visibleCount() {
                return this.stores.filter((store) => this.matches(store)).length;
            },
            get orderMap() {
                const byName = (a, b) => a.title.localeCompare(b.title, 'id');
                const list = [...this.stores];
                if (this.sort === 'nama') {
                    list.sort(byName);
                } else if (this.sort === 'antrean') {
                    list.sort((a, b) => (b.open - a.open) || (a.waiting - b.waiting) || byName(a, b));
                } else {
                    list.sort((a, b) => (b.open - a.open) || ((b.rating ?? 0) - (a.rating ?? 0)) || (a.waiting - b.waiting) || byName(a, b));
                }
                return Object.fromEntries(list.map((store, index) => [store.id, index]));
            },
            selectCategory(slug) {
                this.category = slug;
                this.syncUrl();
            },
            selectArea(value) {
                if (this.area !== 'semua' && value === 'semua') {
                    window.location.assign('{{ route('customer.search') }}');
                    return;
                }
                this.area = value;
                this.syncUrl();
            },
            selectSort(key) {
                this.sort = key;
                this.syncUrl();
            },
            reset() {
                this.category = 'semua';
                this.area = 'semua';
                this.query = '';
                this.syncUrl();
            },
            syncUrl() {
                const url = new URL(window.location.href);
                this.category === 'semua' ? url.searchParams.delete('kategori') : url.searchParams.set('kategori', this.category);
                this.area === 'semua' ? url.searchParams.delete('daerah') : url.searchParams.set('daerah', this.area);
                this.sort === 'rekomendasi' ? url.searchParams.delete('urut') : url.searchParams.set('urut', this.sort);
                this.query.trim() === '' ? url.searchParams.delete('q') : url.searchParams.set('q', this.query.trim());
                window.history.replaceState({}, '', url);
            },
        }"
        x-init="$watch('query', () => syncUrl())"
    >
        {{-- Judul layar --}}
        <div class="border-b border-gray-200 bg-white px-5 pt-6 pb-4 md:border-0 md:bg-transparent md:px-0 md:pt-2 md:pb-6">
            <h1 class="text-2xl font-extrabold tracking-tight leading-8 text-gray-900 md:text-3xl md:leading-10">Cari Toko &amp; Antrean</h1>
            <p class="mt-1 text-sm leading-5 text-gray-600">Pilih toko berdasarkan daerah atau kategori, lalu ambil nomor antrean dari ponsel Anda.</p>
        </div>

        <div class="grid grid-cols-1 gap-5 px-5 pt-4 pb-5 md:px-0 md:pt-0 lg:grid-cols-12 lg:items-start lg:gap-8">
            {{-- Panel kiri: pencarian, daerah, kategori, urutan --}}
            <aside class="flex min-w-0 flex-col gap-4 lg:sticky lg:top-24 lg:col-span-4 lg:rounded-2xl lg:border lg:border-gray-200 lg:bg-white lg:p-5 lg:shadow-card">
                <label class="flex h-12 items-center gap-3 rounded-xl border border-gray-300 bg-white px-3.5 shadow-card transition focus-within:border-brand-700 focus-within:bg-white focus-within:ring-2 focus-within:ring-brand-100">
                    <x-customer.icon name="search" class="size-4.5 text-gray-500" />
                    <span class="sr-only">Cari toko atau antrean</span>
                    <input
                        type="search"
                        x-model.debounce.150ms="query"
                        @keydown.enter.prevent
                        placeholder="Cari nama kedai, klinik, atau jasa"
                        class="w-full min-w-0 border-0 bg-transparent p-0 text-sm leading-5 text-gray-900 placeholder:text-gray-500 focus:outline-none focus:ring-0"
                        @if (request()->filled('q')) autofocus @endif
                    />
                </label>

                {{-- Daerah / Wilayah --}}
                <div class="flex flex-col gap-2">
                    <div class="flex items-center justify-between gap-2">
                        <p class="flex items-center gap-1.5 text-sm font-semibold text-gray-900">
                            <x-customer.icon name="map-pin" class="size-4 text-brand-700" />
                            Daerah
                        </p>
                        <a x-show="area !== 'semua'" x-cloak href="{{ route('customer.search') }}" class="inline-flex min-h-9 items-center text-sm font-semibold text-brand-700 hover:underline">Hapus filter</a>
                    </div>
                    <div class="-mx-5 flex gap-2 overflow-x-auto px-5 pb-1 [scrollbar-width:none] lg:mx-0 lg:flex-wrap lg:overflow-visible lg:px-0" role="group" aria-label="Filter daerah">
                        <a
                            href="{{ route('customer.search', array_filter(['kategori' => $selectedCategory !== 'semua' ? $selectedCategory : null, 'urut' => $selectedSort !== 'rekomendasi' ? $selectedSort : null])) }}"
                            @click.prevent="selectArea('semua')"
                            :class="area === 'semua' ? 'border-brand-700 bg-brand-700 text-white shadow-card' : 'border-gray-200 bg-white text-gray-700 hover:border-gray-300 hover:bg-gray-50'"
                            class="inline-flex h-9 shrink-0 items-center rounded-full border px-3.5 text-sm font-medium whitespace-nowrap transition duration-150 ease-out"
                        >Semua Daerah</a>
                        @foreach ($areas as $areaOption)
                            <a
                                href="{{ route('customer.search', array_filter(['daerah' => $areaOption, 'kategori' => $selectedCategory !== 'semua' ? $selectedCategory : null])) }}"
                                @if ($selectedArea === 'semua') @click.prevent="selectArea(@js($areaOption))" @endif
                                :class="area === @js($areaOption) ? 'border-brand-700 bg-brand-700 text-white shadow-card' : 'border-gray-200 bg-white text-gray-700 hover:border-gray-300 hover:bg-gray-50'"
                                class="inline-flex h-9 shrink-0 items-center rounded-full border px-3.5 text-sm font-medium whitespace-nowrap transition duration-150 ease-out"
                            >{{ $areaOption }}</a>
                        @endforeach
                    </div>
                </div>

                {{-- Kategori: full grid tanpa horizontal cut-off / truncation --}}
                <div class="flex flex-col gap-2">
                    <p class="text-sm font-semibold text-gray-900">Kategori</p>
                    <div class="-mx-5 flex gap-2 overflow-x-auto px-5 pb-1 [scrollbar-width:none] lg:mx-0 lg:grid lg:grid-cols-2 lg:overflow-visible lg:px-0" role="group" aria-label="Kategori layanan">
                        @foreach ($categoryChips as $chip)
                            <button
                                type="button"
                                @click="selectCategory(@js($chip['slug']))"
                                :aria-pressed="category === @js($chip['slug'])"
                                :class="category === @js($chip['slug'])
                                    ? 'border-brand-700 bg-brand-700 text-white shadow-card'
                                    : 'border-gray-200 bg-white text-gray-900 hover:border-gray-300 hover:bg-gray-50'"
                                class="flex h-10 shrink-0 items-center gap-2 rounded-full border px-3.5 transition duration-150 ease-out active:scale-[0.97] lg:h-11 lg:w-full lg:rounded-lg"
                            >
                                <span
                                    class="size-5 shrink-0 transition-colors duration-200"
                                    :class="category === @js($chip['slug']) ? 'bg-white' : '{{ $chip['tint'] }}'"
                                    style="mask: url('{{ asset($chip['icon']) }}') center / contain no-repeat; -webkit-mask: url('{{ asset($chip['icon']) }}') center / contain no-repeat;"
                                    aria-hidden="true"
                                ></span>
                                <span class="text-sm font-medium whitespace-nowrap">{{ $chip['label'] }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>

                {{-- Urutkan --}}
                <div class="flex items-center justify-between gap-3">
                    <label for="store-sort" class="flex items-center gap-1.5 text-sm font-semibold text-gray-900">
                        <x-customer.icon name="arrow-up-down" class="size-4 text-brand-700" />
                        Urutkan
                    </label>
                    <select
                        id="store-sort"
                        x-model="sort"
                        @change="selectSort($event.target.value)"
                        class="h-10 min-w-0 rounded-lg border border-gray-300 bg-white pr-8 pl-3 text-sm font-medium text-gray-900 shadow-card focus:border-brand-600 focus:ring-2 focus:ring-brand-100 focus:outline-none"
                    >
                        @foreach ($sortOptions as $sortKey => $sortLabel)
                            <option value="{{ $sortKey }}" @selected($selectedSort === $sortKey)>{{ $sortLabel }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="hidden flex-col gap-3 border-t border-gray-100 pt-4 lg:flex">
                    <p class="text-sm font-semibold text-gray-900">Cara mengambil antrean</p>
                    <x-customer.how-it-works compact />
                </div>
            </aside>

            {{-- Panel kanan: hasil --}}
            <div class="flex min-w-0 flex-col gap-3.5 lg:col-span-8">
                <div class="flex items-center justify-between">
                    <h2 class="text-base font-extrabold leading-6 text-gray-900 md:text-lg">
                        Toko &amp; Antrean @if ($selectedArea !== 'semua') <span class="text-brand-700">di {{ $selectedArea }}</span> @endif
                    </h2>
                    <p class="rounded-full border border-gray-200 bg-white px-3 py-1 text-xs font-bold leading-4 text-gray-800"><span x-text="visibleCount">{{ $businesses->count() }}</span> toko</p>
                </div>

                <div class="grid grid-cols-1 gap-3.5 sm:grid-cols-2 md:gap-4">
                    @foreach ($businesses as $index => $business)
                        <x-customer.store-card
                            :business="$business"
                            x-show="isVisible({{ $business->id }})"
                            x-bind:style="{ order: orderMap[{{ $business->id }}] }"
                            :style="$selectedCategory !== 'semua' && $business->category?->slug !== $selectedCategory ? 'display: none' : 'order: '.$index"
                            x-transition:enter="transition duration-200 ease-out"
                            x-transition:enter-start="opacity-0 translate-y-1"
                            x-transition:enter-end="opacity-100 translate-y-0"
                        />
                    @endforeach
                </div>

                <div x-show="visibleCount === 0" x-cloak x-transition.opacity class="flex flex-col items-center gap-3 rounded-2xl border border-dashed border-gray-300 bg-white px-5 py-10 text-center">
                    <span class="flex size-12 items-center justify-center rounded-2xl bg-gray-100 text-gray-500">
                        <x-customer.icon name="search" class="size-6" />
                    </span>
                    <p class="text-sm font-bold leading-5 text-gray-900" x-text="query.trim() === '' && category !== 'semua' ? 'Belum ada toko di kategori ini' : 'Tidak ada toko yang cocok'">Tidak ada toko yang cocok</p>
                    <p class="text-xs leading-4 text-gray-600" x-text="query.trim() === '' && category !== 'semua' ? 'Toko baru akan segera bergabung. Lihat kategori lain dulu, yuk.' : 'Coba kata kunci atau kategori lain.'">Coba kata kunci atau kategori lain.</p>
                    <x-button size="sm" @click="reset()">Tampilkan semua toko</x-button>
                </div>

                <div class="flex items-center gap-2 rounded-xl border border-brand-200 bg-brand-50 p-3">
                    <img src="{{ asset('images/figma/info.svg') }}" alt="" width="16" height="16" class="size-4 shrink-0" />
                    <p class="flex-1 text-xs leading-4 text-gray-700">Waktu tunggu dapat berubah sesuai antrean.</p>
                </div>
            </div>
        </div>
    </div>
</x-layouts.customer>
