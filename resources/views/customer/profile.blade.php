<x-layouts.customer title="Profil Saya" activeNav="profil">
    @php
        $initials = collect(preg_split('/\s+/', trim((string) $profile['name'])))
            ->filter()
            ->take(2)
            ->map(fn ($word) => Str::upper(Str::substr($word, 0, 1)))
            ->implode('');
    @endphp

    <form method="POST" action="{{ route('customer.profile.update') }}" class="flex min-h-[calc(100vh-105px)] flex-col md:mx-auto md:min-h-0 md:max-w-2xl">
        @csrf

        {{-- Judul layar --}}
        <div class="flex flex-col gap-1 px-5 pt-8 pb-5 md:px-0 md:pt-2 md:pb-8">
            <h1 class="text-2xl font-bold leading-8 text-gray-900 md:text-3xl md:leading-10">Profil Saya</h1>
            <p class="text-xs leading-4 text-gray-700">Kelola data pribadi untuk antrean Anda.</p>
        </div>

        <div class="flex flex-1 flex-col justify-between gap-6 px-5 pb-5 md:px-0">
            <div class="flex flex-col gap-4 md:gap-5 md:rounded-2xl md:border md:border-gray-200 md:p-8">
                @if (session('status'))
                    <div class="rounded-xl bg-green-50 p-3 text-xs font-semibold leading-4 text-success-700" role="status">{{ session('status') }}</div>
                @endif

                {{-- Identitas pribadi --}}
                <div class="flex items-center gap-3.5 rounded-2xl border border-gray-200 bg-white p-4">
                    <div class="flex size-14 shrink-0 items-center justify-center rounded-full bg-brand-100 text-xl font-bold leading-7 text-brand-700">
                        {{ $initials !== '' ? $initials : '?' }}
                    </div>
                    <div class="flex min-w-0 flex-1 flex-col gap-1">
                        <p class="truncate text-base font-bold leading-6 text-gray-900">{{ $profile['name'] ?? 'Pelanggan AntreIn' }}</p>
                        <p class="text-xs leading-4 text-gray-700">Akun pribadi AntreIn</p>
                    </div>
                </div>

                @foreach ([
                    ['name' => 'name', 'label' => 'Nama lengkap', 'required' => true, 'type' => 'text', 'autocomplete' => 'name', 'placeholder' => 'Nama Anda'],
                    ['name' => 'phone', 'label' => 'Nomor WhatsApp', 'required' => false, 'type' => 'tel', 'autocomplete' => 'tel', 'placeholder' => '0812 3456 7890'],
                    ['name' => 'email', 'label' => 'Email', 'required' => false, 'type' => 'email', 'autocomplete' => 'email', 'placeholder' => 'nama@email.com'],
                ] as $field)
                    <div class="flex flex-col gap-1.5">
                        <label for="profile-{{ $field['name'] }}" class="flex items-center justify-between text-xs font-semibold leading-4 text-gray-900">
                            {{ $field['label'] }}
                            <span @class([
                                'rounded-full px-2 py-0.5 text-[10px] font-bold',
                                'bg-brand-50 text-brand-700' => $field['required'],
                                'bg-gray-100 text-gray-500' => ! $field['required'],
                            ])>{{ $field['required'] ? 'Wajib' : 'Opsional' }}</span>
                        </label>
                        <input
                            id="profile-{{ $field['name'] }}"
                            name="{{ $field['name'] }}"
                            type="{{ $field['type'] }}"
                            value="{{ old($field['name'], $profile[$field['name']]) }}"
                            autocomplete="{{ $field['autocomplete'] }}"
                            placeholder="{{ $field['placeholder'] }}"
                            @if ($field['name'] === 'name') required maxlength="100" @endif
                            @class([
                                'min-h-[46px] rounded-lg border bg-white p-3 text-sm leading-5 text-gray-700 placeholder:text-gray-400 transition focus:border-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-100',
                                'border-gray-200' => ! $errors->has($field['name']),
                                'border-danger-700' => $errors->has($field['name']),
                            ])
                        />
                        @error($field['name'])
                            <p class="text-xs leading-4 text-danger-700">{{ $message }}</p>
                        @enderror
                    </div>
                @endforeach

                <div class="flex flex-col gap-1 rounded-xl bg-brand-50 p-3 text-xs leading-4">
                    <p class="font-semibold text-brand-700">Data hanya untuk kebutuhan pribadi Anda</p>
                    <p class="text-gray-700">Informasi ini membantu pengisian antrean dan pemberitahuan Anda. Tidak ditampilkan kepada pelanggan lain.</p>
                </div>
            </div>

            {{-- Aksi utama --}}
            <button type="submit" class="flex h-12 w-full items-center md:w-56 md:self-end justify-center rounded-xl bg-brand-700 text-sm font-semibold text-white transition hover:bg-blue-900 active:scale-[0.99]">
                Simpan Profil Saya
            </button>
        </div>
    </form>
</x-layouts.customer>
