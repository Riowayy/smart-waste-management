@php
    $editing = isset($user);
    $isSelfEdit = $editing && auth()->user()->is($user);
@endphp

@if ($errors->any())
    <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800" role="alert" aria-labelledby="form-errors-title">
        <p id="form-errors-title" class="font-semibold">Periksa kembali data berikut:</p>
        <ul class="mt-1 list-inside list-disc">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif

<section class="overflow-hidden rounded-xl border border-slate-200 bg-white" aria-labelledby="account-information-title">
    <div class="border-b border-slate-100 px-5 py-4 sm:px-6">
        <h2 id="account-information-title" class="text-base font-semibold text-slate-900">Informasi akun</h2>
        <p class="mt-0.5 text-sm text-slate-500">Isi data yang akan digunakan untuk mengakses EcoTrack.</p>
    </div>

    <div class="grid gap-x-5 gap-y-5 p-5 sm:grid-cols-2 sm:p-6">
        <div>
            <label for="name" class="mb-1.5 block text-sm font-medium text-slate-700">Nama lengkap <span class="text-rose-700" aria-hidden="true">*</span><span class="sr-only">wajib diisi</span></label>
            <input id="name" name="name" type="text" value="{{ old('name', $user->name ?? '') }}" required maxlength="255" autocomplete="name" placeholder="Contoh: Dinda Pratama" class="field-control w-full" @if ($errors->has('name')) aria-invalid="true" aria-describedby="name-error" @endif>
            @error('name')<p id="name-error" class="mt-1.5 text-sm text-rose-700">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="email" class="mb-1.5 block text-sm font-medium text-slate-700">Email <span class="text-rose-700" aria-hidden="true">*</span><span class="sr-only">wajib diisi</span></label>
            <input id="email" name="email" type="email" value="{{ old('email', $user->email ?? '') }}" required maxlength="255" autocomplete="email" placeholder="nama@contoh.com" class="field-control w-full" @if ($errors->has('email')) aria-invalid="true" aria-describedby="email-error" @endif>
            @error('email')<p id="email-error" class="mt-1.5 text-sm text-rose-700">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="role" class="mb-1.5 block text-sm font-medium text-slate-700">Role <span class="text-rose-700" aria-hidden="true">*</span><span class="sr-only">wajib diisi</span></label>
            @if ($isSelfEdit)
                <input type="hidden" name="role" value="{{ $user->role }}">
            @endif
            <select id="role" name="{{ $isSelfEdit ? 'role_display' : 'role' }}" @unless ($isSelfEdit) required @else disabled aria-describedby="role-help" @endunless class="field-control w-full" @if ($errors->has('role')) aria-invalid="true" aria-describedby="role-error" @endif>
                <option value="user" @selected(old('role', $user->role ?? 'user') === 'user')>User</option>
                <option value="admin" @selected(old('role', $user->role ?? 'user') === 'admin')>Admin</option>
            </select>
            <p id="role-help" class="mt-1.5 text-xs leading-5 text-slate-500">@if ($isSelfEdit)Role akun yang sedang digunakan tidak dapat diubah.@elseif (! $editing)Akun baru secara default menggunakan role User.@else Pilih hak akses yang sesuai. Admin terakhir tidak dapat diturunkan menjadi User.@endif</p>
            @error('role')<p id="role-error" class="mt-1.5 text-sm text-rose-700">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="password" class="mb-1.5 block text-sm font-medium text-slate-700">{{ $editing ? 'Password baru (opsional)' : 'Password' }} @unless ($editing)<span class="text-rose-700" aria-hidden="true">*</span><span class="sr-only">wajib diisi</span>@endunless</label>
            <input id="password" name="password" type="password" @required(! $editing) minlength="6" autocomplete="new-password" placeholder="{{ $editing ? 'Kosongkan jika tidak diganti' : 'Minimal 6 karakter' }}" class="field-control w-full" @if ($errors->has('password')) aria-invalid="true" aria-describedby="password-help password-error" @else aria-describedby="password-help" @endif>
            <p id="password-help" class="mt-1.5 text-xs leading-5 text-slate-500">Minimal 6 karakter.</p>
            @error('password')<p id="password-error" class="mt-1.5 text-sm text-rose-700">{{ $message }}</p>@enderror
        </div>

        <div class="sm:col-span-2">
            <label for="password_confirmation" class="mb-1.5 block text-sm font-medium text-slate-700">Konfirmasi {{ $editing ? 'password baru' : 'password' }} @unless ($editing)<span class="text-rose-700" aria-hidden="true">*</span><span class="sr-only">wajib diisi</span>@endunless</label>
            <input id="password_confirmation" name="password_confirmation" type="password" @required(! $editing) autocomplete="new-password" placeholder="Ulangi password" class="field-control w-full sm:max-w-md">
        </div>
    </div>

    <div class="flex flex-wrap gap-3 border-t border-slate-100 px-5 py-4 sm:px-6">
        <button type="submit" class="inline-flex min-h-10 items-center justify-center rounded-lg bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-emerald-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-700">{{ $editing ? 'Simpan Perubahan' : 'Buat Akun' }}</button>
        <a href="{{ $editing ? route('admin.users.show', $user) : route('admin.users.index') }}" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 transition-colors hover:bg-slate-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-700">Batal</a>
    </div>
</section>
