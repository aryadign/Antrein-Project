@php
    $navItems = [
        ['name' => 'Beranda', 'route' => 'merchant.dashboard', 'icon' => 'home'],
        ['name' => 'Antrean', 'route' => 'merchant.queues', 'icon' => 'queue'],
        ['name' => 'Layanan', 'route' => 'merchant.services.index', 'icon' => 'scissors'],
        ['name' => 'Laporan', 'route' => 'merchant.reports', 'icon' => 'chart'],
        ['name' => 'Pengaturan', 'route' => 'merchant.settings', 'icon' => 'cog'],
    ];

    $currentRoute = request()->route() ? request()->route()->getName() : '';
@endphp

@foreach ($navItems as $item)
    @php
        $isActive = $currentRoute === $item['route'] || str_starts_with($currentRoute, $item['route']);
    @endphp
    <a 
        href="{{ Route::has($item['route']) ? route($item['route']) : '#' . $item['route'] }}" 
        class="flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-body font-medium transition-colors select-none {{ $isActive ? 'bg-brand-50 text-brand-700 font-semibold' : 'text-neutral-600 hover:text-neutral-950 hover:bg-neutral-100' }}"
    >
        @if ($item['icon'] === 'home')
            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
        @elseif ($item['icon'] === 'queue')
            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
        @elseif ($item['icon'] === 'scissors')
            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.121 14.121L19 19m-7-7l7-7m-7 7l-2.879 2.879a3 3 0 11-4.242-4.242 3 3 0 014.242 0M12 12L9.121 9.121m0 5.758a3 3 0 10-4.243 4.243 3 3 0 004.243-4.243z"/></svg>
        @elseif ($item['icon'] === 'chart')
            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
        @elseif ($item['icon'] === 'cog')
            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
        @endif
        <span>{{ $item['name'] }}</span>
    </a>
@endforeach

