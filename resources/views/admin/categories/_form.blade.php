@if ($errors->any())
    <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800" role="alert" aria-labelledby="category-form-errors-title">
        <p id="category-form-errors-title" class="font-semibold">Periksa kembali data berikut:</p>
        <ul class="mt-1 list-inside list-disc">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif

<section class="dashboard-card space-y-5 p-5 sm:p-6" aria-label="Informasi kategori">
    <div>
        <label for="name" class="mb-1.5 block text-sm font-medium text-slate-700">Nama kategori <span class="text-rose-700">*</span></label>
        <input id="name" name="name" type="text" value="{{ old('name', $category->name ?? '') }}" required maxlength="255" autocomplete="off" placeholder="Contoh: Sampah Organik" class="field-control w-full" @if($errors->has('name')) aria-invalid="true" aria-describedby="name-error" @endif>
        @error('name')<p id="name-error" class="mt-1.5 text-sm text-rose-700" role="alert">{{ $message }}</p>@enderror
        <p class="mt-1.5 text-xs text-slate-500">Nama kategori harus unik. Slug dibuat otomatis dari nama.</p>
    </div>

    <div>
        <label for="description" class="mb-1.5 block text-sm font-medium text-slate-700">Deskripsi <span class="text-xs font-normal text-slate-500">(opsional)</span></label>
        <textarea id="description" name="description" rows="4" maxlength="5000" placeholder="Jelaskan jenis sampah yang termasuk dalam kategori ini." class="field-control h-auto w-full py-2.5" @if($errors->has('description')) aria-invalid="true" aria-describedby="description-error" @endif>{{ old('description', $category->description ?? '') }}</textarea>
        @error('description')<p id="description-error" class="mt-1.5 text-sm text-rose-700" role="alert">{{ $message }}</p>@enderror
    </div>
</section>

<div class="flex flex-wrap gap-3">
    <button type="submit" class="min-h-10 rounded-lg bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-emerald-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-700">{{ $editing ? 'Simpan Perubahan' : 'Simpan Kategori' }}</button>
    <a href="{{ route('admin.categories.index') }}" class="inline-flex min-h-10 items-center rounded-lg border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 transition-colors hover:bg-slate-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-700">Batal</a>
</div>
