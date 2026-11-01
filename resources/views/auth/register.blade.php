@extends('layouts.auth')

@section('title', 'Daftar')

@section('content')
    <section class="rounded-2xl border border-slate-100 bg-white p-6 shadow-sm sm:p-8" aria-labelledby="register-title">
        <h1 id="register-title" class="font-display text-2xl font-bold tracking-tight text-slate-900">Buat akun EcoTrack</h1>
        <p class="mt-2 text-sm text-slate-500">Daftar untuk mengirim dan memantau laporan sampah.</p>

        <form method="POST" action="{{ route('register.store') }}" class="mt-6 space-y-5">
            @csrf
            <x-auth.field label="Nama lengkap" name="name" autocomplete="name" />
            <x-auth.field label="Email" name="email" type="email" autocomplete="email" inputmode="email" />
            <x-auth.field label="Password" name="password" type="password" autocomplete="new-password" />
            <x-auth.field label="Konfirmasi password" name="password_confirmation" type="password" autocomplete="new-password" />
            <button type="submit" class="w-full rounded-lg bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-700">Buat akun</button>
        </form>

        <p class="mt-6 text-center text-sm text-slate-600">Sudah memiliki akun? <a href="{{ route('login') }}" class="font-semibold text-emerald-800 underline decoration-emerald-300 underline-offset-4 hover:text-emerald-950">Masuk</a></p>
    </section>
@endsection
