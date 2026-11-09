@props([
    'align' => 'left',
])

@php
    $alignClasses = match ($align) {
        'center' => 'text-center',
        'right' => 'text-right',
        default => 'text-left',
    };
@endphp

<th scope="col" {{ $attributes->merge(['class' => "px-4 py-3.5 text-label font-semibold text-neutral-700 select-none {$alignClasses}"]) }}>
    {{ $slot }}
</th>

