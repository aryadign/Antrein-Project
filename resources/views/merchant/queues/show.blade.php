<x-layouts.merchant 
    title="Detail Tiket {{ $ticket->ticket_number }}" 
    :businessName="$business->name"
    :businessStatus="$business->status"
    :businessSlug="$business->slug"
>
    <div class="space-y-6 max-w-4xl mx-auto">
        {{-- Breadcrumb / Back Navigation --}}
        <div>
            <a href="{{ route('merchant.queues') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-neutral-600 hover:text-neutral-900 transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                <span>Kembali ke Daftar Antrean</span>
            </a>
        </div>

        {{-- Ticket Header Card --}}
        <div class="bg-white rounded-2xl border border-neutral-200 p-6 shadow-xs">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-neutral-100 pb-5 mb-5">
                <div class="flex items-center gap-4">
                    <div class="w-16 h-16 rounded-xl bg-brand-50 border border-brand-200 text-brand-700 flex items-center justify-center font-extrabold text-2xl tabular-nums">
                        {{ $ticket->ticket_number }}
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h1 class="text-h2 font-bold text-neutral-950">{{ $ticket->customer_name }}</h1>
                            <x-status-badge :status="$ticket->status" size="md" />
                        </div>
                        <p class="text-caption text-neutral-500 mt-0.5">
                            Tiket ID #{{ $ticket->id }} • Sesi Tanggal: {{ $ticket->dailyQueue->queue_date }}
                        </p>
                    </div>
                </div>

                <div class="text-left sm:text-right">
                    <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold {{ $ticket->source === 'online' ? 'bg-blue-50 text-blue-700 border border-blue-200' : 'bg-neutral-100 text-neutral-700 border border-neutral-200' }}">
                        {{ $ticket->source === 'online' ? 'Tiket Online' : 'Walk-in (Manual)' }}
                    </span>
                    <span class="block text-caption text-neutral-400 mt-1">
                        Urutan ke-{{ $ticket->sequence_number }}
                    </span>
                </div>
            </div>

            {{-- Metadata Grid --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                <div class="p-3 bg-neutral-50 rounded-lg border border-neutral-200">
                    <span class="text-caption text-neutral-500 block">Layanan</span>
                    <span class="font-bold text-sm text-neutral-900 block mt-0.5">{{ $ticket->service->name }}</span>
                    <span class="text-[11px] text-neutral-400">Durasi ~{{ $ticket->service->duration_minutes }} menit</span>
                </div>

                <div class="p-3 bg-neutral-50 rounded-lg border border-neutral-200">
                    <span class="text-caption text-neutral-500 block">Nomor Kontak</span>
                    <span class="font-bold text-sm text-neutral-900 block mt-0.5">{{ $ticket->customer_phone ?: '—' }}</span>
                    <span class="text-[11px] text-neutral-400">{{ $ticket->customer_phone ? 'WhatsApp / Telepon' : 'Tidak dicantumkan' }}</span>
                </div>

                <div class="p-3 bg-neutral-50 rounded-lg border border-neutral-200">
                    <span class="text-caption text-neutral-500 block">Waktu Pengambilan</span>
                    <span class="font-bold text-sm text-neutral-900 block mt-0.5">{{ $ticket->created_at->format('H:i:s') }} WITA</span>
                    <span class="text-[11px] text-neutral-400">{{ $ticket->created_at->format('d M Y') }}</span>
                </div>

                <div class="p-3 bg-neutral-50 rounded-lg border border-neutral-200">
                    <span class="text-caption text-neutral-500 block">Token Akses Publik</span>
                    <span class="font-mono text-xs text-neutral-800 truncate block mt-0.5" title="{{ $ticket->access_token }}">{{ substr($ticket->access_token, 0, 16) }}...</span>
                    <a href="{{ route('customer.queue.track', $ticket->access_token) }}" target="_blank" class="text-[11px] font-semibold text-brand-700 hover:underline">
                        Lihat Tracking Pelanggan &rarr;
                    </a>
                </div>
            </div>

            @if ($ticket->notes)
                <div class="mt-4 p-3 bg-blue-50/50 rounded-lg border border-blue-200 text-xs text-neutral-700">
                    <strong class="font-semibold text-neutral-900">Catatan Pelanggan / Operator:</strong> {{ $ticket->notes }}
                </div>
            @endif

            @if ($ticket->cancellation_reason)
                <div class="mt-4 p-3 bg-rose-50 rounded-lg border border-rose-200 text-xs text-rose-800">
                    <strong class="font-semibold text-rose-950">Alasan Pembatalan:</strong> {{ $ticket->cancellation_reason }}
                </div>
            @endif
        </div>

        {{-- Status Audit Timeline (M7-27) --}}
        <div class="bg-white rounded-2xl border border-neutral-200 p-6 shadow-xs">
            <h3 class="text-h3 font-bold text-neutral-900 mb-1">Riwayat Perubahan Status (Audit Trail)</h3>
            <p class="text-caption text-neutral-500 mb-6">Log kronologis perubahan status tiket antrean.</p>

            @if ($ticket->statusHistories->isEmpty())
                <p class="text-sm text-neutral-500">Belum ada riwayat aktivitas untuk tiket ini.</p>
            @else
                <div class="relative pl-6 space-y-6 before:absolute before:left-2.5 before:top-2 before:bottom-2 before:w-0.5 before:bg-neutral-200">
                    @foreach ($ticket->statusHistories as $history)
                        <div class="relative flex items-start gap-4">
                            <span class="absolute -left-6 top-1 w-3.5 h-3.5 rounded-full bg-brand-600 ring-4 ring-white"></span>
                            
                            <div class="flex-1 bg-neutral-50 rounded-xl p-4 border border-neutral-200">
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1 mb-1.5">
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold text-sm text-neutral-900 capitalize">
                                            {{ str_replace('_', ' ', $history->event_type) }}
                                        </span>
                                        @if ($history->from_status && $history->to_status)
                                            <span class="text-caption text-neutral-400">
                                                ({{ $history->from_status }} &rarr; <strong>{{ $history->to_status }}</strong>)
                                            </span>
                                        @endif
                                    </div>
                                    <span class="text-caption text-neutral-400 tabular-nums">
                                        {{ $history->created_at->format('d/m/Y H:i:s') }} WITA
                                    </span>
                                </div>

                                @if ($history->note)
                                    <p class="text-xs text-neutral-700 bg-white p-2.5 rounded-lg border border-neutral-200 mt-2">
                                        {{ $history->note }}
                                    </p>
                                @endif

                                <div class="mt-2 text-[11px] text-neutral-500 flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 text-neutral-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                    <span>Pelaku: <strong class="text-neutral-700 capitalize">{{ $history->actor_type }}</strong> {{ $history->actor ? '(' . $history->actor->name . ')' : '' }}</span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-layouts.merchant>

