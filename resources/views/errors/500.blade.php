@include('errors.partials.page', [
    'code' => 500,
    'title' => 'Terjadi Kesalahan Server',
    'message' => 'Sistem sedang mengalami kendala saat memproses permintaan Anda. Coba lagi dalam beberapa saat. Jika Anda sedang mengambil antrean, periksa "Antrean Saya" sebelum mencoba ulang.',
    'icon' => 'alert-triangle',
    'tone' => 'danger',
    'actions' => [
        ['label' => 'Coba Lagi', 'onclick' => 'window.location.reload()', 'variant' => 'primary'],
        ['label' => 'Kembali ke Beranda', 'href' => route('home'), 'variant' => 'outline'],
    ],
    'showBack' => false,
])
