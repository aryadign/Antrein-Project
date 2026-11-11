<x-layouts.customer 
    title="Tautan Pelacakan Kedaluwarsa"
    :business="$business"
    :backUrl="$business ? route('customer.queue', ['slug' => $business->slug]) : '/'"
>
    <div class="max-w-md mx-auto py-8 sm:py-16 text-center">
        <x-card padding="lg" class="shadow-sm">
            <div class="w-16 h-16 rounded-2xl bg-neutral-100 text-neutral-400 mx-auto flex items-center justify-center mb-5">
                <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>

            <h1 class="text-xl sm:text-2xl font-extrabold text-neutral-950 tracking-tight">
                Tautan Pelacakan Kedaluwarsa
            </h1>

            <p class="text-sm text-neutral-600 mt-2 leading-relaxed">
                Akses pelacakan untuk tiket antrean ini telah kedaluwarsa setelah 24 jam status selesai atau dibatalkan.
            </p>

            <div class="p-4 rounded-xl bg-neutral-50 border border-neutral-200 mt-5 text-caption text-neutral-500 text-left">
                <div class="font-semibold text-neutral-700 mb-0.5">Kebijakan Privasi Pelanggan:</div>
                Untuk melindungi privasi Anda, data nama dan riwayat nomor telepon otomatis disembunyikan dari tautan publik setelah tiket kedaluwarsa.
            </div>

            <div class="mt-6 pt-2">
                @if ($business)
                    <a 
                        href="{{ route('customer.queue', ['slug' => $business->slug]) }}" 
                        class="inline-flex items-center justify-center w-full px-5 py-3 text-sm font-bold text-white bg-brand-700 hover:bg-brand-600 rounded-xl shadow-2xs transition focus:outline-none focus:ring-2 focus:ring-brand-500"
                    >
                        Kembali ke Halaman {{ $business->name }}
                    </a>
                @else
                    <a 
                        href="/" 
                        class="inline-flex items-center justify-center w-full px-5 py-3 text-sm font-bold text-white bg-brand-700 hover:bg-brand-600 rounded-xl shadow-2xs transition focus:outline-none focus:ring-2 focus:ring-brand-500"
                    >
                        Halaman Utama AntreIn
                    </a>
                @endif
            </div>
        </x-card>
    </div>
</x-layouts.customer>

