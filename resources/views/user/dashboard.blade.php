@extends('layouts.user')

@section('title', 'Dashboard Pengguna')

@section('content')
    <x-user.page-heading :title="'Halo, '.auth()->user()->name.'.'" description="Pantau laporan Anda dan bantu lingkungan sekitar mendapat penanganan yang tepat." />

    <section class="user-overview" aria-label="Ringkasan laporan saya">
        <div class="user-total">
            <div class="flex items-center justify-between gap-4">
                <h2 class="text-sm font-medium text-emerald-50">Total laporan Anda</h2>
                <x-icon name="file-text" class="size-5 shrink-0 text-emerald-200" />
            </div>
            <div>
                <p class="font-display text-5xl font-semibold leading-none tabular-nums">{{ number_format($summary->total) }}</p>
                <p class="mt-3 text-sm leading-6 text-emerald-50">Seluruh laporan yang telah Anda kirim.</p>
            </div>
        </div>
        <div class="user-status-summary">
            <h2 class="text-base font-semibold text-slate-900">Perkembangan penanganan</h2>
            <p class="mt-1 text-sm leading-6 text-slate-600">Status penanganan laporan Anda saat ini.</p>
            <dl class="mt-6 grid grid-cols-3 divide-x divide-slate-200">
                @foreach(['pending' => 'Menunggu', 'in_progress' => 'Dalam Proses', 'completed' => 'Selesai'] as $status => $label)
                    <div class="px-5 first:pl-0 last:pr-0">
                        <dt class="text-sm font-medium text-slate-600">{{ $label }}</dt>
                        <dd class="mt-3 text-4xl font-semibold leading-none tabular-nums {{ $status === 'completed' ? 'text-emerald-800' : 'text-slate-900' }}">{{ number_format($summary->$status ?? 0) }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>
    </section>

    <div class="user-dashboard-content grid items-start gap-7">
        <section class="space-y-4" aria-labelledby="recent-title">
            <div class="flex items-center justify-between gap-3">
                <h2 id="recent-title" class="text-lg font-semibold text-slate-900">Laporan terbaru</h2>
                <a href="{{ route('user.reports.index') }}" class="text-sm font-semibold text-emerald-800 hover:underline">Lihat semua</a>
            </div>
            @if($reports->isEmpty())<x-user.empty-state />@else<x-user.report-table :reports="$reports" />@endif
        </section>
        <aside class="user-guide" aria-labelledby="guide-title">
            <x-icon name="leaf" class="size-7 text-emerald-800" />
            <h2 id="guide-title" class="mt-4 text-balance text-lg font-semibold text-emerald-950">Bantu kami memahami kondisi di lokasi</h2>
            <p class="mt-3 text-pretty text-sm leading-6 text-emerald-900">Foto yang jelas dan deskripsi yang spesifik memudahkan Admin meninjau laporan Anda.</p>
            <ul class="mt-4 space-y-3 text-sm leading-6 text-emerald-900">
                <li>Ambil foto yang memperlihatkan sampah dan area sekitarnya.</li>
                <li>Jelaskan jenis sampah dan kondisi yang Anda temukan.</li>
                <li>Tambahkan koordinat jika tersedia untuk memperjelas lokasi.</li>
            </ul>
        </aside>
    </div>
@endsection
