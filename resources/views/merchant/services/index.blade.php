@php
    $editPayloadFor = fn ($service): array => array_merge($service->toArray(), ['image_url' => $service->imageUrl()]);
    $priceLabelFor = fn ($service): string => $service->price > 0 ? 'Rp '.number_format($service->price, 0, ',', '.') : 'Gratis';
    $deleteConfirmation = 'Hapus layanan ini? (Riwayat tiket terdahulu tetap disimpan aman di sistem)';
@endphp

<x-layouts.merchant title="Manajemen Layanan">
    <div class="space-y-5 sm:space-y-6">
        {{-- Page Header --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="min-w-0">
                <h1 class="text-[1.375rem] leading-[1.875rem] font-bold sm:text-[1.75rem] sm:leading-9 text-neutral-950">Layanan Usaha</h1>
                <p class="mt-1 text-body text-neutral-600">Daftar layanan yang dapat dipilih pelanggan saat mengambil tiket antrean.</p>
            </div>
            <button
                type="button"
                onclick="document.getElementById('modal-add-service').showModal()"
                class="inline-flex h-11 w-full shrink-0 items-center justify-center gap-2 rounded-lg bg-brand-700 px-4 text-sm font-bold text-white shadow-sm transition hover:bg-brand-800 sm:w-auto"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Tambah Layanan
            </button>
        </div>

        {{-- Notifications --}}
        @if (session('status'))
            <div class="flex items-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-800" role="status">
                <svg class="w-5 h-5 shrink-0 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <span>{{ session('status') }}</span>
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-lg border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800" role="alert">
                <p class="mb-1 font-semibold">Periksa kembali data layanan yang dimasukkan:</p>
                <ul class="list-inside list-disc space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Stats --}}
        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            <x-stat-tile label="Total Layanan" :value="$stats['total']" icon="layers" />
            <x-stat-tile label="Layanan Aktif" :value="$stats['active']" hint="Tersedia di antrean" tone="success" icon="check" />
            <x-stat-tile label="Layanan Nonaktif" :value="$stats['inactive']" hint="Disembunyikan" tone="muted" icon="pause" />
            <x-stat-tile label="Rata-rata Durasi" :value="$stats['avg_duration'].' mnt'" tone="brand" icon="clock" />
        </div>

        {{-- Search & Filter --}}
        <form method="GET" action="{{ route('merchant.services.index') }}" class="flex flex-col gap-2 rounded-xl border border-neutral-200 bg-white p-3 shadow-card sm:flex-row sm:items-center sm:gap-3">
            <label for="service-search" class="sr-only">Cari layanan</label>
            <input
                id="service-search"
                type="search"
                name="search"
                value="{{ $search }}"
                placeholder="Cari nama atau deskripsi layanan..."
                class="border placeholder:text-neutral-500 h-11 w-full min-w-0 flex-1 rounded-lg border-neutral-300 px-3 text-sm focus:border-brand-500 focus:ring-brand-500"
            >
            <div class="grid grid-cols-[minmax(0,1fr)_auto] gap-2 sm:flex">
                <label for="service-status" class="sr-only">Status layanan</label>
                <select id="service-status" name="status" onchange="this.form.submit()" class="border h-11 min-w-0 rounded-lg border-neutral-300 text-sm focus:border-brand-500 focus:ring-brand-500 sm:w-44">
                    <option value="">Semua Status</option>
                    <option value="active" @selected($status === 'active')>Aktif Saja</option>
                    <option value="inactive" @selected($status === 'inactive')>Nonaktif Saja</option>
                </select>
                <button type="submit" class="inline-flex h-11 items-center justify-center rounded-lg border border-neutral-300 bg-white px-4 text-sm font-semibold text-neutral-700 transition hover:bg-neutral-50">Filter</button>
                @if ($search || $status)
                    <a href="{{ route('merchant.services.index') }}" class="col-span-2 inline-flex h-11 items-center justify-center rounded-lg px-3 text-sm font-semibold text-neutral-600 transition hover:bg-neutral-100 sm:col-span-1">
                        Reset
                    </a>
                @endif
            </div>
        </form>

        @if ($services->isEmpty())
            <x-empty-state
                title="Belum Ada Layanan"
                description="Tambahkan layanan yang ditawarkan usaha Anda agar pelanggan dapat memilih jenis layanan saat mengambil nomor antrean."
            />
        @else
            {{-- Mobile: card list --}}
            <ul class="space-y-3 xl:hidden" aria-label="Daftar layanan">
                @foreach ($services as $svc)
                    <li class="rounded-xl border border-neutral-200 bg-white p-4 shadow-card {{ $svc->is_active ? '' : 'opacity-90' }}">
                        <div class="flex items-start gap-3">
                            <x-customer.service-thumb :service="$svc" :category="$business->category" class="size-14" />
                            <div class="min-w-0 flex-1">
                                <div class="flex items-start justify-between gap-2">
                                    <p class="min-w-0 font-bold text-neutral-900">{{ $svc->name }}</p>
                                    <x-status-badge :status="$svc->is_active ? 'aktif' : 'nonaktif'" size="sm" />
                                </div>
                                @if ($svc->description)
                                    <p class="mt-0.5 line-clamp-2 text-xs text-neutral-500">{{ $svc->description }}</p>
                                @endif
                            </div>
                        </div>

                        <dl class="mt-3 grid grid-cols-3 gap-2 text-center">
                            <div class="rounded-lg bg-neutral-50 px-2 py-1.5">
                                <dt class="text-xs text-neutral-500">Durasi</dt>
                                <dd class="text-sm font-bold text-neutral-900 tabular-nums">{{ $svc->duration_minutes }} mnt</dd>
                            </div>
                            <div class="rounded-lg bg-neutral-50 px-2 py-1.5">
                                <dt class="text-xs text-neutral-500">Harga</dt>
                                <dd class="truncate text-sm font-bold tabular-nums {{ $svc->price > 0 ? 'text-neutral-900' : 'text-emerald-700' }}">{{ $priceLabelFor($svc) }}</dd>
                            </div>
                            <div class="rounded-lg bg-neutral-50 px-2 py-1.5">
                                <dt class="text-xs text-neutral-500">Kuota</dt>
                                <dd class="truncate text-sm font-bold text-neutral-900 tabular-nums">{{ $svc->daily_quota ? $svc->daily_quota.'/hari' : 'Bebas' }}</dd>
                            </div>
                        </dl>

                        <div class="mt-3 grid grid-cols-3 gap-2">
                            <form method="POST" action="{{ route('merchant.services.toggle-status', $svc) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="inline-flex h-11 w-full items-center justify-center rounded-lg border px-2 text-xs font-semibold transition {{ $svc->is_active ? 'border-neutral-300 text-neutral-700 hover:bg-neutral-100' : 'border-emerald-300 bg-emerald-50 text-emerald-700 hover:bg-emerald-100' }}">
                                    {{ $svc->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                </button>
                            </form>
                            <button type="button" onclick="openEditServiceModal({{ json_encode($editPayloadFor($svc)) }})" class="inline-flex h-11 items-center justify-center rounded-lg border border-neutral-300 px-2 text-xs font-semibold text-neutral-700 transition hover:bg-neutral-100">
                                Edit
                            </button>
                            <form method="POST" action="{{ route('merchant.services.destroy', $svc) }}" onsubmit="return confirm(@js($deleteConfirmation))">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="inline-flex h-11 w-full items-center justify-center rounded-lg border border-rose-200 px-2 text-xs font-semibold text-rose-700 transition hover:bg-rose-50">
                                    Hapus
                                </button>
                            </form>
                        </div>
                    </li>
                @endforeach
            </ul>

            {{-- Tablet & desktop: table --}}
            <div class="hidden xl:block">
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

                    @foreach ($services as $svc)
                        <tr class="transition-colors hover:bg-neutral-50/60">
                            <x-table.td>
                                <div class="flex items-center gap-3">
                                    <x-customer.service-thumb :service="$svc" :category="$business->category" class="size-10 rounded-lg" />
                                    <div class="min-w-0">
                                        <div class="font-bold text-neutral-900">{{ $svc->name }}</div>
                                        @if ($svc->description)
                                            <p class="mt-0.5 max-w-48 truncate text-xs text-neutral-500 2xl:max-w-sm">{{ $svc->description }}</p>
                                        @endif
                                    </div>
                                </div>
                            </x-table.td>
                            <x-table.td class="text-center whitespace-nowrap">
                                <span class="inline-flex items-center gap-1 rounded-full bg-neutral-100 px-2 py-0.5 text-xs font-semibold text-neutral-700 tabular-nums">
                                    <x-customer.icon name="clock" class="size-3 text-neutral-500" />
                                    {{ $svc->duration_minutes }} menit
                                </span>
                            </x-table.td>
                            <x-table.td class="text-right whitespace-nowrap font-medium tabular-nums {{ $svc->price > 0 ? 'text-neutral-900' : 'font-semibold text-emerald-700' }}">
                                {{ $priceLabelFor($svc) }}
                            </x-table.td>
                            <x-table.td class="text-center whitespace-nowrap">
                                @if ($svc->daily_quota)
                                    <span class="rounded-full bg-neutral-100 px-2.5 py-0.5 text-xs font-semibold text-neutral-800 tabular-nums">{{ $svc->daily_quota }}/hari</span>
                                @else
                                    <span class="text-xs text-neutral-500">Tidak dibatasi</span>
                                @endif
                            </x-table.td>
                            <x-table.td class="text-center whitespace-nowrap">
                                <x-status-badge :status="$svc->is_active ? 'aktif' : 'nonaktif'" size="sm" />
                            </x-table.td>
                            <x-table.td class="text-right whitespace-nowrap">
                                <div class="inline-flex items-center gap-1.5">
                                    <form method="POST" action="{{ route('merchant.services.toggle-status', $svc) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button
                                            type="submit"
                                            class="inline-flex h-9 items-center rounded-md border px-2.5 text-xs font-semibold transition-colors {{ $svc->is_active ? 'border-neutral-300 text-neutral-700 hover:bg-neutral-100' : 'border-emerald-300 bg-emerald-50 text-emerald-700 hover:bg-emerald-100' }}"
                                            title="{{ $svc->is_active ? 'Sembunyikan dari pilihan tiket baru' : 'Aktifkan layanan ini' }}"
                                        >
                                            {{ $svc->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                        </button>
                                    </form>
                                    <button type="button" onclick="openEditServiceModal({{ json_encode($editPayloadFor($svc)) }})" class="inline-flex h-9 items-center rounded-md border border-neutral-300 px-2.5 text-xs font-semibold text-neutral-700 transition-colors hover:bg-neutral-100">
                                        Edit
                                    </button>
                                    <form method="POST" action="{{ route('merchant.services.destroy', $svc) }}" onsubmit="return confirm(@js($deleteConfirmation))">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex h-9 items-center rounded-md border border-rose-200 px-2.5 text-xs font-semibold text-rose-700 transition-colors hover:bg-rose-50" title="Hapus Layanan">
                                            Hapus
                                        </button>
                                    </form>
                                </div>
                            </x-table.td>
                        </tr>
                    @endforeach
                </x-table>
            </div>

            @if ($services->hasPages())
                <div>{{ $services->links() }}</div>
            @endif
        @endif
    </div>

    {{-- Modal: Add Service --}}
    <dialog id="modal-add-service" class="m-auto max-h-[92dvh] w-[calc(100%-1.5rem)] max-w-lg rounded-2xl p-0 shadow-xl backdrop:bg-neutral-900/50 max-sm:mb-0 max-sm:rounded-b-none">
        <div class="bg-white">
            <div class="sticky top-0 z-10 flex items-center justify-between border-b border-neutral-200 bg-white py-2 pl-5 pr-2 sm:pl-6">
                <h3 class="text-h3 font-bold text-neutral-900">Tambah Layanan Baru</h3>
                <button type="button" onclick="document.getElementById('modal-add-service').close()" class="inline-flex size-11 items-center justify-center rounded-lg text-neutral-400 transition hover:bg-neutral-100 hover:text-neutral-600" aria-label="Tutup">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form method="POST" action="{{ route('merchant.services.store') }}" enctype="multipart/form-data" class="space-y-4 p-5 sm:p-6">
                @csrf

                <div>
                    <x-form-label for="add_svc_name" value="Nama Layanan" :required="true" />
                    <x-input id="add_svc_name" name="name" required placeholder="Contoh: Potong Rambut Reguler" />
                </div>

                <div>
                    <x-form-label for="add_svc_description" value="Deskripsi Layanan (Opsional)" />
                    <x-textarea id="add_svc_description" name="description" rows="2" placeholder="Penjelasan mengenai cakupan pelayanan ini..." />
                </div>

                <div>
                    <x-form-label for="add_svc_image" value="Foto Layanan (Opsional)" />
                    <input id="add_svc_image" name="image" type="file" accept="image/jpeg,image/png,image/webp" class="block w-full text-sm text-neutral-700 file:mr-3 file:rounded-lg file:border-0 file:bg-neutral-100 file:px-3 file:py-2.5 file:text-sm file:font-semibold file:text-neutral-700 hover:file:bg-neutral-200" />
                    <p class="text-caption text-neutral-600 mt-1">JPG, PNG, atau WEBP, maks. 2 MB. Foto membantu pelanggan memilih layanan.</p>
                    <x-input-error for="image" />
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
                    <p class="text-caption text-neutral-600 mt-1">Jika diatur, antrean untuk layanan ini akan ditutup otomatis ketika kuota harian tercapai.</p>
                </div>

                <div class="flex items-center gap-2 pt-2">
                    <x-checkbox id="add_svc_is_active" name="is_active" value="1" :checked="true" label="Aktifkan layanan ini sekarang" />
                </div>

                <div class="grid grid-cols-2 gap-3 border-t border-neutral-200 pt-4 sm:flex sm:justify-end">
                    <x-button type="button" variant="secondary" class="h-11" onclick="document.getElementById('modal-add-service').close()">
                        Batal
                    </x-button>
                    <x-button type="submit" variant="primary" class="h-11">
                        Simpan Layanan
                    </x-button>
                </div>
            </form>
        </div>
    </dialog>

    {{-- Modal: Edit Service --}}
    <dialog id="modal-edit-service" class="m-auto max-h-[92dvh] w-[calc(100%-1.5rem)] max-w-lg rounded-2xl p-0 shadow-xl backdrop:bg-neutral-900/50 max-sm:mb-0 max-sm:rounded-b-none">
        <div class="bg-white">
            <div class="sticky top-0 z-10 flex items-center justify-between border-b border-neutral-200 bg-white py-2 pl-5 pr-2 sm:pl-6">
                <h3 class="text-h3 font-bold text-neutral-900">Edit Layanan</h3>
                <button type="button" onclick="document.getElementById('modal-edit-service').close()" class="inline-flex size-11 items-center justify-center rounded-lg text-neutral-400 transition hover:bg-neutral-100 hover:text-neutral-600" aria-label="Tutup">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form id="form-edit-service" method="POST" action="" enctype="multipart/form-data" class="space-y-4 p-5 sm:p-6">
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

                <div>
                    <x-form-label for="edit_svc_image" value="Foto Layanan (Opsional)" />
                    <div id="edit_svc_image_current" class="mb-2 hidden items-center gap-3">
                        <img id="edit_svc_image_preview" src="" alt="Foto layanan saat ini" class="size-16 rounded-lg object-cover border border-neutral-200" />
                        <x-checkbox id="edit_svc_remove_image" name="remove_image" value="1" label="Hapus foto ini" />
                    </div>
                    <input id="edit_svc_image" name="image" type="file" accept="image/jpeg,image/png,image/webp" class="block w-full text-sm text-neutral-700 file:mr-3 file:rounded-lg file:border-0 file:bg-neutral-100 file:px-3 file:py-2.5 file:text-sm file:font-semibold file:text-neutral-700 hover:file:bg-neutral-200" />
                    <p class="text-caption text-neutral-600 mt-1">JPG, PNG, atau WEBP, maks. 2 MB. Foto membantu pelanggan memilih layanan.</p>
                    <x-input-error for="image" />
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

                <div class="grid grid-cols-2 gap-3 border-t border-neutral-200 pt-4 sm:flex sm:justify-end">
                    <x-button type="button" variant="secondary" class="h-11" onclick="document.getElementById('modal-edit-service').close()">
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
        function openEditServiceModal(service) {
            const form = document.getElementById('form-edit-service');
            form.action = `/umkm/services/${service.id}`;
            document.getElementById('edit_svc_name').value = service.name;
            document.getElementById('edit_svc_description').value = service.description || '';
            document.getElementById('edit_svc_duration').value = service.duration_minutes;
            document.getElementById('edit_svc_price').value = Math.round(service.price);
            document.getElementById('edit_svc_quota').value = service.daily_quota || '';
            document.getElementById('edit_svc_is_active').checked = !!service.is_active;
            document.getElementById('edit_svc_image').value = '';
            document.getElementById('edit_svc_remove_image').checked = false;
            const currentImage = document.getElementById('edit_svc_image_current');
            currentImage.classList.toggle('hidden', !service.image_url);
            currentImage.classList.toggle('flex', !!service.image_url);
            document.getElementById('edit_svc_image_preview').src = service.image_url || '';

            document.getElementById('modal-edit-service').showModal();
        }
    </script>
</x-layouts.merchant>

