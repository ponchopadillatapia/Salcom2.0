@extends('layouts.cliente')
@section('title', 'Dashboard')
@section('hero')
<div class="hero-band"><h1>Dashboard</h1><p>{{ session('cliente_nombre', 'Cliente') }} — {{ now()->format('d/m/Y') }}</p></div>
@endsection
@push('styles')
<style>
    .metrics{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px}
    .metric{background:var(--white);border:1px solid var(--border);border-radius:10px;padding:20px}
    .metric-label{font-size:12px;color:var(--gray-muted);margin-bottom:6px}.metric-val{font-size:24px;font-weight:700;color:var(--gray-text)}.metric-sub{font-size:11px;color:#9ca3af;margin-top:4px}
    .mid-grid{display:grid;grid-template-columns:2fr 1fr;gap:16px;margin-bottom:24px}
    .card{background:var(--white);border:1px solid var(--border);border-radius:10px;overflow:hidden}
    .card-head{padding:14px 20px;border-bottom:1px solid var(--border);font-size:14px;font-weight:600;color:var(--gray-text)}
    .card-body{padding:20px}
    .chart-container{height:200px;display:flex;align-items:flex-end;gap:12px;padding:0 8px}
    .bar-group{flex:1;display:flex;flex-direction:column;align-items:center;gap:4px}
    .bar{width:100%;border-radius:4px 4px 0 0;transition:height .3s;min-height:4px}
    .bar-label{font-size:11px;color:var(--gray-muted)}.bar-val{font-size:11px;font-weight:600;color:var(--gray-text)}
    .recent-item{display:flex;align-items:center;gap:12px;padding:12px 0;border-bottom:1px solid var(--border);font-size:13px}.recent-item:last-child{border-bottom:none}
    .recent-dot{width:8px;height:8px;border-radius:50%;flex-shrink:0}
    .dot-g{background:#059669}.dot-a{background:#d97706}.dot-b{background:#2563eb}
    .recent-text{flex:1;color:var(--gray-text)}.recent-meta{font-size:11px;color:#9ca3af}
    .badge-api{font-size:11px;color:#d97706;font-weight:600;background:#fffbeb;padding:3px 10px;border-radius:999px;display:inline-block;margin-bottom:16px}
    @media(max-width:900px){.metrics{grid-template-columns:1fr 1fr}.mid-grid{grid-template-columns:1fr}}
</style>
@endpush
@section('content')
<span class="badge-api">⚠ Datos de prueba</span>
<div class="metrics">
    <div class="metric"><div class="metric-label">Pedidos este mes</div><div class="metric-val">5</div><div class="metric-sub">Abril 2026</div></div>
    <div class="metric"><div class="metric-label">Total facturado</div><div class="metric-val">$30,618</div><div class="metric-sub">Datos de prueba</div></div>
    <div class="metric"><div class="metric-label">Pedidos pendientes</div><div class="metric-val">3</div><div class="metric-sub">En proceso</div></div>
    <div class="metric"><div class="metric-label">Último pedido</div><div class="metric-val">PED-005</div><div class="metric-sub">09/04/2026</div></div>
</div>
<div class="mid-grid">
    <div class="card">
        <div class="card-head">Pedidos por mes</div>
        <div class="card-body">
            <div class="chart-container">
                @php $meses = [['Nov',2,1200],['Dic',4,3800],['Ene',3,2500],['Feb',5,4200],['Mar',6,5100],['Abr',5,4700]]; $max=6; @endphp
                @foreach($meses as [$m,$c,$t])
                <div class="bar-group">
                    <div class="bar-val">{{ $c }}</div>
                    <div class="bar" style="height:{{ ($c/$max)*160 }}px;background:{{ $m==='Abr'?'#6B3FA0':'#d4c5e8' }}"></div>
                    <div class="bar-label">{{ $m }}</div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-head">Últimos pedidos</div>
        <div class="card-body" style="padding:12px 20px">
            <div class="recent-item"><div class="recent-dot dot-a"></div><div class="recent-text">PED-2026-005 — En validación</div><div class="recent-meta">09/04</div></div>
            <div class="recent-item"><div class="recent-dot dot-b"></div><div class="recent-text">PED-2026-004 — Autorizado</div><div class="recent-meta">07/04</div></div>
            <div class="recent-item"><div class="recent-dot dot-a"></div><div class="recent-text">PED-2026-003 — En producción</div><div class="recent-meta">05/04</div></div>
        </div>
    </div>
</div>

{{-- FORECAST: Productos que más compras --}}
<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:24px">
    <div class="card">
        <div class="card-head">📈 Productos que más compras — Al alza</div>
        <div class="card-body">
            <div style="display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid var(--border);font-size:13px">
                <span style="flex:1;font-weight:600">Resina epóxica industrial</span>
                <div style="width:60px;height:6px;background:#e5e7eb;border-radius:3px;overflow:hidden"><div style="width:92%;height:100%;background:#059669;border-radius:3px"></div></div>
                <span style="font-size:12px;font-weight:700;color:#059669;width:50px;text-align:right">↑ +12%</span>
            </div>
            <div style="display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid var(--border);font-size:13px">
                <span style="flex:1;font-weight:600">Solvente grado técnico</span>
                <div style="width:60px;height:6px;background:#e5e7eb;border-radius:3px;overflow:hidden"><div style="width:78%;height:100%;background:#059669;border-radius:3px"></div></div>
                <span style="font-size:12px;font-weight:700;color:#059669;width:50px;text-align:right">↑ +8%</span>
            </div>
            <div style="display:flex;align-items:center;gap:10px;padding:8px 0;font-size:13px">
                <span style="flex:1;font-weight:600">Pigmento base agua</span>
                <div style="width:60px;height:6px;background:#e5e7eb;border-radius:3px;overflow:hidden"><div style="width:65%;height:100%;background:#059669;border-radius:3px"></div></div>
                <span style="font-size:12px;font-weight:700;color:#059669;width:50px;text-align:right">↑ +5%</span>
            </div>
            <p style="font-size:11px;color:#9ca3af;margin-top:10px">Basado en tu historial de pedidos · Datos de prueba</p>
        </div>
    </div>
    <div class="card">
        <div class="card-head">📉 Productos a la baja</div>
        <div class="card-body">
            <div style="display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid var(--border);font-size:13px">
                <span style="flex:1;font-weight:600">Catalizador rápido</span>
                <div style="width:60px;height:6px;background:#e5e7eb;border-radius:3px;overflow:hidden"><div style="width:40%;height:100%;background:#dc2626;border-radius:3px"></div></div>
                <span style="font-size:12px;font-weight:700;color:#dc2626;width:50px;text-align:right">↓ -5%</span>
            </div>
            <div style="display:flex;align-items:center;gap:10px;padding:8px 0;font-size:13px">
                <span style="flex:1;font-weight:600">Aditivo antioxidante</span>
                <div style="width:60px;height:6px;background:#e5e7eb;border-radius:3px;overflow:hidden"><div style="width:55%;height:100%;background:#9ca3af;border-radius:3px"></div></div>
                <span style="font-size:12px;font-weight:700;color:#9ca3af;width:50px;text-align:right">→ Estable</span>
            </div>
            <p style="font-size:11px;color:#9ca3af;margin-top:10px">Recomendación: revisa tu inventario de estos productos</p>
        </div>
    </div>
</div>
@endsection
