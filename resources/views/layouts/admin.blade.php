<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') | EcoTrack</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-surface font-sans text-on-surface">
    <x-admin.sidebar />
    <div id="sidebarOverlay" class="fixed inset-0 z-40 hidden bg-slate-950/40 lg:hidden" aria-hidden="true"></div>
    <x-admin.navbar />

    <main class="admin-main min-h-screen bg-surface pt-14 lg:ml-56">
        <div class="mx-auto w-full max-w-[1600px] space-y-6 p-4 sm:p-6 xl:p-7">
            @yield('content')
        </div>
    </main>
</body>
</html>
