{{-- SVG Icon Library — Industrias Salcom Admin --}}
@php $s = $size ?? 18; $c = $color ?? 'currentColor'; $sw = $stroke ?? 2; @endphp

@switch($name ?? '')
    @case('users')
        <svg width="{{ $s }}" height="{{ $s }}" viewBox="0 0 24 24" fill="none" stroke="{{ $c }}" stroke-width="{{ $sw }}" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        @break
    @case('factory')
        <svg width="{{ $s }}" height="{{ $s }}" viewBox="0 0 24 24" fill="none" stroke="{{ $c }}" stroke-width="{{ $sw }}" stroke-linecap="round" stroke-linejoin="round"><path d="M2 20h20"/><path d="M5 20V8l5 4V8l5 4V4h3a2 2 0 0 1 2 2v14"/><path d="M8 14h.01"/><path d="M8 18h.01"/><path d="M12 14h.01"/><path d="M12 18h.01"/><path d="M16 14h.01"/><path d="M16 18h.01"/></svg>
        @break
    @case('package')
        <svg width="{{ $s }}" height="{{ $s }}" viewBox="0 0 24 24" fill="none" stroke="{{ $c }}" stroke-width="{{ $sw }}" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
        @break
    @case('flask')
        <svg width="{{ $s }}" height="{{ $s }}" viewBox="0 0 24 24" fill="none" stroke="{{ $c }}" stroke-width="{{ $sw }}" stroke-linecap="round" stroke-linejoin="round"><path d="M9 3h6v7l4 8H5l4-8V3z"/><path d="M9 3h6"/></svg>
        @break
    @case('dollar')
        <svg width="{{ $s }}" height="{{ $s }}" viewBox="0 0 24 24" fill="none" stroke="{{ $c }}" stroke-width="{{ $sw }}" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
        @break
    @case('star')
        <svg width="{{ $s }}" height="{{ $s }}" viewBox="0 0 24 24" fill="none" stroke="{{ $c }}" stroke-width="{{ $sw }}" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
        @break
    @case('microscope')
        <svg width="{{ $s }}" height="{{ $s }}" viewBox="0 0 24 24" fill="none" stroke="{{ $c }}" stroke-width="{{ $sw }}" stroke-linecap="round" stroke-linejoin="round"><path d="M6 18h8"/><path d="M3 22h18"/><path d="M14 22a7 7 0 1 0 0-14h-1"/><path d="M9 14h2"/><path d="M9 12a2 2 0 0 1-2-2V6h6v4a2 2 0 0 1-2 2Z"/><path d="M12 6V3a1 1 0 0 0-1-1H9a1 1 0 0 0-1 1v3"/></svg>
        @break
    @case('file-text')
        <svg width="{{ $s }}" height="{{ $s }}" viewBox="0 0 24 24" fill="none" stroke="{{ $c }}" stroke-width="{{ $sw }}" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
        @break
    @case('bar-chart')
        <svg width="{{ $s }}" height="{{ $s }}" viewBox="0 0 24 24" fill="none" stroke="{{ $c }}" stroke-width="{{ $sw }}" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="20" x2="12" y2="10"/><line x1="18" y1="20" x2="18" y2="4"/><line x1="6" y1="20" x2="6" y2="16"/></svg>
        @break
    @case('trophy')
        <svg width="{{ $s }}" height="{{ $s }}" viewBox="0 0 24 24" fill="none" stroke="{{ $c }}" stroke-width="{{ $sw }}" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6"/><path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18"/><path d="M4 22h16"/><path d="M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20 7 22"/><path d="M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20 17 22"/><path d="M18 2H6v7a6 6 0 0 0 12 0V2Z"/></svg>
        @break
    @case('zap')
        <svg width="{{ $s }}" height="{{ $s }}" viewBox="0 0 24 24" fill="none" stroke="{{ $c }}" stroke-width="{{ $sw }}" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
        @break
    @case('banknote')
        <svg width="{{ $s }}" height="{{ $s }}" viewBox="0 0 24 24" fill="none" stroke="{{ $c }}" stroke-width="{{ $sw }}" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2"/><path d="M6 12h.01M18 12h.01"/></svg>
        @break
    @case('grid')
        <svg width="{{ $s }}" height="{{ $s }}" viewBox="0 0 24 24" fill="none" stroke="{{ $c }}" stroke-width="{{ $sw }}" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/></svg>
        @break
@endswitch
