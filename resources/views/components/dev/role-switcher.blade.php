@php
    $currentUser = auth()->user();
    $currentRoleName = 'Tamu / Guest';
    $currentRoleBadgeClass = 'bg-neutral-800 text-neutral-300 border-neutral-700';

    if ($currentUser) {
        if ($currentUser->isAdmin()) {
            $currentRoleName = 'Admin';
            $currentRoleBadgeClass = 'bg-rose-950 text-rose-300 border-rose-800';
        } elseif ($currentUser->isOwner()) {
            $currentRoleName = 'UMKM Owner';
            $currentRoleBadgeClass = 'bg-blue-950 text-blue-300 border-blue-800';
        } elseif ($currentUser->isOperator()) {
            $currentRoleName = 'Operator Loket';
            $currentRoleBadgeClass = 'bg-emerald-950 text-emerald-300 border-emerald-800';
        } else {
            $currentRoleName = 'Merchant';
            $currentRoleBadgeClass = 'bg-purple-950 text-purple-300 border-purple-800';
        }
    }
@endphp

<div 
    x-data="{ 
        open: false,
        toggle() { this.open = !this.open; },
        close() { this.open = false; }
    }"
    @keydown.escape.window="close()"
    @click.away="close()"
    class="fixed bottom-4 right-4 z-50 font-sans"
    id="dev-role-switcher"
>
    {{-- Trigger Pill --}}
    <button
        type="button"
        @click="toggle()"
        class="flex items-center gap-2.5 px-3.5 py-2 rounded-full bg-neutral-900/95 hover:bg-neutral-900 text-white border border-neutral-700/80 shadow-2xl backdrop-blur-md transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-brand-500"
        aria-label="Pengalih Persona Demo"
        :aria-expanded="open"
    >
        <span class="flex h-2 w-2 relative">
            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
            <span class="relative inline-flex rounded-full h-2 w-2 bg-amber-400"></span>
        </span>
        <span class="text-xs font-mono font-bold tracking-wider text-amber-300">DEV MODE</span>
        <span class="text-neutral-500 text-xs">|</span>
        <span class="text-xs font-semibold px-2 py-0.5 rounded-full border {{ $currentRoleBadgeClass }}">
            {{ $currentRoleName }}
        </span>
        <svg 
            class="w-3.5 h-3.5 text-neutral-400 transition-transform duration-200" 
            :class="{ 'rotate-180': open }" 
            fill="none" 
            viewBox="0 0 24 24" 
            stroke="currentColor"
        >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
    </button>

    {{-- Dropdown Panel --}}
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-2 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 translate-y-2 scale-95"
        class="absolute bottom-12 right-0 w-80 rounded-2xl bg-neutral-900/95 text-white border border-neutral-700/90 shadow-2xl backdrop-blur-lg p-4 space-y-3"
        style="display: none;"
    >
        {{-- Header --}}
        <div class="flex items-center justify-between border-b border-neutral-800 pb-2.5">
            <div>
                <div class="text-xs font-bold text-amber-300 tracking-wider uppercase font-mono">Peralihan Persona Demo</div>
                <div class="text-[11px] text-neutral-400">Pilih akun untuk langsung beralih role</div>
            </div>
            <span class="px-1.5 py-0.5 rounded text-[10px] font-mono bg-neutral-800 text-neutral-400 border border-neutral-700">
                LOCAL ONLY
            </span>
        </div>

        {{-- Current User Info --}}
        @if ($currentUser)
            <div class="bg-neutral-950/70 rounded-xl p-2.5 border border-neutral-800/80 text-xs space-y-0.5">
                <div class="text-neutral-400 text-[10px] uppercase font-semibold">Sedang Aktif:</div>
                <div class="font-bold text-neutral-200 truncate">{{ $currentUser->name }}</div>
                <div class="text-[11px] text-neutral-400 font-mono truncate">{{ $currentUser->email }}</div>
            </div>
        @else
            <div class="bg-neutral-950/70 rounded-xl p-2.5 border border-neutral-800/80 text-xs">
                <div class="text-neutral-400 text-[10px] uppercase font-semibold">Sedang Aktif:</div>
                <div class="font-bold text-neutral-300">Tamu Publik (Guest / Belum Login)</div>
            </div>
        @endif

        {{-- Persona Switch Buttons --}}
        <div class="space-y-1.5 pt-1">
            {{-- Admin Persona --}}
            <form method="POST" action="{{ route('dev.switch-persona') }}">
                @csrf
                <input type="hidden" name="persona" value="admin">
                <button
                    type="submit"
                    class="w-full flex items-center justify-between px-3 py-2 rounded-xl text-left bg-neutral-800/70 hover:bg-neutral-800 border border-neutral-700/50 hover:border-rose-500/50 transition group"
                >
                    <div>
                        <div class="text-xs font-bold text-neutral-200 group-hover:text-rose-400">Admin AntreIn</div>
                        <div class="text-[11px] text-neutral-400 font-mono">admin@antrein.id</div>
                    </div>
                    <span class="text-[10px] font-mono px-1.5 py-0.5 rounded bg-rose-950/80 text-rose-300 border border-rose-800/60">
                        Admin
                    </span>
                </button>
            </form>

            {{-- Owner Persona --}}
            <form method="POST" action="{{ route('dev.switch-persona') }}">
                @csrf
                <input type="hidden" name="persona" value="owner">
                <button
                    type="submit"
                    class="w-full flex items-center justify-between px-3 py-2 rounded-xl text-left bg-neutral-800/70 hover:bg-neutral-800 border border-neutral-700/50 hover:border-blue-500/50 transition group"
                >
                    <div>
                        <div class="text-xs font-bold text-neutral-200 group-hover:text-blue-400">Made Aryawan (Owner)</div>
                        <div class="text-[11px] text-neutral-400 font-mono">owner@garasi.id</div>
                    </div>
                    <span class="text-[10px] font-mono px-1.5 py-0.5 rounded bg-blue-950/80 text-blue-300 border border-blue-800/60">
                        Owner
                    </span>
                </button>
            </form>

            {{-- Operator Persona --}}
            <form method="POST" action="{{ route('dev.switch-persona') }}">
                @csrf
                <input type="hidden" name="persona" value="operator">
                <button
                    type="submit"
                    class="w-full flex items-center justify-between px-3 py-2 rounded-xl text-left bg-neutral-800/70 hover:bg-neutral-800 border border-neutral-700/50 hover:border-emerald-500/50 transition group"
                >
                    <div>
                        <div class="text-xs font-bold text-neutral-200 group-hover:text-emerald-400">Wayan Sudirga (Operator)</div>
                        <div class="text-[11px] text-neutral-400 font-mono">operator@garasi.id &bull; Loket 1</div>
                    </div>
                    <span class="text-[10px] font-mono px-1.5 py-0.5 rounded bg-emerald-950/80 text-emerald-300 border border-emerald-800/60">
                        Operator
                    </span>
                </button>
            </form>

            {{-- Customer Persona --}}
            <form method="POST" action="{{ route('dev.switch-persona') }}">
                @csrf
                <input type="hidden" name="persona" value="customer">
                <button
                    type="submit"
                    class="w-full flex items-center justify-between px-3 py-2 rounded-xl text-left bg-neutral-800/70 hover:bg-neutral-800 border border-neutral-700/50 hover:border-amber-500/50 transition group"
                >
                    <div>
                        <div class="text-xs font-bold text-neutral-200 group-hover:text-amber-400">Pelanggan (Tamu)</div>
                        <div class="text-[11px] text-neutral-400">Antrean Barbershop Garasi</div>
                    </div>
                    <span class="text-[10px] font-mono px-1.5 py-0.5 rounded bg-amber-950/80 text-amber-300 border border-amber-800/60">
                        Guest
                    </span>
                </button>
            </form>
        </div>

        {{-- Footer Links --}}
        <div class="pt-2 border-t border-neutral-800 flex items-center justify-between text-[11px] text-neutral-400">
            <a href="{{ route('preview.design-system') }}" class="hover:text-white underline">
                Design System
            </a>
            @if ($currentUser)
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-rose-400 hover:text-rose-300 hover:underline">
                        Logout
                    </button>
                </form>
            @else
                <a href="{{ route('login') }}" class="hover:text-white underline">
                    Halaman Login
                </a>
            @endif
        </div>
    </div>
</div>

