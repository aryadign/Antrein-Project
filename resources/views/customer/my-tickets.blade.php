<x-layouts.customer title="Antrean Saya" activeNav="antrean">
    @php
        $completedTickets = $finishedTickets->where('status', \App\Models\QueueTicket::STATUS_SELESAI);
        $cancelledTickets = $finishedTickets->where('status', '!=', \App\Models\QueueTicket::STATUS_SELESAI);
        $tabs = ['aktif' => 'Aktif', 'selesai' => 'Selesai', 'dibatalkan' => 'Dibatalkan'];
        $tabOptions = [
            'aktif' => ['label' => 'Aktif', 'count' => $activeTickets->count()],
            'selesai' => ['label' => 'Selesai', 'count' => $completedTickets->count()],
            'dibatalkan' => ['label' => 'Dibatalkan', 'count' => $cancelledTickets->count()],
        ];
    @endphp

    <div x-data="{ tab: 'aktif' }" class="flex min-h-[calc(100vh-105px)] md:min-h-0 flex-col">
        {{-- Judul layar --}}
        <div class="flex flex-col gap-1 px-5 pt-8 pb-5 md:px-0 md:pt-2 md:pb-8">
            <h1 class="text-2xl font-bold leading-8 text-gray-900 md:text-3xl md:leading-10">Antrean Saya</h1>
            <p class="text-sm leading-5 text-pretty text-gray-600">Pantau giliran dan tiket milik Anda.</p>
        </div>

        <div class="flex flex-1 flex-col justify-between gap-6 px-5 pb-5 md:px-0">
            <div class="flex flex-col gap-4">
                {{-- Filter status --}}
                <x-customer.filter-tabs :options="$tabOptions" model="tab" label="Filter status tiket" />

                {{-- Aktif --}}
                <div x-show="tab === 'aktif'" x-transition.opacity.duration.200ms class="flex flex-col gap-4">
                    <div class="flex items-start justify-between">
                        <h2 class="text-base font-bold leading-6 text-gray-900">Tiket hari ini</h2>
                        <p class="rounded-full bg-brand-50 px-2.5 py-0.5 text-xs font-bold leading-4 text-brand-700 tabular-nums">{{ $activeTickets->count() }} / {{ $maxActiveTickets ?? 3 }} tiket aktif</p>
                    </div>

                    @if ($activeTickets->count() >= ($maxActiveTickets ?? 3))
                        <div class="rounded-xl border border-amber-200 bg-amber-50 p-3 text-xs leading-4 text-amber-900">
                            Anda telah mencapai batas maksimal <strong>{{ $maxActiveTickets ?? 3 }} antrean aktif</strong> sekaligus. Selesaikan atau batalkan salah satu tiket sebelum mengambil antrean baru.
                        </div>
                    @endif

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 md:gap-6">
                    @forelse ($activeTickets as $ticket)
                        <x-customer.ticket-card :ticket="$ticket" />
                    @empty
                        <div class="flex flex-col items-center gap-[18px] rounded-2xl md:col-span-2 border border-gray-200 px-5 py-7 text-center">
                            <div class="flex size-[72px] items-center justify-center rounded-full bg-brand-100">
                                <img src="{{ asset('images/figma/nav-ticket-active.svg') }}" alt="" width="22" height="22" class="size-8" />
                            </div>
                            <p class="text-xl font-bold leading-7 text-gray-900">Belum ada antrean aktif</p>
                            <p class="text-sm leading-5 text-gray-700">Ambil antrean di toko pilihan Anda, tiketnya akan muncul di sini.</p>
                        </div>
                    @endforelse
                    </div>

                    @if ($activeTickets->isNotEmpty())
                        <div class="flex flex-col gap-1 rounded-xl bg-brand-50 p-3 text-xs leading-4">
                            <p class="font-semibold text-brand-700">Kami ingatkan saat giliran mendekat</p>
                            <p class="text-gray-700">Pastikan notifikasi pribadi Anda aktif dan tetap berada di sekitar kedai.</p>
                        </div>
                    @endif

                    <a href="{{ route('customer.history') }}" class="flex items-center gap-3 rounded-2xl bg-gray-100 p-4 transition hover:bg-gray-200">
                        <img src="{{ asset('images/figma/history.svg') }}" alt="" width="20" height="20" class="size-5" />
                        <div class="flex flex-1 flex-col gap-[3px]">
                            <p class="text-xs leading-4 text-gray-700 md:text-sm md:leading-5">Antrean sebelumnya</p>
                            <p class="text-sm font-semibold leading-5 text-gray-900">Lihat riwayat antrean Anda</p>
                        </div>
                    </a>
                </div>

                {{-- Selesai & Dibatalkan --}}
                @foreach (['selesai' => $completedTickets, 'dibatalkan' => $cancelledTickets] as $key => $tickets)
                    <div x-show="tab === @js($key)" x-cloak x-transition.opacity.duration.200ms class="flex flex-col gap-4">
                        <div class="flex items-start justify-between">
                            <h2 class="text-base font-bold leading-6 text-gray-900">Tiket {{ Str::lower($tabs[$key]) }}</h2>
                            <p class="text-xs leading-4 text-gray-700">{{ $tickets->count() }} tiket</p>
                        </div>
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        @forelse ($tickets as $ticket)
                            <x-customer.history-card :ticket="$ticket" />
                        @empty
                            <div class="md:col-span-2 rounded-2xl border border-gray-200 px-5 py-7 text-center text-sm leading-5 text-gray-700">
                                Belum ada tiket {{ Str::lower($tabs[$key]) }}.
                            </div>
                        @endforelse
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Aksi utama --}}
            <a href="{{ route('customer.search') }}" class="flex h-12 items-center justify-center rounded-xl border md:w-64 md:self-start border-gray-200 bg-white text-sm font-semibold text-brand-700 transition hover:bg-gray-50 active:scale-[0.99]">
                Jelajahi Layanan
            </a>
        </div>
    </div>
</x-layouts.customer>
