@extends('layouts.admin')

@section('title', 'Pengaturan')

@section('content')
    <div class="space-y-6">
        <nav aria-label="Breadcrumb" class="text-sm">
            <ol class="flex items-center gap-2 text-slate-500">
                <li><a href="{{ route('admin.dashboard') }}" class="hover:text-emerald-800 hover:underline">Dashboard</a></li>
                <li aria-hidden="true">/</li>
                <li aria-current="page" class="font-medium text-slate-700">Pengaturan</li>
            </ol>
        </nav>

        <header>
            <h1 class="font-display text-2xl font-bold text-slate-900">Pengaturan</h1>
            <p class="mt-1 max-w-2xl text-sm leading-6 text-slate-600">Akses pengaturan akun dan modul utama untuk menjaga operasional EcoTrack tetap tertata.</p>
        </header>

        <div class="grid gap-5 lg:grid-cols-2">
            <section class="overflow-hidden rounded-xl border border-slate-200 bg-white" aria-labelledby="admin-account-title">
                <div class="flex items-start gap-3 border-b border-slate-100 bg-emerald-50/70 px-5 py-4">
                    <span class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-white text-emerald-800 ring-1 ring-emerald-100">
                        <x-icon name="users" class="size-5" />
                    </span>
                    <div>
                        <h2 id="admin-account-title" class="text-base font-semibold text-slate-900">Akun administrator</h2>
                        <p class="mt-0.5 text-sm leading-5 text-slate-600">Informasi akun yang sedang digunakan.</p>
                    </div>
                </div>

                <dl class="grid gap-4 px-5 py-5 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs font-medium text-slate-500">Nama</dt>
                        <dd class="mt-1 break-words text-sm font-semibold text-slate-800">{{ auth()->user()->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-slate-500">Email</dt>
                        <dd class="mt-1 break-all text-sm font-medium text-slate-800">{{ auth()->user()->email }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-xs font-medium text-slate-500">Role</dt>
                        <dd class="mt-1.5"><span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-900">Administrator</span></dd>
                    </div>
                </dl>

                <div class="border-t border-slate-100 px-5 py-3">
                    <a href="{{ route('admin.users.show', auth()->user()) }}" class="inline-flex min-h-10 items-center gap-2 rounded-lg text-sm font-semibold text-emerald-800 hover:text-emerald-950 hover:underline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-emerald-700">
                        Lihat detail akun
                        <x-icon name="chevron-right" class="size-4" />
                    </a>
                </div>
            </section>

            <section class="overflow-hidden rounded-xl border border-slate-200 bg-white" aria-labelledby="report-flow-title">
                <div class="border-b border-slate-100 px-5 py-4">
                    <h2 id="report-flow-title" class="text-base font-semibold text-slate-900">Alur pengelolaan laporan</h2>
                    <p class="mt-0.5 text-sm leading-5 text-slate-600">Status mengikuti proses peninjauan dan tindak lanjut laporan.</p>
                </div>

                <ol class="space-y-4 px-5 py-5">
                    <li class="flex gap-3">
                        <span class="flex size-7 shrink-0 items-center justify-center rounded-full bg-amber-100 text-xs font-bold text-amber-900">1</span>
                        <div class="min-w-0">
                            <span class="inline-flex rounded-full bg-amber-50 px-2 py-0.5 text-xs font-semibold text-amber-800">Menunggu</span>
                            <p class="mt-1 text-sm leading-5 text-slate-600">Tinjau laporan baru dan informasi yang dikirim pelapor.</p>
                        </div>
                    </li>
                    <li class="flex gap-3">
                        <span class="flex size-7 shrink-0 items-center justify-center rounded-full bg-sky-100 text-xs font-bold text-sky-900">2</span>
                        <div class="min-w-0">
                            <span class="inline-flex rounded-full bg-sky-50 px-2 py-0.5 text-xs font-semibold text-sky-800">Dalam Proses</span>
                            <p class="mt-1 text-sm leading-5 text-slate-600">Catat laporan yang sedang ditindaklanjuti oleh petugas.</p>
                        </div>
                    </li>
                    <li class="flex gap-3">
                        <span class="flex size-7 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-xs font-bold text-emerald-900">3</span>
                        <div class="min-w-0">
                            <div class="flex flex-wrap gap-2">
                                <span class="inline-flex rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-800">Selesai</span>
                                <span class="inline-flex rounded-full bg-rose-50 px-2 py-0.5 text-xs font-semibold text-rose-800">Ditolak</span>
                            </div>
                            <p class="mt-1 text-sm leading-5 text-slate-600">Akhiri proses dengan hasil yang sesuai kondisi laporan.</p>
                        </div>
                    </li>
                </ol>
            </section>
        </div>

        <section aria-labelledby="operational-settings-title">
            <div class="mb-3">
                <h2 id="operational-settings-title" class="text-base font-semibold text-slate-900">Pengaturan operasional</h2>
                <p class="mt-0.5 text-sm text-slate-600">Pilih modul untuk mengelola laporan, kategori, atau akses pengguna.</p>
            </div>

            <div class="grid gap-3 md:grid-cols-3">
                <a href="{{ route('admin.reports.index') }}" class="group flex min-h-36 flex-col rounded-xl border border-slate-200 bg-white p-5 transition-colors hover:border-emerald-300 hover:bg-emerald-50/50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-700">
                    <span class="flex size-10 items-center justify-center rounded-lg bg-emerald-50 text-emerald-800 transition-colors group-hover:bg-white"><x-icon name="file-text" class="size-5" /></span>
                    <span class="mt-4 flex items-center justify-between gap-3 text-sm font-semibold text-slate-900">
                        Laporan Sampah
                        <x-icon name="chevron-right" class="size-4 shrink-0 text-emerald-800 transition-transform group-hover:translate-x-0.5" />
                    </span>
                    <span class="mt-1 text-sm leading-5 text-slate-600">Tinjau antrean laporan dan perbarui status penanganannya.</span>
                </a>

                <a href="{{ route('admin.categories.index') }}" class="group flex min-h-36 flex-col rounded-xl border border-slate-200 bg-white p-5 transition-colors hover:border-emerald-300 hover:bg-emerald-50/50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-700">
                    <span class="flex size-10 items-center justify-center rounded-lg bg-emerald-50 text-emerald-800 transition-colors group-hover:bg-white"><x-icon name="tags" class="size-5" /></span>
                    <span class="mt-4 flex items-center justify-between gap-3 text-sm font-semibold text-slate-900">
                        Kategori Sampah
                        <x-icon name="chevron-right" class="size-4 shrink-0 text-emerald-800 transition-transform group-hover:translate-x-0.5" />
                    </span>
                    <span class="mt-1 text-sm leading-5 text-slate-600">Kelola kategori yang digunakan untuk mengelompokkan laporan.</span>
                </a>

                <a href="{{ route('admin.users.index') }}" class="group flex min-h-36 flex-col rounded-xl border border-slate-200 bg-white p-5 transition-colors hover:border-emerald-300 hover:bg-emerald-50/50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-700">
                    <span class="flex size-10 items-center justify-center rounded-lg bg-emerald-50 text-emerald-800 transition-colors group-hover:bg-white"><x-icon name="users" class="size-5" /></span>
                    <span class="mt-4 flex items-center justify-between gap-3 text-sm font-semibold text-slate-900">
                        Manajemen Pengguna
                        <x-icon name="chevron-right" class="size-4 shrink-0 text-emerald-800 transition-transform group-hover:translate-x-0.5" />
                    </span>
                    <span class="mt-1 text-sm leading-5 text-slate-600">Kelola akun Admin dan User yang memiliki akses ke EcoTrack.</span>
                </a>
            </div>
        </section>

        <p class="flex items-start gap-2 rounded-lg border border-emerald-100 bg-emerald-50/60 px-4 py-3 text-sm leading-5 text-emerald-950">
            <x-icon name="clock" class="mt-0.5 size-4 shrink-0" />
            Jam pada sidebar menggunakan zona waktu WITA (Asia/Makassar).
        </p>
    </div>
@endsection
