@props(['reports'])
<div class="overflow-x-auto rounded-xl border border-slate-200 bg-white" role="region" aria-label="Daftar laporan saya" tabindex="0">
    <table class="w-full min-w-[700px] text-left text-sm">
        <caption class="sr-only">Laporan milik Anda beserta kategori, tanggal pengiriman, dan status penanganan</caption>
        <thead class="border-b border-slate-200 text-slate-600">
            <tr><th scope="col" class="px-5 py-4 font-medium">Laporan</th><th scope="col" class="px-4 py-4 font-medium">Kategori</th><th scope="col" class="px-4 py-4 font-medium">Dikirim</th><th scope="col" class="px-4 py-4 font-medium">Status</th><th scope="col" class="px-5 py-4 text-right font-medium">Aksi</th></tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @foreach($reports as $report)
                <tr class="hover:bg-emerald-50/40">
                    <th scope="row" class="max-w-xs px-5 py-4 font-normal">
                        <a href="{{ route('user.reports.show', $report) }}" class="font-semibold text-emerald-800 hover:underline">Laporan #{{ $report->id }}</a>
                        <p class="mt-1 line-clamp-1 break-words text-slate-600">{{ $report->description }}</p>
                    </th>
                    <td class="px-4 py-4 text-slate-700">{{ \App\Support\ReportLabels::category($report->category) }}</td>
                    <td class="whitespace-nowrap px-4 py-4 tabular-nums text-slate-600">{{ $report->created_at->timezone('Asia/Makassar')->locale('id')->translatedFormat('j M Y') }}</td>
                    <td class="whitespace-nowrap px-4 py-4"><x-admin.status-badge :status="$report->status" /></td>
                    <td class="px-5 py-4 text-right"><a href="{{ route('user.reports.show', $report) }}" class="inline-flex min-h-9 items-center font-semibold text-emerald-800 hover:underline" aria-label="Detail laporan #{{ $report->id }}">Detail</a></td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
