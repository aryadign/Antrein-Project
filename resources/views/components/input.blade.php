@props([
    'disabled' => false,
    'error' => false,
    'type' => 'text',
    'icon' => null,
])

@php
    $baseClasses = 'w-full h-11 px-3.5 text-body bg-white rounded-lg border transition-[border-color,box-shadow] duration-150 ease-out placeholder:text-neutral-500 focus:outline-none';
    
    $stateClasses = $error
        ? 'border-danger-700 text-neutral-950 focus:ring-4 focus:ring-danger-700/15 focus:border-danger-700'
        : 'border-neutral-300 text-neutral-950 hover:border-neutral-400 focus:ring-4 focus:ring-brand-500/15 focus:border-brand-500';

    $disabledClasses = $disabled ? 'bg-neutral-100 text-neutral-500 cursor-not-allowed border-neutral-200' : 'disabled:bg-neutral-100 disabled:text-neutral-500 disabled:cursor-not-allowed';

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

