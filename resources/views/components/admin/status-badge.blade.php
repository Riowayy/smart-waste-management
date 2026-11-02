@props(['status'])
@php
    $statuses = [
        'Pending' => ['classes' => 'bg-amber-50 text-amber-700', 'dot' => 'bg-amber-500'],
        'pending' => ['classes' => 'bg-amber-50 text-amber-700', 'dot' => 'bg-amber-500'],
        'In Progress' => ['classes' => 'bg-blue-50 text-blue-700', 'dot' => 'bg-blue-600'],
        'in_progress' => ['classes' => 'bg-blue-50 text-blue-700', 'dot' => 'bg-blue-600'],
        'Completed' => ['classes' => 'bg-emerald-50 text-emerald-700', 'dot' => 'bg-emerald-500'],
        'completed' => ['classes' => 'bg-emerald-50 text-emerald-700', 'dot' => 'bg-emerald-500'],
        'Rejected' => ['classes' => 'bg-red-50 text-red-700', 'dot' => 'bg-red-500'],
        'rejected' => ['classes' => 'bg-red-50 text-red-700', 'dot' => 'bg-red-500'],
    ];
    $statusView = $statuses[$status] ?? ['classes' => 'bg-slate-100 text-slate-600', 'dot' => 'bg-slate-400'];
@endphp
<span class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $statusView['classes'] }}">
    <span class="h-1.5 w-1.5 rounded-full {{ $statusView['dot'] }}"></span>
    {{ \App\Support\ReportLabels::status($status) }}
</span>
