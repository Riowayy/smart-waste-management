@extends('layouts.admin')

@section('title', 'Tambah Kategori')

@section('content')
    <div class="max-w-3xl space-y-5">
        <nav aria-label="Breadcrumb" class="text-sm">
            <ol class="flex items-center gap-2 text-slate-500">
                <li><a href="{{ route('admin.categories.index') }}" class="hover:text-emerald-800 hover:underline">Manajemen Kategori</a></li>
                <li aria-hidden="true">/</li>
                <li aria-current="page" class="font-medium text-slate-700">Tambah</li>
            </ol>
        </nav>

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
