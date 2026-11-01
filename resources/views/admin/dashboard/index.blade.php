@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
    <section class="flex flex-col gap-2">
        <h1 class="font-display text-2xl font-bold tracking-tight text-slate-900 sm:text-[30px]">Selamat datang, {{ auth()->user()->name }}</h1>
        <p class="max-w-2xl text-sm text-slate-500">Ringkasan operasional laporan pengelolaan sampah.</p>
    </section>

    <section class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4 xl:gap-4" aria-label="Statistik laporan">
        <x-admin.stat-card label="Total Laporan" :value="number_format($statistics['total'], 0, ',', '.')" description="Semua laporan tersimpan" icon="clipboard" icon-bg="bg-emerald-50" icon-color="text-emerald-700" />
        <x-admin.stat-card label="Laporan Menunggu" :value="number_format($statistics['pending'], 0, ',', '.')" description="Menunggu verifikasi petugas" icon="clock" icon-bg="bg-amber-50" icon-color="text-amber-600" />
        <x-admin.stat-card label="Dalam Proses" :value="number_format($statistics['inProgress'], 0, ',', '.')" description="Sedang ditindaklanjuti" icon="refresh" icon-bg="bg-blue-50" icon-color="text-blue-700" />
        <x-admin.stat-card label="Laporan Selesai" :value="number_format($statistics['completed'], 0, ',', '.')" description="Telah tertangani" icon="check-circle" icon-bg="bg-emerald-50" icon-color="text-emerald-600" />
    </section>

    <section class="grid grid-cols-1 gap-4 xl:grid-cols-3 xl:gap-5">
        <div class="dashboard-card flex flex-col p-5 sm:p-6 xl:col-span-2">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="font-display text-base font-semibold text-slate-900">Ikhtisar Laporan Sampah</h2>
                    <p class="mt-0.5 text-xs text-slate-500">Laporan masuk dan selesai/ditolak dalam tujuh hari terakhir</p>
                </div>
                <span class="rounded-lg bg-slate-100 px-3 py-2 text-[11px] font-semibold text-slate-600">7 Hari Terakhir</span>
            </div>

            <div class="mt-5 h-60 w-full sm:h-64">
                <svg class="h-full w-full" viewBox="0 0 700 240" role="img" aria-label="Grafik laporan masuk dan tertangani selama tujuh hari terakhir">
                    <defs>
                        <linearGradient id="reportArea" x1="0" x2="0" y1="0" y2="1">
                            <stop offset="0%" stop-color="#10b981" stop-opacity=".22" />
                            <stop offset="100%" stop-color="#10b981" stop-opacity="0" />
                        </linearGradient>
                        <linearGradient id="handledArea" x1="0" x2="0" y1="0" y2="1">
                            <stop offset="0%" stop-color="#005ac2" stop-opacity=".12" />
                            <stop offset="100%" stop-color="#005ac2" stop-opacity="0" />
                        </linearGradient>
                    </defs>
                    <g stroke="#eff4ff" stroke-width="1.5">
                        <line x1="40" y1="30" x2="680" y2="30" />
                        <line x1="40" y1="80" x2="680" y2="80" />
                        <line x1="40" y1="130" x2="680" y2="130" />
                        <line x1="40" y1="180" x2="680" y2="180" />
                    </g>
                    <g fill="#82908a" font-size="11" font-family="Inter, sans-serif" text-anchor="end">
                        @foreach($chartTicks as $index => $tick)
                            <text x="30" y="{{ 34 + ($index * 50) }}">{{ number_format($tick, 0, ',', '.') }}</text>
                        @endforeach
                    </g>
                    <path d="{{ $handledLine }} L 670 190 L 50 190 Z" fill="url(#handledArea)" />
                    <path d="{{ $reportLine }} L 670 190 L 50 190 Z" fill="url(#reportArea)" />
                    <path d="{{ $handledLine }}" fill="none" stroke="#005ac2" stroke-dasharray="4 5" stroke-width="2.5" />
                    <path d="{{ $reportLine }}" fill="none" stroke="#10b981" stroke-width="3" />
                    @foreach($chartDays as $day)
                        <circle cx="{{ $day['x'] }}" cy="{{ $day['reportY'] }}" r="4" fill="#10b981" stroke="#fff" stroke-width="2">
                            <title>{{ $day['date'] }}: {{ $day['reports'] }} laporan masuk</title>
                        </circle>
                        <circle cx="{{ $day['x'] }}" cy="{{ $day['handledY'] }}" r="3" fill="#005ac2" stroke="#fff" stroke-width="1.5">
                            <title>{{ $day['date'] }}: {{ $day['handled'] }} laporan tertangani</title>
                        </circle>
                    @endforeach
                    <g fill="#82908a" font-size="11" font-family="Inter, sans-serif" text-anchor="middle">
                        @foreach($chartDays as $day)
                            <text x="{{ $day['x'] }}" y="215">{{ $day['label'] }}</text>
                        @endforeach
                    </g>
                </svg>
            </div>

            <div class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-3 text-[11px] text-slate-500">
                <div class="flex flex-wrap items-center gap-4">
                    <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-emerald-500"></span>Laporan Masuk</span>
                    <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-blue-700"></span>Laporan Tertangani</span>
                </div>
                <span>Data dari laporan tersimpan</span>
            </div>
        </div>

        <div class="dashboard-card flex flex-col p-5 sm:p-6">
            <div>
                <h2 class="font-display text-base font-semibold text-slate-900">Distribusi Kategori Sampah</h2>
                <p class="mt-0.5 text-xs text-slate-500">Jumlah laporan per kategori</p>
            </div>

            <div class="relative mx-auto my-5 flex h-44 w-44 items-center justify-center">
                <svg class="h-full w-full -rotate-90" viewBox="0 0 120 120" role="img" aria-label="Distribusi kategori berdasarkan jumlah laporan tersimpan">
                    <circle cx="60" cy="60" r="48" fill="none" stroke="#eff4ff" stroke-width="14" />
                    @foreach($categoryDistribution as $category)
                        @if($category['total'] > 0)
                            <circle cx="60" cy="60" r="48" fill="none" stroke="{{ $category['color'] }}" stroke-width="14" stroke-dasharray="{{ $category['dasharray'] }}" stroke-dashoffset="{{ $category['dashoffset'] }}" />
                        @endif
                    @endforeach
                </svg>
                <div class="absolute inset-0 flex flex-col items-center justify-center">
                    <span class="font-display text-2xl font-bold tracking-tight text-slate-900">{{ number_format($categoryTotal, 0, ',', '.') }}</span>
                    <span class="text-[11px] text-slate-500">Laporan berkategori</span>
                </div>
            </div>

            <div class="mt-auto space-y-1">
                @foreach($categoryDistribution as $category)
                    <div class="flex items-center justify-between rounded-lg px-2 py-2 text-xs">
                        <span class="flex items-center gap-2 text-slate-700"><span class="h-2.5 w-2.5 rounded-full" style="background-color: {{ $category['color'] }}"></span>{{ $category['label'] }}</span>
                        <span class="font-medium text-slate-600">{{ number_format($category['total'], 0, ',', '.') }} <span class="text-slate-400">({{ $category['percent'] }}%)</span></span>
                    </div>
                @endforeach
                @if($categoryTotal === 0)
                    <p class="px-2 py-2 text-center text-xs text-slate-500">Belum ada laporan pada kategori ini.</p>
                @endif
            </div>
        </div>
    </section>

    <section class="dashboard-card overflow-hidden">
        <div class="flex flex-col gap-4 border-b border-slate-100 p-5 sm:flex-row sm:items-center sm:justify-between sm:p-6">
            <div>
                <h2 class="font-display text-base font-semibold text-slate-900">Laporan Sampah Terbaru</h2>
                <p class="mt-0.5 text-xs text-slate-500">Enam laporan yang paling baru dibuat</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <label class="relative min-w-[190px] flex-1 sm:flex-none">
                    <span class="sr-only">Cari laporan terbaru</span>
                    <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                    <input type="search" data-report-search class="field-control w-full pl-9 sm:w-48" placeholder="Cari ID atau lokasi...">
                </label>
                <label>
                    <span class="sr-only">Filter kategori laporan terbaru</span>
                    <select data-category-filter class="field-control w-full sm:w-auto">
                        <option value="all">Semua Kategori</option>
                        <option value="Organic Waste">Sampah Organik</option>
                        <option value="Plastic Waste">Sampah Plastik</option>
                        <option value="Paper Waste">Sampah Kertas</option>
                    </select>
                </label>
                <a href="{{ route('admin.reports.index') }}" class="px-2 py-2 text-xs font-semibold text-emerald-800 transition hover:text-emerald-600">Lihat Semua Laporan</a>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="report-table w-full min-w-[900px] border-collapse text-left">
                <thead class="bg-[#eff4ff] text-[10px] font-semibold uppercase tracking-wider text-slate-600">
                    <tr>
                        <th class="px-5 py-3">ID Laporan</th>
                        <th class="px-4 py-3">Pelapor</th>
                        <th class="px-4 py-3">Kategori Sampah</th>
                        <th class="px-4 py-3">Lokasi</th>
                        <th class="px-4 py-3">Tanggal Laporan</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-5 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs text-slate-700">
                    @forelse($recentReports as $report)
                        @php
                            $initials = collect(explode(' ', trim($report->user->name)))
                                ->filter()->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('');
                            $location = $report->latitude !== null && $report->longitude !== null
                                ? $report->latitude.', '.$report->longitude
                                : 'Lokasi belum tersedia';
                        @endphp
                        <tr data-report-row data-category="{{ $report->category?->name }}" data-search="{{ $report->id }} {{ $report->user->name }} {{ $location }} {{ $report->category?->name }}" class="transition-colors hover:bg-slate-50">
                            <td class="px-5 py-4 font-mono text-[11px] font-semibold text-emerald-800">#{{ $report->id }}</td>
                            <td class="px-4 py-4">
                                <div class="flex items-center gap-2.5">
                                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-[10px] font-bold text-emerald-800">{{ $initials }}</span>
                                    <span class="min-w-0">
                                        <span class="block font-semibold leading-4 text-slate-800">{{ $report->user->name }}</span>
                                        <span class="mt-0.5 block text-[10px] text-slate-500">{{ $report->user->email }}</span>
                                    </span>
                                </div>
                            </td>
                            <td class="px-4 py-4"><x-admin.category-badge :category="$report->category?->name ?? 'Belum ditentukan'" /></td>
                            <td class="report-location px-4 py-4"><span class="flex max-w-[220px] items-center gap-1.5 truncate"><x-icon name="map-pin" class="h-4 w-4 shrink-0 text-slate-400" /><span class="truncate">{{ $location }}</span></span></td>
                            <td class="px-4 py-4 whitespace-nowrap text-[11px] text-slate-500">{{ $report->created_at->format('d M Y · H:i') }}</td>
                            <td class="px-4 py-4"><x-admin.status-badge :status="$report->status" /></td>
                            <td class="px-5 py-4 text-right"><a href="{{ route('admin.reports.show', $report) }}" class="inline-flex rounded-md bg-slate-100 px-2.5 py-1.5 text-[11px] font-semibold text-slate-700 transition hover:bg-slate-200">Detail</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-5 py-12 text-center text-sm text-slate-500">Belum ada laporan untuk ditampilkan.</td></tr>
                    @endforelse
                    @if($recentReports->isNotEmpty())
                        <tr data-empty-state hidden><td colspan="7" class="px-5 py-10 text-center text-sm text-slate-500">Tidak ada laporan yang cocok dengan pencarian.</td></tr>
                    @endif
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 px-5 py-3 text-[11px] text-slate-500">Menampilkan <span data-visible-count>{{ $recentReports->count() }}</span> laporan terbaru dari {{ number_format($statistics['total'], 0, ',', '.') }} laporan.</div>
    </section>
@endsection
