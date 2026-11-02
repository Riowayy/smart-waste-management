@extends('layouts.admin')

@section('body-class', 'user-portal')
@section('sidebar-storage', 'ecotrack.userSidebar.collapsed')
@section('skip-link')
    <a href="#main-content" class="user-skip-link">Lewati navigasi</a>
@endsection
@section('sidebar')
    <x-admin.sidebar portal="user" />
@endsection
@section('navbar')
    <header class="admin-navbar fixed inset-x-0 top-0 z-30 flex h-14 items-center justify-between border-b border-slate-200 bg-white px-6 lg:left-56">
        <div class="flex items-center gap-3">
            <button type="button" data-sidebar-toggle class="rounded-lg p-2 lg:hidden" aria-label="Buka navigasi" aria-controls="adminSidebar" aria-expanded="false"><x-icon name="menu" /></button>
            <a href="{{ route('user.dashboard') }}" class="text-sm font-semibold text-emerald-900">Portal Pelaporan EcoTrack</a>
        </div>
        <span class="hidden text-sm text-slate-600 sm:block">Lapor dengan jelas, pantau perkembangannya.</span>
    </header>
@endsection
