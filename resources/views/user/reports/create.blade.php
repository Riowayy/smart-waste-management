@extends('layouts.user')

@section('title', 'Buat Laporan')

@section('content')
    <x-user.page-heading title="Buat Laporan" description="Ceritakan kondisi yang Anda temukan. Laporan akan dikirim dengan status Menunggu untuk ditinjau Admin." />
    <div class="grid items-start gap-7 xl:grid-cols-3">
        <form method="POST" action="{{ route('user.reports.store') }}" enctype="multipart/form-data" class="user-form space-y-6 xl:col-span-2" data-user-report-form novalidate>
            @csrf
            @if($errors->any())
                <div class="user-error" role="alert" tabindex="-1" data-form-errors>
                    <p class="font-semibold">Laporan belum dapat dikirim. Periksa data berikut:</p>
                    <ul class="mt-2 list-inside list-disc">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif
            <p class="text-sm text-slate-600">Field bertanda <span class="font-semibold text-red-700">*</span> wajib diisi.</p>
            <div>
                <label for="category_id" class="user-label">Kategori sampah <span class="font-normal text-slate-600">(opsional)</span></label>
                <select id="category_id" name="category_id" class="field-control w-full" @if($errors->has('category_id')) aria-invalid="true" aria-describedby="category_id-error" @endif>
                    <option value="">Belum ditentukan</option>
                    @foreach($categories as $category)<option value="{{ $category->id }}" @selected((string)old('category_id') === (string)$category->id)>{{ \App\Support\ReportLabels::category($category) }}</option>@endforeach
                </select>
                @error('category_id')<p id="category_id-error" class="user-field-error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="description" class="user-label">Deskripsi laporan <span class="text-red-700">*</span></label>
                <textarea id="description" name="description" rows="4" required maxlength="1000" class="field-control w-full" placeholder="Contoh: Tumpukan sampah plastik di dekat pintu masuk taman…" aria-describedby="description-help description-count{{ $errors->has('description') ? ' description-error' : '' }}" @if($errors->has('description')) aria-invalid="true" @endif>{{ old('description') }}</textarea>
                <p id="description-help" class="mt-2 text-xs leading-5 text-slate-600">Jelaskan jenis sampah, kondisi, dan petunjuk lokasi. Maksimal 1.000 karakter.</p>
                <output id="description-count" for="description" class="mt-1 block text-right text-xs tabular-nums text-slate-600" data-description-count>{{ mb_strlen(old('description', '')) }} / 1000 karakter</output>
                @error('description')<p id="description-error" class="user-field-error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="image" class="user-label">Foto laporan <span class="text-red-700">*</span></label>
                <input id="image" name="image" type="file" required accept="image/jpeg,image/png,image/webp" class="field-control block w-full" data-report-image aria-describedby="image-help image-feedback{{ $errors->has('image') ? ' image-error' : '' }}" @if($errors->has('image')) aria-invalid="true" @endif>
                <p id="image-help" class="mt-2 text-xs leading-5 text-slate-600">JPEG, PNG, atau WebP, maksimal 5 MB. Setelah validasi gagal, pilih kembali foto Anda.</p>
                @error('image')<p id="image-error" class="user-field-error">{{ $message }}</p>@enderror
                <p id="image-feedback" class="user-field-error" role="status" data-image-feedback></p>
                <figure class="mt-3" hidden data-image-preview>
                    <img alt="Pratinjau foto yang akan dikirim" width="640" height="360" class="max-h-64 w-full rounded-lg border border-slate-200 object-contain" data-preview-image>
                    <figcaption class="mt-2 text-xs text-slate-600">Pratinjau foto laporan</figcaption>
                </figure>
            </div>
            <fieldset>
                <legend class="text-base font-semibold text-slate-900">Lokasi laporan</legend>
                <p class="mt-1 text-sm leading-6 text-slate-600">Di Google Maps, klik kanan pada lokasi, lalu salin koordinatnya. Angka pertama adalah Latitude dan angka kedua adalah Longitude.</p>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    @foreach(['latitude' => ['Latitude', '-90', '90'], 'longitude' => ['Longitude', '-180', '180']] as $name => [$label, $min, $max])
                        <div>
                            <label for="{{ $name }}" class="user-label">{{ $label }} <span class="text-red-700">*</span></label>
                            <input id="{{ $name }}" name="{{ $name }}" type="number" required step="any" min="{{ $min }}" max="{{ $max }}" value="{{ old($name) }}" class="field-control w-full" placeholder="Antara {{ $min }} dan {{ $max }}" @if($errors->has($name)) aria-invalid="true" aria-describedby="{{ $name }}-error" @endif>
                            @error($name)<p id="{{ $name }}-error" class="user-field-error">{{ $message }}</p>@enderror
                        </div>
                    @endforeach
                </div>
            </fieldset>
            <div class="flex flex-wrap items-center justify-end gap-4 border-t border-slate-200 pt-5">
                <a href="{{ route('user.reports.index') }}" class="text-sm font-semibold text-slate-600 hover:text-emerald-800">Batal</a>
                <button type="submit" class="user-button" data-report-submit>Kirim Laporan</button>
            </div>
        </form>
        <aside class="user-guide">
            <h2 class="text-balance text-lg font-semibold text-emerald-950">Laporan yang jelas lebih mudah ditinjau</h2>
            <p class="mt-3 text-pretty text-sm leading-6 text-emerald-900">Pastikan foto menunjukkan kondisi sampah. Hindari memasukkan informasi pribadi yang tidak berkaitan dengan laporan.</p>
            <hr class="my-5 border-emerald-200">
            <h3 class="font-semibold text-emerald-950">Setelah laporan dikirim</h3>
            <p class="mt-2 text-sm leading-6 text-emerald-900">Anda akan diarahkan ke detail laporan. Status terbaru dan catatan Admin dapat dipantau melalui menu Laporan Saya.</p>
        </aside>
    </div>
@endsection
