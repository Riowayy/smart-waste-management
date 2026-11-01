<header class="admin-navbar fixed inset-x-0 top-0 z-30 flex h-14 items-center justify-between border-b border-slate-100 bg-white/95 px-4 shadow-[0_1px_8px_rgba(15,23,42,0.035)] backdrop-blur lg:left-56 sm:px-6">
    <div class="flex items-center gap-3">
        <button type="button" data-sidebar-toggle class="rounded-lg p-2 text-slate-600 transition hover:bg-slate-100 hover:text-slate-900 lg:hidden" aria-label="Buka navigasi" aria-controls="adminSidebar" aria-expanded="false">
            <x-icon name="menu" class="h-5 w-5" />
        </button>
        <span class="hidden text-xs font-medium text-slate-400 sm:inline">Smart Waste Management</span>
    </div>

    <div class="flex items-center gap-3 sm:gap-4">
        <button type="button" class="relative rounded-lg p-2 text-slate-500 transition hover:bg-slate-100 hover:text-slate-800" aria-label="Notifikasi">
            <x-icon name="bell" class="h-5 w-5" />
            <span class="absolute right-2 top-2 h-2 w-2 rounded-full border-2 border-white bg-emerald-500"></span>
        </button>
    </div>
</header>
