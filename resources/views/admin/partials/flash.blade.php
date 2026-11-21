{{-- Session status and validation errors shared by every admin page. --}}
@if (session('status'))
    <div class="flex items-start gap-2.5 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-800 shadow-card" role="status">
        <svg class="mt-px size-5 shrink-0 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
        <span>{{ session('status') }}</span>
    </div>
@endif

@if (session('error'))
    <div class="flex items-start gap-2.5 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm font-medium text-rose-800 shadow-card" role="alert">
        <svg class="mt-px size-5 shrink-0 text-rose-700" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" /></svg>
        <span>{{ session('error') }}</span>
    </div>
@endif

@if ($errors->any())
    <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800 shadow-card" role="alert">
        <p class="font-semibold">Periksa kembali isian berikut:</p>
        <ul class="mt-1 list-inside list-disc space-y-0.5">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
