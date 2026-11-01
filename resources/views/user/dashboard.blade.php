@extends('layouts.auth')

@section('title', 'Dashboard Pengguna')

@section('content')
    <section class="rounded-2xl border border-slate-100 bg-white p-6 shadow-sm sm:p-8" aria-labelledby="dashboard-title">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-sm font-medium text-emerald-800">Dashboard pengguna</p>
                <h1 id="dashboard-title" class="mt-1 font-display text-2xl font-bold tracking-tight text-slate-900">Halo, {{ auth()->user()->name }}</h1>
                <p class="mt-2 text-sm text-slate-500">Pantau laporan dan status penanganan yang Anda kirim.</p>
            </div>
            <form method="POST" action="{{ route('logout') }}" data-logout-confirm>
                @csrf
                <button type="submit" class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-700">Keluar</button>
            </form>
        </div>

        <div class="mt-8 border-t border-slate-100 pt-6">
            <div class="flex items-center justify-between gap-3">
                <h2 class="font-display text-lg font-semibold text-slate-900">Laporan saya</h2>
                <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-800">{{ $reports->total() }} laporan</span>
            </div>

            @if ($reports->isEmpty())
                <p class="mt-5 rounded-xl border border-dashed border-slate-200 bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">Anda belum memiliki laporan.</p>
            @else
                <ul class="mt-4 divide-y divide-slate-100" aria-label="Daftar laporan saya">
                    @foreach ($reports as $report)
                        <li class="flex flex-wrap items-center justify-between gap-3 py-4">
                            <div>
                                <p class="text-sm font-semibold text-slate-800"><a href="{{ route('user.reports.show', $report) }}" class="hover:text-emerald-800">{{ $report->category?->name ?? 'Kategori belum ditentukan' }}</a></p>
                                <p class="mt-1 line-clamp-2 text-sm text-slate-500">{{ $report->description }}</p>
                                <p class="mt-1 text-xs text-slate-400">{{ $report->created_at->format('d M Y, H:i') }}</p>
                            </div>
                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold capitalize text-slate-700">{{ str_replace('_', ' ', $report->status) }}</span>
                        </li>
                    @endforeach
                </ul>
                <div class="mt-4">{{ $reports->links() }}</div>
            @endif
        </div>
    </section>
@endsection
