@php($report = $report ?? null)
<div class="grid gap-5 sm:grid-cols-2">
    <div>
        <label for="user_id" class="mb-1.5 block text-sm font-medium text-slate-700">Pelapor</label>
        <select id="user_id" name="user_id" required class="field-control w-full">
            <option value="">Pilih pengguna</option>
            @foreach ($users as $user)
                <option value="{{ $user->id }}" @selected((string) old('user_id', $report?->user_id) === (string) $user->id)>{{ $user->name }} ({{ $user->email }})</option>
            @endforeach
        </select>
        @error('user_id') <p class="mt-1.5 text-sm text-red-700" role="alert">{{ $message }}</p> @enderror
    </div>
    <div>
        <label for="category_id" class="mb-1.5 block text-sm font-medium text-slate-700">Kategori</label>
        <select id="category_id" name="category_id" class="field-control w-full">
            <option value="">Belum ditentukan</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected((string) old('category_id', $report?->category_id) === (string) $category->id)>{{ $category->name }}</option>
            @endforeach
        </select>
        @error('category_id') <p class="mt-1.5 text-sm text-red-700" role="alert">{{ $message }}</p> @enderror
    </div>
    <div class="sm:col-span-2">
        <label for="description" class="mb-1.5 block text-sm font-medium text-slate-700">Deskripsi laporan</label>
        <textarea id="description" name="description" rows="4" required maxlength="10000" class="field-control h-auto w-full py-2.5">{{ old('description', $report?->description) }}</textarea>
        @error('description') <p class="mt-1.5 text-sm text-red-700" role="alert">{{ $message }}</p> @enderror
    </div>
    <div class="sm:col-span-2">
        <label for="image" class="mb-1.5 block text-sm font-medium text-slate-700">Foto laporan @if(!$report) <span class="text-red-700">(wajib)</span> @else <span class="text-slate-400">(opsional)</span> @endif</label>
        <input id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp" @required(!$report) class="block w-full rounded-lg border border-slate-200 bg-white text-sm text-slate-700 file:mr-4 file:border-0 file:bg-emerald-50 file:px-4 file:py-2.5 file:font-semibold file:text-emerald-800">
        <p class="mt-1 text-xs text-slate-500">JPEG, PNG, atau WebP; ukuran maksimal 5 MB.</p>
        @error('image') <p class="mt-1.5 text-sm text-red-700" role="alert">{{ $message }}</p> @enderror
        @if($report)
            <img src="{{ asset('storage/'.$report->image_path) }}" alt="Foto laporan saat ini" class="mt-3 h-32 w-40 rounded-lg border border-slate-200 object-cover">
        @endif
    </div>
    <div>
        <label for="latitude" class="mb-1.5 block text-sm font-medium text-slate-700">Latitude <span class="text-slate-400">(opsional)</span></label>
        <input id="latitude" name="latitude" type="number" step="any" min="-90" max="90" value="{{ old('latitude', $report?->latitude) }}" class="field-control w-full">
        @error('latitude') <p class="mt-1.5 text-sm text-red-700" role="alert">{{ $message }}</p> @enderror
    </div>
    <div>
        <label for="longitude" class="mb-1.5 block text-sm font-medium text-slate-700">Longitude <span class="text-slate-400">(opsional)</span></label>
        <input id="longitude" name="longitude" type="number" step="any" min="-180" max="180" value="{{ old('longitude', $report?->longitude) }}" class="field-control w-full">
        @error('longitude') <p class="mt-1.5 text-sm text-red-700" role="alert">{{ $message }}</p> @enderror
    </div>
</div>
<div class="mt-6 flex flex-wrap justify-end gap-3 border-t border-slate-100 pt-5">
    <a href="{{ $report ? route('admin.reports.show', $report) : route('admin.reports.index') }}" class="rounded-lg border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Batal</a>
    <button type="submit" class="rounded-lg bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800">{{ $report ? 'Simpan perubahan' : 'Buat laporan' }}</button>
</div>
