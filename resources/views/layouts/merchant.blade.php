@props([
    'title' => 'Merchant AntreIn',
    'heading' => null,
    'description' => null,
    'businessName' => auth()->user()?->business?->name ?? 'Unit Usaha AntreIn',
    'businessStatus' => auth()->user()?->business?->status ?? 'buka',
    'businessSlug' => auth()->user()?->business?->slug ?? '',
])

<x-layouts.base :title="$title . ' — AntreIn UMKM'">
    <div x-data="{ sidebarOpen: false }" class="min-h-screen bg-neutral-50 flex">
        {{-- Mobile Sidebar Backdrop --}}
        <div 
            x-show="sidebarOpen" 
            x-transition:enter="transition-opacity ease-linear duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity ease-linear duration-300"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 bg-neutral-950/60 z-40 lg:hidden"
            @click="sidebarOpen = false"
            style="display: none;"
        ></div>

        {{-- Mobile Drawer Sidebar --}}
        <div 
            x-show="sidebarOpen" 
            x-transition:enter="transition ease-in-out duration-300 transform"
            x-transition:enter-start="-translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transition ease-in-out duration-300 transform"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="-translate-x-full"
            class="fixed inset-y-0 left-0 z-50 w-72 bg-white text-neutral-800 flex flex-col border-r border-neutral-200 lg:hidden shadow-xl"
            style="display: none;"
        >
            <div class="h-16 px-6 flex items-center justify-between border-b border-neutral-200">
                <div class="flex items-center gap-2">
                    <span class="text-xl font-bold tracking-tight text-neutral-950">AntreIn<span class="text-brand-700">.</span></span>
                    <span class="px-2 py-0.5 text-[10px] font-semibold tracking-wider uppercase bg-brand-50 text-brand-700 rounded border border-brand-200">UMKM</span>
                </div>
                <button @click="sidebarOpen = false" class="text-neutral-400 hover:text-neutral-700 p-1">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            
            <nav class="flex-1 px-4 py-4 space-y-1 overflow-y-auto">
                @include('layouts.partials.merchant-nav')
            </nav>
        </div>

        {{-- Desktop Static White Sidebar --}}
        <aside class="hidden lg:flex lg:flex-col lg:w-64 lg:fixed lg:inset-y-0 bg-white text-neutral-800 z-30 border-r border-neutral-200">
            <div class="h-16 px-6 flex items-center gap-2 border-b border-neutral-200">
                <span class="text-xl font-bold tracking-tight text-neutral-950">AntreIn<span class="text-brand-700">.</span></span>
                <span class="px-2 py-0.5 text-[10px] font-semibold tracking-wider uppercase bg-brand-50 text-brand-700 rounded border border-brand-200">UMKM</span>
            </div>

            <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
                @include('layouts.partials.merchant-nav')
            </nav>

            {{-- Quick Public QR / Link Card in Sidebar --}}
            <div class="p-4 border-t border-neutral-200 bg-neutral-50/50 m-3 rounded-xl border border-neutral-200">
                <div class="text-caption font-semibold text-neutral-900 flex items-center justify-between">
                    <span>Tautan Publik</span>
                    <svg class="w-4 h-4 text-brand-700" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                </div>
                <p class="text-[11px] text-neutral-500 mt-0.5 truncate">/q/{{ $businessSlug }}</p>
                <div class="mt-2.5">
                    <a href="/q/{{ $businessSlug }}" target="_blank" class="w-full inline-flex items-center justify-center text-[12px] h-7 px-2.5 font-medium bg-white text-brand-700 border border-brand-200 rounded-md hover:bg-brand-50 transition shadow-xs">
                        Buka Halaman Antrean
                    </a>
                </div>
            </div>
        </aside>

        {{-- Main Area --}}
        <div class="lg:pl-64 flex-1 flex flex-col min-w-0">
            {{-- Topbar --}}
            <header class="h-16 bg-white border-b border-neutral-200 sticky top-0 z-20 flex items-center justify-between px-4 sm:px-6 lg:px-8">
                <div class="flex items-center gap-3">
                    <button @click="sidebarOpen = true" class="lg:hidden p-2 rounded-md text-neutral-600 hover:bg-neutral-100 focus:outline-none focus:ring-2 focus:ring-brand-500">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                    <div>
                        <span class="text-caption font-medium text-neutral-500 block">Unit Usaha</span>
                        <h1 class="text-body font-bold text-neutral-900 line-clamp-1">{{ $businessName }}</h1>
                    </div>
                </div>

                <div class="flex items-center gap-3 sm:gap-4">
                    {{-- Operational Status Indicator Badge --}}
                    <div class="flex items-center gap-2">
                        <span class="hidden sm:inline text-caption text-neutral-500 font-medium">Status Operasional:</span>
                        <x-status-badge :status="$businessStatus" size="md" />
                    </div>

                    {{-- Merchant Owner Dropdown --}}
                    <x-dropdown align="right" width="48">
                        <x-slot name="trigger">
                            <button class="flex items-center gap-2 p-1.5 rounded-lg hover:bg-neutral-100 transition focus:outline-none focus:ring-2 focus:ring-brand-500">
                                <div class="w-8 h-8 rounded-full bg-brand-700 text-white flex items-center justify-center font-semibold text-caption">
                                    {{ strtoupper(substr(auth()->user()?->name ?? 'UM', 0, 2)) }}
                                </div>
                                <span class="hidden sm:inline text-label font-medium text-neutral-700">{{ auth()->user()?->name ?? 'Pemilik Usaha' }}</span>
                                <svg class="w-4 h-4 text-neutral-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            <div class="px-4 py-2 border-b border-neutral-100">
                                <p class="text-caption font-medium text-neutral-900">{{ auth()->user()?->name ?? 'Pengguna' }}</p>
                                <p class="text-[11px] text-neutral-500 truncate">{{ auth()->user()?->email ?? 'user@antrein.id' }}</p>
                                <span class="inline-block mt-1 text-[10px] font-semibold px-2 py-0.5 rounded-full {{ auth()->user()?->isOwner() ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700' }}">
                                    {{ auth()->user()?->isOwner() ? 'Pemilik Usaha' : 'Staf Operator' }}
                                </span>
                            </div>
                            @if (auth()->user()?->isOwner())
                                <a href="{{ route('merchant.settings', ['tab' => 'profile']) }}" class="block px-4 py-2 text-label text-neutral-700 hover:bg-neutral-50 transition">Profil & Jam Buka</a>
                                <a href="{{ route('merchant.staff.index') }}" class="block px-4 py-2 text-label text-neutral-700 hover:bg-neutral-50 transition">Kelola Staf</a>
                                <a href="{{ route('merchant.settings', ['tab' => 'account']) }}" class="block px-4 py-2 text-label text-neutral-700 hover:bg-neutral-50 transition">Pengaturan Akun</a>
                            @endif
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="w-full text-left px-4 py-2 text-label text-danger-700 hover:bg-red-50 transition border-t border-neutral-100">Keluar</button>
                            </form>
                        </x-slot>
                    </x-dropdown>
                </div>
            </header>

            {{-- Main Content Area --}}
            <main class="flex-1 p-4 sm:p-6 lg:p-8">
                @if ($heading && $description)
                    <div class="mb-6">
                        <h2 class="text-h2 font-bold text-neutral-950">{{ $heading }}</h2>
                        <p class="text-body text-neutral-500 mt-1">{{ $description }}</p>
                    </div>
                @endif

                {{ $slot }}
            </main>
        </div>
    </div>
</x-layouts.base>

