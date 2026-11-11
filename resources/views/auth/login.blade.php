<x-layouts.auth title="Masuk" heading="Masuk ke Akun Anda" subheading="Kelola operasional antrean usaha atau akses panel kontrol.">
    @if (session('status'))
        <div class="mb-4 rounded-md bg-emerald-50 p-4 text-sm font-medium text-emerald-800 border border-emerald-200">
            {{ session('status') }}
        </div>
    @endif

    @if (session('error'))
        <div class="mb-4 rounded-md bg-rose-50 p-4 text-sm font-medium text-rose-800 border border-rose-200">
            {{ session('error') }}
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf

        <div>
            <x-form-label for="email" value="Alamat Email" :required="true" />
            <x-input
                id="email"
                name="email"
                type="email"
                :value="old('email')"
                required
                autofocus
                autocomplete="username"
                placeholder="nama@usaha.id"
                :invalid="$errors->has('email')"
            />
            <x-input-error :messages="$errors->get('email')" class="mt-1" />
        </div>

        <div x-data="{ showPassword: false }">
            <div class="flex items-center justify-between mb-1.5">
                <x-form-label for="password" value="Kata Sandi" :required="true" class="mb-0" />
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="text-caption font-medium text-brand-700 hover:text-brand-800 hover:underline">
                        Lupa kata sandi?
                    </a>
                @endif
            </div>
            <div class="relative">
                <x-input
                    id="password"
                    name="password"
                    type="password"
                    x-bind:type="showPassword ? 'text' : 'password'"
                    required
                    autocomplete="current-password"
                    placeholder="••••••••"
                    :invalid="$errors->has('password')"
                    class="pr-10"
                />
                <button
                    type="button"
                    @click="showPassword = !showPassword"
                    class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-neutral-400 hover:text-neutral-600 focus:outline-none"
                    aria-label="Tampilkan atau sembunyikan kata sandi"
                    tabindex="-1"
                >
                    <svg x-show="!showPassword" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    <svg x-show="showPassword" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="display: none;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                    </svg>
                </button>
            </div>
            <x-input-error :messages="$errors->get('password')" class="mt-1" />
        </div>

        <div class="flex items-center justify-between">
            <x-checkbox id="remember" name="remember" label="Ingat saya di perangkat ini" />
        </div>

        <div class="pt-2">
            <x-button type="submit" variant="primary" class="w-full h-11 justify-center">
                Masuk ke Akun
            </x-button>
        </div>

        <div class="mt-6 text-center text-sm text-neutral-600">
            Belum memiliki akun usaha?
            <a href="{{ route('register') }}" class="font-semibold text-brand-700 hover:text-brand-800 hover:underline">
                Daftar UMKM Baru
            </a>
        </div>
    </form>

    {{-- Developer Quick Demo Access Panel (Local & Testing Only) --}}
    @if (app()->environment(['local', 'testing']))
        <div
            x-data="{ submitting: false, selected: null }"
            class="mt-8 pt-6 border-t border-neutral-200"
        >
            <div class="rounded-xl border border-amber-300 bg-amber-50/60 p-4 space-y-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="flex h-2 w-2 rounded-full bg-amber-500" :class="{ 'animate-pulse': !submitting, 'animate-ping': submitting }"></span>
                        <span class="text-xs font-mono font-bold uppercase tracking-wider text-amber-900">
                            Akses Cepat Pengujian (Dev Mode)
                        </span>
                    </div>
                    <span class="text-[10px] font-mono px-1.5 py-0.5 rounded bg-amber-200/80 text-amber-900 font-semibold">
                        LOCAL / TEST ONLY
                    </span>
                </div>

                <p class="text-xs text-neutral-600">
                    Klik salah satu persona di bawah untuk masuk langsung tanpa input password:
                </p>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    {{-- Admin --}}
                    <form method="POST" action="{{ route('dev.quick-login') }}" @submit="submitting = true; selected = 'admin'">
                        @csrf
                        <input type="hidden" name="persona" value="admin">
                        <button
                            type="submit"
                            :disabled="submitting"
                            class="w-full text-left p-2.5 rounded-lg bg-white border border-neutral-200 hover:border-rose-400 hover:bg-rose-50/30 transition shadow-sm group disabled:opacity-60 disabled:cursor-not-allowed"
                        >
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-neutral-900 group-hover:text-rose-700">Admin AntreIn</span>
                                <span class="text-[10px] font-mono font-semibold px-1 rounded bg-rose-100 text-rose-800">Admin</span>
                            </div>
                            <div class="text-[11px] text-neutral-500 font-mono mt-0.5 truncate">
                                <span x-show="!submitting || selected !== 'admin'">admin@antrein.id</span>
                                <span x-show="submitting && selected === 'admin'" class="text-rose-600 font-semibold" style="display: none;">Menyiapkan sesi...</span>
                            </div>
                        </button>
                    </form>

                    {{-- Owner --}}
                    <form method="POST" action="{{ route('dev.quick-login') }}" @submit="submitting = true; selected = 'owner'">
                        @csrf
                        <input type="hidden" name="persona" value="owner">
                        <button
                            type="submit"
                            :disabled="submitting"
                            class="w-full text-left p-2.5 rounded-lg bg-white border border-neutral-200 hover:border-blue-400 hover:bg-blue-50/30 transition shadow-sm group disabled:opacity-60 disabled:cursor-not-allowed"
                        >
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-neutral-900 group-hover:text-blue-700">Owner UMKM</span>
                                <span class="text-[10px] font-mono font-semibold px-1 rounded bg-blue-100 text-blue-800">Owner</span>
                            </div>
                            <div class="text-[11px] text-neutral-500 font-mono mt-0.5 truncate">
                                <span x-show="!submitting || selected !== 'owner'">owner@garasi.id</span>
                                <span x-show="submitting && selected === 'owner'" class="text-blue-600 font-semibold" style="display: none;">Menyiapkan sesi...</span>
                            </div>
                        </button>
                    </form>

                    {{-- Operator --}}
                    <form method="POST" action="{{ route('dev.quick-login') }}" @submit="submitting = true; selected = 'operator'">
                        @csrf
                        <input type="hidden" name="persona" value="operator">
                        <button
                            type="submit"
                            :disabled="submitting"
                            class="w-full text-left p-2.5 rounded-lg bg-white border border-neutral-200 hover:border-emerald-400 hover:bg-emerald-50/30 transition shadow-sm group disabled:opacity-60 disabled:cursor-not-allowed"
                        >
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-neutral-900 group-hover:text-emerald-700">Operator Loket 1</span>
                                <span class="text-[10px] font-mono font-semibold px-1 rounded bg-emerald-100 text-emerald-800">Operator</span>
                            </div>
                            <div class="text-[11px] text-neutral-500 font-mono mt-0.5 truncate">
                                <span x-show="!submitting || selected !== 'operator'">operator@garasi.id</span>
                                <span x-show="submitting && selected === 'operator'" class="text-emerald-600 font-semibold" style="display: none;">Menyiapkan sesi...</span>
                            </div>
                        </button>
                    </form>

                    {{-- Customer Guest --}}
                    <form method="POST" action="{{ route('dev.quick-login') }}" @submit="submitting = true; selected = 'customer'">
                        @csrf
                        <input type="hidden" name="persona" value="customer">
                        <button
                            type="submit"
                            :disabled="submitting"
                            class="w-full text-left p-2.5 rounded-lg bg-white border border-neutral-200 hover:border-amber-400 hover:bg-amber-50/30 transition shadow-sm group disabled:opacity-60 disabled:cursor-not-allowed"
                        >
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-neutral-900 group-hover:text-amber-700">Pelanggan (Tamu)</span>
                                <span class="text-[10px] font-mono font-semibold px-1 rounded bg-amber-100 text-amber-800">Guest</span>
                            </div>
                            <div class="text-[11px] text-neutral-500 mt-0.5 truncate">
                                <span x-show="!submitting || selected !== 'customer'">Buka Antrean Barbershop</span>
                                <span x-show="submitting && selected === 'customer'" class="text-amber-600 font-semibold" style="display: none;">Membuka antrean...</span>
                            </div>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    @endif
</x-layouts.auth>
