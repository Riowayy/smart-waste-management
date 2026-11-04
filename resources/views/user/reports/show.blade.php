@extends('layouts.user')

@section('title', 'Detail Laporan')

@section('content')
    <nav aria-label="Breadcrumb" class="flex flex-wrap gap-2 text-sm text-slate-600">
        <a href="{{ route('user.reports.index') }}" class="font-medium text-emerald-800 hover:underline">Laporan Saya</a><span aria-hidden="true">/</span><span aria-current="page">Detail Laporan</span>
    </nav>
    <x-user.page-heading :title="'Laporan #'.$report->id" :description="'Dikirim '.$report->created_at->timezone('Asia/Makassar')->locale('id')->translatedFormat('j F Y, H:i').' WITA'">
        <x-admin.status-badge :status="$report->status" />
    </x-user.page-heading>
    @if(session('status'))<p class="rounded-lg bg-emerald-100 px-5 py-4 text-sm text-emerald-950" role="status">{{ session('status') }}</p>@endif
    <div class="grid items-start gap-7 xl:grid-cols-3">
        <article class="user-form space-y-6 xl:col-span-2" aria-labelledby="report-info-title">
            <h2 id="report-info-title" class="text-lg font-semibold text-slate-900">Informasi laporan</h2>
            <dl class="grid gap-6 sm:grid-cols-2">
                <div><dt class="text-sm text-slate-600">Kategori sampah</dt><dd class="mt-2 font-semibold text-slate-900">{{ \App\Support\ReportLabels::category($report->category) }}</dd></div>
                <div><dt class="text-sm text-slate-600">Lokasi</dt><dd class="mt-2 text-sm leading-6 text-slate-700">
                    @if($report->latitude !== null || $report->longitude !== null)
                        Lintang: {{ $report->latitude ?? 'Tidak diisi' }}<br>Bujur: {{ $report->longitude ?? 'Tidak diisi' }}
                    @else Koordinat tidak disertakan. @endif
                    @if(is_numeric($report->latitude) && is_numeric($report->longitude)
                        && is_finite((float) $report->latitude) && is_finite((float) $report->longitude)
                        && $report->latitude >= -90 && $report->latitude <= 90
                        && $report->longitude >= -180 && $report->longitude <= 180)
                        <a href="https://www.google.com/maps?q={{ $report->latitude }},{{ $report->longitude }}" target="_blank" rel="noopener noreferrer" class="mt-2 inline-flex rounded-lg bg-emerald-700 px-3 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800">Buka di GMaps</a>
                    @endif
                </dd></div>
                <div class="sm:col-span-2"><dt class="text-sm text-slate-600">Deskripsi laporan</dt><dd class="mt-2 whitespace-pre-line break-words text-sm leading-7 text-slate-800">{{ $report->description }}</dd></div>
            </dl>
            <figure>
                <img src="{{ asset('storage/'.$report->image_path) }}" alt="Foto kondisi pada laporan #{{ $report->id }}" width="960" height="640" class="max-h-[28rem] w-full rounded-xl bg-slate-50 object-contain">
                <figcaption class="mt-2 text-xs text-slate-600">Foto yang Anda lampirkan saat mengirim laporan.</figcaption>
            </figure>
        </article>
        <section class="user-timeline" aria-labelledby="history-title">
            <h2 id="history-title" class="text-lg font-semibold text-slate-900">Riwayat status</h2>
            <p class="mt-2 text-sm leading-6 text-slate-600">Perubahan status dan catatan penanganan untuk laporan Anda.</p>
            <ol class="mt-6 space-y-0">
                <li class="user-timeline-item">
                    <span class="user-timeline-dot" aria-hidden="true"></span>
                    <h3 class="text-sm font-semibold text-slate-900">Laporan dikirim</h3>
                    <p class="mt-1 text-sm text-slate-600">Menunggu peninjauan Admin.</p>
                    <time datetime="{{ $report->created_at->toIso8601String() }}" class="mt-2 block text-xs leading-5 tabular-nums text-slate-600">{{ $report->created_at->timezone('Asia/Makassar')->locale('id')->translatedFormat('j F Y, H:i') }} WITA</time>
                </li>
                @foreach($report->statusHistories as $history)
                    <li class="user-timeline-item">
                        <span class="user-timeline-dot" aria-hidden="true"></span>
                        <h3 class="text-sm font-semibold text-slate-900">{{ \App\Support\ReportLabels::status($history->new_status) }}</h3>
                        <p class="mt-1 break-words text-xs leading-5 text-slate-600">Dari {{ $history->old_status ? \App\Support\ReportLabels::status($history->old_status) : 'status awal' }} @if($history->changedBy) oleh {{ $history->changedBy->name }} @endif</p>
                        @if($history->note)<p class="mt-3 whitespace-pre-line break-words text-sm leading-6 text-slate-800">{{ $history->note }}</p>@endif
                        <time datetime="{{ $history->created_at->toIso8601String() }}" class="mt-2 block text-xs leading-5 tabular-nums text-slate-600">{{ $history->created_at->timezone('Asia/Makassar')->locale('id')->translatedFormat('j F Y, H:i') }} WITA</time>
                    </li>
                @endforeach
            </ol>
            @if($report->statusHistories->isEmpty())<p class="mt-4 text-sm leading-6 text-slate-600">Belum ada perubahan status dari Admin.</p>@endif
        </section>
    </div>
@endsection
