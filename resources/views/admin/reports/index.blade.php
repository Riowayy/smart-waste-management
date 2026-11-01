@extends('layouts.admin')

@section('title', 'Manajemen Laporan')

@section('content')
    <section class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm font-medium text-emerald-800">Operasional</p>
            <h1 class="font-display text-2xl font-bold tracking-tight text-slate-900">Laporan Sampah</h1>
            <p class="mt-1 text-sm text-slate-500">Cari, tinjau, dan kelola laporan yang masuk.</p>
        </div>
        <a href="{{ route('admin.reports.create') }}" class="inline-flex w-fit items-center rounded-lg bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800">Buat laporan</a>
    </section>

    @if(session('status')) <p class="rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">{{ session('status') }}</p> @endif

    <section class="dashboard-card overflow-hidden">
        <form method="GET" action="{{ route('admin.reports.index') }}" class="grid gap-3 border-b border-slate-100 p-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="xl:col-span-2">
                <label for="search" class="sr-only">Cari laporan</label>
                <input id="search" name="search" type="search" value="{{ request('search') }}" placeholder="Cari ID, deskripsi, atau nama pelapor" class="field-control w-full">
            </div>
            <div>
                <label for="category" class="sr-only">Filter kategori</label>
                <select id="category" name="category" class="field-control w-full">
                    <option value="">Semua kategori</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) request('category') === (string) $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2">
                <label for="status" class="sr-only">Filter status</label>
                <select id="status" name="status" class="field-control min-w-0 flex-1">
                    <option value="">Semua status</option>
                    @foreach($statuses as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <button type="submit" class="rounded-lg bg-slate-900 px-4 text-sm font-semibold text-white hover:bg-slate-700">Cari</button>
            </div>
        </form>

        <div class="overflow-x-auto">
            <table class="report-table w-full min-w-[850px] text-left">
                <thead class="bg-slate-50 text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                    <tr><th class="px-5 py-3">ID</th><th class="px-4 py-3">Pelapor</th><th class="px-4 py-3">Kategori</th><th class="px-4 py-3">Deskripsi</th><th class="px-4 py-3">Status</th><th class="px-5 py-3 text-right">Aksi</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm text-slate-700">
                    @forelse($reports as $report)
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-4 font-mono text-xs font-semibold text-emerald-800">#{{ $report->id }}</td>
                            <td class="px-4 py-4"><span class="block font-medium">{{ $report->user->name }}</span><span class="text-xs text-slate-500">{{ $report->user->email }}</span></td>
                            <td class="px-4 py-4">{{ $report->category?->name ?? '—' }}</td>
                            <td class="max-w-xs truncate px-4 py-4">{{ $report->description }}</td>
                            <td class="px-4 py-4"><x-admin.status-badge :status="$report->status" /></td>
                            <td class="px-5 py-4 text-right"><a href="{{ route('admin.reports.show', $report) }}" class="font-semibold text-emerald-800 hover:underline">Detail</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-12 text-center text-sm text-slate-500">Tidak ada laporan yang cocok dengan filter.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 px-5 py-4">{{ $reports->links() }}</div>
    </section>
@endsection
