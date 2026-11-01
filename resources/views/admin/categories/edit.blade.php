@extends('layouts.admin')

@section('title', 'Edit Kategori')

@section('content')
    <div class="max-w-3xl space-y-5">
        <nav aria-label="Breadcrumb" class="text-sm">
            <ol class="flex items-center gap-2 text-slate-500">
                <li><a href="{{ route('admin.categories.index') }}" class="hover:text-emerald-800 hover:underline">Manajemen Kategori</a></li>
                <li aria-hidden="true">/</li>
                <li aria-current="page" class="font-medium text-slate-700">Edit</li>
            </ol>
        </nav>

        <header>
            <h1 class="font-display text-2xl font-bold text-slate-900">Edit Kategori</h1>
            <p class="mt-1 text-sm leading-6 text-slate-600">Perbarui nama atau deskripsi {{ $category->name }}.</p>
        </header>

        <form method="POST" action="{{ route('admin.categories.update', $category) }}" class="space-y-5">
            @csrf
            @method('PUT')
            @include('admin.categories._form', ['editing' => true])
        </form>
    </div>
@endsection
