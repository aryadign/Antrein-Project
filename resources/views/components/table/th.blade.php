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

<th scope="col" {{ $attributes->merge(['class' => "px-4 py-3 text-label font-semibold text-neutral-600 whitespace-nowrap select-none {$alignClasses}"]) }}>
    {{ $slot }}
</th>

