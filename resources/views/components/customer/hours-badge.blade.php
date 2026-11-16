@props([
    'business',
    'size' => 'sm',
])

{{-- Prominent opening-hours pill: "Buka s/d 21.00", "Antrean dijeda", "Buka pukul 08.00", "Tutup". --}}
@php
    ['label' => $label, 'tone' => $tone] = $business->customerHoursBadge();
@endphp

<span {{ $attributes->class([
    'inline-flex w-fit items-center gap-1.5 rounded-full font-semibold whitespace-nowrap ring-1 ring-inset',
    'px-2.5 py-1 text-xs leading-4' => $size === 'sm',
    'px-3 py-1.5 text-sm leading-5' => $size === 'lg',
    'bg-green-50 text-green-800 ring-green-600/20' => $tone === 'open',
    'bg-amber-50 text-amber-800 ring-amber-600/25' => $tone === 'paused',
    'bg-gray-100 text-gray-700 ring-gray-500/15' => $tone === 'closed',
]) }}>
    <x-customer.icon name="clock" :class="$size === 'lg' ? 'size-4' : 'size-3.5'" />
    {{ $label }}
</span>
