<aside id="adminSidebar" class="fixed inset-y-0 left-0 z-50 flex w-56 flex-col border-r border-slate-100 bg-white shadow-[0_1px_8px_rgba(15,23,42,0.04)]">
    <div class="flex h-14 shrink-0 items-center border-b border-slate-100 px-4">
        <a href="{{ url('/admin') }}" class="flex items-center gap-2.5" aria-label="EcoTrack Dashboard">
            <img src="{{ asset('assets/images/ecotrack-mark.svg') }}" alt="" class="h-8 w-8" width="32" height="32">
            <span class="font-display text-lg font-bold tracking-tight text-emerald-900">EcoTrack</span>
        </a>
        <button type="button" data-sidebar-close class="ml-auto rounded-lg p-1.5 text-slate-500 hover:bg-slate-100 lg:hidden" aria-label="Tutup navigasi">
            <x-icon name="x" class="h-5 w-5" />
        </button>
    </div>

    <nav class="flex-1 space-y-1 px-3 py-3" aria-label="Navigasi utama">
        <a href="{{ url('/admin') }}" @if(request()->is('admin') || request()->is('admin/dashboard')) aria-current="page" @endif class="sidebar-link {{ request()->is('admin') || request()->is('admin/dashboard') ? 'sidebar-link-active' : '' }}">
            <x-icon name="grid" class="h-[18px] w-[18px] shrink-0" />
            <span>Dashboard</span>
        </a>
        <a href="{{ route('admin.reports.index') }}" @if(request()->is('admin/reports*')) aria-current="page" @endif class="sidebar-link {{ request()->is('admin/reports*') ? 'sidebar-link-active' : '' }}">
            <x-icon name="file-text" class="h-[18px] w-[18px] shrink-0" />
            <span>Laporan Sampah</span>
        </a>
        <a href="{{ url('/admin/categories') }}" @if(request()->is('admin/categories*')) aria-current="page" @endif class="sidebar-link {{ request()->is('admin/categories*') ? 'sidebar-link-active' : '' }}">
            <x-icon name="tags" class="h-[18px] w-[18px] shrink-0" />
            <span>Kategori</span>
        </a>
        <a href="{{ url('/admin/users') }}" @if(request()->is('admin/users*')) aria-current="page" @endif class="sidebar-link {{ request()->is('admin/users*') ? 'sidebar-link-active' : '' }}">
            <x-icon name="users" class="h-[18px] w-[18px] shrink-0" />
            <span>Manajemen Pengguna</span>
        </a>
        <a href="{{ url('/admin/settings') }}" @if(request()->is('admin/settings*')) aria-current="page" @endif class="sidebar-link {{ request()->is('admin/settings*') ? 'sidebar-link-active' : '' }}">
            <x-icon name="settings" class="h-[18px] w-[18px] shrink-0" />
            <span>Pengaturan</span>
        </a>
    </nav>

    <div class="border-t border-slate-100 px-4 py-3">
        <div class="flex items-center gap-2 text-xs text-slate-500">
            <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
            <span>EcoTrack Management System</span>
        </div>
        <p class="mt-1 text-[11px] text-slate-400">Admin workspace</p>
    </div>
</aside>
