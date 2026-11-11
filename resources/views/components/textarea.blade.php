@props([
    'disabled' => false,
    'error' => false,
    'rows' => 3,
])

@php
    $baseClasses = 'w-full p-3.5 text-body bg-white rounded-md border transition-all duration-150 placeholder:text-neutral-500 focus:outline-none';
    
    $stateClasses = $error
        ? 'border-danger-700 text-neutral-950 focus:ring-2 focus:ring-danger-700/20 focus:border-danger-700'
        : 'border-neutral-300 text-neutral-950 hover:border-neutral-400 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500';

    $disabledClasses = $disabled ? 'bg-neutral-100 text-neutral-500 cursor-not-allowed border-neutral-200' : '';

    $classes = "{$baseClasses} {$stateClasses} {$disabledClasses}";
@endphp

<textarea rows="{{ $rows }}" {{ $disabled ? 'disabled' : '' }} {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</textarea>

