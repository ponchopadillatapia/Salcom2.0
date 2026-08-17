@php
    $fecha = $fecha ?? null;
    $plazo = $plazo ?? null;
    $restantes = \App\Models\Factura::diasHastaVencimiento($fecha);
@endphp
@if($restantes === null && ! $fecha)
    —
@else
    <div class="dias-count {{ $restantes !== null && $restantes < 0 ? 'late' : ($restantes !== null && $restantes <= 15 ? 'warn' : '') }}">
        @if($restantes === null)
            {{ $fecha->format('d/m/Y') }}
        @elseif($restantes > 0)
            {{ $restantes }} días
        @elseif($restantes === 0)
            Vence hoy
        @else
            Vencida ({{ abs($restantes) }})
        @endif
    </div>
    @if($fecha && $restantes !== null)
        <div class="dias-sub">{{ $fecha->format('d/m/Y') }}@if($plazo) · de {{ $plazo }}@endif</div>
    @elseif($plazo)
        <div class="dias-sub">de {{ $plazo }}</div>
    @endif
@endif
