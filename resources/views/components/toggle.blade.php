@props([
    'disabled' => false,
    'label' => null,
    'description' => null,
    'checked' => false,
])

<label x-data="{ state: @js($checked) }" class="inline-flex items-center gap-3 cursor-pointer select-none">
    <button type="button" 
        role="switch" 
        :aria-checked="state.toString()" 
        @click="if (!@js($disabled)) state = !state"
        :class="state ? 'bg-brand-700' : 'bg-neutral-300'"
        {{ $disabled ? 'disabled' : '' }}
        class="relative inline-flex h-6 w-11 shrink-0 rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed">
        <span 
            :class="state ? 'translate-x-5' : 'translate-x-0'"
            class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow-sm ring-0 transition duration-200 ease-in-out"></span>
    </button>

    @if ($label)
        <div class="text-body text-neutral-800">
            <span class="font-medium">{{ $label }}</span>
            @if ($description)
                <p class="text-caption text-neutral-500">{{ $description }}</p>
            @endif
        </div>
    @endif
</label>

