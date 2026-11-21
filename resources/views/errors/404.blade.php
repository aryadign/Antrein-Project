@include('errors.partials.page', [
    'code' => 404,
    'title' => 'Halaman Tidak Ditemukan',
    'message' => 'Alamat yang Anda buka tidak tersedia, mungkin sudah dipindahkan, atau tiket antreannya sudah kedaluwarsa.',
    'icon' => 'search',
    'tone' => 'brand',
    'actions' => [
        ['label' => 'Kembali ke Beranda', 'href' => route('home'), 'variant' => 'primary'],
        ['label' => 'Cari Toko', 'href' => route('customer.dashboard'), 'variant' => 'outline'],
    ],
    'showBack' => false,
])
