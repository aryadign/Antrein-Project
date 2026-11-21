@php
    $operatorMemberships = $user->businessMemberships->where('role', \App\Models\BusinessMembership::ROLE_OPERATOR);
    $isOperatorAccount = $user->isMerchant() && $user->ownedBusinesses->isEmpty() && $operatorMemberships->isNotEmpty();
    $primaryBusiness = $user->ownedBusinesses->first() ?? $operatorMemberships->first()?->business ?? $user->business;
    $assignedCounters = $isOperatorAccount ? $operatorMemberships->first()?->assignedCounters->pluck('name')->implode(', ') : '';

    $roleLabel = match (true) {
        $user->isAdmin() => 'Administrator platform',
        $user->isCustomer() => 'Pelanggan',
        $isOperatorAccount => 'Operator UMKM',
        default => 'Owner UMKM',
    };

    $accountFields = [
        ['label' => 'Email', 'value' => $user->email],
        ['label' => 'Peran', 'value' => $roleLabel],
        ['label' => 'ID pengguna', 'value' => '#'.$user->id],
        ['label' => 'Terdaftar', 'value' => $user->created_at->translatedFormat('d M Y, H:i')],
        ['label' => 'Terakhir diperbarui', 'value' => $user->updated_at->translatedFormat('d M Y, H:i')],
    ];

    $isSelf = auth()->id() === $user->id;
@endphp

<x-layouts.admin
    :title="'Detail Pengguna: ' . $user->name"
    :heading="$user->name"
    description="Profil akun, UMKM yang terhubung, dan aktivitas terakhir pengguna ini."
>
    <div
        class="mx-auto max-w-5xl space-y-6"
        x-data="{ deactivateModalOpen: false, targetUserId: {{ $user->id }}, targetUserName: @js($user->name), targetUserEmail: @js($user->email), targetUserRole: @js($user->role) }"
    >
        @include('admin.partials.flash')

        <a href="{{ route('admin.users.index') }}" class="-my-2 inline-flex min-h-11 items-center gap-2 text-sm font-medium text-neutral-600 transition-colors hover:text-neutral-900">
            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Kembali ke daftar pengguna
        </a>

        {{-- Summary --}}
        <x-card padding="sm">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex min-w-0 items-center gap-4">
                    <span class="flex size-14 shrink-0 items-center justify-center rounded-full border border-brand-200 bg-brand-50 text-lg font-bold text-brand-700" aria-hidden="true">{{ strtoupper(substr($user->name, 0, 2)) }}</span>
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-sm font-medium text-neutral-800">{{ $roleLabel }}</span>
                            <x-status-badge :status="$user->is_active ? 'aktif' : 'nonaktif'" size="sm" />
                            @if ($isSelf)
                                <span class="rounded bg-brand-50 px-1.5 py-0.5 text-xs font-semibold text-brand-700">Akun Anda</span>
                            @endif
                        </div>
                        <p class="mt-0.5 break-all text-sm text-neutral-600">{{ $user->email }}</p>
                    </div>
                </div>

                @unless ($isSelf)
                    @if ($user->is_active)
                        <x-button variant="outline" class="w-full text-rose-700! hover:border-rose-300! hover:bg-rose-50! sm:w-auto" @click="deactivateModalOpen = true">Nonaktifkan akun</x-button>
                    @else
                        <form method="POST" action="{{ route('admin.users.activate', $user) }}" onsubmit="return confirm('Aktifkan kembali akun pengguna ini?')">
                            @csrf
                            <x-button type="submit" class="w-full sm:w-auto">Aktifkan akun</x-button>
                        </form>
                    @endif
                @endunless
            </div>
        </x-card>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <x-card padding="none">
                <x-slot:header>
                    <h2 class="text-h3 font-semibold text-neutral-900">Informasi akun</h2>
                </x-slot:header>
                <dl class="divide-y divide-neutral-100 px-5 sm:px-6">
                    @foreach ($accountFields as $field)
                        <div class="flex items-start justify-between gap-4 py-3 text-sm">
                            <dt class="text-neutral-600">{{ $field['label'] }}</dt>
                            <dd class="min-w-0 break-all text-right font-medium tabular-nums text-neutral-900">{{ $field['value'] }}</dd>
                        </div>
                    @endforeach
                </dl>
            </x-card>

            <x-card padding="none">
                <x-slot:header>
                    <h2 class="text-h3 font-semibold text-neutral-900">UMKM terhubung</h2>
                </x-slot:header>

                @if ($primaryBusiness)
                    <div class="space-y-4 px-5 py-5 sm:px-6">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <a href="{{ route('admin.businesses.show', $primaryBusiness) }}" class="font-semibold text-neutral-950 hover:text-brand-700 hover:underline">{{ $primaryBusiness->name }}</a>
                                <p class="text-sm text-neutral-600">{{ $primaryBusiness->category?->name ?? 'Tanpa kategori' }}</p>
                            </div>
                            <div class="flex shrink-0 flex-col items-end gap-1">
                                <x-status-badge :status="$primaryBusiness->status" size="sm" />
                                @if ($primaryBusiness->is_suspended)
                                    <x-status-badge status="suspended" size="sm" />
                                @endif
                            </div>
                        </div>
                        <dl class="space-y-2 text-sm">
                            <div class="flex justify-between gap-4">
                                <dt class="text-neutral-600">Kontak</dt>
                                <dd class="font-medium tabular-nums text-neutral-900">{{ $primaryBusiness->phone ?: 'Belum diisi' }}</dd>
                            </div>
                            @if ($assignedCounters)
                                <div class="flex justify-between gap-4">
                                    <dt class="text-neutral-600">Loket ditugaskan</dt>
                                    <dd class="text-right font-medium text-neutral-900">{{ $assignedCounters }}</dd>
                                </div>
                            @endif
                        </dl>
                        <x-button variant="outline" size="sm" :href="route('admin.businesses.show', $primaryBusiness)">Buka detail UMKM</x-button>
                    </div>
                @else
                    <p class="px-5 py-8 text-center text-sm text-pretty text-neutral-600 sm:px-6">
                        @if ($user->isMerchant())
                            Pengguna ini belum menyelesaikan pendaftaran profil UMKM.
                        @elseif ($user->isCustomer())
                            Akun pelanggan dipakai untuk mengambil nomor antrean dan memberi ulasan, tanpa UMKM terhubung.
                        @else
                            Akun administrator tidak terhubung dengan UMKM tertentu.
                        @endif
                    </p>
                @endif
            </x-card>
        </div>

        <x-card padding="none">
            <x-slot:header>
                <h2 class="text-h3 font-semibold text-neutral-900">Aktivitas terakhir</h2>
                <p class="text-sm text-neutral-600">10 catatan audit terbaru untuk akun ini.</p>
            </x-slot:header>
            @if ($userLogs->isEmpty())
                <p class="px-5 py-8 text-center text-sm text-neutral-600">Belum ada aktivitas tercatat untuk akun ini.</p>
            @else
                <ul class="divide-y divide-neutral-100">
                    @foreach ($userLogs as $log)
                        <li class="flex flex-col gap-1 px-5 py-3 sm:flex-row sm:items-start sm:justify-between sm:gap-4 sm:px-6">
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-neutral-900">{{ $log->description }}</p>
                                <p class="mt-0.5 font-mono text-xs text-neutral-600">{{ $log->action }}</p>
                            </div>
                            <time datetime="{{ $log->created_at->toIso8601String() }}" class="shrink-0 text-xs tabular-nums text-neutral-600" title="{{ $log->created_at->translatedFormat('d M Y, H:i') }}">{{ $log->created_at->diffForHumans() }}</time>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-card>

        @include('admin.partials.user-deactivate-dialog')
    </div>
</x-layouts.admin>
