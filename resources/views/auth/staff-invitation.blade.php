<x-layouts.auth title="Undangan Staf Operasional" heading="Undangan Bergabung Staf" subheading="Bergabung sebagai operator antrean unit usaha di AntreIn.">
    @if ($status !== 'valid')
        <div class="space-y-4">
            <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-left">
                <div class="flex items-start gap-3">
                    <svg class="h-6 w-6 text-red-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                    </svg>
                    <div>
                        <h3 class="text-sm font-semibold text-red-900">
                            @if ($status === 'expired')
                                Undangan Telah Kedaluwarsa
                            @elseif ($status === 'revoked')
                                Undangan Telah Dicabut
                            @elseif ($status === 'accepted')
                                Undangan Sudah Digunakan
                            @elseif ($status === 'email_mismatch')
                                Akun Tidak Sesuai
                            @elseif ($status === 'is_admin')
                                Akses Dibatasi
                            @elseif ($status === 'already_member_other')
                                Batasan Keanggotaan UMKM
                            @else
                                Undangan Tidak Valid
                            @endif
                        </h3>
                        <p class="mt-1 text-sm text-red-700 leading-relaxed">{{ $message }}</p>
                    </div>
                </div>
            </div>

            @if ($status === 'email_mismatch' && auth()->check())
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-button type="submit" variant="secondary" class="h-11 w-full justify-center">
                        Keluar dari Akun {{ auth()->user()->email }}
                    </x-button>
                </form>
            @endif

            <div class="pt-2">
                <a href="{{ route('home') }}" class="inline-flex h-11 w-full justify-center items-center px-4 text-sm font-medium text-neutral-700 bg-neutral-100 hover:bg-neutral-200 rounded-lg transition-colors">
                    Kembali ke Beranda
                </a>
            </div>
        </div>
    @else
        <div class="space-y-6">
            {{-- Business Information Card --}}
            <div class="rounded-xl border border-brand-200 bg-brand-50/50 p-4">
                <div class="flex items-center gap-3">
                    @if ($business->logo_path)
                        <img src="{{ $business->getLogoUrl() }}" alt="{{ $business->name }}" class="size-12 shrink-0 rounded-lg border border-neutral-200 object-cover" />
                    @else
                        <div class="flex size-12 shrink-0 items-center justify-center rounded-lg bg-brand-600 text-lg font-bold text-white">
                            {{ Str::upper(Str::substr($business->name, 0, 1)) }}
                        </div>
                    @endif
                    <div class="min-w-0">
                        <span class="text-xs font-semibold tracking-wider text-brand-700 uppercase">Undangan Unit Usaha</span>
                        <h3 class="truncate text-base font-bold text-neutral-900">{{ $business->name }}</h3>
                        <p class="mt-0.5 text-xs text-neutral-600">Peran: <span class="font-semibold text-neutral-900">Staf Operasional (Operator)</span></p>
                    </div>
                </div>

                <dl class="mt-3 grid grid-cols-1 gap-2 border-t border-brand-200/60 pt-3 text-xs sm:grid-cols-2">
                    <div class="flex min-w-0 flex-col">
                        <dt class="text-neutral-500">Email tujuan</dt>
                        <dd class="truncate font-semibold text-neutral-900">{{ $invitation->email }}</dd>
                    </div>
                    <div class="flex flex-col">
                        <dt class="text-neutral-500">Kedaluwarsa</dt>
                        <dd class="font-semibold text-neutral-900">{{ $invitation->expires_at->diffForHumans() }}</dd>
                    </div>
                    <div class="flex flex-col sm:col-span-2">
                        <dt class="text-neutral-500">Loket awal</dt>
                        <dd class="font-semibold text-neutral-900">
                            @if ($invitation->counter && $invitation->counter->is_active)
                                {{ $invitation->counter->name }} ({{ $invitation->counter->code }})
                            @else
                                Ditentukan otomatis ke loket aktif pertama
                            @endif
                        </dd>
                    </div>
                </dl>
            </div>

            @if ($errors->any())
                <div class="rounded-lg bg-red-50 p-3 border border-red-200 text-sm text-red-700">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (auth()->check())
                {{-- Already logged in as matching email --}}
                <div class="bg-neutral-50 rounded-xl p-4 border border-neutral-200 space-y-3">
                    <p class="text-sm text-neutral-700">
                        Anda saat ini masuk sebagai <strong>{{ auth()->user()->name }}</strong> ({{ auth()->user()->email }}). Klik tombol di bawah untuk bergabung ke tim usaha ini.
                    </p>
                    <form method="POST" action="{{ route('invitations.accept', ['token' => $token]) }}">
                        @csrf
                        <x-button type="submit" variant="primary" class="w-full justify-center h-11">
                            Terima Undangan & Buka Konsol Antrean
                        </x-button>
                    </form>
                </div>
            @elseif ($userExists)
                {{-- Existing user: login & accept --}}
                <form method="POST" action="{{ route('invitations.accept', ['token' => $token]) }}" class="space-y-4">
                    @csrf
                    <div>
                        <x-form-label for="email_display" value="Alamat Email Terdaftar" />
                        <x-input id="email_display" type="email" :value="$invitation->email" disabled class="bg-neutral-100 text-neutral-500 cursor-not-allowed" />
                    </div>

                    <div>
                        <x-form-label for="password" value="Kata Sandi Akun Anda" :required="true" />
                        <x-password-input id="password" name="password" required autofocus placeholder="Masukkan kata sandi untuk masuk" :error="$errors->has('password')" />
                        <x-input-error :messages="$errors->get('password')" class="mt-1" />
                    </div>

                    <div class="pt-2">
                        <x-button type="submit" variant="primary" class="w-full justify-center h-11">
                            Masuk & Terima Undangan
                        </x-button>
                    </div>
                </form>
            @else
                {{-- New user: register & accept --}}
                <form method="POST" action="{{ route('invitations.accept', ['token' => $token]) }}" class="space-y-4">
                    @csrf
                    <div>
                        <x-form-label for="email_display" value="Alamat Email Undangan" />
                        <x-input id="email_display" type="email" :value="$invitation->email" disabled class="bg-neutral-100 text-neutral-500 cursor-not-allowed" />
                    </div>

                    <div>
                        <x-form-label for="name" value="Nama Lengkap Staf" :required="true" />
                        <x-input id="name" name="name" type="text" :value="old('name')" required autofocus placeholder="Contoh: Budi Santoso" :error="$errors->has('name')" />
                        <x-input-error :messages="$errors->get('name')" class="mt-1" />
                    </div>

                    <div>
                        <x-form-label for="password" value="Buat Kata Sandi Baru" :required="true" />
                        <x-password-input id="password" name="password" required autocomplete="new-password" placeholder="Minimal 8 karakter" :error="$errors->has('password')" />
                        <x-input-error :messages="$errors->get('password')" class="mt-1" />
                    </div>

                    <div>
                        <x-form-label for="password_confirmation" value="Konfirmasi Kata Sandi" :required="true" />
                        <x-password-input id="password_confirmation" name="password_confirmation" required autocomplete="new-password" placeholder="Ulangi kata sandi" />
                    </div>

                    <div class="pt-2">
                        <x-button type="submit" variant="primary" class="w-full justify-center h-11">
                            Daftar & Terima Undangan
                        </x-button>
                    </div>
                </form>
            @endif
        </div>
    @endif
</x-layouts.auth>

