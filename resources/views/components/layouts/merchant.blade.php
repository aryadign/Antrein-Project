@props([
    'title' => 'Merchant AntreIn',
    'heading' => null,
    'description' => null,
    'businessName' => auth()->user()?->business?->name ?? 'Unit Usaha AntreIn',
    'businessStatus' => auth()->user()?->business?->status ?? 'buka',
    'businessSlug' => auth()->user()?->business?->slug ?? '',
    'businessLogoUrl' => auth()->user()?->business?->getLogoUrl(),
])

@php
    $currentUser = auth()->user();
    $isOwnerUser = $currentUser?->isOwner() ?? false;
    $activeBusinessId = $currentUser?->business?->id;
    $accessibleBranches = $isOwnerUser ? ($currentUser?->accessibleBusinesses() ?? collect()) : collect();
    $isCombinedScope = $isOwnerUser
        && ($currentUser?->canManageOrganization() ?? false)
        && session('merchant_dashboard_scope') === 'all'
        && $accessibleBranches->count() > 1;
    $businessInitials = collect(preg_split('/\s+/', trim((string) $businessName)))
        ->filter()
        ->take(2)
        ->map(fn (string $word): string => Str::upper(Str::substr($word, 0, 1)))
        ->implode('') ?: 'UM';
    $userInitials = Str::upper(Str::substr($currentUser?->name ?? 'UM', 0, 2));
@endphp

<x-layouts.base :title="$title . ' — AntreIn UMKM'">
    <div x-data="{ sidebarOpen: false }" @keydown.escape.window="sidebarOpen = false" class="min-h-screen bg-neutral-50 flex">
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
            class="fixed inset-y-0 left-0 z-50 w-[min(18rem,85vw)] bg-white text-neutral-800 flex flex-col border-r border-neutral-200 lg:hidden shadow-overlay"
            style="display: none;"
            role="dialog"
            aria-modal="true"
            aria-label="Menu navigasi UMKM"
        >
            <div class="h-16 px-4 flex items-center justify-between border-b border-neutral-200">
                <x-brand-logo size="sm" badge="UMKM" />
                <button type="button" @click="sidebarOpen = false" class="flex size-11 items-center justify-center rounded-lg text-neutral-500 transition-colors hover:bg-neutral-100 hover:text-neutral-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500" aria-label="Tutup menu">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="flex items-center gap-3 border-b border-neutral-200 px-4 py-3">
                <x-merchant.business-avatar :name="$businessName" :logo-url="$businessLogoUrl" :initials="$businessInitials" class="size-10" />
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-bold text-neutral-900" title="{{ $businessName }}">{{ $businessName }}</p>
                    <x-status-badge :status="$businessStatus" size="sm" class="mt-0.5" />
                </div>
            </div>

            <nav class="flex-1 px-3 py-3 space-y-1 overflow-y-auto" aria-label="Navigasi UMKM">
                @include('layouts.partials.merchant-nav')
            </nav>
        </div>

        {{-- Desktop Static White Sidebar --}}
        <aside class="hidden print:hidden! lg:flex lg:flex-col lg:w-64 lg:fixed lg:inset-y-0 bg-white text-neutral-800 z-30 border-r border-neutral-200/80 shadow-card">
            <div class="h-16 px-6 flex items-center border-b border-neutral-200">
                <x-brand-logo size="sm" badge="UMKM" />
            </div>

            <div class="mx-3 mt-3 flex items-center gap-3 rounded-lg bg-neutral-50 p-3">
                <x-merchant.business-avatar :name="$businessName" :logo-url="$businessLogoUrl" :initials="$businessInitials" class="size-10" />
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-bold text-neutral-900" title="{{ $businessName }}">{{ $businessName }}</p>
                    <p class="truncate text-xs text-neutral-500">{{ $currentUser?->isOwner() ? 'Pemilik Usaha' : 'Staf Operator' }}</p>
                </div>
            </div>

            <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto" aria-label="Navigasi UMKM">
                @include('layouts.partials.merchant-nav')
            </nav>
        </aside>

        {{-- Main Area --}}
        <div class="lg:pl-64 print:pl-0 flex-1 flex flex-col min-w-0">
            {{-- Topbar --}}
            <header class="print:hidden h-16 bg-white border-b border-neutral-200/80 shadow-[0_1px_2px_rgba(15,23,42,0.03)] sticky top-0 z-20 flex items-center justify-between gap-2 px-2 sm:px-6 lg:px-8">
                <div class="flex min-w-0 flex-1 items-center gap-1.5 sm:gap-3">
                    <button type="button" @click="sidebarOpen = true" class="lg:hidden flex size-11 shrink-0 items-center justify-center rounded-lg text-neutral-600 hover:bg-neutral-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500" aria-label="Buka menu" :aria-expanded="sidebarOpen.toString()">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>

                    <x-merchant.business-avatar :name="$businessName" :logo-url="$businessLogoUrl" :initials="$businessInitials" class="size-9 lg:hidden" />

                    <div class="min-w-0">
                        <span class="hidden text-caption font-medium text-neutral-500 sm:block">Unit Usaha</span>
                        <div class="flex min-w-0 items-center gap-1.5 sm:gap-2">
                            <h1 class="truncate text-sm font-bold text-neutral-900 sm:text-body" title="{{ $businessName }}">{{ $businessName }}</h1>

                            @if ($isOwnerUser && $accessibleBranches->count() > 1)
                                <div x-data="{ openBranch: false }" @keydown.escape="openBranch = false" class="relative shrink-0">
                                    <button
                                        @click="openBranch = ! openBranch"
                                        type="button"
                                        class="inline-flex h-9 items-center gap-1 rounded-lg bg-brand-50 px-2.5 text-xs font-semibold text-brand-700 ring-1 ring-inset ring-brand-100 transition-colors hover:bg-brand-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500"
                                        :aria-expanded="openBranch.toString()"
                                        aria-label="Ganti Cabang"
                                    >
                                        <span class="hidden sm:inline">{{ $isCombinedScope ? 'Semua Cabang' : 'Ganti Cabang' }}</span>
                                        <span class="sm:hidden">Cabang</span>
                                        <svg class="w-3 h-3 transition-transform" :class="{ 'rotate-180': openBranch }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </button>
                                    <div x-show="openBranch" @click.away="openBranch = false" x-cloak class="fixed inset-x-3 top-16 z-50 rounded-xl border border-neutral-200/80 bg-white py-1 shadow-overlay sm:absolute sm:inset-x-auto sm:left-0 sm:top-auto sm:mt-2 sm:w-64">
                                        <div class="px-3 py-2 text-xs font-semibold text-neutral-500 border-b border-neutral-100">
                                            Pilih cabang aktif
                                        </div>
                                        <div class="max-h-64 overflow-y-auto py-1">
                                            @if ($currentUser?->canManageOrganization())
                                                <form method="POST" action="{{ route('merchant.branches.switch') }}" class="border-b border-neutral-100 pb-1 mb-1">
                                                    @csrf
                                                    <input type="hidden" name="business_id" value="all">
                                                    <button type="submit" class="flex min-h-11 w-full items-center justify-between gap-2 px-3 text-left text-sm transition hover:bg-neutral-50 {{ $isCombinedScope ? 'bg-brand-50 text-brand-700 font-bold' : 'text-neutral-700 font-semibold' }}">
                                                        <span class="truncate">Semua Cabang (Gabungan)</span>
                                                        @if ($isCombinedScope)
                                                            <x-customer.icon name="check" class="size-4 shrink-0 text-brand-600" />
                                                        @endif
                                                    </button>
                                                </form>
                                            @endif
                                            @foreach ($accessibleBranches as $branch)
                                                <form method="POST" action="{{ route('merchant.branches.switch') }}">
                                                    @csrf
                                                    <input type="hidden" name="business_id" value="{{ $branch->id }}">
                                                    <button type="submit" class="flex min-h-11 w-full items-center justify-between gap-2 px-3 text-left text-sm transition hover:bg-neutral-50 {{ ! $isCombinedScope && $branch->id === $activeBusinessId ? 'bg-brand-50 text-brand-700 font-bold' : 'text-neutral-700' }}">
                                                        <span class="truncate">{{ $branch->name }}</span>
                                                        @if (! $isCombinedScope && $branch->id === $activeBusinessId)
                                                            <x-customer.icon name="check" class="size-4 shrink-0 text-brand-600" />
                                                        @endif
                                                    </button>
                                                </form>
                                            @endforeach
                                        </div>
                                        <div class="border-t border-neutral-100 px-3 bg-neutral-50/50">
                                            <a href="{{ route('merchant.branches.index') }}" class="flex min-h-11 items-center text-xs font-semibold text-brand-700 hover:text-brand-800">
                                                Kelola Semua Cabang &rarr;
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="flex shrink-0 items-center gap-1.5 sm:gap-4">
                    {{-- Operational Status Indicator Badge --}}
                    <div class="flex items-center gap-2">
                        <span class="hidden md:inline text-caption text-neutral-500 font-medium">Status Operasional:</span>
                        <span class="sm:hidden"><x-status-badge :status="$businessStatus" size="sm" /></span>
                        <span class="hidden sm:inline-flex"><x-status-badge :status="$businessStatus" size="md" /></span>
                    </div>

                    {{-- Merchant Owner Dropdown --}}
                    <x-dropdown align="right" width="56">
                        <x-slot name="trigger">
                            <button type="button" class="flex min-h-11 items-center gap-2 rounded-lg p-1 sm:p-1.5 hover:bg-neutral-100 transition focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500" aria-label="Menu akun">
                                <div class="w-8 h-8 rounded-full bg-neutral-800 text-white flex items-center justify-center font-semibold text-caption">
                                    {{ $userInitials }}
                                </div>
                                <span class="hidden md:inline max-w-[10rem] truncate text-label font-medium text-neutral-700" title="{{ $currentUser?->name ?? 'Pemilik Usaha' }}">{{ $currentUser?->name ?? 'Pemilik Usaha' }}</span>
                                <svg class="hidden sm:block w-4 h-4 text-neutral-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            <div class="px-4 py-2.5 border-b border-neutral-100">
                                <p class="truncate text-sm font-medium text-neutral-900">{{ $currentUser?->name ?? 'Pengguna' }}</p>
                                <p class="text-xs text-neutral-500 truncate">{{ $currentUser?->email ?? 'user@antrein.id' }}</p>
                                <span class="inline-block mt-1.5 text-xs font-medium px-2 py-0.5 rounded-full {{ $currentUser?->isOwner() ? 'bg-brand-50 text-brand-700' : 'bg-neutral-100 text-neutral-700' }}">
                                    {{ $currentUser?->isOwner() ? 'Pemilik Usaha' : 'Staf Operator' }}
                                </span>
                            </div>
                            @if ($currentUser?->isOwner())
                                <a href="{{ route('merchant.settings', ['tab' => 'account']) }}" class="flex min-h-11 items-center px-4 text-label text-neutral-700 hover:bg-neutral-50 transition">Pengaturan Akun</a>
                            @endif
                            <form method="POST" action="{{ route('logout') }}" @click.stop>
                                @csrf
                                <button type="submit" class="flex min-h-11 w-full items-center px-4 text-left text-label text-danger-700 hover:bg-red-50 transition border-t border-neutral-100">Keluar</button>
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
