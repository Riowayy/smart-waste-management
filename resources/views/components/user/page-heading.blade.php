@props(['title', 'description' => null])
<header class="flex flex-wrap items-end justify-between gap-4">
    <div class="min-w-0">
        <h1 class="break-words text-balance font-display text-3xl font-semibold text-slate-900">{{ $title }}</h1>
        @if($description)<p class="mt-2 max-w-2xl text-pretty text-sm leading-6 text-slate-600">{{ $description }}</p>@endif
    </div>
    {{ $slot }}
</header>
