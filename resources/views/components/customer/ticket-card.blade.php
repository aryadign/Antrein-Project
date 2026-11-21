@props([
    'ticket',
    'compact' => false,
    'headingId' => null,
])

{{--
    The customer's current ticket: the one raised object on the screen.
    Order of information follows what a waiting customer asks first:
    number → status → place in line and estimate → what to do next.
--}}
@php
    $isWaiting = $ticket->status === \App\Models\QueueTicket::STATUS_MENUNGGU;
    $isCalled = $ticket->status === \App\Models\QueueTicket::STATUS_DIPANGGIL;
    $isServing = $ticket->status === \App\Models\QueueTicket::STATUS_SEDANG_DILAYANI;

    $peopleAhead = max(0, (int) ($ticket->queue_position ?? 0) - 1);
    $scheduleEstimate = $ticket->schedule_estimate ?? [];
    $serveTimeLabel = data_get($scheduleEstimate, 'estimated_serve_time_label');
    $waitRangeLabel = data_get($scheduleEstimate, 'wait_range_label');
    $hasDelayShift = (bool) data_get($scheduleEstimate, 'has_delay_shift');
    $shiftNotice = data_get($scheduleEstimate, 'shift_notice');
    $bookingSlotLabel = $ticket->bookingSlotWindowLabel();
    $counterName = $ticket->counter?->name;

    $positionLabel = match (true) {
        $isCalled => 'Segera menuju '.($counterName ?? 'loket pelayanan'),
        $isServing => 'Anda sedang dilayani'.($counterName ? ' di '.$counterName : ''),
        default => '',
    };
@endphp

<article {{ $attributes->class([
    'overflow-hidden rounded-2xl border bg-white shadow-raised',
    'border-brand-200' => ! $isCalled,
    'border-brand-500 ring-2 ring-brand-100' => $isCalled,
]) }} @if ($headingId) aria-labelledby="{{ $headingId }}" @endif>
    <header class="flex items-start justify-between gap-3 border-b border-brand-100 bg-brand-50/70 px-4 py-3">
        <div class="flex min-w-0 flex-col">
            <p @if ($headingId) id="{{ $headingId }}" @endif class="line-clamp-2 text-sm font-bold leading-5 text-gray-900">{{ $ticket->business->name }}</p>
            <p class="truncate text-xs leading-4 text-gray-600">{{ $ticket->service?->name ?? 'Layanan' }}</p>
        </div>
        <x-status-badge :status="$ticket" size="sm" class="shrink-0" />
    </header>

    <div class="flex flex-col gap-3 p-4">
        <div class="flex min-w-0 flex-col gap-1">
            <span class="text-xs font-medium leading-4 text-gray-600">Nomor antrean</span>
            <span @class([
                'font-extrabold leading-none tracking-tight whitespace-nowrap text-brand-700 tabular-nums',
                'text-4xl' => $compact,
                'text-5xl' => ! $compact,
            ])>{{ $ticket->ticket_number }}</span>
        </div>

        @if ($isWaiting)
            <dl class="grid grid-cols-2 border-y border-gray-100 py-3">
                <div class="flex min-w-0 flex-col gap-0.5 pr-3">
                    <dt class="text-xs font-medium leading-4 text-gray-600">Perkiraan dilayani</dt>
                    <dd class="text-lg font-bold leading-6 text-gray-900 tabular-nums">{{ $serveTimeLabel ?? 'Menyesuaikan' }}</dd>
                    @if ($bookingSlotLabel)
                        <dd class="text-xs leading-4 text-gray-600 tabular-nums">Slot {{ $bookingSlotLabel }}</dd>
                    @endif
                </div>
                <div class="flex min-w-0 flex-col gap-0.5 border-l border-gray-100 pl-3">
                    <dt class="text-xs font-medium leading-4 text-gray-600">Di depan Anda</dt>
                    <dd class="text-lg font-bold leading-6 text-gray-900 tabular-nums">{{ $peopleAhead > 0 ? $peopleAhead.' orang' : 'Tidak ada' }}</dd>
                    @if ($waitRangeLabel)
                        <dd class="text-xs leading-4 text-gray-600">{{ $waitRangeLabel }}</dd>
                    @endif
                </div>
            </dl>
        @else
            <p @class([
                'flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-semibold leading-5',
                'bg-brand-700 text-white' => $isCalled,
                'bg-brand-50 text-brand-800' => $isServing,
            ])>
                <x-customer.icon :name="$isCalled ? 'volume' : 'check-circle'" class="size-4" />
                <span>{{ $positionLabel }}</span>
            </p>
        @endif

        @if ($isWaiting && $hasDelayShift && $shiftNotice)
            <p class="flex items-start gap-2 rounded-lg bg-amber-50 px-3 py-2 text-xs leading-4 text-amber-900">
                <x-customer.icon name="alert-triangle" class="mt-px size-3.5 text-amber-700" />
                <span>{{ $shiftNotice }}</span>
            </p>
        @endif

        @if (($bookingSlotLabel && ! $isWaiting) || ! $compact)
            <dl class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs leading-4 text-gray-600">
                @if ($bookingSlotLabel && ! $isWaiting)
                    <div class="flex items-center gap-1">
                        <dt>Slot</dt>
                        <dd class="font-semibold text-gray-900 tabular-nums">{{ $bookingSlotLabel }}</dd>
                    </div>
                @endif
                @unless ($compact)
                    <div class="flex items-center gap-1">
                        <dt>Atas nama</dt>
                        <dd class="font-semibold text-gray-900">{{ $ticket->customer_name }}</dd>
                    </div>
                    <div class="flex items-center gap-1">
                        <dt class="sr-only">Diambil</dt>
                        <dd class="flex items-center gap-1">
                            <x-customer.icon name="clock" class="size-3.5 text-gray-500" />
                            {{ $ticket->created_at->translatedFormat('d M Y') }}, {{ $ticket->created_at->format('H.i') }}
                        </dd>
                    </div>
                @endunless
            </dl>
        @endif

        <x-button :href="route('customer.queue.track', $ticket->access_token)" class="w-full">
            {{ $isCalled ? 'Buka tiket & menuju loket' : 'Pantau tiket' }}
            <x-customer.icon name="arrow-right" class="size-4" />
        </x-button>

        {{ $slot }}
    </div>
</article>
