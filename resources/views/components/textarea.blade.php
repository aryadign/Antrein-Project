@props([
    'disabled' => false,
    'error' => false,
    'rows' => 3,
])

@php
    $baseClasses = 'w-full p-3.5 text-body bg-white rounded-lg border transition-[border-color,box-shadow] duration-150 ease-out placeholder:text-neutral-500 focus:outline-none';
    
    $stateClasses = $error
        ? 'border-danger-700 text-neutral-950 focus:ring-4 focus:ring-danger-700/15 focus:border-danger-700'
        : 'border-neutral-300 text-neutral-950 hover:border-neutral-400 focus:ring-4 focus:ring-brand-500/15 focus:border-brand-500';

    $disabledClasses = $disabled ? 'bg-neutral-100 text-neutral-500 cursor-not-allowed border-neutral-200' : 'disabled:bg-neutral-100 disabled:text-neutral-500 disabled:cursor-not-allowed';

    $classes = "{$baseClasses} {$stateClasses} {$disabledClasses}";
@endphp

<textarea rows="{{ $rows }}" {{ $disabled ? 'disabled' : '' }} {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</textarea>

