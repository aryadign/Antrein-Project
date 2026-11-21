@props([
    'name' => 'modal',
    'show' => false,
    'maxWidth' => 'md',
    'title' => null,
])

@php
    $maxWidthClasses = match ($maxWidth) {
        'sm' => 'sm:max-w-sm',
        'md' => 'sm:max-w-md',
        'lg' => 'sm:max-w-lg',
        'xl' => 'sm:max-w-xl',
        '2xl' => 'sm:max-w-2xl',
        default => 'sm:max-w-md',
    };
@endphp

<div
    x-data="{ show: @js($show) }"
    x-init="$watch('show', value => {
        if (value) {
            document.body.classList.add('overflow-y-hidden');
        } else {
            document.body.classList.remove('overflow-y-hidden');
        }
    })"
    x-on:open-modal.window="$event.detail === '{{ $name }}' ? show = true : null"
    x-on:close-modal.window="$event.detail === '{{ $name }}' ? show = false : null"
    x-on:close.stop="show = false"
    x-on:keydown.escape.window="show = false"
    x-show="show"
    class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 sm:px-0 flex items-center justify-center"
    style="display: none;"
    role="dialog"
    aria-modal="true"
    @if ($title) aria-labelledby="{{ $name }}-title" @endif
>
    {{-- Backdrop --}}
    <div 
        x-show="show"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-neutral-950/50 transition-opacity"
        @click="show = false"
    ></div>

    {{-- Dialog Box --}}
    <div
        x-show="show"
        x-transition:enter="ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        class="relative w-full {{ $maxWidthClasses }} bg-white rounded-2xl shadow-overlay border border-neutral-200/80 overflow-hidden transform transition-all"
    >
        @if ($title)
            <div class="pl-6 pr-3 py-3 border-b border-neutral-200/80 flex items-center justify-between gap-3">
                <h3 id="{{ $name }}-title" class="text-h3 font-semibold text-neutral-900">{{ $title }}</h3>
                <button @click="show = false" type="button" class="inline-flex size-11 shrink-0 items-center justify-center rounded-lg text-neutral-500 transition-colors hover:bg-neutral-100 hover:text-neutral-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500" aria-label="Tutup">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        @endif

        <div class="px-6 py-5">
            {{ $slot }}
        </div>

        @isset($footer)
            <div class="px-6 py-4 bg-neutral-50/80 border-t border-neutral-200/80 flex flex-col-reverse gap-2 sm:flex-row sm:items-center sm:justify-end sm:gap-3">
                {{ $footer }}
            </div>
        @endisset
    </div>
</div>

