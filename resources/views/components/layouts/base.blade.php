<!DOCTYPE html>
<html lang="id" class="h-full bg-neutral-50 antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'AntreIn — Manajemen Antrean Digital UMKM' }}</title>

    <!-- Inter Typography via Bunny Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

    <!-- Vite Assets -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @livewireStyles
    @stack('styles')
</head>
<body class="h-full text-neutral-800 font-sans selection:bg-brand-100 selection:text-brand-700">
    <div id="app" class="min-h-full flex flex-col">
        {{ $slot }}
    </div>

    <!-- Global Toast Container -->
    <x-toast />

    @livewireScripts
    @stack('scripts')
</body>
</html>
