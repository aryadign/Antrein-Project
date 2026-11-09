@props([
    'variant' => 'text',
    'height' => null,
    'width' => null,
])

@php
    $variantClasses = match ($variant) {
        'circle' => 'rounded-full',
        'card' => 'rounded-xl h-32 w-full',
        'button' => 'rounded-md h-10 w-24',
        'badge' => 'rounded-full h-6 w-16',
        default => 'rounded h-4 w-full',
    };

    $customStyles = '';
    if ($height) $customStyles .= "height: {$height}; ";
    if ($width) $customStyles .= "width: {$width}; ";
@endphp

<div 
    {{ $attributes->merge(['class' => "animate-pulse bg-neutral-200/80 {$variantClasses}"]) }}
    @if ($customStyles) style="{{ $customStyles }}" @endif
></div>

