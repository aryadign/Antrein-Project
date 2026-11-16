@props([
    'title' => 'Masuk',
    'heading' => 'Masuk ke Akun Anda',
    'subheading' => 'Kelola operasional antrean usaha Anda dengan efisien.',
])

<x-layouts.base :title="$title . ' — AntreIn'">
    <div class="min-h-screen bg-neutral-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
        <div class="sm:mx-auto sm:w-full sm:max-w-md text-center">
            <a href="/" class="inline-flex items-center justify-center focus:outline-none">
                <x-brand-logo size="lg" />
            </a>
            <h2 class="mt-4 text-h2 font-bold tracking-tight text-neutral-950">{{ $heading }}</h2>
            @if ($subheading)
                <p class="mt-1 text-body text-neutral-600">{{ $subheading }}</p>
            @endif
        </div>

        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md px-4 sm:px-0">
            <x-card padding="lg" class="shadow-md">
                {{ $slot }}
            </x-card>

            <div class="mt-6 text-center text-caption text-neutral-500">
                <p>&copy; {{ date('Y') }} AntreIn Platform. Hak cipta dilindungi undang-undang.</p>
            </div>
        </div>
    </div>
</x-layouts.base>

