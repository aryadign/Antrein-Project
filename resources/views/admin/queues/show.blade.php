@php
    $timezone = $ticket->business?->timezone ?: config('app.timezone');
    $timezoneLabel = match ($timezone) {
        'Asia/Jakarta' => 'WIB',
        'Asia/Jayapura' => 'WIT',
        default => 'WITA',
    };
@endphp

<x-layouts.admin
    :title="'Monitoring Tiket ' . $ticket->ticket_number"
    :heading="'Detail Tiket: ' . $ticket->ticket_number" 
    description="Pemantauan audit lintas-gerai untuk tiket antrean dan rekam jejak status transisinya."
>
    <div class="space-y-5 sm:space-y-6">
        {{-- Top Navigation --}}
        <div class="flex flex-wrap items-center justify-between gap-2">
            <a href="{{ route('admin.queues.index') }}" class="inline-flex min-h-11 items-center gap-2 text-xs font-semibold text-neutral-600 hover:text-neutral-900 transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Kembali ke Daftar Antrean
            </a>

            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-neutral-100 text-neutral-700 border border-neutral-200">
                <svg class="w-3.5 h-3.5 text-neutral-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                Mode Audit Read-Only
            </span>
        </div>

        {{-- Read-Only Operational Restriction Banner (M8-25) --}}
        <div class="rounded-xl bg-blue-50/75 border border-blue-200 p-4 text-xs text-blue-900 flex items-start gap-3 shadow-xs">
            <svg class="w-5 h-5 text-blue-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <div>
                <strong class="font-bold text-blue-950 block">Pengingat Pembatasan Wewenang Administrator</strong>
                <p class="mt-0.5 text-blue-800">
                    Sistem AntreIn menerapkan pemisahan wewenang yang tegas: Administrator hanya memiliki hak pemantauan audit (read-only). Tombol operasional seperti Panggil, Mulai Layani, Selesaikan, Lewati, dan Batalkan hanya dapat dioperasikan oleh akun pemilik gerai UMKM terkait demi integritas alur fisik pelayanan. Nomor kontak pelanggan disamarkan (OQ-14).
                </p>
            </div>
        </div>

        {{-- Ticket Header Card --}}
        <div class="bg-white rounded-xl border border-neutral-200 p-4 shadow-xs sm:p-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex min-w-0 items-center gap-4">
                    <div class="h-16 min-w-16 px-3 rounded-2xl bg-brand-50 border border-brand-200 text-brand-700 font-mono font-extrabold text-2xl tabular-nums flex items-center justify-center shrink-0">
                        {{ $ticket->ticket_number }}
                    </div>
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="text-xl font-bold text-neutral-950">Tiket {{ $ticket->ticket_number }}</h2>

                            <x-status-badge :status="$ticket->status" size="sm" />

                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold {{ $ticket->source === 'online' ? 'bg-sky-50 text-sky-700 border border-sky-200' : 'bg-neutral-100 text-neutral-700 border border-neutral-200' }}">
                                {{ $ticket->source === 'online' ? 'Online' : 'Walk-in' }}
                            </span>
                        </div>
                        <p class="text-caption text-neutral-500 mt-1">
                            Terdaftar di gerai 
                            @if ($ticket->business)
                                <a href="{{ route('admin.businesses.show', $ticket->business) }}" class="font-bold text-neutral-900 hover:text-brand-600 hover:underline">
                                    {{ $ticket->business->name }}
                                </a>
                            @else
                                <span class="font-semibold text-neutral-800">—</span>
                            @endif
                            pada <span class="tabular-nums">{{ $ticket->created_at->copy()->timezone($timezone)->format('d M Y, H:i') }} {{ $timezoneLabel }}</span>
                        </p>
                    </div>
                </div>

                <div class="sm:text-right">
                    <span class="text-caption text-neutral-400 block">ID Tiket Database</span>
                    <span class="font-mono text-xs text-neutral-700 font-bold bg-neutral-100 px-2 py-1 rounded inline-block mt-0.5">
                        #{{ $ticket->id }}
                    </span>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-5 lg:grid-cols-2 lg:gap-6">
            {{-- Customer Information (Masked PII) --}}
            <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden shadow-xs">
                <div class="p-4 sm:p-5 border-b border-neutral-200 flex flex-wrap items-start justify-between gap-2">
                    <div class="min-w-0">
                        <h3 class="text-h3 font-bold text-neutral-900">Informasi Pelanggan</h3>
                        <p class="text-caption text-neutral-500 mt-0.5">Data pemesan antrean dengan proteksi privasi.</p>
                    </div>
                    <span class="text-[11px] font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">
                        PII Protected (OQ-14)
                    </span>
                </div>

                <div class="p-4 sm:p-5 divide-y divide-neutral-100 text-sm">
                    <div class="py-2.5 flex justify-between items-start gap-3">
                        <span class="shrink-0 text-neutral-500">Nama Pelanggan</span>
                        <span class="min-w-0 text-right font-semibold text-neutral-900">{{ $ticket->customer_name }}</span>
                    </div>

                    <div class="py-2.5 flex justify-between items-start gap-3">
                        <span class="shrink-0 text-neutral-500">Nomor Telepon (Masked)</span>
                        <span class="font-mono font-medium text-neutral-800 bg-neutral-100 px-2 py-0.5 rounded text-xs">
                            {{ \App\Http\Controllers\Admin\QueueMonitoringController::maskPhoneNumber($ticket->customer_phone) }}
                        </span>
                    </div>

                    <div class="py-2.5 flex justify-between items-start gap-3">
                        <span class="shrink-0 text-neutral-500">Kanal Pendaftaran</span>
                        <span class="min-w-0 text-right font-semibold {{ $ticket->source === 'online' ? 'text-sky-600' : 'text-neutral-800' }}">
                            {{ $ticket->source === 'online' ? 'Online (Customer Web App)' : 'Walk-in (Didaftarkan di Tempat)' }}
                        </span>
                    </div>

                    <div class="py-2.5 flex justify-between items-start gap-3">
                        <span class="shrink-0 text-neutral-500">Catatan Pelanggan</span>
                        <span class="font-medium text-neutral-800 text-right max-w-xs">
                            {{ $ticket->notes ?: '— (Tidak ada catatan)' }}
                        </span>
                    </div>

                    <div class="py-2.5 flex justify-between items-start gap-3">
                        <span class="shrink-0 text-neutral-500">Estimasi Waktu Pelayanan</span>
                        <span class="font-medium text-neutral-800">
                            {{ $ticket->estimated_service_time ? \Illuminate\Support\Carbon::parse($ticket->estimated_service_time)->timezone($timezone)->format('H:i').' '.$timezoneLabel : '—' }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- Business & Service Context --}}
            <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden shadow-xs">
                <div class="p-4 sm:p-5 border-b border-neutral-200">
                    <h3 class="text-h3 font-bold text-neutral-900">Gerai & Layanan Terkait</h3>
                    <p class="text-caption text-neutral-500 mt-0.5">Konteks operasional UMKM tempat antrean didaftarkan.</p>
                </div>

                <div class="p-4 sm:p-5 divide-y divide-neutral-100 text-sm">
                    <div class="py-2.5 flex justify-between items-start gap-3">
                        <span class="shrink-0 text-neutral-500">Gerai UMKM</span>
                        @if ($ticket->business)
                            <a href="{{ route('admin.businesses.show', $ticket->business) }}" class="min-w-0 text-right font-bold text-brand-600 hover:underline">
                                {{ $ticket->business->name }}
                            </a>
                        @else
                            <span class="text-neutral-400">—</span>
                        @endif
                    </div>

                    <div class="py-2.5 flex justify-between items-start gap-3">
                        <span class="shrink-0 text-neutral-500">Kategori Bisnis</span>
                        <span class="font-medium text-neutral-800">
                            {{ $ticket->business?->category?->name ?? '—' }}
                        </span>
                    </div>

                    <div class="py-2.5 flex justify-between items-start gap-3">
                        <span class="shrink-0 text-neutral-500">Layanan Dipilih</span>
                        <span class="min-w-0 text-right font-semibold text-neutral-900">
                            {{ $ticket->service?->name ?? '—' }}
                        </span>
                    </div>

                    <div class="py-2.5 flex justify-between items-start gap-3">
                        <span class="shrink-0 text-neutral-500">Estimasi Durasi Layanan</span>
                        <span class="font-medium text-neutral-800">
                            {{ $ticket->service?->duration_minutes ?? '—' }} Menit
                        </span>
                    </div>

                    <div class="py-2.5 flex justify-between items-start gap-3">
                        <span class="shrink-0 text-neutral-500">Tanggal Sesi Antrean</span>
                        <span class="font-medium text-neutral-800">
                            {{ $ticket->dailyQueue?->queue_date ? \Carbon\Carbon::parse($ticket->dailyQueue->queue_date)->format('d M Y') : '—' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Status Transition Timeline (M8-24) --}}
        <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden shadow-xs">
            <div class="p-4 sm:p-5 border-b border-neutral-200">
                <h3 class="text-h3 font-bold text-neutral-900">Kronologi Transisi Status Tiket</h3>
                <p class="text-caption text-neutral-500 mt-0.5">Rekam jejak perubahan siklus hidup tiket dari awal pendaftaran hingga selesai/batal.</p>
            </div>

            <div class="p-4 sm:p-6">
                @if ($ticket->statusHistories->isNotEmpty())
                    <ol class="relative ml-3 space-y-4 border-l-2 border-neutral-200 sm:space-y-5">
                        @foreach ($ticket->statusHistories as $history)
                            <li class="relative pl-6">
                                <span class="absolute -left-[13px] top-3 flex size-6 items-center justify-center rounded-full bg-brand-100 text-[10px] font-extrabold text-brand-700 ring-4 ring-white tabular-nums">{{ $loop->iteration }}</span>

                                <div class="rounded-lg border border-neutral-200 bg-neutral-50/75 p-3.5 text-sm">
                                    <div class="mb-1.5 flex flex-col justify-between gap-1.5 sm:flex-row sm:items-center">
                                        <div class="flex flex-wrap items-center gap-2">
                                            @if ($history->from_status)
                                                <x-status-badge :status="$history->from_status" size="sm" />
                                            @else
                                                <span class="rounded bg-neutral-200 px-2 py-0.5 text-xs font-semibold text-neutral-700">Dibuat</span>
                                            @endif
                                            <svg class="size-4 text-neutral-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-label="menjadi"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                                            <x-status-badge :status="$history->to_status" size="sm" />
                                        </div>

                                        <time class="text-caption text-neutral-400 tabular-nums" @if ($history->created_at) datetime="{{ $history->created_at->toIso8601String() }}" @endif>
                                            {{ $history->created_at ? $history->created_at->copy()->timezone($timezone)->format('d M Y, H:i:s').' '.$timezoneLabel : '—' }}
                                        </time>
                                    </div>

                                    <div class="mt-2 flex flex-col justify-between gap-1 border-t border-neutral-200/60 pt-2 text-xs text-neutral-600 sm:flex-row sm:items-center">
                                        <div>
                                            <span class="text-neutral-400">Dilakukan oleh:</span>
                                            <strong class="text-neutral-800">{{ $history->actor?->name ?? 'Sistem / Pelanggan' }}</strong>
                                        </div>

                                        @if ($history->note ?? $history->notes ?? null)
                                            <div>
                                                <span class="text-neutral-400">Catatan:</span>
                                                <span class="italic text-neutral-700">{{ $history->note ?? $history->notes }}</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                @else
                    <div class="py-8 text-center text-caption text-neutral-400">
                        Belum ada riwayat transisi status tercatat untuk tiket ini.
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-layouts.admin>

