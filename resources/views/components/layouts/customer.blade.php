@props([
    'title' => 'Ambil Antrean',
    'business' => null,
    'backUrl' => null,
    'activeNav' => 'beranda',
])

@php
    $businessName = data_get($business, 'name');

    $navItems = [
        ['key' => 'beranda', 'label' => 'Beranda', 'icon' => 'home', 'route' => 'customer.dashboard', 'width' => 'w-16'],
        ['key' => 'antrean', 'label' => 'Antrean Saya', 'icon' => 'ticket', 'route' => 'customer.my-tickets', 'width' => 'w-[82px]'],
        ['key' => 'riwayat', 'label' => 'Riwayat', 'icon' => 'history', 'route' => 'customer.history', 'width' => 'w-16'],
        ['key' => 'profil', 'label' => 'Profil', 'icon' => 'user', 'route' => 'customer.profile', 'width' => 'w-16'],
    ];
@endphp

<x-layouts.base :title="$title . ($businessName ? '' : ' — AntreIn')">
    {{--
        Mobile (< md): Figma frame 430px with the bottom navbar.
        Desktop (≥ md): top navbar and a wide content container.
    --}}
    <div class="flex min-h-screen flex-col bg-gray-100 md:bg-white">
        @php($brandHomeUrl = auth()->check() && auth()->user()->isCustomer() ? route('customer.dashboard') : route('home'))
        <header class="sticky top-0 z-30 hidden border-b border-gray-200 bg-white/95 backdrop-blur md:block">
            <div class="mx-auto flex h-16 max-w-6xl items-center justify-between gap-6 px-8">
                <a href="{{ $brandHomeUrl }}" class="focus:outline-none">
                    <x-brand-logo size="sm" />
                </a>

                <nav class="flex items-center gap-1" aria-label="Navigasi pelanggan">
                    @foreach ($navItems as $item)
                        @php($isActive = $activeNav === $item['key'])
                        <a
                            href="{{ route($item['route']) }}"
                            @class([
                                'flex h-10 items-center gap-2 rounded-lg px-3 text-sm font-semibold transition-colors duration-200',
                                'bg-brand-50 text-brand-700' => $isActive,
                                'text-gray-700 hover:bg-gray-100 hover:text-gray-900' => ! $isActive,
                            ])
                            @if ($isActive) aria-current="page" @endif
                        >
                            <img src="{{ asset('images/figma/nav-'.$item['icon'].($isActive ? '-active' : '').'.svg') }}" alt="" width="22" height="22" class="size-5" />
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                </nav>

                <div class="flex items-center gap-2">
                    @auth
                        <span class="hidden lg:inline-flex items-center rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-700">
                            {{ auth()->user()->name }}
                        </span>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="inline-flex h-9 items-center rounded-lg border border-gray-200 bg-white px-3 text-xs font-semibold text-gray-700 transition hover:bg-gray-50">
                                Keluar
                            </button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="inline-flex h-9 items-center rounded-lg border border-gray-200 bg-white px-3.5 text-xs font-bold text-gray-800 transition hover:bg-gray-50">
                            Masuk
                        </a>
                        <a href="{{ route('register', ['type' => 'customer']) }}" class="inline-flex h-9 items-center rounded-lg bg-brand-700 px-3.5 text-xs font-bold text-white transition hover:bg-blue-900">
                            Daftar Pelanggan
                        </a>
                    @endauth
                </div>
            </div>
        </header>

        <div class="mx-auto flex w-full max-w-[430px] flex-1 flex-col bg-white pb-[85px] sm:shadow-[0_0_10px_rgba(0,0,0,0.06)] md:max-w-6xl md:bg-transparent md:pb-0 md:shadow-none">
            <div class="flex items-center justify-between border-b border-gray-100 px-5 py-3 md:hidden">
                <a href="{{ $brandHomeUrl }}" class="focus:outline-none">
                    <x-brand-logo size="xs" />
                </a>
                <div class="flex items-center gap-2">
                    @auth
                        <span class="max-w-[130px] truncate rounded-full bg-brand-50 px-2.5 py-1 text-[11px] font-bold text-brand-700">{{ auth()->user()->name }}</span>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="rounded-lg border border-gray-200 px-2.5 py-1 text-[11px] font-semibold text-gray-700">Keluar</button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="rounded-lg border border-gray-200 px-2.5 py-1 text-xs font-bold text-gray-800">Masuk</a>
                        <a href="{{ route('register', ['type' => 'customer']) }}" class="rounded-lg bg-brand-700 px-2.5 py-1 text-xs font-bold text-white">Daftar</a>
                    @endauth
                </div>
            </div>

            <main class="flex-1 md:px-8 md:py-8">
                @if ($backUrl)
                    <div class="px-5 pt-6 md:px-0 md:pt-0">
                        <a href="{{ $backUrl }}" class="inline-flex items-center gap-1 rounded-lg py-1 pr-2 text-sm font-semibold text-gray-700 transition hover:text-gray-900">
                            <img src="{{ asset('images/figma/chevron-left.svg') }}" alt="" width="24" height="24" class="size-5" />
                            Kembali
                        </a>
                    </div>
                @endif

                {{ $slot }}
            </main>

            <footer class="px-5 py-6 text-center text-xs text-gray-500">
                <p class="font-medium">Powered by AntreIn — Platform Antrean Digital UMKM</p>
            </footer>
        </div>

        {{-- Navigasi konsumen mobile (Figma: Navbar 85px) --}}
        <nav
            class="fixed bottom-0 left-1/2 z-40 h-[85px] w-full max-w-[430px] -translate-x-1/2 border border-gray-200 bg-white shadow-[0_0_10px_rgba(0,0,0,0.1)] md:hidden"
            aria-label="Navigasi pelanggan"
        >
            <div class="flex h-full items-start justify-between px-5 pt-[13px]">
                @foreach ($navItems as $item)
                    @php($isActive = $activeNav === $item['key'])
                    <a
                        href="{{ route($item['route']) }}"
                        @class([
                            'flex h-[38px] flex-col items-center justify-center gap-[3px] rounded-lg transition-colors duration-200',
                            $item['width'],
                            'text-brand-700' => $isActive,
                            'text-gray-400 hover:text-gray-700' => ! $isActive,
                        ])
                        @if ($isActive) aria-current="page" @endif
                    >
                        <img
                            src="{{ asset('images/figma/nav-'.$item['icon'].($isActive ? '-active' : '').'.svg') }}"
                            alt=""
                            width="22"
                            height="22"
                            class="size-[22px]"
                        />
                        <span class="text-[10px] font-semibold leading-4 whitespace-nowrap">{{ $item['label'] }}</span>
                    </a>
                @endforeach
            </div>
        </nav>
    </div>
</x-layouts.base>
