@php
    $joinedDateFor = fn ($membership): string => ($membership->joined_at ?? $membership->created_at)->translatedFormat('d M Y');
    $initialsFor = fn ($membership): string => Str::upper(Str::substr($membership->user->name, 0, 2));
    $hasCreateErrors = $errors->hasAny(['name', 'email', 'business_id', 'business_counter_id']);
@endphp

<x-layouts.merchant title="Kelola Staf & Akun Operator">
    <div
        class="space-y-5 sm:space-y-6"
        x-data="{
            createModalOpen: {{ $hasCreateErrors ? 'true' : 'false' }},
            pwdModalOpen: false,
            pwdModalAction: '',
            pwdModalStaffName: '',
            pwdModalStaffEmail: '',
            selectedBranchId: '{{ (string) old('business_id', $business->id) }}',
            countersByBranch: @js($countersByBranch),
            get branchCounters() {
                return this.countersByBranch[String(this.selectedBranchId)] || [];
            },
            openPasswordModal(actionUrl, name, email) {
                this.pwdModalAction = actionUrl;
                this.pwdModalStaffName = name;
                this.pwdModalStaffEmail = email;
                this.pwdModalOpen = true;
            }
        }"
        @keydown.escape.window="createModalOpen = false; pwdModalOpen = false"
    >
        {{-- Header --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="min-w-0">
                <h1 class="text-h2 font-bold text-neutral-950">Kelola Staf &amp; Akun Operator</h1>
                <p class="mt-1 text-body text-neutral-600">
                    Buat akun login staf operator secara langsung, atur cabang &amp; loket tugas, serta kelola kredensial akses mereka.
                </p>
            </div>
            <button
                type="button"
                @click="createModalOpen = true"
                class="inline-flex h-11 w-full shrink-0 items-center justify-center gap-2 rounded-lg bg-brand-700 px-4 text-sm font-bold text-white shadow-sm transition hover:bg-brand-800 sm:w-auto"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" /></svg>
                Tambah Akun Staf
            </button>
        </div>

        {{-- Notifications --}}
        @if (session('status'))
            <div class="flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-800" role="status">
                <svg class="mt-0.5 w-5 h-5 shrink-0 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <p class="flex-1">{{ session('status') }}</p>
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800" role="alert">
                <p class="mb-1 font-semibold">Gagal memproses permohonan:</p>
                <ul class="list-inside list-disc space-y-0.5 text-xs">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Stats --}}
        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            <x-stat-tile label="Total Staf" :value="$memberships->count()" hint="Termasuk Pemilik Usaha" />
            <x-stat-tile label="Operator Aktif" :value="$activeStaffCount" hint="Dapat mengoperasikan antrean" tone="success" />
            <x-stat-tile label="Cabang Terkelola" :value="$branches->count()" hint="Unit usaha dalam cakupan" tone="brand" />
            <div class="min-w-0 rounded-xl border border-neutral-200 bg-white p-3.5 shadow-xs sm:p-4">
                <p class="truncate text-caption font-semibold text-neutral-500">Pemilik Usaha</p>
                <p class="mt-1 truncate text-base font-bold text-neutral-950 sm:text-lg">{{ $ownerMembership?->user->name ?? 'Owner' }}</p>
                <p class="mt-0.5 truncate text-[11px] text-neutral-500">Pemegang otoritas penuh</p>
            </div>
        </div>

        {{-- Role & Permission Guide --}}
        <section class="rounded-xl border border-neutral-200 bg-white p-4 shadow-xs sm:p-5">
            <h3 class="mb-3 flex items-center gap-2 text-sm font-semibold text-neutral-900">
                <svg class="h-4 w-4 text-brand-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                Panduan Hak Akses &amp; Kredensial Staf
            </h3>
            <div class="grid grid-cols-1 gap-3 text-xs text-neutral-600 md:grid-cols-2">
                <div class="flex gap-3 rounded-lg border border-neutral-200 bg-neutral-50 p-3">
                    <span class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-purple-100 text-purple-700">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                    </span>
                    <div>
                        <p class="mb-1 font-semibold text-neutral-900">Pemilik Usaha (Owner)</p>
                        <p class="leading-relaxed">Mengatur profil usaha, cabang, layanan, laporan &amp; ekspor, serta membuat akun login operator langsung untuk setiap cabang tanpa tautan undangan.</p>
                    </div>
                </div>
                <div class="flex gap-3 rounded-lg border border-neutral-200 bg-neutral-50 p-3">
                    <span class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-blue-100 text-blue-700">
                        <x-customer.icon name="users" class="size-4" />
                    </span>
                    <div>
                        <p class="mb-1 font-semibold text-neutral-900">Staf Operasional (Operator)</p>
                        <p class="leading-relaxed">Login memakai email &amp; password yang dibuat Pemilik. Fokus memanggil, melayani, dan menyelesaikan antrean serta tiket walk-in pada loket tugasnya.</p>
                    </div>
                </div>
            </div>
        </section>

        {{-- Team Members --}}
        <section class="space-y-3">
            <div class="flex items-end justify-between gap-3">
                <div class="min-w-0">
                    <h2 class="text-base font-bold text-neutral-900">Daftar Akun Tim &amp; Kredensial Operator</h2>
                    <p class="mt-0.5 text-xs text-neutral-500">Pemilik dapat meninjau atau memperbarui password login operator kapan saja.</p>
                </div>
                <span class="shrink-0 text-xs font-medium text-neutral-500 tabular-nums">{{ $memberships->count() }} Anggota</span>
            </div>

            @if ($memberships->isEmpty())
                <x-empty-state title="Belum ada data anggota tim terdaftar." description="Buat akun staf operator agar petugas loket bisa langsung login dan melayani antrean." />
            @else
                {{-- Mobile: card list --}}
                <ul class="space-y-3 md:hidden" aria-label="Daftar anggota tim">
                    @foreach ($memberships as $membership)
                        @php
                            $staffPlainPassword = $membership->isOwner() ? null : $membership->getDecryptedStaffPassword();
                        @endphp
                        <li class="rounded-xl border border-neutral-200 bg-white p-4 shadow-xs">
                            <div class="flex items-start gap-3">
                                <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-brand-100 text-xs font-bold text-brand-700">{{ $initialsFor($membership) }}</span>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate font-semibold text-neutral-900">{{ $membership->user->name }}</p>
                                    <p class="truncate text-xs text-neutral-500">{{ $membership->user->email }}</p>
                                    <div class="mt-2 flex flex-wrap items-center gap-1.5">
                                        @if ($membership->isOwner())
                                            <span class="inline-flex items-center rounded-full border border-purple-200 bg-purple-50 px-2.5 py-0.5 text-xs font-semibold text-purple-700">Pemilik Usaha</span>
                                        @else
                                            <span class="inline-flex items-center rounded-full border border-blue-200 bg-blue-50 px-2.5 py-0.5 text-xs font-semibold text-blue-700">Operator Antrean</span>
                                        @endif
                                        <x-status-badge :status="$membership->is_active ? 'active' : 'inactive'" size="sm" />
                                    </div>
                                    <div class="mt-2 flex flex-wrap gap-1.5 text-[11px]">
                                        <span class="rounded-md bg-neutral-100 px-2 py-0.5 font-medium text-neutral-700">{{ $membership->business?->name ?? $business->name }}</span>
                                        @foreach ($membership->assignedCounters as $assignedCounter)
                                            <span class="rounded-md bg-brand-50 px-2 py-0.5 font-semibold text-brand-700">{{ $assignedCounter->name }}</span>
                                        @endforeach
                                    </div>
                                    <p class="mt-1.5 text-[11px] text-neutral-500">Bergabung {{ $joinedDateFor($membership) }}</p>
                                </div>
                            </div>

                            @unless ($membership->isOwner())
                                <div x-data="{ showPwd: false }" class="mt-3 flex items-center justify-between gap-2 rounded-lg border border-neutral-200 bg-neutral-50 px-3 py-2 text-xs">
                                    <div class="min-w-0">
                                        <span class="block text-[10px] font-semibold uppercase tracking-wider text-neutral-400">Password Login</span>
                                        <span x-show="!showPwd" class="font-mono font-bold tracking-widest text-neutral-700">********</span>
                                        <span x-show="showPwd" x-cloak class="font-mono font-bold text-neutral-900 select-all">{{ $staffPlainPassword ?? 'Belum disimpan — klik Ubah Password' }}</span>
                                    </div>
                                    <button type="button" @click="showPwd = !showPwd" class="shrink-0 rounded-md border border-neutral-300 bg-white px-2.5 py-1 text-[11px] font-semibold text-neutral-700 hover:bg-neutral-100">
                                        <span x-text="showPwd ? 'Sembunyikan' : 'Lihat'"></span>
                                    </button>
                                </div>

                                <div class="mt-3 grid grid-cols-3 gap-2">
                                    <button
                                        type="button"
                                        @click="openPasswordModal(@js(route('merchant.staff.password', $membership)), @js($membership->user->name), @js($membership->user->email))"
                                        class="inline-flex h-11 items-center justify-center rounded-lg border border-brand-200 bg-brand-50 px-2 text-xs font-semibold text-brand-700 hover:bg-brand-100"
                                    >
                                        Ubah Sandi
                                    </button>
                                    <form method="POST" action="{{ route('merchant.staff.toggle-status', $membership) }}" onsubmit="return confirm('Apakah Anda yakin ingin mengubah status aktif staf ini?')">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="inline-flex h-11 w-full items-center justify-center rounded-lg border px-2 text-xs font-semibold transition-colors {{ $membership->is_active ? 'border-amber-300 text-amber-700 hover:bg-amber-50' : 'border-emerald-300 text-emerald-700 hover:bg-emerald-50' }}">
                                            {{ $membership->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('merchant.staff.destroy', $membership) }}" onsubmit="return confirm(@js('Apakah Anda yakin ingin menghapus staf '.$membership->user->name.' dari unit usaha? Akses ke antrean akan langsung dicabut.'))">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex h-11 w-full items-center justify-center rounded-lg border border-rose-200 px-2 text-xs font-semibold text-rose-700 transition-colors hover:bg-rose-50">
                                            Hapus
                                        </button>
                                    </form>
                                </div>
                            @endunless
                        </li>
                    @endforeach
                </ul>

                {{-- Tablet & desktop: table --}}
                <div class="hidden overflow-x-auto rounded-xl border border-neutral-200 bg-white shadow-xs md:block">
                    <table class="w-full border-collapse text-left text-sm">
                        <thead>
                            <tr class="border-b border-neutral-200 bg-neutral-50/75 text-xs font-semibold uppercase tracking-wider text-neutral-600">
                                <th scope="col" class="px-5 py-3">Nama &amp; Email</th>
                                <th scope="col" class="px-5 py-3">Cabang &amp; Loket</th>
                                <th scope="col" class="px-5 py-3">Kredensial Login</th>
                                <th scope="col" class="px-5 py-3">Status</th>
                                <th scope="col" class="px-5 py-3 text-right">Tindakan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-200">
                            @foreach ($memberships as $membership)
                                @php
                                    $staffPlainPassword = $membership->isOwner() ? null : $membership->getDecryptedStaffPassword();
                                @endphp
                                <tr class="transition-colors hover:bg-neutral-50/50">
                                    <td class="px-5 py-3.5">
                                        <div class="flex items-center gap-3">
                                            <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-brand-100 text-xs font-bold text-brand-700">{{ $initialsFor($membership) }}</span>
                                            <div class="min-w-0">
                                                <div class="flex items-center gap-2">
                                                    <p class="font-semibold text-neutral-900">{{ $membership->user->name }}</p>
                                                    @if ($membership->isOwner())
                                                        <span class="inline-flex items-center rounded-full border border-purple-200 bg-purple-50 px-2 py-0.5 text-[10px] font-bold text-purple-700">Pemilik</span>
                                                    @else
                                                        <span class="inline-flex items-center rounded-full border border-blue-200 bg-blue-50 px-2 py-0.5 text-[10px] font-bold text-blue-700">Operator</span>
                                                    @endif
                                                </div>
                                                <p class="text-xs text-neutral-500">{{ $membership->user->email }}</p>
                                                <p class="text-[11px] text-neutral-400 tabular-nums">Bergabung {{ $joinedDateFor($membership) }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-5 py-3.5">
                                        <p class="text-xs font-semibold text-neutral-800">{{ $membership->business?->name ?? $business->name }}</p>
                                        <div class="mt-1 flex flex-wrap gap-1">
                                            @forelse ($membership->assignedCounters as $assignedCounter)
                                                <span class="inline-flex items-center rounded-md bg-brand-50 px-2 py-0.5 text-[11px] font-semibold text-brand-700 ring-1 ring-brand-200">
                                                    {{ $assignedCounter->name }} ({{ $assignedCounter->code }})
                                                </span>
                                            @empty
                                                <span class="text-[11px] text-neutral-400">{{ $membership->isOwner() ? 'Akses manajemen penuh' : 'Semua loket cabang' }}</span>
                                            @endforelse
                                        </div>
                                    </td>
                                    <td class="px-5 py-3.5">
                                        @if ($membership->isOwner())
                                            <span class="text-xs italic text-neutral-400">Dikelola di Pengaturan Akun</span>
                                        @else
                                            <div x-data="{ showPwd: false }" class="inline-flex items-center gap-2">
                                                <span x-show="!showPwd" class="rounded bg-neutral-100 px-2 py-1 font-mono text-xs tracking-widest text-neutral-700">********</span>
                                                <span x-show="showPwd" x-cloak class="rounded bg-brand-50 px-2 py-1 font-mono text-xs font-bold text-brand-900 select-all">{{ $staffPlainPassword ?? 'Belum tersimpan' }}</span>
                                                <button
                                                    type="button"
                                                    @click="showPwd = !showPwd"
                                                    class="inline-flex h-8 items-center rounded-md border border-neutral-200 bg-white px-2 text-[11px] font-semibold text-neutral-600 transition hover:bg-neutral-50 hover:text-neutral-900"
                                                >
                                                    <span x-text="showPwd ? 'Sembunyi' : 'Lihat'"></span>
                                                </button>
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3.5">
                                        <x-status-badge :status="$membership->is_active ? 'active' : 'inactive'" size="sm" />
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-3.5 text-right">
                                        @if ($membership->isOwner())
                                            <span class="text-xs italic text-neutral-400">Akun Utama</span>
                                        @else
                                            <div class="inline-flex items-center justify-end gap-1.5">
                                                <button
                                                    type="button"
                                                    @click="openPasswordModal(@js(route('merchant.staff.password', $membership)), @js($membership->user->name), @js($membership->user->email))"
                                                    class="inline-flex h-9 items-center rounded-md border border-brand-200 bg-brand-50 px-2.5 text-xs font-semibold text-brand-700 transition-colors hover:bg-brand-100"
                                                >
                                                    Ubah Sandi
                                                </button>
                                                <form method="POST" action="{{ route('merchant.staff.toggle-status', $membership) }}" onsubmit="return confirm('Apakah Anda yakin ingin mengubah status aktif staf ini?')">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="inline-flex h-9 items-center rounded-md border px-2.5 text-xs font-semibold transition-colors {{ $membership->is_active ? 'border-amber-300 text-amber-700 hover:bg-amber-50' : 'border-emerald-300 text-emerald-700 hover:bg-emerald-50' }}">
                                                        {{ $membership->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                                    </button>
                                                </form>
                                                <form method="POST" action="{{ route('merchant.staff.destroy', $membership) }}" onsubmit="return confirm(@js('Apakah Anda yakin ingin menghapus staf '.$membership->user->name.' dari unit usaha? Akses ke antrean akan langsung dicabut.'))">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="inline-flex h-9 items-center rounded-md border border-rose-200 px-2.5 text-xs font-semibold text-rose-700 transition-colors hover:bg-rose-50">Hapus</button>
                                                </form>
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        {{-- Modal: Create Direct Operator Account --}}
        <div
            x-show="createModalOpen"
            x-cloak
            class="fixed inset-0 z-50 flex items-end justify-center bg-neutral-950/50 p-0 sm:items-center sm:p-4"
            role="dialog"
            aria-modal="true"
            aria-labelledby="create-staff-modal-title"
        >
            <div @click.away="createModalOpen = false" class="max-h-[92dvh] w-full max-w-lg overflow-y-auto rounded-t-2xl bg-white shadow-2xl sm:rounded-2xl">
                <div class="flex items-center justify-between gap-3 border-b border-neutral-200 py-3 pl-5 pr-2 sm:pl-6">
                    <div>
                        <h3 id="create-staff-modal-title" class="text-h3 font-bold text-neutral-900">Buat Akun Staf Operator</h3>
                        <p class="mt-0.5 text-xs text-neutral-500">Akun langsung aktif dan siap digunakan login oleh petugas loket.</p>
                    </div>
                    <button type="button" @click="createModalOpen = false" class="inline-flex size-11 shrink-0 items-center justify-center rounded-lg text-neutral-400 transition hover:bg-neutral-100 hover:text-neutral-600" aria-label="Tutup">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <form method="POST" action="{{ route('merchant.staff.store') }}" class="space-y-4 p-5 sm:p-6">
                    @csrf

                    <div>
                        <x-form-label for="staff-name" value="Nama Lengkap Staf" :required="true" />
                        <x-input id="staff-name" name="name" type="text" :value="old('name')" required placeholder="Contoh: Made Putra" :error="$errors->has('name')" />
                        <x-input-error :messages="$errors->get('name')" class="mt-1" />
                    </div>

                    <div>
                        <x-form-label for="staff-email" value="Alamat Email Login Staf" :required="true" />
                        <x-input id="staff-email" name="email" type="email" :value="old('email')" required placeholder="operator@tokoanda.id" :error="$errors->has('email')" />
                        <x-input-error :messages="$errors->get('email')" class="mt-1" />
                    </div>

                    <div>
                        <x-form-label for="staff-password" value="Password Awal Staf" :required="true" />
                        <x-password-input id="staff-password" name="password" required placeholder="Minimal 8 karakter" :error="$errors->has('password')" />
                        <x-input-error :messages="$errors->get('password')" class="mt-1" />
                        <p class="mt-1 text-[11px] text-neutral-500">Kredensial ini disimpan terenkripsi dan bisa dilihat kembali di daftar staf.</p>
                    </div>

                    @if ($branches->count() > 1)
                        <div>
                            <x-form-label for="staff-branch" value="Cabang Penugasan" :required="true" />
                            <x-select id="staff-branch" name="business_id" x-model="selectedBranchId">
                                @foreach ($branches as $branchOption)
                                    <option value="{{ $branchOption->id }}">{{ $branchOption->name }}</option>
                                @endforeach
                            </x-select>
                            <x-input-error :messages="$errors->get('business_id')" class="mt-1" />
                        </div>
                    @else
                        <input type="hidden" name="business_id" value="{{ $business->id }}">
                    @endif

                    <div>
                        <x-form-label for="staff-counter" value="Loket Penugasan Awal (Opsional)" />
                        <select id="staff-counter" name="business_counter_id" class="h-11 w-full rounded-lg border-neutral-300 text-sm focus:border-brand-500 focus:ring-brand-500">
                            <option value="">Otomatis, loket aktif pertama di cabang</option>
                            <template x-for="counter in branchCounters" :key="counter.id">
                                <option :value="counter.id" x-text="counter.name + ' (' + counter.code + ')'"></option>
                            </template>
                        </select>
                        <x-input-error :messages="$errors->get('business_counter_id')" class="mt-1" />
                    </div>

                    <div class="flex flex-col-reverse gap-2 pt-3 sm:flex-row sm:justify-end sm:gap-3">
                        <x-button type="button" variant="secondary" class="h-11" @click="createModalOpen = false">
                            Batal
                        </x-button>
                        <x-button type="submit" variant="primary" class="h-11">
                            Simpan &amp; Buat Akun Staf
                        </x-button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Modal: Update Operator Password --}}
        <div
            x-show="pwdModalOpen"
            x-cloak
            class="fixed inset-0 z-50 flex items-end justify-center bg-neutral-950/50 p-0 sm:items-center sm:p-4"
            role="dialog"
            aria-modal="true"
            aria-labelledby="pwd-modal-title"
        >
            <div @click.away="pwdModalOpen = false" class="w-full max-w-md rounded-t-2xl bg-white shadow-2xl sm:rounded-2xl">
                <div class="flex items-center justify-between gap-3 border-b border-neutral-200 py-3 pl-5 pr-2 sm:pl-6">
                    <div class="min-w-0">
                        <h3 id="pwd-modal-title" class="text-h3 font-bold text-neutral-900">Perbarui Password Operator</h3>
                        <p class="mt-0.5 truncate text-xs text-neutral-500"><span x-text="pwdModalStaffName"></span> (<span x-text="pwdModalStaffEmail"></span>)</p>
                    </div>
                    <button type="button" @click="pwdModalOpen = false" class="inline-flex size-11 shrink-0 items-center justify-center rounded-lg text-neutral-400 transition hover:bg-neutral-100 hover:text-neutral-600" aria-label="Tutup">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <form method="POST" :action="pwdModalAction" class="space-y-4 p-5 sm:p-6">
                    @csrf
                    @method('PATCH')

                    <div>
                        <x-form-label for="new-staff-password" value="Password Baru Operator" :required="true" />
                        <x-password-input id="new-staff-password" name="password" required placeholder="Minimal 8 karakter" />
                    </div>

                    <div class="flex flex-col-reverse gap-2 pt-2 sm:flex-row sm:justify-end sm:gap-3">
                        <x-button type="button" variant="secondary" class="h-11" @click="pwdModalOpen = false">Batal</x-button>
                        <x-button type="submit" variant="primary" class="h-11">Simpan Password Baru</x-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-layouts.merchant>
