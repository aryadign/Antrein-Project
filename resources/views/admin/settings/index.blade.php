@php
    $timezones = [
        'Asia/Makassar' => 'WITA (UTC+8), Asia/Makassar',
        'Asia/Jakarta' => 'WIB (UTC+7), Asia/Jakarta',
        'Asia/Jayapura' => 'WIT (UTC+9), Asia/Jayapura',
    ];
    $selectedTimezone = old('default_timezone', $settings['default_timezone']);
@endphp

<x-layouts.admin
    title="Pengaturan Platform"
    heading="Pengaturan Platform"
    description="Identitas, kontak bantuan, dan zona waktu bawaan yang dipakai di seluruh AntreIn."
>
    <div class="max-w-3xl space-y-6" x-data="{ logoPreview: @js($settings['logo_url'] ?? '') }">
        @include('admin.partials.flash')

        <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @method('PUT')

            <x-card padding="none">
                <x-slot:header>
                    <h2 class="text-h3 font-semibold text-neutral-900">Identitas platform</h2>
                    <p class="text-sm text-neutral-600">Nama dan logo yang tampil di halaman publik.</p>
                </x-slot:header>

                <div class="space-y-5 px-5 py-5 sm:px-6">
                    <div>
                        <label for="logo" class="mb-2 block text-sm font-medium text-neutral-800">Logo</label>
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                            <div class="flex size-20 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-neutral-200 bg-neutral-50">
                                <template x-if="logoPreview">
                                    <img :src="logoPreview" alt="Pratinjau logo" class="size-full object-contain p-2">
                                </template>
                                <template x-if="!logoPreview">
                                    <svg class="size-8 text-neutral-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" role="img" aria-label="Belum ada logo"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                </template>
                            </div>
                            <div class="min-w-0 flex-1 space-y-1.5">
                                <input
                                    type="file"
                                    name="logo"
                                    id="logo"
                                    accept="image/png,image/jpeg,image/webp,image/svg+xml"
                                    class="block w-full cursor-pointer text-sm text-neutral-700 file:mr-3 file:h-10 file:cursor-pointer file:rounded-lg file:border file:border-neutral-300 file:bg-white file:px-4 file:text-sm file:font-semibold file:text-neutral-800 hover:file:bg-neutral-50"
                                    @change="
                                        const file = $event.target.files[0];
                                        if (file) {
                                            const reader = new FileReader();
                                            reader.onload = (e) => { logoPreview = e.target.result; };
                                            reader.readAsDataURL(file);
                                        }
                                    "
                                >
                                <p class="text-xs text-neutral-600">PNG, JPG, WebP, atau SVG, maksimal 2 MB. Rasio persegi atau melebar paling rapi.</p>
                            </div>
                        </div>
                    </div>

                    <div>
                        <x-form-label for="platform_name" value="Nama platform" :required="true" />
                        <x-input id="platform_name" name="platform_name" required :value="old('platform_name', $settings['platform_name'])" />
                    </div>

                    <div>
                        <x-form-label for="platform_tagline" value="Slogan" />
                        <x-input id="platform_tagline" name="platform_tagline" :value="old('platform_tagline', $settings['platform_tagline'])" placeholder="Contoh: Antrean Lebih Teratur, Pelayanan Lebih Lancar." />
                    </div>
                </div>
            </x-card>

            <x-card padding="none">
                <x-slot:header>
                    <h2 class="text-h3 font-semibold text-neutral-900">Kontak bantuan &amp; zona waktu</h2>
                    <p class="text-sm text-neutral-600">Ditampilkan ke pelanggan dan UMKM yang membutuhkan bantuan.</p>
                </x-slot:header>

                <div class="space-y-5 px-5 py-5 sm:px-6">
                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <div>
                            <x-form-label for="contact_email" value="Email bantuan" :required="true" />
                            <x-input type="email" id="contact_email" name="contact_email" required :value="old('contact_email', $settings['contact_email'])" />
                        </div>
                        <div>
                            <x-form-label for="contact_phone" value="Telepon atau WhatsApp bantuan" :required="true" />
                            <x-input id="contact_phone" name="contact_phone" required :value="old('contact_phone', $settings['contact_phone'])" />
                        </div>
                    </div>

                    <div class="sm:max-w-sm">
                        <x-form-label for="default_timezone" value="Zona waktu bawaan" :required="true" />
                        <x-select id="default_timezone" name="default_timezone" required>
                            @foreach ($timezones as $value => $label)
                                <option value="{{ $value }}" @selected($selectedTimezone === $value)>{{ $label }}</option>
                            @endforeach
                        </x-select>
                        <p class="mt-1.5 text-xs text-pretty text-neutral-600">Dipakai untuk UMKM baru sampai pemiliknya mengatur zona waktu sendiri.</p>
                    </div>
                </div>

                <x-slot:footer>
                    <div class="flex justify-end">
                        <x-button type="submit" class="w-full sm:w-auto">Simpan pengaturan</x-button>
                    </div>
                </x-slot:footer>
            </x-card>
        </form>
    </div>
</x-layouts.admin>
