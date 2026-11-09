@props([
    'title' => 'Terjadi Kesalahan',
    'message' => 'Gagal memuat data dari server. Silakan coba kembali.',
    'retryAction' => null,
])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center p-8 text-center bg-rose-50/50 rounded-xl border border-rose-200']) }}>
    <div class="w-12 h-12 rounded-full bg-rose-100 text-danger-700 flex items-center justify-center mb-3">
        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>
    </div>

    <h3 class="text-h3 text-neutral-900 font-semibold">{{ $title }}</h3>

    @if ($message)
        <p class="mt-1 text-body text-neutral-600 max-w-sm">{{ $message }}</p>
    @endif

    @if ($retryAction)
        <div class="mt-4">
            <x-button variant="outline" size="sm" wire:click="{{ $retryAction }}">
                <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                Coba Lagi
            </x-button>
        </div>
    @elseif ($slot->isNotEmpty())
        <div class="mt-4">
            {{ $slot }}
        </div>
    @endif
</div>

