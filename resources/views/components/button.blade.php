@props([
    'variant' => 'primary',
    'size' => 'md',
    'type' => 'button',
    'disabled' => false,
    'loading' => false,
    'href' => null,
    'target' => null,
])

@php
    $baseClasses = 'inline-flex items-center justify-center font-semibold rounded-lg transition-[background-color,border-color,color,box-shadow,transform] duration-150 ease-out focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 active:translate-y-px disabled:opacity-50 disabled:cursor-not-allowed disabled:active:translate-y-0 aria-disabled:opacity-50 aria-disabled:pointer-events-none select-none cursor-pointer';

    $sizeClasses = match ($size) {
        'sm' => 'h-9 px-3 text-label gap-1.5',
        'lg' => 'h-12 px-6 text-body-lg gap-2.5',
        default => 'h-11 px-4 text-body gap-2',
    };

    $variantClasses = match ($variant) {
        'secondary' => 'bg-neutral-100 text-neutral-800 hover:bg-neutral-200 active:bg-neutral-300 focus-visible:ring-neutral-400 border border-neutral-200',
        'outline' => 'bg-white text-neutral-800 hover:bg-neutral-50 hover:border-neutral-400 active:bg-neutral-100 focus-visible:ring-brand-500 border border-neutral-300 shadow-card',
        'ghost' => 'bg-transparent text-neutral-700 hover:bg-neutral-100 active:bg-neutral-200 focus-visible:ring-neutral-400',
        'danger', 'destructive' => 'bg-danger-700 text-white hover:bg-red-800 active:bg-red-900 focus-visible:ring-danger-700 shadow-card',
        'link' => 'bg-transparent text-brand-700 underline-offset-4 hover:underline active:text-brand-600 focus-visible:ring-brand-500 p-0 h-auto',
        default => 'bg-brand-700 text-white hover:bg-brand-800 hover:shadow-raised active:bg-brand-900 focus-visible:ring-brand-500 shadow-card',
    };

    $classes = "{$baseClasses} {$sizeClasses} {$variantClasses}";
@endphp

@if ($href)
    <a href="{{ $href }}" target="{{ $target }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if ($loading)
            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
            </svg>
        @endif
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $disabled || $loading ? 'disabled' : '' }} {{ $attributes->merge(['class' => $classes]) }}>
        @if ($loading)
            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
            </svg>
        @endif
        {{ $slot }}
    </button>
@endif

