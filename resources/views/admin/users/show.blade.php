<x-layouts.admin 
    :title="'Detail Pengguna: ' . $user->name" 
    :heading="'Detail Pengguna: ' . $user->name" 
    description="Informasi profil akun, kepemilikan UMKM, dan rekam jejak aktivitas pengguna."
>
    <div 
        class="space-y-6" 
        x-data="{ 
            deactivateModalOpen: false, 
            targetUserId: {{ $user->id }}, 
            targetUserName: '{{ addslashes($user->name) }}', 
            targetUserRole: '{{ $user->role }}', 
            targetUserEmail: '{{ addslashes($user->email) }}',
            actionUrl: '{{ route('admin.users.deactivate', $user) }}'
        }"
    >
        {{-- Flash Messages --}}
        @if (session('status'))
            <div class="rounded-xl bg-emerald-50 border border-emerald-200 p-4 text-sm font-medium text-emerald-800 flex items-center justify-between shadow-xs">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <span>{{ session('status') }}</span>
                </div>
            </div>
        @endif

        @if (session('error'))
            <div class="rounded-xl bg-rose-50 border border-rose-200 p-4 text-sm font-medium text-rose-800 flex items-center justify-between shadow-xs">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                    <span>{{ session('error') }}</span>
                </div>
            </div>
        @endif

        {{-- Top Navigation & Actions --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <a href="{{ route('admin.users.index') }}" class="inline-flex items-center gap-2 text-xs font-semibold text-neutral-600 hover:text-neutral-900 transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Kembali ke Daftar Pengguna
            </a>

            <div class="flex items-center gap-2">
                @if ($user->is_active)
                    <button 
                        type="button" 
                        @click="deactivateModalOpen = true"
                        class="px-4 py-2 rounded-lg bg-rose-50 text-rose-700 text-xs font-semibold hover:bg-rose-100 transition border border-rose-200"
                    >
                        Nonaktifkan Akun
                    </button>
                @else
                    <form method="POST" action="{{ route('admin.users.activate', $user) }}" class="inline" onsubmit="return confirm('Aktifkan kembali akun pengguna ini?')">
                        @csrf
                        <button 
                            type="submit" 
                            class="px-4 py-2 rounded-lg bg-emerald-600 text-white text-xs font-semibold hover:bg-emerald-700 transition shadow-xs"
                        >
                            Aktifkan Akun
                        </button>
                    </form>
                @endif
            </div>
        </div>

        {{-- Header Profile Card --}}
        <div class="bg-white rounded-xl border border-neutral-200 p-6 shadow-xs">
            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4">
                <div class="w-16 h-16 rounded-full bg-brand-50 border border-brand-200 text-brand-700 font-extrabold text-xl flex items-center justify-center shrink-0">
                    {{ strtoupper(substr($user->name, 0, 2)) }}
                </div>
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2.5">
                        <h2 class="text-xl font-bold text-neutral-950">{{ $user->name }}</h2>
                        @if ($user->isAdmin())
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-800 border border-indigo-200">
                                Administrator
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-800 border border-blue-200">
                                Merchant UMKM
                            </span>
                        @endif

                        @if ($user->is_active)
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                Akun Aktif
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                                <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                Akun Dinonaktifkan
                            </span>
                        @endif
                    </div>
                    <p class="text-sm text-neutral-500 mt-1">{{ $user->email }}</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Account Information Details --}}
            <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden shadow-xs">
                <div class="p-4 sm:p-5 border-b border-neutral-200">
                    <h3 class="text-h3 font-bold text-neutral-900">Informasi Akun</h3>
                    <p class="text-caption text-neutral-500 mt-0.5">Detail identitas login dan status verifikasi.</p>
                </div>

                <div class="p-4 sm:p-5 divide-y divide-neutral-100 text-sm">
                    <div class="py-2.5 flex justify-between items-center">
                        <span class="text-neutral-500">ID Pengguna</span>
                        <span class="font-mono text-xs text-neutral-800 bg-neutral-100 px-2 py-0.5 rounded">#{{ $user->id }}</span>
                    </div>

                    <div class="py-2.5 flex justify-between items-center">
                        <span class="text-neutral-500">Nama Lengkap</span>
                        <span class="font-semibold text-neutral-900">{{ $user->name }}</span>
                    </div>

                    <div class="py-2.5 flex justify-between items-center">
                        <span class="text-neutral-500">Alamat Email</span>
                        <span class="font-medium text-neutral-800">{{ $user->email }}</span>
                    </div>

                    <div class="py-2.5 flex justify-between items-center">
                        <span class="text-neutral-500">Peran Sistem</span>
                        <span class="font-semibold {{ $user->isAdmin() ? 'text-indigo-600' : 'text-blue-600' }}">
                            {{ $user->isAdmin() ? 'Administrator Platform' : 'Pemilik Usaha (Merchant)' }}
                        </span>
                    </div>

                    <div class="py-2.5 flex justify-between items-center">
                        <span class="text-neutral-500">Status Akun</span>
                        <span class="font-semibold {{ $user->is_active ? 'text-emerald-600' : 'text-rose-600' }}">
                            {{ $user->is_active ? 'Aktif (Dapat Login)' : 'Nonaktif (Akses Dicegah)' }}
                        </span>
                    </div>

                    <div class="py-2.5 flex justify-between items-center">
                        <span class="text-neutral-500">Terdaftar Pada</span>
                        <span class="text-neutral-800">{{ $user->created_at->format('d M Y, H:i') }}</span>
                    </div>

                    <div class="py-2.5 flex justify-between items-center">
                        <span class="text-neutral-500">Pembaruan Terakhir</span>
                        <span class="text-neutral-800">{{ $user->updated_at->format('d M Y, H:i') }}</span>
                    </div>
                </div>
            </div>

            {{-- Connected UMKM Information (If Merchant) --}}
            <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden shadow-xs">
                <div class="p-4 sm:p-5 border-b border-neutral-200">
                    <h3 class="text-h3 font-bold text-neutral-900">Gerai UMKM Terhubung</h3>
                    <p class="text-caption text-neutral-500 mt-0.5">Entitas bisnis yang dimiliki oleh akun pengguna ini.</p>
                </div>

                <div class="p-4 sm:p-5">
                    @if ($user->business)
                        <div class="space-y-4">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <h4 class="font-bold text-base text-neutral-900">{{ $user->business->name }}</h4>
                                    <p class="text-caption text-neutral-500">Slug: <code class="font-mono bg-neutral-100 px-1 rounded">{{ $user->business->slug }}</code></p>
                                </div>

                                @if ($user->business->is_suspended)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-rose-100 text-rose-800">
                                        Ditangguhkan
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-emerald-100 text-emerald-800">
                                        Normal / Aktif
                                    </span>
                                @endif
                            </div>

                            <div class="p-3 bg-neutral-50 rounded-lg text-xs space-y-1.5 border border-neutral-200">
                                <div class="flex justify-between">
                                    <span class="text-neutral-500">Kategori:</span>
                                    <span class="font-medium text-neutral-800">{{ $user->business->category?->name ?? '—' }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-neutral-500">Kontak Telepon:</span>
                                    <span class="font-medium text-neutral-800">{{ $user->business->phone ?? '—' }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-neutral-500">Status Operasional:</span>
                                    <span class="font-medium {{ $user->business->is_active ? 'text-emerald-700' : 'text-neutral-600' }}">
                                        {{ $user->business->is_active ? 'Buka / Menerima' : 'Tutup' }}
                                    </span>
                                </div>
                            </div>

                            <div class="pt-2">
                                <a 
                                    href="{{ route('admin.businesses.show', $user->business) }}" 
                                    class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-neutral-900 text-white text-xs font-semibold hover:bg-neutral-800 transition"
                                >
                                    Lihat Detail Profil UMKM
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                </a>
                            </div>
                        </div>
                    @elseif ($user->isMerchant())
                        <div class="py-8 text-center text-neutral-400">
                            <svg class="w-10 h-10 mx-auto text-neutral-300 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                            <p class="font-semibold text-neutral-700">Belum Ada Gerai Terdaftar</p>
                            <p class="text-caption text-neutral-500 mt-1">Pengguna ini terdaftar sebagai Merchant namun belum menyelesaikan langkah onboarding profil gerai.</p>
                        </div>
                    @else
                        <div class="py-8 text-center text-neutral-400">
                            <svg class="w-10 h-10 mx-auto text-indigo-200 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                            <p class="font-semibold text-neutral-700">Akun Administrator</p>
                            <p class="text-caption text-neutral-500 mt-1">Akun administrator platform tidak terasosiasi dengan gerai UMKM manapun.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Activity Logs History --}}
        <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden shadow-xs">
            <div class="p-4 sm:p-5 border-b border-neutral-200">
                <h3 class="text-h3 font-bold text-neutral-900">Rekam Jejak Aktivitas Terakhir (Audit Log)</h3>
                <p class="text-caption text-neutral-500 mt-0.5">10 aktivitas operasional terakhir yang dicatat untuk akun ini.</p>
            </div>

            <div class="divide-y divide-neutral-100">
                @forelse ($userLogs as $log)
                    <div class="p-4 sm:px-5 flex items-start gap-3 text-sm">
                        <div class="w-2 h-2 rounded-full bg-brand-500 mt-2 shrink-0"></div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center justify-between gap-2">
                                <span class="font-semibold text-neutral-900">{{ $log->action }}</span>
                                <span class="text-caption text-neutral-400 shrink-0">{{ $log->created_at->diffForHumans() }}</span>
                            </div>
                            <p class="text-caption text-neutral-600 mt-0.5">{{ $log->description }}</p>
                        </div>
                    </div>
                @empty
                    <div class="p-6 text-center text-caption text-neutral-400">
                        Belum ada aktivitas tercatat untuk akun pengguna ini.
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Deactivation Confirmation Modal --}}
        <div 
            x-show="deactivateModalOpen" 
            x-cloak
            class="fixed inset-0 z-50 overflow-y-auto" 
            aria-labelledby="modal-title" 
            role="dialog" 
            aria-modal="true"
        >
            <div 
                x-show="deactivateModalOpen" 
                class="fixed inset-0 bg-neutral-900/60 backdrop-blur-xs transition-opacity" 
                @click="deactivateModalOpen = false"
            ></div>

            <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg border border-neutral-200">
                    <form method="POST" :action="actionUrl">
                        @csrf
                        <div class="bg-white p-6">
                            <div class="flex items-start gap-4">
                                <div class="w-10 h-10 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center shrink-0">
                                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <h3 class="text-h3 font-bold text-neutral-900">
                                        Nonaktifkan Akun Pengguna
                                    </h3>
                                    <p class="text-caption text-neutral-500 mt-1">
                                        Anda akan menonaktifkan akun <strong class="text-neutral-900 font-semibold" x-text="targetUserName"></strong>.
                                    </p>
                                </div>
                            </div>

                            <div class="mt-5 space-y-4">
                                @if ($user->isAdmin())
                                    <div class="rounded-lg bg-amber-50 border border-amber-200 p-3 text-xs text-amber-900">
                                        <strong>Perlindungan Admin Aktif Terakhir (M8-19):</strong> Sistem memastikan minimal satu administrator aktif tetap tersedia sebelum penonaktifan berhasil diproses.
                                    </div>
                                @elseif ($user->isMerchant())
                                    <div class="space-y-3">
                                        <div class="rounded-lg bg-blue-50 border border-blue-200 p-3 text-xs text-blue-900">
                                            <strong>Interlock Antrean Aktif (OQ-03, M8-17):</strong> Akun tidak dapat dinonaktifkan jika masih ada tiket aktif yang berjalan.
                                        </div>

                                        <label class="flex items-start gap-2.5 p-3 rounded-lg border border-rose-200 bg-rose-50/50 cursor-pointer">
                                            <input 
                                                type="checkbox" 
                                                name="emergency_force" 
                                                value="1" 
                                                class="rounded border-rose-300 text-rose-600 focus:ring-rose-500 mt-0.5"
                                            >
                                            <div class="text-xs">
                                                <span class="font-bold text-rose-900 block">Bypass Darurat (Emergency Force)</span>
                                                <span class="text-rose-700 block mt-0.5">Tutup paksa semua antrean aktif gerai ini untuk keperluan darurat.</span>
                                            </div>
                                        </label>
                                    </div>
                                @endif

                                <div>
                                    <label for="deactivation_reason_show" class="block text-caption font-semibold text-neutral-700 mb-1">
                                        Alasan Penonaktifan (Wajib untuk Audit Log)
                                    </label>
                                    <textarea 
                                        id="deactivation_reason_show" 
                                        name="reason" 
                                        rows="3" 
                                        required 
                                        placeholder="Tuliskan alasan penonaktifan akun..." 
                                        class="w-full rounded-lg border-neutral-300 text-sm focus:border-rose-500 focus:ring-rose-500"
                                    ></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="bg-neutral-50 px-6 py-4 flex flex-col-reverse sm:flex-row sm:justify-end gap-2 border-t border-neutral-200">
                            <button 
                                type="button" 
                                @click="deactivateModalOpen = false" 
                                class="px-4 py-2 rounded-lg border border-neutral-300 text-neutral-700 text-xs font-semibold hover:bg-neutral-100 transition"
                            >
                                Batal
                            </button>
                            <button 
                                type="submit" 
                                class="px-4 py-2 rounded-lg bg-rose-600 text-white text-xs font-bold hover:bg-rose-700 transition shadow-xs"
                            >
                                Konfirmasi Nonaktifkan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-layouts.admin>

