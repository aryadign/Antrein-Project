@props([
    'header' => null,
    'footer' => null,
])

<div class="w-full overflow-hidden rounded-xl border border-neutral-200 bg-white shadow-sm">
    <div class="overflow-x-auto">
        <table {{ $attributes->merge(['class' => 'min-w-full divide-y divide-neutral-200 text-left text-body']) }}>
            @if ($header)
                <thead class="bg-neutral-50/75 text-label font-semibold text-neutral-700">
                    {{ $header }}
                </thead>
            @endif

            <tbody class="divide-y divide-neutral-200 bg-white text-neutral-800">
                {{ $slot }}
            </tbody>
        </table>
    </div>

    @if ($footer)
        <div class="border-t border-neutral-200 bg-neutral-50/50 px-4 py-3 sm:px-6">
            {{ $footer }}
        </div>
    @endif
</div>

