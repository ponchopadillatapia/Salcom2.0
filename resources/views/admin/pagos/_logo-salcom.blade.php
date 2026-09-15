@php
    // Logo de Salcom embebido en base64 para que DomPDF lo renderice sin depender de rutas.
    $logoPath = public_path('images/logo.png');
    $logoData = is_file($logoPath) ? 'data:image/png;base64,'.base64_encode(file_get_contents($logoPath)) : null;
@endphp
@if($logoData)
    <img src="{{ $logoData }}" alt="Industrias Salcom" style="height:38px;">
@else
    <div style="font-weight:bold;font-size:16px;color:#111;">INDUSTRIAS SALCOM <span style="font-size:9px;color:#666;">S.A. DE C.V.</span></div>
@endif
