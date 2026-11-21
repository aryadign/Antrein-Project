@props([
    'business',
    'variant' => 'inline',
])

{{-- Store rating summary, e.g. "★ 4.8 (12 ulasan)". Renders nothing until the store has a review. --}}
@php
    $rating = $business->customerRating();
    $reviewCount = $business->customerReviewCount();
@endphp

@if ($rating !== null && $reviewCount > 0)
    <span {{ $attributes->class([
        'inline-flex w-fit items-center gap-1 whitespace-nowrap tabular-nums',
        'text-xs leading-4 text-gray-700' => $variant === 'inline',
        'rounded-full bg-white/95 px-2 py-0.5 text-xs font-semibold leading-4 text-gray-900 shadow-sm' => $variant === 'overlay',
        'rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold leading-4 text-amber-900 ring-1 ring-inset ring-amber-500/25' => $variant === 'pill',
    ]) }}>
        <x-customer.icon name="star" class="size-3.5 text-amber-500" />
        <span @class(['font-semibold text-gray-900' => $variant === 'inline'])>{{ number_format($rating, 1) }}</span>
        <span @class(['text-gray-500' => $variant !== 'pill', 'font-medium text-amber-800' => $variant === 'pill'])>({{ $reviewCount }} ulasan)</span>
    </span>
@endif
