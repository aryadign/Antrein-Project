@props([
    'for' => null,
    'message' => null,
    'messages' => [],
])

@php
    $errorMessage = $message
        ?? collect((array) $messages)->flatten()->filter()->first()
        ?? ($for && $errors->has($for) ? $errors->first($for) : null);
@endphp

@if ($errorMessage)
    <div {{ $attributes->merge(['class' => 'flex items-center gap-1.5 mt-1.5 text-caption text-danger-700 font-medium']) }} role="alert">
        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <span>{{ $errorMessage }}</span>
    </div>
@endif
