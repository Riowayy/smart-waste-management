@extends('layouts.admin')

@section('title', 'Detail Pengguna')

@section('content')
    <div class="space-y-6">
        <nav aria-label="Breadcrumb" class="text-sm">
            <ol class="flex items-center gap-2 text-slate-500">
                <li><a href="{{ route('admin.dashboard') }}" class="hover:text-emerald-800 hover:underline">Dashboard</a></li>
                <li aria-hidden="true">/</li>
                <li><a href="{{ route('admin.users.index') }}" class="hover:text-emerald-800 hover:underline">Pengguna</a></li>
                <li aria-hidden="true">/</li>
                <li aria-current="page" class="max-w-40 truncate font-medium text-slate-700">{{ $user->name }}</li>
            </ol>
        </nav>

        @if (session('status'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900" role="status">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800" role="alert">{{ $errors->first() }}</div>
        @endif

        <header class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="font-display text-2xl font-bold text-slate-900">Detail Pengguna</h1>
                <p class="mt-1 text-sm leading-6 text-slate-600">Informasi akun dan aktivitas pengguna EcoTrack.</p>
            </div>
            <a href="{{ route('admin.users.edit', $user) }}" class="inline-flex min-h-10 items-center gap-2 rounded-lg bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-emerald-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-700">
                <x-icon name="pencil" class="size-4" />
                Edit Pengguna
            </a>
        </header>

        <section class="max-w-4xl overflow-hidden rounded-xl border border-slate-200 bg-white" aria-labelledby="user-profile-title">
            <div class="flex flex-wrap items-center gap-4 border-b border-slate-100 px-5 py-5 sm:px-6">
                <span aria-hidden="true" class="inline-flex size-14 shrink-0 items-center justify-center rounded-full bg-emerald-50 text-lg font-semibold text-emerald-800">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span>
                <div class="min-w-0 flex-1">
                    <h2 id="user-profile-title" class="truncate text-lg font-semibold text-slate-900">{{ $user->name }}</h2>
                    <p class="mt-0.5 break-all text-sm text-slate-600">{{ $user->email }}</p>
                </div>
                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $user->isAdmin() ? 'bg-emerald-50 text-emerald-800' : 'bg-slate-100 text-slate-700' }}">{{ $user->isAdmin() ? 'Admin' : 'User' }}</span>
            </div>

            <dl class="grid gap-x-8 gap-y-5 px-5 py-5 sm:grid-cols-2 sm:px-6">
                <div>
                    <dt class="text-sm text-slate-500">Jumlah laporan</dt>
                    <dd class="mt-1 text-base font-semibold tabular-nums text-slate-900">{{ number_format($user->waste_reports_count) }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-slate-500">Perubahan status laporan</dt>
                    <dd class="mt-1 text-base font-semibold tabular-nums text-slate-900">{{ number_format($user->changed_report_statuses_count) }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-slate-500">Tanggal bergabung</dt>
                    <dd class="mt-1 font-medium text-slate-900">{{ $user->created_at?->locale('id')->translatedFormat('j F Y, H:i') ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-slate-500">Kredensial</dt>
                    <dd class="mt-1 font-medium text-slate-900">Informasi password tidak ditampilkan.</dd>
                </div>
            </dl>
        </section>

        @php
            $deleteReason = null;
            if (auth()->user()->is($user)) {
                $deleteReason = 'Akun yang sedang digunakan tidak dapat dihapus.';
            } elseif ($user->waste_reports_count > 0) {
                $deleteReason = 'Akun ini masih memiliki laporan. Laporan dan riwayatnya harus tetap tersimpan.';
            } elseif ($user->isAdmin() && $adminCount <= 1) {
                $deleteReason = 'Akun ini adalah Admin terakhir dan harus dipertahankan.';
            }
        @endphp

        <section class="max-w-4xl rounded-xl border border-rose-200 bg-white p-5 sm:p-6" aria-labelledby="delete-user-title">
            <h2 id="delete-user-title" class="font-semibold text-slate-900">Penghapusan akun</h2>
            <p class="mt-1 max-w-2xl text-sm leading-6 text-slate-600">Akun yang sedang digunakan, Admin terakhir, atau akun yang memiliki laporan tidak dapat dihapus untuk menjaga akses dan riwayat data.</p>
            @if ($deleteReason)
                <p class="mt-4 inline-flex items-center gap-2 text-sm font-medium text-slate-600">
                    <x-icon name="x" class="size-4 text-slate-400" />
                    {{ $deleteReason }}
                </p>
            @else
                <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="mt-4" onsubmit="return confirm('Hapus akun {{ $user->name }}? Tindakan ini tidak dapat dibatalkan.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="inline-flex min-h-10 items-center gap-2 rounded-lg border border-rose-200 px-4 py-2.5 text-sm font-semibold text-rose-700 transition-colors hover:bg-rose-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-rose-600">
                        <x-icon name="trash-2" class="size-4" />
                        Hapus akun
                    </button>
                </form>
            @endif
        </section>

        <a href="{{ route('admin.users.index') }}" class="inline-flex min-h-10 items-center gap-2 rounded-lg px-2 text-sm font-semibold text-slate-600 transition-colors hover:bg-slate-100 hover:text-slate-900 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-700">
            <x-icon name="chevron-left" class="size-4" />
            Kembali ke Manajemen Pengguna
        </a>
    </div>
@endsection
