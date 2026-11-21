{{--
    Segmented status filter used on admin list pages.

    @param array<int, array{label: string, href: string, active: bool, count?: int|null}> $tabs
    @param string $label  Accessible name of the nav
--}}
<nav class="-mx-4 overflow-x-auto px-4 sm:mx-0 sm:px-0" aria-label="{{ $label }}">
    <div class="flex w-max gap-1 rounded-lg border border-neutral-200 bg-neutral-100 p-1">
        @foreach ($tabs as $tab)
            <a
                href="{{ $tab['href'] }}"
                @if ($tab['active']) aria-current="page" @endif
                class="inline-flex min-h-9 items-center gap-2 whitespace-nowrap rounded-md px-3 text-sm font-medium transition-colors duration-150 ease-out {{ $tab['active'] ? 'bg-white text-brand-700 shadow-card' : 'text-neutral-600 hover:bg-white/60 hover:text-neutral-900' }}"
            >
                {{ $tab['label'] }}
                @if (isset($tab['count']))
                    <span class="rounded-full px-1.5 text-xs font-semibold tabular-nums {{ $tab['active'] ? 'bg-brand-50 text-brand-700' : 'bg-neutral-200/70 text-neutral-700' }}">{{ number_format($tab['count']) }}</span>
                @endif
            </a>
        @endforeach
    </div>
</nav>
