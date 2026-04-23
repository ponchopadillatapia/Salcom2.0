{{-- Logo Industrias Salcom — PNG real --}}
{{-- Uso: @include('partials.logo-salcom', ['size' => 'lg', 'color' => 'light']) --}}
{{-- size: 'sm' (navbar) | 'md' (error pages) | 'lg' (login/inicio) --}}
{{-- color: 'light' (fondos oscuros) | 'dark' (fondos claros) --}}
@php
    $h = match($size ?? 'md') {
        'sm' => 28,
        'md' => 32,
        'lg' => 48,
        default => 32,
    };
    $isLight = ($color ?? 'dark') === 'light';
@endphp
@if($isLight)
<div style="background:rgba(255,255,255,0.95);border-radius:10px;padding:8px 16px;display:inline-block;">
    <img src="/images/logo.png" alt="Industrias Salcom S.A. de C.V." style="height:{{ $h }}px;display:block;">
</div>
@else
<img src="/images/logo.png" alt="Industrias Salcom S.A. de C.V." style="height:{{ $h }}px;display:block;">
@endif
