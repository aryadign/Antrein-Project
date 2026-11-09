<x-layouts.auth title="Buat Kata Sandi Baru" heading="Kata Sandi Baru" subheading="Silakan masukkan kata sandi baru untuk akun Anda.">
    <form method="POST" action="{{ route('password.store') }}" class="space-y-4">
        @csrf

        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div>
            <x-form-label for="email" value="Alamat Email Akun" :required="true" />
            <x-input
                id="email"
                name="email"
                type="email"
                :value="old('email', $request->email)"
                required
                autofocus
                autocomplete="username"
                :invalid="$errors->has('email')"
            />
            <x-input-error :messages="$errors->get('email')" class="mt-1" />
        </div>

        <div>
            <x-form-label for="password" value="Kata Sandi Baru" :required="true" />
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
            <x-form-label for="password_confirmation" value="Konfirmasi Kata Sandi Baru" :required="true" />
            <x-input
                id="password_confirmation"
                name="password_confirmation"
                type="password"
                required
                autocomplete="new-password"
                placeholder="Ulangi kata sandi baru"
                :invalid="$errors->has('password_confirmation')"
            />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1" />
        </div>

        <div class="pt-2">
            <x-button type="submit" variant="primary" class="w-full h-11 justify-center">
                Simpan Kata Sandi Baru
            </x-button>
        </div>
    </form>
</x-layouts.auth>

