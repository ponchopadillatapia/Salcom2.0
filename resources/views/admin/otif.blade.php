@extends('layouts.admin')
@section('title', 'OTIF Proveedores')
@section('hero')
<div class="hero-band">
    <h1>OTIF — Proveedores</h1>
    <p>On Time In Full — Cumplimiento de proveedores en pagos y entregas</p>
</div>
@endsection
@push('styles')
<style>
    @keyframes fadeUp{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:translateY(0)}}
    .anim{animation:fadeUp .4s cubic-bezier(.4,0,.2,1) both}

    .otif-layout{display:grid;grid-template-columns:320px 1fr;gap:24px;margin-bottom:28px}
    .gauge-card{background:var(--white);border:1px solid var(--border-light);border-radius:16px;padding:32px;display:flex;flex-direction:column;align-items:center;justify-content:center}
    .gauge-card-title{font-size:15px;font-weight:700;color:var(--gray-text);margin-bottom:24px}
    .gauge-wrap{position:relative;width:180px;height:180px;margin-bottom:16px}
    .gauge-label{font-size:13px;color:var(--gray-muted);font-weight:600;margin-top:12px}
    .gauge-params{display:flex;gap:14px;margin-top:16px;padding-top:14px;border-top:1px solid var(--border-light);flex-wrap:wrap;justify-content:center}
    .gauge-param{display:flex;align-items:center;gap:6px;font-size:11px;font-weight:600;color:var(--gray-text)}
    .gp-dot{width:10px;height:10px;border-radius:50%;flex-shrink:0}

    .stats-card{background:var(--white);border:1px solid var(--border-light);border-radius:16px;padding:28px}
    .stat-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px}
    .stat{border:1px solid var(--border-light);border-radius:12px;padding:18px;text-align:center}
    .stat-val{font-size:28px;font-weight:800;line-height:1;margin-bottom:6px}
    .stat-label{font-size:11px;color:var(--gray-muted);font-weight:600;text-transform:uppercase;letter-spacing:.3px}
    .progress-section{padding-top:16px;border-top:1px solid var(--border-light)}
    .progress-label{font-size:12px;color:var(--gray-muted);font-weight:600;margin-bottom:8px;display:flex;justify-content:space-between}
    .progress-bar{width:100%;height:12px;background:#e5e7eb;border-radius:6px;overflow:hidden;margin-bottom:14px}
    .progress-fill{height:100%;border-radius:6px;transition:width .8s cubic-bezier(.4,0,.2,1)}

    .table-card{background:var(--white);border:1px solid var(--border-light);border-radius:16px;overflow:hidden}
    .table-head{padding:16px 22px;font-size:13px;font-weight:700;color:var(--gray-text);background:var(--gray-soft);border-bottom:1px solid var(--border-light)}
    .tbl{width:100%;border-collapse:collapse}
    .tbl th{font-size:10px;font-weight:700;color:var(--gray-muted);text-transform:uppercase;letter-spacing:.6px;padding:10px 16px;text-align:left;border-bottom:1px solid var(--border-light)}
    .tbl td{padding:11px 16px;font-size:13px;color:var(--gray-text);border-bottom:1px solid var(--border-light)}
    .tbl tr:last-child td{border-bottom:none}
    .tbl tr:hover td{background:var(--purple-subtle)}
    .score-bar{width:60px;height:6px;background:#e5e7eb;border-radius:3px;overflow:hidden;display:inline-block;vertical-align:middle;margin-right:6px}
    .score-fill{height:100%;border-radius:3px}

    @media(max-width:900px){.otif-layout{grid-template-columns:1fr}.stat-grid{grid-template-columns:1fr 1fr}}
</style>
@endpush
@section('content')

<div class="otif-layout anim">
    <div class="gauge-card">
        <div class="gauge-card-title">OTIF Proveedores</div>
        <div style="display:flex;gap:24px;align-items:center">
            <div style="text-align:center">
                <div style="width:140px;height:140px"><canvas id="gaugeOT"></canvas></div>
                <div class="gauge-label">OT (On Time)</div>
            </div>
            <div style="text-align:center">
                <div style="width:140px;height:140px"><canvas id="gaugeIF"></canvas></div>
                <div class="gauge-label">IF (In Full)</div>
            </div>
        </div>
        <div class="gauge-params">
            <div class="gauge-param"><div class="gp-dot" style="background:#059669"></div>≥ 80% Óptimo</div>
            <div class="gauge-param"><div class="gp-dot" style="background:#d97706"></div>50–79% Alerta</div>
            <div class="gauge-param"><div class="gp-dot" style="background:#dc2626"></div>&lt; 50% Crítico</div>
        </div>
    </div>

    <div class="stats-card">
        <div class="stat-grid">
            <div class="stat"><div class="stat-val" style="color:#059669">{{ $pagadas }}</div><div class="stat-label">Pagadas a tiempo</div></div>
            <div class="stat"><div class="stat-val" style="color:#d97706">{{ $pendientes }}</div><div class="stat-label">Pendientes</div></div>
            <div class="stat"><div class="stat-val" style="color:#dc2626">{{ $vencidas }}</div><div class="stat-label">Vencidas</div></div>
            <div class="stat"><div class="stat-val">{{ $total }}</div><div class="stat-label">Total facturas</div></div>
        </div>
        <div class="progress-section">
            <div class="progress-label"><span>OT (On Time)</span><span style="font-weight:800;color:{{ $otPercent >= 80 ? '#059669' : ($otPercent >= 50 ? '#d97706' : '#dc2626') }}">{{ $otPercent }}%</span></div>
            <div class="progress-bar"><div class="progress-fill" style="width:{{ $otPercent }}%;background:{{ $otPercent >= 80 ? '#059669' : ($otPercent >= 50 ? '#d97706' : '#dc2626') }}"></div></div>
            <div class="progress-label"><span>IF (In Full)</span><span style="font-weight:800;color:{{ $ifPercent >= 80 ? '#059669' : ($ifPercent >= 50 ? '#d97706' : '#dc2626') }}">{{ $ifPercent }}%</span></div>
            <div class="progress-bar"><div class="progress-fill" style="width:{{ $ifPercent }}%;background:{{ $ifPercent >= 80 ? '#059669' : ($ifPercent >= 50 ? '#d97706' : '#dc2626') }}"></div></div>
        </div>
    </div>
</div>

@if(count($detalleProveedores))
<div class="table-card">
    <div class="table-head">Detalle OTIF por proveedor</div>
    <table class="tbl">
        <thead><tr><th>Código</th><th>Proveedor</th><th>Facturas</th><th>Pagadas</th><th>OT %</th><th>Score</th></tr></thead>
        <tbody>
        @foreach($detalleProveedores as $dp)
            @php $c = $dp['ot'] >= 80 ? '#059669' : ($dp['ot'] >= 50 ? '#d97706' : '#dc2626'); @endphp
            <tr>
                <td style="font-weight:700;color:var(--purple)">{{ $dp['codigo'] ?? '—' }}</td>
                <td style="font-weight:600">{{ $dp['nombre'] }}</td>
                <td>{{ $dp['total'] }}</td>
                <td>{{ $dp['pagadas'] }}</td>
                <td><div class="score-bar"><div class="score-fill" style="width:{{ $dp['ot'] }}%;background:{{ $c }}"></div></div><strong style="color:{{ $c }}">{{ $dp['ot'] }}%</strong></td>
                <td>{{ number_format($dp['score'], 0) }}%</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endif

@endsection
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script src="/js/chart-config.js"></script>
<script>
function createGauge(canvas, percent, color) {
    let segments, segColors;
    if (percent >= 80) {
        const r = 100 - percent;
        segments = [percent, r * 0.6, r * 0.4];
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
            responsive: true, maintainAspectRatio: true, cutout: '76%',
            plugins: { legend: { display: false }, tooltip: { enabled: false },
                centerText: { text: percent.toFixed(percent % 1 ? 1 : 0) + '%', color: color }
            }
        }
    });
}

createGauge(document.getElementById('gaugeOT'), {{ $otPercent }}, '{{ $otPercent >= 80 ? "#059669" : ($otPercent >= 50 ? "#d97706" : "#dc2626") }}');
createGauge(document.getElementById('gaugeIF'), {{ $ifPercent }}, '{{ $ifPercent >= 80 ? "#059669" : ($ifPercent >= 50 ? "#d97706" : "#dc2626") }}');
</script>
@endpush
