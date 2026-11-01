@props(['status'])
@php
    $statuses = [
        'Pending' => ['label' => 'Menunggu', 'classes' => 'bg-amber-50 text-amber-700', 'dot' => 'bg-amber-500'],
        'pending' => ['label' => 'Menunggu', 'classes' => 'bg-amber-50 text-amber-700', 'dot' => 'bg-amber-500'],
        'In Progress' => ['label' => 'Dalam Proses', 'classes' => 'bg-blue-50 text-blue-700', 'dot' => 'bg-blue-600'],
        'in_progress' => ['label' => 'Dalam Proses', 'classes' => 'bg-blue-50 text-blue-700', 'dot' => 'bg-blue-600'],
        'Completed' => ['label' => 'Selesai', 'classes' => 'bg-emerald-50 text-emerald-700', 'dot' => 'bg-emerald-500'],
        'completed' => ['label' => 'Selesai', 'classes' => 'bg-emerald-50 text-emerald-700', 'dot' => 'bg-emerald-500'],
        'Rejected' => ['label' => 'Ditolak', 'classes' => 'bg-red-50 text-red-700', 'dot' => 'bg-red-500'],
        'rejected' => ['label' => 'Ditolak', 'classes' => 'bg-red-50 text-red-700', 'dot' => 'bg-red-500'],
    ];
    $statusView = $statuses[$status] ?? ['label' => $status, 'classes' => 'bg-slate-100 text-slate-600', 'dot' => 'bg-slate-400'];
@endphp
<span class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $statusView['classes'] }}">
    <span class="h-1.5 w-1.5 rounded-full {{ $statusView['dot'] }}"></span>
    {{ $statusView['label'] }}
</span>
