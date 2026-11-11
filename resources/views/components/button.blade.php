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
    $baseClasses = 'inline-flex items-center justify-center font-medium rounded-md transition-all duration-150 focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed select-none cursor-pointer';

    $sizeClasses = match ($size) {
        'sm' => 'h-8 px-3 text-label gap-1.5',
        'lg' => 'h-12 px-6 text-body-lg gap-2.5',
        default => 'h-10 px-4 text-body gap-2',
    };

    $variantClasses = match ($variant) {
        'secondary' => 'bg-neutral-100 text-neutral-800 hover:bg-neutral-200 active:bg-neutral-300 focus:ring-neutral-400 border border-neutral-200',
        'outline' => 'bg-white text-neutral-700 hover:bg-neutral-50 active:bg-neutral-100 focus:ring-brand-500 border border-neutral-300 shadow-sm',
        'ghost' => 'bg-transparent text-neutral-700 hover:bg-neutral-100 active:bg-neutral-200 focus:ring-neutral-400',
        'danger', 'destructive' => 'bg-danger-700 text-white hover:bg-red-800 active:bg-red-900 focus:ring-danger-700 shadow-sm',
        'link' => 'bg-transparent text-brand-700 hover:underline active:text-brand-600 focus:ring-brand-500 p-0 h-auto',
        default => 'bg-brand-700 text-white hover:bg-brand-600 active:bg-blue-800 focus:ring-brand-500 shadow-sm',
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

