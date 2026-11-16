<x-layouts.admin 
    title="Manajemen Pengguna" 
    heading="Manajemen Pengguna Platform" 
    description="Kelola akun Administrator dan Pemilik UMKM (Merchant), monitor aktivitas, dan kelola penonaktifan akun."
>
    <div 
        class="space-y-5 sm:space-y-6"
        x-data="{ 
            deactivateModalOpen: false, 
            targetUserId: null, 
            targetUserName: '', 
            targetUserRole: '', 
            targetUserEmail: '',
            actionUrl: '',
            openDeactivate(id, name, email, role) {
                this.targetUserId = id;
                this.targetUserName = name;
                this.targetUserEmail = email;
                this.targetUserRole = role;
                this.actionUrl = '{{ url('admin/users') }}/' + id + '/deactivate';
                this.deactivateModalOpen = true;
            }
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

        @if ($errors->any())
            <div class="rounded-xl bg-rose-50 border border-rose-200 p-4 text-sm text-rose-800 shadow-xs">
                <p class="font-semibold mb-1">Terjadi kesalahan pada permintaan Anda:</p>
                <ul class="list-disc list-inside space-y-0.5 text-xs">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Metrics Summary Strip --}}
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
            <x-stat-tile label="Total Pengguna" :value="number_format($stats['total'])" hint="Akun terdaftar" />
            <x-stat-tile label="Administrator" :value="number_format($stats['admins'])" hint="Akses platform" tone="brand" />
            <x-stat-tile label="Owner & Staf UMKM" :value="number_format($stats['merchants'])" hint="{{ number_format($stats['operators']) }} operator" tone="brand" />
            <x-stat-tile label="Pelanggan" :value="number_format($stats['customers'])" hint="Pengambil antrean" tone="brand" />
            <x-stat-tile label="Pengguna Aktif" :value="number_format($stats['active'])" hint="Dapat login" tone="success" />
            <x-stat-tile label="Nonaktif" :value="number_format($stats['inactive'])" hint="Akses diblokir" tone="muted" />
        </div>

        {{-- Search and Multi-Filter Bar (M8-15) --}}
        <div class="bg-white rounded-xl border border-neutral-200 p-3 shadow-xs sm:p-4">
            <form method="GET" action="{{ route('admin.users.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 items-end">
                {{-- Search Box --}}
                <div class="lg:col-span-2">
                    <label for="user-search" class="block text-caption font-semibold text-neutral-700 mb-1">Cari Nama, Email, Peran, atau Nama UMKM</label>
                    <div class="relative">
                        <input
                            id="user-search"
                            type="search"
                            name="search"
                            value="{{ $search }}" 
                            placeholder="Ketik nama, email, 'operator', atau nama UMKM..." 
                            class="h-11 w-full pl-9 pr-3 rounded-lg border-neutral-300 text-sm focus:border-brand-500 focus:ring-brand-500"
                        >
                        <svg class="w-4 h-4 text-neutral-400 absolute left-3 top-1/2 -translate-y-1/2" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                </div>

                {{-- Role Filter --}}
                <div>
                    <label for="user-role" class="block text-caption font-semibold text-neutral-700 mb-1">Peran (Role)</label>
                    <select id="user-role" name="role" class="h-11 w-full rounded-lg border-neutral-300 text-sm focus:border-brand-500 focus:ring-brand-500">
                        <option value="">Semua Peran</option>
                        <option value="admin" {{ $role === 'admin' ? 'selected' : '' }}>Administrator</option>
                        <option value="owner" {{ $role === 'owner' ? 'selected' : '' }}>Owner UMKM</option>
                        <option value="operator" {{ $role === 'operator' ? 'selected' : '' }}>Operator UMKM</option>
                        <option value="merchant" {{ $role === 'merchant' ? 'selected' : '' }}>Semua Merchant (Owner &amp; Operator)</option>
                        <option value="customer" {{ $role === 'customer' ? 'selected' : '' }}>Pelanggan</option>
                    </select>
                </div>

                {{-- Status Filter --}}
                <div>
                    <label for="user-status" class="block text-caption font-semibold text-neutral-700 mb-1">Status Akun</label>
                    <select id="user-status" name="status" class="h-11 w-full rounded-lg border-neutral-300 text-sm focus:border-brand-500 focus:ring-brand-500">
                        <option value="">Semua Status</option>
                        <option value="active" {{ $status === 'active' ? 'selected' : '' }}>Aktif</option>
                        <option value="inactive" {{ $status === 'inactive' ? 'selected' : '' }}>Nonaktif</option>
                    </select>
                </div>

                {{-- Action Filter Buttons --}}
                <div class="sm:col-span-2 lg:col-span-4 flex flex-col gap-2 pt-2 border-t border-neutral-100 sm:flex-row sm:items-center sm:justify-between">
                    <div class="grid grid-cols-2 gap-2 sm:flex sm:items-center">
                        <button type="submit" class="inline-flex h-11 items-center justify-center px-4 rounded-lg bg-neutral-900 text-white text-xs font-bold hover:bg-neutral-800 transition shadow-xs">
                            Terapkan Filter
                        </button>
                        @if ($search || $role || $status)
                            <a href="{{ route('admin.users.index') }}" class="inline-flex h-11 items-center justify-center px-3 rounded-lg border border-neutral-300 text-neutral-700 text-xs font-medium hover:bg-neutral-50 transition">
                                Reset
                            </a>
                        @endif
                    </div>

                    <span class="text-caption text-neutral-500">
                        Total <strong>{{ $users->total() }}</strong> pengguna ditemukan
                    </span>
                </div>
            </form>
        </div>

        {{-- User Listing (M8-15, M8-16, M8-30, M8-33) --}}
        <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden shadow-xs">
            <div class="p-4 sm:p-5 border-b border-neutral-200 flex items-center justify-between">
                <div>
                    <h3 class="text-h3 font-bold text-neutral-900">Daftar Pengguna Platform</h3>
                    <p class="text-caption text-neutral-500 mt-0.5">Seluruh administrator, owner UMKM, operator loket, dan pelanggan yang terdaftar di AntreIn.</p>
                </div>
            </div>

            {{-- Desktop Table View --}}
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="border-b border-neutral-200 bg-neutral-50/75 text-neutral-600 font-semibold text-xs tracking-wider uppercase">
                            <th class="py-3.5 px-4">Pengguna</th>
                            <th class="py-3.5 px-4">Peran (Role)</th>
                            <th class="py-3.5 px-4">UMKM Terkait</th>
                            <th class="py-3.5 px-4">Status</th>
                            <th class="py-3.5 px-4">Terdaftar</th>
                            <th class="py-3.5 px-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-200">
                        @forelse ($users as $user)
                            @php
                                $operatorMembership = $user->businessMemberships->firstWhere('role', \App\Models\BusinessMembership::ROLE_OPERATOR);
                                $isOperatorAccount = $user->isMerchant() && $user->ownedBusinesses->isEmpty() && $operatorMembership !== null;
                                $relatedBusiness = $user->ownedBusinesses->first() ?? $operatorMembership?->business ?? $user->businessMemberships->first()?->business;
                            @endphp
                            <tr class="hover:bg-neutral-50/50 transition">
                                {{-- User Identity --}}
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-full bg-neutral-100 text-neutral-700 font-bold text-xs flex items-center justify-center shrink-0 border border-neutral-200">
                                            {{ strtoupper(substr($user->name, 0, 2)) }}
                                        </div>
                                        <div class="min-w-0">
                                            <a href="{{ route('admin.users.show', $user) }}" class="font-bold text-neutral-900 hover:text-brand-600 transition truncate block">
                                                {{ $user->name }}
                                                @if (auth()->id() === $user->id)
                                                    <span class="ml-1 text-[10px] font-bold px-1.5 py-0.5 rounded bg-brand-100 text-brand-700">Anda</span>
                                                @endif
                                            </a>
                                            <span class="text-caption text-neutral-500 block truncate">{{ $user->email }}</span>
                                        </div>
                                    </div>
                                </td>

                                {{-- Role Badge --}}
                                <td class="py-3.5 px-4">
                                    @if ($user->isAdmin())
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-800 border border-indigo-200">
                                            Administrator
                                        </span>
                                    @elseif ($user->isCustomer())
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                            Pelanggan
                                        </span>
                                    @elseif ($isOperatorAccount)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-cyan-100 text-cyan-800 border border-cyan-200">
                                            Operator UMKM
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-800 border border-blue-200">
                                            Owner UMKM
                                        </span>
                                    @endif
                                </td>

                                {{-- Business Connection --}}
                                <td class="py-3.5 px-4">
                                    @if ($relatedBusiness)
                                        <a href="{{ route('admin.businesses.show', $relatedBusiness) }}" class="font-medium text-neutral-900 hover:text-brand-600 hover:underline block truncate max-w-xs">
                                            {{ $relatedBusiness->name }}
                                        </a>
                                        <span class="text-caption text-neutral-400 block">
                                            /q/{{ $relatedBusiness->slug }}
                                            @if ($isOperatorAccount && $operatorMembership->assignedCounters->isNotEmpty())
                                                | Loket: {{ $operatorMembership->assignedCounters->pluck('name')->implode(', ') }}
                                            @endif
                                        </span>
                                    @elseif ($user->isMerchant())
                                        <span class="text-caption text-amber-600 font-medium italic">Belum melengkapi profil</span>
                                    @else
                                        <span class="text-caption text-neutral-400">—</span>
                                    @endif
                                </td>

                                {{-- Status Badge --}}
                                <td class="py-3.5 px-4">
                                    <x-status-badge :status="$user->is_active ? 'aktif' : 'nonaktif'" size="sm" />
                                </td>

                                {{-- Date --}}
                                <td class="py-3.5 px-4 text-caption text-neutral-500">
                                    {{ $user->created_at->format('d M Y, H:i') }}
                                </td>

                                {{-- Actions --}}
                                <td class="py-3.5 px-4 text-right">
                                    <div class="inline-flex items-center gap-2">
                                        <a 
                                            href="{{ route('admin.users.show', $user) }}" 
                                            aria-label="Lihat detail pengguna"
                                            class="inline-flex size-9 items-center justify-center rounded-lg text-neutral-600 hover:text-neutral-900 hover:bg-neutral-100 transition"
                                            title="Lihat Detail Pengguna"
                                        >
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        </a>

                                        @if ($user->is_active)
                                            <button 
                                                type="button" 
                                                @click="openDeactivate({{ $user->id }}, @js($user->name), @js($user->email), @js($user->role))"
                                                class="inline-flex h-9 items-center px-2.5 text-xs font-semibold rounded-lg bg-rose-50 text-rose-700 hover:bg-rose-100 transition border border-rose-200"
                                            >
                                                Nonaktifkan
                                            </button>
                                        @else
                                            <form method="POST" action="{{ route('admin.users.activate', $user) }}" class="inline" onsubmit="return confirm(@js('Aktifkan kembali akun '.$user->name.'?'))">
                                                @csrf
                                                <button 
                                                    type="submit" 
                                                    class="inline-flex h-9 items-center px-2.5 text-xs font-semibold rounded-lg bg-emerald-50 text-emerald-700 hover:bg-emerald-100 transition border border-emerald-200"
                                                >
                                                    Aktifkan
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-12 text-center">
                                    <div class="max-w-xs mx-auto text-neutral-400">
                                        <svg class="w-10 h-10 mx-auto text-neutral-300 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                                        <p class="font-semibold text-neutral-700">Tidak ada pengguna ditemukan</p>
                                        <p class="text-caption text-neutral-500 mt-1">Coba sesuaikan kata kunci pencarian atau filter peranan.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Mobile Card View (M8-33) --}}
            <div class="md:hidden divide-y divide-neutral-200">
                @forelse ($users as $user)
                    @php
                        $operatorMembership = $user->businessMemberships->firstWhere('role', \App\Models\BusinessMembership::ROLE_OPERATOR);
                        $isOperatorAccount = $user->isMerchant() && $user->ownedBusinesses->isEmpty() && $operatorMembership !== null;
                        $relatedBusiness = $user->ownedBusinesses->first() ?? $operatorMembership?->business ?? $user->businessMemberships->first()?->business;
                        $mobileRoleLabel = $user->isAdmin()
                            ? 'Administrator'
                            : ($user->isCustomer() ? 'Pelanggan' : ($isOperatorAccount ? 'Operator UMKM' : 'Owner UMKM'));
                    @endphp
                    <div class="p-4 space-y-3">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <div class="w-9 h-9 rounded-full bg-neutral-100 text-neutral-700 font-bold text-xs flex items-center justify-center shrink-0 border border-neutral-200">
                                    {{ strtoupper(substr($user->name, 0, 2)) }}
                                </div>
                                <div class="min-w-0">
                                    <h4 class="font-bold text-neutral-900 truncate">
                                        {{ $user->name }}
                                        @if (auth()->id() === $user->id)
                                            <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-brand-100 text-brand-700">Anda</span>
                                        @endif
                                    </h4>
                                    <p class="text-caption text-neutral-500 truncate">{{ $user->email }}</p>
                                </div>
                            </div>

                            <x-status-badge :status="$user->is_active ? 'aktif' : 'nonaktif'" size="sm" class="shrink-0" />
                        </div>

                        <div class="text-caption text-neutral-600 space-y-1 bg-neutral-50/75 p-2.5 rounded-lg border border-neutral-200/60">
                            <div class="flex justify-between gap-3">
                                <span class="text-neutral-500">Peran:</span>
                                <span class="font-semibold text-neutral-800">{{ $mobileRoleLabel }}</span>
                            </div>
                            @if ($relatedBusiness)
                                <div class="flex justify-between gap-3">
                                    <span class="shrink-0 text-neutral-500">UMKM:</span>
                                    <a href="{{ route('admin.businesses.show', $relatedBusiness) }}" class="font-semibold text-brand-600 hover:underline truncate max-w-[180px]">
                                        {{ $relatedBusiness->name }}
                                    </a>
                                </div>
                            @endif
                            <div class="flex justify-between gap-3">
                                <span class="text-neutral-500">Terdaftar:</span>
                                <span>{{ $user->created_at->format('d M Y') }}</span>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-2">
                            <a
                                href="{{ route('admin.users.show', $user) }}"
                                class="inline-flex h-11 items-center justify-center px-3 rounded-lg border border-neutral-300 text-neutral-700 text-xs font-medium hover:bg-neutral-50 transition"
                            >
                                Detail
                            </a>

                            @if ($user->is_active)
                                <button 
                                    type="button" 
                                    @click="openDeactivate({{ $user->id }}, @js($user->name), @js($user->email), @js($user->role))"
                                    class="inline-flex h-11 w-full items-center justify-center px-3 text-xs font-semibold rounded-lg bg-rose-50 text-rose-700 hover:bg-rose-100 transition border border-rose-200"
                                >
                                    Nonaktifkan
                                </button>
                            @else
                                <form method="POST" action="{{ route('admin.users.activate', $user) }}" class="inline" onsubmit="return confirm(@js('Aktifkan kembali akun '.$user->name.'?'))">
                                    @csrf
                                    <button 
                                        type="submit" 
                                        class="inline-flex h-11 w-full items-center justify-center px-3 text-xs font-semibold rounded-lg bg-emerald-50 text-emerald-700 hover:bg-emerald-100 transition border border-emerald-200"
                                    >
                                        Aktifkan
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="py-8 text-center text-neutral-400">
                        <p class="font-semibold text-neutral-700">Tidak ada pengguna ditemukan</p>
                    </div>
                @endforelse
            </div>

            {{-- Pagination Links --}}
            @if ($users->hasPages())
                <div class="p-4 border-t border-neutral-200 bg-neutral-50/50">
                    {{ $users->links() }}
                </div>
            @endif
        </div>

        {{-- Deactivation Confirmation Modal (M8-17, M8-18, M8-19) --}}
        <div 
            x-show="deactivateModalOpen" 
            x-cloak
            class="fixed inset-0 z-50 overflow-y-auto" 
            aria-labelledby="modal-title" 
            role="dialog" 
            aria-modal="true"
        >
            {{-- Backdrop --}}
            <div 
                x-show="deactivateModalOpen" 
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 bg-neutral-900/60 backdrop-blur-xs transition-opacity" 
                @click="deactivateModalOpen = false"
            ></div>

            <div class="flex min-h-full items-end justify-center text-center sm:items-center sm:p-4">
                <div 
                    x-show="deactivateModalOpen" 
                    x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    class="relative w-full transform overflow-hidden rounded-t-2xl bg-white text-left shadow-xl transition-all sm:my-8 sm:max-w-lg sm:rounded-2xl border border-neutral-200"
                >
                    <form method="POST" :action="actionUrl">
                        @csrf
                        <div class="bg-white p-5 sm:p-6">
                            <div class="flex items-start gap-4">
                                <div class="w-10 h-10 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center shrink-0">
                                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <h3 class="text-h3 font-bold text-neutral-900" id="modal-title">
                                        Nonaktifkan Akun Pengguna
                                    </h3>
                                    <p class="text-caption text-neutral-500 mt-1">
                                        Anda akan menonaktifkan akses akun <strong class="text-neutral-900 font-semibold" x-text="targetUserName"></strong> (<span x-text="targetUserEmail"></span>).
                                    </p>
                                </div>
                            </div>

                            <div class="mt-5 space-y-4">
                                {{-- Role specific warnings --}}
                                <template x-if="targetUserRole === 'admin'">
                                    <div class="rounded-lg bg-amber-50 border border-amber-200 p-3 text-xs text-amber-900">
                                        <strong>Perlindungan Admin Aktif Terakhir (M8-19):</strong> Sistem akan secara otomatis memvalidasi bahwa minimal satu administrator aktif lainnya tetap tersedia di platform sebelum penonaktifan dieksekusi.
                                    </div>
                                </template>

                                <template x-if="targetUserRole === 'merchant'">
                                    <div class="space-y-3">
                                        <div class="rounded-lg bg-blue-50 border border-blue-200 p-3 text-xs text-blue-900">
                                            <strong>Interlock Antrean Aktif (OQ-03, M8-17):</strong> Akun pemilik UMKM tidak dapat dinonaktifkan jika masih memiliki tiket antrean aktif (menunggu, dipanggil, atau sedang dilayani), kecuali diabaikan secara darurat.
                                        </div>

                                        <label class="flex items-start gap-2.5 p-3 rounded-lg border border-rose-200 bg-rose-50/50 cursor-pointer">
                                            <input 
                                                type="checkbox" 
                                                name="emergency_force" 
                                                value="1" 
                                                class="size-5 rounded border-rose-300 text-rose-600 focus:ring-rose-500 mt-0.5"
                                            >
                                            <div class="text-xs">
                                                <span class="font-bold text-rose-900 block">Bypass Darurat (Emergency Force)</span>
                                                <span class="text-rose-700 block mt-0.5">Centang ini HANYA jika terjadi keadaan darurat dan Anda ingin membatalkan/menutup semua tiket antrean aktif yang masih berjalan secara otomatis.</span>
                                            </div>
                                        </label>
                                    </div>
                                </template>

                                {{-- Reason Input --}}
                                <div>
                                    <label for="deactivation_reason" class="block text-caption font-semibold text-neutral-700 mb-1">
                                        Alasan Penonaktifan (Wajib Dicatat pada Audit Log)
                                    </label>
                                    <textarea 
                                        id="deactivation_reason" 
                                        name="reason" 
                                        rows="3" 
                                        required 
                                        placeholder="Tuliskan alasan penonaktifan akun untuk keperluan rekam jejak audit sistem..." 
                                        class="w-full rounded-lg border-neutral-300 text-sm focus:border-rose-500 focus:ring-rose-500"
                                    ></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="bg-neutral-50 px-5 py-4 sm:px-6 grid grid-cols-2 gap-2 sm:flex sm:justify-end border-t border-neutral-200">
                            <button 
                                type="button" 
                                @click="deactivateModalOpen = false"
                                class="inline-flex h-11 items-center justify-center px-4 rounded-lg border border-neutral-300 text-neutral-700 text-xs font-semibold hover:bg-neutral-100 transition"
                            >
                                Batal
                            </button>
                            <button 
                                type="submit"
                                class="inline-flex h-11 items-center justify-center px-4 rounded-lg bg-rose-600 text-white text-xs font-bold hover:bg-rose-700 transition shadow-xs"
                            >
                                Konfirmasi Nonaktifkan Akun
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-layouts.admin>

