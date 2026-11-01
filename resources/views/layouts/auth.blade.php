<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') | EcoTrack</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-surface font-sans text-on-surface">
    <main class="flex min-h-screen items-center justify-center px-4 py-10 sm:px-6">
        <div class="w-full max-w-md">
            <a href="{{ route('login') }}" class="mx-auto mb-7 flex w-fit items-center gap-3" aria-label="EcoTrack beranda">
                <img src="{{ asset('assets/images/ecotrack-mark.svg') }}" alt="" class="h-10 w-10" width="40" height="40">
                <span class="font-display text-2xl font-bold tracking-tight text-emerald-900">EcoTrack</span>
            </a>
            @yield('content')
        </div>
    </main>
</body>
</html>
