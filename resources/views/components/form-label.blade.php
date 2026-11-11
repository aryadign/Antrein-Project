@props([
    'value' => null,
    'required' => false,
])

<label {{ $attributes->merge(['class' => 'block text-label text-neutral-700 mb-1.5 font-medium']) }}>
    {{ $value ?? $slot }}
    @if ($required)
        <span class="text-danger-700 ml-0.5" aria-hidden="true">*</span>
    @endif
</label>

