@php
    $adminName = auth()->user()?->name ?? 'Admin EcoTrack';
    $adminInitials = collect(explode(' ', trim($adminName)))
        ->filter()
        ->take(2)
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('');
@endphp

<header class="admin-navbar fixed inset-x-0 top-0 z-30 flex h-14 items-center justify-between border-b border-slate-100 bg-white/95 px-4 shadow-[0_1px_8px_rgba(15,23,42,0.035)] backdrop-blur lg:left-56 sm:px-6">
    <div class="flex items-center gap-3">
        <button type="button" data-sidebar-toggle class="rounded-lg p-2 text-slate-600 transition hover:bg-slate-100 hover:text-slate-900" aria-label="Buka navigasi" aria-controls="adminSidebar" aria-expanded="false">
            <x-icon name="menu" class="h-5 w-5" />
        </button>
        <span class="hidden text-xs font-medium text-slate-400 sm:inline">Smart Waste Management</span>
    </div>

    <div class="flex items-center gap-3 sm:gap-4">
        <button type="button" class="relative rounded-lg p-2 text-slate-500 transition hover:bg-slate-100 hover:text-slate-800" aria-label="Notifikasi">
            <x-icon name="bell" class="h-5 w-5" />
            <span class="absolute right-2 top-2 h-2 w-2 rounded-full border-2 border-white bg-emerald-500"></span>
        </button>
        <span class="hidden h-7 w-px bg-slate-200 sm:block"></span>
        <div class="flex items-center gap-2.5">
            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-emerald-100 text-xs font-bold text-emerald-800">
                {{ $adminInitials ?: 'AD' }}
            </div>
            <div class="hidden min-w-0 sm:block">
                <p class="max-w-40 truncate text-xs font-semibold leading-4 text-slate-800">{{ $adminName }}</p>
                <p class="text-[11px] leading-4 text-slate-500">Administrator</p>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="rounded-lg px-2 py-1.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-100 hover:text-slate-900">Keluar</button>
            </form>
        </div>
    </div>
</header>
