<x-layouts.merchant title="Kelola Staf & Hak Akses — AntreIn">
    <div class="space-y-6">
        <!-- Header Section -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-h2 font-bold text-neutral-950">Kelola Staf & Hak Akses</h1>
                <p class="text-body text-neutral-600 mt-1">
                    Kelola anggota tim operasional untuk memanggil antrean dan melayani pelanggan.
                </p>
            </div>
            <div>
                <x-button variant="primary" onclick="document.getElementById('modal-invite-staff').showModal()" class="flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                    </svg>
                    Undang Staf Operator
                </x-button>
            </div>
        </div>

        <!-- Success Notifications -->
        @if (session('status'))
            <div class="rounded-xl bg-emerald-50 border border-emerald-200 p-4 text-sm font-medium text-emerald-800 flex items-start gap-3">
                <svg class="w-5 h-5 text-emerald-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div class="flex-1">
                    <p>{{ session('status') }}</p>
                </div>
            </div>
        @endif

        <!-- Invitation URL Flash Banner -->
        @if (session('invitation_url'))
            <div class="rounded-xl border border-brand-200 bg-brand-50/70 p-5 shadow-sm space-y-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex h-2 w-2 rounded-full bg-brand-600 animate-pulse"></span>
                        <h3 class="text-sm font-bold text-brand-900 uppercase tracking-wider">Tautan Undangan Siap Dibagikan</h3>
                    </div>
                    <span class="text-xs text-neutral-500 font-medium">Berlaku selama 72 jam</span>
                </div>
                <p class="text-xs text-neutral-600">
                    Kirim tautan di bawah ini kepada calon staf operasional (<strong>{{ session('invited_email') }}</strong>):
                </p>
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
                    <input
                        type="text"
                        readonly
                        value="{{ session('invitation_url') }}"
                        id="invitation-link-input"
                        class="flex-1 rounded-lg border border-neutral-300 bg-white px-3.5 py-2 text-xs font-mono text-neutral-800 select-all focus:outline-none focus:ring-2 focus:ring-brand-500"
                    />
                    <button
                        type="button"
                        onclick="copyInvitationLink()"
                        id="btn-copy-link"
                        class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand-600 px-4 py-2 text-xs font-semibold text-white hover:bg-brand-700 transition-colors shrink-0"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                        <span id="btn-copy-text">Salin Tautan</span>
                    </button>
                </div>
            </div>
            <script>
                function copyInvitationLink() {
                    const input = document.getElementById('invitation-link-input');
                    input.select();
                    navigator.clipboard.writeText(input.value);
                    const btnText = document.getElementById('btn-copy-text');
                    btnText.textContent = 'Tersalin!';
                    setTimeout(() => {
                        btnText.textContent = 'Salin Tautan';
                    }, 2500);
                }
            </script>
        @endif

        @if ($errors->any())
            <div class="rounded-xl bg-rose-50 border border-rose-200 p-4 text-sm text-rose-800">
                <p class="font-semibold mb-1">Gagal memproses permohonan:</p>
                <ul class="list-disc list-inside space-y-0.5 text-xs">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Stats Overview -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <x-stat-card
                title="Total Staf"
                :value="$memberships->count()"
                description="Termasuk Pemilik Usaha"
            />
            <x-stat-card
                title="Operator Aktif"
                :value="$activeStaffCount"
                description="Dapat mengoperasikan antrean"
            />
            <x-stat-card
                title="Undangan Tertunda"
                :value="$pendingInvitations->count()"
                description="Menunggu diterima calon staf"
            />
            <x-stat-card
                title="Pemilik Usaha"
                :value="$ownerMembership ? Str::limit($ownerMembership->user->name, 14) : 'Owner'"
                description="Pemegang otoritas penuh"
            />
        </div>

        <!-- Role & Permission Info Card -->
        <div class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-5 shadow-sm">
            <h3 class="text-sm font-semibold text-neutral-900 mb-2 flex items-center gap-2">
                <svg class="h-4 w-4 text-brand-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Panduan Hak Akses AntreIn
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs text-neutral-600">
                <div class="rounded-lg bg-neutral-50 p-3 border border-neutral-200">
                    <span class="font-semibold text-neutral-900 flex items-center gap-1.5 mb-1">
                        <span class="inline-block w-2 h-2 rounded-full bg-purple-600"></span>
                        Pemilik Usaha (Owner)
                    </span>
                    <p class="leading-relaxed">
                        Akses menyeluruh ke seluruh sistem: Kelola profil usaha, jam operasional, daftar layanan, unduh laporan & ekspor PDF/CSV, serta menambah dan menghapus staf.
                    </p>
                </div>
                <div class="rounded-lg bg-neutral-50 p-3 border border-neutral-200">
                    <span class="font-semibold text-neutral-900 flex items-center gap-1.5 mb-1">
                        <span class="inline-block w-2 h-2 rounded-full bg-blue-600"></span>
                        Staf Operasional (Operator)
                    </span>
                    <p class="leading-relaxed">
                        Fokus pada konsol antrean: Mengambil nomor antrean walk-in, memanggil nomor berikutnya, panggil ulang, memulai layanan, dan menyelesaikan tiket. Dilarang mengakses laporan dan pengaturan.
                    </p>
                </div>
            </div>
        </div>

        <!-- Staff List Section -->
        <x-card padding="none" class="overflow-hidden">
            <div class="px-5 py-4 border-b border-neutral-200 flex items-center justify-between">
                <div>
                    <h2 class="text-base font-bold text-neutral-900">Daftar Anggota Tim</h2>
                    <p class="text-xs text-neutral-500 mt-0.5">Daftar seluruh akun yang memiliki hak akses ke unit usaha Anda.</p>
                </div>
                <span class="text-xs font-medium text-neutral-500">{{ $memberships->count() }} Anggota</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="border-b border-neutral-200 bg-neutral-50/75 text-xs font-semibold text-neutral-600 uppercase tracking-wider">
                            <th scope="col" class="py-3 px-5">Nama & Email</th>
                            <th scope="col" class="py-3 px-5">Peran</th>
                            <th scope="col" class="py-3 px-5">Status</th>
                            <th scope="col" class="py-3 px-5">Bergabung</th>
                            <th scope="col" class="py-3 px-5 text-right">Tindakan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-200">
                        @forelse ($memberships as $membership)
                            <tr class="hover:bg-neutral-50/50 transition-colors">
                                <td class="py-3.5 px-5">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-100 font-bold text-brand-700 text-xs">
                                            {{ strtoupper(substr($membership->user->name, 0, 2)) }}
                                        </div>
                                        <div>
                                            <p class="font-semibold text-neutral-900">{{ $membership->user->name }}</p>
                                            <p class="text-xs text-neutral-500">{{ $membership->user->email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3.5 px-5">
                                    @if ($membership->isOwner())
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-purple-50 text-purple-700 border border-purple-200">
                                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                            </svg>
                                            Pemilik Usaha
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200">
                                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                            </svg>
                                            Operator Antrean
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-5">
                                    @if ($membership->is_active)
                                        <x-status-badge status="active" size="sm" />
                                    @else
                                        <x-status-badge status="inactive" size="sm" />
                                    @endif
                                </td>
                                <td class="py-3.5 px-5 text-xs text-neutral-500">
                                    {{ $membership->joined_at ? $membership->joined_at->translatedFormat('d M Y') : $membership->created_at->translatedFormat('d M Y') }}
                                </td>
                                <td class="py-3.5 px-5 text-right">
                                    @if ($membership->isOwner())
                                        <span class="text-xs text-neutral-400 italic">Akun Utama</span>
                                    @else
                                        <div class="inline-flex items-center justify-end gap-2">
                                            <form method="POST" action="{{ route('merchant.staff.toggle-status', $membership) }}" onsubmit="return confirm('Apakah Anda yakin ingin mengubah status aktif staf ini?')">
                                                @csrf
                                                @method('PATCH')
                                                <button
                                                    type="submit"
                                                    class="inline-flex items-center px-2.5 py-1 rounded border text-xs font-medium {{ $membership->is_active ? 'border-amber-300 text-amber-700 hover:bg-amber-50' : 'border-emerald-300 text-emerald-700 hover:bg-emerald-50' }} transition-colors"
                                                >
                                                    {{ $membership->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                                </button>
                                            </form>

                                            <form method="POST" action="{{ route('merchant.staff.destroy', $membership) }}" onsubmit="return confirm('Apakah Anda yakin ingin menghapus staf {{ $membership->user->name }} dari unit usaha? Akses ke antrean akan langsung dicabut.')">
                                                @csrf
                                                @method('DELETE')
                                                <button
                                                    type="submit"
                                                    class="inline-flex items-center px-2.5 py-1 rounded border border-rose-200 text-xs font-medium text-rose-700 hover:bg-rose-50 transition-colors"
                                                >
                                                    Hapus
                                                </button>
                                            </form>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-8 text-center text-neutral-500 text-sm">
                                    Belum ada data anggota tim terdaftar.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>

        <!-- Pending Invitations Section -->
        <x-card padding="none" class="overflow-hidden">
            <div class="px-5 py-4 border-b border-neutral-200 flex items-center justify-between">
                <div>
                    <h2 class="text-base font-bold text-neutral-900">Undangan Menunggu Konfirmasi</h2>
                    <p class="text-xs text-neutral-500 mt-0.5">Daftar calon staf yang telah diundang tetapi belum menerima undangan.</p>
                </div>
                <span class="text-xs font-medium text-neutral-500">{{ $pendingInvitations->count() }} Tertunda</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="border-b border-neutral-200 bg-neutral-50/75 text-xs font-semibold text-neutral-600 uppercase tracking-wider">
                            <th scope="col" class="py-3 px-5">Email Calon Staf</th>
                            <th scope="col" class="py-3 px-5">Peran Ditawarkan</th>
                            <th scope="col" class="py-3 px-5">Diundang Oleh</th>
                            <th scope="col" class="py-3 px-5">Kedaluwarsa</th>
                            <th scope="col" class="py-3 px-5 text-right">Tindakan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-200">
                        @forelse ($pendingInvitations as $invitation)
                            <tr class="hover:bg-neutral-50/50 transition-colors">
                                <td class="py-3.5 px-5 font-medium text-neutral-900">
                                    {{ $invitation->email }}
                                </td>
                                <td class="py-3.5 px-5">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-50 text-blue-700">
                                        Operator Antrean
                                    </span>
                                </td>
                                <td class="py-3.5 px-5 text-xs text-neutral-600">
                                    {{ $invitation->invitedBy->name ?? 'Pemilik' }}
                                </td>
                                <td class="py-3.5 px-5 text-xs text-neutral-500">
                                    <span title="{{ $invitation->expires_at->toIso8601String() }}">
                                        {{ $invitation->expires_at->diffForHumans() }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-5 text-right">
                                    <form method="POST" action="{{ route('merchant.staff.invitations.revoke', $invitation) }}" onsubmit="return confirm('Apakah Anda yakin ingin mencabut undangan untuk {{ $invitation->email }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button
                                            type="submit"
                                            class="inline-flex items-center px-2.5 py-1 rounded border border-rose-200 text-xs font-medium text-rose-700 hover:bg-rose-50 transition-colors"
                                        >
                                            Cabut Undangan
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-8 text-center text-neutral-500 text-sm">
                                    Tidak ada undangan yang sedang aktif menunggu konfirmasi.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>
    </div>

    <!-- Modal: Undang Staf Baru -->
    <dialog id="modal-invite-staff" class="rounded-2xl shadow-xl backdrop:bg-neutral-950/40 p-0 max-w-lg w-full m-auto">
        <div class="bg-white rounded-2xl overflow-hidden">
            <div class="px-6 py-5 border-b border-neutral-200 flex items-center justify-between">
                <div>
                    <h3 class="text-h3 font-bold text-neutral-900">Undang Operator Antrean</h3>
                    <p class="text-xs text-neutral-500 mt-0.5">Kirim undangan peran staf operator ke alamat email.</p>
                </div>
                <button type="button" onclick="document.getElementById('modal-invite-staff').close()" class="text-neutral-400 hover:text-neutral-600 p-1">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form method="POST" action="{{ route('merchant.staff.invitations.store') }}" class="p-6 space-y-4">
                @csrf
                <div>
                    <x-form-label for="modal-email" value="Alamat Email Calon Staf" :required="true" />
                    <x-input
                        id="modal-email"
                        name="email"
                        type="email"
                        required
                        placeholder="operator@contoh.id"
                    />
                    <p class="mt-1.5 text-xs text-neutral-500">
                        Calon staf akan menerima tautan akses khusus yang dapat digunakan untuk masuk atau membuat akun.
                    </p>
                </div>

                <div class="rounded-lg bg-neutral-50 border border-neutral-200 p-3 text-xs text-neutral-600 space-y-1">
                    <p class="font-semibold text-neutral-900">Ketentuan Peran Operator:</p>
                    <ul class="list-disc list-inside space-y-0.5">
                        <li>Dapat memanggil, melayani, dan menyelesaikan antrean pelanggan.</li>
                        <li>Dapat membuat tiket antrean walk-in langsung di tempat.</li>
                        <li>Tidak dapat mengakses laporan keuangan, pengaturan usaha, atau membatalkan tiket.</li>
                        <li>Satu akun email hanya dapat menjadi anggota pada satu UMKM.</li>
                    </ul>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3">
                    <x-button type="button" variant="secondary" onclick="document.getElementById('modal-invite-staff').close()">
                        Batal
                    </x-button>
                    <x-button type="submit" variant="primary">
                        Buat & Salin Tautan Undangan
                    </x-button>
                </div>
            </form>
        </div>
    </dialog>
</x-layouts.merchant>

