@extends('layouts.admin')
@section('title', 'Validación Fiscal')
@section('hero')
<div class="hero-band">
    <h1>Validación Fiscal de Proveedores</h1>
    <p>Estado de documentos fiscales — cumplimiento ante el SAT</p>
</div>
@endsection
@push('styles')
<style>
    .fiscal-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:18px;margin-bottom:28px}
    .fiscal-card{background:var(--white);border:1px solid var(--border-light);border-radius:14px;padding:22px;transition:all .2s}
    .fiscal-card:hover{box-shadow:0 6px 20px rgba(0,0,0,.05)}
    .fiscal-card.verde{border-left:4px solid #059669}
    .fiscal-card.amarillo{border-left:4px solid #d97706}
    .fiscal-card.rojo{border-left:4px solid #dc2626}
    .fiscal-card.gris{border-left:4px solid #9ca3af}

    .fiscal-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:14px}
    .fiscal-name{font-size:14px;font-weight:700;color:var(--gray-text)}
    .fiscal-code{font-size:11px;color:var(--gray-muted);font-weight:500}
    .fiscal-badge{font-size:10px;font-weight:700;padding:4px 10px;border-radius:999px;text-transform:uppercase;letter-spacing:.4px}
    .fiscal-badge.verde{background:#ecfdf5;color:#059669}
    .fiscal-badge.amarillo{background:#fefce8;color:#d97706}
    .fiscal-badge.rojo{background:#fef2f2;color:#dc2626}
    .fiscal-badge.gris{background:#f3f4f6;color:#6b7280}

    .fiscal-docs{display:grid;grid-template-columns:repeat(3,1fr);gap:6px;margin-top:12px}
    .doc-chip{font-size:10px;font-weight:600;padding:5px 8px;border-radius:6px;text-align:center;text-transform:uppercase;letter-spacing:.3px}
    .doc-chip.aprobado{background:#ecfdf5;color:#059669}
    .doc-chip.pendiente{background:#fefce8;color:#d97706}
    .doc-chip.rechazado{background:#fef2f2;color:#dc2626}
    .doc-chip.faltante{background:#f3f4f6;color:#9ca3af}

    .fiscal-summary{display:flex;gap:12px;margin-top:10px;padding-top:10px;border-top:1px solid var(--border-light)}
    .fiscal-stat{font-size:11px;color:var(--gray-muted)}
    .fiscal-stat strong{color:var(--gray-text)}

    .btn-validar{display:inline-flex;align-items:center;gap:6px;margin-top:12px;padding:8px 16px;font-size:12px;font-weight:600;color:var(--purple);background:var(--purple-light);border:1px solid var(--purple-mid);border-radius:8px;text-decoration:none;transition:all .15s}
    .btn-validar:hover{background:var(--purple);color:#fff}

    .legend{display:flex;gap:16px;margin-bottom:20px;flex-wrap:wrap}
    .legend-item{display:flex;align-items:center;gap:6px;font-size:12px;font-weight:600;color:var(--gray-text)}
    .legend-dot{width:10px;height:10px;border-radius:3px}

    @media(max-width:768px){.fiscal-grid{grid-template-columns:1fr}.fiscal-docs{grid-template-columns:repeat(2,1fr)}}
</style>
@endpush
@section('content')

<div class="legend">
    <div class="legend-item"><div class="legend-dot" style="background:#059669"></div>Cumple (SAT al día)</div>
    <div class="legend-item"><div class="legend-dot" style="background:#d97706"></div>En revisión</div>
    <div class="legend-item"><div class="legend-dot" style="background:#dc2626"></div>Rechazado / No cumple</div>
    <div class="legend-item"><div class="legend-dot" style="background:#9ca3af"></div>Sin documentos</div>
</div>

<div class="fiscal-grid">
@foreach($documentosPorProveedor as $item)
    @php
        $prov = $item['proveedor'];
        $docs = $item['docs'];
        $semaforo = $item['semaforo'];
    @endphp
    <div class="fiscal-card {{ $semaforo }}">
        <div class="fiscal-header">
            <div>
                <div class="fiscal-name">{{ $prov->nombre ?? $prov->usuario }}</div>
                <div class="fiscal-code">{{ $prov->codigo_compras ?? '—' }}</div>
            </div>
            <span class="fiscal-badge {{ $semaforo }}">
                @if($semaforo === 'verde') Cumple
                @elseif($semaforo === 'amarillo') En revisión
                @elseif($semaforo === 'rojo') No cumple
                @else Sin docs
                @endif
            </span>
        </div>

        <div class="fiscal-docs">
            @foreach($tiposRequeridos as $tipo)
                @php
                    $doc = $docs->get($tipo);
                    $estado = $doc ? $doc->estatus : 'faltante';
                    $label = str_replace('_', ' ', $tipo);
                @endphp
                <div class="doc-chip {{ $estado }}">{{ $label }}</div>
            @endforeach
        </div>

        <div class="fiscal-summary">
            <div class="fiscal-stat"><strong>{{ $item['aprobados'] }}</strong> aprobados</div>
            <div class="fiscal-stat"><strong>{{ $item['pendientes'] }}</strong> pendientes</div>
            <div class="fiscal-stat"><strong>{{ $item['rechazados'] }}</strong> rechazados</div>
        </div>

        <a href="{{ route('empresa.form') }}" class="btn-validar">Validar documentos →</a>
    </div>
@endforeach
</div>

@if(empty($documentosPorProveedor))
    <div style="text-align:center;padding:48px;color:var(--gray-muted);font-size:14px">No hay proveedores activos registrados</div>
@endif

@endsection
