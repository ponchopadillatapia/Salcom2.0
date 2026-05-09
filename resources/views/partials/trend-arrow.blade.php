{{-- 
    Componente de flechita de tendencia
    Uso: @include('partials.trend-arrow', ['value' => 12]) 
    - Positivo: ↑ +12% en verde
    - Negativo: ↓ -5% en rojo
    - Cero: → 0% en gris
--}}
@php
    $val = $value ?? 0;
    $color = $val > 0 ? 'var(--green)' : ($val < 0 ? 'var(--red)' : 'var(--gray-muted)');
    $arrow = $val > 0 ? '↑' : ($val < 0 ? '↓' : '→');
    $sign = $val > 0 ? '+' : '';
@endphp
<span style="font-size:{{ $size ?? '13' }}px;font-weight:700;color:{{ $color }};">{{ $arrow }} {{ $sign }}{{ $val }}%</span>
