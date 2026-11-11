@props([
    'title' => '',
    'value' => '0',
    'icon' => null,
    'description' => null,
    'trend' => null,
    'trendDirection' => 'up',
])

<div {{ $attributes->merge(['class' => 'bg-white rounded-xl border border-neutral-200 p-5 sm:p-6 shadow-sm flex flex-col justify-between']) }}>
    <div class="flex items-center justify-between gap-3">
        <span class="text-label text-neutral-600 font-medium line-clamp-1">{{ $title }}</span>
        @if ($icon)
            <div class="w-10 h-10 rounded-lg bg-brand-50 text-brand-700 flex items-center justify-center shrink-0">
                {{ $icon }}
            </div>
        @endif
    </div>

    <div class="mt-3">
        <div class="text-display tabular-nums text-neutral-950 font-bold tracking-tight">
            {{ $value }}
        </div>

        @if ($description || $trend)
            <div class="mt-1 flex items-center gap-1.5 text-caption">
                @if ($trend)
                    <span class="font-medium inline-flex items-center gap-0.5 {{ $trendDirection === 'up' ? 'text-success-700' : 'text-danger-700' }}">
                        @if ($trendDirection === 'up')
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                        @else
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                        @endif
                        {{ $trend }}
                    </span>
                @endif

                @if ($description)
                    <span class="text-neutral-500">{{ $description }}</span>
                @endif
            </div>
        @endif
    </div>
</div>

