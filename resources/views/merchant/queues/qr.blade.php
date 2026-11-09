<x-layouts.merchant 
    title="Tautan & QR Code Antrean" 
    :businessName="$business->name"
    :businessStatus="$business->status"
    :businessSlug="$business->slug"
>
    <div class="max-w-2xl mx-auto space-y-6" x-data="{ copied: false }">
        {{-- Header --}}
        <div>
            <h1 class="text-h2 font-bold text-neutral-950">Tautan & QR Code Antrean</h1>
            <p class="text-body text-neutral-600 mt-1">
                Gunakan tautan atau cetak kode QR ini agar pelanggan dapat mengambil nomor tiket secara online tanpa login saat berada di lokasi usaha Anda.
            </p>
        </div>

        {{-- QR Display Card --}}
        <div class="bg-white rounded-2xl border border-neutral-200 p-8 shadow-sm text-center">
            <span class="text-xs uppercase font-semibold text-neutral-400 tracking-wider block mb-2">
                Scan untuk Ambil Antrean
            </span>
            <h2 class="text-h3 font-bold text-neutral-900 mb-6">{{ $business->name }}</h2>

            {{-- Render SVG QR --}}
            <div class="inline-flex p-4 bg-white rounded-2xl border-2 border-neutral-200 shadow-sm mx-auto mb-6">
                <div class="w-64 h-64 flex items-center justify-center [&>svg]:w-full [&>svg]:h-full">
                    {!! $qrSvg !!}
                </div>
            </div>

            {{-- Public URL Box --}}
            <div class="max-w-md mx-auto mb-6">
                <label class="block text-caption font-semibold text-neutral-600 mb-1.5 text-left">
                    Tautan Publik Pengambilan Antrean:
                </label>
                <div class="flex items-center gap-2">
                    <input 
                        type="text" 
                        readonly 
                        value="{{ $publicUrl }}" 
                        class="flex-1 rounded-lg border-neutral-300 bg-neutral-50 text-xs font-mono text-neutral-800 focus:outline-none focus:ring-1 focus:ring-brand-500 py-2.5 px-3 select-all"
                    >
                    <button 
                        type="button" 
                        @click="navigator.clipboard.writeText('{{ $publicUrl }}'); copied = true; setTimeout(() => copied = false, 2500);"
                        class="px-4 py-2.5 rounded-lg bg-neutral-900 hover:bg-neutral-800 text-white text-xs font-semibold transition shrink-0 shadow-xs"
                    >
                        <span x-show="!copied">Salin Tautan</span>
                        <span x-show="copied" style="display: none;" class="text-emerald-400">Tersalin!</span>
                    </button>
                </div>
            </div>

            {{-- Download & Open Actions --}}
            <div class="flex flex-wrap items-center justify-center gap-3 pt-4 border-t border-neutral-100">
                <a 
                    href="{{ route('merchant.onboarding.download-qr') }}" 
                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg bg-brand-700 text-white text-sm font-bold hover:bg-brand-800 transition shadow-sm"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    <span>Unduh Kode QR (SVG)</span>
                </a>

                <a 
                    href="{{ $publicUrl }}" 
                    target="_blank" 
                    class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg border border-neutral-300 bg-white text-neutral-700 text-sm font-semibold hover:bg-neutral-50 transition shadow-xs"
                >
                    <svg class="w-4 h-4 text-neutral-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    <span>Buka Halaman Pelanggan</span>
                </a>
            </div>
        </div>

        {{-- Guide Tips --}}
        <div class="bg-blue-50/70 rounded-xl border border-blue-200 p-5 text-blue-950 text-xs space-y-2">
            <h4 class="font-bold text-sm text-blue-900">Tips Penggunaan QR AntreIn di Lokasi UMKM:</h4>
            <ul class="list-disc list-inside space-y-1 text-blue-800">
                <li>Cetak kode QR ini dan tempelkan pada meja kasir, pintu masuk, atau area tunggu.</li>
                <li>Pelanggan dapat memindai QR langsung menggunakan kamera ponsel tanpa perlu mendaftar atau login.</li>
                <li>Setelah mengambil nomor, pelanggan dapat memantau estimasi giliran secara real-time dari ponsel masing-masing.</li>
            </ul>
        </div>
    </div>
</x-layouts.merchant>
