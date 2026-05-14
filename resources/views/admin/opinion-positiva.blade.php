@extends('layouts.admin')
@section('title', 'Opinión Positiva')
@section('hero')
<div class="hero-band">
    <h1>Opinión Positiva SAT</h1>
    <p>Estado de la opinión de cumplimiento de cada proveedor</p>
</div>
@endsection
@push('styles')
<style>
    .kpis{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px}
    .kpi{background:var(--white);border:1px solid var(--border-light);border-radius:14px;padding:20px;text-align:center}
    .kpi-val{font-size:28px;font-weight:800;line-height:1;margin-bottom:6px}
    .kpi-label{font-size:11px;color:var(--gray-muted);font-weight:600;text-transform:uppercase;letter-spacing:.4px}

    .table-card{background:var(--white);border:1px solid var(--border-light);border-radius:14px;overflow:hidden}
    .table-head{padding:16px 22px;font-size:13px;font-weight:700;color:var(--gray-text);background:var(--gray-soft);border-bottom:1px solid var(--border-light)}
    .tbl{width:100%;border-collapse:collapse}
    .tbl th{font-size:10px;font-weight:700;color:var(--gray-muted);text-transform:uppercase;letter-spacing:.6px;padding:11px 16px;text-align:left;border-bottom:1px solid var(--border-light)}
    .tbl td{padding:12px 16px;font-size:13px;color:var(--gray-text);border-bottom:1px solid var(--border-light)}
    .tbl tr:last-child td{border-bottom:none}
    .tbl tr:hover td{background:var(--purple-subtle)}

    .badge-op{font-size:11px;font-weight:700;padding:4px 12px;border-radius:999px;display:inline-block}
    .badge-op.aprobado{background:#ecfdf5;color:#059669}
    .badge-op.pendiente{background:#fefce8;color:#d97706}
    .badge-op.rechazado{background:#fef2f2;color:#dc2626}
    .badge-op.sin_documento{background:#f3f4f6;color:#6b7280}

    .mes-badge{font-size:10px;font-weight:600;padding:3px 8px;border-radius:6px;background:var(--purple-light);color:var(--purple)}

    @media(max-width:768px){.kpis{grid-template-columns:1fr 1fr}.table-card{overflow-x:auto}}
</style>
@endpush
@section('content')

<div class="kpis">
    <div class="kpi"><div class="kpi-val" style="color:#059669">{{ $aprobados }}</div><div class="kpi-label">Opinión positiva</div></div>
    <div class="kpi"><div class="kpi-val" style="color:#d97706">{{ $pendientes }}</div><div class="kpi-label">En revisión</div></div>
    <div class="kpi"><div class="kpi-val" style="color:#dc2626">{{ $rechazados }}</div><div class="kpi-label">Rechazada</div></div>
    <div class="kpi"><div class="kpi-val" style="color:#6b7280">{{ $sinDoc }}</div><div class="kpi-label">Sin documento</div></div>
</div>

<div class="table-card">
    <div class="table-head">Opinión de Cumplimiento SAT por Proveedor</div>
    <table class="tbl">
        <thead>
            <tr>
                <th>Código</th>
                <th>Proveedor</th>
                <th>Estado</th>
                <th>Fecha revisión</th>
                <th>Notas</th>
            </tr>
        </thead>
        <tbody>
        @foreach($opiniones as $op)
            @php
                $prov = $op['proveedor'];
                $doc = $op['documento'];
                $est = $op['estatus'];
                $labels = ['aprobado' => 'Positiva', 'pendiente' => 'En revisión', 'rechazado' => 'Negativa/Rechazada', 'sin_documento' => 'Sin documento'];
            @endphp
            <tr>
                <td style="font-weight:700;color:var(--purple)">{{ $prov->codigo_compras ?? '—' }}</td>
                <td style="font-weight:600">{{ $prov->nombre ?? $prov->usuario }}</td>
                <td><span class="badge-op {{ $est }}">{{ $labels[$est] ?? $est }}</span></td>
                <td style="color:var(--gray-muted)">{{ $doc && $doc->revisado_at ? $doc->revisado_at->format('d/m/Y') : '—' }}</td>
                <td style="color:var(--gray-muted);max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $doc->notas_revision ?? '—' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

@endsection
