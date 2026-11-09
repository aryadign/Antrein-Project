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

<td {{ $attributes->merge(['class' => "px-4 py-3.5 text-body text-neutral-800 whitespace-nowrap {$alignClasses}"]) }}>
    {{ $slot }}
</td>

