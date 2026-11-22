@props([
    'prefix',
    'categoryIcons',
    'colorThemes',
    'allowRemoveCover' => false,
])

<div class="space-y-5 border-t border-neutral-200 pt-5">
    <div>
        <h4 class="text-sm font-semibold text-neutral-900">Tampilan di halaman pelanggan</h4>
        <p class="mt-0.5 text-xs text-neutral-600">Pilih warna, ikon, dan foto sampul bawaan agar kategori mudah dikenali pelanggan. Biarkan &ldquo;Otomatis&rdquo; untuk mengikuti aturan bawaan sistem.</p>
    </div>

    {{-- Live Preview: Category Chip + Mini Store Card --}}
    <div class="rounded-xl border border-neutral-200 bg-neutral-50/80 p-3.5 sm:p-4">
        <div class="mb-2.5 flex items-center justify-between gap-2">
            <span class="text-xs font-semibold tracking-wide text-neutral-600 uppercase">Pratinjau langsung</span>
            <span class="rounded-full bg-white px-2 py-0.5 text-xs font-medium text-neutral-600 ring-1 ring-neutral-200" x-text="colorTheme ? ('Warna: ' + activePalette().label) : ('Warna: Otomatis (' + activePalette().label + ')')"></span>
        </div>

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-12 sm:items-center">
            {{-- Category Chip Preview --}}
            <div class="sm:col-span-5">
                <p class="mb-1.5 text-xs font-medium text-neutral-600">Chip kategori</p>
                <div class="flex items-center gap-2.5 rounded-xl border border-neutral-200 bg-white p-3 shadow-card">
                    <span class="flex size-9 shrink-0 items-center justify-center rounded-lg transition-colors" :class="activePalette().background">
                        <span class="size-5 transition-colors" :class="activePalette().tint" :style="maskStyle(activeIconUrl())" aria-hidden="true"></span>
                    </span>
                    <span class="flex min-w-0 flex-col">
                        <span class="truncate text-xs font-bold leading-tight text-neutral-900" x-text="shortName()"></span>
                        <span class="text-xs leading-tight text-neutral-600">3 toko</span>
                    </span>
                </div>
            </div>

            {{-- Mini Store Card Preview --}}
            <div class="sm:col-span-7">
                <p class="mb-1.5 text-xs font-medium text-neutral-600">Mini kartu toko</p>
                <div class="overflow-hidden rounded-xl border border-neutral-200 bg-white shadow-card">
                    <div class="relative isolate h-24 overflow-hidden transition-colors" :class="activePalette().background">
                        <template x-if="activeCoverUrl()">
                            <div class="contents">
                                <img :src="activeCoverUrl()" alt="Pratinjau foto sampul kategori" class="absolute inset-0 -z-10 size-full object-cover" />
                                <span class="absolute inset-0 -z-10 bg-linear-to-t from-gray-950/70 via-gray-950/15 to-gray-950/5" aria-hidden="true"></span>
                            </div>
                        </template>
                        <template x-if="! activeCoverUrl()">
                            <div class="contents">
                                <span class="absolute -right-3 -bottom-5 -z-10 size-24 opacity-20" :class="activePalette().tint" :style="maskStyle(activeIconUrl())" aria-hidden="true"></span>
                                <span class="absolute top-3 right-20 -z-10 size-6 opacity-10" :class="activePalette().tint" :style="maskStyle(activeIconUrl())" aria-hidden="true"></span>
                            </div>
                        </template>

                        <span class="absolute top-2.5 left-2.5 inline-flex items-center gap-1.5 rounded-full bg-white/95 px-2.5 py-0.5 text-xs font-semibold text-neutral-900 shadow-sm">
                            <span class="size-3.5 shrink-0" :class="activePalette().tint" :style="maskStyle(activeIconUrl())" aria-hidden="true"></span>
                            <span class="max-w-32 truncate" x-text="shortName()"></span>
                        </span>

                        <div class="absolute bottom-2.5 left-2.5 flex size-9 items-center justify-center rounded-lg bg-white shadow-md ring-2 ring-white">
                            <span class="text-xs font-bold" :class="activePalette().text" x-text="previewInitials()"></span>
                        </div>
                    </div>
                    <div class="flex items-center justify-between gap-2 px-3 py-2">
                        <span class="truncate text-xs font-bold text-neutral-900" x-text="'Contoh ' + shortName()"></span>
                        <span class="shrink-0 rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-800">Buka</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Color Theme Picker --}}
    <fieldset>
        <legend class="mb-2 block text-sm font-medium text-neutral-800">Tema warna</legend>
        <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
            <label
                class="relative flex cursor-pointer items-center gap-2 rounded-lg border px-3 py-2.5 text-xs font-semibold transition"
                :class="colorTheme === '' ? 'border-brand-600 bg-brand-50/70 text-brand-800 ring-1 ring-brand-600' : 'border-neutral-200 bg-white text-neutral-700 hover:bg-neutral-50'"
            >
                <input
                    type="radio"
                    name="color_theme"
                    value=""
                    x-model="colorTheme"
                    class="sr-only"
                />
                <span class="flex size-4 shrink-0 items-center justify-center rounded-full border border-dashed border-neutral-400 bg-neutral-100" aria-hidden="true"></span>
                <span class="truncate">Otomatis</span>
            </label>

            @foreach ($colorThemes as $theme)
                <label
                    class="relative flex cursor-pointer items-center gap-2 rounded-lg border px-3 py-2.5 text-xs font-semibold transition"
                    :class="colorTheme === '{{ $theme['key'] }}' ? 'border-brand-600 bg-brand-50/70 text-brand-800 ring-1 ring-brand-600' : 'border-neutral-200 bg-white text-neutral-700 hover:bg-neutral-50'"
                >
                    <input
                        type="radio"
                        name="color_theme"
                        value="{{ $theme['key'] }}"
                        x-model="colorTheme"
                        class="sr-only"
                    />
                    <span class="flex size-5 shrink-0 items-center justify-center rounded-full {{ $theme['background'] }} border {{ $theme['border'] }}" aria-hidden="true">
                        <span class="size-2.5 rounded-full {{ $theme['tint'] }}"></span>
                    </span>
                    <span class="truncate">{{ $theme['label'] }}</span>
                </label>
            @endforeach
        </div>
    </fieldset>

    {{-- Icon Picker --}}
    <fieldset>
        <legend class="mb-2 block text-sm font-medium text-neutral-800">Ikon kategori</legend>
        <div class="grid grid-cols-3 gap-2 sm:grid-cols-5">
            <label
                class="relative flex cursor-pointer flex-col items-center justify-center gap-1.5 rounded-xl border p-2.5 text-center transition"
                :class="icon === '' ? 'border-brand-600 bg-brand-50/70 text-brand-800 ring-1 ring-brand-600' : 'border-neutral-200 bg-white text-neutral-700 hover:bg-neutral-50'"
            >
                <input
                    type="radio"
                    name="icon"
                    value=""
                    x-model="icon"
                    class="sr-only"
                />
                <span class="flex size-9 items-center justify-center rounded-lg border border-dashed border-neutral-300 bg-neutral-50">
                    <span class="size-5" :class="activePalette().tint" :style="maskStyle(guessedIconUrl())" aria-hidden="true"></span>
                </span>
                <span class="w-full truncate text-xs font-semibold">Otomatis</span>
            </label>

            @foreach ($categoryIcons as $iconItem)
                <label
                    class="relative flex cursor-pointer flex-col items-center justify-center gap-1.5 rounded-xl border p-2.5 text-center transition"
                    :class="icon === '{{ $iconItem['key'] }}' ? 'border-brand-600 bg-brand-50/70 text-brand-800 ring-1 ring-brand-600' : 'border-neutral-200 bg-white text-neutral-700 hover:bg-neutral-50'"
                    title="{{ $iconItem['label'] }}"
                >
                    <input
                        type="radio"
                        name="icon"
                        value="{{ $iconItem['key'] }}"
                        x-model="icon"
                        class="sr-only"
                    />
                    <span
                        class="flex size-9 items-center justify-center rounded-lg transition-colors"
                        :class="activePalette().background"
                    >
                        <span
                            class="size-5 transition-colors"
                            :class="activePalette().tint"
                            style="mask: url('{{ $iconItem['url'] }}') center / contain no-repeat; -webkit-mask: url('{{ $iconItem['url'] }}') center / contain no-repeat;"
                            aria-hidden="true"
                        ></span>
                    </span>
                    <span class="w-full truncate text-xs font-medium">{{ Str::before($iconItem['label'], ' /') }}</span>
                </label>
            @endforeach
        </div>
    </fieldset>

    {{-- Default Cover Photo Upload --}}
    <div>
        <x-form-label for="{{ $prefix }}_cover" value="Foto sampul bawaan (opsional)" />
        @if ($allowRemoveCover)
            <input type="hidden" name="remove_cover" :value="removeCover ? '1' : '0'" />
        @endif

        <div class="mt-2 flex flex-col gap-3 rounded-xl border border-dashed border-neutral-300 bg-neutral-50/60 p-3.5 sm:flex-row sm:items-center">
            <div class="flex aspect-[16/9] w-full shrink-0 items-center justify-center overflow-hidden rounded-lg border border-neutral-200 bg-white shadow-card sm:w-36">
                <template x-if="hasCustomCover()">
                    <img
                        :src="previewCoverUrl || savedCoverUrl"
                        alt="Pratinjau foto sampul kategori"
                        class="size-full object-cover"
                    />
                </template>
                <span x-show="! hasCustomCover()" class="px-2 text-center text-xs text-neutral-500">
                    Bawaan sistem
                </span>
            </div>

            <div class="min-w-0 flex-1 space-y-1">
                <p class="text-xs text-neutral-600">Dipakai sebagai foto kartu toko jika UMKM belum mengunggah foto sampul sendiri. Format JPG, PNG, atau WebP, maksimal 4 MB.</p>
                <p x-show="coverFileName" x-text="coverFileName" class="truncate text-xs font-medium text-brand-700" style="display: none;"></p>
                <p x-show="removeCover && ! previewCoverUrl" class="text-xs font-medium text-rose-700" style="display: none;">Foto sampul khusus akan dihapus saat perubahan disimpan.</p>
            </div>

            <div class="flex shrink-0 gap-2">
                <label
                    for="{{ $prefix }}_cover"
                    class="relative inline-flex h-10 flex-1 cursor-pointer items-center justify-center rounded-lg border border-neutral-300 bg-white px-3.5 text-xs font-semibold text-neutral-800 shadow-card transition hover:bg-neutral-50 focus-within:ring-2 focus-within:ring-brand-500 sm:flex-none"
                >
                    <span x-text="hasCustomCover() ? 'Ganti foto' : 'Unggah foto'">Unggah foto</span>
                    <input
                        type="file"
                        id="{{ $prefix }}_cover"
                        name="cover"
                        accept="image/jpeg,image/png,image/webp"
                        class="sr-only"
                        x-ref="coverInput"
                        @change="pickCover($event)"
                    />
                </label>

                <button
                    type="button"
                    x-show="hasCustomCover()"
                    @click="clearCover()"
                    class="inline-flex h-10 items-center justify-center rounded-lg border border-rose-200 bg-rose-50 px-3.5 text-xs font-semibold text-rose-700 transition hover:bg-rose-100"
                    style="display: none;"
                >
                    Hapus foto
                </button>
            </div>
        </div>
    </div>
</div>
