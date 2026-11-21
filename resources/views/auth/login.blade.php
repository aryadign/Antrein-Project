<x-layouts.auth title="Masuk" heading="Masuk ke Akun Anda" subheading="Untuk Pelanggan, Pemilik UMKM, Staf Operator, dan Admin AntreIn.">
    @include('auth.partials.flash')

    <form method="POST" action="{{ route('login') }}" class="space-y-5" x-data="{ submitting: false }" @submit="submitting = true">
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
                inputmode="email"
                placeholder="nama@email.id"
                :error="$errors->has('email')"
                :aria-invalid="$errors->has('email') ? 'true' : 'false'"
                aria-describedby="email-error"
            />
            <x-input-error id="email-error" :messages="$errors->get('email')" />
        </div>

        <div>
            <div class="mb-1.5 flex items-center justify-between gap-3">
                <x-form-label for="password" value="Kata Sandi" :required="true" class="mb-0" />
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="-my-3 inline-flex min-h-11 items-center rounded-md text-label font-medium text-brand-700 underline-offset-4 hover:text-brand-800 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">
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
                :aria-invalid="$errors->has('password') ? 'true' : 'false'"
                aria-describedby="password-error"
            />
            <x-input-error id="password-error" :messages="$errors->get('password')" />
        </div>

        <x-checkbox id="remember" name="remember" label="Ingat saya di perangkat ini" />

        <x-button type="submit" variant="primary" size="lg" class="w-full" x-bind:disabled="submitting">
            <svg x-show="submitting" x-cloak class="size-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
            </svg>
            <span x-text="submitting ? 'Memproses…' : 'Masuk ke Akun'">Masuk ke Akun</span>
        </x-button>
    </form>

    <div class="mt-6 border-t border-neutral-200 pt-6">
        <p class="text-center text-label text-neutral-600">Belum punya akun?</p>
        <div class="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-2">
            <x-button variant="outline" :href="route('register', ['type' => 'customer'])" class="w-full">
                <x-customer.icon name="ticket" class="size-4 text-brand-700" />
                Daftar Pelanggan
            </x-button>
            <x-button variant="outline" :href="route('register', ['type' => 'merchant'])" class="w-full">
                <x-customer.icon name="store" class="size-4 text-brand-700" />
                Daftar UMKM Baru
            </x-button>
        </div>
    </div>

    <div class="mt-6 flex items-start gap-3 text-label">
        <x-customer.icon name="search" class="mt-0.5 size-5 shrink-0 text-brand-700" />
        <p class="text-pretty text-neutral-600">
            <span class="font-semibold text-neutral-900">Ingin mengambil nomor antrean?</span>
            Lihat toko dan layanan tanpa akun, lalu masuk saat siap mengambil nomor (hingga 3 antrean aktif).
            <a href="{{ route('customer.dashboard') }}" class="inline-flex items-center gap-1 font-semibold text-brand-700 underline-offset-4 hover:text-brand-800 hover:underline">
                Jelajahi Toko
                <x-customer.icon name="arrow-right" class="size-4" />
            </a>
        </p>
    </div>

    @if (app()->environment(['local', 'testing']))
        @php
            $demoPersonas = [
                ['key' => 'admin', 'name' => 'Admin AntreIn', 'role' => 'Admin', 'email' => 'admin@antrein.id'],
                ['key' => 'owner', 'name' => 'Owner UMKM', 'role' => 'Pemilik', 'email' => 'owner@garasi.id'],
                ['key' => 'operator', 'name' => 'Operator Loket 1', 'role' => 'Operator', 'email' => 'operator@garasi.id'],
                ['key' => 'customer', 'name' => 'Pelanggan Demo', 'role' => 'Pelanggan', 'email' => 'pelanggan@antrein.id'],
            ];
        @endphp

        <section x-data="{ submitting: false, selected: null }" class="mt-6 border-t border-dashed border-amber-300 pt-5" aria-labelledby="dev-quick-access-heading">
            <div class="flex flex-wrap items-baseline justify-between gap-x-3 gap-y-1">
                <h3 id="dev-quick-access-heading" class="text-label font-semibold text-amber-900">Akses Cepat Pengujian (Dev Mode)</h3>
                <span class="text-xs font-medium text-amber-800">Hanya lokal &amp; pengujian</span>
            </div>
            <p class="mt-1 text-xs text-neutral-600">Pilih persona untuk masuk langsung tanpa kata sandi.</p>

            <div class="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-2">
                @foreach ($demoPersonas as $persona)
                    <form method="POST" action="{{ route('dev.quick-login') }}" class="h-full" @submit="submitting = true; selected = '{{ $persona['key'] }}'">
                        @csrf
                        <input type="hidden" name="persona" value="{{ $persona['key'] }}">
                        <button
                            type="submit"
                            :disabled="submitting"
                            class="flex h-full min-h-11 w-full flex-col items-start rounded-lg px-3 py-2 text-left ring-1 ring-amber-200 transition-colors duration-150 ease-out hover:bg-amber-50 hover:ring-amber-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            <span class="flex w-full items-center justify-between gap-2">
                                <span class="text-label font-semibold text-neutral-900">{{ $persona['name'] }}</span>
                                <span class="text-xs font-medium text-amber-800">{{ $persona['role'] }}</span>
                            </span>
                            <span class="mt-0.5 w-full truncate text-xs text-neutral-600">
                                <span x-show="!submitting || selected !== '{{ $persona['key'] }}'">{{ $persona['email'] }}</span>
                                <span x-show="submitting && selected === '{{ $persona['key'] }}'" class="font-semibold text-brand-700" style="display: none;">Menyiapkan sesi…</span>
                            </span>
                        </button>
                    </form>
                @endforeach
            </div>
        </section>
    @endif
</x-layouts.auth>
