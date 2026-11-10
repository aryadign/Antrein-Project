<x-layouts.merchant title="Layar Antrean Publik (TV Display) — AntreIn">
    <div class="space-y-6">
        {{-- Page Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-h2 font-bold text-neutral-950">Layar Antrean Publik (TV Display)</h1>
                <p class="text-body text-neutral-600 mt-1">
                    Kelola tautan monitor antrean untuk ditampilkan pada TV, monitor ruang tunggu, atau layar pelanggan di lokasi usaha.
                </p>
            </div>

            {{-- Status Badge Header --}}
            <div>
                @if ($business->isDisplayActive())
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-300 shadow-2xs">
                        <span class="w-2 h-2 rounded-full bg-emerald-600 animate-pulse"></span>
                        LAYAR TV AKTIF
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-neutral-100 text-neutral-700 border border-neutral-300 shadow-2xs">
                        <span class="w-2 h-2 rounded-full bg-neutral-400"></span>
                        LAYAR TV NONAKTIF
                    </span>
                @endif
            </div>
        </div>

        {{-- Flash Notification --}}
        @if (session('status'))
            <div class="rounded-xl bg-emerald-50 border border-emerald-200 p-4 text-sm font-medium text-emerald-800 flex items-center gap-2.5 shadow-2xs">
                <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <span>{{ session('status') }}</span>
            </div>
        @endif

        {{-- Main Configuration Card --}}
        <x-card padding="lg">
            <div class="space-y-6">
                {{-- Status & Overview Row --}}
                <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-5 border-b border-neutral-200 gap-4">
                    <div>
                        <h2 class="text-h3 font-bold text-neutral-900">Konfigurasi & Tautan Layar Monitor</h2>
                        <p class="text-caption text-neutral-500 mt-0.5">
                            Unit Usaha: <strong class="text-neutral-800">{{ $business->name }}</strong> • Total Loket Aktif: <strong class="text-neutral-800">{{ $activeCountersCount }} Loket</strong>
                        </p>
                    </div>

                    {{-- Activation Toggle Form --}}
                    <div>
                        <form method="POST" action="{{ route('merchant.display.toggle') }}">
                            @csrf
                            <input type="hidden" name="is_display_enabled" value="{{ $business->is_display_enabled ? '0' : '1' }}">
                            <button
                                type="submit"
                                class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-bold transition shadow-xs {{ $business->is_display_enabled ? 'bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200' : 'bg-brand-700 hover:bg-brand-800 text-white' }}"
                            >
                                @if ($business->is_display_enabled)
                                    <svg class="w-4 h-4 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                    Nonaktifkan Layar Publik
                                @else
                                    <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    Aktifkan Layar Publik
                                @endif
                            </button>
                        </form>
                    </div>
                </div>

                {{-- Display Link & Quick Launch --}}
                @if ($business->isDisplayActive() && $business->display_token)
                    <div class="space-y-4" x-data="{ copied: false }">
                        <label class="block text-label font-bold text-neutral-900">
                            Tautan Layar Publik (Buka di Smart TV / Monitor Ruang Tunggu)
                        </label>

                        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5">
                            <input 
                                type="text" 
                                readonly 
                                value="{{ route('display.show', ['token' => $business->display_token]) }}"
                                class="flex-1 px-4 py-2.5 text-xs sm:text-sm font-mono bg-neutral-50 border border-neutral-300 rounded-xl text-neutral-800 select-all focus:outline-none focus:ring-2 focus:ring-brand-500 shadow-2xs"
                            >

                            <button 
                                type="button" 
                                @click="navigator.clipboard.writeText('{{ route('display.show', ['token' => $business->display_token]) }}'); copied = true; setTimeout(() => copied = false, 2500);"
                                class="px-4 py-2.5 rounded-xl font-bold text-xs sm:text-sm transition flex items-center justify-center gap-2 shrink-0 border"
                                :class="copied ? 'bg-emerald-50 text-emerald-800 border-emerald-300' : 'bg-brand-50 hover:bg-brand-100 text-brand-700 border-brand-200'"
                            >
                                <svg x-show="!copied" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                <svg x-show="copied" class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="display: none;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                <span x-text="copied ? 'Tersalin ke Clipboard!' : 'Salin Tautan'"></span>
                            </button>

                            <a 
                                href="{{ route('display.show', ['token' => $business->display_token]) }}" 
                                target="_blank"
                                class="px-4 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs sm:text-sm transition flex items-center justify-center gap-2 shrink-0 shadow-xs"
                            >
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                <span>Buka Layar Display</span>
                            </a>
                        </div>

                        <div class="flex flex-col sm:flex-row sm:items-center justify-between text-caption text-neutral-500 pt-1 gap-2">
                            <div>
                                Kredensial aktif sejak: <strong>{{ $business->display_token_created_at ? $business->display_token_created_at->setTimezone($business->timezone ?? 'Asia/Makassar')->translatedFormat('d M Y, H:i') : 'Baru saja' }}</strong>
                            </div>

                            {{-- Danger Zone Actions: Regenerate & Revoke --}}
                            <div class="flex items-center gap-4">
                                <form method="POST" action="{{ route('merchant.display.generate') }}" onsubmit="return confirm('Apakah Anda yakin ingin membuat tautan baru? Tautan lama akan segera dinonaktifkan.');">
                                    @csrf
                                    <button type="submit" class="text-amber-700 hover:text-amber-900 text-xs font-semibold underline flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                        Rotasi / Regenerasi Tautan
                                    </button>
                                </form>

                                <form method="POST" action="{{ route('merchant.display.revoke') }}" onsubmit="return confirm('Apakah Anda yakin ingin mencabut akses layar ini? Layar TV akan segera dinonaktifkan.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-rose-600 hover:text-rose-800 text-xs font-semibold underline flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        Cabut Akses
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @else
                    {{-- Display is currently disabled or no token yet --}}
                    <div class="p-6 rounded-2xl bg-neutral-50 border border-neutral-200 text-center space-y-3">
                        <div class="w-12 h-12 rounded-xl bg-neutral-200 text-neutral-500 mx-auto flex items-center justify-center">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        </div>
                        <h3 class="text-base font-bold text-neutral-900">Layar Antrean Publik Sedang Nonaktif</h3>
                        <p class="text-caption text-neutral-600 max-w-md mx-auto">
                            Aktifkan layar publik untuk menghasilkan tautan unik berenkripsi tinggi yang dapat dibuka pada peramban TV atau monitor ruang tunggu pelanggan Anda.
                        </p>
                        <div class="pt-2">
                            <form method="POST" action="{{ route('merchant.display.generate') }}">
                                @csrf
                                <x-button type="submit" variant="primary" class="px-5 py-2">
                                    Aktifkan & Buat Tautan Layar TV
                                </x-button>
                            </form>
                        </div>
                    </div>
                @endif
            </div>
        </x-card>

        {{-- Live Screen Preview Card --}}
        <x-card padding="lg">
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-h3 font-bold text-neutral-900">Pratinjau Layar TV (Live Data Preview)</h3>
                        <p class="text-caption text-neutral-500 mt-0.5">
                            Representasi tampilan publik yang dilihat oleh pengunjung di ruang tunggu usaha Anda.
                        </p>
                    </div>

                    @if ($business->isDisplayActive() && $business->display_token)
                        <a 
                            href="{{ route('display.show', ['token' => $business->display_token]) }}" 
                            target="_blank"
                            class="text-xs font-bold text-brand-700 hover:text-brand-800 underline inline-flex items-center gap-1"
                        >
                            <span>Buka Layar Penuh</span>
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                        </a>
                    @endif
                </div>

                {{-- Scaled Down Miniature TV Frame --}}
                <div class="rounded-2xl border border-slate-800 bg-slate-950 p-6 text-white shadow-lg overflow-hidden">
                    {{-- Mini Header --}}
                    <div class="flex items-center justify-between pb-4 border-b border-slate-800 text-xs">
                        <div class="flex items-center gap-2">
                            <span class="font-black text-brand-400">AntreIn Display</span>
                            <span class="text-slate-600">•</span>
                            <span class="font-bold text-white">{{ $business->name }}</span>
                        </div>
                        <div class="font-mono text-slate-400 text-xs">
                            {{ $displayData['server_time']['time'] }} • {{ $business->timezone }}
                        </div>
                    </div>

                    {{-- Mini Counters Grid --}}
                    <div class="py-6">
                        @if (empty($displayData['counters']))
                            <div class="text-center py-8 text-xs text-slate-500">
                                Belum ada loket aktif yang terdaftar di usaha ini.
                            </div>
                        @else
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                                @foreach ($displayData['counters'] as $counter)
                                    <div class="rounded-xl border p-4 bg-slate-900/80 {{ $counter['active_ticket'] && $counter['active_ticket']['status'] === 'dipanggil' ? 'border-amber-500/80 bg-amber-950/20' : ($counter['active_ticket'] ? 'border-emerald-500/60' : 'border-slate-800') }}">
                                        <div class="flex items-center justify-between mb-2">
                                            <span class="font-bold text-xs uppercase text-slate-300">{{ $counter['name'] }}</span>
                                            <span class="text-[10px] font-mono text-slate-500">{{ $counter['code'] }}</span>
                                        </div>

                                        <div class="text-center py-2">
                                            @if ($counter['active_ticket'])
                                                <div class="text-3xl font-black font-mono tracking-tight {{ $counter['active_ticket']['status'] === 'dipanggil' ? 'text-amber-300 animate-pulse' : 'text-white' }}">
                                                    {{ $counter['active_ticket']['ticket_number'] }}
                                                </div>
                                                <div class="text-[11px] font-medium text-slate-400 truncate mt-1">
                                                    {{ $counter['active_ticket']['service_name'] }}
                                                </div>
                                            @else
                                                <div class="text-2xl font-black font-mono text-slate-700">—</div>
                                                <div class="text-[11px] text-slate-600 mt-1">Standby</div>
                                            @endif
                                        </div>

                                        <div class="mt-2 pt-2 border-t border-slate-800/80 flex items-center justify-between text-[10px]">
                                            @if ($counter['active_ticket'] && $counter['active_ticket']['status'] === 'dipanggil')
                                                <span class="px-2 py-0.5 rounded bg-amber-400 text-slate-950 font-bold uppercase">Dipanggil</span>
                                            @elseif ($counter['active_ticket'] && $counter['active_ticket']['status'] === 'sedang_dilayani')
                                                <span class="px-2 py-0.5 rounded bg-emerald-500 text-slate-950 font-bold uppercase">Dilayani</span>
                                            @else
                                                <span class="text-slate-500">Tersedia</span>
                                            @endif
                                            <span class="text-slate-600 font-mono">
                                                {{ $counter['active_ticket']['called_at_formatted'] ?? '' }}
                                            </span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    {{-- Mini Footer --}}
                    <div class="pt-3 border-t border-slate-800 text-[11px] text-slate-500 flex items-center justify-between">
                        <span>Pembaruan otomatis secara real-time via WebSocket & Polling</span>
                        <span class="font-semibold text-slate-400">AntreIn Platform</span>
                    </div>
                </div>
            </div>
        </x-card>
    </div>
</x-layouts.merchant>

