@props([
    'status' => 'menunggu',
    'size' => 'md',
    'showIcon' => true,
])

@php
    $normalized = strtolower(trim((string) $status));

    $config = match ($normalized) {
        // Queue Ticket Statuses
        'menunggu', 'waiting' => [
            'label' => 'Menunggu',
            'classes' => 'bg-warning-100 text-warning-700 border-amber-200',
            'dot' => 'bg-amber-500',
            'icon' => 'clock',
        ],
        'dipanggil', 'called' => [
            'label' => 'Dipanggil',
            'classes' => 'bg-info-100 text-info-700 border-blue-200 animate-pulse',
            'dot' => 'bg-blue-600',
            'icon' => 'megaphone',
        ],
        'sedang_dilayani', 'serving' => [
            'label' => 'Sedang dilayani',
            'classes' => 'bg-brand-100 text-brand-700 border-blue-300 font-semibold',
            'dot' => 'bg-brand-600',
            'icon' => 'sparkles',
        ],
        'selesai', 'completed' => [
            'label' => 'Selesai',
            'classes' => 'bg-success-100 text-success-700 border-emerald-200',
            'dot' => 'bg-emerald-600',
            'icon' => 'check-circle',
        ],
        'dilewati', 'skipped' => [
            'label' => 'Dilewati',
            'classes' => 'bg-neutral-100 text-neutral-600 border-neutral-300',
            'dot' => 'bg-neutral-400',
            'icon' => 'forward',
        ],
        'dibatalkan', 'cancelled' => [
            'label' => 'Dibatalkan',
            'classes' => 'bg-danger-100 text-danger-700 border-rose-200',
            'dot' => 'bg-rose-600',
            'icon' => 'x-circle',
        ],

        // Business Operational Statuses
        'buka', 'open' => [
            'label' => 'Buka',
            'classes' => 'bg-emerald-50 text-emerald-700 border-emerald-300',
            'dot' => 'bg-emerald-500',
            'icon' => 'dot',
        ],
        'jeda', 'break' => [
            'label' => 'Jeda',
            'classes' => 'bg-amber-50 text-amber-700 border-amber-300',
            'dot' => 'bg-amber-500',
            'icon' => 'dot',
        ],
        'tutup', 'closed' => [
            'label' => 'Tutup',
            'classes' => 'bg-neutral-100 text-neutral-700 border-neutral-300',
            'dot' => 'bg-neutral-500',
            'icon' => 'dot',
        ],

        // Account Statuses
        'aktif', 'active' => [
            'label' => 'Aktif',
            'classes' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'dot' => 'bg-emerald-500',
            'icon' => 'dot',
        ],
        'nonaktif', 'inactive' => [
            'label' => 'Nonaktif',
            'classes' => 'bg-neutral-100 text-neutral-600 border-neutral-200',
            'dot' => 'bg-neutral-400',
            'icon' => 'dot',
        ],
        'suspend', 'suspended' => [
            'label' => 'Ditangguhkan',
            'classes' => 'bg-red-50 text-red-700 border-red-200',
            'dot' => 'bg-red-500',
            'icon' => 'dot',
        ],

        default => [
            'label' => ucfirst($status),
            'classes' => 'bg-neutral-100 text-neutral-700 border-neutral-200',
            'dot' => 'bg-neutral-500',
            'icon' => 'dot',
        ],
    };

    $sizeClasses = match ($size) {
        'sm' => 'px-2 py-0.5 text-[11px] gap-1',
        'lg' => 'px-3.5 py-1.5 text-body gap-2',
        default => 'px-2.5 py-1 text-caption gap-1.5',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center font-medium rounded-full border select-none {$config['classes']} {$sizeClasses}"]) }}>
    @if ($showIcon)
        @if ($config['icon'] === 'clock')
            <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        @elseif ($config['icon'] === 'megaphone')
            <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
        @elseif ($config['icon'] === 'sparkles')
            <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
        @elseif ($config['icon'] === 'check-circle')
            <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        @elseif ($config['icon'] === 'forward')
            <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"/></svg>
        @elseif ($config['icon'] === 'x-circle')
            <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        @else
            <span class="w-1.5 h-1.5 rounded-full {{ $config['dot'] }} shrink-0"></span>
        @endif
    @endif

    <span>{{ $slot->isNotEmpty() ? $slot : $config['label'] }}</span>
</span>

