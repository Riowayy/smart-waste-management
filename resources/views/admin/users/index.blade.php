@extends('layouts.admin')

@section('title', 'Manajemen Pengguna')

@section('content')
    <div class="space-y-6">
        <nav aria-label="Breadcrumb" class="text-sm">
            <ol class="flex items-center gap-2 text-slate-500">
                <li><a href="{{ route('admin.dashboard') }}" class="hover:text-emerald-800 hover:underline">Dashboard</a></li>
                <li aria-hidden="true">/</li>
                <li aria-current="page" class="font-medium text-slate-700">Pengguna</li>
            </ol>
        </nav>

        @if (session('status'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900" role="status">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800" role="alert">
                <p class="font-semibold">Tindakan belum dapat dilakukan.</p>
                <p class="mt-0.5">{{ $errors->first() }}</p>
            </div>
        @endif

        <header class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="font-display text-2xl font-bold text-slate-900">Manajemen Pengguna</h1>
                <p class="mt-1 max-w-2xl text-sm leading-6 text-slate-600">Kelola akun Admin dan User yang memiliki akses ke platform EcoTrack.</p>
            </div>
            <a href="{{ route('admin.users.create') }}" class="inline-flex min-h-10 items-center gap-2 rounded-lg bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-emerald-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-700">
                <x-icon name="plus" class="size-4" />
                Tambah Pengguna
            </a>
        </header>

        <section aria-label="Ringkasan pengguna" class="grid overflow-hidden rounded-xl border border-slate-200 bg-white sm:grid-cols-3 sm:divide-x sm:divide-slate-200">
            <div class="px-5 py-4">
                <p class="text-xs font-medium text-slate-500">Total Pengguna</p>
                <p class="mt-1 text-xl font-semibold tabular-nums text-slate-900">{{ number_format($summary->total ?? 0) }}</p>
            </div>
            <div class="border-t border-slate-200 px-5 py-4 sm:border-t-0">
                <p class="text-xs font-medium text-slate-500">Admin</p>
                <p class="mt-1 text-xl font-semibold tabular-nums text-slate-900">{{ number_format($summary->admins ?? 0) }}</p>
            </div>
            <div class="border-t border-slate-200 px-5 py-4 sm:border-t-0">
                <p class="text-xs font-medium text-slate-500">User</p>
                <p class="mt-1 text-xl font-semibold tabular-nums text-slate-900">{{ number_format($summary->users ?? 0) }}</p>
            </div>
        </section>

        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white" aria-labelledby="user-list-title">
            <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-100 px-5 py-4">
                <div>
                    <h2 id="user-list-title" class="text-base font-semibold text-slate-900">Daftar Pengguna</h2>
                    <p class="mt-0.5 text-sm text-slate-500">Akun dan akses yang terdaftar di EcoTrack.</p>
                </div>
                <form method="GET" action="{{ route('admin.users.index') }}" role="search" class="flex w-full flex-wrap items-center gap-2 lg:w-auto lg:flex-nowrap">
                    <label for="user-search" class="sr-only">Cari nama atau email pengguna</label>
                    <div class="relative min-w-[14rem] flex-1 lg:w-64 lg:flex-none">
                        <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
                        <input id="user-search" name="search" type="search" value="{{ request('search') }}" placeholder="Cari nama atau email" class="field-control w-full pl-9" autocomplete="off">
                    </div>
                    <label for="user-role" class="sr-only">Filter berdasarkan role</label>
                    <select id="user-role" name="role" class="field-control min-w-36">
                        <option value="">Semua role</option>
                        <option value="admin" @selected(request('role') === 'admin')>Admin</option>
                        <option value="user" @selected(request('role') === 'user')>User</option>
                    </select>
                    <button type="submit" class="min-h-10 rounded-lg border border-slate-200 px-3 text-sm font-semibold text-slate-700 transition-colors hover:bg-slate-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-700">Terapkan</button>
                    @if (request()->filled('search') || request()->filled('role'))
                        <a href="{{ route('admin.users.index') }}" class="shrink-0 text-sm font-semibold text-emerald-800 hover:underline">Reset</a>
                    @endif
                </form>
            </div>

            @if ($users->isEmpty())
                <div class="px-6 py-14 text-center">
                    <x-icon name="users" class="mx-auto size-8 text-slate-400" />
                    <h3 class="mt-3 text-base font-semibold text-slate-900">{{ request()->filled('search') || request()->filled('role') ? 'Pengguna tidak ditemukan' : 'Belum ada pengguna' }}</h3>
                    <p class="mx-auto mt-1 max-w-md text-sm leading-6 text-slate-500">{{ request()->filled('search') || request()->filled('role') ? 'Ubah kata kunci atau filter untuk melihat akun lainnya.' : 'Buat akun untuk memberi akses ke platform EcoTrack.' }}</p>
                    @if (request()->filled('search') || request()->filled('role'))
                        <a href="{{ route('admin.users.index') }}" class="mt-4 inline-flex text-sm font-semibold text-emerald-800 hover:underline">Reset pencarian</a>
                    @else
                        <a href="{{ route('admin.users.create') }}" class="mt-4 inline-flex text-sm font-semibold text-emerald-800 hover:underline">Tambah pengguna pertama</a>
                    @endif
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[900px] text-left text-sm">
                        <caption class="sr-only">Daftar akun, role, jumlah laporan, dan tanggal bergabung pengguna EcoTrack</caption>
                        <thead class="border-b border-slate-100 text-xs font-semibold text-slate-500">
                            <tr>
                                <th scope="col" class="px-5 py-3">Pengguna</th>
                                <th scope="col" class="px-4 py-3">Role</th>
                                <th scope="col" class="px-4 py-3">Jumlah laporan</th>
                                <th scope="col" class="px-4 py-3">Bergabung</th>
                                <th scope="col" class="px-5 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            @foreach ($users as $user)
                                @php
                                    $deleteReason = null;
                                    if (auth()->user()->is($user)) {
                                        $deleteReason = 'Akun yang sedang digunakan tidak dapat dihapus.';
                                    } elseif ($user->waste_reports_count > 0) {
                                        $deleteReason = 'Akun yang memiliki laporan tidak dapat dihapus.';
                                    } elseif ($user->isAdmin() && (int) ($summary->admins ?? 0) <= 1) {
                                        $deleteReason = 'Admin terakhir tidak dapat dihapus.';
                                    }
                                @endphp
                                <tr class="transition-colors hover:bg-slate-50/70">
                                    <th scope="row" class="px-5 py-4 font-medium">
                                        <div class="flex min-w-0 items-center gap-3">
                                            <span aria-hidden="true" class="inline-flex size-10 shrink-0 items-center justify-center rounded-full bg-emerald-50 text-sm font-semibold text-emerald-800">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span>
                                            <span class="min-w-0">
                                                <span class="block truncate font-semibold text-slate-900">{{ $user->name }}</span>
                                                <span class="mt-0.5 block truncate font-normal text-slate-500">{{ $user->email }}</span>
                                            </span>
                                        </div>
                                    </th>
                                    <td class="whitespace-nowrap px-4 py-4">
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $user->isAdmin() ? 'bg-emerald-50 text-emerald-800' : 'bg-slate-100 text-slate-700' }}">{{ $user->isAdmin() ? 'Admin' : 'User' }}</span>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-4 tabular-nums text-slate-700">{{ number_format($user->waste_reports_count) }}</td>
                                    <td class="whitespace-nowrap px-4 py-4 text-slate-600">{{ $user->created_at?->locale('id')->translatedFormat('j M Y') ?? '—' }}</td>
                                    <td class="px-5 py-4">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <a href="{{ route('admin.users.show', $user) }}" class="inline-flex min-h-9 items-center rounded-lg px-2.5 text-sm font-semibold text-emerald-800 transition-colors hover:bg-emerald-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-700">Detail</a>
                                            <a href="{{ route('admin.users.edit', $user) }}" aria-label="Edit {{ $user->name }}" title="Edit pengguna" class="inline-flex size-9 items-center justify-center rounded-lg text-slate-600 transition-colors hover:bg-slate-100 hover:text-slate-900 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-700">
                                                <x-icon name="pencil" class="size-4" />
                                            </a>
                                            @if ($deleteReason)
                                                <button type="button" aria-disabled="true" aria-label="Penghapusan akun tidak tersedia" aria-describedby="delete-reason-{{ $user->id }}" title="{{ $deleteReason }}" class="inline-flex size-9 cursor-not-allowed items-center justify-center rounded-lg text-slate-300 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-slate-400">
                                                    <x-icon name="trash-2" class="size-4" />
                                                </button>
                                                <span id="delete-reason-{{ $user->id }}" class="sr-only">{{ $deleteReason }}</span>
                                            @else
                                                <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Hapus akun {{ $user->name }}? Tindakan ini tidak dapat dibatalkan.');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" aria-label="Hapus {{ $user->name }}" title="Hapus pengguna" class="inline-flex size-9 items-center justify-center rounded-lg text-rose-700 transition-colors hover:bg-rose-50 hover:text-rose-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-rose-600">
                                                        <x-icon name="trash-2" class="size-4" />
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($users->hasPages())
                    <div class="border-t border-slate-100 px-5 py-4">{{ $users->links() }}</div>
                @endif
            @endif
        </section>
    </div>
@endsection
