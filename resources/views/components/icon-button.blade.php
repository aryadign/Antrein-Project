@props([
    'variant' => 'ghost',
    'size' => 'md',
    'type' => 'button',
    'disabled' => false,
    'label' => '',
])

@php
    $baseClasses = 'inline-flex items-center justify-center rounded-lg transition-[background-color,border-color,color,transform] duration-150 ease-out focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 active:translate-y-px disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer';

    $sizeClasses = match ($size) {
        'sm' => 'size-9',
        'lg' => 'size-12',
        default => 'size-11',
    };

    $variantClasses = match ($variant) {
        'secondary' => 'bg-neutral-100 text-neutral-700 hover:bg-neutral-200 focus-visible:ring-neutral-400 border border-neutral-200',
        'outline' => 'bg-white text-neutral-700 hover:bg-neutral-50 focus-visible:ring-brand-500 border border-neutral-300 shadow-card',
        'danger' => 'bg-red-50 text-danger-700 hover:bg-red-100 focus-visible:ring-danger-700 border border-red-200',
        'primary' => 'bg-brand-700 text-white hover:bg-brand-800 focus-visible:ring-brand-500 shadow-card',
        default => 'bg-transparent text-neutral-600 hover:bg-neutral-100 active:bg-neutral-200 focus-visible:ring-neutral-400',
    };

    $classes = "{$baseClasses} {$sizeClasses} {$variantClasses}";
@endphp

<button type="{{ $type }}" aria-label="{{ $label }}" title="{{ $label }}" {{ $disabled ? 'disabled' : '' }} {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</button>

