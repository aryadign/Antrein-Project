@props([
    'name',
    'logoUrl' => null,
    'initials' => null,
])

{{-- Business identity: uploaded logo, otherwise initials on the brand colour. Size comes from the passed classes. --}}
@php
    $fallbackInitials = $initials ?: collect(preg_split('/\s+/', trim((string) $name)))
        ->filter()
        ->take(2)
        ->map(fn (string $word): string => Str::upper(Str::substr($word, 0, 1)))
        ->implode('');
@endphp

<span {{ $attributes->class('inline-flex shrink-0 items-center justify-center overflow-hidden rounded-lg bg-brand-700 text-sm font-bold text-white ring-1 ring-neutral-200') }}>
    @if ($logoUrl)
        <img src="{{ $logoUrl }}" alt="Logo {{ $name }}" class="size-full bg-white object-cover" />
    @else
        <span aria-hidden="true">{{ $fallbackInitials ?: 'UM' }}</span>
    @endif
</span>
