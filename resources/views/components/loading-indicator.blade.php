@props([
    'size' => 'md',
    'text' => null,
])

@php
    $sizeClasses = match ($size) {
        'sm' => 'h-4 w-4',
        'lg' => 'h-8 w-8',
        default => 'h-6 w-6',
    };
@endphp

<div {{ $attributes->merge(['class' => 'inline-flex items-center gap-2.5 text-neutral-600']) }} role="status">
    <svg class="animate-spin text-brand-700 {{ $sizeClasses }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
    </svg>
    @if ($text)
        <span class="text-body font-medium">{{ $text }}</span>
    @endif
    <span class="sr-only">Memuat...</span>
</div>

