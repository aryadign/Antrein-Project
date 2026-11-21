@props([
    'options',
    'model',
    'label',
])

{{-- Pill filter bound to an Alpine variable named by `model`. `options` is [key => label] or [key => ['label' => ..., 'count' => ...]]. --}}
<div {{ $attributes->class('flex flex-wrap gap-2') }} role="tablist" aria-label="{{ $label }}">
    @foreach ($options as $key => $option)
        @php
            $optionLabel = is_array($option) ? $option['label'] : $option;
            $optionCount = is_array($option) ? ($option['count'] ?? null) : null;
        @endphp
        <button
            type="button"
            role="tab"
            @click="{{ $model }} = @js($key)"
            :aria-selected="{{ $model }} === @js($key)"
            :class="{{ $model }} === @js($key) ? 'border-brand-700 bg-brand-700 text-white shadow-card' : 'border-gray-200 bg-white text-gray-700 hover:border-gray-300 hover:bg-gray-50'"
            class="inline-flex h-10 items-center gap-1.5 rounded-full border px-4 text-sm font-semibold transition-colors duration-200 ease-out"
        >
            {{ $optionLabel }}
            @if ($optionCount !== null)
                <span class="tabular-nums opacity-80">{{ $optionCount }}</span>
            @endif
        </button>
    @endforeach
</div>
