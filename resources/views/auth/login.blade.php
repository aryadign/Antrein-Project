<x-layouts.auth title="Masuk" heading="Masuk ke Akun Anda" subheading="Kelola operasional antrean usaha atau akses panel kontrol.">
    @if (session('status'))
        <div class="mb-4 rounded-md bg-emerald-50 p-4 text-sm font-medium text-emerald-800 border border-emerald-200">
            {{ session('status') }}
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

        <div>
            <div class="flex items-center justify-between mb-1.5">
                <x-form-label for="password" value="Kata Sandi" :required="true" class="mb-0" />
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="text-caption font-medium text-brand-700 hover:text-brand-800 hover:underline">
                        Lupa kata sandi?
                    </a>
                @endif
            </div>
            <x-input
                id="password"
                name="password"
                type="password"
                required
                autocomplete="current-password"
                placeholder="••••••••"
                :invalid="$errors->has('password')"
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

        <div class="mt-6 text-center text-sm text-neutral-600">
            Belum memiliki akun usaha?
            <a href="{{ route('register') }}" class="font-semibold text-brand-700 hover:text-brand-800 hover:underline">
                Daftar UMKM Baru
            </a>
        </div>
    </form>
</x-layouts.auth>

