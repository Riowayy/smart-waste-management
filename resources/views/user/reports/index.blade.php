@extends('layouts.user')

@section('title', 'Laporan Saya')

@section('content')
    <x-user.page-heading title="Laporan Saya" description="Seluruh laporan yang Anda kirim, beserta perkembangan penanganannya.">
    </x-user.page-heading>
    @if($errors->any())<div class="user-error" role="alert">{{ $errors->first() }}</div>@endif
    <form method="GET" action="{{ route('user.reports.index') }}" class="user-filter" role="search" novalidate>
        <div class="min-w-0 flex-1">
            <label for="search" class="user-label">Cari laporan</label>
            <input id="search" name="search" type="search" value="{{ request('search') }}" class="field-control w-full" placeholder="Nomor atau deskripsi laporan…" autocomplete="off">
        </div>
        <div>
            <label for="category" class="user-label">Kategori</label>
            <select id="category" name="category" class="field-control w-full">
                <option value="">Semua kategori</option>
                @foreach($categories as $category)<option value="{{ $category->id }}" @selected((string)request('category') === (string)$category->id)>{{ \App\Support\ReportLabels::category($category) }}</option>@endforeach
            </select>
        </div>
        <div>
            <label for="status" class="user-label">Status</label>
            <select id="status" name="status" class="field-control w-full">
                <option value="">Semua status</option>
                @foreach($statuses as $status => $label)<option value="{{ $status }}" @selected(request('status') === $status)>{{ $label }}</option>@endforeach
            </select>
        </div>
        <button class="user-button" type="submit">Terapkan</button>
        @if(request()->filled('search') || request()->filled('category') || request()->filled('status'))<a href="{{ route('user.reports.index') }}" class="py-3 text-sm font-medium text-emerald-800 hover:underline">Reset</a>@endif
    </form>
    <section class="space-y-4" aria-label="Hasil pencarian laporan">
        <p class="text-sm text-slate-600"><span class="font-semibold tabular-nums text-slate-900">{{ number_format($reports->total()) }}</span> laporan ditemukan</p>
        @if($reports->isEmpty())
            <x-user.empty-state :filtered="request()->filled('search') || request()->filled('category') || request()->filled('status')" />
        @else
            <x-user.report-table :reports="$reports" />
            {{ $reports->links('admin.reports._pagination') }}
        @endif
    </section>
@endsection
