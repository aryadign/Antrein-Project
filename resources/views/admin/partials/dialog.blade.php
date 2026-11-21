{{--
    Admin confirmation dialog shell. Visibility is bound to an Alpine flag on the parent x-data.

    @param string $open   Alpine expression for visibility, e.g. "rejectModalOpen"
    @param string $id     Unique id used for aria-labelledby
--}}
<div
    x-show="{{ $open }}"
    x-transition.opacity.duration.200ms
    class="fixed inset-0 z-50 flex items-end justify-center overflow-y-auto bg-neutral-950/50 sm:items-center sm:p-4"
    style="display: none;"
    role="dialog"
    aria-modal="true"
    aria-labelledby="{{ $id }}-title"
    @keydown.escape.window="{{ $open }} = false"
>
    <div @click.outside="{{ $open }} = false" class="w-full max-w-md rounded-t-2xl border border-neutral-200 bg-white p-5 shadow-overlay sm:rounded-2xl sm:p-6">
        <div class="mb-4 flex items-start justify-between gap-3">
            <div class="min-w-0">
                <h3 id="{{ $id }}-title" class="text-h3 font-semibold text-neutral-900" @isset($titleExpression) x-text="{{ $titleExpression }}" @endisset>{{ $title ?? '' }}</h3>
                @isset($lead)
                    <p class="mt-1 text-sm text-pretty text-neutral-600">{{ $lead }}</p>
                @endisset
            </div>
            <button type="button" @click="{{ $open }} = false" class="-mr-2 -mt-1 inline-flex size-11 shrink-0 items-center justify-center rounded-lg text-neutral-500 transition-colors hover:bg-neutral-100 hover:text-neutral-700" aria-label="Tutup">
                <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        {{ $slot }}
    </div>
</div>
