@php
    $activeCounters = $counters->where('is_active', true);
    $busyCountersCount = $activeCounters->filter(fn ($counter): bool => $counter->activeTicket !== null)->count();
    $idleCountersCount = max(0, $activeCounters->count() - $busyCountersCount);
    $counterRows = $counters->map(function ($counter) use ($activeCounters, $counters): array {
        $hasActiveTicket = $counter->activeTicket !== null;

        return [
            'counter' => $counter,
            'activeTicket' => $counter->activeTicket,
            'canDeactivate' => $counter->is_active && ! $hasActiveTicket && $activeCounters->count() > 1,
            'canDelete' => ! $hasActiveTicket && $counters->count() > 1,
            'deactivateBlockedReason' => $hasActiveTicket ? 'Tidak dapat dinonaktifkan: ada tiket aktif.' : 'Tidak dapat dinonaktifkan: minimal satu loket aktif diperlukan.',
        ];
    });
@endphp

<x-layouts.merchant title="Kelola Loket Pelayanan">
    <div class="space-y-5 sm:space-y-6">
        {{-- Header --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="min-w-0">
                <h1 class="text-h2 font-bold text-neutral-950">Kelola Loket Pelayanan</h1>
                <p class="mt-1 text-body text-neutral-600">
                    Atur loket operasional, kode pemanggil, dan penugasan operator untuk melayani antrean secara paralel.
                </p>
            </div>
            <button
                type="button"
                onclick="document.getElementById('modal-add-counter').showModal()"
                class="inline-flex h-11 w-full shrink-0 items-center justify-center gap-2 rounded-lg bg-brand-700 px-4 text-sm font-bold text-white shadow-sm transition hover:bg-brand-800 sm:w-auto"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                Tambah Loket
            </button>
        </div>

        {{-- Notifications --}}
        @if (session('status'))
            <div class="flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-800" role="status">
                <svg class="mt-0.5 w-5 h-5 shrink-0 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <p class="flex-1">{{ session('status') }}</p>
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800" role="alert">
                <p class="mb-1 font-semibold">Gagal memproses permohonan:</p>
                <ul class="list-inside list-disc space-y-0.5 text-xs">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Mode Operasional Station / Loket --}}
        <section class="rounded-2xl border border-neutral-200 bg-white p-4 shadow-xs sm:p-5" aria-labelledby="station-mode-heading">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 id="station-mode-heading" class="text-sm font-bold text-neutral-950">Mode Operasional Station / Loket</h2>
                        <span @class([
                            'inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-bold',
                            'bg-brand-100 text-brand-800' => $business->usesStations(),
                            'bg-slate-100 text-slate-800' => ! $business->usesStations(),
                        ])>
                            {{ $business->usesStations() ? 'Multi-Station Aktif' : 'Mode 1 Station Utama' }}
                        </span>
                    </div>
                    <p class="mt-1 text-xs text-neutral-600">
                        Pilih apakah usaha Anda menggunakan banyak station/loket paralel (mis. Kursi 1, Kursi 2) atau cukup 1 antrean tunggal tanpa pembagian multi-station.
                    </p>
                </div>

                <div class="grid grid-cols-1 gap-2 sm:flex sm:shrink-0">
                    <form method="POST" action="{{ route('merchant.counters.toggle-station-mode') }}">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="use_stations" value="1">
                        <button
                            type="submit"
                            @class([
                                'inline-flex h-10 w-full items-center justify-center gap-1.5 rounded-xl border px-3.5 text-xs font-bold transition sm:w-auto',
                                'border-brand-700 bg-brand-700 text-white shadow-xs' => $business->usesStations(),
                                'border-neutral-300 bg-white text-neutral-700 hover:bg-neutral-50' => ! $business->usesStations(),
                            ])
                        >
                            Multi-Station
                        </button>
                    </form>
                    <form method="POST" action="{{ route('merchant.counters.toggle-station-mode') }}">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="use_stations" value="0">
                        <button
                            type="submit"
                            @class([
                                'inline-flex h-10 w-full items-center justify-center gap-1.5 rounded-xl border px-3.5 text-xs font-bold transition sm:w-auto',
                                'border-slate-900 bg-slate-900 text-white shadow-xs' => ! $business->usesStations(),
                                'border-neutral-300 bg-white text-neutral-700 hover:bg-neutral-50' => $business->usesStations(),
                            ])
                        >
                            1 Station (Tanpa Multi-Station)
                        </button>
                    </form>
                </div>
            </div>

            @unless ($business->usesStations())
                <div class="mt-3 flex items-start gap-2.5 rounded-xl border border-amber-200 bg-amber-50/70 p-3 text-xs text-amber-900" role="status">
                    <svg class="mt-0.5 size-4 shrink-0 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <p>
                        <strong>Mode 1 Station Aktif:</strong> Estimasi waktu tunggu pelanggan, kapasitas slot per jam, meja operator, dan layar TV publik otomatis menghitung kapasitas sebagai <strong>1 station utama</strong> meskipun ada lebih dari satu loket tersimpan di bawah.
                    </p>
                </div>
            @endunless
        </section>

        {{-- Stats --}}
        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            <x-stat-tile label="Total Loket" :value="$counters->count()" hint="Semua loket terdaftar" icon="counter" />
            <x-stat-tile label="Loket Aktif" :value="$business->usesStations() ? $activeCounters->count() : min(1, $activeCounters->count())" :hint="$business->usesStations() ? 'Siap operasional' : '1 station utama aktif'" tone="success" icon="check" />
            <x-stat-tile label="Sedang Melayani" :value="$busyCountersCount" hint="Ada tiket aktif" tone="brand" icon="activity" />
            <x-stat-tile label="Loket Tersedia" :value="$business->usesStations() ? $idleCountersCount : max(0, min(1, $activeCounters->count()) - min(1, $busyCountersCount))" hint="Menunggu pemanggilan" tone="muted" icon="clock" />
        </div>

        {{-- Counter List --}}
        <section class="space-y-3">
            <div>
                <h2 class="text-h3 font-bold text-neutral-900">Daftar Loket Usaha</h2>
                <p class="mt-0.5 text-xs text-neutral-500">Semua loket melayani antrean terpadu (FIFO global) dari antrean harian usaha.</p>
            </div>

            @if ($counters->isEmpty())
                <x-empty-state title="Belum ada loket yang terdaftar." description="Tambahkan loket pertama untuk mulai melayani antrean." />
            @else
                {{-- Mobile: card list --}}
                <ul class="space-y-3 md:hidden" aria-label="Daftar loket">
                    @foreach ($counterRows as $row)
                        @php
                            $counter = $row['counter'];
                            $activeTicket = $row['activeTicket'];
                        @endphp
                        <li class="rounded-xl border border-neutral-200 bg-white p-4 shadow-xs">
                            <div class="flex items-start gap-3">
                                <span class="flex size-11 shrink-0 items-center justify-center rounded-lg font-mono text-xs font-bold {{ $counter->is_active ? 'bg-brand-100 text-brand-700' : 'bg-neutral-100 text-neutral-400' }}">
                                    {{ $counter->code }}
                                </span>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-start justify-between gap-2">
                                        <p class="min-w-0 truncate font-bold text-neutral-900">{{ $counter->name }}</p>
                                        <x-status-badge :status="$counter->is_active ? 'aktif' : 'nonaktif'" size="sm" />
                                    </div>
                                    @if ($counter->description)
                                        <p class="mt-0.5 line-clamp-2 text-xs text-neutral-500">{{ $counter->description }}</p>
                                    @endif
                                </div>
                            </div>

                            <div class="mt-3 rounded-lg bg-neutral-50 px-3 py-2 text-xs">
                                <p class="font-semibold text-neutral-500">Tiket aktif</p>
                                @if ($activeTicket)
                                    <p class="mt-0.5 flex items-center gap-2 font-bold text-brand-900">
                                        <span class="font-mono tabular-nums">{{ $activeTicket->formatted_ticket_number }}</span>
                                        <x-status-badge :status="$activeTicket->status" size="sm" />
                                    </p>
                                @else
                                    <p class="mt-0.5 text-neutral-500">Kosong / Siap</p>
                                @endif
                            </div>

                            <div class="mt-3">
                                <p class="mb-1.5 text-xs font-semibold text-neutral-500">Operator ditugaskan</p>
                                <div class="flex flex-wrap items-center gap-1.5">
                                    @forelse ($counter->assignedMemberships as $membership)
                                        <span class="inline-flex items-center gap-0.5 rounded-lg border border-neutral-200 bg-neutral-100 py-0.5 pl-2.5 text-xs text-neutral-700">
                                            <span class="max-w-40 truncate">{{ $membership->user->name }}</span>
                                            <form action="{{ route('merchant.counters.unassign-operator', [$counter, $membership]) }}" method="POST" onsubmit="return confirm('Hapus penugasan staf ini dari loket?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="inline-flex size-9 items-center justify-center rounded-md text-neutral-400 hover:text-rose-600" aria-label="Hapus {{ $membership->user->name }} dari {{ $counter->name }}">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                </button>
                                            </form>
                                        </span>
                                    @empty
                                        <span class="text-xs italic text-neutral-400">Hanya Pemilik Usaha</span>
                                    @endforelse
                                </div>
                            </div>

                            <div class="mt-3 grid grid-cols-2 gap-2">
                                <button type="button" onclick="openAssignModal({{ $counter->id }}, @js($counter->name))" class="inline-flex h-11 items-center justify-center rounded-lg border border-brand-200 bg-brand-50 px-2 text-xs font-semibold text-brand-700 transition hover:bg-brand-100">
                                    Tugaskan Operator
                                </button>
                                <button type="button" onclick="openEditModal({{ $counter->id }}, @js($counter->name), @js($counter->code), @js($counter->description ?? ''))" class="inline-flex h-11 items-center justify-center rounded-lg border border-neutral-300 px-2 text-xs font-semibold text-neutral-700 transition hover:bg-neutral-100">
                                    Edit
                                </button>
                                <form action="{{ route('merchant.counters.toggle-status', $counter) }}" method="POST" class="{{ $row['canDelete'] ? '' : 'col-span-2' }}">
                                    @csrf
                                    @method('PATCH')
                                    @if ($counter->is_active)
                                        <button
                                            type="submit"
                                            @disabled(! $row['canDeactivate'])
                                            @if (! $row['canDeactivate']) title="{{ $row['deactivateBlockedReason'] }}" @endif
                                            onclick="return confirm(@js('Nonaktifkan '.$counter->name.'? Loket tidak akan menerima antrean baru.'))"
                                            class="inline-flex h-11 w-full items-center justify-center rounded-lg border border-amber-200 px-2 text-xs font-semibold text-amber-700 transition hover:bg-amber-50 disabled:cursor-not-allowed disabled:border-neutral-200 disabled:text-neutral-300 disabled:hover:bg-transparent"
                                        >
                                            Nonaktifkan
                                        </button>
                                    @else
                                        <button type="submit" class="inline-flex h-11 w-full items-center justify-center rounded-lg border border-emerald-300 bg-emerald-50 px-2 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-100">
                                            Aktifkan
                                        </button>
                                    @endif
                                </form>
                                @if ($row['canDelete'])
                                    <form action="{{ route('merchant.counters.destroy', $counter) }}" method="POST" onsubmit="return confirm(@js('Hapus '.$counter->name.' secara permanen?'));">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex h-11 w-full items-center justify-center rounded-lg border border-rose-200 px-2 text-xs font-semibold text-rose-700 transition hover:bg-rose-50">
                                            Hapus
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>

                {{-- Tablet & desktop: table --}}
                <div class="hidden overflow-x-auto rounded-xl border border-neutral-200 bg-white shadow-xs md:block">
                    <table class="w-full border-collapse text-left text-sm">
                        <thead>
                            <tr class="border-b border-neutral-200 bg-neutral-50/75 text-xs font-semibold uppercase tracking-wider text-neutral-500">
                                <th class="px-4 py-3.5 lg:px-6">Loket</th>
                                <th class="px-4 py-3.5">Kode</th>
                                <th class="px-4 py-3.5">Status</th>
                                <th class="px-4 py-3.5">Operator Ditugaskan</th>
                                <th class="px-4 py-3.5">Tiket Aktif</th>
                                <th class="px-4 py-3.5 pr-6 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-100">
                            @foreach ($counterRows as $row)
                                @php
                                    $counter = $row['counter'];
                                    $activeTicket = $row['activeTicket'];
                                @endphp
                                <tr class="transition-colors hover:bg-neutral-50/50">
                                    <td class="px-4 py-4 lg:px-6">
                                        <div class="flex items-center gap-3">
                                            <div class="flex size-9 shrink-0 items-center justify-center rounded-lg {{ $counter->is_active ? 'bg-brand-100 text-brand-700' : 'bg-neutral-100 text-neutral-400' }}">
                                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                            </div>
                                            <div class="min-w-0">
                                                <div class="font-semibold text-neutral-900">{{ $counter->name }}</div>
                                                @if ($counter->description)
                                                    <p class="mt-0.5 max-w-xs truncate text-xs text-neutral-500">{{ $counter->description }}</p>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 font-mono text-xs font-bold text-neutral-700">
                                        <span class="inline-flex items-center rounded border border-neutral-200 bg-neutral-100 px-2 py-0.5">{{ $counter->code }}</span>
                                    </td>
                                    <td class="px-4 py-4">
                                        <x-status-badge :status="$counter->is_active ? 'aktif' : 'nonaktif'" size="sm" />
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="flex max-w-xs flex-wrap items-center gap-1.5">
                                            @forelse ($counter->assignedMemberships as $membership)
                                                <span class="inline-flex items-center gap-0.5 rounded-md border border-neutral-200 bg-neutral-100 py-0.5 pl-2 text-xs text-neutral-700">
                                                    <span>{{ $membership->user->name }}</span>
                                                    <form action="{{ route('merchant.counters.unassign-operator', [$counter, $membership]) }}" method="POST" onsubmit="return confirm('Hapus penugasan staf ini dari loket?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="inline-flex size-6 items-center justify-center rounded text-neutral-400 hover:text-rose-600" aria-label="Hapus {{ $membership->user->name }} dari {{ $counter->name }}">
                                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                        </button>
                                                    </form>
                                                </span>
                                            @empty
                                                <span class="text-xs italic text-neutral-400">Hanya Pemilik Usaha</span>
                                            @endforelse
                                            <button type="button" onclick="openAssignModal({{ $counter->id }}, @js($counter->name))" class="inline-flex h-7 items-center rounded border border-brand-200 bg-brand-50 px-2 text-[11px] font-semibold text-brand-700 transition-colors hover:bg-brand-100">
                                                + Tugaskan
                                            </button>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4">
                                        @if ($activeTicket)
                                            <div class="inline-flex items-center gap-2 text-xs font-semibold text-brand-900">
                                                <span class="font-mono tabular-nums">{{ $activeTicket->formatted_ticket_number }}</span>
                                                <x-status-badge :status="$activeTicket->status" size="sm" />
                                            </div>
                                        @else
                                            <span class="text-xs text-neutral-400">Kosong / Siap</span>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-4 pr-6 text-right">
                                        <div class="inline-flex items-center gap-1.5">
                                            <button type="button" onclick="openEditModal({{ $counter->id }}, @js($counter->name), @js($counter->code), @js($counter->description ?? ''))" class="inline-flex h-9 items-center rounded-md px-2.5 text-xs font-semibold text-neutral-600 transition-colors hover:bg-neutral-100 hover:text-brand-700">
                                                Edit
                                            </button>
                                            <form action="{{ route('merchant.counters.toggle-status', $counter) }}" method="POST">
                                                @csrf
                                                @method('PATCH')
                                                @if ($counter->is_active)
                                                    <button
                                                        type="submit"
                                                        @disabled(! $row['canDeactivate'])
                                                        @if (! $row['canDeactivate']) title="{{ $row['deactivateBlockedReason'] }}" @endif
                                                        onclick="return confirm(@js('Nonaktifkan '.$counter->name.'? Loket tidak akan menerima antrean baru.'))"
                                                        class="inline-flex h-9 items-center rounded-md px-2.5 text-xs font-semibold text-amber-700 transition-colors hover:bg-amber-50 disabled:cursor-not-allowed disabled:text-neutral-300 disabled:hover:bg-transparent"
                                                    >
                                                        Nonaktifkan
                                                    </button>
                                                @else
                                                    <button type="submit" class="inline-flex h-9 items-center rounded-md px-2.5 text-xs font-semibold text-emerald-700 transition-colors hover:bg-emerald-50">
                                                        Aktifkan
                                                    </button>
                                                @endif
                                            </form>
                                            @if ($row['canDelete'])
                                                <form action="{{ route('merchant.counters.destroy', $counter) }}" method="POST" onsubmit="return confirm(@js('Hapus '.$counter->name.' secara permanen?'));">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="inline-flex h-9 items-center rounded-md px-2.5 text-xs font-semibold text-rose-700 transition-colors hover:bg-rose-50">
                                                        Hapus
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>

    {{-- Modal: Add Counter --}}
    <dialog id="modal-add-counter" class="m-auto w-[calc(100%-1.5rem)] max-w-md rounded-2xl p-0 shadow-xl backdrop:bg-neutral-950/50 max-sm:mb-0 max-sm:rounded-b-none">
        <form method="POST" action="{{ route('merchant.counters.store') }}" class="space-y-4 p-5 sm:p-6">
            @csrf
            <div class="flex items-center justify-between gap-3 border-b border-neutral-100 pb-3">
                <h3 class="text-h3 font-bold text-neutral-900">Tambah Loket Pelayanan</h3>
                <button type="button" onclick="document.getElementById('modal-add-counter').close()" class="-mr-2 inline-flex size-11 shrink-0 items-center justify-center rounded-lg text-neutral-400 transition hover:bg-neutral-100 hover:text-neutral-600" aria-label="Tutup">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div>
                <label for="add_name" class="block text-xs font-semibold text-neutral-700 mb-1">Nama Loket *</label>
                <input id="add_name" name="name" type="text" class="w-full min-h-11 rounded-lg border-neutral-300 text-sm focus:border-brand-500 focus:ring-brand-500" placeholder="Contoh: Loket 2" required />
            </div>

            <div>
                <label for="add_code" class="block text-xs font-semibold text-neutral-700 mb-1">Kode Loket *</label>
                <input id="add_code" name="code" type="text" class="w-full min-h-11 rounded-lg border-neutral-300 text-sm font-mono uppercase focus:border-brand-500 focus:ring-brand-500" placeholder="Contoh: L-02" required maxlength="10" />
                <p class="text-[11px] text-neutral-500 mt-1">Kode unik identifikasi loket, maksimal 10 karakter.</p>
            </div>

            <div>
                <label for="add_description" class="block text-xs font-semibold text-neutral-700 mb-1">Deskripsi (Opsional)</label>
                <textarea id="add_description" name="description" rows="2" class="w-full min-h-11 rounded-lg border-neutral-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm" placeholder="Contoh: Loket khusus pembayaran & konsultasi cepat"></textarea>
            </div>

            <div class="grid grid-cols-2 gap-2 border-t border-neutral-100 pt-3 sm:flex sm:justify-end">
                <x-button type="button" variant="secondary" class="h-11" onclick="document.getElementById('modal-add-counter').close()">Batal</x-button>
                <x-button type="submit" variant="primary" class="h-11">Simpan Loket</x-button>
            </div>
        </form>
    </dialog>

    {{-- Modal: Edit Counter --}}
    <dialog id="modal-edit-counter" class="m-auto w-[calc(100%-1.5rem)] max-w-md rounded-2xl p-0 shadow-xl backdrop:bg-neutral-950/50 max-sm:mb-0 max-sm:rounded-b-none">
        <form method="POST" id="form-edit-counter" action="" class="space-y-4 p-5 sm:p-6">
            @csrf
            @method('PUT')
            <div class="flex items-center justify-between gap-3 border-b border-neutral-100 pb-3">
                <h3 class="text-h3 font-bold text-neutral-900">Edit Loket Pelayanan</h3>
                <button type="button" onclick="document.getElementById('modal-edit-counter').close()" class="-mr-2 inline-flex size-11 shrink-0 items-center justify-center rounded-lg text-neutral-400 transition hover:bg-neutral-100 hover:text-neutral-600" aria-label="Tutup">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div>
                <label for="edit_name" class="block text-xs font-semibold text-neutral-700 mb-1">Nama Loket *</label>
                <input id="edit_name" name="name" type="text" class="w-full min-h-11 rounded-lg border-neutral-300 text-sm focus:border-brand-500 focus:ring-brand-500" required />
            </div>

            <div>
                <label for="edit_code" class="block text-xs font-semibold text-neutral-700 mb-1">Kode Loket *</label>
                <input id="edit_code" name="code" type="text" class="w-full min-h-11 rounded-lg border-neutral-300 text-sm font-mono uppercase focus:border-brand-500 focus:ring-brand-500" required maxlength="10" />
            </div>

            <div>
                <label for="edit_description" class="block text-xs font-semibold text-neutral-700 mb-1">Deskripsi (Opsional)</label>
                <textarea id="edit_description" name="description" rows="2" class="w-full min-h-11 rounded-lg border-neutral-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm"></textarea>
            </div>

            <div class="grid grid-cols-2 gap-2 border-t border-neutral-100 pt-3 sm:flex sm:justify-end">
                <x-button type="button" variant="secondary" class="h-11" onclick="document.getElementById('modal-edit-counter').close()">Batal</x-button>
                <x-button type="submit" variant="primary" class="h-11">Perbarui Loket</x-button>
            </div>
        </form>
    </dialog>

    {{-- Modal: Assign Operator --}}
    <dialog id="modal-assign-operator" class="m-auto w-[calc(100%-1.5rem)] max-w-md rounded-2xl p-0 shadow-xl backdrop:bg-neutral-950/50 max-sm:mb-0 max-sm:rounded-b-none">
        <form method="POST" id="form-assign-operator" action="" class="space-y-4 p-5 sm:p-6">
            @csrf
            <div class="flex items-center justify-between gap-3 border-b border-neutral-100 pb-3">
                <div>
                    <h3 class="text-h3 font-bold text-neutral-900">Tugaskan Operator</h3>
                    <p class="text-xs text-neutral-500 mt-0.5" id="assign-counter-title">Pilih staf untuk loket ini</p>
                </div>
                <button type="button" onclick="document.getElementById('modal-assign-operator').close()" class="-mr-2 inline-flex size-11 shrink-0 items-center justify-center rounded-lg text-neutral-400 transition hover:bg-neutral-100 hover:text-neutral-600" aria-label="Tutup">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div>
                <label for="assign_membership_id" class="block text-xs font-semibold text-neutral-700 mb-1">Pilih Staf Operator *</label>
                <select id="assign_membership_id" name="business_membership_id" class="mt-1 block h-11 w-full rounded-lg border-neutral-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm" required>
                    <option value="">-- Pilih Anggota Staf --</option>
                    @foreach ($availableOperators as $op)
                        <option value="{{ $op->id }}">{{ $op->user->name }} ({{ $op->user->email }})</option>
                    @endforeach
                </select>
                <p class="text-[11px] text-neutral-500 mt-1">Staf operator yang ditugaskan dapat memilih dan mengoperasikan loket ini dari dashboard.</p>
            </div>

            <div class="grid grid-cols-2 gap-2 border-t border-neutral-100 pt-3 sm:flex sm:justify-end">
                <x-button type="button" variant="secondary" class="h-11" onclick="document.getElementById('modal-assign-operator').close()">Batal</x-button>
                <x-button type="submit" variant="primary" class="h-11">Tugaskan</x-button>
            </div>
        </form>
    </dialog>

    <script>
        function openEditModal(counterId, name, code, description) {
            const form = document.getElementById('form-edit-counter');
            form.action = `/umkm/counters/${counterId}`;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_code').value = code;
            document.getElementById('edit_description').value = description;
            document.getElementById('modal-edit-counter').showModal();
        }

        function openAssignModal(counterId, counterName) {
            const form = document.getElementById('form-assign-operator');
            form.action = `/umkm/counters/${counterId}/operators`;
            document.getElementById('assign-counter-title').textContent = `Tugaskan staf untuk ${counterName}`;
            document.getElementById('modal-assign-operator').showModal();
        }
    </script>
</x-layouts.merchant>
