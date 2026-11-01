@extends('layouts.admin')

@section('title', 'Edit Laporan')

@section('content')
    <section>
        <a href="{{ route('admin.reports.show', $report) }}" class="text-sm font-medium text-emerald-800 hover:underline">&larr; Kembali ke detail</a>
        <h1 class="mt-3 font-display text-2xl font-bold tracking-tight text-slate-900">Edit laporan #{{ $report->id }}</h1>
        <p class="mt-1 text-sm text-slate-500">Perubahan status dilakukan terpisah agar tercatat dalam riwayat.</p>
    </section>
    <section class="dashboard-card p-5 sm:p-7">
        <form method="POST" action="{{ route('admin.reports.update', $report) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            @include('admin.reports._form', ['report' => $report])
        </form>
    </section>
@endsection
