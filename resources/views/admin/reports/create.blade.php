@extends('layouts.admin')

@section('title', 'Buat Laporan')

@section('content')
    <section>
        <a href="{{ route('admin.reports.index') }}" class="text-sm font-medium text-emerald-800 hover:underline">&larr; Kembali ke laporan</a>
        <h1 class="mt-3 font-display text-2xl font-bold tracking-tight text-slate-900">Buat laporan</h1>
        <p class="mt-1 text-sm text-slate-500">Status awal laporan baru adalah {{ \App\Support\ReportLabels::status('pending') }}.</p>
    </section>
    <section class="dashboard-card p-5 sm:p-7">
        <form method="POST" action="{{ route('admin.reports.store') }}" enctype="multipart/form-data">
            @csrf
            @include('admin.reports._form')
        </form>
    </section>
@endsection
