@props([
    'title' => 'Ambil Antrean',
    'business' => null,
    'backUrl' => null,
])

@php
    $businessName = data_get($business, 'name');
    $businessCategory = data_get($business, 'category.name', data_get($business, 'category', 'UMKM Mitra'));
    $businessStatus = data_get($business, 'status', 'buka');
@endphp

<x-layouts.base :title="$title . ($businessName ? ' — ' . $businessName : ' — AntreIn')">
    <div class="min-h-screen bg-neutral-50 flex flex-col justify-between">
        {{-- Public Header --}}
        <header class="bg-white border-b border-neutral-200 sticky top-0 z-30 shadow-2xs">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    @if ($backUrl)
                        <a href="{{ $backUrl }}" class="p-2 -ml-2 rounded-lg text-neutral-600 hover:text-neutral-900 hover:bg-neutral-100 transition focus:outline-none focus:ring-2 focus:ring-brand-500" aria-label="Kembali">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                        </a>
                    @endif

                    <a href="/" class="flex items-center gap-1.5 focus:outline-none">
                        <span class="text-xl font-bold tracking-tight text-neutral-950">AntreIn<span class="text-brand-700">.</span></span>
                    </a>
                </div>

                @if ($business)
                    <div class="flex items-center gap-2 sm:gap-3">
                        <div class="hidden sm:block text-right">
                            <div class="text-label font-bold text-neutral-900 line-clamp-1">{{ $businessName }}</div>
                            <div class="text-[11px] text-neutral-500">{{ $businessCategory }}</div>
                        </div>

                        <x-status-badge :status="$businessStatus" size="sm" />
                    </div>
                @else
                    <div class="text-caption text-neutral-500 font-medium">
                        Antrean Digital
                    </div>
                @endif
            </div>
        </header>

        {{-- Main Public Content Container --}}
        <main class="flex-1 max-w-6xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 lg:py-10">
            {{ $slot }}
        </main>

        {{-- Public Footer --}}
        <footer class="bg-white border-t border-neutral-200 py-6 text-center text-caption text-neutral-500">
            <div class="max-w-6xl mx-auto px-4">
                <p class="font-medium text-neutral-600">Powered by AntreIn — Platform Antrean Digital UMKM</p>
                <p class="text-[11px] text-neutral-400 mt-1">Solusi manajemen antrean online & walk-in terintegrasi.</p>
            </div>
        </footer>
    </div>
</x-layouts.base>
