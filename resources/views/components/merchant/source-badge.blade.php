@props([
    'source',
])

{{-- How a ticket entered the queue: taken from a phone, or added at the counter for someone who came in person. --}}
@php
    $isOnline = $source === \App\Models\QueueTicket::SOURCE_ONLINE;
@endphp

<span {{ $attributes->class([
    'inline-flex items-center gap-1 whitespace-nowrap rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset',
    'bg-brand-50 text-brand-800 ring-brand-200' => $isOnline,
    'bg-neutral-100 text-neutral-700 ring-neutral-200' => ! $isOnline,
]) }}>
    @if ($isOnline)
        <svg class="size-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
        Dari ponsel
    @else
        <svg class="size-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
        Datang langsung
    @endif
</span>
