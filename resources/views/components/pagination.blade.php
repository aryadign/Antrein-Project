@props([
    'paginator' => null,
])

@if ($paginator && $paginator->hasPages())
    <nav role="navigation" aria-label="Navigasi halaman" class="flex items-center justify-between">
        <div class="flex justify-between flex-1 sm:hidden">
            @if ($paginator->onFirstPage())
                <span class="relative inline-flex min-h-11 items-center px-4 text-label font-medium text-neutral-500 bg-neutral-50 border border-neutral-200 cursor-not-allowed rounded-lg">
                    Sebelumnya
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="relative inline-flex min-h-11 items-center px-4 text-label font-medium text-neutral-700 bg-white border border-neutral-300 rounded-lg hover:bg-neutral-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">
                    Sebelumnya
                </a>
            @endif

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="relative inline-flex min-h-11 items-center px-4 ml-3 text-label font-medium text-neutral-700 bg-white border border-neutral-300 rounded-lg hover:bg-neutral-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">
                    Berikutnya
                </a>
            @else
                <span class="relative inline-flex min-h-11 items-center px-4 ml-3 text-label font-medium text-neutral-500 bg-neutral-50 border border-neutral-200 cursor-not-allowed rounded-lg">
                    Berikutnya
                </span>
            @endif
        </div>

        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
            <div>
                <p class="text-label text-neutral-600">
                    Menampilkan
                    <span class="font-medium text-neutral-900">{{ $paginator->firstItem() ?? 0 }}</span>
                    sampai
                    <span class="font-medium text-neutral-900">{{ $paginator->lastItem() ?? 0 }}</span>
                    dari
                    <span class="font-medium text-neutral-900">{{ $paginator->total() }}</span>
                    data
                </p>
            </div>

            <div>
                <span class="relative z-0 inline-flex gap-1">
                    {{-- Previous Page Link --}}
                    @if ($paginator->onFirstPage())
                        <span aria-disabled="true" aria-label="Sebelumnya">
                            <span class="relative inline-flex size-10 items-center justify-center text-neutral-500 bg-neutral-50 border border-neutral-200 cursor-not-allowed rounded-lg" aria-hidden="true">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                            </span>
                        </span>
                    @else
                        <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="relative inline-flex size-10 items-center justify-center text-neutral-600 bg-white border border-neutral-300 rounded-lg hover:bg-neutral-50 focus:z-10 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500" aria-label="Sebelumnya">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                        </a>
                    @endif

                    {{-- Next Page Link --}}
                    @if ($paginator->hasMorePages())
                        <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="relative inline-flex size-10 items-center justify-center text-neutral-600 bg-white border border-neutral-300 rounded-lg hover:bg-neutral-50 focus:z-10 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500" aria-label="Berikutnya">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" /></svg>
                        </a>
                    @else
                        <span aria-disabled="true" aria-label="Berikutnya">
                            <span class="relative inline-flex size-10 items-center justify-center text-neutral-500 bg-neutral-50 border border-neutral-200 cursor-not-allowed rounded-lg" aria-hidden="true">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" /></svg>
                            </span>
                        </span>
                    @endif
                </span>
            </div>
        </div>
    </nav>
@endif

