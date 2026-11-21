<x-layouts.customer title="Riwayat Antrean" activeNav="riwayat">
    @php
        $filters = ['semua' => 'Semua', 'selesai' => 'Selesai', 'dibatalkan' => 'Dibatalkan'];
        $hasHistory = $historyByMonth->isNotEmpty();
    @endphp

    <div x-data="{ filter: 'semua' }">
        {{-- Judul layar --}}
        <div class="flex flex-col gap-1 px-5 pt-8 pb-5 md:px-0 md:pt-2 md:pb-8">
            <h1 class="text-2xl font-bold leading-8 text-gray-900 md:text-3xl md:leading-10">Riwayat Antrean</h1>
            <p class="text-sm leading-5 text-pretty text-gray-600">
                {{ $hasHistory ? 'Catatan antrean yang pernah Anda ambil.' : 'Semua perjalanan antrean Anda, di satu tempat.' }}
            </p>
        </div>

        <div class="flex flex-col gap-4 px-5 pb-5 md:px-0">
            {{-- Filter status --}}
            <x-customer.filter-tabs :options="$filters" model="filter" label="Filter status riwayat" />

            @if ($hasHistory)
                @foreach ($historyByMonth as $month => $tickets)
                    @php($statusGroups = $tickets->map(fn ($ticket) => $ticket->status === \App\Models\QueueTicket::STATUS_SELESAI ? 'selesai' : 'dibatalkan'))
                    <div
                        class="flex flex-col gap-4"
                        x-show="filter === 'semua' || @js($statusGroups->unique()->values()).includes(filter)"
                        x-transition.opacity.duration.200ms
                    >
                        <h2 class="text-base font-bold leading-6 text-gray-900">{{ $month }}</h2>
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        @foreach ($tickets as $index => $ticket)
                            <x-customer.history-card
                                :ticket="$ticket"
                                x-show="filter === 'semua' || filter === {{ Js::from($statusGroups[$index]) }}"
                                x-transition.opacity.duration.200ms
                            />
                        @endforeach
                        </div>
                    </div>
                @endforeach

                <div
                    x-show="filter !== 'semua' && ! @js($historyByMonth->flatten()->map(fn ($ticket) => $ticket->status === \App\Models\QueueTicket::STATUS_SELESAI ? 'selesai' : 'dibatalkan')->unique()->values()).includes(filter)"
                    x-cloak
                    class="rounded-2xl border border-gray-200 px-5 py-7 text-center text-sm leading-5 text-gray-700"
                >
                    Belum ada antrean dengan status ini.
                </div>

                <div class="flex flex-col gap-1 rounded-xl bg-brand-50 p-3 text-xs leading-4">
                    <p class="font-semibold text-brand-700">Hanya antrean milik Anda</p>
                    <p class="text-gray-700">Tiket aktif ada di Antrean Saya. Riwayat ini tidak menampilkan data pelanggan lain.</p>
                </div>
            @else
                <h2 class="text-base font-bold leading-6 text-gray-900">Riwayat Anda</h2>

                {{-- Keadaan layanan: riwayat kosong --}}
                <div class="flex flex-col items-center gap-[18px] rounded-2xl border border-gray-200 px-5 py-7 text-center md:py-12">
                    <div class="flex size-[72px] items-center justify-center rounded-full bg-brand-100">
                        <img src="{{ asset('images/figma/history-lg.svg') }}" alt="" width="32" height="32" class="size-8" />
                    </div>
                    <p class="text-xl font-bold leading-7 text-gray-900">Belum ada riwayat antrean</p>
                    <p class="text-sm leading-5 text-gray-700">Antrean yang sudah selesai atau Anda batalkan akan muncul di sini. Yuk, temukan layanan pertama Anda.</p>
                    <a href="{{ route('customer.search') }}" class="flex h-12 w-full items-center justify-center rounded-xl bg-brand-700 text-sm font-semibold text-white transition hover:bg-blue-900 active:scale-[0.99] md:w-64">
                        Jelajahi Layanan
                    </a>
                </div>

                <a href="{{ route('customer.my-tickets') }}" class="flex flex-col gap-1 rounded-xl bg-brand-50 p-3 text-xs leading-4 transition hover:bg-brand-100">
                    <p class="font-semibold text-brand-700">Sedang menunggu giliran?</p>
                    <p class="text-gray-700">Buka Antrean Saya untuk melihat tiket yang masih aktif.</p>
                </a>
            @endif
        </div>
    </div>
</x-layouts.customer>
