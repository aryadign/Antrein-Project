@include('errors.partials.page', [
    'code' => 419,
    'title' => 'Sesi Halaman Berakhir',
    'message' => 'Halaman ini terlalu lama dibiarkan terbuka sehingga sesinya berakhir. Kembali ke halaman sebelumnya, lalu ulangi langkah terakhir Anda.',
    'icon' => 'clock',
    'tone' => 'warning',
    'actions' => [
        ['label' => 'Muat Ulang Halaman', 'onclick' => 'window.location.reload()', 'variant' => 'primary'],
        ['label' => 'Kembali ke Beranda', 'href' => route('home'), 'variant' => 'outline'],
    ],
    'backIsPrimary' => true,
])
