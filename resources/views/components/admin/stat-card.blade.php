@props(['label', 'value', 'description', 'icon', 'iconBg' => 'bg-emerald-50', 'iconColor' => 'text-emerald-700'])
<div class="dashboard-card flex min-h-32 flex-col justify-between p-5 transition-shadow hover:shadow-md sm:p-6">
    <div class="flex items-center justify-between gap-3">
        <span class="text-[11px] font-semibold uppercase tracking-[0.08em] text-slate-500">{{ $label }}</span>
        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg {{ $iconBg }} {{ $iconColor }}">
            <x-icon :name="$icon" class="h-5 w-5" />
        </span>
    </div>
    <div class="mt-5">
        <p class="font-display text-[27px] font-bold leading-none tracking-tight text-slate-900 tabular-nums">{{ $value }}</p>
        <p class="mt-1.5 text-xs text-slate-500">{{ $description }}</p>
    </div>
</div>
