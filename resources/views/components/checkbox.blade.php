@props([
    'disabled' => false,
    'label' => null,
    'description' => null,
])

<label class="inline-flex items-start gap-2.5 cursor-pointer select-none">
    <input type="checkbox" {{ $disabled ? 'disabled' : '' }} {{ $attributes->merge([
        'class' => 'mt-0.5 h-4 w-4 rounded border-neutral-300 text-brand-700 focus:ring-brand-500/20 focus:ring-2 disabled:opacity-50 disabled:cursor-not-allowed transition'
    ]) }}>
    
    @if ($label || $slot->isNotEmpty())
        <div class="text-body text-neutral-800">
            <div>{{ $label ?? $slot }}</div>
            @if ($description)
                <p class="text-caption text-neutral-500 mt-0.5">{{ $description }}</p>
            @endif
        </div>
    @endif
</label>

