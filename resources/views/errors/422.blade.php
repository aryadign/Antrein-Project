@include('errors.partials.page', [
    'code' => 422,
    'title' => 'Permintaan Tidak Dapat Diproses',
    'message' => $exception->getMessage() ?: 'Data yang dikirim tidak sesuai dengan status antrean atau aturan usaha saat ini. Periksa kembali, lalu coba lagi.',
    'icon' => 'alert-triangle',
    'tone' => 'warning',
    'actions' => [
        ['label' => 'Kembali ke Beranda', 'href' => route('home'), 'variant' => 'primary'],
    ],
])
