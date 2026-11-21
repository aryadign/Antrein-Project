@php
    $invalidTitles = [
        'expired' => 'Undangan Telah Kedaluwarsa',
        'revoked' => 'Undangan Telah Dicabut',
        'accepted' => 'Undangan Sudah Digunakan',
        'email_mismatch' => 'Akun Tidak Sesuai',
        'is_admin' => 'Akses Dibatasi',
        'already_member_other' => 'Batasan Keanggotaan UMKM',
    ];
@endphp

<x-layouts.auth title="Undangan Staf Operasional" heading="Undangan Bergabung Staf" subheading="Bergabung sebagai operator antrean unit usaha di AntreIn.">
    @if ($status !== 'valid')
        <div class="flex items-start gap-3" role="alert">
            <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-rose-50 text-danger-700 ring-1 ring-rose-200">
                <x-customer.icon name="alert-triangle" class="size-5" />
            </span>
            <div class="min-w-0 flex-1">
                <h3 class="text-body font-semibold text-neutral-950">{{ $invalidTitles[$status] ?? 'Undangan Tidak Valid' }}</h3>
                <p class="mt-1 text-label text-pretty text-neutral-600">{{ $message }}</p>
            </div>
        </div>

        <div class="mt-6 space-y-2">
            @if ($status === 'email_mismatch' && auth()->check())
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-button type="submit" variant="primary" class="w-full">
                        Keluar dari Akun {{ auth()->user()->email }}
                    </x-button>
                </form>
            @endif

            <x-button variant="outline" :href="route('home')" class="w-full">Kembali ke Beranda</x-button>
        </div>
    @else
        <div class="flex items-center gap-3">
            @if ($business->logo_path)
                <img src="{{ $business->getLogoUrl() }}" alt="{{ $business->name }}" class="size-12 shrink-0 rounded-lg border border-neutral-200 object-cover" />
            @else
                <div class="flex size-12 shrink-0 items-center justify-center rounded-lg bg-brand-700 text-lg font-bold text-white" aria-hidden="true">
                    {{ Str::upper(Str::substr($business->name, 0, 1)) }}
                </div>
            @endif
            <div class="min-w-0">
                <h3 class="truncate text-body-lg font-bold text-neutral-950">{{ $business->name }}</h3>
                <p class="text-label text-neutral-600">Peran: <span class="font-semibold text-neutral-900">Staf Operasional (Operator)</span></p>
            </div>
        </div>

        <dl class="mt-4 grid grid-cols-1 gap-3 border-y border-neutral-200 py-4 text-label sm:grid-cols-2">
            <div class="min-w-0">
                <dt class="text-neutral-600">Email tujuan</dt>
                <dd class="truncate font-semibold text-neutral-900">{{ $invitation->email }}</dd>
            </div>
            <div>
                <dt class="text-neutral-600">Kedaluwarsa</dt>
                <dd class="font-semibold text-neutral-900">{{ $invitation->expires_at->diffForHumans() }}</dd>
            </div>
            <div class="sm:col-span-2">
                <dt class="text-neutral-600">Loket awal</dt>
                <dd class="font-semibold text-neutral-900">
                    @if ($invitation->counter && $invitation->counter->is_active)
                        {{ $invitation->counter->name }} ({{ $invitation->counter->code }})
                    @else
                        Ditentukan otomatis ke loket aktif pertama
                    @endif
                </dd>
            </div>
        </dl>

        @if ($errors->any())
            <div class="mt-5 flex items-start gap-2.5 rounded-lg bg-rose-50 px-3.5 py-3 text-label text-rose-800 ring-1 ring-rose-200" role="alert">
                <x-customer.icon name="alert-triangle" class="mt-0.5 size-4 shrink-0" />
                <ul class="space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="mt-5">
            @if (auth()->check())
                <p class="text-label text-pretty text-neutral-700">
                    Anda masuk sebagai <strong>{{ auth()->user()->name }}</strong> ({{ auth()->user()->email }}). Tekan tombol di bawah untuk bergabung ke tim usaha ini.
                </p>
                <form method="POST" action="{{ route('invitations.accept', ['token' => $token]) }}" class="mt-4">
                    @csrf
                    <x-button type="submit" variant="primary" size="lg" class="w-full">
                        Terima Undangan &amp; Buka Konsol Antrean
                    </x-button>
                </form>
            @elseif ($userExists)
                <form method="POST" action="{{ route('invitations.accept', ['token' => $token]) }}" class="space-y-5">
                    @csrf
                    <div>
                        <x-form-label for="email_display" value="Alamat Email Terdaftar" />
                        <x-input id="email_display" type="email" :value="$invitation->email" disabled />
                    </div>

                    <div>
                        <x-form-label for="password" value="Kata Sandi Akun Anda" :required="true" />
                        <x-password-input id="password" name="password" required autofocus autocomplete="current-password" placeholder="Masukkan kata sandi" :error="$errors->has('password')" />
                        <x-input-error :messages="$errors->get('password')" />
                    </div>

                    <x-button type="submit" variant="primary" size="lg" class="w-full">
                        Masuk &amp; Terima Undangan
                    </x-button>
                </form>
            @else
                <form method="POST" action="{{ route('invitations.accept', ['token' => $token]) }}" class="space-y-5">
                    @csrf
                    <div>
                        <x-form-label for="email_display" value="Alamat Email Undangan" />
                        <x-input id="email_display" type="email" :value="$invitation->email" disabled />
                    </div>

                    <div>
                        <x-form-label for="name" value="Nama Lengkap Staf" :required="true" />
                        <x-input id="name" name="name" type="text" :value="old('name')" required autofocus autocomplete="name" placeholder="Contoh: Budi Santoso" :error="$errors->has('name')" />
                        <x-input-error :messages="$errors->get('name')" />
                    </div>

                    <div>
                        <x-form-label for="password" value="Buat Kata Sandi Baru" :required="true" />
                        <x-password-input id="password" name="password" required minlength="8" autocomplete="new-password" placeholder="Buat kata sandi" :error="$errors->has('password')" />
                        @if ($errors->has('password'))
                            <x-input-error :messages="$errors->get('password')" />
                        @else
                            <p class="mt-1.5 text-caption text-neutral-600">Gunakan minimal 8 karakter.</p>
                        @endif
                    </div>

                    <div>
                        <x-form-label for="password_confirmation" value="Konfirmasi Kata Sandi" :required="true" />
                        <x-password-input id="password_confirmation" name="password_confirmation" required minlength="8" autocomplete="new-password" placeholder="Ulangi kata sandi" />
                    </div>

                    <x-button type="submit" variant="primary" size="lg" class="w-full">
                        Daftar &amp; Terima Undangan
                    </x-button>
                </form>
            @endif
        </div>
    @endif
</x-layouts.auth>
