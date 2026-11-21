@props([
    'ticket' => null,
    'counter' => null,
    'waitingCount' => 0,
    'canCallNext' => false,
    'callNextHint' => null,
    'variant' => 'desk',
])

{{--
    The one primary object on the operator screens: the ticket this counter is handling right now,
    or (when idle) how many people wait and the call-next action.
    variant="desk"    full controls (skip, cancel); on phones the primary action moves to the thumb dock.
    variant="summary" dashboard preview; primary action always visible, links to the full desk.
    The desk variant's "Batalkan" button expects the parent Alpine scope to define cancelTicketId,
    cancelTicketNumber and cancelModalOpen.
--}}
@php
    $isDesk = $variant === 'desk';
    $counterName = $ticket?->counter?->name ?? $counter?->name;
    $isCalled = $ticket?->status === \App\Models\QueueTicket::STATUS_DIPANGGIL;
    $isServing = $ticket?->status === \App\Models\QueueTicket::STATUS_SEDANG_DILAYANI;
    $primaryWrapperClasses = $isDesk ? 'hidden sm:block' : 'block';
    $megaphonePath = 'M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z';
@endphp

<section
    {{ $attributes->class([
        'overflow-hidden rounded-2xl border bg-white transition-shadow duration-200 ease-out',
        'border-brand-200 shadow-raised' => $ticket || $waitingCount > 0,
        'border-neutral-200/80 shadow-card' => ! $ticket && $waitingCount === 0,
    ]) }}
    aria-labelledby="now-serving-heading-{{ $variant }}"
    data-now-serving
>
    <header class="flex flex-wrap items-center justify-between gap-2 border-b px-4 py-3 sm:px-6 {{ $ticket ? 'border-brand-100 bg-brand-50/60' : 'border-neutral-200/80 bg-neutral-50/60' }}">
        <div class="flex min-w-0 items-center gap-2">
            <svg class="size-4 shrink-0 {{ $ticket ? 'text-brand-700' : 'text-neutral-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            <h2 id="now-serving-heading-{{ $variant }}" class="truncate text-sm font-semibold text-neutral-900">
                {{ $counterName ? $counterName : 'Loket utama' }}
            </h2>
        </div>
        @if ($ticket)
            <x-status-badge :status="$ticket" size="md" />
        @else
            <span class="inline-flex items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-800">
                <span class="size-1.5 rounded-full bg-emerald-600" aria-hidden="true"></span>
                Siap memanggil
            </span>
        @endif
    </header>

    @if ($ticket)
        <div class="grid gap-5 p-4 sm:p-6 {{ $isDesk ? 'md:grid-cols-[minmax(0,1fr)_19rem]' : 'xl:grid-cols-[minmax(0,1fr)_17rem]' }} md:items-center md:gap-8">
            <div class="min-w-0">
                <p class="text-sm font-medium text-neutral-600">{{ $isServing ? 'Sedang dilayani' : 'Nomor yang dipanggil' }}</p>
                <p class="mt-1 whitespace-nowrap text-5xl font-extrabold tracking-tight text-brand-900 tabular-nums sm:text-6xl">{{ $ticket->ticket_number }}</p>
                <p class="mt-2 truncate text-lg font-semibold text-neutral-900">{{ $ticket->customer_name }}</p>

                <div class="mt-3 flex flex-wrap items-center gap-2 text-sm text-neutral-700">
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-neutral-100 px-2.5 py-0.5 text-xs font-medium text-neutral-800 ring-1 ring-inset ring-neutral-200">
                        {{ $ticket->service->name }}
                        <span class="text-neutral-600 tabular-nums">{{ $ticket->service->duration_minutes }} mnt</span>
                    </span>
                    <x-merchant.source-badge :source="$ticket->source" />
                    @if ($ticket->customer_phone)
                        <span class="inline-flex items-center gap-1 text-xs text-neutral-600 tabular-nums">
                            <x-customer.icon name="phone" class="size-3.5" />
                            {{ $ticket->customer_phone }}
                        </span>
                    @endif
                </div>

                @if ($ticket->called_at)
                    <p class="mt-3 inline-flex items-center gap-1.5 text-xs text-neutral-600 tabular-nums">
                        <x-customer.icon name="clock" class="size-3.5" />
                        Dipanggil pukul {{ $ticket->called_at->format('H:i') }}
                        @if ($ticket->recalled_at)
                            <span>(dipanggil ulang {{ $ticket->recalled_at->format('H:i') }})</span>
                        @endif
                    </p>
                @endif
            </div>

            <div class="flex flex-col gap-2">
                <div class="{{ $primaryWrapperClasses }}">
                    @if ($isCalled)
                        <form method="POST" action="{{ route('merchant.queues.start-service', $ticket) }}">
                            @csrf
                            <x-button type="submit" size="lg" class="w-full">
                                <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                Mulai Melayani
                            </x-button>
                        </form>
                    @elseif ($isServing)
                        <form method="POST" action="{{ route('merchant.queues.complete', $ticket) }}">
                            @csrf
                            <x-button type="submit" size="lg" class="w-full bg-emerald-700! hover:bg-emerald-800! focus-visible:ring-emerald-600">
                                <x-customer.icon name="check" class="size-5" />
                                Selesaikan Pelayanan
                            </x-button>
                        </form>
                    @endif
                </div>

                @if ($isCalled)
                    <div class="grid grid-cols-2 gap-2">
                        <form method="POST" action="{{ route('merchant.queues.recall', $ticket) }}">
                            @csrf
                            <x-button type="submit" variant="outline" class="w-full whitespace-nowrap px-3">
                                <svg class="size-4 text-neutral-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                Panggil Ulang
                            </x-button>
                        </form>
                        @if ($isDesk)
                            <form method="POST" action="{{ route('merchant.queues.skip', $ticket) }}" onsubmit="return confirm('Lewati nomor {{ $ticket->ticket_number }}? Pelanggan ini tidak akan dipanggil lagi hari ini.');">
                                @csrf
                                <x-button type="submit" variant="outline" class="w-full whitespace-nowrap px-3">
                                    <svg class="size-4 text-neutral-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13 5l7 7-7 7M5 5l7 7-7 7"/></svg>
                                    Lewati
                                </x-button>
                            </form>
                        @else
                            <x-button variant="outline" :href="route('merchant.queues')" class="w-full px-3">Meja Antrean</x-button>
                        @endif
                    </div>
                @elseif (! $isDesk)
                    <x-button variant="outline" :href="route('merchant.queues')" class="w-full">Buka Meja Antrean</x-button>
                @endif

                @if ($isDesk)
                    <button
                        type="button"
                        @click="cancelTicketId = {{ $ticket->id }}; cancelTicketNumber = '{{ $ticket->ticket_number }}'; cancelModalOpen = true;"
                        class="inline-flex h-11 items-center justify-center rounded-lg px-3 text-sm font-semibold text-rose-700 transition-colors duration-150 hover:bg-rose-50 hover:text-rose-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-500"
                    >
                        Batalkan tiket ini
                    </button>
                @endif
            </div>
        </div>
    @else
        <div class="flex flex-col gap-5 p-4 sm:p-6 md:flex-row md:items-center md:justify-between">
            <div class="flex items-center gap-4">
                <p class="text-5xl font-extrabold tracking-tight tabular-nums {{ $waitingCount > 0 ? 'text-brand-900' : 'text-neutral-500' }}">{{ $waitingCount }}</p>
                <div class="min-w-0">
                    <p class="text-base font-semibold text-neutral-900">pelanggan menunggu</p>
                    <p class="mt-0.5 max-w-sm text-sm text-pretty text-neutral-600">
                        @if ($waitingCount > 0)
                            Panggil nomor berikutnya sesuai urutan kedatangan.
                        @else
                            Belum ada yang menunggu. Tiket baru muncul di sini secara otomatis.
                        @endif
                    </p>
                </div>
            </div>

            <div class="{{ $primaryWrapperClasses }} md:w-72">
                <form method="POST" action="{{ route('merchant.queues.call-next') }}" x-data="{ submitting: false }" @submit="submitting = true">
                    @csrf
                    @if ($counter)
                        <input type="hidden" name="counter_id" value="{{ $counter->id }}">
                    @endif
                    <x-button
                        type="submit"
                        size="lg"
                        class="w-full"
                        :disabled="! $canCallNext"
                        x-bind:disabled="submitting || {{ $canCallNext ? 'false' : 'true' }}"
                        title="{{ $callNextHint }}"
                    >
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $megaphonePath }}"/></svg>
                        Panggil Berikutnya
                    </x-button>
                </form>
            </div>
        </div>
    @endif
</section>
