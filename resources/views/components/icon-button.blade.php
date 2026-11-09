@props([
    'variant' => 'ghost',
    'size' => 'md',
    'type' => 'button',
    'disabled' => false,
    'label' => '',
])

@php
    $baseClasses = 'inline-flex items-center justify-center rounded-md transition-all duration-150 focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer';

    $sizeClasses = match ($size) {
        'sm' => 'h-8 w-8',
        'lg' => 'h-12 w-12',
        default => 'h-10 w-10',
    };

    $variantClasses = match ($variant) {
        'secondary' => 'bg-neutral-100 text-neutral-700 hover:bg-neutral-200 focus:ring-neutral-400 border border-neutral-200',
        'outline' => 'bg-white text-neutral-700 hover:bg-neutral-50 focus:ring-brand-500 border border-neutral-300 shadow-sm',
        'danger' => 'bg-red-50 text-danger-700 hover:bg-red-100 focus:ring-danger-700 border border-red-200',
        'primary' => 'bg-brand-700 text-white hover:bg-brand-600 focus:ring-brand-500 shadow-sm',
        default => 'bg-transparent text-neutral-600 hover:bg-neutral-100 active:bg-neutral-200 focus:ring-neutral-400',
    };

    $classes = "{$baseClasses} {$sizeClasses} {$variantClasses}";
@endphp

<button type="{{ $type }}" aria-label="{{ $label }}" title="{{ $label }}" {{ $disabled ? 'disabled' : '' }} {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</button>

