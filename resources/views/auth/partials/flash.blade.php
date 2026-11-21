{{-- Session feedback shared by the auth screens: success (status) and failure (error) notices. --}}
@if (session('status'))
    <div class="mb-5 flex items-start gap-2.5 rounded-lg bg-emerald-50 px-3.5 py-3 text-label font-medium text-emerald-800 ring-1 ring-emerald-200" role="status">
        <x-customer.icon name="check" class="mt-0.5 size-4 shrink-0" />
        <p>{{ session('status') }}</p>
    </div>
@endif

@if (session('error'))
    <div class="mb-5 flex items-start gap-2.5 rounded-lg bg-rose-50 px-3.5 py-3 text-label font-medium text-rose-800 ring-1 ring-rose-200" role="alert">
        <x-customer.icon name="alert-triangle" class="mt-0.5 size-4 shrink-0" />
        <p>{{ session('error') }}</p>
    </div>
@endif
