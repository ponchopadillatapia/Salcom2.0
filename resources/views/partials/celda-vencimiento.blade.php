@php
    $fecha = $fecha ?? null;
    $plazo = $plazo ?? null;
    $restantes = \App\Models\Factura::diasHastaVencimiento($fecha);

    // Colores: verde >30, amarillo 11-30, rojo 1-10, rojo tinto <=0
    if ($restantes === null) {
        $colorClass = '';
        $colorStyle = '';
    } elseif ($restantes <= 0) {
        $colorClass = '';
        $colorStyle = 'color:#7f1d1d;font-weight:800;'; // rojo tinto
    } elseif ($restantes <= 10) {
        $colorClass = '';
        $colorStyle = 'color:#dc2626;font-weight:700;'; // rojo
    } elseif ($restantes <= 30) {
        $colorClass = '';
        $colorStyle = 'color:#d97706;font-weight:700;'; // amarillo/ámbar
    } else {
        $colorClass = '';
        $colorStyle = 'color:#16a34a;font-weight:700;'; // verde
    }
@endphp
@if($restantes === null && ! $fecha)
    —
@else
    <div class="dias-count" style="{{ $colorStyle }}">
        @if($restantes === null)
            {{ $fecha->format('d/m/Y') }}
        @elseif($restantes > 0)
            {{ $restantes }} días
        @elseif($restantes === 0)
            Vence hoy
        @else
            {{ $restantes }} días
        @endif
    </div>
    @if($fecha && $restantes !== null)
        <div class="dias-sub">{{ $fecha->format('d/m/Y') }}@if($plazo) · de {{ $plazo }}@endif</div>
    @elseif($plazo)
        <div class="dias-sub">de {{ $plazo }}</div>
    @endif
@endif
