@props([
    'business',
    'services',
])

{{-- Walk-in ticket form (M7-24, M7-25). Opened via the parent Alpine `walkInModalOpen` flag; bottom sheet on mobile. --}}
@php
    $controlClasses = 'border h-11 w-full rounded-lg border-neutral-300 text-sm focus:border-brand-500 focus:ring-brand-500';
@endphp

<div
    x-show="walkInModalOpen"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-50 flex items-end justify-center overflow-y-auto bg-neutral-950/60 sm:items-center sm:p-4"
    style="display: none;"
    @keydown.escape.window="walkInModalOpen = false"
    role="dialog"
    aria-modal="true"
    aria-labelledby="walk-in-heading"
>
    <div
        @click.outside="walkInModalOpen = false"
        class="relative max-h-[92vh] w-full max-w-lg overflow-y-auto rounded-t-2xl border border-neutral-200 bg-white p-5 shadow-overlay sm:rounded-2xl sm:p-6"
    >
        <div class="mb-5 flex items-start justify-between gap-3 border-b border-neutral-100 pb-4">
            <div>
                <h3 id="walk-in-heading" class="text-h3 font-bold text-neutral-900">Tambah Pelanggan Datang Langsung</h3>
                <p class="text-caption text-neutral-600">Untuk pelanggan yang datang langsung ke lokasi usaha.</p>
            </div>
            <button type="button" @click="walkInModalOpen = false" class="-mr-2 -mt-2 flex size-11 items-center justify-center rounded-lg text-neutral-400 hover:bg-neutral-100 hover:text-neutral-600" aria-label="Tutup">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        @if ($services->isEmpty())
            <div class="mb-4 rounded-xl bg-amber-50 p-4 text-sm text-amber-800">
                Belum ada layanan aktif. Tambahkan layanan dulu di menu Layanan.
            </div>
        @else
            <form method="POST" action="{{ route('merchant.queues.walk-in') }}" class="space-y-4">
                @csrf

                @if ($business->status === 'jeda')
                    <div class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900">
                        <label class="flex cursor-pointer items-start gap-2">
                            <input type="checkbox" name="confirmed_paused" value="1" required class="mt-0.5 rounded border-amber-300 text-amber-600 focus:ring-amber-500">
                            <span>Konfirmasi tiket untuk pelanggan yang datang langsung saat usaha dalam status Jeda.</span>
                        </label>
                    </div>
                @endif

                <div>
                    <label for="walk_in_service" class="mb-1.5 block text-label font-semibold text-neutral-700">Layanan Antrean <span class="text-rose-500">*</span></label>
                    <select id="walk_in_service" name="service_id" required class="{{ $controlClasses }}">
                        <option value="">Pilih layanan</option>
                        @foreach ($services as $service)
                            <option value="{{ $service->id }}">{{ $service->name }} (Estimasi: {{ $service->duration_minutes }} mnt)</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="walk_in_name" class="mb-1.5 block text-label font-semibold text-neutral-700">Nama Pelanggan <span class="text-rose-500">*</span></label>
                    <input id="walk_in_name" type="text" name="customer_name" required maxlength="100" autocomplete="off" placeholder="Contoh: Budi Santoso" class="placeholder:text-neutral-500 {{ $controlClasses }}">
                </div>

                <div>
                    <label for="walk_in_phone" class="mb-1.5 block text-label font-semibold text-neutral-700">Nomor WhatsApp / HP (Opsional)</label>
                    <input id="walk_in_phone" type="tel" inputmode="tel" name="customer_phone" maxlength="30" placeholder="08xxxxxxxxxx" class="placeholder:text-neutral-500 {{ $controlClasses }}">
                </div>

                <div>
                    <label for="walk_in_notes" class="mb-1.5 block text-label font-semibold text-neutral-700">Catatan Tambahan (Opsional)</label>
                    <textarea id="walk_in_notes" name="notes" rows="2" maxlength="255" placeholder="Catatan preferensi atau kebutuhan khusus..." class="border w-full rounded-lg border-neutral-300 text-sm focus:border-brand-500 focus:ring-brand-500"></textarea>
                </div>

                <div class="grid grid-cols-2 gap-3 border-t border-neutral-100 pt-4 sm:flex sm:justify-end">
                    <button type="button" @click="walkInModalOpen = false" class="inline-flex h-11 items-center justify-center rounded-lg border border-neutral-300 px-4 text-sm font-semibold text-neutral-700 transition hover:bg-neutral-50">
                        Batal
                    </button>
                    <button type="submit" class="inline-flex h-11 items-center justify-center rounded-lg bg-brand-700 px-5 text-sm font-bold text-white shadow-card transition-colors duration-150 hover:bg-brand-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2">
                        Terbitkan Tiket
                    </button>
                </div>
            </form>
        @endif
    </div>
</div>
