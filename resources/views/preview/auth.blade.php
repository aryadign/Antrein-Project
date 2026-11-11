<x-layouts.auth title="Masuk Akun — Preview" heading="Masuk ke Akun Anda" subheading="Akses dashboard operasional atau panel administrasi.">
    <form class="space-y-4">
        <div>
            <x-form-label for="auth_email" value="Alamat Email" :required="true" />
            <x-input id="auth_email" type="email" placeholder="nama@usaha.id" value="owner@garasi.id" />
        </div>

        <div>
            <div class="flex items-center justify-between mb-1.5">
                <x-form-label for="auth_password" value="Kata Sandi" :required="true" class="mb-0" />
                <a href="#forgot" class="text-caption text-brand-700 hover:underline">Lupa kata sandi?</a>
            </div>
            <x-input id="auth_password" type="password" value="secret123" />
        </div>

        <div class="flex items-center justify-between">
            <x-checkbox label="Ingat saya di perangkat ini" />
        </div>

        <div class="pt-2">
            <x-button variant="primary" class="w-full h-11">
                Masuk ke Akun
            </x-button>
        </div>
    </form>
</x-layouts.auth>

