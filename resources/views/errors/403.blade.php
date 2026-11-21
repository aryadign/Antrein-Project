@php
    $reason = $exception->getMessage();
    $isGenericReason = $reason === '' || in_array($reason, ['This action is unauthorized.', 'Forbidden'], true);
@endphp

@include('errors.partials.page', [
    'code' => 403,
    'title' => 'Akses Ditolak',
    'message' => $isGenericReason ? 'Akun Anda tidak memiliki izin untuk membuka halaman ini. Pastikan Anda masuk dengan akun yang tepat.' : $reason,
    'icon' => 'alert-triangle',
    'tone' => 'danger',
    'actions' => auth()->check()
        ? [['label' => 'Kembali ke Beranda', 'href' => route('home'), 'variant' => 'primary']]
        : [['label' => 'Masuk ke Akun', 'href' => route('login'), 'variant' => 'primary']],
])
