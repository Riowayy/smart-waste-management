@props(['filtered' => false])
<div class="rounded-xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center">
    <x-icon name="file-text" class="mx-auto size-8 text-emerald-700" />
    <h2 class="mt-4 text-balance text-lg font-semibold text-slate-900">{{ $filtered ? 'Tidak ada laporan yang sesuai' : 'Laporan pertama Anda dimulai di sini' }}</h2>
    <p class="mx-auto mt-2 max-w-md text-pretty text-sm leading-6 text-slate-600">{{ $filtered ? 'Coba kata pencarian lain atau hapus filter untuk melihat kembali laporan Anda.' : 'Temukan sampah yang perlu ditangani? Kirim foto dan keterangan agar laporan Anda mudah ditinjau.' }}</p>
    <a href="{{ $filtered ? route('user.reports.index') : route('user.reports.create') }}" class="user-button mt-5">{{ $filtered ? 'Hapus filter' : 'Buat Laporan' }}</a>
</div>
