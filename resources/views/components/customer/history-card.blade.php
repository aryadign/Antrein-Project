@props(['ticket'])

@php
    [$statusLabel, $statusColor] = match ($ticket->status) {
        \App\Models\QueueTicket::STATUS_SELESAI => ['Selesai', 'bg-green-50 text-green-800 ring-green-600/20'],
        \App\Models\QueueTicket::STATUS_DILEWATI => ['Dilewati', 'bg-gray-100 text-gray-700 ring-gray-500/15'],
        \App\Models\QueueTicket::STATUS_TIDAK_HADIR => ['Tidak hadir', 'bg-amber-50 text-amber-800 ring-amber-600/20'],
        default => ['Dibatalkan', 'bg-red-50 text-red-800 ring-red-600/15'],
    };
@endphp

{{-- Figma: Kartu informasi (riwayat antrean) --}}
<a
    href="{{ route('customer.queue.track', $ticket->access_token) }}"
    {{ $attributes->class('flex flex-col gap-3 rounded-2xl border border-gray-200 bg-white p-4 transition-[border-color,box-shadow] hover:border-brand-700/40 hover:shadow-md') }}
>
    <div class="flex items-start justify-between gap-3">
        <p class="text-lg font-bold leading-[26px] text-brand-700 tabular-nums">{{ $ticket->ticket_number }}</p>
        <span class="shrink-0 rounded-full px-2.5 py-1 text-[11px] font-semibold ring-1 ring-inset {{ $statusColor }}">{{ $statusLabel }}</span>
    </div>
    <p class="text-base font-bold leading-6 text-gray-900">{{ $ticket->business->name }}</p>
    <div class="flex flex-wrap items-center gap-1.5 text-xs leading-4">
        <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2.5 py-1 font-semibold text-gray-800">
            <x-customer.icon name="ticket" class="size-3.5" />
            {{ $ticket->service?->name ?? 'Layanan' }}
        </span>
        <span class="inline-flex items-center gap-1 text-gray-500">
            <x-customer.icon name="clock" class="size-3.5" />
            {{ $ticket->created_at->translatedFormat('d M Y') }}, {{ $ticket->created_at->format('H.i') }}
        </span>
    </div>
    <div class="flex items-center justify-between text-xs">
        <span class="font-semibold leading-4 text-brand-700">Lihat Detail</span>
        <x-customer.icon name="arrow-right" class="size-4 text-brand-700" />
    </div>
</a>
