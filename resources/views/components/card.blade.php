@props([
    'padding' => 'default',
    'header' => null,
    'footer' => null,
])

@php
    $paddingClasses = match ($padding) {
        'none' => '',
        'sm' => 'p-4',
        'lg' => 'p-6 sm:p-8',
        default => 'p-5 sm:p-6',
    };
@endphp

<div {{ $attributes->merge(['class' => 'bg-white rounded-xl border border-neutral-200 shadow-sm overflow-hidden']) }}>
    @if ($header)
        <div class="px-5 py-4 border-b border-neutral-200 bg-neutral-50/50 sm:px-6">
            {{ $header }}
        </div>
    @endif

    <div class="{{ $paddingClasses }}">
        {{ $slot }}
    </div>

    @if ($footer)
        <div class="px-5 py-3 border-t border-neutral-200 bg-neutral-50/50 sm:px-6">
            {{ $footer }}
        </div>
    @endif
</div>

