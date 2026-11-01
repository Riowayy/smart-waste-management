@extends('layouts.auth')

@section('title', 'Detail Laporan')

@section('content')
    <section class="rounded-2xl border border-slate-100 bg-white p-6 shadow-sm sm:p-8" aria-labelledby="report-title">
        <a href="{{ route('user.dashboard') }}" class="text-sm font-medium text-emerald-800 hover:underline">&larr; Dashboard saya</a>
        <h1 id="report-title" class="mt-4 font-display text-2xl font-bold tracking-tight text-slate-900">Laporan #{{ $report->id }}</h1>
        <div class="mt-4"><x-admin.status-badge :status="$report->status" /></div>
        <dl class="mt-6 space-y-4 border-t border-slate-100 pt-5">
            <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Kategori</dt><dd class="mt-1 text-sm text-slate-800">{{ $report->category?->name ?? 'Belum ditentukan' }}</dd></div>
            <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Deskripsi</dt><dd class="mt-1 whitespace-pre-line text-sm leading-6 text-slate-700">{{ $report->description }}</dd></div>
            <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Dikirim</dt><dd class="mt-1 text-sm text-slate-800">{{ $report->created_at->format('d M Y, H:i') }}</dd></div>
        </dl>
        <img src="{{ asset('storage/'.$report->image_path) }}" alt="Foto laporan #{{ $report->id }}" class="mt-6 max-h-80 w-full rounded-xl border border-slate-100 object-contain">
        <div class="mt-7 border-t border-slate-100 pt-5">
            <h2 class="font-display text-lg font-semibold text-slate-900">Riwayat status</h2>
            @if($report->statusHistories->isEmpty())
                <p class="mt-3 text-sm text-slate-500">Belum ada pembaruan status.</p>
            @else
                <ol class="mt-4 space-y-4">
                    @foreach($report->statusHistories->sortByDesc('created_at') as $history)
                        <li class="border-l-2 border-emerald-200 pl-3">
                            <p class="text-sm font-semibold text-slate-800">{{ str($history->old_status)->replace('_', ' ')->title() }} &rarr; {{ str($history->new_status)->replace('_', ' ')->title() }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ $history->created_at->format('d M Y, H:i') }}</p>
                            @if($history->note)<p class="mt-1 text-sm text-slate-600">{{ $history->note }}</p>@endif
                        </li>
                    @endforeach
                </ol>
            @endif
        </div>
    </section>
@endsection
