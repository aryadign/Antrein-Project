@php
    $timezone = $business->timezone ?: config('app.timezone');
    $timezoneLabel = match ($timezone) {
        'Asia/Jakarta' => 'WIB',
        'Asia/Jayapura' => 'WIT',
        default => 'WITA',
    };
    $eventLabels = [
        'status_changed' => 'Status Diperbarui',
        'ticket_recalled' => 'Dipanggil Ulang',
        'ticket_skipped' => 'Dilewati',
        'ticket_cancelled' => 'Dibatalkan',
        'note_added' => 'Catatan Ditambahkan',
    ];
    $eventTones = [
        'status_changed' => 'bg-brand-100 text-brand-700',
        'ticket_recalled' => 'bg-blue-100 text-blue-700',
        'ticket_skipped' => 'bg-neutral-200 text-neutral-700',
        'ticket_cancelled' => 'bg-rose-100 text-rose-700',
        'note_added' => 'bg-amber-100 text-amber-700',
    ];
    $actorLabels = ['merchant' => 'Petugas', 'customer' => 'Pelanggan', 'system' => 'Sistem'];
    $createdAt = $ticket->created_at->copy()->timezone($timezone);
@endphp

<x-layouts.merchant
    title="Detail Tiket {{ $ticket->ticket_number }}"
    :businessName="$business->name"
    :businessStatus="$business->status"
    :businessSlug="$business->slug"
>
    <div class="mx-auto max-w-4xl space-y-5 sm:space-y-6">
        {{-- Back Navigation --}}
        <a href="{{ route('merchant.queues') }}" class="inline-flex min-h-11 items-center gap-2 text-sm font-semibold text-neutral-600 transition hover:text-neutral-900">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            <span>Kembali ke Daftar Antrean</span>
        </a>

        {{-- Ticket Header Card --}}
        <section class="rounded-2xl border border-neutral-200 bg-white p-4 shadow-xs sm:p-6">
            <div class="mb-5 flex flex-col gap-4 border-b border-neutral-100 pb-5 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex min-w-0 items-center gap-4">
                    <div class="flex h-16 min-w-16 shrink-0 items-center justify-center rounded-xl border border-brand-200 bg-brand-50 px-3 text-2xl font-extrabold text-brand-700 tabular-nums">
                        {{ $ticket->ticket_number }}
                    </div>
                    <div class="min-w-0">
                        <h1 class="truncate text-h2 font-bold text-neutral-950">{{ $ticket->customer_name }}</h1>
                        <div class="mt-1 flex flex-wrap items-center gap-2">
                            <x-status-badge :status="$ticket->status" size="md" />
                            <span class="rounded-md px-2 py-0.5 text-xs font-semibold ring-1 ring-inset {{ $ticket->source === 'online' ? 'bg-blue-50 text-blue-700 ring-blue-600/20' : 'bg-neutral-100 text-neutral-700 ring-neutral-500/20' }}">
                                {{ $ticket->source === 'online' ? 'Tiket Online' : 'Walk-in (Manual)' }}
                            </span>
                        </div>
                    </div>
                </div>

                <dl class="grid grid-cols-3 gap-2 text-left sm:text-right">
                    <div>
                        <dt class="text-[11px] text-neutral-500">Urutan</dt>
                        <dd class="text-sm font-bold text-neutral-900 tabular-nums">ke-{{ $ticket->sequence_number }}</dd>
                    </div>
                    <div>
                        <dt class="text-[11px] text-neutral-500">Sesi</dt>
                        <dd class="text-sm font-bold text-neutral-900 tabular-nums">{{ \Illuminate\Support\Carbon::parse($ticket->dailyQueue->queue_date)->format('d/m/Y') }}</dd>
                    </div>
                    <div>
                        <dt class="text-[11px] text-neutral-500">ID Tiket</dt>
                        <dd class="text-sm font-bold text-neutral-900 tabular-nums">#{{ $ticket->id }}</dd>
                    </div>
                </dl>
            </div>

            {{-- Metadata Grid --}}
            <dl class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-lg border border-neutral-200 bg-neutral-50 p-3">
                    <dt class="text-caption text-neutral-500">Layanan</dt>
                    <dd class="mt-0.5 text-sm font-bold text-neutral-900">{{ $ticket->service->name }}</dd>
                    <dd class="text-[11px] text-neutral-500 tabular-nums">Durasi sekitar {{ $ticket->service->duration_minutes }} menit</dd>
                </div>

                <div class="rounded-lg border border-neutral-200 bg-neutral-50 p-3">
                    <dt class="text-caption text-neutral-500">Nomor Kontak</dt>
                    <dd class="mt-0.5 text-sm font-bold text-neutral-900 tabular-nums">{{ $ticket->customer_phone ?: 'Tidak dicantumkan' }}</dd>
                    @if ($ticket->customer_phone)
                        <dd class="text-[11px] text-neutral-500">WhatsApp / Telepon</dd>
                    @endif
                </div>

                <div class="rounded-lg border border-neutral-200 bg-neutral-50 p-3">
                    <dt class="text-caption text-neutral-500">Waktu & Slot</dt>
                    <dd class="mt-0.5 text-sm font-bold text-neutral-900 tabular-nums">{{ $createdAt->format('H:i:s') }} {{ $timezoneLabel }}</dd>
                    <dd class="text-[11px] text-neutral-500">{{ $createdAt->translatedFormat('d M Y') }}</dd>
                    @if ($ticket->bookingSlotWindowLabel())
                        <dd class="mt-1 inline-flex rounded border border-brand-200 bg-brand-50 px-1.5 py-0.5 text-[10px] font-semibold text-brand-700 tabular-nums">
                            Slot {{ $ticket->bookingSlotWindowLabel() }}
                        </dd>
                    @endif
                </div>

                <div class="rounded-lg border border-neutral-200 bg-neutral-50 p-3">
                    <dt class="text-caption text-neutral-500">Loket</dt>
                    <dd class="mt-0.5 text-sm font-bold text-neutral-900">{{ $ticket->counter ? $ticket->counter->name.' ('.$ticket->counter->code.')' : 'Belum ditentukan' }}</dd>
                    <dd>
                        <a href="{{ route('customer.queue.track', $ticket->access_token) }}" target="_blank" rel="noopener" class="inline-flex min-h-8 items-center text-[11px] font-semibold text-brand-700 hover:underline">
                            Lihat Halaman Pelanggan &rarr;
                        </a>
                    </dd>
                </div>
            </dl>

            @if ($ticket->notes)
                <div class="mt-4 rounded-lg border border-blue-200 bg-blue-50/50 p-3 text-sm text-neutral-700">
                    <strong class="font-semibold text-neutral-900">Catatan Pelanggan / Operator:</strong> {{ $ticket->notes }}
                </div>
            @endif

            @if ($ticket->cancellation_reason)
                <div class="mt-4 rounded-lg border border-rose-200 bg-rose-50 p-3 text-sm text-rose-800">
                    <strong class="font-semibold text-rose-950">Alasan Pembatalan:</strong> {{ $ticket->cancellation_reason }}
                </div>
            @endif
        </section>

        {{-- Status Audit Timeline (M7-27) --}}
        <section class="rounded-2xl border border-neutral-200 bg-white p-4 shadow-xs sm:p-6">
            <h3 class="mb-1 text-h3 font-bold text-neutral-900">Riwayat Perubahan Status (Audit Trail)</h3>
            <p class="mb-6 text-caption text-neutral-500">Log kronologis perubahan status tiket antrean, dari yang paling awal.</p>

            @if ($ticket->statusHistories->isEmpty())
                <p class="text-sm text-neutral-500">Belum ada riwayat aktivitas untuk tiket ini.</p>
            @else
                <ol class="relative space-y-4 border-l-2 border-neutral-200 pl-6">
                    @foreach ($ticket->statusHistories as $history)
                        @php($historyAt = $history->created_at->copy()->timezone($timezone))
                        <li class="relative">
                            <span class="absolute -left-[2.15rem] top-3 flex size-6 items-center justify-center rounded-full ring-4 ring-white {{ $eventTones[$history->event_type] ?? 'bg-neutral-200 text-neutral-700' }}">
                                <span class="text-[10px] font-extrabold tabular-nums">{{ $loop->iteration }}</span>
                            </span>

                            <div class="rounded-xl border border-neutral-200 bg-neutral-50 p-4">
                                <div class="flex flex-col gap-1 sm:flex-row sm:items-start sm:justify-between">
                                    <p class="text-sm font-bold text-neutral-900">{{ $eventLabels[$history->event_type] ?? Str::headline($history->event_type) }}</p>
                                    <time datetime="{{ $historyAt->toIso8601String() }}" class="text-caption text-neutral-500 tabular-nums">
                                        {{ $historyAt->format('d/m/Y H:i:s') }} {{ $timezoneLabel }}
                                    </time>
                                </div>

                                @if ($history->from_status && $history->to_status)
                                    <div class="mt-2 flex flex-wrap items-center gap-2">
                                        <x-status-badge :status="$history->from_status" size="sm" />
                                        <svg class="w-4 h-4 text-neutral-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-label="menjadi"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                                        <x-status-badge :status="$history->to_status" size="sm" />
                                    </div>
                                @endif

                                @if ($history->note)
                                    <p class="mt-2 rounded-lg border border-neutral-200 bg-white p-2.5 text-xs text-neutral-700">
                                        {{ $history->note }}
                                    </p>
                                @endif

                                <p class="mt-2 flex items-center gap-1.5 text-[11px] text-neutral-500">
                                    <svg class="w-3.5 h-3.5 text-neutral-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                    <span>Pelaku: <strong class="text-neutral-700">{{ $actorLabels[$history->actor_type] ?? Str::headline((string) $history->actor_type) }}</strong>@if ($history->actor) ({{ $history->actor->name }})@endif</span>
                                </p>
                            </div>
                        </li>
                    @endforeach
                </ol>
            @endif
        </section>
    </div>
</x-layouts.merchant>
