{{-- Logo Industrias Salcom S.A. de C.V. --}}
{{-- Uso: @include('partials.logo-salcom', ['size' => 'lg', 'color' => 'light']) --}}
{{-- size: 'sm' (navbar) | 'md' (error pages) | 'lg' (login/inicio) --}}
{{-- color: 'light' (fondos oscuros) | 'dark' (fondos claros) --}}
@php
    $sz = $size ?? 'md';
    $isLight = ($color ?? 'light') === 'light';

    $config = match($sz) {
        'sm' => ['sal' => '20px', 'com' => '20px', 'ind' => '7px', 'sa' => '7px', 'gap' => '0px', 'indSpacing' => '3px', 'saSpacing' => '1.5px'],
        'md' => ['sal' => '26px', 'com' => '26px', 'ind' => '9px', 'sa' => '8px', 'gap' => '1px', 'indSpacing' => '4px', 'saSpacing' => '2px'],
        'lg' => ['sal' => '36px', 'com' => '36px', 'ind' => '11px', 'sa' => '10px', 'gap' => '2px', 'indSpacing' => '5px', 'saSpacing' => '2.5px'],
        default => ['sal' => '26px', 'com' => '26px', 'ind' => '9px', 'sa' => '8px', 'gap' => '1px', 'indSpacing' => '4px', 'saSpacing' => '2px'],
    };

    $salColor = $isLight ? '#ffffff' : '#1a1a1a';
    $indColor = $isLight ? 'rgba(255,255,255,0.5)' : '#777';
    $saColor  = $isLight ? 'rgba(255,255,255,0.35)' : '#999';
    $comColor = '#8BC34A';
@endphp
<div style="display:inline-flex;flex-direction:column;align-items:flex-start;line-height:1;gap:{{ $config['gap'] }};">
    <span style="font-family:'Inter','Segoe UI',sans-serif;font-size:{{ $config['ind'] }};font-weight:700;color:{{ $indColor }};letter-spacing:{{ $config['indSpacing'] }};text-transform:uppercase;">INDUSTRIAS</span>
    <div style="display:flex;align-items:baseline;gap:0;">
        <span style="font-family:'Inter','Segoe UI',sans-serif;font-size:{{ $config['sal'] }};font-weight:900;color:{{ $salColor }};letter-spacing:-0.5px;line-height:1;">SAL</span><span style="font-family:'Inter','Segoe UI',sans-serif;font-size:{{ $config['com'] }};font-weight:800;color:{{ $comColor }};letter-spacing:-0.5px;line-height:1;">COM</span><span style="font-family:'Inter',sans-serif;font-size:{{ $config['sa'] }};color:{{ $comColor }};vertical-align:super;margin-left:2px;">®</span>
    </div>
    <span style="font-family:'Inter','Segoe UI',sans-serif;font-size:{{ $config['sa'] }};font-weight:600;color:{{ $saColor }};letter-spacing:{{ $config['saSpacing'] }};align-self:flex-end;">S.A. DE C.V.</span>
</div>
