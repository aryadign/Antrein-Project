@php
    $isDisplayActive = $business->isDisplayActive();
    $oneTimeDisplayUrl = session('one_time_display_token') ? route('display.show', ['token' => session('one_time_display_token')]) : null;
    $timezone = $business->timezone ?? 'Asia/Makassar';
@endphp

<x-layouts.merchant title="Layar TV Antrean">
    <div class="space-y-5 sm:space-y-6">
        {{-- Page Header --}}
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between sm:gap-4">
            <div class="min-w-0">
                <h1 class="text-[1.375rem] leading-[1.875rem] font-bold sm:text-[1.75rem] sm:leading-9 text-neutral-950">Layar TV Antrean</h1>
                <p class="mt-1 text-body text-neutral-600">
                    Kelola akses layar monitor antrean untuk ditampilkan pada TV, monitor ruang tunggu, atau layar pelanggan di lokasi usaha.
                </p>
            </div>

            <span class="inline-flex w-fit shrink-0 items-center gap-1.5 rounded-full border px-3 py-1.5 text-xs font-bold {{ $isDisplayActive ? 'border-emerald-300 bg-emerald-100 text-emerald-800' : 'border-neutral-300 bg-neutral-100 text-neutral-700' }}">
                <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                {{ $isDisplayActive ? 'Layar TV Aktif' : 'Layar TV Nonaktif' }}
            </span>
        </div>

        {{-- Flash Notification --}}
        @if (session('status'))
            <div class="flex items-center gap-2.5 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-800" role="status">
                <svg class="w-5 h-5 shrink-0 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <span>{{ session('status') }}</span>
            </div>
        @endif

        {{-- Main Configuration Card --}}
        <section class="space-y-5 rounded-2xl border border-neutral-200 bg-white p-4 shadow-card sm:p-6">
            {{-- Status & Overview Row --}}
            <div class="flex flex-col gap-4 border-b border-neutral-200 pb-5 sm:flex-row sm:items-center sm:justify-between">
                <div class="min-w-0">
                    <h2 class="text-h3 font-bold text-neutral-900">Konfigurasi & Akses Layar Monitor</h2>
                    <dl class="mt-2 flex flex-wrap gap-2 text-caption">
                        <div class="inline-flex min-w-0 items-center gap-1 rounded-lg bg-neutral-100 px-2.5 py-1">
                            <dt class="text-neutral-500">Unit Usaha:</dt>
                            <dd class="truncate font-semibold text-neutral-800">{{ $business->name }}</dd>
                        </div>
                        <div class="inline-flex items-center gap-1 rounded-lg bg-neutral-100 px-2.5 py-1">
                            <dt class="text-neutral-500">Mode loket:</dt>
                            <dd class="font-semibold text-neutral-800 tabular-nums">
                                {{ $business->usesStations() ? $activeCountersCount.' Loket Aktif' : '1 Loket Utama' }}
                            </dd>
                        </div>
                    </dl>
                </div>

                {{-- Activation Toggle Form --}}
                <form method="POST" action="{{ route('merchant.display.toggle') }}" class="shrink-0">
                    @csrf
                    <input type="hidden" name="is_display_enabled" value="{{ $business->is_display_enabled ? '0' : '1' }}">
                    <button
                        type="submit"
                        class="inline-flex h-11 w-full items-center justify-center gap-2 rounded-xl px-4 text-sm font-bold shadow-card transition sm:w-auto {{ $business->is_display_enabled ? 'border border-rose-200 bg-rose-50 text-rose-700 hover:bg-rose-100' : 'bg-brand-700 text-white hover:bg-brand-800' }}"
                    >
                        @if ($business->is_display_enabled)
                            <svg class="w-4 h-4 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                            Nonaktifkan Layar Publik
                        @else
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Aktifkan Layar Publik
                        @endif
                    </button>
                </form>
            </div>

            {{-- Display QR & Quick Launch --}}
            @if ($isDisplayActive && $oneTimeDisplayUrl)
                @php $displayQrSvg = app(\App\Services\QrCodeService::class)->generateSvg($oneTimeDisplayUrl, 160); @endphp
                <div class="flex flex-col gap-4 rounded-xl border border-amber-200 bg-amber-50/50 p-4 sm:flex-row sm:items-center">
                    <div class="mx-auto shrink-0 rounded-xl bg-white p-2.5 shadow-card ring-1 ring-amber-200 sm:mx-0 [&_svg]:block [&_svg]:size-32">
                        {!! $displayQrSvg !!}
                    </div>
                    <div class="min-w-0 flex-1 space-y-3">
                        <div class="flex items-start gap-2.5">
                            <span class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-amber-100 text-amber-800">
                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            </span>
                            <div class="min-w-0">
                                <h4 class="text-sm font-bold text-amber-900">Buka atau pindai layar TV sekarang</h4>
                                <p class="mt-0.5 text-caption text-amber-800">
                                    Demi keamanan privasi & anti-tampering, kredensial akses ini <strong>hanya ditampilkan sekali ini saja</strong> dan tidak disimpan dalam teks biasa di database. Pindai QR atau buka langsung pada browser Smart TV / Monitor ruang tunggu Anda.
                                </p>
                            </div>
                        </div>

                        <div>
                            <a
                                href="{{ $oneTimeDisplayUrl }}"
                                target="_blank"
                                rel="noopener"
                                class="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-neutral-900 px-5 text-sm font-bold text-white shadow-card transition hover:bg-neutral-800"
                            >
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                <span>Buka Layar Monitor</span>
                            </a>
                        </div>
                    </div>
                </div>
            @elseif ($isDisplayActive)
                <div class="space-y-3 rounded-xl border border-emerald-200 bg-emerald-50/50 p-4">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div class="flex items-center gap-2">
                            <span class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700">
                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </span>
                            <h4 class="text-sm font-bold text-emerald-900">Layar TV aktif dan berjalan</h4>
                        </div>
                        <span class="rounded border border-emerald-200 bg-emerald-100 px-2 py-0.5 font-mono text-2xs text-emerald-800">
                            Hash: {{ substr($business->display_token_hash, 0, 12) }}...
                        </span>
                    </div>
                    <p class="text-caption text-neutral-600">
                        Layar display monitor TV Anda saat ini sedang aktif menerima pembaruan antrean langsung. Demi alasan keamanan, token plaintext tidak disimpan di sistem. Jika Anda perlu membuka ulang layar TV Anda, gunakan tombol <strong>Rotasi / Regenerasi Akses</strong> di bawah untuk membuat kredensial baru.
                    </p>
                </div>
            @endif

            @if ($isDisplayActive)
                <div class="flex flex-col gap-3 text-caption text-neutral-600 sm:flex-row sm:items-center sm:justify-between">
                    <p>
                        Kredensial aktif sejak:
                        <strong class="tabular-nums">{{ $business->display_token_created_at ? $business->display_token_created_at->setTimezone($timezone)->translatedFormat('d M Y, H:i') : 'Baru saja' }}</strong>
                    </p>

                    {{-- Danger Zone Actions: Regenerate & Revoke --}}
                    <div class="grid grid-cols-2 gap-2 sm:flex">
                        <form method="POST" action="{{ route('merchant.display.generate') }}" onsubmit="return confirm('Apakah Anda yakin ingin membuat akses layar baru? Akses lama akan segera dinonaktifkan.');">
                            @csrf
                            <button type="submit" class="inline-flex h-11 w-full items-center justify-center gap-1.5 rounded-lg border border-amber-200 px-3 text-xs font-semibold text-amber-700 transition hover:bg-amber-50">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                Rotasi / Regenerasi Akses
                            </button>
                        </form>

                        <form method="POST" action="{{ route('merchant.display.revoke') }}" onsubmit="return confirm('Apakah Anda yakin ingin mencabut akses layar ini? Layar TV akan segera dinonaktifkan.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="inline-flex h-11 w-full items-center justify-center gap-1.5 rounded-lg border border-rose-200 px-3 text-xs font-semibold text-rose-700 transition hover:bg-rose-50">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                Cabut Akses
                            </button>
                        </form>
                    </div>
                </div>
            @else
                {{-- Display is currently disabled or no token yet --}}
                <div class="space-y-3 rounded-2xl border border-neutral-200 bg-neutral-50 p-5 text-center sm:p-6">
                    <div class="mx-auto flex size-12 items-center justify-center rounded-xl bg-neutral-200 text-neutral-500">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    </div>
                    <h3 class="text-base font-bold text-neutral-900">Layar Antrean Publik Sedang Nonaktif</h3>
                    <p class="mx-auto max-w-md text-caption text-neutral-600">
                        Aktifkan layar publik untuk menghasilkan akses monitor berenkripsi tinggi yang dapat dibuka pada peramban TV atau monitor ruang tunggu pelanggan Anda.
                    </p>
                    <form method="POST" action="{{ route('merchant.display.generate') }}" class="pt-2">
                        @csrf
                        <x-button type="submit" variant="primary" class="h-11 w-full px-5 sm:w-auto">
                            Aktifkan & Buat Akses Layar TV
                        </x-button>
                    </form>
                </div>
            @endif
        </section>

        {{-- Live Screen Preview Card --}}
        <section class="rounded-2xl border border-neutral-200 bg-white p-4 shadow-card sm:p-6">
            <div class="space-y-4">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div class="min-w-0">
                        <h3 class="text-h3 font-bold text-neutral-900">Pratinjau Layar TV (Live Data Preview)</h3>
                        <p class="text-caption text-neutral-600 mt-0.5">
                            Representasi tampilan publik yang dilihat oleh pengunjung di ruang tunggu usaha Anda.
                        </p>
                    </div>

                    @if ($business->isDisplayActive() && $business->display_token)
                        <a
                            href="{{ route('display.show', ['token' => $business->display_token]) }}"
                            target="_blank"
                            rel="noopener"
                            class="inline-flex min-h-11 shrink-0 items-center gap-1 text-xs font-bold text-brand-700 underline hover:text-brand-800"
                        >
                            <span>Buka Layar Penuh</span>
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                        </a>
                    @endif
                </div>

                {{-- Scaled Down Miniature TV Frame --}}
                <div class="overflow-hidden rounded-2xl border border-neutral-200 bg-neutral-50 p-4 text-neutral-900 shadow-card sm:p-6">
                    {{-- Mini Header --}}
                    <div class="flex flex-wrap items-center justify-between gap-2 rounded-xl border border-neutral-200 bg-white px-3.5 py-2.5 text-xs shadow-2xs">
                        <div class="flex min-w-0 items-center gap-2">
                            <span class="shrink-0 rounded-md bg-brand-700 px-2 py-0.5 font-black text-white">Layar AntreIn</span>
                            <span class="truncate font-bold text-neutral-950">{{ $business->name }}</span>
                        </div>
                        <div class="flex items-center gap-1.5 font-mono text-xs text-neutral-700 tabular-nums">
                            <span class="font-bold">{{ $displayData['server_time']['time'] }}</span>
                            <span class="rounded border border-neutral-200 bg-neutral-100 px-1.5 py-0.5 text-xs text-neutral-600">{{ $business->timezone }}</span>
                        </div>
                    </div>

                    {{-- Mini Counters Grid --}}
                    <div class="py-5">
                        @if (empty($displayData['counters']))
                            <div class="rounded-xl border border-neutral-200 bg-white py-8 text-center text-xs text-neutral-500">
                                Belum ada loket aktif yang terdaftar di usaha ini.
                            </div>
                        @else
                            @php
                                $previewCount = count($displayData['counters']);
                                $previewGridClass = match (true) {
                                    $previewCount <= 1 => 'grid-cols-1 max-w-md mx-auto',
                                    $previewCount === 2 => 'grid-cols-1 sm:grid-cols-2',
                                    $previewCount === 3 => 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3',
                                    default => 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-4',
                                };
                            @endphp
                            <div class="grid gap-4 {{ $previewGridClass }}">
                                @foreach ($displayData['counters'] as $counter)
                                    @php
                                        $ticket = $counter['active_ticket'] ?? null;
                                        $isCalled = $ticket && ($ticket['status'] ?? null) === 'dipanggil';
                                        $isServing = $ticket && ($ticket['status'] ?? null) === 'sedang_dilayani';
                                    @endphp
                                    <div class="overflow-hidden rounded-xl border-2 bg-white shadow-2xs {{ $isCalled ? 'border-amber-400 bg-amber-50/60' : ($isServing ? 'border-brand-600' : 'border-neutral-200') }}">
                                        <div class="flex items-center justify-between border-b px-3.5 py-2 {{ $isCalled ? 'border-amber-300 bg-amber-400 text-amber-950' : ($isServing ? 'border-brand-700 bg-brand-700 text-white' : 'border-neutral-200 bg-neutral-50 text-neutral-800') }}">
                                            <span class="truncate text-xs font-extrabold">{{ $counter['name'] }}</span>
                                            <span class="rounded px-1.5 py-0.5 font-mono text-xs font-bold {{ $isCalled ? 'bg-amber-950/15 text-amber-950' : ($isServing ? 'bg-white/15 text-white' : 'bg-white text-neutral-600 ring-1 ring-neutral-200') }}">{{ $counter['code'] }}</span>
                                        </div>

                                        <div class="px-3.5 py-4 text-center">
                                            @if ($ticket)
                                                <div class="font-mono text-3xl font-black tracking-tight tabular-nums {{ $isCalled ? 'text-neutral-950' : 'text-brand-700' }}">
                                                    {{ $ticket['ticket_number'] }}
                                                </div>
                                                <div class="mt-1 truncate text-xs font-semibold text-neutral-700">
                                                    {{ $ticket['service_name'] }}
                                                </div>
                                                @if (! empty($ticket['booking_slot_window']))
                                                    <div class="mt-1 text-xs font-medium text-neutral-500 tabular-nums">
                                                        Slot {{ $ticket['booking_slot_window'] }}
                                                    </div>
                                                @endif
                                            @else
                                                <div class="font-mono text-2xl font-black text-neutral-300">—</div>
                                                <div class="mt-1 text-xs text-neutral-500">Standby</div>
                                            @endif
                                        </div>

                                        <div class="flex items-center justify-between border-t px-3.5 py-2 text-xs {{ $isCalled ? 'border-amber-300 bg-amber-100/70' : ($isServing ? 'border-neutral-100 bg-brand-50/40' : 'border-neutral-100 bg-neutral-50') }}">
                                            @if ($isCalled)
                                                <span class="rounded-full bg-amber-500 px-2 py-0.5 font-bold uppercase text-amber-950">Dipanggil</span>
                                            @elseif ($isServing)
                                                <span class="rounded-full border border-emerald-200 bg-emerald-100 px-2 py-0.5 font-bold uppercase text-emerald-800">Dilayani</span>
                                            @else
                                                <span class="rounded-full bg-neutral-200/80 px-2 py-0.5 font-semibold text-neutral-600">Standby</span>
                                            @endif
                                            <span class="font-mono text-neutral-500 tabular-nums">
                                                {{ $ticket['called_at_formatted'] ?? 'Loket Siap' }}
                                            </span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    {{-- Mini Footer --}}
                    <div class="flex flex-col gap-1 border-t border-neutral-200 pt-3 text-xs text-neutral-500 sm:flex-row sm:items-center sm:justify-between">
                        <span>Pembaruan otomatis secara langsung via WebSocket & Polling</span>
                        <span class="font-bold text-neutral-800">AntreIn Platform</span>
                    </div>
                </div>
            </div>
        </section>
    </div>
</x-layouts.merchant>

