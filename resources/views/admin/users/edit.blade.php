@extends('layouts.admin')

@section('title', 'Edit Pengguna')

@section('content')
    <div class="space-y-6">
        @include('admin.users._breadcrumb', ['currentPage' => 'Edit Pengguna'])

        <header>
            <h1 class="font-display text-2xl font-bold text-slate-900">Edit Pengguna</h1>
            <p class="mt-1 max-w-2xl text-sm leading-6 text-slate-600">Perbarui informasi akun dan akses untuk {{ $user->name }}.</p>
        </header>

        <form novalidate method="POST" action="{{ route('admin.users.update', $user) }}" class="max-w-3xl space-y-4">
            @csrf
            @method('PUT')
            @include('admin.users._form')
        </form>
    </div>
@endsection
