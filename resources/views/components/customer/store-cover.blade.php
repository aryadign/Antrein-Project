@props([
    'business',
    'size' => 'card',
    'showCategory' => true,
    'showLogo' => true,
])

{{--
    Store visual: a category stock photo under a soft gradient (or the category colour + icon when no
    photo fits), a category badge, and the store's own logo (initials when no logo is uploaded).
--}}
@php
    $category = $business->category;
    ['background' => $background, 'tint' => $iconTint, 'text' => $initialsColor] = $category?->customerPalette() ?? \App\Models\BusinessCategory::CUSTOMER_PALETTES['umum'];

    $iconUrl = asset($category?->iconPath() ?? 'images/figma/store.svg');
    $mask = "mask: url('{$iconUrl}') center / contain no-repeat; -webkit-mask: url('{$iconUrl}') center / contain no-repeat;";

    $initials = collect(preg_split('/\s+/', trim(preg_replace('/\(.*?\)/', '', (string) $business->name))))
        ->filter()
        ->take(2)
        ->map(fn (string $word): string => Str::upper(Str::substr($word, 0, 1)))
        ->implode('');

    $logoUrl = $business->getLogoUrl();
    $photoPath = $business->coverImagePath();
    $isHero = $size === 'hero';
@endphp

<div {{ $attributes->class(['relative isolate overflow-hidden', $background, $isHero ? 'h-40 md:h-48' : 'h-32']) }}>
    @if ($photoPath)
        <img src="{{ asset($photoPath) }}" alt="" loading="lazy" class="absolute inset-0 -z-10 size-full object-cover transition-transform duration-500 group-hover:scale-[1.03]" />
        <span class="absolute inset-0 -z-10 bg-linear-to-t from-gray-950/70 via-gray-950/15 to-gray-950/5" aria-hidden="true"></span>
    @else
        <span class="absolute -right-4 -bottom-6 -z-10 opacity-20 {{ $iconTint }} {{ $isHero ? 'size-40' : 'size-28' }}" style="{{ $mask }}" aria-hidden="true"></span>
        <span class="absolute top-5 right-28 -z-10 size-7 opacity-10 {{ $iconTint }}" style="{{ $mask }}" aria-hidden="true"></span>
    @endif

    @if ($showCategory && $category)
        <span class="absolute top-3 left-3 inline-flex items-center gap-1.5 rounded-full bg-white/95 px-2.5 py-1 text-xs font-semibold leading-4 text-gray-900 shadow-sm">
            <span class="size-3.5 {{ $iconTint }}" style="{{ $mask }}" aria-hidden="true"></span>
            {{ $category->shortName() }}
        </span>
    @endif

    @if ($showLogo)
        <div @class([
            'absolute left-3 flex items-center justify-center overflow-hidden rounded-xl bg-white shadow-md ring-2 ring-white',
            'bottom-4 left-4 size-16' => $isHero,
            'bottom-3 size-11' => ! $isHero,
        ])>
            @if ($logoUrl)
                <img src="{{ $logoUrl }}" alt="Logo {{ $business->name }}" class="size-full object-cover" />
            @else
                <span @class(['font-bold', $initialsColor, 'text-xl' => $isHero, 'text-sm' => ! $isHero]) aria-hidden="true">{{ $initials ?: '?' }}</span>
            @endif
        </div>
    @endif

    {{ $slot }}
</div>
