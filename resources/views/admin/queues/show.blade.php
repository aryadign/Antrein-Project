@php
    use App\Http\Controllers\Admin\QueueMonitoringController;
    use Illuminate\Support\Carbon;

    $timezone = $ticket->business?->timezone ?: config('app.timezone');
    $timezoneLabel = match ($timezone) {
        'Asia/Jakarta' => 'WIB',
        'Asia/Jayapura' => 'WIT',
        default => 'WITA',
    };

    $customerFields = [
        ['label' => 'Nama', 'value' => $ticket->customer_name],
        ['label' => 'Telepon (disamarkan)', 'value' => QueueMonitoringController::maskPhoneNumber($ticket->customer_phone)],
        ['label' => 'Asal tiket', 'value' => $ticket->source === 'online' ? 'Dari ponsel' : 'Datang langsung'],
        ['label' => 'Catatan', 'value' => $ticket->notes ?: 'Tidak ada catatan'],
        ['label' => 'Perkiraan dilayani', 'value' => $ticket->estimated_service_time ? Carbon::parse($ticket->estimated_service_time)->timezone($timezone)->format('H:i').' '.$timezoneLabel : 'Belum tersedia'],
    ];

    $serviceFields = [
        ['label' => 'Kategori', 'value' => $ticket->business?->category?->name ?? 'Tanpa kategori'],
        ['label' => 'Layanan', 'value' => $ticket->service?->name ?? 'Layanan dihapus'],
        ['label' => 'Durasi layanan', 'value' => $ticket->service?->duration_minutes ? 'Sekitar '.$ticket->service->duration_minutes.' menit' : 'Tidak diketahui'],
        ['label' => 'Tanggal antrean', 'value' => $ticket->dailyQueue?->queue_date ? Carbon::parse($ticket->dailyQueue->queue_date)->translatedFormat('d M Y') : 'Tidak diketahui'],
    ];
@endphp

<x-layouts.admin
    :title="'Pemantauan Tiket ' . $ticket->ticket_number"
    :heading="'Tiket ' . $ticket->ticket_number"
    description="Detail tiket dan riwayat perubahan statusnya."
>
    <div class="mx-auto max-w-5xl space-y-6">
        <div class="-my-2 flex flex-wrap items-center justify-between gap-2">
            <a href="{{ route('admin.queues.index') }}" class="inline-flex min-h-11 items-center gap-2 text-sm font-medium text-neutral-600 transition-colors hover:text-neutral-900">
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Kembali ke monitoring antrean
            </a>
            <span class="inline-flex items-center gap-1.5 rounded-full border border-neutral-200 bg-white px-3 py-1 text-xs font-medium text-neutral-700 shadow-card" title="Admin hanya dapat melihat. Memanggil, melayani, melewati, dan membatalkan tiket dilakukan oleh UMKM.">
                <svg class="size-3.5 text-neutral-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                Mode pemantauan, hanya baca
            </span>
        </div>

        {{-- Ticket summary --}}
        <x-card padding="sm">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                <div class="flex h-16 min-w-16 shrink-0 items-center justify-center rounded-xl border border-brand-200 bg-brand-50 px-4 text-2xl font-bold tabular-nums text-brand-800 shadow-raised">
                    {{ $ticket->ticket_number }}
                </div>
                <div class="min-w-0 space-y-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <x-status-badge :status="$ticket" />
                        <span class="text-sm text-neutral-600">{{ $ticket->source === 'online' ? 'Dari ponsel' : 'Datang langsung' }}</span>
                    </div>
                    <p class="text-sm text-neutral-600">
                        @if ($ticket->business)
                            <a href="{{ route('admin.businesses.show', $ticket->business) }}" class="font-semibold text-neutral-900 hover:text-brand-700 hover:underline">{{ $ticket->business->name }}</a>,
                        @endif
                        dibuat <span class="tabular-nums">{{ $ticket->created_at->copy()->timezone($timezone)->translatedFormat('d M Y, H:i') }} {{ $timezoneLabel }}</span>
                    </p>
                </div>
            </div>
        </x-card>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            @foreach ([['title' => 'Pelanggan', 'fields' => $customerFields], ['title' => 'Layanan', 'fields' => $serviceFields]] as $section)
                <x-card padding="none">
                    <x-slot:header>
                        <h2 class="text-h3 font-semibold text-neutral-900">{{ $section['title'] }}</h2>
                    </x-slot:header>
                    <dl class="divide-y divide-neutral-100 px-5 sm:px-6">
                        @foreach ($section['fields'] as $field)
                            <div class="flex items-start justify-between gap-4 py-3 text-sm">
                                <dt class="shrink-0 text-neutral-600">{{ $field['label'] }}</dt>
                                <dd class="min-w-0 text-right font-medium tabular-nums text-neutral-900">{{ $field['value'] }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </x-card>
            @endforeach
        </div>

        {{-- Status history --}}
        <x-card padding="none">
            <x-slot:header>
                <h2 class="text-h3 font-semibold text-neutral-900">Riwayat status</h2>
                <p class="text-sm text-neutral-600">Setiap perubahan status sejak tiket dibuat.</p>
            </x-slot:header>

            @if ($ticket->statusHistories->isEmpty())
                <p class="px-5 py-8 text-center text-sm text-neutral-600">Belum ada perubahan status untuk tiket ini.</p>
            @else
                <ol class="divide-y divide-neutral-100">
                    @foreach ($ticket->statusHistories as $history)
                        <li class="flex flex-col gap-2 px-5 py-3.5 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                            <div class="min-w-0 space-y-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    @if ($history->from_status)
                                        <x-status-badge :status="$history->from_status" size="sm" />
                                    @else
                                        <span class="rounded-full border border-neutral-200 bg-neutral-100 px-2 py-0.5 text-xs font-medium text-neutral-700">Dibuat</span>
                                    @endif
                                    <svg class="size-4 text-neutral-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" role="img" aria-label="menjadi"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                                    <x-status-badge :status="$history->to_status" size="sm" />
                                </div>
                                <p class="text-xs text-neutral-600">
                                    Oleh {{ $history->actor?->name ?? 'sistem atau pelanggan' }}
                                    @if ($history->note ?? $history->notes ?? null)
                                        <span class="text-neutral-800">&ndash; {{ $history->note ?? $history->notes }}</span>
                                    @endif
                                </p>
                            </div>
                            <time class="shrink-0 text-xs tabular-nums text-neutral-600" @if ($history->created_at) datetime="{{ $history->created_at->toIso8601String() }}" @endif>
                                {{ $history->created_at ? $history->created_at->copy()->timezone($timezone)->translatedFormat('d M Y, H:i:s').' '.$timezoneLabel : 'Waktu tidak tercatat' }}
                            </time>
                        </li>
                    @endforeach
                </ol>
            @endif
        </x-card>
    </div>
</x-layouts.admin>
