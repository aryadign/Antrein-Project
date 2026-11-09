<x-layouts.base title="422 — Permintaan Tidak Valid">
    <div class="min-h-screen bg-neutral-50 flex flex-col justify-center items-center px-4 py-12">
        <div class="text-center max-w-md">
            <span class="text-sm font-bold tracking-wider uppercase text-warning-700 bg-amber-100 px-3 py-1 rounded-full border border-amber-200">Error 422</span>
            <h1 class="text-3xl font-extrabold text-neutral-950 mt-4 tracking-tight">Permintaan Tidak Valid</h1>
            <p class="text-body text-neutral-600 mt-2">{{ $exception->getMessage() ?: 'Data yang dikirimkan tidak sesuai aturan bisnis atau status operasional saat ini.' }}</p>
            <div class="mt-6 flex justify-center gap-3">
                <x-button variant="primary" href="javascript:history.back()">Kembali</x-button>
            </div>
        </div>
    </div>
</x-layouts.base>

