@extends('layouts.admin')

@section('title', 'Tambah Kategori')

@section('content')
    <div class="max-w-3xl space-y-5">

        <header>
            <h1 class="font-display text-2xl font-bold text-slate-900">Tambah Kategori</h1>
            <p class="mt-1 text-sm leading-6 text-slate-600">Kategori baru dapat dipilih saat Admin mengelola laporan sampah.</p>
        </header>

        <form method="POST" action="{{ route('admin.categories.store') }}" class="space-y-5">
            @csrf
            @include('admin.categories._form', ['editing' => false])
        </form>
    </div>
@endsection
