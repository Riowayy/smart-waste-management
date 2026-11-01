@extends('layouts.admin')

@section('title', 'Tambah Pengguna')

@section('content')
    <div class="space-y-6">

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
