<!DOCTYPE html>
<html lang="id" data-sidebar-storage="@yield('sidebar-storage', 'ecotrack.adminSidebar.collapsed')">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') | EcoTrack</title>
    <script>
        try {
            if (window.matchMedia('(min-width: 1024px)').matches && window.sessionStorage.getItem(document.documentElement.dataset.sidebarStorage) === 'true') {
                document.documentElement.classList.add('sidebar-collapsed');
            }
        } catch (error) {
            // Sidebar persistence is optional when browser storage is unavailable.
        }
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-surface font-sans text-on-surface @yield('body-class')">
    @yield('skip-link')
    @hasSection('sidebar') @yield('sidebar') @else <x-admin.sidebar /> @endif
    <div id="sidebarOverlay" class="fixed inset-0 z-40 hidden bg-slate-950/40 lg:hidden" aria-hidden="true"></div>
    @hasSection('navbar') @yield('navbar') @else <x-admin.navbar /> @endif

    <main id="main-content" tabindex="-1" class="admin-main min-h-screen bg-surface pt-14 lg:ml-56">
        <div class="mx-auto w-full max-w-[1600px] space-y-6 p-4 sm:p-6 xl:p-7">
            @yield('content')
        </div>
    </main>
</body>
</html>
