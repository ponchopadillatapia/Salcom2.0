@extends('layouts.admin')
@section('title', 'Reembolsos de Viaje')

@push('styles')
<style>
    .rv-kpis { display: grid; grid-template-columns: repeat(5, 1fr); gap: 12px; margin-bottom: 24px; }
    .rv-kpi { background: var(--white); border: 1px solid var(--border-light); border-radius: 12px; padding: 16px; text-align: center; }
    .rv-kpi .num { font-size: 22px; font-weight: 700; color: var(--gray-text); }
    .rv-kpi .lbl { font-size: 11px; color: var(--gray-muted); text-transform: uppercase; letter-spacing: .5px; margin-top: 4px; }
    .rv-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; flex-wrap: wrap; gap: 12px; }
    .rv-header h2 { font-size: 18px; font-weight: 700; color: var(--gray-text); margin: 0; }
    .btn-nuevo { padding: 9px 18px; background: var(--purple); color: #fff; border: none; border-radius: 10px; font-size: 13px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
    .btn-nuevo:hover { background: var(--purple-dark); }
    .rv-table { width: 100%; border-collapse: collapse; background: var(--white); border: 1px solid var(--border-light); border-radius: 12px; overflow: hidden; }
    .rv-table th { background: var(--gray-soft); font-size: 11px; font-weight: 700; color: var(--gray-muted); text-transform: uppercase; padding: 10px 14px; text-align: left; }
    .rv-table td { padding: 12px 14px; font-size: 13px; border-top: 1px solid var(--border-light); }
    .rv-table tr:hover td { background: var(--purple-subtle); }
    .badge-est { padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; }
    .badge-borrador { background: #f3f4f6; color: #6b7280; }
    .badge-pendiente { background: #fef3c7; color: #92400e; }
    .badge-aprobado { background: #dcfce7; color: #166534; }
    .badge-rechazado { background: #fee2e2; color: #991b1b; }
    .badge-pagado { background: #dbeafe; color: #1e40af; }
    .rv-empty { text-align: center; padding: 48px; color: var(--gray-muted); }
    @media(max-width:768px) { .rv-kpis { grid-template-columns: repeat(2, 1fr); } }
</style>
@endpush

@section('content')
@if(session('mensaje'))
<div style="background:#ecfdf5;border:1px solid #059669;border-radius:10px;padding:12px 16px;margin-bottom:16px;color:#059669;font-size:13px;font-weight:600;">{{ session('mensaje') }}</div>
@endif

<div class="rv-kpis">
    <div class="rv-kpi"><div class="num">{{ $kpis['borradores'] }}</div><div class="lbl">Borradores</div></div>
    <div class="rv-kpi"><div class="num">{{ $kpis['enviados'] }}</div><div class="lbl">Enviados</div></div>
    <div class="rv-kpi"><div class="num">{{ $kpis['aprobados'] }}</div><div class="lbl">Aprobados</div></div>
    <div class="rv-kpi"><div class="num">{{ $kpis['reembolsados'] }}</div><div class="lbl">Reembolsados</div></div>
    <div class="rv-kpi"><div class="num">${{ number_format($kpis['total_pendiente'], 0) }}</div><div class="lbl">Pendiente MXN</div></div>
</div>

<div class="rv-header">
    <h2>Reembolsos de Viaje</h2>
    <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
        <form method="GET" action="{{ route('admin.reembolsos-viaje') }}" style="display:flex;gap:8px;align-items:center;">
            <input type="text" name="busqueda" value="{{ request('busqueda') }}" placeholder="Buscar por Nº Empleado o nombre" style="padding:7px 12px;border:1.5px solid var(--border);border-radius:8px;font-size:12px;width:200px;">
            <button type="submit" style="padding:7px 14px;background:var(--purple);color:#fff;border:none;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;">Filtrar</button>
            @if(request('busqueda'))
            <a href="{{ route('admin.reembolsos-viaje') }}" style="font-size:11px;color:var(--gray-muted);text-decoration:none;">✕ Limpiar</a>
            @endif
        </form>
        @if($solicitudes->total() > 0)
        <a href="{{ route('admin.reembolsos-viaje.excel') }}" style="padding:9px 16px;background:#dcfce7;border:1px solid #86efac;border-radius:10px;font-size:12px;font-weight:600;color:#166534;text-decoration:none;display:inline-flex;align-items:center;gap:6px;">Exportar Excel</a>
        @endif
        <a href="{{ route('admin.reembolsos-viaje.crear') }}" class="btn-nuevo">+ Nueva solicitud</a>
    </div>
</div>

@if(request('busqueda'))
<p style="font-size:12px;color:var(--purple);margin-bottom:12px;font-weight:600;">Mostrando registros del empleado: {{ request('busqueda') }}</p>
@endif

@if($solicitudes->isEmpty())
<div class="rv-empty">
    <p>No hay solicitudes de reembolso de viaje.</p>
</div>
@else
<table class="rv-table">
    <thead>
        <tr>
            <th>#</th>
            <th>Empleado</th>
            <th>Destino</th>
            <th>Total local</th>
            <th>Total MXN</th>
            <th>Estatus</th>
            <th>Fecha</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        @foreach($solicitudes as $s)
        <tr>
            <td>{{ $s->id }}</td>
            <td><strong>{{ $s->nombre_empleado }}</strong><br><span style="font-size:11px;color:var(--gray-muted);">{{ $s->codigo_empleado }}</span></td>
            <td>{{ $s->pais_destino }} <span style="font-size:11px;color:var(--gray-muted);">({{ $s->moneda_destino }})</span></td>
            <td>{{ number_format($s->total_moneda_local, 2) }} {{ $s->moneda_destino }}</td>
            <td><strong>${{ number_format($s->total_moneda_base, 2) }} MXN</strong></td>
            <td>
                <span class="badge-est {{ $s->badgeClass() }}">{{ $s->estatusLabel() }}</span>
                @if($s->facturasVencidas())
                <br><span style="display:inline-block;margin-top:4px;padding:2px 8px;border-radius:12px;font-size:10px;font-weight:700;background:#fee2e2;color:#991b1b;">Bloqueado (plazo vencido)</span>
                @elseif($s->diasParaSubirFacturas() !== null && $s->diasParaSubirFacturas() >= 0 && empty($s->archivo_comprobantes))
                <br><span style="display:inline-block;margin-top:4px;padding:2px 8px;border-radius:12px;font-size:10px;font-weight:700;background:#fef3c7;color:#92400e;">{{ $s->diasParaSubirFacturas() }}d para facturas</span>
                @endif
            </td>
            <td>{{ $s->created_at->format('d/m/Y') }}</td>
            <td><a href="{{ route('admin.reembolsos-viaje.ver', $s) }}" style="color:var(--purple);font-weight:600;font-size:12px;">Ver</a></td>
        </tr>
        @endforeach
    </tbody>
</table>
<div style="margin-top:16px;">{{ $solicitudes->links() }}</div>
@endif
@endsection
