@php
    use App\Http\Controllers\Admin\QueueMonitoringController;
    use App\Models\QueueTicket;

    $filterQuery = array_filter(['search' => $search, 'business_id' => $businessId, 'source' => $source, 'date' => $date]);

    /* Status tabs follow QueueTicket::statusPresentation(); "dilewati" stats already include "tidak hadir". */
    $statusTabs = collect([null => ['label' => 'Semua', 'count' => $stats['total']]])
        ->union(collect(QueueTicket::statusPresentation())->except(QueueTicket::STATUS_TIDAK_HADIR)->map(fn (array $presentation, string $key): array => [
            'label' => $presentation['label'],
            'count' => $stats[$key] ?? null,
        ]))
        ->map(fn (array $tab, string $key): array => [
            'label' => $tab['label'],
            'count' => $tab['count'],
            'active' => ($status ?: '') === $key,
            'href' => route('admin.queues.index', array_filter(['status' => $key ?: null, ...$filterQuery])),
        ])
        ->values()
        ->all();

    $runningNow = $stats['menunggu'] + $stats['dipanggil'] + $stats['sedang_dilayani'];

    $sourceLabels = [
        QueueTicket::SOURCE_ONLINE => 'Dari ponsel',
        QueueTicket::SOURCE_WALK_IN => 'Datang langsung',
    ];

    $hasFilters = $filterQuery !== [];
@endphp

<x-layouts.admin
    title="Pemantauan Antrean Platform"
    heading="Pemantauan Antrean"
    description="Pantau tiket antrean di semua UMKM. Halaman ini hanya untuk melihat; pemanggilan tiket tetap dilakukan oleh UMKM."
>
    <div class="space-y-5">
        <section aria-labelledby="queue-kpi-heading" class="space-y-3">
            <h2 id="queue-kpi-heading" class="sr-only">Ringkasan antrean</h2>
            <div class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-3">
                <x-stat-tile label="Sedang berjalan" :value="number_format($runningNow)" tone="warning" icon="clock" :emphasis="true" class="col-span-2 lg:col-span-1" />
                <x-stat-tile label="Selesai dilayani" :value="number_format($stats['selesai'])" tone="success" icon="check" />
                <x-stat-tile label="Total tiket" :value="number_format($stats['total'])" hint="Seluruh UMKM" icon="ticket" />
            </div>
        </section>

        @include('admin.partials.filter-tabs', ['tabs' => $statusTabs, 'label' => 'Filter status tiket'])

        <form method="GET" action="{{ route('admin.queues.index') }}" class="grid grid-cols-1 gap-3 rounded-xl border border-neutral-200/80 bg-white p-3 shadow-card sm:grid-cols-2 sm:p-4 lg:grid-cols-[minmax(0,1.6fr)_minmax(0,1fr)_minmax(0,0.8fr)_minmax(0,0.8fr)_auto] lg:items-end">
            @if ($status)
                <input type="hidden" name="status" value="{{ $status }}">
            @endif

            <div class="sm:col-span-2 lg:col-span-1">
                <label for="queue-search" class="mb-1.5 block text-sm font-medium text-neutral-800">Cari tiket</label>
                <x-input id="queue-search" type="search" name="search" :value="$search" placeholder="Tiket, pelanggan, UMKM">
                    <x-slot:icon>
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </x-slot:icon>
                </x-input>
            </div>

            <div>
                <label for="queue-business" class="mb-1.5 block text-sm font-medium text-neutral-800">UMKM</label>
                <x-select id="queue-business" name="business_id">
                    <option value="">Semua UMKM</option>
                    @foreach ($businesses as $b)
                        <option value="{{ $b->id }}" @selected($businessId == $b->id)>{{ $b->name }}</option>
                    @endforeach
                </x-select>
            </div>

            <div>
                <label for="queue-source" class="mb-1.5 block text-sm font-medium text-neutral-800">Asal tiket</label>
                <x-select id="queue-source" name="source">
                    <option value="">Semua</option>
                    @foreach ($sourceLabels as $value => $label)
                        <option value="{{ $value }}" @selected($source === $value)>{{ $label }}</option>
                    @endforeach
                </x-select>
            </div>

            <div>
                <label for="queue-date" class="mb-1.5 block text-sm font-medium text-neutral-800">Tanggal</label>
                <x-input id="queue-date" type="date" name="date" :value="$date" />
            </div>

            <div class="flex gap-2 *:flex-1 sm:col-span-2 lg:col-span-1 sm:*:flex-none">
                <x-button type="submit">Terapkan</x-button>
                @if ($hasFilters)
                    <x-button variant="ghost" :href="route('admin.queues.index', array_filter(['status' => $status]))">Hapus filter</x-button>
                @endif
            </div>
        </form>

        <p class="text-sm text-neutral-600" aria-live="polite">
            Menampilkan <strong class="font-semibold tabular-nums text-neutral-900">{{ number_format($tickets->total()) }}</strong> tiket. Nomor telepon pelanggan disamarkan.
        </p>

        @if ($tickets->isEmpty())
            <x-empty-state title="Tidak ada tiket ditemukan" description="Coba ubah status, UMKM, asal tiket, atau tanggal.">
                @if ($hasFilters || $status)
                    <x-button variant="outline" :href="route('admin.queues.index')">Tampilkan semua tiket</x-button>
                @endif
            </x-empty-state>
        @else
            <div class="hidden md:block">
                <x-table>
                    <x-slot:header>
                        <tr>
                            <x-table.th>Tiket</x-table.th>
                            <x-table.th>UMKM &amp; layanan</x-table.th>
                            <x-table.th>Pelanggan</x-table.th>
                            <x-table.th>Status</x-table.th>
                            <x-table.th>Dibuat</x-table.th>
                            <x-table.th align="right">Aksi</x-table.th>
                        </tr>
                    </x-slot:header>

                    @foreach ($tickets as $ticket)
                        <tr>
                            <x-table.td>
                                <span class="font-semibold tracking-tight text-neutral-950">{{ $ticket->ticket_number }}</span>
                                <p class="text-xs text-neutral-600">{{ $sourceLabels[$ticket->source] ?? $ticket->source }}</p>
                            </x-table.td>
                            <x-table.td class="whitespace-normal! text-sm">
                                @if ($ticket->business)
                                    <a href="{{ route('admin.businesses.show', $ticket->business) }}" class="font-medium text-neutral-900 hover:text-brand-700 hover:underline">{{ $ticket->business->name }}</a>
                                @else
                                    <span class="text-neutral-500">UMKM tidak ditemukan</span>
                                @endif
                                <p class="text-xs text-neutral-600">{{ $ticket->service?->name ?? 'Layanan dihapus' }}</p>
                            </x-table.td>
                            <x-table.td class="text-sm">
                                <p class="font-medium text-neutral-900">{{ $ticket->customer_name }}</p>
                                <p class="text-xs text-neutral-600">{{ QueueMonitoringController::maskPhoneNumber($ticket->customer_phone) }}</p>
                            </x-table.td>
                            <x-table.td><x-status-badge :status="$ticket" size="sm" /></x-table.td>
                            <x-table.td class="text-sm text-neutral-600">{{ $ticket->created_at->translatedFormat('d M, H:i') }}</x-table.td>
                            <x-table.td align="right">
                                <x-button variant="ghost" size="sm" :href="route('admin.queues.show', $ticket)" aria-label="Lihat detail tiket {{ $ticket->ticket_number }}">Detail</x-button>
                            </x-table.td>
                        </tr>
                    @endforeach

                    @if ($tickets->hasPages())
                        <x-slot:footer>
                            <x-pagination :paginator="$tickets" />
                        </x-slot:footer>
                    @endif
                </x-table>
            </div>

            <div class="md:hidden">
                <x-card padding="none">
                    <ul class="divide-y divide-neutral-100">
                        @foreach ($tickets as $ticket)
                            <li>
                                <a href="{{ route('admin.queues.show', $ticket) }}" class="flex items-start justify-between gap-3 p-4 transition-colors hover:bg-neutral-50">
                                    <div class="min-w-0">
                                        <p class="font-semibold text-neutral-950">{{ $ticket->ticket_number }} <span class="font-normal text-neutral-600">{{ $ticket->customer_name }}</span></p>
                                        <p class="text-sm text-neutral-700">{{ $ticket->business?->name ?? 'UMKM tidak ditemukan' }}</p>
                                        <p class="text-xs text-neutral-600">{{ $ticket->service?->name ?? 'Layanan dihapus' }}, {{ $ticket->created_at->translatedFormat('d M, H:i') }}</p>
                                        <p class="text-xs text-neutral-600">{{ QueueMonitoringController::maskPhoneNumber($ticket->customer_phone) }}</p>
                                    </div>
                                    <x-status-badge :status="$ticket" size="sm" class="shrink-0" />
                                </a>
                            </li>
                        @endforeach
                    </ul>
                    @if ($tickets->hasPages())
                        <x-slot:footer>
                            <x-pagination :paginator="$tickets" />
                        </x-slot:footer>
                    @endif
                </x-card>
            </div>
        @endif
    </div>
</x-layouts.admin>
