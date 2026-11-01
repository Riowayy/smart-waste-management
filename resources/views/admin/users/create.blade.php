@extends('layouts.admin')

@section('title', 'Tambah Pengguna')

@section('content')
    <div class="space-y-6">
        <nav aria-label="Breadcrumb" class="text-sm">
            <ol class="flex items-center gap-2 text-slate-500">
                <li><a href="{{ route('admin.dashboard') }}" class="hover:text-emerald-800 hover:underline">Dashboard</a></li>
                <li aria-hidden="true">/</li>
                <li><a href="{{ route('admin.users.index') }}" class="hover:text-emerald-800 hover:underline">Pengguna</a></li>
                <li aria-hidden="true">/</li>
                <li aria-current="page" class="font-medium text-slate-700">Tambah</li>
            </ol>
        </nav>

        <header>
            <h1 class="font-display text-2xl font-bold text-slate-900">Tambah Pengguna</h1>
            <p class="mt-1 max-w-2xl text-sm leading-6 text-slate-600">Buat akun baru agar pengguna dapat masuk ke EcoTrack.</p>
        </header>

        <form method="POST" action="{{ route('admin.users.store') }}" class="max-w-3xl space-y-4">
            @csrf
            @include('admin.users._form')
        </form>
    </div>
@endsection
