<x-layouts.customer
    title="Tiket Pelacakan Kedaluwarsa"
    :business="$business"
    :backUrl="$business ? route('customer.queue', ['slug' => $business->slug]) : '/'"
>
    <div class="mx-auto max-w-md px-5 py-8 sm:py-16 md:px-0">
        <x-card padding="lg">
            <div class="flex flex-col gap-4">
                <div class="flex items-start gap-3">
                    <x-customer.icon name="clock" class="mt-1 size-5 text-gray-500" />
                    <div class="flex flex-col gap-1.5">
                        <h1 class="text-xl font-bold leading-7 text-gray-900 sm:text-2xl">Tiket Pelacakan Kedaluwarsa</h1>
                        <p class="text-sm leading-6 text-pretty text-gray-600">Akses pelacakan untuk tiket antrean ini telah kedaluwarsa setelah 24 jam status selesai atau dibatalkan.</p>
                    </div>
                </div>

                <p class="border-t border-gray-100 pt-4 text-sm leading-5 text-pretty text-gray-600">
                    <span class="font-semibold text-gray-900">Kebijakan Privasi Pelanggan:</span>
                    untuk melindungi privasi Anda, nama dan nomor telepon otomatis disembunyikan setelah tiket kedaluwarsa.
                </p>

                <x-button size="lg" :href="$business ? route('customer.queue', ['slug' => $business->slug]) : '/'" class="w-full">
                    {{ $business ? 'Kembali ke halaman kedai' : 'Halaman Utama AntreIn' }}
                </x-button>
            </div>
        </x-card>
    </div>
</x-layouts.customer>
