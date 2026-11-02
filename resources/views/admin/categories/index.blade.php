@extends('layouts.admin')

@section('title', 'Manajemen Kategori')

@section('content')
    <div class="space-y-6">

        @if (session('status'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900" role="status">{{ session('status') }}</div>
        @endif
        @if (session('error'))
            <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800" role="alert">{{ session('error') }}</div>
        @endif

        <header class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="font-display text-2xl font-bold text-slate-900">Manajemen Kategori</h1>
                <p class="mt-1 max-w-2xl text-sm leading-6 text-slate-600">Kelola kategori sampah yang digunakan dalam pencatatan dan pengelolaan laporan EcoTrack.</p>
            </div>
            <a href="{{ route('admin.categories.create') }}" class="inline-flex min-h-10 items-center gap-2 rounded-lg bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-emerald-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-700">
                <x-icon name="plus" class="size-4" />
                Tambah Kategori
            </a>
        </header>

        <section aria-label="Ringkasan kategori" class="grid overflow-hidden rounded-xl border border-slate-200 bg-white sm:grid-cols-3 sm:divide-x sm:divide-slate-200">
            <div class="px-5 py-4">
                <p class="text-xs font-medium text-slate-500">Total kategori</p>
                <p class="mt-1 text-xl font-semibold tabular-nums text-slate-900">{{ number_format($summary['categories']) }}</p>
            </div>
            <div class="border-t border-slate-200 px-5 py-4 sm:border-t-0">
                <p class="text-xs font-medium text-slate-500">Kategori dengan laporan</p>
                <p class="mt-1 text-xl font-semibold tabular-nums text-slate-900">{{ number_format($summary['usedCategories']) }}</p>
            </div>
            <div class="border-t border-slate-200 px-5 py-4 sm:border-t-0">
                <p class="text-xs font-medium text-slate-500">Laporan terhubung</p>
                <p class="mt-1 text-xl font-semibold tabular-nums text-slate-900">{{ number_format($summary['linkedReports']) }}</p>
            </div>
        </section>

        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white" aria-labelledby="category-list-title">
            <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-100 px-5 py-4">
                <div>
                    <h2 id="category-list-title" class="text-base font-semibold text-slate-900">Daftar kategori</h2>
                    <p class="mt-0.5 text-sm text-slate-500">Kategori laporan dan penggunaan pada data saat ini.</p>
                </div>
                <form method="GET" action="{{ route('admin.categories.index') }}" role="search" class="flex w-full max-w-md items-center gap-2">
                    <label for="category-search" class="sr-only">Cari kategori</label>
                    <div class="relative min-w-0 flex-1">
                        <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
                        <input id="category-search" name="search" type="search" value="{{ request('search') }}" placeholder="Cari nama atau deskripsi" class="field-control w-full pl-9" autocomplete="off">
                    </div>
                    <button type="submit" class="min-h-10 rounded-lg border border-slate-200 px-3 text-sm font-semibold text-slate-700 transition-colors hover:bg-slate-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-700">Cari</button>
                    @if (request()->filled('search'))
                        <a href="{{ route('admin.categories.index') }}" class="shrink-0 text-sm font-medium text-emerald-800 hover:underline">Reset</a>
                    @endif
                </form>
            </div>

            @if ($categories->isEmpty())
                <div class="px-6 py-14 text-center">
                    <x-icon name="tag" class="mx-auto size-8 text-slate-400" />
                    <h3 class="mt-3 text-base font-semibold text-slate-900">{{ request()->filled('search') ? 'Kategori tidak ditemukan' : 'Belum ada kategori' }}</h3>
                    <p class="mx-auto mt-1 max-w-md text-sm leading-6 text-slate-500">{{ request()->filled('search') ? 'Coba kata kunci lain atau reset pencarian untuk melihat seluruh kategori.' : 'Tambahkan kategori agar laporan dapat dicatat dengan klasifikasi yang jelas.' }}</p>
                    @if (request()->filled('search'))
                        <a href="{{ route('admin.categories.index') }}" class="mt-4 inline-flex text-sm font-semibold text-emerald-800 hover:underline">Reset pencarian</a>
                    @else
                        <a href="{{ route('admin.categories.create') }}" class="mt-4 inline-flex text-sm font-semibold text-emerald-800 hover:underline">Tambah kategori pertama</a>
                    @endif
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[860px] text-left text-sm">
                        <caption class="sr-only">Daftar kategori sampah dan jumlah laporan yang terkait</caption>
                        <thead class="border-b border-slate-100 text-xs font-semibold text-slate-500">
                            <tr>
                                <th scope="col" class="px-5 py-3">Kategori</th>
                                <th scope="col" class="px-4 py-3">Deskripsi</th>
                                <th scope="col" class="px-4 py-3">Laporan</th>
                                <th scope="col" class="px-4 py-3">Dibuat</th>
                                <th scope="col" class="px-5 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            @foreach ($categories as $category)
                                @php
                                    $categoryLabel = \App\Support\ReportLabels::category($category);
                                    $categoryIcon = match ($category->slug) {
                                        'organic-waste' => 'leaf',
                                        'plastic-waste' => 'recycle',
                                        'paper-waste' => 'file-text',
                                        default => 'tag',
                                    };
                                @endphp
                                <tr class="transition-colors hover:bg-slate-50/70">
                                    <th scope="row" class="px-5 py-4 font-medium">
                                        <div class="flex items-start gap-3">
                                            <x-icon :name="$categoryIcon" class="mt-0.5 size-5 shrink-0 text-emerald-700" />
                                            <div class="min-w-0">
                                                <p class="font-semibold text-slate-900">{{ $categoryLabel }}</p>
                                            </div>
                                        </div>
                                    </th>
                                    <td class="max-w-xs px-4 py-4 leading-5 text-slate-600">{{ $category->description ?: '—' }}</td>
                                    <td class="px-4 py-4">
                                        <div class="space-y-1 text-xs text-slate-600">
                                            <p><span class="font-semibold tabular-nums text-slate-800">{{ number_format($category->reports_count) }}</span> terkonfirmasi</p>
                                            <p><span class="font-semibold tabular-nums text-slate-800">{{ number_format($category->ai_predicted_reports_count) }}</span> prediksi AI</p>
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-4 text-slate-600">{{ $category->created_at?->locale('id')->translatedFormat('j F Y') ?? '—' }}</td>
                                    <td class="px-5 py-4">
                                        <div class="flex items-center justify-end gap-2">
                                            <a href="{{ route('admin.categories.edit', $category) }}" aria-label="Edit {{ $categoryLabel }}" class="inline-flex size-9 items-center justify-center rounded-lg text-slate-600 transition-colors hover:bg-emerald-50 hover:text-emerald-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-700" title="Edit kategori">
                                                <x-icon name="pencil" class="size-4" />
                                            </a>
                                            <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" onsubmit="return confirm(this.dataset.confirm)" data-confirm="Hapus kategori {{ $categoryLabel }}? Kategori yang masih digunakan tidak dapat dihapus.">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" aria-label="Hapus {{ $categoryLabel }}" class="inline-flex size-9 items-center justify-center rounded-lg text-rose-700 transition-colors hover:bg-rose-50 hover:text-rose-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-rose-600" title="Hapus kategori">
                                                    <x-icon name="trash-2" class="size-4" />
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($categories->hasPages())
                    <div class="border-t border-slate-100 px-5 py-4">{{ $categories->links() }}</div>
                @endif
            @endif
        </section>
    </div>
@endsection
