@props(['business'])

@php
    $isOpen = $business->isOpen();
    $waitingCount = (int) ($business->waiting_count ?? 0);
    $waitRange = $isOpen ? $business->customerWaitRange($waitingCount) : null;
    $location = $business->areaLabel() ?? $business->cityLabel() ?? $business->address;
@endphp

<a
    href="{{ route('customer.queue', $business->slug) }}"
    {{ $attributes->class([
        'group flex flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm transition-[border-color,box-shadow,transform] duration-200 hover:-translate-y-0.5 hover:border-brand-300 hover:shadow-md active:scale-[0.99]',
        'opacity-85' => ! $isOpen,
    ]) }}
>
    <x-customer.store-cover :business="$business" :class="$isOpen ? '' : 'saturate-[.35]'">
        <div class="absolute top-2.5 right-2.5 flex items-center gap-1.5">
            <x-customer.rating :business="$business" variant="overlay" />
            @if ($waitRange !== null)
                <span class="rounded-full bg-white/95 px-2.5 py-0.5 text-[11px] font-extrabold text-gray-900 shadow-sm tabular-nums">{{ $waitRange['label'] }}</span>
            @endif
        </div>
    </x-customer.store-cover>

    <div class="flex flex-1 flex-col gap-2.5 p-3.5 sm:p-4">
        <div class="flex min-w-0 flex-col gap-1">
            <p class="truncate text-base font-extrabold leading-6 text-gray-900">{{ $business->name }}</p>
            @if ($location)
                <p class="flex min-w-0 items-center gap-1 text-xs leading-4 text-gray-500">
                    <x-customer.icon name="map-pin" class="size-3.5 shrink-0 text-brand-700" />
                    <span class="truncate font-semibold text-gray-700">{{ $location }}</span>
                </p>
            @endif
        </div>

        <x-customer.hours-badge :business="$business" />

        <div class="mt-auto flex items-center justify-between gap-2 border-t border-gray-100 pt-3">
            <p @class([
                'flex min-w-0 items-center gap-1.5 text-xs leading-4',
                'font-semibold text-amber-800' => $waitingCount >= 4 && $isOpen,
                'font-semibold text-brand-700' => $waitingCount > 0 && $waitingCount < 4 && $isOpen,
                'font-semibold text-emerald-700' => $waitingCount === 0 && $isOpen,
                'text-gray-500' => ! $isOpen,
            ])>
                <x-customer.icon name="users" class="size-3.5 shrink-0" />
                @if ($waitingCount > 0)
                    <span class="truncate"><span class="font-extrabold tabular-nums">{{ $waitingCount }}</span> orang menunggu</span>
                @else
                    <span class="truncate">{{ $isOpen ? 'Tanpa antrean' : 'Belum ada antrean' }}</span>
                @endif
            </p>
            @if ($isOpen)
                <span class="inline-flex shrink-0 items-center gap-1 rounded-lg border border-brand-200 bg-brand-50 px-3 py-1.5 text-xs font-bold text-brand-700 transition-colors group-hover:border-brand-700 group-hover:bg-brand-700 group-hover:text-white">
                    Antre di sini
                    <x-customer.icon name="arrow-right" class="size-3.5" />
                </span>
            @else
                <span class="inline-flex shrink-0 items-center gap-1 rounded-lg border border-gray-200 bg-gray-50 px-3 py-1.5 text-xs font-semibold text-gray-700 transition-colors group-hover:bg-gray-100">
                    Lihat layanan
                </span>
            @endif
        </div>
    </div>
</a>
