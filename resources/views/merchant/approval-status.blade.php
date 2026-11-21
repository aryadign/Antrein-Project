@php
    $businessInitials = collect(preg_split('/\s+/', trim($business->name)))
        ->filter()
        ->take(2)
        ->map(fn (string $word): string => Str::upper(Str::substr($word, 0, 1)))
        ->implode('') ?: 'UM';
    $openDaysCount = $business->hours->where('is_closed', false)->count();
@endphp

<x-layouts.base title="Status Pendaftaran UMKM — AntreIn">
    <div class="min-h-screen bg-neutral-50">
        {{-- Top Bar --}}
        <header class="border-b border-neutral-200 bg-white">
            <div class="mx-auto flex h-16 max-w-4xl items-center justify-between px-4 sm:px-6">
                <a href="/" class="flex items-center gap-2 focus:outline-none">
                    <span class="text-2xl font-extrabold tracking-tight text-neutral-950">AntreIn<span class="text-brand-700">.</span></span>
                    <span class="rounded border border-brand-200 bg-brand-50 px-2 py-0.5 text-xs font-semibold tracking-wider text-brand-700 uppercase">UMKM</span>
                </a>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="inline-flex h-10 items-center rounded-lg border border-neutral-300 bg-white px-4 text-sm font-semibold text-neutral-700 transition hover:bg-neutral-50">
                        Keluar
                    </button>
                </form>
            </div>
        </header>

        <main class="mx-auto max-w-3xl px-4 py-8 sm:px-6 sm:py-12">
            @if (session('status'))
                <div class="mb-6 flex items-center gap-2.5 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-800 shadow-card" role="status">
                    <svg class="size-5 shrink-0 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>{{ session('status') }}</span>
                </div>
            @endif

            <section class="overflow-hidden rounded-2xl border border-neutral-200 bg-white shadow-sm">
                @if ($business->isRejected())
                    <div class="border-b border-rose-200 bg-rose-50/80 px-6 py-6 sm:px-8">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start">
                            <span class="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-rose-600 text-white shadow-card">
                                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </span>
                            <div class="min-w-0 flex-1">
                                <x-status-badge status="rejected" size="sm" />
                                <h1 class="mt-2 text-[1.375rem] leading-[1.875rem] font-bold sm:text-[1.75rem] sm:leading-9 text-rose-950">Pendaftaran UMKM Ditolak Admin</h1>
                                <p class="mt-1 text-sm text-rose-900">
                                    Maaf, pengajuan pendaftaran usaha Anda belum dapat disetujui. Silakan perbaiki data sesuai catatan di bawah lalu ajukan ulang.
                                </p>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="border-b border-blue-200 bg-linear-to-r from-brand-50 to-blue-50/70 px-6 py-6 sm:px-8">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start">
                            <span class="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-brand-700 text-white shadow-card">
                                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </span>
                            <div class="min-w-0 flex-1">
                                <x-status-badge status="pending" size="sm" />
                                <h1 class="mt-2 text-[1.375rem] leading-[1.875rem] font-bold sm:text-[1.75rem] sm:leading-9 text-neutral-950">Pendaftaran UMKM Sedang Diproses Admin</h1>
                                <p class="mt-1 text-sm leading-relaxed text-neutral-700">
                                    Data usaha Anda telah kami terima dan sedang dalam tahap verifikasi tim administrator AntreIn. Anda akan dihubungi melalui nomor kontak/email terdaftar ketika pengajuan disetujui (Accept) atau ditolak (Reject).
                                </p>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="space-y-6 p-6 sm:p-8">
                    @if ($business->isRejected() && $business->rejection_reason)
                        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-900">
                            <p class="font-bold text-rose-950">Catatan Penolakan Admin:</p>
                            <p class="mt-1 leading-relaxed">{{ $business->rejection_reason }}</p>
                        </div>
                    @endif

                    {{-- Store Summary --}}
                    <div class="flex flex-col gap-4 rounded-xl border border-neutral-200 bg-neutral-50/70 p-4 sm:flex-row sm:items-center">
                        <x-merchant.business-avatar :name="$business->name" :logo-url="$business->getLogoUrl()" :initials="$businessInitials" class="size-16 text-lg" />
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-semibold tracking-wider text-brand-700 uppercase">{{ $business->category?->name ?? 'Usaha UMKM' }}</p>
                            <h2 class="mt-0.5 truncate text-lg font-bold text-neutral-950">{{ $business->name }}</h2>
                            <p class="mt-0.5 text-xs text-neutral-600">{{ $business->address }}</p>
                            <p class="mt-1 text-xs text-neutral-500 tabular-nums">Kontak: {{ $business->phone }}  {{ $business->services->count() }} Layanan  {{ $openDaysCount }} Hari Buka</p>
                        </div>
                    </div>

                    {{-- Process Timeline --}}
                    <div class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-5">
                        <h3 class="text-sm font-bold text-neutral-900">Tahapan Verifikasi UMKM</h3>
                        <ol class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-3">
                            <li class="rounded-lg border border-emerald-200 bg-emerald-50/50 p-3">
                                <span class="text-xs font-bold text-emerald-700">1. Data Terkirim</span>
                                <p class="mt-0.5 text-xs text-neutral-600">Profil usaha, jam buka, dan layanan sudah lengkap.</p>
                            </li>
                            <li class="rounded-lg border p-3 {{ $business->isRejected() ? 'border-rose-200 bg-rose-50/50' : 'border-brand-200 bg-brand-50/60' }}">
                                <span class="text-xs font-bold {{ $business->isRejected() ? 'text-rose-700' : 'text-brand-700' }}">2. Review Admin</span>
                                <p class="mt-0.5 text-xs text-neutral-600">
                                    {{ $business->isRejected() ? 'Admin meminta perbaikan data usaha Anda.' : 'Tim admin memeriksa kelayakan informasi usaha.' }}
                                </p>
                            </li>
                            <li class="rounded-lg border border-neutral-200 bg-neutral-50 p-3">
                                <span class="text-xs font-bold text-neutral-700">3. Usaha Aktif</span>
                                <p class="mt-0.5 text-xs text-neutral-600">Dasbor UMKM terbuka & toko tampil untuk pelanggan.</p>
                            </li>
                        </ol>
                    </div>

                    {{-- Actions --}}
                    <div class="flex flex-col-reverse gap-3 border-t border-neutral-200 pt-5 sm:flex-row sm:items-center sm:justify-between">
                        <a href="{{ route('merchant.onboarding', ['step' => 1]) }}" class="inline-flex h-11 items-center justify-center rounded-lg border border-neutral-300 bg-white px-4 text-sm font-semibold text-neutral-800 transition hover:bg-neutral-50">
                            Perbarui Data Usaha
                        </a>

                        @if ($business->isRejected())
                            <form method="POST" action="{{ route('merchant.approval.resubmit') }}">
                                @csrf
                                <button type="submit" class="inline-flex h-11 w-full items-center justify-center rounded-lg bg-brand-700 px-6 text-sm font-bold text-white shadow-sm transition hover:bg-brand-800 sm:w-auto">
                                    Ajukan Ulang ke Admin
                                </button>
                            </form>
                        @else
                            <a href="{{ route('merchant.approval.status') }}" class="inline-flex h-11 items-center justify-center rounded-lg bg-brand-700 px-6 text-sm font-bold text-white shadow-sm transition hover:bg-brand-800">
                                Cek Status Terbaru
                            </a>
                        @endif
                    </div>
                </div>
            </section>
        </main>
    </div>
</x-layouts.base>
