@php
    use App\Models\BusinessMembership;
    use App\Models\User;

    $roleTabs = collect([
        ['key' => null, 'label' => 'Semua', 'count' => $stats['total']],
        ['key' => 'admin', 'label' => 'Administrator', 'count' => $stats['admins']],
        ['key' => 'owner', 'label' => 'Owner UMKM', 'count' => $stats['merchants'] - $stats['operators']],
        ['key' => 'operator', 'label' => 'Operator', 'count' => $stats['operators']],
        ['key' => 'customer', 'label' => 'Pelanggan', 'count' => $stats['customers']],
    ])->map(fn (array $tab): array => [
        'label' => $tab['label'],
        'count' => $tab['count'],
        'active' => ($role ?: null) === $tab['key'],
        'href' => route('admin.users.index', array_filter(['role' => $tab['key'], 'search' => $search, 'status' => $status])),
    ])->all();

    /**
     * @return array{label: string, business: ?\App\Models\Business, counters: string}
     */
    $describeUser = function (User $user): array {
        $operatorMembership = $user->businessMemberships->firstWhere('role', BusinessMembership::ROLE_OPERATOR);
        $isOperator = $user->isMerchant() && $user->ownedBusinesses->isEmpty() && $operatorMembership !== null;

        return [
            'label' => match (true) {
                $user->isAdmin() => 'Administrator',
                $user->isCustomer() => 'Pelanggan',
                $isOperator => 'Operator UMKM',
                default => 'Owner UMKM',
            },
            'business' => $user->ownedBusinesses->first() ?? $operatorMembership?->business ?? $user->businessMemberships->first()?->business,
            'counters' => $isOperator ? $operatorMembership->assignedCounters->pluck('name')->implode(', ') : '',
        ];
    };

    $hasFilters = $search !== '' || $status;
@endphp

<x-layouts.admin
    title="Manajemen Pengguna"
    heading="Manajemen Pengguna"
    description="Cari akun administrator, pemilik dan operator UMKM, serta pelanggan, lalu kelola akses mereka."
>
    <div class="space-y-5" x-data="{ deactivateModalOpen: false, targetUserId: null, targetUserName: '', targetUserEmail: '', targetUserRole: '' }">
        @include('admin.partials.flash')

        @include('admin.partials.filter-tabs', ['tabs' => $roleTabs, 'label' => 'Filter peran pengguna'])

        <form method="GET" action="{{ route('admin.users.index') }}" class="flex flex-col gap-3 rounded-xl border border-neutral-200/80 bg-white p-3 shadow-card sm:p-4 lg:flex-row lg:items-end">
            @if ($role)
                <input type="hidden" name="role" value="{{ $role }}">
            @endif

            <div class="min-w-0 flex-1">
                <label for="user-search" class="mb-1.5 block text-sm font-medium text-neutral-800">Cari pengguna</label>
                <x-input id="user-search" type="search" name="search" :value="$search" placeholder="Nama, email, atau nama UMKM">
                    <x-slot:icon>
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </x-slot:icon>
                </x-input>
            </div>

            <div class="lg:w-56">
                <label for="user-status" class="mb-1.5 block text-sm font-medium text-neutral-800">Status akun</label>
                <x-select id="user-status" name="status">
                    <option value="">Semua status</option>
                    <option value="active" @selected($status === 'active')>Aktif ({{ number_format($stats['active']) }})</option>
                    <option value="inactive" @selected($status === 'inactive')>Nonaktif ({{ number_format($stats['inactive']) }})</option>
                </x-select>
            </div>

            <div class="flex gap-2 *:flex-1 sm:*:flex-none">
                <x-button type="submit">Terapkan</x-button>
                @if ($hasFilters)
                    <x-button variant="ghost" :href="route('admin.users.index', array_filter(['role' => $role]))">Hapus filter</x-button>
                @endif
            </div>
        </form>

        <p class="text-sm text-neutral-600" aria-live="polite">
            Menampilkan <strong class="font-semibold tabular-nums text-neutral-900">{{ number_format($users->total()) }}</strong> pengguna
        </p>

        @if ($users->isEmpty())
            <x-empty-state title="Tidak ada pengguna ditemukan" description="Coba ubah kata kunci, peran, atau status akun.">
                <x-button variant="outline" :href="route('admin.users.index')">Tampilkan semua pengguna</x-button>
            </x-empty-state>
        @else
            <div class="hidden md:block">
                <x-table>
                    <x-slot:header>
                        <tr>
                            <x-table.th>Pengguna</x-table.th>
                            <x-table.th>Peran</x-table.th>
                            <x-table.th>UMKM terkait</x-table.th>
                            <x-table.th>Status</x-table.th>
                            <x-table.th align="right">Aksi</x-table.th>
                        </tr>
                    </x-slot:header>

                    @foreach ($users as $user)
                        @php($info = $describeUser($user))
                        <tr>
                            <x-table.td class="whitespace-normal!">
                                <div class="flex items-center gap-3">
                                    <span class="flex size-9 shrink-0 items-center justify-center rounded-full border border-neutral-200 bg-neutral-100 text-xs font-semibold text-neutral-700" aria-hidden="true">{{ strtoupper(substr($user->name, 0, 2)) }}</span>
                                    <div class="min-w-0">
                                        <a href="{{ route('admin.users.show', $user) }}" class="font-semibold text-neutral-950 hover:text-brand-700 hover:underline">{{ $user->name }}</a>
                                        @if (auth()->id() === $user->id)
                                            <span class="ml-1 rounded bg-brand-50 px-1.5 py-0.5 text-xs font-semibold text-brand-700">Anda</span>
                                        @endif
                                        <p class="break-words text-sm text-neutral-600">{{ $user->email }}</p>
                                    </div>
                                </div>
                            </x-table.td>
                            <x-table.td class="text-sm text-neutral-800">{{ $info['label'] }}</x-table.td>
                            <x-table.td class="whitespace-normal! text-sm">
                                @if ($info['business'])
                                    <a href="{{ route('admin.businesses.show', $info['business']) }}" class="font-medium text-neutral-900 hover:text-brand-700 hover:underline">{{ $info['business']->name }}</a>
                                    @if ($info['counters'])
                                        <p class="text-xs text-pretty text-neutral-600">Loket: {{ $info['counters'] }}</p>
                                    @endif
                                @elseif ($user->isMerchant())
                                    <span class="text-amber-800">Profil UMKM belum lengkap</span>
                                @else
                                    <span class="text-neutral-500">&mdash;</span>
                                @endif
                            </x-table.td>
                            <x-table.td><x-status-badge :status="$user->is_active ? 'aktif' : 'nonaktif'" size="sm" /></x-table.td>
                            <x-table.td align="right">
                                <div class="inline-flex items-center justify-end gap-2">
                                    <x-button variant="ghost" size="sm" :href="route('admin.users.show', $user)">Detail</x-button>
                                    @if (auth()->id() !== $user->id)
                                        @if ($user->is_active)
                                            <x-button
                                                variant="outline"
                                                size="sm"
                                                class="text-rose-700! hover:border-rose-300! hover:bg-rose-50!"
                                                data-name="{{ $user->name }}"
                                                data-email="{{ $user->email }}"
                                                @click="targetUserId = {{ $user->id }}; targetUserName = $el.dataset.name; targetUserEmail = $el.dataset.email; targetUserRole = '{{ $user->role }}'; deactivateModalOpen = true;"
                                            >Nonaktifkan</x-button>
                                        @else
                                            <form method="POST" action="{{ route('admin.users.activate', $user) }}" onsubmit="return confirm(@js('Aktifkan kembali akun '.$user->name.'?'))">
                                                @csrf
                                                <x-button type="submit" variant="outline" size="sm">Aktifkan</x-button>
                                            </form>
                                        @endif
                                    @endif
                                </div>
                            </x-table.td>
                        </tr>
                    @endforeach

                    @if ($users->hasPages())
                        <x-slot:footer>
                            <x-pagination :paginator="$users" />
                        </x-slot:footer>
                    @endif
                </x-table>
            </div>

            <div class="md:hidden">
                <x-card padding="none">
                    <ul class="divide-y divide-neutral-100">
                        @foreach ($users as $user)
                            @php($info = $describeUser($user))
                            <li class="space-y-2 p-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <a href="{{ route('admin.users.show', $user) }}" class="font-semibold text-neutral-950">{{ $user->name }}</a>
                                        <p class="break-words text-sm text-neutral-600">{{ $user->email }}</p>
                                    </div>
                                    <x-status-badge :status="$user->is_active ? 'aktif' : 'nonaktif'" size="sm" class="shrink-0" />
                                </div>
                                <p class="text-sm text-neutral-700">
                                    {{ $info['label'] }}@if ($info['business']), {{ $info['business']->name }}@endif
                                </p>
                            </li>
                        @endforeach
                    </ul>
                    @if ($users->hasPages())
                        <x-slot:footer>
                            <x-pagination :paginator="$users" />
                        </x-slot:footer>
                    @endif
                </x-card>
            </div>
        @endif

        @include('admin.partials.user-deactivate-dialog')
    </div>
</x-layouts.admin>
