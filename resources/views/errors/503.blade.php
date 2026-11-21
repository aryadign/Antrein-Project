@include('errors.partials.page', [
    'code' => 503,
    'title' => 'Sedang dalam Pemeliharaan',
    'message' => 'AntreIn sedang diperbarui dan akan segera kembali. Silakan muat ulang halaman ini beberapa menit lagi.',
    'icon' => 'refresh',
    'tone' => 'brand',
    'actions' => [
        ['label' => 'Muat Ulang Halaman', 'onclick' => 'window.location.reload()', 'variant' => 'primary'],
    ],
    'showBack' => false,
])
