@props([
    'number' => 'A-001',
    'size' => 'default',
    'service' => null,
    'source' => null,
])

@php
    $sizeClasses = match ($size) {
        'hero' => 'text-5xl sm:text-6xl font-extrabold tracking-tight',
        'lg' => 'text-3xl sm:text-4xl font-bold tracking-tight',
        'sm' => 'text-lg font-semibold tracking-normal',
        default => 'text-2xl sm:text-3xl font-bold tracking-tight',
    };
@endphp

<div {{ $attributes->merge(['class' => 'inline-flex flex-col items-center justify-center']) }}>
    <div class="tabular-nums text-neutral-950 font-sans {{ $sizeClasses }}">
        {{ $number }}
    </div>

    @if ($service || $source)
        <div class="flex items-center gap-1.5 mt-1 text-caption text-neutral-500">
            @if ($service)
                <span class="font-medium text-neutral-700">{{ $service }}</span>
            @endif

            @if ($service && $source)
                <span>•</span>
            @endif

            @if ($source)
                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[11px] font-medium {{ $source === 'online' ? 'bg-blue-50 text-blue-700' : 'bg-neutral-100 text-neutral-600' }}">
                    {{ $source === 'online' ? 'Online' : 'Walk-in' }}
                </span>
            @endif
        </div>
    @endif
</div>

