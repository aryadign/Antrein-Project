@include('errors.partials.page', [
    'code' => 429,
    'title' => 'Terlalu Banyak Percobaan',
    'message' => 'Anda mengirim terlalu banyak permintaan dalam waktu singkat. Tunggu sekitar satu menit, lalu coba lagi.',
    'icon' => 'clock',
    'tone' => 'warning',
    'actions' => [
        ['label' => 'Coba Lagi', 'onclick' => 'window.location.reload()', 'variant' => 'primary'],
    ],
])
