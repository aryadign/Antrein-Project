<x-layouts.merchant
    title="QR Code Antrean"
    :businessName="$business->name"
    :businessStatus="$business->status"
    :businessSlug="$business->slug"
>
    <div class="mx-auto max-w-3xl space-y-5 sm:space-y-6">
        {{-- Header --}}
        <div class="print:hidden">
            <h1 class="text-h2 font-bold text-neutral-950">QR Code Antrean</h1>
            <p class="mt-1 text-body text-neutral-600">
                Cetak atau tampilkan kode QR ini agar pelanggan yang telah masuk ke akun AntreIn dapat memindai dan mengambil nomor antrean langsung di lokasi usaha Anda.
            </p>
        </div>

        <div class="grid grid-cols-1 gap-5 md:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]">
            {{-- Printable QR Card --}}
            <section class="flex flex-col items-center rounded-2xl border border-neutral-200 bg-white p-6 text-center shadow-sm print:border-2 print:border-neutral-900 print:shadow-none" aria-label="Kartu QR untuk dicetak">
                <x-merchant.business-avatar :name="$business->name" :logo-url="$business->getLogoUrl()" class="mb-3 size-14 text-lg" />
                <h2 class="text-h3 font-bold text-neutral-900">{{ $business->name }}</h2>
                <p class="mt-1 text-sm font-semibold text-brand-700">Scan untuk Ambil Antrean</p>

                <div class="my-5 w-full max-w-[260px] rounded-2xl border-2 border-neutral-200 bg-white p-4 [&_svg]:h-auto [&_svg]:w-full">
                    {!! $qrSvg !!}
                </div>

                <ol class="grid w-full grid-cols-3 gap-2 text-[11px] font-semibold text-neutral-700">
                    <li class="rounded-lg bg-neutral-50 px-2 py-2"><span class="block text-base font-extrabold text-brand-700">1</span>Scan QR</li>
                    <li class="rounded-lg bg-neutral-50 px-2 py-2"><span class="block text-base font-extrabold text-brand-700">2</span>Masuk &amp; Antre</li>
                    <li class="rounded-lg bg-neutral-50 px-2 py-2"><span class="block text-base font-extrabold text-brand-700">3</span>Pantau Giliran</li>
                </ol>
                <p class="mt-3 text-[11px] text-neutral-500">Scan QR dengan kamera ponsel. Powered by AntreIn.</p>
            </section>

            {{-- QR Actions --}}
            <section class="flex flex-col gap-4 rounded-2xl border border-neutral-200 bg-white p-5 shadow-xs print:hidden">
                <div>
                    <h3 class="text-sm font-bold text-neutral-900">Unduh &amp; Cetak Kartu QR</h3>
                    <p class="mt-1 text-xs leading-relaxed text-neutral-600">
                        Gunakan kode QR resmi berikut untuk meja kasir, pintu masuk, atau papan informasi ruang tunggu toko Anda.
                    </p>
                </div>

                <div class="flex flex-col gap-2.5">
                    <a
                        href="{{ route('merchant.onboarding.download-qr') }}"
                        class="inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-brand-700 px-4 text-sm font-bold text-white shadow-sm transition hover:bg-brand-800"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        <span>Unduh Kode QR (SVG)</span>
                    </a>

                    <button
                        type="button"
                        onclick="window.print()"
                        class="inline-flex h-11 items-center justify-center gap-2 rounded-lg border border-neutral-300 bg-white px-4 text-sm font-semibold text-neutral-700 shadow-xs transition hover:bg-neutral-50"
                    >
                        <svg class="w-4 h-4 text-neutral-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                        Cetak Kartu QR
                    </button>
                </div>

                {{-- Guide Tips --}}
                <div class="mt-auto rounded-xl border border-blue-200 bg-blue-50/70 p-4 text-xs text-blue-950">
                    <h4 class="mb-2 text-sm font-bold text-blue-900">Tips penempatan QR</h4>
                    <ul class="list-inside list-disc space-y-1 text-blue-800">
                        <li>Tempel di meja kasir, pintu masuk, atau area tunggu.</li>
                        <li>Pelanggan memindai QR langsung dengan kamera ponsel dan masuk menggunakan akun AntreIn mereka.</li>
                        <li>Setelah mengambil nomor, pelanggan memantau giliran secara langsung dari ponsel masing-masing.</li>
                    </ul>
                </div>
            </section>
        </div>
    </div>
</x-layouts.merchant>
