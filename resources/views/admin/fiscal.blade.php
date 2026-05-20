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
    .fiscal-table-card{background:var(--white);border:1px solid var(--border-light);border-radius:14px;overflow:hidden;margin-bottom:24px}
    .fiscal-table-head{padding:16px 22px;border-bottom:1px solid var(--border-light);display:flex;align-items:center;justify-content:space-between}
    .fiscal-table-head h3{font-size:15px;font-weight:700;color:var(--gray-text)}
    .fiscal-table{width:100%;border-collapse:collapse}
    .fiscal-table th{font-size:10px;font-weight:700;color:var(--gray-muted);text-transform:uppercase;letter-spacing:.4px;padding:10px 14px;text-align:left;background:var(--gray-soft);border-bottom:1px solid var(--border-light)}
    .fiscal-table td{padding:12px 14px;font-size:12px;color:var(--gray-text);border-bottom:1px solid var(--border-light)}
    .fiscal-table tr:last-child td{border-bottom:none}
    .fiscal-table tr:hover td{background:var(--purple-subtle)}
    .doc-chip{font-size:10px;font-weight:600;padding:3px 8px;border-radius:6px;text-align:center;display:inline-block}
    .doc-chip.aprobado{background:#ecfdf5;color:#059669}
    .doc-chip.pendiente{background:#fefce8;color:#d97706}
    .doc-chip.rechazado{background:#fef2f2;color:#dc2626}
    .doc-chip.faltante{background:#f3f4f6;color:#9ca3af}
    .badge-estado{font-size:10px;font-weight:700;padding:4px 10px;border-radius:999px}
    .badge-estado.verde{background:#ecfdf5;color:#059669}
    .badge-estado.amarillo{background:#fefce8;color:#d97706}
    .badge-estado.rojo{background:#fef2f2;color:#dc2626}
    .badge-estado.gris{background:#f3f4f6;color:#6b7280}
    .legend{display:flex;gap:16px;margin-bottom:20px;flex-wrap:wrap}
    .legend-item{display:flex;align-items:center;gap:6px;font-size:12px;font-weight:600;color:var(--gray-text)}
    .legend-dot{width:10px;height:10px;border-radius:3px}
</style>
@endpush
@section('content')

<div class="legend">
    <div class="legend-item"><div class="legend-dot" style="background:#059669"></div>Cumple (SAT al día)</div>
    <div class="legend-item"><div class="legend-dot" style="background:#d97706"></div>En revisión</div>
    <div class="legend-item"><div class="legend-dot" style="background:#dc2626"></div>Rechazado / No cumple</div>
    <div class="legend-item"><div class="legend-dot" style="background:#9ca3af"></div>Sin documentos</div>
</div>

<div class="fiscal-table-card">
    <div class="fiscal-table-head">
        <h3>Detalle de proveedores</h3>
    </div>
    <div style="overflow-x:auto;">
    <table class="fiscal-table">
        <thead>
            <tr>
                <th>Proveedor</th>
                <th>Código</th>
                <th>CIF</th>
                <th>Opinión</th>
                <th>Acta</th>
                <th>Rep Legal</th>
                <th>Contribuyente</th>
                <th>Carátula Banco</th>
                <th>Aprobados</th>
                <th>Pendientes</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @foreach($documentosPorProveedor as $item)
            @php
                $prov = $item['proveedor'];
                $docs = $item['docs'];
                $semaforo = $item['semaforo'];
            @endphp
            <tr>
                <td style="font-weight:600;">{{ $prov->nombre ?? $prov->usuario }}</td>
                <td style="font-size:11px;color:var(--gray-muted);">{{ $prov->codigo_compras ?? '—' }}</td>
                @foreach($tiposRequeridos as $tipo)
                    @php
                        $doc = $docs->get($tipo);
                        $estado = $doc ? $doc->estatus : 'faltante';
                    @endphp
                    <td><span class="doc-chip {{ $estado }}">{{ ucfirst($estado) }}</span></td>
                @endforeach
                <td style="font-weight:700;color:#059669;">{{ $item['aprobados'] }}</td>
                <td style="font-weight:700;color:#d97706;">{{ $item['pendientes'] }}</td>
                <td><span class="badge-estado {{ $semaforo }}">
                    @if($semaforo === 'verde') Cumple
                    @elseif($semaforo === 'amarillo') En revisión
                    @elseif($semaforo === 'rojo') No cumple
                    @else Sin docs
                    @endif
                </span></td>
            </tr>
            @endforeach
        </tbody>
    </table>
    </div>
</div>

@if(empty($documentosPorProveedor))
    <div style="text-align:center;padding:48px;color:var(--gray-muted);font-size:14px">No hay proveedores activos registrados</div>
@endif

@endsection
