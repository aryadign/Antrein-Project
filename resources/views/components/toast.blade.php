<div 
    x-data="{
        messages: [],
        add(message) {
            const id = Date.now() + Math.random();
            this.messages.push({ id, ...message });
            setTimeout(() => this.remove(id), message.duration || 4500);
        },
        remove(id) {
            this.messages = this.messages.filter(m => m.id !== id);
        }
    }"
    x-init="
        @if (session()->has('success'))
            add({ type: 'success', text: @js(session('success')) });
        @endif
        @if (session()->has('error'))
            add({ type: 'danger', text: @js(session('error')) });
        @endif
        @if (session()->has('warning'))
            add({ type: 'warning', text: @js(session('warning')) });
        @endif
        @if (session()->has('info'))
            add({ type: 'info', text: @js(session('info')) });
        @endif
    "
    @notify.window="add($event.detail)"
    class="fixed top-4 right-4 z-50 flex flex-col gap-2.5 max-w-sm w-full pointer-events-none px-4 sm:px-0"
    role="status"
    aria-live="polite"
>
    <template x-for="msg in messages" :key="msg.id">
        <div 
            x-transition:enter="transform ease-out duration-300 transition"
            x-transition:enter-start="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-4"
            x-transition:enter-end="translate-y-0 opacity-100 sm:translate-x-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="pointer-events-auto flex items-start p-4 rounded-xl shadow-lg border text-body bg-white"
            :class="{
                'border-emerald-200 bg-emerald-50/40 text-emerald-950': msg.type === 'success',
                'border-rose-200 bg-rose-50/40 text-rose-950': msg.type === 'danger' || msg.type === 'error',
                'border-amber-200 bg-amber-50/40 text-amber-950': msg.type === 'warning',
                'border-blue-200 bg-blue-50/40 text-blue-950': msg.type === 'info',
            }"
        >
            {{-- Icon --}}
            <div class="shrink-0 mr-3 mt-0.5">
                <template x-if="msg.type === 'success'">
                    <svg class="w-5 h-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </template>
                <template x-if="msg.type === 'danger' || msg.type === 'error'">
                    <svg class="w-5 h-5 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </template>
                <template x-if="msg.type === 'warning'">
                    <svg class="w-5 h-5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </template>
                <template x-if="msg.type === 'info'">
                    <svg class="w-5 h-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </template>
            </div>

            {{-- Body --}}
            <div class="flex-1 text-body font-medium" x-text="msg.text"></div>

            {{-- Close Button --}}
            <button 
                @click="remove(msg.id)"
                class="shrink-0 ml-3 text-neutral-400 hover:text-neutral-600 transition"
                aria-label="Tutup"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    </template>
</div>

