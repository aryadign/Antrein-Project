@props([
    'error' => false,
])

{{-- Password field with a show/hide toggle (44px touch target). Extra attributes go to the input. --}}
<div x-data="{ showPassword: false }" class="relative">
    <x-input
        type="password"
        x-bind:type="showPassword ? 'text' : 'password'"
        :error="$error"
        {{ $attributes->merge(['class' => 'pr-11']) }}
    />
    <button
        type="button"
        @click="showPassword = ! showPassword"
        class="absolute inset-y-0 right-0 flex w-11 items-center justify-center rounded-r-md text-neutral-400 hover:text-neutral-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500"
        x-bind:aria-label="showPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'"
        x-bind:aria-pressed="showPassword.toString()"
    >
        <svg x-show="! showPassword" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
        </svg>
        <svg x-show="showPassword" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true" style="display: none;">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
        </svg>
    </button>
</div>
