@php
    $initialType = old('account_type', $defaultAccountType ?? 'merchant');
    $initialType = in_array($initialType, ['customer', 'merchant'], true) ? $initialType : 'merchant';

    $accountTypes = [
        'customer' => [
            'label' => 'Pelanggan',
            'icon' => 'ticket',
            'summary' => 'Ambil hingga 3 antrean aktif sekaligus, pantau tiket digital lewat kode QR, dan simpan riwayat kunjungan Anda.',
        ],
        'merchant' => [
            'label' => 'Pemilik UMKM',
            'icon' => 'store',
            'summary' => 'Setelah mendaftar, Anda dipandu mengisi profil usaha, jam operasional, dan layanan. Admin meninjau pendaftaran sebelum toko tampil.',
        ],
    ];
@endphp

<x-layouts.auth title="Daftar Akun" heading="Buat Akun AntreIn" subheading="Pilih jenis akun sebagai Pelanggan untuk mengambil antrean atau Pemilik UMKM untuk mengelola toko.">
    <div x-data="{ accountType: '{{ $initialType }}', submitting: false }">
        <div class="grid grid-cols-2 gap-1 rounded-lg bg-neutral-100 p-1" role="tablist" aria-label="Jenis akun">
            @foreach ($accountTypes as $typeKey => $type)
                <button
                    type="button"
                    role="tab"
                    id="account-tab-{{ $typeKey }}"
                    aria-controls="account-summary"
                    @click="accountType = '{{ $typeKey }}'"
                    :aria-selected="(accountType === '{{ $typeKey }}').toString()"
                    :class="accountType === '{{ $typeKey }}' ? 'bg-white text-brand-700 shadow-card' : 'text-neutral-600 hover:text-neutral-900'"
                    class="flex min-h-11 items-center justify-center gap-2 rounded-md px-3 text-label font-semibold transition-[background-color,color,box-shadow] duration-150 ease-out focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500"
                >
                    <x-customer.icon :name="$type['icon']" class="size-4" />
                    {{ $type['label'] }}
                </button>
            @endforeach
        </div>

        <div id="account-summary" role="tabpanel" class="mt-4 text-label text-pretty text-neutral-600" aria-live="polite">
            @foreach ($accountTypes as $typeKey => $type)
                <p x-show="accountType === '{{ $typeKey }}'" @if ($typeKey !== $initialType) style="display: none;" @endif>{{ $type['summary'] }}</p>
            @endforeach
        </div>

        <form method="POST" action="{{ route('register') }}" class="mt-6 space-y-5" @submit="submitting = true">
            @csrf
            <input type="hidden" name="account_type" :value="accountType" value="{{ $initialType }}">

            <div>
                <x-form-label for="name" :required="true">
                    <span x-text="accountType === 'customer' ? 'Nama Lengkap' : 'Nama Pemilik / Pengelola'">{{ $initialType === 'customer' ? 'Nama Lengkap' : 'Nama Pemilik / Pengelola' }}</span>
                </x-form-label>
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
                    :aria-invalid="$errors->has('name') ? 'true' : 'false'"
                    aria-describedby="name-error"
                />
                <x-input-error id="name-error" :messages="$errors->get('name')" />
            </div>

            <div>
                <x-form-label for="email" :required="true">
                    <span x-text="accountType === 'customer' ? 'Alamat Email' : 'Alamat Email Usaha'">{{ $initialType === 'customer' ? 'Alamat Email' : 'Alamat Email Usaha' }}</span>
                </x-form-label>
                <x-input
                    id="email"
                    name="email"
                    type="email"
                    :value="old('email')"
                    required
                    autocomplete="email"
                    inputmode="email"
                    placeholder="nama@email.id"
                    :error="$errors->has('email')"
                    :aria-invalid="$errors->has('email') ? 'true' : 'false'"
                    aria-describedby="email-error"
                />
                <x-input-error id="email-error" :messages="$errors->get('email')" />
            </div>

            <div>
                <x-form-label for="password" value="Kata Sandi" :required="true" />
                <x-password-input
                    id="password"
                    name="password"
                    required
                    minlength="8"
                    autocomplete="new-password"
                    placeholder="Buat kata sandi"
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
                <x-form-label for="password_confirmation" value="Konfirmasi Kata Sandi" :required="true" />
                <x-password-input
                    id="password_confirmation"
                    name="password_confirmation"
                    required
                    minlength="8"
                    autocomplete="new-password"
                    placeholder="Ulangi kata sandi"
                    :error="$errors->has('password_confirmation')"
                    :aria-invalid="$errors->has('password_confirmation') ? 'true' : 'false'"
                    aria-describedby="password_confirmation-error"
                />
                <x-input-error id="password_confirmation-error" :messages="$errors->get('password_confirmation')" />
            </div>

            <x-button type="submit" variant="primary" size="lg" class="w-full" x-bind:disabled="submitting">
                <svg x-show="submitting" x-cloak class="size-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                </svg>
                <span x-text="submitting ? 'Memproses…' : (accountType === 'customer' ? 'Daftar sebagai Pelanggan' : 'Daftar & Atur Usaha')">{{ $initialType === 'customer' ? 'Daftar sebagai Pelanggan' : 'Daftar & Atur Usaha' }}</span>
            </x-button>
        </form>

        <div class="mt-6 border-t border-neutral-200 pt-5 text-center text-label text-neutral-600">
            <p>
                Sudah memiliki akun?
                <a href="{{ route('login') }}" class="inline-flex min-h-11 items-center font-semibold text-brand-700 underline-offset-4 hover:text-brand-800 hover:underline">Masuk ke Akun</a>
            </p>
            <p class="-mt-2">
                Hanya ingin melihat toko?
                <a href="{{ route('customer.dashboard') }}" class="inline-flex min-h-11 items-center font-semibold text-brand-700 underline-offset-4 hover:text-brand-800 hover:underline">Jelajahi Toko</a>
            </p>
        </div>
    </div>
</x-layouts.auth>
