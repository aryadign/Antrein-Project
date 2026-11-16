@props([
    'ticket',
    'size' => 'md',
])

@php
    [$statusLabel, $statusPillClass] = match ($ticket->status) {
        \App\Models\QueueTicket::STATUS_DIPANGGIL => ['Sedang Dipanggil', 'bg-white text-brand-700'],
        \App\Models\QueueTicket::STATUS_SEDANG_DILAYANI => ['Sedang dilayani', 'bg-emerald-200 text-emerald-950'],
        default => ['Menunggu', 'bg-amber-300 text-slate-900'],
    };

    $peopleAhead = max(0, (int) ($ticket->queue_position ?? 0) - 1);
    $scheduleEstimate = $ticket->schedule_estimate ?? [];
    $serveTimeLabel = data_get($scheduleEstimate, 'estimated_serve_time_label');
    $waitRangeLabel = data_get($scheduleEstimate, 'wait_range_label');
    $hasDelayShift = (bool) data_get($scheduleEstimate, 'has_delay_shift');
    $shiftNotice = data_get($scheduleEstimate, 'shift_notice');
    $bookingSlotLabel = $ticket->bookingSlotWindowLabel();
@endphp

{{-- Two-Tone Physical Ticket Pass matching Screen 1 & 4 --}}
<div {{ $attributes->class('overflow-hidden rounded-2xl border border-brand-600/30 bg-white shadow-md') }}>
    <div class="flex items-center justify-between gap-3 bg-linear-to-r from-brand-800 to-brand-600 px-4 py-3 text-white">
        <div class="flex min-w-0 flex-col">
            <span class="text-[10px] font-bold tracking-wider text-brand-100 uppercase">Tiket Antrean</span>
            <p class="truncate text-sm font-bold leading-5 text-white">{{ $ticket->business->name }}</p>
        </div>
        <span class="shrink-0 rounded-full px-2.5 py-1 text-[11px] font-extrabold {{ $statusPillClass }}">{{ $statusLabel }}</span>
    </div>

    <div class="relative p-4">
        <span class="pointer-events-none absolute -top-2 -left-2 size-4 rounded-full border-r border-brand-600/20 bg-white" aria-hidden="true"></span>
        <span class="pointer-events-none absolute -top-2 -right-2 size-4 rounded-full border-l border-brand-600/20 bg-white" aria-hidden="true"></span>

        <div class="flex items-center gap-3.5">
            <div class="flex shrink-0 flex-col items-center justify-center rounded-xl border border-brand-200 bg-brand-50 px-3.5 py-2 text-center">
                <span class="text-[9px] font-bold tracking-wider text-brand-700/80 uppercase">Tiket</span>
                <span @class([
                    'font-extrabold leading-none text-brand-700 tabular-nums',
                    'text-3xl sm:text-4xl' => $size === 'lg',
                    'text-2xl sm:text-3xl' => $size !== 'lg',
                ])>{{ $ticket->ticket_number }}</span>
            </div>

            <div class="flex min-w-0 flex-1 flex-col gap-1">
                <span class="inline-flex w-fit max-w-full items-center gap-1 rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-semibold text-gray-800">
                    <x-customer.icon name="ticket" class="size-3.5 shrink-0 text-brand-700" />
                    <span class="truncate">{{ $ticket->service?->name ?? 'Layanan' }}</span>
                </span>
                <p class="truncate text-xs leading-4 text-gray-600">Atas nama <span class="font-semibold text-gray-900">{{ $ticket->customer_name }}</span></p>
                @if ($bookingSlotLabel)
                    <p class="text-xs leading-4 text-gray-700">Slot booking: <span class="font-bold text-brand-700 tabular-nums">{{ $bookingSlotLabel }}</span></p>
                @endif
            </div>
        </div>

        <p class="mt-3 flex items-center gap-1.5 text-xs leading-4 text-gray-500">
            <x-customer.icon name="clock" class="size-3.5 text-gray-400" />
            {{ $ticket->created_at->translatedFormat('l, d M Y') }}, pukul {{ $ticket->created_at->format('H.i') }}
        </p>

        @if ($ticket->status === \App\Models\QueueTicket::STATUS_MENUNGGU)
            <div class="mt-3 flex flex-col gap-1.5 border-t border-dashed border-gray-200 pt-2.5 text-xs">
                <div class="flex items-center justify-between gap-2">
                    <span class="font-bold text-gray-900">Posisi #{{ $ticket->queue_position }} ({{ $peopleAhead > 0 ? $peopleAhead.' orang di depan Anda' : 'Giliran berikutnya' }})</span>
                    <span class="font-extrabold text-brand-700 tabular-nums">{{ $serveTimeLabel ? 'Dilayani ~ '.$serveTimeLabel : ($waitRangeLabel ?? 'Segera') }}</span>
                </div>
                @if ($hasDelayShift && $shiftNotice)
                    <p class="rounded-lg border border-amber-200 bg-amber-50 px-2.5 py-1.5 text-[11px] leading-4 font-medium text-amber-900">{{ $shiftNotice }}</p>
                @endif
            </div>
        @endif
    </div>
</div>
