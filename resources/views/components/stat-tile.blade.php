@props([
    'label',
    'value',
    'hint' => null,
    'tone' => 'neutral',
    'icon' => null,
    'trend' => null,
    'trendDirection' => 'up',
    'emphasis' => false,
])

{{--
    The single KPI tile for merchant & admin console pages (x-stat-card is a thin alias).
    Calm white surface; the tone is carried by the small icon chip only. Use `emphasis` for
    the one primary KPI on a screen (raised shadow + light brand tint). `icon` accepts an icon
    name (check, clock, alert, pause, activity, counter, layers, users, ticket, store) or a slot.
    Fits a two-column grid at 390px; the icon chip hides itself when the tile is narrower than 8rem.
--}}
@php
    $tone = match ($tone) {
        'info' => 'brand',
        default => $tone,
    };

    $chipClasses = match ($tone) {
        'brand' => 'bg-brand-50 text-brand-700 ring-brand-100',
        'success' => 'bg-emerald-50 text-emerald-700 ring-emerald-100',
        'warning' => 'bg-amber-50 text-amber-700 ring-amber-100',
        'danger' => 'bg-rose-50 text-rose-700 ring-rose-100',
        'muted' => 'bg-neutral-100 text-neutral-500 ring-neutral-200',
        default => 'bg-neutral-100 text-neutral-700 ring-neutral-200',
    };

    $iconIsSlot = $icon instanceof \Illuminate\View\ComponentSlot;

    $iconName = $iconIsSlot ? null : ($icon ?? match ($tone) {
        'brand' => 'activity',
        'success' => 'check',
        'warning' => 'clock',
        'danger' => 'alert',
        'muted' => 'pause',
        default => 'layers',
    });

    $surfaceClasses = $emphasis
        ? 'border-brand-200 bg-brand-50/40 shadow-raised'
        : 'border-neutral-200/80 bg-white shadow-card';
@endphp

<div data-stat-tile data-tone="{{ $tone }}" {{ $attributes->class("@container relative min-w-0 rounded-xl border p-4 {$surfaceClasses}") }}>
    <div class="flex items-start justify-between gap-3">
        <p class="min-w-0 text-sm font-medium leading-5 text-pretty text-neutral-600 [overflow-wrap:anywhere]">{{ $label }}</p>
        <span class="hidden @[8rem]:flex size-8 shrink-0 items-center justify-center rounded-lg ring-1 ring-inset {{ $chipClasses }}" aria-hidden="true">
            @if ($iconIsSlot)
                {{ $icon }}
            @else
                @switch($iconName)
                    @case('check')
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        @break
                    @case('clock')
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        @break
                    @case('alert')
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                        @break
                    @case('pause')
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        @break
                    @case('activity')
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        @break
                    @case('counter')
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        @break
                    @case('users')
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        @break
                    @case('ticket')
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg>
                        @break
                    @case('store')
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        @break
                    @default
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                @endswitch
            @endif
        </span>
    </div>

    <p class="mt-2 text-xl font-bold tracking-tight text-neutral-950 tabular-nums [overflow-wrap:anywhere] sm:text-2xl">{{ $value }}</p>

    @if ($hint || $trend)
        <p class="mt-1 flex flex-wrap items-center gap-x-1.5 text-xs leading-4 {{ $emphasis ? 'text-neutral-600' : 'text-neutral-500' }}">
            @if ($trend)
                <span class="inline-flex items-center gap-0.5 font-semibold {{ $trendDirection === 'up' ? 'text-success-700' : 'text-danger-700' }}">
                    @if ($trendDirection === 'up')
                        <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                    @else
                        <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                    @endif
                    {{ $trend }}
                </span>
            @endif
            @if ($hint)
                <span class="min-w-0 line-clamp-2" title="{{ $hint }}">{{ $hint }}</span>
            @endif
        </p>
    @endif
</div>
