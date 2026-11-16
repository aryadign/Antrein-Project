@props([
    'size' => 'md',
    'variant' => 'light',
    'showText' => true,
    'badge' => null,
])

@php
    $iconSize = match ($size) {
        'xs' => 'size-6',
        'sm' => 'size-8',
        'lg' => 'size-11',
        'xl' => 'size-13',
        default => 'size-9',
    };

    $textSize = match ($size) {
        'xs' => 'text-base',
        'sm' => 'text-lg',
        'lg' => 'text-2xl',
        'xl' => 'text-3xl',
        default => 'text-xl',
    };

    $isDarkSurface = $variant === 'dark';
    $textClass = $isDarkSurface ? 'text-white' : 'text-neutral-950';
    $dotClass = $isDarkSurface ? 'text-brand-400' : 'text-brand-700';
@endphp

<span {{ $attributes->class('inline-flex items-center gap-2.5 select-none') }}>
    <span @class([
        'inline-flex shrink-0 items-center justify-center overflow-hidden rounded-xl',
        $iconSize,
        'bg-white p-0.5 shadow-xs ring-1 ring-white/20' => $isDarkSurface,
    ])>
        <img
            src="{{ asset('images/antrein-logo.svg') }}"
            alt="Logo AntreIn"
            class="size-full object-contain"
        />
    </span>

    @if ($showText)
        <span class="font-black tracking-tight {{ $textSize }} {{ $textClass }}">
            AntreIn<span class="{{ $dotClass }}">.</span>
        </span>
    @endif

    @if ($badge)
        <span @class([
            'px-2 py-0.5 text-[10px] font-bold tracking-wider uppercase rounded border',
            'bg-brand-700/60 text-brand-200 border-brand-500/30' => $isDarkSurface,
            'bg-brand-50 text-brand-700 border-brand-200' => ! $isDarkSurface,
        ])>
            {{ $badge }}
        </span>
    @endif
</span>
