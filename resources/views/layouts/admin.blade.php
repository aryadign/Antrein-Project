@props([
    'title' => 'Admin AntreIn',
    'heading' => null,
    'description' => null,
])

<x-layouts.base :title="$title . ' — AntreIn Admin'">
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
            class="fixed inset-0 bg-neutral-950/75 z-40 lg:hidden"
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
            class="fixed inset-y-0 left-0 z-50 w-72 bg-neutral-950 text-white flex flex-col lg:hidden"
            style="display: none;"
        >
            <div class="h-16 px-6 flex items-center justify-between border-b border-neutral-800">
                <div class="flex items-center gap-2">
                    <span class="text-xl font-bold tracking-tight text-white">AntreIn<span class="text-brand-500">.</span></span>
                    <span class="px-2 py-0.5 text-[10px] font-semibold tracking-wider uppercase bg-brand-700/60 text-brand-300 rounded border border-brand-500/30">Admin</span>
                </div>
                <button @click="sidebarOpen = false" class="text-neutral-400 hover:text-white p-1">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            
            <nav class="flex-1 px-4 py-4 space-y-1 overflow-y-auto">
                @include('layouts.partials.admin-nav')
            </nav>
        </div>

        {{-- Desktop Static Sidebar --}}
        <aside class="hidden lg:flex lg:flex-col lg:w-64 lg:fixed lg:inset-y-0 bg-neutral-950 text-white z-30 border-r border-neutral-800/80">
            <div class="h-16 px-6 flex items-center gap-2 border-b border-neutral-800/80">
                <span class="text-xl font-bold tracking-tight text-white">AntreIn<span class="text-brand-500">.</span></span>
                <span class="px-2 py-0.5 text-[10px] font-semibold tracking-wider uppercase bg-brand-700/60 text-brand-300 rounded border border-brand-500/30">Admin</span>
            </div>

            <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
                @include('layouts.partials.admin-nav')
            </nav>

            <div class="p-4 border-t border-neutral-800/80 text-caption text-neutral-500">
                <p>AntreIn Platform v1.0</p>
                <p class="text-[11px] text-neutral-600">Enterprise Pilot Release</p>
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
                        <h1 class="text-body font-semibold text-neutral-900 line-clamp-1">{{ $heading ?? $title }}</h1>
                    </div>
                </div>

                <div class="flex items-center gap-3 sm:gap-4">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-caption bg-emerald-50 text-emerald-700 border border-emerald-200 font-medium">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        Platform Online
                    </span>

                    {{-- Admin User Profile Dropdown --}}
                    <x-dropdown align="right" width="48">
                        <x-slot name="trigger">
                            <button class="flex items-center gap-2 p-1.5 rounded-lg hover:bg-neutral-100 transition focus:outline-none focus:ring-2 focus:ring-brand-500">
                                <div class="w-8 h-8 rounded-full bg-neutral-900 text-white flex items-center justify-center font-semibold text-caption">
                                    {{ strtoupper(substr(auth()->user()?->name ?? 'AD', 0, 2)) }}
                                </div>
                                <span class="hidden sm:inline text-label font-medium text-neutral-700">{{ auth()->user()?->name ?? 'Administrator' }}</span>
                                <svg class="w-4 h-4 text-neutral-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            <div class="px-4 py-2 border-b border-neutral-100">
                                <p class="text-caption font-medium text-neutral-900">{{ auth()->user()?->name ?? 'Admin AntreIn' }}</p>
                                <p class="text-[11px] text-neutral-500 truncate">{{ auth()->user()?->email ?? 'admin@antrein.id' }}</p>
                            </div>
                            <a href="{{ route('admin.settings') }}" class="block px-4 py-2 text-label text-neutral-700 hover:bg-neutral-50 transition">Pengaturan Platform</a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="w-full text-left px-4 py-2 text-label text-danger-700 hover:bg-red-50 transition border-t border-neutral-100">Keluar</button>
                            </form>
                        </x-slot>
                    </x-dropdown>
                </div>
            </header>

            {{-- Main Content --}}
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

