@php
    $adminName = auth()->user()?->name ?? 'Admin EcoTrack';
    $adminInitials = collect(explode(' ', trim($adminName)))
        ->filter()
        ->take(2)
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('');
@endphp

<aside id="adminSidebar" class="fixed inset-y-0 left-0 z-50 flex w-56 flex-col border-r border-emerald-100 bg-[#f1f7f3]" aria-label="Navigasi Admin">
    <div class="sidebar-header flex h-16 shrink-0 items-center gap-3 border-b border-emerald-100 px-4">
        <a href="{{ url('/admin') }}" class="sidebar-brand flex min-w-0 items-center gap-2.5" aria-label="EcoTrack Dashboard">
            <img src="{{ asset('assets/images/ecotrack-mark.svg') }}" alt="" class="size-8 shrink-0" width="32" height="32">
            <span class="sidebar-brand-name truncate font-display text-lg font-bold tracking-tight text-emerald-900">EcoTrack</span>
        </a>

        <button type="button" data-sidebar-collapse class="sidebar-collapse-button ml-auto hidden size-9 shrink-0 items-center justify-center rounded-lg text-slate-500 transition-colors hover:bg-slate-100 hover:text-slate-900 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-700 lg:inline-flex" aria-label="Ciutkan navigasi" aria-controls="adminSidebar" aria-expanded="true" title="Ciutkan sidebar">
            <x-icon name="panel-left-close" class="size-5" />
        </button>

        <button type="button" data-sidebar-expand class="sidebar-rail-expand hidden size-10 shrink-0 items-center justify-center rounded-lg focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-700" aria-label="Perluas navigasi" aria-controls="adminSidebar" aria-expanded="false" title="Perluas sidebar">
            <img src="{{ asset('assets/images/ecotrack-mark.svg') }}" alt="" class="sidebar-rail-logo size-8" width="32" height="32">
            <span class="sidebar-rail-expand-icon" aria-hidden="true"><x-icon name="panel-left-open" class="size-5" /></span>
        </button>

        <button type="button" data-sidebar-close class="ml-auto rounded-lg p-1.5 text-slate-500 hover:bg-slate-100 lg:hidden" aria-label="Tutup navigasi">
            <x-icon name="x" class="size-5" />
        </button>
    </div>

    <nav class="sidebar-navigation flex-1 space-y-2 overflow-y-auto px-3 py-6" aria-label="Navigasi utama">
        <a href="{{ url('/admin') }}" aria-label="Dashboard" data-tooltip="Dashboard" @if(request()->is('admin') || request()->is('admin/dashboard')) aria-current="page" @endif class="sidebar-link {{ request()->is('admin') || request()->is('admin/dashboard') ? 'sidebar-link-active' : '' }}">
            <x-icon name="grid" class="size-5 shrink-0" />
            <span class="sidebar-label">Dashboard</span>
        </a>
        <a href="{{ route('admin.reports.index') }}" aria-label="Laporan Sampah" data-tooltip="Laporan Sampah" @if(request()->is('admin/reports*')) aria-current="page" @endif class="sidebar-link {{ request()->is('admin/reports*') ? 'sidebar-link-active' : '' }}">
            <x-icon name="file-text" class="size-5 shrink-0" />
            <span class="sidebar-label">Laporan Sampah</span>
        </a>
        <a href="{{ url('/admin/categories') }}" aria-label="Kategori" data-tooltip="Kategori" @if(request()->is('admin/categories*')) aria-current="page" @endif class="sidebar-link {{ request()->is('admin/categories*') ? 'sidebar-link-active' : '' }}">
            <x-icon name="tags" class="size-5 shrink-0" />
            <span class="sidebar-label">Kategori</span>
        </a>
        <a href="{{ route('admin.users.index') }}" aria-label="Manajemen Pengguna" data-tooltip="Manajemen Pengguna" @if(request()->is('admin/users*')) aria-current="page" @endif class="sidebar-link {{ request()->is('admin/users*') ? 'sidebar-link-active' : '' }}">
            <x-icon name="users" class="size-5 shrink-0" />
            <span class="sidebar-label">Manajemen Pengguna</span>
        </a>
        <a href="{{ route('admin.settings.index') }}" aria-label="Pengaturan" data-tooltip="Pengaturan" @if(request()->is('admin/settings*')) aria-current="page" @endif class="sidebar-link {{ request()->is('admin/settings*') ? 'sidebar-link-active' : '' }}">
            <x-icon name="settings" class="size-5 shrink-0" />
            <span class="sidebar-label">Pengaturan</span>
        </a>
    </nav>

    <div class="sidebar-footer flex shrink-0 flex-col border-t border-emerald-100 px-3 py-4">
        <div class="sidebar-clock mb-4 px-2 py-1" data-sidebar-clock data-clock-tooltip="Waktu WITA sedang dimuat" role="timer" aria-live="off" aria-label="Memuat waktu WITA" tabindex="-1">
            <p class="sidebar-clock-date mb-1 text-xs leading-5 text-slate-500" data-clock-date>Memuat tanggal…</p>
            <div class="sidebar-clock-time-row flex items-center gap-2 text-emerald-800">
                <x-icon name="clock" class="sidebar-clock-icon size-4 shrink-0 text-emerald-700" />
                <span class="sidebar-clock-time font-semibold tabular-nums" data-clock-time>--:--:--</span>
                <span class="sidebar-clock-zone text-[10px] font-semibold tracking-wide text-emerald-700">WITA</span>
            </div>
            <span class="sidebar-clock-compact hidden" aria-hidden="true"><x-icon name="clock" class="size-5" /></span>
        </div>

        <div class="sidebar-footer-divider mb-3 border-t border-slate-100" aria-hidden="true"></div>

        <div class="sidebar-account flex min-w-0 items-center gap-3 rounded-xl px-2 py-1.5" title="{{ $adminName }} — Administrator">
            <div class="flex size-9 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-xs font-bold text-emerald-900" aria-hidden="true">
                {{ $adminInitials ?: 'AD' }}
            </div>
            <div class="sidebar-account-copy min-w-0">
                <p class="truncate text-sm font-semibold leading-5 text-slate-800">{{ $adminName }}</p>
                <p class="text-xs leading-4 text-slate-500">Administrator</p>
            </div>
        </div>

        <form class="sidebar-logout-form mt-2" method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="sidebar-logout flex min-h-10 w-full items-center gap-3 rounded-lg px-2.5 text-sm font-medium text-rose-700 transition-colors hover:bg-rose-50 hover:text-rose-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-rose-600" aria-label="Keluar" data-tooltip="Keluar" title="Keluar">
                <x-icon name="log-out" class="size-5 shrink-0" />
                <span class="sidebar-logout-label">Keluar</span>
            </button>
        </form>
    </div>
</aside>
