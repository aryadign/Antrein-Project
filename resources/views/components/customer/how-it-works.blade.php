@props([
    'compact' => false,
])

{{-- "3 Langkah Mudah": Masuk & Pilih Toko, Ambil Tiket, Pantau Giliran. --}}
@php
    $steps = [
        ['icon' => 'store', 'title' => 'Pilih Toko', 'text' => 'Scan QR kasir atau pilih toko.'],
        ['icon' => 'ticket', 'title' => 'Ambil Tiket', 'text' => 'Dapatkan nomor & QR tiket digital.'],
        ['icon' => 'bell', 'title' => 'Pantau Giliran', 'text' => 'Datang pas nomor dipanggil.'],
    ];
@endphp

@if ($compact)
    <ol {{ $attributes->class('flex flex-col gap-2.5') }} aria-label="3 langkah mudah">
        @foreach ($steps as $index => $step)
            <li class="flex items-center gap-3">
                <span class="flex size-7 shrink-0 items-center justify-center rounded-lg bg-brand-700 text-xs font-bold text-white shadow-xs tabular-nums">{{ $index + 1 }}</span>
                <span class="flex min-w-0 flex-col">
                    <span class="text-sm font-bold leading-5 text-gray-900">{{ $step['title'] }}</span>
                    <span class="text-xs leading-4 text-gray-600">{{ $step['text'] }}</span>
                </span>
            </li>
        @endforeach
    </ol>
@else
    <ol {{ $attributes->class('relative grid grid-cols-3 gap-2 pt-0.5') }} aria-label="3 langkah mudah">
        <span class="pointer-events-none absolute top-4 right-[18%] left-[18%] h-0.5 bg-brand-200" aria-hidden="true"></span>
        @foreach ($steps as $index => $step)
            <li class="relative z-10 flex flex-col items-center gap-1.5 text-center">
                <span class="flex size-8 items-center justify-center rounded-xl bg-brand-700 text-xs font-extrabold text-white shadow-sm ring-4 ring-brand-50 tabular-nums">{{ $index + 1 }}</span>
                <span class="text-xs font-bold leading-tight text-gray-900">{{ $step['title'] }}</span>
                <span class="text-xs leading-3.5 text-gray-600">{{ $step['text'] }}</span>
            </li>
        @endforeach
    </ol>
@endif
