@props([
    'service',
    'category' => null,
])

{{--
    Service visual: the merchant's photo when uploaded, otherwise a stock photo matching the service
    or store category, and finally an illustration in the category colours.
--}}
@php
    $imageUrl = $service->imageUrl() ?? (($fallbackPath = $service->fallbackImagePath($category)) ? asset($fallbackPath) : null);
    ['background' => $background, 'tint' => $tint] = $category?->customerPalette() ?? \App\Models\BusinessCategory::CUSTOMER_PALETTES['umum'];
    $iconUrl = asset($service->iconPath() ?? $category?->iconPath() ?? 'images/figma/shopping-bag.svg');
    $mask = "mask: url('{$iconUrl}') center / contain no-repeat; -webkit-mask: url('{$iconUrl}') center / contain no-repeat;";
@endphp

<div {{ $attributes->class(['relative shrink-0 overflow-hidden rounded-xl', $background => ! $imageUrl]) }}>
    @if ($imageUrl)
        <img src="{{ $imageUrl }}" alt="{{ $service->name }}" loading="lazy" class="size-full object-cover" />
    @else
        <span class="absolute -right-3 -bottom-3 size-3/5 opacity-15 {{ $tint }}" style="{{ $mask }}" aria-hidden="true"></span>
        <span class="absolute inset-0 m-auto size-2/5 {{ $tint }}" style="{{ $mask }}" aria-hidden="true"></span>
    @endif
</div>
