@extends('layouts.admin')

@section('title', 'Edit Kategori')

@section('content')
    <div class="max-w-3xl space-y-5">

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
