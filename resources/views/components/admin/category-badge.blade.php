@props(['category'])
@php
    $categories = [
        'Organic Waste' => ['label' => 'Sampah Organik', 'classes' => 'bg-emerald-50 text-emerald-800'],
        'Plastic Waste' => ['label' => 'Sampah Plastik', 'classes' => 'bg-blue-50 text-blue-800'],
        'Paper Waste' => ['label' => 'Sampah Kertas', 'classes' => 'bg-indigo-50 text-indigo-800'],
    ];
    $categoryView = $categories[$category] ?? ['label' => $category, 'classes' => 'bg-slate-100 text-slate-700'];
@endphp
<span class="inline-flex rounded-full px-2.5 py-1 text-[11px] font-medium {{ $categoryView['classes'] }}">
    {{ $categoryView['label'] }}
</span>
