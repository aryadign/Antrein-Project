@php
    use App\Models\QueueTicket;

    $timezoneLabel = match ($business->timezone) {
        'Asia/Jakarta' => 'WIB',
        'Asia/Jayapura' => 'WIT',
        default => 'WITA',
    };

    $statusBreakdown = collect($queueStats)->except('total')->map(fn (int $count, string $status): array => [
        'status' => $status,
        'label' => $status === QueueTicket::STATUS_DILEWATI ? 'Dilewati / tidak hadir' : QueueTicket::labelForStatus($status),
        'count' => $count,
    ])->values();

    $activeTickets = $queueStats['menunggu'] + $queueStats['dipanggil'] + $queueStats['sedang_dilayani'];

    $profileFields = [
        ['label' => 'Kategori', 'value' => $business->category?->name ?? 'Tanpa kategori'],
        ['label' => 'Pemilik', 'value' => $business->user?->name ?? 'Belum tercatat', 'sub' => $business->user?->email],
        ['label' => 'Nomor kontak', 'value' => $business->phone ?: 'Belum diisi'],
        ['label' => 'Zona waktu', 'value' => $timezoneLabel.' ('.$business->timezone.')'],
        ['label' => 'Kode toko', 'value' => $business->slug],
        ['label' => 'Terdaftar', 'value' => $business->created_at->translatedFormat('d F Y')],
    ];

    $needsDecision = $business->approval_status !== 'approved';
@endphp

<x-layouts.admin
    title="Detail UMKM {{ $business->name }}"
    heading="{{ $business->name }}"
    description="Profil, layanan, jam buka, dan riwayat antrean UMKM ini."
>
    <div
        class="mx-auto max-w-5xl space-y-6"
        x-data="{ rejectModalOpen: false, suspendModalOpen: false, targetBusinessId: {{ $business->id }}, targetBusinessName: @js($business->name), currentSuspended: {{ $business->is_suspended ? 'true' : 'false' }} }"
    >
        @include('admin.partials.flash')

        <a href="{{ route('admin.businesses.index') }}" class="-my-2 inline-flex min-h-11 items-center gap-2 text-sm font-medium text-neutral-600 transition-colors hover:text-neutral-900">
            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Kembali ke daftar UMKM
        </a>

        {{-- Summary --}}
        <x-card padding="sm">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex min-w-0 items-center gap-4">
                    <div class="flex size-14 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-neutral-200 bg-brand-50 text-lg font-bold text-brand-700">
                        @if ($business->logo_path)
                            <img src="{{ $business->getLogoUrl() }}" alt="Logo {{ $business->name }}" class="size-full object-cover">
                        @else
                            {{ strtoupper(substr($business->name, 0, 2)) }}
                        @endif
                    </div>
                    <div class="min-w-0 space-y-1.5">
                        <div class="flex flex-wrap items-center gap-1.5">
                            <x-status-badge :status="$business->approval_status" size="sm" />
                            @if ($business->is_suspended)
                                <x-status-badge status="suspended" size="sm" />
                            @endif
                            <x-status-badge :status="$business->status" size="sm" />
                        </div>
                        @if ($business->approval_status === 'approved')
                            <p class="text-sm text-neutral-600">
                                Disetujui{{ $business->reviewedBy ? ' oleh '.$business->reviewedBy->name : '' }}{{ $business->reviewed_at ? ' pada '.$business->reviewed_at->translatedFormat('d M Y, H:i') : '' }}
                            </p>
                        @endif
                    </div>
                </div>

                @if ($business->approval_status === 'approved')
                    @if ($business->is_suspended)
                        <x-button variant="outline" class="w-full sm:w-auto" @click="suspendModalOpen = true">Pulihkan akun</x-button>
                    @else
                        <x-button variant="outline" class="w-full text-rose-700! hover:border-rose-300! hover:bg-rose-50! sm:w-auto" @click="suspendModalOpen = true">Tangguhkan akun</x-button>
                    @endif
                @endif
            </div>
        </x-card>

        {{-- Verification decision: only when the admin still has to act --}}
        @if ($needsDecision)
            <section class="rounded-xl border p-4 shadow-raised sm:p-5 {{ $business->approval_status === 'pending' ? 'border-amber-200 bg-amber-50/60' : 'border-rose-200 bg-rose-50/50' }}" aria-labelledby="decision-heading">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="min-w-0">
                        <h2 id="decision-heading" class="text-h3 font-semibold text-neutral-950">
                            {{ $business->approval_status === 'pending' ? 'Pendaftaran menunggu keputusan Anda' : 'Pendaftaran ini ditolak' }}
                        </h2>
                        @if ($business->approval_status === 'pending')
                            <p class="mt-1 text-sm text-pretty text-neutral-700">Pemilik masih melihat halaman tunggu. Periksa profil, layanan, dan jam buka di bawah sebelum memutuskan.</p>
                        @else
                            <p class="mt-1 text-sm text-pretty text-neutral-700"><span class="font-medium text-neutral-900">Alasan:</span> {{ $business->rejection_reason ?: 'Tidak ada alasan tercatat.' }}</p>
                        @endif
                    </div>
                    <div class="flex shrink-0 gap-2 *:flex-1 sm:*:flex-none">
                        @if ($business->approval_status === 'pending')
                            <x-button variant="outline" class="text-rose-700! hover:border-rose-300! hover:bg-rose-50!" @click="rejectModalOpen = true">Tolak</x-button>
                        @endif
                        <form method="POST" action="{{ route('admin.businesses.approve', $business) }}">
                            @csrf
                            <x-button type="submit" class="w-full">{{ $business->approval_status === 'pending' ? 'Setujui pendaftaran' : 'Setujui sekarang' }}</x-button>
                        </form>
                    </div>
                </div>
            </section>
        @endif

        {{-- Profile --}}
        <x-card padding="none">
            <x-slot:header>
                <h2 class="text-h3 font-semibold text-neutral-900">Profil usaha</h2>
            </x-slot:header>
            <dl class="grid grid-cols-1 gap-x-6 gap-y-4 px-5 py-5 sm:grid-cols-2 sm:px-6 lg:grid-cols-3">
                @foreach ($profileFields as $field)
                    <div class="min-w-0">
                        <dt class="text-sm text-neutral-600">{{ $field['label'] }}</dt>
                        <dd class="mt-0.5 break-words font-medium text-neutral-900">{{ $field['value'] }}</dd>
                        @if (! empty($field['sub']))
                            <dd class="break-all text-sm text-neutral-600">{{ $field['sub'] }}</dd>
                        @endif
                    </div>
                @endforeach
                @if ($business->address)
                    <div class="sm:col-span-2 lg:col-span-3">
                        <dt class="text-sm text-neutral-600">Alamat</dt>
                        <dd class="mt-0.5 text-neutral-900">{{ $business->address }}</dd>
                    </div>
                @endif
                @if ($business->description)
                    <div class="sm:col-span-2 lg:col-span-3">
                        <dt class="text-sm text-neutral-600">Deskripsi</dt>
                        <dd class="mt-0.5 max-w-prose text-pretty text-neutral-800">{{ $business->description }}</dd>
                    </div>
                @endif
            </dl>
        </x-card>

        {{-- Queue statistics --}}
        <section aria-labelledby="queue-stats-heading" class="space-y-3">
            <h2 id="queue-stats-heading" class="text-h3 font-semibold text-neutral-900">Statistik antrean</h2>
            <div class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-3">
                <x-stat-tile label="Total tiket" :value="number_format($queueStats['total'])" hint="Sejak UMKM terdaftar" icon="ticket" />
                <x-stat-tile label="Selesai dilayani" :value="number_format($queueStats['selesai'])" tone="success" icon="check" />
                <x-stat-tile label="Masih berjalan" :value="number_format($activeTickets)" hint="Menunggu, dipanggil, dilayani" tone="warning" icon="clock" class="col-span-2 lg:col-span-1" />
            </div>
            <details class="group rounded-xl border border-neutral-200/80 bg-white shadow-card">
                <summary class="flex min-h-11 cursor-pointer list-none items-center justify-between gap-2 px-4 py-3 text-sm font-medium text-neutral-800 hover:bg-neutral-50 [&::-webkit-details-marker]:hidden">
                    Rincian per status
                    <svg class="size-4 text-neutral-500 transition-transform duration-200 group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </summary>
                <ul class="grid grid-cols-1 gap-x-6 border-t border-neutral-100 px-4 py-2 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($statusBreakdown as $row)
                        <li class="flex items-center justify-between gap-3 py-2">
                            <x-status-badge :status="$row['status']" size="sm">{{ $row['label'] }}</x-status-badge>
                            <span class="font-semibold tabular-nums text-neutral-900">{{ number_format($row['count']) }}</span>
                        </li>
                    @endforeach
                </ul>
            </details>
        </section>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-5">
            {{-- Services --}}
            <x-card padding="none" class="lg:col-span-3">
                <x-slot:header>
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="text-h3 font-semibold text-neutral-900">Layanan</h2>
                        <span class="text-sm tabular-nums text-neutral-600">{{ $business->services->count() }} layanan</span>
                    </div>
                </x-slot:header>

                @if ($business->services->isEmpty())
                    <p class="px-5 py-8 text-center text-sm text-neutral-600">Pemilik belum menambahkan layanan.</p>
                @else
                    <ul class="max-h-[36rem] divide-y divide-neutral-100 overflow-y-auto">
                        @foreach ($business->services as $srv)
                            <li class="flex items-start justify-between gap-3 px-5 py-3.5 sm:px-6">
                                <div class="flex min-w-0 items-start gap-3">
                                    <x-customer.service-thumb :service="$srv" :category="$business->category" class="size-12 shrink-0 rounded-lg border border-neutral-200" />
                                    <div class="min-w-0">
                                        <h3 class="text-sm font-semibold text-neutral-900">{{ $srv->name }}</h3>
                                        @if ($srv->description)
                                            <p class="mt-0.5 line-clamp-2 text-sm text-pretty text-neutral-600">{{ $srv->description }}</p>
                                        @endif
                                        <p class="mt-1 text-xs tabular-nums text-neutral-600">
                                            Sekitar {{ $srv->duration_minutes }} menit, kuota {{ $srv->daily_quota ? $srv->daily_quota.' tiket/hari' : 'tidak terbatas' }}
                                        </p>
                                    </div>
                                </div>
                                <div class="flex shrink-0 flex-col items-end gap-1.5">
                                    <span class="text-sm font-semibold tabular-nums text-neutral-900">Rp {{ number_format($srv->price, 0, ',', '.') }}</span>
                                    <x-status-badge :status="$srv->is_active ? 'aktif' : 'nonaktif'" size="sm" :show-icon="false" />
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-card>

            {{-- Operating hours --}}
            <x-card padding="none" class="self-start lg:col-span-2">
                <x-slot:header>
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="text-h3 font-semibold text-neutral-900">Jam buka</h2>
                        <span class="text-sm text-neutral-600">{{ $timezoneLabel }}</span>
                    </div>
                </x-slot:header>

                @if ($business->hours->isEmpty())
                    <p class="px-5 py-8 text-center text-sm text-neutral-600">Jam buka belum diatur.</p>
                @else
                    <dl class="divide-y divide-neutral-100 px-5 sm:px-6">
                        @foreach ($business->hours as $h)
                            <div class="flex items-center justify-between py-2.5 text-sm">
                                <dt class="font-medium text-neutral-800">{{ $h->getDayName() }}</dt>
                                @if ($h->is_closed)
                                    <dd class="text-neutral-600">Tutup</dd>
                                @else
                                    <dd class="tabular-nums text-neutral-800">{{ substr($h->open_time, 0, 5) }}&ndash;{{ substr($h->close_time, 0, 5) }} {{ $timezoneLabel }}</dd>
                                @endif
                            </div>
                        @endforeach
                    </dl>
                @endif
            </x-card>
        </div>

        {{-- Audit trail --}}
        <x-card padding="none">
            <x-slot:header>
                <h2 class="text-h3 font-semibold text-neutral-900">Log aktivitas terakhir</h2>
            </x-slot:header>
            @if ($recentLogs->isEmpty())
                <p class="px-5 py-8 text-center text-sm text-neutral-600">Belum ada aktivitas yang tercatat untuk UMKM ini.</p>
            @else
                <ul class="divide-y divide-neutral-100">
                    @foreach ($recentLogs as $log)
                        <li class="flex flex-col gap-1 px-5 py-3 sm:flex-row sm:items-start sm:justify-between sm:gap-4 sm:px-6">
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-neutral-900">{{ $log->description }}</p>
                                <p class="mt-0.5 text-xs text-neutral-600">Oleh {{ $log->user?->name ?? 'Sistem' }}</p>
                            </div>
                            <time datetime="{{ $log->created_at->toIso8601String() }}" class="shrink-0 text-xs tabular-nums text-neutral-600">{{ $log->created_at->translatedFormat('d M Y, H:i') }}</time>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-card>

        @include('admin.partials.business-dialogs')
    </div>
</x-layouts.admin>
