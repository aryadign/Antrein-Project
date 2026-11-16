@props([
    'label',
    'value',
    'hint' => null,
    'tone' => 'neutral',
    'icon' => null,
])

{{-- Compact KPI tile for merchant & admin console pages; fits a two-column grid on 390px screens. --}}
@php
    $gradientClasses = match ($tone) {
        'brand' => 'bg-linear-to-br from-blue-600 via-brand-700 to-indigo-800 border-blue-500/30',
        'success' => 'bg-linear-to-br from-emerald-600 via-emerald-700 to-teal-800 border-emerald-500/30',
        'warning' => 'bg-linear-to-br from-amber-500 via-amber-600 to-orange-700 border-amber-400/30',
        'danger' => 'bg-linear-to-br from-rose-600 via-rose-700 to-red-800 border-rose-500/30',
        'muted' => 'bg-linear-to-br from-slate-600 via-slate-700 to-slate-800 border-slate-500/30',
        default => 'bg-linear-to-br from-slate-800 via-slate-900 to-brand-950 border-slate-700/40',
    };

    $iconName = $icon ?? match ($tone) {
        'brand' => 'activity',
        'success' => 'check',
        'warning' => 'clock',
        'danger' => 'alert',
        'muted' => 'pause',
        default => 'layers',
    };
@endphp

<div {{ $attributes->class("relative min-w-0 overflow-hidden rounded-xl border p-3.5 text-white shadow-sm sm:p-4 {$gradientClasses}") }}>
    <div class="flex items-start justify-between gap-2">
        <p class="truncate text-caption font-semibold text-white/85">{{ $label }}</p>
        <span class="flex size-7 shrink-0 items-center justify-center rounded-lg bg-white/15 text-white ring-1 ring-white/20" aria-hidden="true">
            @switch($iconName)
                @case('check')
                    <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    @break
                @case('clock')
                    <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    @break
                @case('alert')
                    <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                    @break
                @case('pause')
                    <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    @break
                @case('activity')
                    <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    @break
                @case('counter')
                    <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    @break
                @default
                    <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
            @endswitch
        </span>
    </div>
    <p class="mt-1.5 text-h2 font-extrabold tracking-tight text-white tabular-nums">{{ $value }}</p>
    @if ($hint)
        <p class="mt-0.5 truncate text-[11px] text-white/75">{{ $hint }}</p>
    @endif
</div>
