<x-layouts.auth title="Buat Kata Sandi Baru" heading="Kata Sandi Baru" subheading="Buat kata sandi baru untuk akun Anda, lalu masuk kembali.">
    @include('auth.partials.flash')

    <form method="POST" action="{{ route('password.store') }}" class="space-y-5" x-data="{ submitting: false }" @submit="submitting = true">
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
                autocomplete="username"
                inputmode="email"
                :error="$errors->has('email')"
                :aria-invalid="$errors->has('email') ? 'true' : 'false'"
                aria-describedby="email-error"
            />
            <x-input-error id="email-error" :messages="$errors->get('email')" />
        </div>

        <div>
            <x-form-label for="password" value="Kata Sandi Baru" :required="true" />
            <x-password-input
                id="password"
                name="password"
                required
                autofocus
                minlength="8"
                autocomplete="new-password"
                placeholder="Buat kata sandi baru"
                :error="$errors->has('password')"
                :aria-invalid="$errors->has('password') ? 'true' : 'false'"
                aria-describedby="password-hint password-error"
            />
            @if ($errors->has('password'))
                <x-input-error id="password-error" :messages="$errors->get('password')" />
            @else
                <p id="password-hint" class="mt-1.5 text-caption text-neutral-600">Gunakan minimal 8 karakter.</p>
            @endif
        </div>

        <div>
            <x-form-label for="password_confirmation" value="Konfirmasi Kata Sandi Baru" :required="true" />
            <x-password-input
                id="password_confirmation"
                name="password_confirmation"
                required
                minlength="8"
                autocomplete="new-password"
                placeholder="Ulangi kata sandi baru"
                :error="$errors->has('password_confirmation')"
                :aria-invalid="$errors->has('password_confirmation') ? 'true' : 'false'"
                aria-describedby="password_confirmation-error"
            />
            <x-input-error id="password_confirmation-error" :messages="$errors->get('password_confirmation')" />
        </div>

        <x-button type="submit" variant="primary" size="lg" class="w-full" x-bind:disabled="submitting">
            <span x-text="submitting ? 'Menyimpan…' : 'Simpan Kata Sandi Baru'">Simpan Kata Sandi Baru</span>
        </x-button>
    </form>

    <div class="mt-6 border-t border-neutral-200 pt-5 text-center text-label text-neutral-600">
        <a href="{{ route('login') }}" class="inline-flex min-h-11 items-center gap-1.5 font-semibold text-brand-700 underline-offset-4 hover:text-brand-800 hover:underline">
            <x-customer.icon name="arrow-left" class="size-4" />
            Kembali ke Halaman Masuk
        </a>
    </div>
</x-layouts.auth>
