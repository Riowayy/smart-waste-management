@extends('layouts.auth')

@section('title', 'Masuk')

@section('content')
    <section class="rounded-2xl border border-slate-100 bg-white p-6 shadow-sm sm:p-8" aria-labelledby="login-title">
        <h1 id="login-title" class="font-display text-2xl font-bold tracking-tight text-slate-900">Masuk ke akun Anda</h1>
        <p class="mt-2 text-sm text-slate-500">Kelola laporan sampah Anda bersama EcoTrack.</p>

        @if (session('status'))
            <p class="mt-5 rounded-lg bg-emerald-50 px-3 py-2 text-sm text-emerald-800" role="status">{{ session('status') }}</p>
        @endif

        <form method="POST" action="{{ route('login.store') }}" class="mt-6 space-y-5">
            @csrf
            <x-auth.field label="Email" name="email" type="email" autocomplete="email" inputmode="email" />
            <x-auth.field label="Password" name="password" type="password" autocomplete="current-password" />
            <label class="flex items-center gap-2 text-sm text-slate-600">
                <input type="checkbox" name="remember" value="1" class="rounded border-slate-300 text-emerald-700 focus:ring-emerald-600">
                Ingat saya
            </label>
            <button type="submit" class="w-full rounded-lg bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-700">Masuk</button>
        </form>

        <p class="mt-6 text-center text-sm text-slate-600">Belum memiliki akun? <a href="{{ route('register') }}" class="font-semibold text-emerald-800 underline decoration-emerald-300 underline-offset-4 hover:text-emerald-950">Daftar</a></p>
    </section>
@endsection
