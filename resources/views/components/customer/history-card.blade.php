@props(['ticket'])

{{-- Finished ticket in Antrean Saya and Riwayat. Status label and tone come from QueueTicket::statusPresentation(). --}}
<a
    href="{{ route('customer.queue.track', $ticket->access_token) }}"
    {{ $attributes->class('group flex flex-col gap-2.5 rounded-xl border border-gray-200 bg-white p-4 shadow-card transition-[border-color,box-shadow] duration-200 ease-out hover:border-brand-300 hover:shadow-raised focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600') }}
>
    <div class="flex items-start justify-between gap-3">
        <div class="flex min-w-0 flex-col gap-0.5">
            <p class="text-base font-bold leading-6 text-gray-900">{{ $ticket->business->name }}</p>
            <p class="truncate text-sm leading-5 text-gray-600">{{ $ticket->service?->name ?? 'Layanan' }}</p>
        </div>
        <x-status-badge :status="$ticket" size="sm" class="shrink-0" />
    </div>
    <div class="flex items-center justify-between gap-3 border-t border-gray-100 pt-2.5 text-xs leading-4">
        <span class="flex items-center gap-3 text-gray-600">
            <span class="font-bold text-brand-700 tabular-nums">{{ $ticket->ticket_number }}</span>
            <span class="inline-flex items-center gap-1">
                <x-customer.icon name="clock" class="size-3.5 text-gray-500" />
                {{ $ticket->created_at->translatedFormat('d M Y') }}, {{ $ticket->created_at->format('H.i') }}
            </span>
        </span>
        <span class="inline-flex shrink-0 items-center gap-1 font-semibold text-brand-700">
            Lihat detail
            <x-customer.icon name="arrow-right" class="size-3.5 transition-transform duration-200 group-hover:translate-x-0.5" />
        </span>
    </div>
</a>
