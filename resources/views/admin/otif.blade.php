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

    .otif-wrap{max-width:1140px;margin:0 auto}
    .adm-otif-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:24px;align-items:stretch;margin-bottom:24px}
    .otif-chart-card{background:var(--white);border:1px solid var(--border-light);border-radius:var(--radius-lg);padding:32px;display:flex;flex-direction:column;align-items:center;justify-content:center;box-shadow:var(--shadow-sm);transition:var(--transition);min-width:0}
    .otif-chart-card:hover{border-color:var(--purple-mid);box-shadow:var(--shadow-md)}
    .otif-canvas-wrap{position:relative;width:180px;height:180px;margin-bottom:16px}
    .otif-canvas-wrap canvas{position:absolute;top:0;left:0}
    .otif-center{position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);text-align:center;pointer-events:none}
    .otif-percent{font-size:32px;font-weight:700;line-height:1}
    .otif-chart-label{font-size:14px;color:var(--gray-muted);font-weight:600;margin-top:8px;text-align:center;line-height:1.35;max-width:280px}

    .stats-card{background:var(--white);border:1px solid var(--border-light);border-radius:var(--radius-lg);padding:32px;box-shadow:var(--shadow-sm);display:flex;flex-direction:column;justify-content:center;min-width:0}
    .stat-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}
    .stat{border:1px solid var(--border-light);border-radius:12px;padding:16px;text-align:center}
    .stat-val{font-size:26px;font-weight:800;line-height:1;margin-bottom:6px}
    .stat-label{font-size:10px;color:var(--gray-muted);font-weight:600;text-transform:uppercase;letter-spacing:.3px}

    .otif-legend-row{display:flex;flex-wrap:wrap;gap:12px 20px;justify-content:center;align-items:center;padding:16px 20px;background:var(--white);border:1px solid var(--border-light);border-radius:var(--radius-lg);box-shadow:var(--shadow-sm);margin-bottom:28px;font-size:11px;font-weight:600;color:var(--gray-text)}
    .otif-legend-row span{display:inline-flex;align-items:center;gap:6px}
    .otif-legend-row .dot{width:9px;height:9px;border-radius:50%;flex-shrink:0}

    .table-card{background:var(--white);border:1px solid var(--border-light);border-radius:16px;overflow:hidden;box-shadow:var(--shadow-sm)}
    .table-head{padding:16px 22px;font-size:13px;font-weight:700;color:var(--gray-text);background:var(--gray-soft);border-bottom:1px solid var(--border-light)}
    .tbl{width:100%;border-collapse:collapse}
    .tbl th{font-size:10px;font-weight:700;color:var(--gray-muted);text-transform:uppercase;letter-spacing:.6px;padding:10px 16px;text-align:left;border-bottom:1px solid var(--border-light)}
    .tbl td{padding:11px 16px;font-size:13px;color:var(--gray-text);border-bottom:1px solid var(--border-light)}
    .tbl tr:last-child td{border-bottom:none}
    .tbl tr:hover td{background:var(--purple-subtle)}
    .score-bar{width:60px;height:6px;background:#e5e7eb;border-radius:3px;overflow:hidden;display:inline-block;vertical-align:middle;margin-right:6px}

    @media(max-width:900px){
        .adm-otif-grid{grid-template-columns:1fr}
    }
</style>
@endpush
@section('content')

<div class="otif-wrap anim">
    <div class="adm-otif-grid">
            <div class="otif-chart-card">
                <div class="otif-canvas-wrap">
                    <canvas id="gaugeOT" width="180" height="180"></canvas>
                    <div class="otif-center"><div class="otif-percent" id="adminOtifPctOT"></div></div>
                </div>
                <span class="otif-chart-label">OT — facturas pagadas a tiempo vs. total (proveedores)</span>
            </div>
            <div class="otif-chart-card">
                <div class="otif-canvas-wrap">
                    <canvas id="gaugeIF" width="180" height="180"></canvas>
                    <div class="otif-center"><div class="otif-percent" id="adminOtifPctIF"></div></div>
                </div>
                <span class="otif-chart-label">IF — facturas no canceladas vs. total (proveedores)</span>
            </div>

        <div class="stats-card">
            <div class="stat-grid">
                <div class="stat"><div class="stat-val" style="color:#34c759">{{ $pagadas }}</div><div class="stat-label">Pagadas a tiempo</div></div>
                <div class="stat"><div class="stat-val" style="color:#ff9500">{{ $pendientes }}</div><div class="stat-label">Pendientes</div></div>
                <div class="stat"><div class="stat-val" style="color:#ff3b30">{{ $vencidas }}</div><div class="stat-label">Vencidas</div></div>
                <div class="stat"><div class="stat-val">{{ $total }}</div><div class="stat-label">Total facturas</div></div>
            </div>
        </div>
    </div>

    <div class="otif-legend-row anim" style="animation-delay:.05s">
        <span><span class="dot" style="background:#34c759"></span>Cumplido</span>
        <span><span class="dot" style="background:#ff9500"></span>Faltante menor (valor &gt; 95%)</span>
        <span><span class="dot" style="background:#ff3b30"></span>Faltante mayor (valor ≤ 95%)</span>
    </div>
</div>

@if(count($detalleProveedores))
<div class="table-card anim" style="animation-delay:.08s;max-width:1140px;margin:0 auto">
    <div class="table-head">Detalle OTIF por proveedor</div>
    <table class="tbl">
        <thead><tr><th>Código</th><th>Proveedor</th><th>Facturas</th><th>Pagadas</th><th>OT %</th><th>Score</th></tr></thead>
        <tbody>
        @foreach($detalleProveedores as $dp)
            @php
                $otv = (float) $dp['ot'];
                $g = $otv > 95 ? '#ff9500' : '#ff3b30';
                $tc = $otv <= 95 ? '#ff3b30' : '#34c759';
                $bg = $otv >= 100 ? 'linear-gradient(90deg,#34c759 0%,#34c759 100%)' : ($otv <= 0 ? 'linear-gradient(90deg,'.$g.' 0%,'.$g.' 100%)' : 'linear-gradient(90deg,#34c759 0%,#34c759 '.$otv.'%,'.$g.' '.$otv.'%,'.$g.' 100%)');
            @endphp
            <tr>
                <td style="font-weight:700;color:var(--purple)">{{ $dp['codigo'] ?? '—' }}</td>
                <td style="font-weight:600">{{ $dp['nombre'] }}</td>
                <td>{{ $dp['total'] }}</td>
                <td>{{ $dp['pagadas'] }}</td>
                <td><div class="score-bar" style="background:{{ $bg }}"></div><strong style="color:{{ $tc }}">{{ $dp['ot'] }}%</strong></td>
                <td>{{ number_format($dp['score'], 0) }}%</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endif

@endsection
@push('scripts')
<script src="/js/otif-donut.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    salcomDrawOtifDonut('gaugeOT', {{ $otPercent }}, 'adminOtifPctOT', 180);
    salcomDrawOtifDonut('gaugeIF', {{ $ifPercent }}, 'adminOtifPctIF', 180);
});
</script>
@endpush
