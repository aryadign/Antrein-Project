<x-layouts.auth title="Masuk" heading="Masuk ke Akun Anda" subheading="Untuk Pelanggan, Pemilik UMKM, Staf Operator, dan Admin AntreIn.">
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
                placeholder="nama@email.id"
                :error="$errors->has('email')"
            />
            <x-input-error :messages="$errors->get('email')" class="mt-1" />
        </div>

        <div>
            <div class="mb-1.5 flex items-center justify-between">
                <x-form-label for="password" value="Kata Sandi" :required="true" class="mb-0" />
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="inline-flex min-h-11 items-center text-caption font-medium text-brand-700 hover:text-brand-800 hover:underline">
                        Lupa kata sandi?
                    </a>
                @endif
            </div>
            <x-password-input
                id="password"
                name="password"
                required
                autocomplete="current-password"
                placeholder="Masukkan kata sandi"
                :error="$errors->has('password')"
            />
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

        <div class="mt-2 flex flex-wrap items-center justify-center gap-x-3 gap-y-1 text-center text-sm text-neutral-600">
            <span>Belum punya akun?</span>
            <a href="{{ route('register', ['type' => 'customer']) }}" class="inline-flex min-h-11 items-center font-semibold text-brand-700 hover:text-brand-800 hover:underline">
                Daftar Pelanggan
            </a>
            <span class="text-neutral-300">&bull;</span>
            <a href="{{ route('register', ['type' => 'merchant']) }}" class="inline-flex min-h-11 items-center font-semibold text-neutral-800 hover:text-brand-700 hover:underline">
                Daftar UMKM Baru
            </a>
        </div>
    </form>

    {{-- Jalur pelanggan --}}
    <div class="mt-4 flex flex-col gap-3 rounded-xl border border-brand-100 bg-brand-50/70 p-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-start gap-3">
            <span class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-white text-brand-700 ring-1 ring-brand-100">
                <x-customer.icon name="ticket" class="size-5" />
            </span>
            <div class="text-sm">
                <p class="font-semibold text-neutral-900">Ingin mengambil nomor antrean?</p>
                <p class="text-neutral-600">Masuk atau daftar akun pelanggan untuk mengambil hingga 3 antrean aktif.</p>
            </div>
        </div>
        <a href="{{ route('customer.dashboard') }}" class="inline-flex h-11 shrink-0 items-center justify-center gap-1.5 rounded-lg bg-white px-4 text-sm font-semibold text-brand-700 ring-1 ring-brand-200 transition hover:bg-brand-100">
            Jelajahi Toko
            <x-customer.icon name="arrow-right" class="size-4" />
        </a>
    </div>

    {{-- Developer Quick Demo Access Panel (Local & Testing Only) --}}
    @if (app()->environment(['local', 'testing']))
        <div
            x-data="{ submitting: false, selected: null }"
            class="mt-8 pt-6 border-t border-neutral-200"
        >
            <div class="rounded-xl border border-amber-300 bg-amber-50/60 p-4 space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-mono font-bold uppercase tracking-wider text-amber-900">
                        Akses Cepat Pengujian (Dev Mode)
                    </span>
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

                    {{-- Customer Demo --}}
                    <form method="POST" action="{{ route('dev.quick-login') }}" @submit="submitting = true; selected = 'customer'">
                        @csrf
                        <input type="hidden" name="persona" value="customer">
                        <button
                            type="submit"
                            :disabled="submitting"
                            class="w-full text-left p-2.5 rounded-lg bg-white border border-neutral-200 hover:border-amber-400 hover:bg-amber-50/30 transition shadow-sm group disabled:opacity-60 disabled:cursor-not-allowed"
                        >
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-neutral-900 group-hover:text-amber-700">Pelanggan Demo</span>
                                <span class="text-[10px] font-mono font-semibold px-1 rounded bg-amber-100 text-amber-800">Pelanggan</span>
                            </div>
                            <div class="text-[11px] text-neutral-500 font-mono mt-0.5 truncate">
                                <span x-show="!submitting || selected !== 'customer'">pelanggan@antrein.id</span>
                                <span x-show="submitting && selected === 'customer'" class="text-amber-600 font-semibold" style="display: none;">Menyiapkan sesi...</span>
                            </div>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    @endif
</x-layouts.auth>
