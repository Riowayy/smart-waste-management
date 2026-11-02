@extends('layouts.auth')

@section('title', 'Masuk')
@section('body-class', 'login-page')
@section('main-class', 'login-main')
@section('container-class', 'login-container')
@section('hide-auth-brand', 'true')

@section('page-background')
    <img
        src="{{ asset('assets/images/background.png') }}"
        alt=""
        aria-hidden="true"
        width="1672"
        height="941"
        fetchpriority="high"
        class="login-background"
    >
@endsection

@section('content')
    <section class="login-panel rounded-3xl p-6 sm:p-8" aria-labelledby="login-title">
        <a href="{{ route('login') }}" class="login-brand flex w-fit items-center gap-3" aria-label="EcoTrack beranda">
            <img src="{{ asset('assets/images/ecotrack-mark.svg') }}" alt="" class="size-10" width="40" height="40">
            <span class="login-brand-name font-display text-2xl font-bold tracking-tight">EcoTrack</span>
        </a>

        <h1 id="login-title" class="mt-6 text-balance font-display text-2xl font-bold tracking-tight text-slate-950">Masuk ke akun Anda</h1>
        <p class="login-description mt-2 text-pretty text-sm leading-6">Kelola laporan sampah Anda bersama EcoTrack.</p>

        @if (session('status'))
            <p class="login-status mt-5 rounded-lg border px-3 py-2 text-sm" role="status">{{ session('status') }}</p>
        @endif

        <form method="POST" action="{{ route('login.store') }}" class="mt-5 space-y-4">
            @csrf
            <x-auth.field label="Email" name="email" type="email" autocomplete="email" inputmode="email" class="login-input" />
            <x-auth.field label="Password" name="password" type="password" autocomplete="current-password" class="login-input" />
            <label class="login-remember flex w-fit cursor-pointer items-center gap-2 text-sm">
                <input type="checkbox" name="remember" value="1" class="field-choice">
                Ingat saya
            </label>
            <button type="submit" class="login-submit w-full rounded-lg px-4 py-3 text-sm font-semibold text-white focus-visible:outline-2 focus-visible:outline-offset-2">Masuk</button>
        </form>
    </section>
@endsection
