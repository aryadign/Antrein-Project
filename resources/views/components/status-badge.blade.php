@props([
    'status' => 'menunggu',
    'size' => 'md',
    'showIcon' => true,
])

{{-- Accepts a status string or a QueueTicket model. Ticket statuses use \App\Models\QueueTicket::statusPresentation() for label + tone. --}}
@php
    $rawStatus = $status instanceof \App\Models\QueueTicket ? $status->status : $status;
    $normalized = strtolower(trim((string) $rawStatus));

    $toneClasses = [
        'warning' => ['classes' => 'bg-amber-50 text-amber-800 border-amber-200', 'dot' => 'bg-amber-500'],
        'info' => ['classes' => 'bg-blue-50 text-blue-800 border-blue-200 font-semibold', 'dot' => 'bg-blue-600'],
        'brand' => ['classes' => 'bg-brand-100 text-brand-800 border-brand-300 font-semibold', 'dot' => 'bg-brand-600'],
        'success' => ['classes' => 'bg-emerald-50 text-emerald-800 border-emerald-200', 'dot' => 'bg-emerald-600'],
        'neutral' => ['classes' => 'bg-neutral-100 text-neutral-700 border-neutral-200', 'dot' => 'bg-neutral-400'],
        'caution' => ['classes' => 'bg-orange-50 text-orange-800 border-orange-200', 'dot' => 'bg-orange-500'],
        'danger' => ['classes' => 'bg-rose-50 text-rose-800 border-rose-200', 'dot' => 'bg-rose-600'],
    ];

    $ticketIcons = [
        'warning' => 'clock',
        'info' => 'megaphone',
        'brand' => 'sparkles',
        'success' => 'check-circle',
        'neutral' => 'forward',
        'caution' => 'x-circle',
        'danger' => 'x-circle',
    ];

    if (\App\Models\QueueTicket::isTicketStatus($normalized)) {
        $tone = \App\Models\QueueTicket::toneForStatus($normalized);
        $config = [
            'label' => \App\Models\QueueTicket::labelForStatus($normalized),
            ...$toneClasses[$tone],
            'icon' => $ticketIcons[$tone],
        ];
    } else {
        $config = match ($normalized) {
            // Business operational statuses
            'buka', 'open' => ['label' => 'Buka', ...$toneClasses['success'], 'icon' => 'open-clock'],
            'jeda', 'break' => ['label' => 'Jeda', ...$toneClasses['warning'], 'icon' => 'pause'],
            'tutup', 'closed' => ['label' => 'Tutup', ...$toneClasses['neutral'], 'icon' => 'lock'],

            // Account statuses
            'aktif', 'active' => ['label' => 'Aktif', ...$toneClasses['success'], 'icon' => 'check-circle'],
            'nonaktif', 'inactive' => ['label' => 'Nonaktif', ...$toneClasses['neutral'], 'icon' => 'minus-circle'],
            'suspend', 'suspended' => ['label' => 'Ditangguhkan', ...$toneClasses['danger'], 'icon' => 'alert'],

            // UMKM approval statuses
            'pending' => ['label' => 'Menunggu Verifikasi', ...$toneClasses['warning'], 'icon' => 'clock'],
            'approved' => ['label' => 'Disetujui', ...$toneClasses['success'], 'icon' => 'check-circle'],
            'rejected' => ['label' => 'Ditolak', ...$toneClasses['danger'], 'icon' => 'x-circle'],

            default => ['label' => ucfirst((string) $rawStatus), ...$toneClasses['neutral'], 'icon' => null],
        };
    }

    $sizeClasses = match ($size) {
        'sm' => 'px-2 py-0.5 text-xs gap-1',
        'lg' => 'px-3.5 py-1.5 text-sm gap-2',
        default => 'px-2.5 py-1 text-xs gap-1.5',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center whitespace-nowrap font-medium leading-4 rounded-full border select-none {$config['classes']} {$sizeClasses}"]) }}>
    @if ($showIcon)
        @if ($config['icon'] === 'clock')
            <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        @elseif ($config['icon'] === 'megaphone')
            <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
        @elseif ($config['icon'] === 'sparkles')
            <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
        @elseif ($config['icon'] === 'check-circle')
            <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        @elseif ($config['icon'] === 'forward')
            <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"/></svg>
        @elseif ($config['icon'] === 'x-circle')
            <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        @elseif ($config['icon'] === 'open-clock')
            <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        @elseif ($config['icon'] === 'pause')
            <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        @elseif ($config['icon'] === 'lock')
            <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><rect x="5" y="11" width="14" height="10" rx="2" stroke-width="2"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0v4"/></svg>
        @elseif ($config['icon'] === 'minus-circle')
            <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        @elseif ($config['icon'] === 'alert')
            <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
        @endif
    @endif

    <span>{{ $slot->isNotEmpty() ? $slot : $config['label'] }}</span>
</span>

