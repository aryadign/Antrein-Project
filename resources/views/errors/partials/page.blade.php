@php
    /**
     * Shared error page body.
     *
     * @var int|string $code
     * @var string $title
     * @var string $message
     * @var string $icon  x-customer.icon name
     * @var string $tone  danger|warning|brand
     * @var array<int, array{label: string, href?: string, onclick?: string, variant: string}> $actions
     * @var bool $showBack  offer "Halaman Sebelumnya" as a secondary action
     * @var bool $backIsPrimary  replace the primary action with a return to the previous page when one exists
     */
    $toneClasses = match ($tone ?? 'brand') {
        'danger' => 'bg-rose-50 text-danger-700 ring-rose-200',
        'warning' => 'bg-amber-50 text-amber-800 ring-amber-200',
        default => 'bg-brand-50 text-brand-700 ring-brand-100',
    };

    $previousUrl = url()->previous();
    $hasPrevious = $previousUrl !== url()->current() && str_starts_with($previousUrl, url('/'));
@endphp

<x-layouts.base :title="$title . ' — AntreIn'">
    <main class="flex min-h-screen flex-col items-center justify-center bg-neutral-50 px-4 py-12">
        <a href="{{ route('home') }}" class="rounded-lg focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-4" aria-label="AntreIn — kembali ke beranda">
            <x-brand-logo size="lg" />
        </a>

        <div class="mt-8 w-full max-w-md rounded-xl border border-neutral-200/80 bg-white p-6 text-center shadow-raised sm:p-8">
            <span class="mx-auto flex size-14 items-center justify-center rounded-full ring-1 {{ $toneClasses }}" aria-hidden="true">
                <x-customer.icon :name="$icon" class="size-7" />
            </span>

            <h1 class="mt-5 text-h2 font-bold tracking-tight text-balance text-neutral-950">{{ $title }}</h1>
            <p class="mt-2 text-body text-pretty text-neutral-600">{{ $message }}</p>

            <div class="mt-6 flex flex-col gap-2 sm:flex-row sm:justify-center">
                @if ($hasPrevious && ($backIsPrimary ?? false))
                    <x-button variant="primary" :href="$previousUrl" class="w-full sm:w-auto">
                        <x-customer.icon name="arrow-left" class="size-4" />
                        Kembali &amp; Coba Lagi
                    </x-button>
                @endif

                @foreach ($actions as $action)
                    @continue($hasPrevious && ($backIsPrimary ?? false) && $action['variant'] === 'primary')

                    @if (isset($action['onclick']))
                        <x-button :variant="$action['variant']" onclick="{{ $action['onclick'] }}" class="w-full sm:w-auto">{{ $action['label'] }}</x-button>
                    @else
                        <x-button :variant="$action['variant']" :href="$action['href']" class="w-full sm:w-auto">{{ $action['label'] }}</x-button>
                    @endif
                @endforeach

                @if ($hasPrevious && ($showBack ?? true) && ! ($backIsPrimary ?? false))
                    <x-button variant="outline" :href="$previousUrl" class="w-full sm:w-auto">
                        <x-customer.icon name="arrow-left" class="size-4" />
                        Halaman Sebelumnya
                    </x-button>
                @endif
            </div>
        </div>

        <p class="mt-6 text-caption text-neutral-600">Kode kesalahan {{ $code }}</p>
    </main>
</x-layouts.base>
