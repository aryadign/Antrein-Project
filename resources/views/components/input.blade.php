@props([
    'disabled' => false,
    'error' => false,
    'type' => 'text',
    'icon' => null,
])

@php
    $baseClasses = 'w-full h-11 px-3.5 text-body bg-white rounded-md border transition-all duration-150 placeholder:text-neutral-500 focus:outline-none';
    
    $stateClasses = $error
        ? 'border-danger-700 text-neutral-950 focus:ring-2 focus:ring-danger-700/20 focus:border-danger-700'
        : 'border-neutral-300 text-neutral-950 hover:border-neutral-400 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500';

    $disabledClasses = $disabled ? 'bg-neutral-100 text-neutral-500 cursor-not-allowed border-neutral-200' : '';

    $paddingClasses = $icon ? 'pl-10' : '';

    $classes = "{$baseClasses} {$stateClasses} {$disabledClasses} {$paddingClasses}";
@endphp

<div class="relative w-full">
    @if ($icon)
        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-neutral-500">
            {{ $icon }}
        </div>
    @endif

    <input type="{{ $type }}" {{ $disabled ? 'disabled' : '' }} {{ $attributes->merge(['class' => $classes]) }}>
</div>

