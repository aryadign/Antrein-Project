@php
    $initialType = old('account_type', $defaultAccountType ?? 'merchant');
    $initialType = in_array($initialType, ['customer', 'merchant'], true) ? $initialType : 'merchant';
@endphp

<x-layouts.auth title="Daftar Akun" heading="Buat Akun AntreIn" subheading="Pilih jenis akun sebagai Pelanggan untuk mengambil antrean atau Pemilik UMKM untuk mengelola toko.">
    <div x-data="{ accountType: '{{ $initialType }}' }" class="space-y-5">
        {{-- Account Type Switcher --}}
        <div class="grid grid-cols-2 gap-2 rounded-xl border border-neutral-200 bg-neutral-100 p-1">
            <button
                type="button"
                @click="accountType = 'customer'"
                :class="accountType === 'customer' ? 'bg-white text-brand-700 shadow-sm ring-1 ring-neutral-200/80' : 'text-neutral-600 hover:text-neutral-900'"
                class="flex min-h-11 items-center justify-center gap-2 rounded-lg px-3 text-sm font-semibold transition"
            >
                <x-customer.icon name="user" class="size-4" />
                Pelanggan
            </button>
            <button
                type="button"
                @click="accountType = 'merchant'"
                :class="accountType === 'merchant' ? 'bg-white text-brand-700 shadow-sm ring-1 ring-neutral-200/80' : 'text-neutral-600 hover:text-neutral-900'"
                class="flex min-h-11 items-center justify-center gap-2 rounded-lg px-3 text-sm font-semibold transition"
            >
                <x-customer.icon name="store" class="size-4" />
                Pemilik UMKM
            </button>
        </div>

        <div x-show="accountType === 'customer'" class="flex items-start gap-3 rounded-lg border border-brand-200 bg-brand-50/70 p-3.5 text-sm text-brand-900">
            <x-customer.icon name="ticket" class="mt-0.5 size-5 shrink-0 text-brand-700" />
            <p>Buat akun pelanggan untuk mengambil hingga <strong>3 antrean aktif sekaligus</strong>, memantau tiket digital via QR Code, dan menyimpan riwayat kunjungan.</p>
        </div>

        <div x-show="accountType === 'merchant'" class="flex items-start gap-3 rounded-lg border border-neutral-200 bg-neutral-50 p-3.5 text-sm text-neutral-700">
            <x-customer.icon name="store" class="mt-0.5 size-5 shrink-0 text-brand-700" />
            <p>Setelah mendaftar, Anda akan dipandu mengisi profil usaha, jam operasional, layanan, lalu pendaftaran akan ditinjau oleh Admin sebelum toko tampil.</p>
        </div>

        <form method="POST" action="{{ route('register') }}" class="space-y-4">
            @csrf
            <input type="hidden" name="account_type" :value="accountType">

            <div>
                <label for="name" class="mb-1.5 block text-sm font-medium text-neutral-800">
                    <span x-text="accountType === 'customer' ? 'Nama Lengkap' : 'Nama Pemilik / Pengelola'">Nama Pemilik / Pengelola</span>
                    <span class="text-red-500">*</span>
                </label>
                <x-input
                    id="name"
                    name="name"
                    type="text"
                    :value="old('name')"
                    required
                    autofocus
                    autocomplete="name"
                    placeholder="Contoh: Raka Pratama"
                    :error="$errors->has('name')"
                />
                <x-input-error :messages="$errors->get('name')" class="mt-1" />
            </div>

            <div>
                <label for="email" class="mb-1.5 block text-sm font-medium text-neutral-800">
                    <span x-text="accountType === 'customer' ? 'Alamat Email' : 'Alamat Email Usaha'">Alamat Email</span>
                    <span class="text-red-500">*</span>
                </label>
                <x-input
                    id="email"
                    name="email"
                    type="email"
                    :value="old('email')"
                    required
                    autocomplete="username"
                    placeholder="nama@email.id"
                    :error="$errors->has('email')"
                />
                <x-input-error :messages="$errors->get('email')" class="mt-1" />
            </div>

            <div>
                <x-form-label for="password" value="Kata Sandi" :required="true" />
                <x-password-input
                    id="password"
                    name="password"
                    required
                    autocomplete="new-password"
                    placeholder="Minimal 8 karakter"
                    :error="$errors->has('password')"
                />
                <x-input-error :messages="$errors->get('password')" class="mt-1" />
            </div>

            <div>
                <x-form-label for="password_confirmation" value="Konfirmasi Kata Sandi" :required="true" />
                <x-password-input
                    id="password_confirmation"
                    name="password_confirmation"
                    required
                    autocomplete="new-password"
                    placeholder="Ulangi kata sandi"
                    :error="$errors->has('password_confirmation')"
                />
                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1" />
            </div>

            <div class="pt-2">
                <x-button type="submit" variant="primary" class="w-full h-11 justify-center">
                    <span x-text="accountType === 'customer' ? 'Daftar sebagai Pelanggan' : 'Daftar & Lanjutkan Onboarding'">Daftar Akun</span>
                </x-button>
            </div>

            <div class="mt-2 text-center text-sm text-neutral-600">
                Sudah memiliki akun?
                <a href="{{ route('login') }}" class="inline-flex min-h-11 items-center font-semibold text-brand-700 hover:text-brand-800 hover:underline">
                    Masuk ke Akun
                </a>
            </div>
        </form>

        <p class="border-t border-neutral-200 pt-4 text-center text-caption text-neutral-500">
            Ingin melihat daftar UMKM yang tersedia?
            <a href="{{ route('customer.dashboard') }}" class="inline-flex min-h-11 items-center font-semibold text-brand-700 hover:underline">Jelajahi toko di sini</a>
        </p>
    </div>
</x-layouts.auth>
