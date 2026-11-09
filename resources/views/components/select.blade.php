@props([
    'disabled' => false,
    'error' => false,
    'placeholder' => null,
])

@php
    $baseClasses = 'w-full h-11 px-3.5 pr-10 text-body bg-white rounded-md border appearance-none transition-all duration-150 focus:outline-none bg-[url("data:image/svg+xml;charset=utf-8,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20fill%3D%22none%22%20viewBox%3D%220%200%2020%2020%22%3E%3Cpath%20stroke%3D%22%2364748B%22%20stroke-linecap%3D%22round%22%20stroke-linejoin%3D%22round%22%20stroke-width%3D%221.5%22%20d%3D%22m6%208%204%204%204-4%22%2F%3E%3C%2Fsvg%3E")] bg-[length:1.25rem_1.25rem] bg-[right_0.75rem_center] bg-no-repeat cursor-pointer';

    $stateClasses = $error
        ? 'border-danger-700 text-neutral-950 focus:ring-2 focus:ring-danger-700/20 focus:border-danger-700'
        : 'border-neutral-300 text-neutral-950 hover:border-neutral-400 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500';

    $disabledClasses = $disabled ? 'bg-neutral-100 text-neutral-500 cursor-not-allowed border-neutral-200' : '';

    $classes = "{$baseClasses} {$stateClasses} {$disabledClasses}";
@endphp

<div class="relative w-full">
    <select {{ $disabled ? 'disabled' : '' }} {{ $attributes->merge(['class' => $classes]) }}>
        @if ($placeholder)
            <option value="" disabled selected>{{ $placeholder }}</option>
        @endif
        {{ $slot }}
    </select>
</div>

