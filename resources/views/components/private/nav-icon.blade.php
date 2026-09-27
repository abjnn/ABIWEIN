@props(['name'])

{{-- SYMBOLES DE NAVIGATION : traits simples, sans emoji ni bibliothèque externe. --}}
<span class="private-nav__icon" aria-hidden="true">
    @switch($name)
        @case('dashboard')
            <svg viewBox="0 0 24 24"><path d="M4 13h6V4H4v9Zm0 7h6v-4H4v4Zm10 0h6v-9h-6v9Zm0-16v4h6V4h-6Z"/></svg>
            @break
        @case('flock')
            <svg viewBox="0 0 24 24"><path d="M5 14c0-4 3-7 7-7 3 0 5.5 1.6 6.6 4M7 17h9c2.2 0 4-1.8 4-4v-1h-3M8 17v3m7-3v3M11 7V4l3 2m-8 7H3m2-3H3"/></svg>
            @break
        @case('report')
            <svg viewBox="0 0 24 24"><path d="M6 3h9l3 3v15H6V3Zm3 7h6m-6 4h6m-6 4h4M15 3v4h4"/></svg>
            @break
        @case('stock')
            <svg viewBox="0 0 24 24"><path d="m4 7 8-4 8 4-8 4-8-4Zm0 0v10l8 4 8-4V7M12 11v10"/></svg>
            @break
        @case('commerce')
            <svg viewBox="0 0 24 24"><path d="M4 5h16v14H4V5Zm0 4h16M8 15h3m4 0h2"/></svg>
            @break
        @case('client')
            <svg viewBox="0 0 24 24"><path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm-7 8c.7-4 3-6 7-6s6.3 2 7 6"/></svg>
            @break
        @case('sale')
            <svg viewBox="0 0 24 24"><path d="M5 5h2l2 10h8l2-7H8m2 11a1 1 0 1 0 0 .1M17 19a1 1 0 1 0 0 .1"/></svg>
            @break
        @case('receivable')
            <svg viewBox="0 0 24 24"><path d="M4 7h16v11H4V7Zm3-3h10v3m-6 4h4m-2-2v4"/></svg>
            @break
        @case('expense')
            <svg viewBox="0 0 24 24"><path d="M12 3v14m-4-4 4 4 4-4M5 21h14"/></svg>
            @break
        @case('health')
            <svg viewBox="0 0 24 24"><path d="M12 21S4 16.5 4 10a4 4 0 0 1 7-2.6A4 4 0 0 1 20 10c0 6.5-8 11-8 11Zm-3-9h6m-3-3v6"/></svg>
            @break
        @case('users')
            <svg viewBox="0 0 24 24"><path d="M9 11a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Zm-6 9c.5-4 2.5-6 6-6s5.5 2 6 6m1-10a3 3 0 0 0 0-6m1 10c2.3.4 3.7 2.4 4 5"/></svg>
            @break
        @case('audit')
            <svg viewBox="0 0 24 24"><path d="M12 3 5 6v5c0 4.8 2.8 8.2 7 10 4.2-1.8 7-5.2 7-10V6l-7-3Zm-3 9 2 2 4-5"/></svg>
            @break
        @default
            <svg viewBox="0 0 24 24"><path d="M5 12h14M12 5v14"/></svg>
    @endswitch
</span>
