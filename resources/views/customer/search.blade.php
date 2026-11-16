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
            <p class="mt-1 text-sm leading-5 text-gray-600">Pilih toko berdasarkan daerah atau kategori, lalu ambil nomor antrean dari HP Anda.</p>
        </div>

        <div class="grid grid-cols-1 gap-5 px-5 pt-4 pb-5 md:px-0 md:pt-0 lg:grid-cols-12 lg:items-start lg:gap-8">
            {{-- Panel kiri: pencarian, daerah, kategori, urutan --}}
            <aside class="flex min-w-0 flex-col gap-4 lg:sticky lg:top-24 lg:col-span-4 lg:rounded-2xl lg:border lg:border-gray-200 lg:bg-white lg:p-5 lg:shadow-xs">
                <label class="flex h-11 items-center gap-3 rounded-xl border-[1.5px] border-gray-300 bg-gray-50/90 px-3.5 shadow-xs transition focus-within:border-brand-700 focus-within:bg-white focus-within:ring-2 focus-within:ring-brand-100">
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
                        <p class="flex items-center gap-1.5 text-xs font-extrabold tracking-wide text-gray-500 uppercase">
                            <x-customer.icon name="map-pin" class="size-3.5 text-brand-700" />
                            Daerah / Wilayah
                        </p>
                        <a x-show="area !== 'semua'" x-cloak href="{{ route('customer.search') }}" class="text-[11px] font-bold text-brand-700 hover:underline">Reset</a>
                    </div>
                    <div class="flex flex-wrap gap-1.5" role="group" aria-label="Filter daerah">
                        <a
                            href="{{ route('customer.search', array_filter(['kategori' => $selectedCategory !== 'semua' ? $selectedCategory : null, 'urut' => $selectedSort !== 'rekomendasi' ? $selectedSort : null])) }}"
                            @click.prevent="selectArea('semua')"
                            :class="area === 'semua' ? 'border-brand-700 bg-brand-700 text-white' : 'border-gray-200 bg-white text-gray-700 hover:border-brand-300'"
                            class="inline-flex items-center rounded-full border px-3 py-1.5 text-xs font-bold transition"
                        >Semua Daerah</a>
                        @foreach ($areas as $areaOption)
                            <a
                                href="{{ route('customer.search', array_filter(['daerah' => $areaOption, 'kategori' => $selectedCategory !== 'semua' ? $selectedCategory : null])) }}"
                                @if ($selectedArea === 'semua') @click.prevent="selectArea(@js($areaOption))" @endif
                                :class="area === @js($areaOption) ? 'border-brand-700 bg-brand-700 text-white' : 'border-gray-200 bg-white text-gray-700 hover:border-brand-300'"
                                class="inline-flex items-center rounded-full border px-3 py-1.5 text-xs font-bold transition"
                            >{{ $areaOption }}</a>
                        @endforeach
                    </div>
                </div>

                {{-- Kategori: full grid tanpa horizontal cut-off / truncation --}}
                <div class="flex flex-col gap-2">
                    <p class="text-xs font-extrabold tracking-wide text-gray-500 uppercase">Kategori</p>
                    <div class="grid grid-cols-3 gap-2 sm:grid-cols-4 md:grid-cols-6 lg:grid-cols-2" role="group" aria-label="Kategori layanan">
                        @foreach ($categoryChips as $chip)
                            <button
                                type="button"
                                @click="selectCategory(@js($chip['slug']))"
                                :aria-pressed="category === @js($chip['slug'])"
                                :class="category === @js($chip['slug'])
                                    ? 'border-brand-700 bg-brand-700 text-white shadow-sm'
                                    : 'border-gray-200 bg-white text-gray-900 hover:border-brand-300 hover:bg-brand-50/50'"
                                class="flex min-h-[68px] min-w-0 w-full flex-col items-center justify-center gap-1.5 rounded-xl border-[1.5px] px-2 py-2.5 text-center transition duration-200 active:scale-[0.97]"
                            >
                                <span
                                    class="size-5 shrink-0 transition-colors duration-200"
                                    :class="category === @js($chip['slug']) ? 'bg-white' : '{{ $chip['tint'] }}'"
                                    style="mask: url('{{ asset($chip['icon']) }}') center / contain no-repeat; -webkit-mask: url('{{ asset($chip['icon']) }}') center / contain no-repeat;"
                                    aria-hidden="true"
                                ></span>
                                <span class="w-full wrap-break-word text-[11px] font-bold leading-tight sm:text-xs">{{ $chip['label'] }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>

                {{-- Urutkan: box/pill highlight jelas (UCD affordance) --}}
                <div class="flex flex-col gap-2">
                    <p class="flex items-center gap-1.5 text-xs font-extrabold tracking-wide text-gray-500 uppercase">
                        <x-customer.icon name="arrow-up-down" class="size-3.5 text-brand-700" />
                        Urutkan
                    </p>
                    <div class="flex flex-wrap gap-2 lg:flex-col" role="group" aria-label="Urutkan toko">
                        @foreach ($sortOptions as $sortKey => $sortLabel)
                            <button
                                type="button"
                                @click="selectSort(@js($sortKey))"
                                :aria-pressed="sort === @js($sortKey)"
                                :class="sort === @js($sortKey)
                                    ? 'border-brand-400 bg-brand-50 text-brand-800 shadow-xs'
                                    : 'border-gray-300 bg-white text-gray-800 hover:border-brand-300 hover:bg-gray-50'"
                                class="inline-flex min-h-10 items-center justify-between gap-2 rounded-xl border-[1.5px] px-3.5 py-2 text-xs font-bold transition duration-200 active:scale-[0.97] sm:text-sm lg:w-full"
                            >
                                <span class="flex items-center gap-2">
                                    <x-customer.icon name="check" class="size-4 shrink-0 text-brand-700" x-show="sort === '{{ $sortKey }}'" />
                                    <span>{{ $sortLabel }}</span>
                                </span>
                                <span x-show="sort === '{{ $sortKey }}'" class="hidden rounded-full bg-brand-700 px-2 py-0.5 text-[10px] font-bold text-white lg:inline">Aktif</span>
                            </button>
                        @endforeach
                    </div>
                </div>

                <div class="hidden flex-col gap-3 border-t border-gray-100 pt-4 lg:flex">
                    <p class="text-xs font-extrabold tracking-wide text-gray-500 uppercase">3 Langkah Mudah</p>
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
                    <button type="button" @click="reset()" class="rounded-full bg-brand-700 px-4 py-2 text-xs font-bold text-white transition hover:bg-blue-900">Tampilkan semua toko</button>
                </div>

                <div class="flex items-center gap-2 rounded-xl border border-brand-200 bg-brand-50 p-3">
                    <img src="{{ asset('images/figma/info.svg') }}" alt="" width="16" height="16" class="size-4 shrink-0" />
                    <p class="flex-1 text-xs leading-4 text-gray-700">Waktu tunggu dapat berubah sesuai antrean.</p>
                </div>
            </div>
        </div>
    </div>
</x-layouts.customer>
