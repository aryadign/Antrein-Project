@php
    $statusTabs = collect([
        ['key' => null, 'label' => 'Semua', 'count' => $stats['total']],
        ['key' => 'pending', 'label' => 'Menunggu verifikasi', 'count' => $stats['pending']],
        ['key' => 'active', 'label' => 'Aktif', 'count' => $stats['active']],
        ['key' => 'rejected', 'label' => 'Ditolak', 'count' => $stats['rejected']],
        ['key' => 'suspended', 'label' => 'Ditangguhkan', 'count' => $stats['suspended']],
    ])->map(fn (array $tab): array => [
        'label' => $tab['label'],
        'count' => $tab['count'],
        'active' => ($status ?: null) === $tab['key'],
        'href' => route('admin.businesses.index', array_filter(['status' => $tab['key'], 'search' => $search, 'category_id' => $categoryId])),
    ])->all();

    $hasFilters = $search !== '' || $categoryId;
@endphp

<x-layouts.admin
    title="Manajemen UMKM"
    heading="Manajemen UMKM"
    description="Verifikasi pendaftaran baru dan kelola status UMKM yang terdaftar di platform."
>
    <div class="space-y-5" x-data="{ rejectModalOpen: false, suspendModalOpen: false, targetBusinessId: null, targetBusinessName: '', currentSuspended: false }">
        @include('admin.partials.flash')

        @include('admin.partials.filter-tabs', ['tabs' => $statusTabs, 'label' => 'Filter status UMKM'])

        <form method="GET" action="{{ route('admin.businesses.index') }}" class="flex flex-col gap-3 rounded-xl border border-neutral-200/80 bg-white p-3 shadow-card sm:p-4 lg:flex-row lg:items-end">
            @if ($status)
                <input type="hidden" name="status" value="{{ $status }}">
            @endif

            <div class="min-w-0 flex-1">
                <label for="business-search" class="mb-1.5 block text-sm font-medium text-neutral-800">Cari UMKM</label>
                <x-input id="business-search" type="search" name="search" :value="$search" placeholder="Nama usaha, pemilik, atau email">
                    <x-slot:icon>
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </x-slot:icon>
                </x-input>
            </div>

            <div class="lg:w-60">
                <label for="business-category" class="mb-1.5 block text-sm font-medium text-neutral-800">Kategori</label>
                <x-select id="business-category" name="category_id">
                    <option value="">Semua kategori</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat->id }}" @selected($categoryId == $cat->id)>{{ $cat->name }}</option>
                    @endforeach
                </x-select>
            </div>

            <div class="flex gap-2 *:flex-1 sm:*:flex-none">
                <x-button type="submit">Terapkan</x-button>
                @if ($hasFilters)
                    <x-button variant="ghost" :href="route('admin.businesses.index', array_filter(['status' => $status]))">Hapus filter</x-button>
                @endif
            </div>
        </form>

        <p class="text-sm text-neutral-600" aria-live="polite">
            Menampilkan <strong class="font-semibold tabular-nums text-neutral-900">{{ number_format($businesses->total()) }}</strong> UMKM
        </p>

        @if ($businesses->isEmpty())
            <x-empty-state
                title="Tidak ada UMKM ditemukan"
                :description="$hasFilters || $status ? 'Tidak ada UMKM yang cocok dengan filter ini. Coba ubah kata kunci atau status.' : 'Belum ada UMKM yang mendaftar di platform.'"
            >
                @if ($hasFilters || $status)
                    <x-button variant="outline" :href="route('admin.businesses.index')">Tampilkan semua UMKM</x-button>
                @endif
            </x-empty-state>
        @else
            {{-- Desktop table --}}
            <div class="hidden md:block">
                <x-table>
                    <x-slot:header>
                        <tr>
                            <x-table.th>UMKM</x-table.th>
                            <x-table.th>Kategori &amp; aktivitas</x-table.th>
                            <x-table.th>Terdaftar</x-table.th>
                            <x-table.th>Status</x-table.th>
                            <x-table.th align="right">Aksi</x-table.th>
                        </tr>
                    </x-slot:header>

                    @foreach ($businesses as $b)
                        <tr>
                            <x-table.td class="whitespace-normal!">
                                <a href="{{ route('admin.businesses.show', $b) }}" class="font-semibold text-neutral-950 hover:text-brand-700 hover:underline">{{ $b->name }}</a>
                                <p class="mt-0.5 text-sm text-neutral-600">{{ $b->user?->name ?? 'Pemilik belum tercatat' }}</p>
                                <p class="text-xs text-neutral-500">{{ $b->user?->email }}</p>
                            </x-table.td>
                            <x-table.td class="whitespace-normal! text-sm">
                                <p class="w-40 text-neutral-800">{{ $b->category?->name ?? 'Tanpa kategori' }}</p>
                                <p class="text-xs text-neutral-500">{{ number_format($b->services_count) }} layanan, {{ number_format($b->queue_tickets_count) }} tiket</p>
                            </x-table.td>
                            <x-table.td class="text-sm text-neutral-600">{{ $b->created_at->translatedFormat('d M Y') }}</x-table.td>
                            <x-table.td>
                                <div class="flex flex-col items-start gap-1">
                                    <x-status-badge :status="$b->is_suspended ? 'suspended' : ($b->approval_status === 'approved' ? 'aktif' : $b->approval_status)" size="sm" />
                                </div>
                            </x-table.td>
                            <x-table.td align="right">
                                <div class="inline-flex items-center justify-end gap-2">
                                    @if ($b->approval_status === 'pending')
                                        <x-button
                                            variant="outline"
                                            size="sm"
                                            class="text-rose-700! hover:border-rose-300! hover:bg-rose-50!"
                                            data-business-name="{{ $b->name }}"
                                            @click="targetBusinessId = {{ $b->id }}; targetBusinessName = $el.dataset.businessName; rejectModalOpen = true;"
                                        >Tolak</x-button>
                                        <form method="POST" action="{{ route('admin.businesses.approve', $b) }}">
                                            @csrf
                                            <x-button type="submit" size="sm">Setujui</x-button>
                                        </form>
                                    @else
                                        <x-button variant="outline" size="sm" :href="route('admin.businesses.show', $b)">Kelola</x-button>
                                    @endif
                                </div>
                            </x-table.td>
                        </tr>
                    @endforeach

                    @if ($businesses->hasPages())
                        <x-slot:footer>
                            <x-pagination :paginator="$businesses" />
                        </x-slot:footer>
                    @endif
                </x-table>
            </div>

            {{-- Mobile list --}}
            <div class="md:hidden">
                <x-card padding="none">
                    <ul class="divide-y divide-neutral-100">
                        @foreach ($businesses as $b)
                            <li class="space-y-3 p-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <a href="{{ route('admin.businesses.show', $b) }}" class="font-semibold text-neutral-950 hover:text-brand-700">{{ $b->name }}</a>
                                        <p class="mt-0.5 break-words text-sm text-neutral-600">{{ $b->user?->name ?? 'Pemilik belum tercatat' }}</p>
                                    </div>
                                    <div class="flex shrink-0 flex-col items-end gap-1">
                                        <x-status-badge :status="$b->is_suspended ? 'suspended' : ($b->approval_status === 'approved' ? 'aktif' : $b->approval_status)" size="sm" />
                                    </div>
                                </div>

                                <p class="text-xs text-neutral-600">
                                    {{ $b->category?->name ?? 'Tanpa kategori' }}
                                    <span aria-hidden="true">&ndash;</span>
                                    <span class="tabular-nums">{{ $b->services_count }} layanan, {{ $b->queue_tickets_count }} tiket</span>
                                </p>

                                @if ($b->approval_status === 'pending')
                                    <div class="grid grid-cols-2 gap-2">
                                        <x-button
                                            variant="outline"
                                            size="sm"
                                            class="text-rose-700!"
                                            data-business-name="{{ $b->name }}"
                                            @click="targetBusinessId = {{ $b->id }}; targetBusinessName = $el.dataset.businessName; rejectModalOpen = true;"
                                        >Tolak</x-button>
                                        <form method="POST" action="{{ route('admin.businesses.approve', $b) }}">
                                            @csrf
                                            <x-button type="submit" size="sm" class="w-full">Setujui</x-button>
                                        </form>
                                    </div>
                                @else
                                    <x-button variant="outline" size="sm" class="w-full" :href="route('admin.businesses.show', $b)">Kelola UMKM</x-button>
                                @endif
                            </li>
                        @endforeach
                    </ul>

                    @if ($businesses->hasPages())
                        <x-slot:footer>
                            <x-pagination :paginator="$businesses" />
                        </x-slot:footer>
                    @endif
                </x-card>
            </div>
        @endif

        @include('admin.partials.business-dialogs')
    </div>
</x-layouts.admin>
