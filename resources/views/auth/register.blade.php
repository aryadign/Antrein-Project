<x-layouts.auth title="Daftar UMKM Baru" heading="Daftarkan Usaha Anda" subheading="Mulai kelola antrean pelanggan Anda secara digital dalam hitungan menit.">
    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf

        <div>
            <x-form-label for="name" value="Nama Pemilik / Pengelola" :required="true" />
            <x-input
                id="name"
                name="name"
                type="text"
                :value="old('name')"
                required
                autofocus
                autocomplete="name"
                placeholder="Contoh: Budi Santoso"
                :invalid="$errors->has('name')"
            />
            <x-input-error :messages="$errors->get('name')" class="mt-1" />
        </div>

        <div>
            <x-form-label for="email" value="Alamat Email Usaha" :required="true" />
            <x-input
                id="email"
                name="email"
                type="email"
                :value="old('email')"
                required
                autocomplete="username"
                placeholder="nama@usaha.id"
                :invalid="$errors->has('email')"
            />
            <x-input-error :messages="$errors->get('email')" class="mt-1" />
        </div>

        <div>
            <x-form-label for="password" value="Kata Sandi" :required="true" />
            <x-input
                id="password"
                name="password"
                type="password"
                required
                autocomplete="new-password"
                placeholder="Minimal 8 karakter"
                :invalid="$errors->has('password')"
            />
            <x-input-error :messages="$errors->get('password')" class="mt-1" />
        </div>

        <div>
            <x-form-label for="password_confirmation" value="Konfirmasi Kata Sandi" :required="true" />
            <x-input
                id="password_confirmation"
                name="password_confirmation"
                type="password"
                required
                autocomplete="new-password"
                placeholder="Ulangi kata sandi"
                :invalid="$errors->has('password_confirmation')"
            />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1" />
        </div>

        <div class="pt-2">
            <x-button type="submit" variant="primary" class="w-full h-11 justify-center">
                Daftar & Lanjutkan Onboarding
            </x-button>
        </div>

        <div class="mt-6 text-center text-sm text-neutral-600">
            Sudah memiliki akun usaha?
            <a href="{{ route('login') }}" class="font-semibold text-brand-700 hover:text-brand-800 hover:underline">
                Masuk ke Akun
            </a>
        </div>
    </form>
</x-layouts.auth>

