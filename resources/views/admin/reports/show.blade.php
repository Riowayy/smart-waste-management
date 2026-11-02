@extends('layouts.admin')

@section('title', 'Detail Laporan')

@section('content')
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <a href="{{ route('admin.reports.index') }}" class="text-sm font-medium text-emerald-800 hover:underline">&larr; Semua laporan</a>
            <h1 class="mt-3 font-display text-2xl font-bold tracking-tight text-slate-900">Laporan #{{ $report->id }}</h1>
            <p class="mt-1 text-sm text-slate-500">Dibuat {{ $report->created_at->locale('id')->translatedFormat('d F Y, H:i') }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.reports.edit', $report) }}" class="rounded-lg border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Ubah laporan</a>
            <form method="POST" action="{{ route('admin.reports.destroy', $report) }}" onsubmit="return confirm('Hapus laporan ini secara permanen?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="rounded-lg bg-red-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-red-800">Hapus</button>
            </form>
        </div>
    </section>

    @if(session('status')) <p class="rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">{{ session('status') }}</p> @endif

    <div class="grid gap-5 xl:grid-cols-3">
        <section class="dashboard-card p-5 sm:p-6 xl:col-span-2" aria-labelledby="report-info-title">
            <h2 id="report-info-title" class="font-display text-lg font-semibold text-slate-900">Informasi laporan</h2>
            <dl class="mt-5 grid gap-5 sm:grid-cols-2">
                <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Pelapor</dt><dd class="mt-1 text-sm font-medium text-slate-800">{{ $report->user->name }} <span class="text-slate-500">({{ $report->user->email }})</span></dd></div>
                <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Kategori</dt><dd class="mt-1 text-sm text-slate-800">{{ \App\Support\ReportLabels::category($report->category) }}</dd></div>
                <div class="sm:col-span-2"><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Deskripsi</dt><dd class="mt-1 whitespace-pre-line text-sm leading-6 text-slate-700">{{ $report->description }}</dd></div>
                <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Koordinat</dt><dd class="mt-1 text-sm text-slate-800">{{ $report->latitude ?? '—' }}, {{ $report->longitude ?? '—' }}</dd></div>
                <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Status</dt><dd class="mt-2"><x-admin.status-badge :status="$report->status" /></dd></div>
            </dl>
            <img src="{{ asset('storage/'.$report->image_path) }}" alt="Foto laporan #{{ $report->id }}" class="mt-6 max-h-[28rem] w-full rounded-xl border border-slate-100 object-contain">
        </section>

        <div class="space-y-5">
            <section class="dashboard-card p-5" aria-labelledby="status-title">
                <h2 id="status-title" class="font-display text-lg font-semibold text-slate-900">Perbarui status</h2>
                <form method="POST" action="{{ route('admin.reports.status', $report) }}" class="mt-4 space-y-4">
                    @csrf
                    @method('PATCH')
                    <div>
                        <label for="status" class="mb-1.5 block text-sm font-medium text-slate-700">Status baru</label>
                        <select id="status" name="status" required class="field-control w-full" @if($errors->has('status')) aria-invalid="true" aria-describedby="status-error" @endif>
                            @foreach($statuses as $value => $label)
                                <option value="{{ $value }}" @selected(old('status', $report->status) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('status') <p id="status-error" class="mt-1.5 text-sm text-red-700" role="alert">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="note" class="mb-1.5 block text-sm font-medium text-slate-700">Catatan <span class="text-slate-400">(opsional)</span></label>
                        <textarea id="note" name="note" rows="3" maxlength="2000" class="field-control h-auto w-full py-2.5" @if($errors->has('note')) aria-invalid="true" aria-describedby="note-error" @endif>{{ old('note') }}</textarea>
                        @error('note') <p id="note-error" class="mt-1.5 text-sm text-red-700" role="alert">{{ $message }}</p> @enderror
                    </div>
                    <button type="submit" class="w-full rounded-lg bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800">Simpan status</button>
                </form>
            </section>

            <section class="dashboard-card p-5" aria-labelledby="history-title">
                <h2 id="history-title" class="font-display text-lg font-semibold text-slate-900">Riwayat status</h2>
                @if($report->statusHistories->isEmpty())
                    <p class="mt-4 text-sm text-slate-500">Belum ada perubahan status.</p>
                @else
                    <ol class="mt-4 space-y-4">
                        @foreach($report->statusHistories->sortByDesc('created_at') as $history)
                            <li class="border-l-2 border-emerald-200 pl-3">
                                <p class="text-sm font-semibold text-slate-800">{{ $statuses[$history->old_status] ?? $history->old_status }} &rarr; {{ $statuses[$history->new_status] ?? $history->new_status }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $history->changedBy?->name ?? 'Pengguna tidak tersedia' }} · {{ $history->created_at->locale('id')->translatedFormat('d F Y, H:i') }}</p>
                                @if($history->note)<p class="mt-1 text-sm text-slate-600">{{ $history->note }}</p>@endif
                            </li>
                        @endforeach
                    </ol>
                @endif
            </section>
        </div>
    </div>
@endsection
