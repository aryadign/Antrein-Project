@props([
    'title' => 'Tidak ada data ditemukan',
    'description' => 'Belum ada data yang tercatat dalam sistem.',
    'icon' => null,
])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center px-6 py-10 text-center bg-neutral-50/60 rounded-xl border border-dashed border-neutral-300']) }}>
    <div class="size-12 rounded-full bg-white text-neutral-500 shadow-card ring-1 ring-neutral-200 flex items-center justify-center mb-3" aria-hidden="true">
        @if ($icon)
            {{ $icon }}
        @else
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
            </svg>
        @endif
    </div>

    <h3 class="text-h3 text-neutral-900 font-semibold">{{ $title }}</h3>

    @if ($description)
        <p class="mt-1 text-body text-neutral-600 max-w-sm text-pretty">{{ $description }}</p>
    @endif

    @if ($slot->isNotEmpty())
        <div class="mt-5">
            {{ $slot }}
        </div>
    @endif
</div>

