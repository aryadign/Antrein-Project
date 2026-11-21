<x-layouts.auth title="Lupa Kata Sandi" heading="Atur Ulang Kata Sandi" subheading="Masukkan email akun Anda. Kami kirimkan tautan untuk membuat kata sandi baru.">
    @include('auth.partials.flash')

    <form method="POST" action="{{ route('password.email') }}" class="space-y-5" x-data="{ submitting: false }" @submit="submitting = true">
        @csrf

        <div>
            <x-form-label for="email" value="Alamat Email Akun" :required="true" />
            <x-input
                id="email"
                name="email"
                type="email"
                :value="old('email')"
                required
                autofocus
                autocomplete="email"
                inputmode="email"
                placeholder="nama@email.id"
                :error="$errors->has('email')"
                :aria-invalid="$errors->has('email') ? 'true' : 'false'"
                aria-describedby="email-hint email-error"
            />
            @if ($errors->has('email'))
                <x-input-error id="email-error" :messages="$errors->get('email')" />
            @else
                <p id="email-hint" class="mt-1.5 text-caption text-neutral-600">Gunakan email yang sama dengan saat Anda mendaftar. Periksa juga folder spam.</p>
            @endif
        </div>

        <x-button type="submit" variant="primary" size="lg" class="w-full" x-bind:disabled="submitting">
            <span x-text="submitting ? 'Mengirim…' : 'Kirim Tautan Atur Ulang'">Kirim Tautan Atur Ulang</span>
        </x-button>
    </form>

    <div class="mt-6 border-t border-neutral-200 pt-5 text-center text-label text-neutral-600">
        <a href="{{ route('login') }}" class="inline-flex min-h-11 items-center gap-1.5 font-semibold text-brand-700 underline-offset-4 hover:text-brand-800 hover:underline">
            <x-customer.icon name="arrow-left" class="size-4" />
            Kembali ke Halaman Masuk
        </a>
    </div>
</x-layouts.auth>
