{{-- Logo Salcom Industries Inc. — SVG inline, se adapta a cualquier fondo --}}
{{-- Uso: @include('partials.logo-salcom', ['height' => 40, 'color' => 'light']) --}}
{{-- color: 'light' (para fondos oscuros) | 'dark' (para fondos claros) --}}
@php
    $h = $height ?? 40;
    $w = round($h * 3.2);
    $salColor = ($color ?? 'light') === 'light' ? '#c0c0c0' : '#4a4a4a';
    $comColor = '#8BC34A';
    $indColor = ($color ?? 'light') === 'light' ? '#999' : '#888';
@endphp
<svg width="{{ $w }}" height="{{ $h }}" viewBox="0 0 320 100" fill="none" xmlns="http://www.w3.org/2000/svg" aria-label="Salcom Industries Inc.">
    {{-- S --}}
    <path d="M8 22h42c4 0 7 3 7 7v10c0 4-3 7-7 7H14c-4 0-7 3-7 7v18c0 4 3 7 7 7h43" stroke="{{ $salColor }}" stroke-width="5" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
    {{-- A --}}
    <path d="M72 78L88 22h10l16 56" stroke="{{ $salColor }}" stroke-width="5" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
    <line x1="77" y1="58" x2="99" y2="58" stroke="{{ $salColor }}" stroke-width="4" stroke-linecap="round"/>
    {{-- L --}}
    <path d="M118 22v56h32" stroke="{{ $salColor }}" stroke-width="5" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
    {{-- C --}}
    <path d="M196 28c-4-4-10-7-17-7h-6c-10 0-18 8-18 18v22c0 10 8 18 18 18h6c7 0 13-3 17-7" stroke="{{ $comColor }}" stroke-width="5.5" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
    {{-- O --}}
    <rect x="202" y="21" width="38" height="58" rx="12" stroke="{{ $comColor }}" stroke-width="5.5" fill="none"/>
    {{-- M --}}
    <path d="M250 78V22l18 34 18-34v56" stroke="{{ $comColor }}" stroke-width="5.5" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
    {{-- INDUSTRIES INC. --}}
    <text x="160" y="96" text-anchor="middle" fill="{{ $indColor }}" font-family="'Inter','Segoe UI',sans-serif" font-size="11" font-weight="600" letter-spacing="5">INDUSTRIES INC.</text>
</svg>
