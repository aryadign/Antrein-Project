<x-layouts.auth title="Lupa Kata Sandi" heading="Atur Ulang Kata Sandi" subheading="Masukkan alamat email akun Anda. Kami akan mengirimkan tautan untuk mengatur ulang kata sandi Anda.">
    @if (session('status'))
        <div class="mb-4 rounded-md bg-emerald-50 p-4 text-sm font-medium text-emerald-800 border border-emerald-200">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
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
                placeholder="nama@usaha.id"
                :invalid="$errors->has('email')"
            />
            <x-input-error :messages="$errors->get('email')" class="mt-1" />
        </div>

        <div class="pt-2">
            <x-button type="submit" variant="primary" class="w-full h-11 justify-center">
                Kirim Tautan Atur Ulang
            </x-button>
        </div>

        <div class="mt-6 text-center text-sm text-neutral-600">
            Ingat kata sandi Anda?
            <a href="{{ route('login') }}" class="font-semibold text-brand-700 hover:text-brand-800 hover:underline">
                Kembali ke Halaman Masuk
            </a>
        </div>
    </form>
</x-layouts.auth>

