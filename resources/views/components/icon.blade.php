@props(['name', 'class' => 'h-5 w-5'])
<svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    @switch($name)
        @case('grid')
            <rect x="3.5" y="3.5" width="7" height="7" rx="1.2"/><rect x="13.5" y="3.5" width="7" height="7" rx="1.2"/><rect x="3.5" y="13.5" width="7" height="7" rx="1.2"/><rect x="13.5" y="13.5" width="7" height="7" rx="1.2"/>
            @break
        @case('file-text')
            <path d="M14 2.8H6.5A2.5 2.5 0 0 0 4 5.3v13.4a2.5 2.5 0 0 0 2.5 2.5h11a2.5 2.5 0 0 0 2.5-2.5V9z"/><path d="M14 2.8V9h6M8 13h8M8 17h8"/>
            @break
        @case('leaf')
            <path d="M20 4c-6.2 0-11 1.9-13.6 5.4C4.2 12.2 5.1 16 8.1 17c3 1 6-.6 8.1-3.7C18.1 10.5 19.2 7 20 4Z"/><path d="M4 20c3.2-4.1 6.6-7 11.4-9.8"/>
            @break
        @case('recycle')
            <path d="m7 19-3-3 3-3"/><path d="M4 16h4a3 3 0 0 0 2.6-1.5l1-1.7"/><path d="m17 5 4 1-1 4"/><path d="m21 6-2 3.5a3 3 0 0 1-2.6 1.5h-2"/><path d="m8 5 1-3 4 1"/><path d="m9 2 2 3.5a3 3 0 0 1 0 3L10 10"/>
            @break
        @case('plus')
            <path d="M12 5v14M5 12h14"/>
            @break
        @case('pencil')
            <path d="m16 4 4 4M4 20l4.5-1 10.7-10.7a2.1 2.1 0 0 0-3-3L5.5 16 4 20Z"/>
            @break
        @case('trash-2')
            <path d="M3 6h18M8 6V4h8v2m3 0-1 14H6L5 6m4 4v6m6-6v6"/>
            @break
        @case('tag')
            <path d="m20.2 13.2-7 7a2 2 0 0 1-2.8 0l-7.6-7.6a2 2 0 0 1-.6-1.4V5a2 2 0 0 1 2-2h6.2a2 2 0 0 1 1.4.6l8.4 6.8a2 2 0 0 1 0 2.8Z"/><circle cx="7.5" cy="7.5" r="1.1"/>
            @break
        @case('tags')
            <path d="m20.2 13.2-7 7a2 2 0 0 1-2.8 0l-7.6-7.6a2 2 0 0 1-.6-1.4V5a2 2 0 0 1 2-2h6.2a2 2 0 0 1 1.4.6l8.4 6.8a2 2 0 0 1 0 2.8Z"/><circle cx="7.5" cy="7.5" r="1.1"/>
            @break
        @case('users')
            <path d="M16 20v-1.5a3.5 3.5 0 0 0-3.5-3.5h-5A3.5 3.5 0 0 0 4 18.5V20"/><circle cx="10" cy="7.5" r="3.5"/><path d="M17 4.5a3.5 3.5 0 0 1 0 6.8M20 20v-1.5a3.5 3.5 0 0 0-2.5-3.35"/>
            @break
        @case('settings')
            <circle cx="12" cy="12" r="3"/><path d="m19.4 15 .1.1a1.8 1.8 0 1 1-2.5 2.5l-.1-.1a1.8 1.8 0 0 0-3 .9v.2a1.8 1.8 0 1 1-3.6 0v-.2a1.8 1.8 0 0 0-3-.9l-.1.1a1.8 1.8 0 1 1-2.5-2.5l.1-.1a1.8 1.8 0 0 0-.9-3h-.2a1.8 1.8 0 1 1 0-3.6h.2a1.8 1.8 0 0 0 .9-3l-.1-.1a1.8 1.8 0 1 1 2.5-2.5l.1.1a1.8 1.8 0 0 0 3-.9v-.2a1.8 1.8 0 1 1 3.6 0v.2a1.8 1.8 0 0 0 3 .9l.1-.1a1.8 1.8 0 1 1 2.5 2.5l-.1.1a1.8 1.8 0 0 0 .9 3h.2a1.8 1.8 0 1 1 0 3.6h-.2a1.8 1.8 0 0 0-.9 3Z"/>
            @break
        @case('menu')
            <path d="M4 6h16M4 12h16M4 18h16"/>
            @break
        @case('bell')
            <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/>
            @break
        @case('chevron-down')
            <path d="m6 9 6 6 6-6"/>
            @break
        @case('clipboard')
            <rect x="5" y="4.5" width="14" height="17" rx="2"/><path d="M9 4.5V3h6v1.5M8.5 10h7M8.5 14h7M8.5 18h4"/>
            @break
        @case('clock')
            <circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>
            @break
        @case('refresh')
            <path d="M20 7v5h-5M4 17v-5h5"/><path d="M5.6 9A7 7 0 0 1 18 6l2 6M4 12l2 6a7 7 0 0 0 12.4-3"/>
            @break
        @case('check-circle')
            <circle cx="12" cy="12" r="9"/><path d="m8 12 2.5 2.5L16.5 9"/>
            @break
        @case('search')
            <circle cx="10.8" cy="10.8" r="6.8"/><path d="m16 16 4.5 4.5"/>
            @break
        @case('map-pin')
            <path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/>
            @break
        @case('more')
            <circle cx="5" cy="12" r="1"/><circle cx="12" cy="12" r="1"/><circle cx="19" cy="12" r="1"/>
            @break
        @case('chevron-left')
            <path d="m15 18-6-6 6-6"/>
            @break
        @case('chevron-right')
            <path d="m9 18 6-6-6-6"/>
            @break
        @case('panel-left-close')
            <rect x="3.5" y="4" width="17" height="16" rx="2"/><path d="M9 4v16m7-11-3 3 3 3"/>
            @break
        @case('panel-left-open')
            <rect x="3.5" y="4" width="17" height="16" rx="2"/><path d="M9 4v16m4-11 3 3-3 3"/>
            @break
        @case('log-out')
            <path d="M10 17l5-5-5-5M15 12H3"/><path d="M12 3h5a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-5"/>
            @break
        @case('x')
            <path d="m18 6-12 12M6 6l12 12"/>
            @break
        @case('calendar')
            <rect x="3.5" y="5" width="17" height="16" rx="2"/><path d="M7.5 3v4M16.5 3v4M3.5 9h17"/>
            @break
        @default
            <circle cx="12" cy="12" r="8"/>
    @endswitch
</svg>
