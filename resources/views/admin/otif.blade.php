@extends('layouts.admin')
@section('title', 'OTIF')
@section('hero')
<div class="hero-band">
    <h1>OTIF — On Time In Full</h1>
    <p>Indicador de entregas a tiempo y completas</p>
</div>
@endsection
@push('styles')
<style>
    @keyframes fadeUp{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:translateY(0)}}
    .anim{animation:fadeUp .4s cubic-bezier(.4,0,.2,1) both}

    .otif-top{display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:28px}
    .gauge-card{background:var(--white);border:1px solid var(--border-light);border-radius:16px;padding:32px;display:flex;flex-direction:column;align-items:center;transition:box-shadow .2s}
    .gauge-card:hover{box-shadow:0 8px 24px rgba(0,0,0,.05)}
    .gauge-card-title{font-size:15px;font-weight:700;color:var(--gray-text);margin-bottom:24px}
    .gauge-wrap{position:relative;width:180px;height:180px}
    .gauge-label{font-size:13px;color:var(--gray-muted);font-weight:600;margin-top:16px}
    .gauge-params{display:flex;gap:14px;margin-top:16px;padding-top:14px;border-top:1px solid var(--border-light);flex-wrap:wrap;justify-content:center}
    .gauge-param{display:flex;align-items:center;gap:6px;font-size:11px;font-weight:600;color:var(--gray-text)}
    .gp-dot{width:10px;height:10px;border-radius:50%;flex-shrink:0}

    .stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:28px}
    .stat-card{background:var(--white);border:1px solid var(--border-light);border-radius:14px;padding:20px;text-align:center;transition:box-shadow .2s}
    .stat-card:hover{box-shadow:0 4px 12px rgba(0,0,0,.04)}
    .stat-val{font-size:28px;font-weight:800;line-height:1;margin-bottom:6px}
    .stat-label{font-size:11px;color:var(--gray-muted);font-weight:600;text-transform:uppercase;letter-spacing:.4px}

    .progress-card{background:var(--white);border:1px solid var(--border-light);border-radius:16px;padding:24px;margin-bottom:28px}
    .progress-title{font-size:13px;font-weight:700;color:var(--gray-text);margin-bottom:14px}
    .progress-row{display:flex;align-items:center;gap:14px;margin-bottom:12px}
    .progress-row:last-child{margin-bottom:0}
    .progress-label{font-size:12px;font-weight:600;color:var(--gray-text);width:100px;flex-shrink:0}
    .progress-bar{flex:1;height:10px;background:#e5e7eb;border-radius:5px;overflow:hidden}
    .progress-fill{height:100%;border-radius:5px;transition:width .8s cubic-bezier(.4,0,.2,1)}
    .progress-val{font-size:12px;font-weight:700;width:45px;text-align:right;flex-shrink:0}

    .table-card{background:var(--white);border:1px solid var(--border-light);border-radius:16px;overflow:hidden}
    .table-head{padding:16px 22px;font-size:13px;font-weight:700;color:var(--gray-text);background:var(--gray-soft);border-bottom:1px solid var(--border-light)}
    .tbl{width:100%;border-collapse:collapse}
    .tbl th{font-size:10px;font-weight:700;color:var(--gray-muted);text-transform:uppercase;letter-spacing:.6px;padding:10px 16px;text-align:left;border-bottom:1px solid var(--border-light)}
    .tbl td{padding:11px 16px;font-size:13px;color:var(--gray-text);border-bottom:1px solid var(--border-light)}
    .tbl tr:last-child td{border-bottom:none}
    .tbl tr:hover td{background:var(--purple-subtle)}
    .badge-estatus{font-size:11px;font-weight:600;padding:4px 12px;border-radius:999px;display:inline-block;text-transform:capitalize}
    .badge-estatus.validacion{background:var(--amber-bg);color:var(--amber)}
    .badge-estatus.procesando{background:var(--blue-bg);color:var(--blue)}
    .badge-estatus.enviado{background:#ede9fe;color:#7c3aed}
    .badge-estatus.entregado{background:var(--green-bg);color:var(--green)}
    .badge-estatus.cancelado{background:var(--red-bg);color:var(--red)}

    @media(max-width:768px){.otif-top{grid-template-columns:1fr}.stats-grid{grid-template-columns:1fr 1fr}.table-card{overflow-x:auto}}
</style>
@endpush
@section('content')

@php
    $otPercent = $total > 0 ? round(($entregados / $total) * 100, 1) : 0;
    $ifPercent = $total > 0 ? round((($total - $cancelados) / $total) * 100, 1) : 0;
    $otColor = $otPercent >= 80 ? '#059669' : ($otPercent >= 50 ? '#d97706' : '#dc2626');
    $ifColor = $ifPercent >= 80 ? '#059669' : ($ifPercent >= 50 ? '#d97706' : '#dc2626');
@endphp

<div class="otif-top anim">
    <div class="gauge-card">
        <div class="gauge-card-title">OT (On Time)</div>
        <div class="gauge-wrap"><canvas id="gaugeOT"></canvas></div>
        <div class="gauge-label">Pedidos entregados a tiempo</div>
        <div class="gauge-params">
            <div class="gauge-param"><span class="gp-dot" style="background:#059669"></span>≥ 80% Óptimo</div>
            <div class="gauge-param"><span class="gp-dot" style="background:#d97706"></span>50–79% Alerta</div>
            <div class="gauge-param"><span class="gp-dot" style="background:#dc2626"></span>< 50% Crítico</div>
        </div>
    </div>
    <div class="gauge-card">
        <div class="gauge-card-title">IF (In Full)</div>
        <div class="gauge-wrap"><canvas id="gaugeIF"></canvas></div>
        <div class="gauge-label">Pedidos entregados completos</div>
        <div class="gauge-params">
            <div class="gauge-param"><span class="gp-dot" style="background:#059669"></span>≥ 80% Óptimo</div>
            <div class="gauge-param"><span class="gp-dot" style="background:#d97706"></span>50–79% Alerta</div>
            <div class="gauge-param"><span class="gp-dot" style="background:#dc2626"></span>< 50% Crítico</div>
        </div>
    </div>
</div>

<div class="stats-grid anim" style="animation-delay:.1s">
    <div class="stat-card"><div class="stat-val" style="color:var(--green)">{{ $entregados }}</div><div class="stat-label">Entregados</div></div>
    <div class="stat-card"><div class="stat-val" style="color:#d97706">{{ $enProceso }}</div><div class="stat-label">En proceso</div></div>
    <div class="stat-card"><div class="stat-val" style="color:var(--red)">{{ $cancelados }}</div><div class="stat-label">Cancelados</div></div>
    <div class="stat-card"><div class="stat-val">{{ $total }}</div><div class="stat-label">Total</div></div>
</div>

<div class="progress-card anim" style="animation-delay:.15s">
    <div class="progress-title">Desglose por estatus</div>
    <div class="progress-row">
        <div class="progress-label">Entregados</div>
        <div class="progress-bar"><div class="progress-fill" style="width:{{ $total > 0 ? round($entregados/$total*100) : 0 }}%;background:#059669"></div></div>
        <div class="progress-val" style="color:#059669">{{ $total > 0 ? round($entregados/$total*100) : 0 }}%</div>
    </div>
    <div class="progress-row">
        <div class="progress-label">En proceso</div>
        <div class="progress-bar"><div class="progress-fill" style="width:{{ $total > 0 ? round($enProceso/$total*100) : 0 }}%;background:#d97706"></div></div>
        <div class="progress-val" style="color:#d97706">{{ $total > 0 ? round($enProceso/$total*100) : 0 }}%</div>
    </div>
    <div class="progress-row">
        <div class="progress-label">Cancelados</div>
        <div class="progress-bar"><div class="progress-fill" style="width:{{ $total > 0 ? round($cancelados/$total*100) : 0 }}%;background:#dc2626"></div></div>
        <div class="progress-val" style="color:#dc2626">{{ $total > 0 ? round($cancelados/$total*100) : 0 }}%</div>
    </div>
</div>

<div class="table-card anim" style="animation-delay:.2s">
    <div class="table-head">Detalle de pedidos</div>
    <table class="tbl">
        <thead><tr><th>Folio</th><th>Cliente</th><th>Total</th><th>Estatus</th><th>Fecha</th></tr></thead>
        <tbody>
        @foreach($pedidos as $p)
        <tr>
            <td style="font-weight:700;color:var(--purple)">{{ $p->folio }}</td>
            <td>{{ Str::limit($p->nombre_cliente, 30) }}</td>
            <td style="font-variant-numeric:tabular-nums">${{ number_format($p->total, 2) }}</td>
            <td><span class="badge-estatus {{ $p->estatus }}">{{ ucfirst($p->estatus) }}</span></td>
            <td style="color:var(--gray-muted)">{{ $p->created_at?->format('d/m/Y') }}</td>
        </tr>
        @endforeach
        </tbody>
    </table>
</div>

@endsection
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script src="/js/chart-config.js"></script>
<script>
function createGauge(canvas, percent, color) {
    let segments, segColors;
    if (percent >= 80) {
        const remaining = 100 - percent;
        segments = [percent, remaining * 0.6, remaining * 0.4];
        segColors = [color, '#d97706', '#dc2626'];
    } else if (percent >= 50) {
        segments = [percent, 100 - percent];
        segColors = [color, '#dc2626'];
    } else {
        segments = [percent, 100 - percent];
        segColors = [color, '#fecaca'];
    }

    new Chart(canvas, {
        type: 'doughnut',
        data: { datasets: [{ data: segments, backgroundColor: segColors, borderWidth: 0, borderRadius: 14 }] },
        options: {
            responsive: true, maintainAspectRatio: true, cutout: '78%',
            plugins: { legend: { display: false }, tooltip: { enabled: false },
                centerText: { text: percent.toFixed(percent % 1 ? 1 : 0) + '%', color: color }
            },
            animation: { animateRotate: true, duration: 1200, easing: 'easeOutQuart' }
        }
    });
}

createGauge(document.getElementById('gaugeOT'), {{ $otPercent }}, '{{ $otColor }}');
createGauge(document.getElementById('gaugeIF'), {{ $ifPercent }}, '{{ $ifColor }}');
</script>
@endpush
