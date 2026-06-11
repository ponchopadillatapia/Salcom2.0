@extends('layouts.admin')
@section('title', 'OTIF Proveedores')
@section('hero')
<div class="hero-band">
    <h1>OTIF — Proveedores</h1>
    <p>On Time In Full — Cumplimiento de pagos y entregas de facturas de proveedor</p>
</div>
@endsection
@push('styles')
<style>
    @keyframes fadeUp{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:translateY(0)}}
    .anim{animation:fadeUp .4s cubic-bezier(.4,0,.2,1) both}

    .otif-wrap{max-width:1140px;margin:0 auto}
    .otif-summary{background:var(--white);border:1px solid var(--border-light);border-radius:var(--radius-lg);padding:24px 28px;margin-bottom:24px;display:flex;align-items:center;gap:28px;flex-wrap:wrap;box-shadow:var(--shadow-sm)}
    .otif-summary-score{text-align:center;min-width:110px}
    .otif-summary-pct{font-size:48px;font-weight:800;line-height:1}
    .otif-summary-label{font-size:12px;color:var(--gray-muted);margin-top:6px}
    .otif-summary-metrics{flex:1;display:flex;gap:28px;flex-wrap:wrap}
    .otif-metric-label{font-size:12px;color:var(--gray-muted);margin-bottom:4px}
    .otif-metric-val{font-size:24px;font-weight:700;display:flex;align-items:center;gap:8px}
    .otif-summary-badge{padding:10px 16px;border-radius:10px;font-size:12px;font-weight:600;line-height:1.4}

    .adm-otif-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:24px;align-items:stretch;margin-bottom:24px}
    .otif-chart-card{background:var(--white);border:1px solid var(--border-light);border-radius:var(--radius-lg);padding:32px;display:flex;flex-direction:column;align-items:center;justify-content:center;box-shadow:var(--shadow-sm);transition:var(--transition);min-width:0}
    .otif-chart-card:hover{border-color:var(--purple-mid);box-shadow:var(--shadow-md)}
    .otif-canvas-wrap{position:relative;width:180px;height:180px;margin-bottom:16px}
    .otif-canvas-wrap canvas{position:absolute;top:0;left:0}
    .otif-center{position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);text-align:center;pointer-events:none}
    .otif-percent{font-size:32px;font-weight:700;line-height:1}
    .otif-chart-label{font-size:14px;color:var(--gray-muted);font-weight:600;margin-top:8px;text-align:center;line-height:1.35;max-width:280px}
    .otif-trend{font-size:12px;font-weight:700;margin-top:6px}

    .stats-card{background:var(--white);border:1px solid var(--border-light);border-radius:var(--radius-lg);padding:28px 32px;box-shadow:var(--shadow-sm);display:flex;flex-direction:column;justify-content:center;min-width:0}
    .stats-card-title{font-size:11px;font-weight:700;color:var(--gray-muted);text-transform:uppercase;letter-spacing:.4px;margin-bottom:14px}
    .stat-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
    .stat{border:1px solid var(--border-light);border-radius:12px;padding:14px;text-align:center;cursor:pointer;transition:all .15s}
    .stat:hover{border-color:var(--purple);box-shadow:0 2px 8px rgba(107,63,160,.12);transform:translateY(-1px)}
    .stat-val{font-size:24px;font-weight:800;line-height:1;margin-bottom:6px}
    .stat-label{font-size:10px;color:var(--gray-muted);font-weight:600;text-transform:uppercase;letter-spacing:.3px}

    .otif-legend-row{display:flex;flex-wrap:wrap;gap:12px 20px;justify-content:center;align-items:center;padding:16px 20px;background:var(--white);border:1px solid var(--border-light);border-radius:var(--radius-lg);box-shadow:var(--shadow-sm);margin-bottom:28px;font-size:11px;font-weight:600;color:var(--gray-text)}
    .otif-legend-row span{display:inline-flex;align-items:center;gap:6px}
    .otif-legend-row .dot{width:9px;height:9px;border-radius:50%;flex-shrink:0}

    .otif-section{background:var(--white);border:1px solid var(--border-light);border-radius:16px;overflow:hidden;box-shadow:var(--shadow-sm);margin-bottom:24px}
    .otif-section-head{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;padding:16px 22px;background:var(--gray-soft);border-bottom:1px solid var(--border-light)}
    .otif-section-head h4{font-size:14px;font-weight:700;color:var(--gray-text);margin:0}
    .otif-section-meta{font-size:12px;color:var(--gray-muted)}
    .otif-toolbar{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
    .otif-search{border:1.5px solid var(--border-light);border-radius:8px;padding:8px 12px;font-size:13px;font-family:inherit;color:var(--gray-text);outline:none;min-width:200px}
    .otif-search:focus{border-color:var(--purple);box-shadow:0 0 0 3px rgba(107,63,160,.1)}
    .btn-export{display:inline-flex;align-items:center;gap:6px;padding:8px 16px;font-size:12px;font-weight:600;color:var(--green);background:var(--green-bg);border:1px solid var(--green);border-radius:8px;cursor:pointer;font-family:inherit;transition:var(--transition)}
    .btn-export:hover{background:var(--green);color:#fff}

    .tbl{width:100%;border-collapse:collapse}
    .tbl th{font-size:10px;font-weight:700;color:var(--gray-muted);text-transform:uppercase;letter-spacing:.6px;padding:10px 16px;text-align:left;border-bottom:1px solid var(--border-light);background:var(--white)}
    .tbl td{padding:11px 16px;font-size:13px;color:var(--gray-text);border-bottom:1px solid var(--border-light)}
    .tbl tr:last-child td{border-bottom:none}
    .tbl tbody tr:hover td{background:var(--purple-subtle)}
    .tbl-wrap{overflow-x:auto}

    .score-bar{width:72px;height:7px;background:#e5e7eb;border-radius:4px;overflow:hidden;display:inline-block;vertical-align:middle;margin-right:8px}
    .score-fill{height:100%;border-radius:4px}
    .score-high .score-fill{background:var(--green)}
    .score-mid .score-fill{background:var(--amber)}
    .score-low .score-fill{background:var(--red)}
    .pct-val{display:inline-flex;align-items:center;gap:6px;white-space:nowrap}
    .pct-cell{display:inline-flex;align-items:center;gap:6px}

    .badge-ok{font-size:11px;font-weight:600;padding:3px 10px;border-radius:999px;background:var(--green-bg);color:var(--green)}
    .badge-warn{font-size:11px;font-weight:600;padding:3px 10px;border-radius:999px;background:var(--amber-bg);color:var(--amber)}
    .badge-late{font-size:11px;font-weight:600;padding:3px 10px;border-radius:999px;background:var(--red-bg);color:var(--red)}
    .badge-vencida{font-size:10px;font-weight:700;padding:2px 8px;border-radius:999px;background:var(--red-bg);color:var(--red)}

    .empty-state{text-align:center;padding:40px 20px;color:var(--gray-muted);font-size:14px}

    @media(max-width:900px){
        .adm-otif-grid{grid-template-columns:1fr}
        .otif-summary{flex-direction:column;align-items:flex-start}
    }
</style>
@endpush
@section('content')

@php
    $scoreColor = $scoreGeneral >= 80 ? 'var(--green)' : ($scoreGeneral >= 60 ? 'var(--amber)' : 'var(--red)');
    $scoreBg = $scoreGeneral >= 80 ? 'var(--green-bg)' : ($scoreGeneral >= 60 ? 'var(--amber-bg)' : 'var(--red-bg)');
    $scoreMsg = $scoreGeneral >= 80 ? 'Buen cumplimiento general' : ($scoreGeneral >= 60 ? 'Cumplimiento aceptable — revisar pendientes' : 'Bajo cumplimiento — requiere atención');
@endphp

<div class="otif-wrap anim">
    <div class="otif-summary">
        <div class="otif-summary-score">
            <div class="otif-summary-pct" style="color:{{ $scoreColor }}">{{ number_format($scoreGeneral, 1) }}%</div>
            <div class="otif-summary-label">Score OTIF global</div>
        </div>
        <div class="otif-summary-metrics">
            <div>
                <div class="otif-metric-label">Puntualidad (OT)</div>
                <div class="otif-metric-val" style="color:{{ $otPercent >= 80 ? 'var(--green)' : ($otPercent >= 60 ? 'var(--amber)' : 'var(--red)') }}">
                    {{ number_format($otPercent, 1) }}%
                    @include('partials.trend-arrow', ['value' => $trendOt, 'size' => '12'])
                </div>
            </div>
            <div>
                <div class="otif-metric-label">Entrega (IF)</div>
                <div class="otif-metric-val" style="color:{{ $ifPercent >= 80 ? 'var(--green)' : ($ifPercent >= 60 ? 'var(--amber)' : 'var(--red)') }}">
                    {{ number_format($ifPercent, 1) }}%
                    @include('partials.trend-arrow', ['value' => $trendIf, 'size' => '12'])
                </div>
            </div>
            <div>
                <div class="otif-metric-label">Proveedores con facturas</div>
                <div class="otif-metric-val" style="font-size:20px;color:var(--gray-text)">{{ $proveedoresConFacturas }} <span style="font-size:13px;font-weight:500;color:var(--gray-muted)">/ {{ $proveedoresActivos }} activos</span></div>
                <a href="{{ route('admin.proveedores') }}" style="font-size:12px;font-weight:600;color:var(--purple);text-decoration:none;display:inline-block;margin-top:6px;padding:5px 12px;background:var(--purple-subtle);border-radius:6px;transition:background .15s;">Ver proveedores →</a>
            </div>
        </div>
        <div class="otif-summary-badge" style="background:{{ $scoreBg }};color:{{ $scoreColor }}">{{ $scoreMsg }}</div>
    </div>

    <div class="adm-otif-grid" style="grid-template-columns:1fr 1fr;">
        <div class="otif-chart-card">
            <div class="otif-canvas-wrap">
                <canvas id="gaugeOT" width="180" height="180"></canvas>
                <div class="otif-center"><div class="otif-percent" id="adminOtifPctOT"></div></div>
            </div>
            <span class="otif-chart-label">OT — pagos puntuales vs. fecha de vencimiento</span>
            <div class="otif-trend">@include('partials.trend-arrow', ['value' => $trendOt, 'size' => '12']) <span style="color:var(--gray-muted);font-weight:500">vs. trim. anterior</span></div>
        </div>
        <div class="otif-chart-card">
            <div class="otif-canvas-wrap">
                <canvas id="gaugeIF" width="180" height="180"></canvas>
                <div class="otif-center"><div class="otif-percent" id="adminOtifPctIF"></div></div>
            </div>
            <span class="otif-chart-label">IF — facturas no canceladas vs. total emitidas</span>
            <div class="otif-trend">@include('partials.trend-arrow', ['value' => $trendIf, 'size' => '12']) <span style="color:var(--gray-muted);font-weight:500">vs. trim. anterior</span></div>
        </div>
    </div>

    <div class="otif-legend-row anim" style="animation-delay:.05s">
        <span><span class="dot" style="background:#ff3b30"></span>Faltante mayor (valor ≤ 95%)</span>
        <span><span class="dot" style="background:#ff9500"></span>Faltante menor (valor &gt; 95%)</span>
        <span><span class="dot" style="background:#34c759"></span>Cumplido</span>
    </div>
</div>

{{-- Resumen de facturas: 4 cuadros en fila tipo inventario + tabla debajo --}}
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;max-width:1140px;margin:0 auto 16px;" class="anim" style="animation-delay:.06s">
    <div class="stat" style="cursor:pointer;position:relative;overflow:hidden;background:var(--white);border:1px solid var(--border-light);border-radius:14px;padding:22px;text-align:center;" onclick="filtrarFacturasOtif('vencida', this)">
        <div style="position:absolute;top:0;left:0;right:0;height:4px;background:var(--red);"></div>
        <div class="stat-val" style="color:#ff3b30;font-size:30px;">{{ $vencidas }}</div>
        <div class="stat-label">Vencidas</div>
    </div>
    <div class="stat" style="cursor:pointer;position:relative;overflow:hidden;background:var(--white);border:1px solid var(--border-light);border-radius:14px;padding:22px;text-align:center;" onclick="filtrarFacturasOtif('pendiente', this)">
        <div style="position:absolute;top:0;left:0;right:0;height:4px;background:var(--amber);"></div>
        <div class="stat-val" style="color:#ff9500;font-size:30px;">{{ $pendientes }}</div>
        <div class="stat-label">Pendientes</div>
    </div>
    <div class="stat" style="cursor:pointer;position:relative;overflow:hidden;background:var(--white);border:1px solid var(--border-light);border-radius:14px;padding:22px;text-align:center;" onclick="filtrarFacturasOtif('pagada', this)">
        <div style="position:absolute;top:0;left:0;right:0;height:4px;background:var(--green);"></div>
        <div class="stat-val" style="color:#34c759;font-size:30px;">{{ $pagadas }}</div>
        <div class="stat-label">Pagadas</div>
    </div>
    <div class="stat" style="cursor:pointer;position:relative;overflow:hidden;background:var(--white);border:1px solid var(--border-light);border-radius:14px;padding:22px;text-align:center;" onclick="filtrarFacturasOtif('cancelada', this)">
        <div style="position:absolute;top:0;left:0;right:0;height:4px;background:var(--gray-muted);"></div>
        <div class="stat-val" style="color:var(--gray-muted);font-size:30px;">{{ $canceladas }}</div>
        <div class="stat-label">Canceladas</div>
    </div>
</div>

{{-- Tabla de TODAS las facturas (se filtra con los 4 cuadros) --}}
<div class="otif-section anim" style="animation-delay:.06s;max-width:1140px;margin:0 auto 24px" id="seccionTodasFacturas">
    <div class="otif-section-head">
        <div>
            <h4 id="tituloFacturasFiltro">Todas las facturas</h4>
            <div class="otif-section-meta" id="metaFacturasFiltro">{{ $facturasProveedor->count() }} facturas registradas</div>
        </div>
        <button type="button" class="btn-export" onclick="exportOtifTable('tableTodasFacturas', 'OTIF_Facturas')">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Exportar Excel
        </button>
    </div>
    <div class="tbl-wrap">
        <table class="tbl" id="tableTodasFacturas">
            <thead>
                <tr>
                    <th>Folio CFDI</th>
                    <th>Proveedor</th>
                    <th>Código</th>
                    <th>Total</th>
                    <th>Vencimiento</th>
                    <th>Estatus</th>
                </tr>
            </thead>
            <tbody>
            @php
                $ordenEstatus = ['pendiente' => 1, 'pagada' => 2, 'cancelada' => 3];
                $facturasOrdenadas = $facturasProveedor->sortBy(function($f) use ($ordenEstatus) {
                    $esVencida = ($f->estatus === 'pendiente' && $f->fecha_vencimiento && $f->fecha_vencimiento->isPast());
                    if ($esVencida) return 0;
                    return $ordenEstatus[$f->estatus] ?? 4;
                });
            @endphp
            @foreach($facturasOrdenadas as $f)
                @php
                    $esVencida = ($f->estatus === 'pendiente' && $f->fecha_vencimiento && $f->fecha_vencimiento->isPast());
                    $estatusMostrar = $esVencida ? 'vencida' : $f->estatus;
                    $provF = \App\Models\ProveedorUser::where('codigo_compras', $f->codigo_proveedor)->first();
                    $nombreProvF = $provF->nombre ?? $provF->usuario ?? $f->codigo_proveedor ?? '—';
                @endphp
                <tr data-estatus="{{ $estatusMostrar }}">
                    <td style="font-weight:600;color:var(--purple)">{{ $f->folio_cfdi ?? '—' }}</td>
                    <td>{{ $nombreProvF }}</td>
                    <td>{{ $f->codigo_proveedor ?? '—' }}</td>
                    <td style="font-variant-numeric:tabular-nums">${{ number_format($f->total, 2) }}</td>
                    <td>{{ $f->fecha_vencimiento ? $f->fecha_vencimiento->format('d/m/Y') : '—' }}</td>
                    <td>
                        @if($estatusMostrar === 'vencida')
                            <span class="badge-late">Vencida</span>
                        @elseif($estatusMostrar === 'pendiente')
                            <span class="badge-warn">Pendiente</span>
                        @elseif($estatusMostrar === 'pagada')
                            <span class="badge-ok">Pagada</span>
                        @else
                            <span style="font-size:11px;font-weight:600;padding:3px 10px;border-radius:999px;background:var(--gray-soft);color:var(--gray-muted)">Cancelada</span>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="otif-section anim" style="animation-delay:.08s;max-width:1140px;margin:0 auto 24px">
    <div class="otif-section-head">
        <div>
            <h4>Detalle OTIF por proveedor</h4>
            <div class="otif-section-meta">{{ count($detalleProveedores) }} proveedor{{ count($detalleProveedores) !== 1 ? 'es' : '' }} con facturas registradas</div>
        </div>
        <div class="otif-toolbar">
            <input type="search" class="otif-search" id="otifSearch" placeholder="Buscar código o nombre…" aria-label="Buscar proveedor">
            <button type="button" class="btn-export" onclick="exportOtifTable('tableProveedores', 'OTIF_Admin_Proveedores')">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                Exportar Excel
            </button>
        </div>
    </div>
    @if(count($detalleProveedores))
    <div class="tbl-wrap">
        <table class="tbl" id="tableProveedores">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Proveedor</th>
                    <th>Facturas</th>
                    <th>Pagadas</th>
                    <th>Pendientes</th>
                    <th>OT %</th>
                    <th>IF %</th>
                    <th>Score</th>
                </tr>
            </thead>
            <tbody>
            @foreach($detalleProveedores as $dp)
                @php
                    $otv = (float) $dp['ot'];
                    $ifv = (float) $dp['if'];
                    $otColor = $otv >= 80 ? 'var(--green)' : ($otv >= 60 ? 'var(--amber)' : 'var(--red)');
                    $ifColor = $ifv >= 80 ? 'var(--green)' : ($ifv >= 60 ? 'var(--amber)' : 'var(--red)');
                @endphp
                <tr data-search="{{ strtolower(($dp['codigo'] ?? '').' '.($dp['nombre'] ?? '')) }}">
                    <td style="font-weight:700;color:var(--purple)">{{ $dp['codigo'] ?? '—' }}</td>
                    <td style="font-weight:600">{{ $dp['nombre'] }}</td>
                    <td>{{ $dp['total'] }}</td>
                    <td>{{ $dp['pagadas'] }}</td>
                    <td>{{ $dp['pendientes'] }}</td>
                    <td>
                        <span class="pct-val" style="color:{{ $otColor }};font-weight:700">
                            {{ number_format($otv, 1) }}%
                            @include('partials.trend-arrow', ['value' => $dp['trend_ot'] ?? 0, 'size' => '11'])
                        </span>
                    </td>
                    <td>
                        <span class="pct-val" style="color:{{ $ifColor }};font-weight:700">
                            {{ number_format($ifv, 1) }}%
                            @include('partials.trend-arrow', ['value' => $dp['trend_if'] ?? 0, 'size' => '11'])
                        </span>
                    </td>
                    <td>
                        <div class="pct-cell">
                            <div class="score-bar {{ $dp['score_class'] ?? 'score-low' }}"><div class="score-fill" style="width:{{ min(100, $dp['score']) }}%"></div></div>
                            <span class="pct-val"><strong>{{ number_format($dp['score'], 0) }}%</strong>@include('partials.trend-arrow', ['value' => $dp['trend_score'] ?? 0, 'size' => '11'])</span>
                        </div>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    @else
    <div class="empty-state">No hay proveedores activos con facturas registradas.</div>
    @endif
</div>

@if($facturasVencidas->count())
<div class="otif-section anim" style="animation-delay:.1s;max-width:1140px;margin:0 auto">
    <div class="otif-section-head">
        <div>
            <h4>Facturas vencidas pendientes de pago</h4>
            <div class="otif-section-meta">Mostrando las {{ $facturasVencidas->count() }} más antiguas · <a href="{{ route('admin.proveedores', ['tab' => 'facturas']) }}" style="color:var(--purple);font-weight:600;text-decoration:none">Ver en Proveedores →</a></div>
        </div>
        <button type="button" class="btn-export" onclick="exportOtifTable('tableVencidas', 'OTIF_Admin_Facturas_Vencidas')">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Exportar Excel
        </button>
    </div>
    <div class="tbl-wrap">
        <table class="tbl" id="tableVencidas">
            <thead>
                <tr>
                    <th>Folio CFDI</th>
                    <th>Proveedor</th>
                    <th>Código</th>
                    <th>Total</th>
                    <th>Vencimiento</th>
                    <th>Días vencida</th>
                    <th>Estatus</th>
                </tr>
            </thead>
            <tbody>
            @foreach($facturasVencidas as $fv)
                @php
                    $diasVencida = $fv->fecha_vencimiento ? (int) $fv->fecha_vencimiento->diffInDays(now()) : 0;
                    $nombreProv = $fv->proveedor->nombre ?? $fv->proveedor->usuario ?? '—';
                @endphp
                <tr>
                    <td style="font-weight:600;color:var(--purple)">{{ $fv->folio_cfdi ?? '—' }}</td>
                    <td>{{ $nombreProv }}</td>
                    <td>{{ $fv->codigo_proveedor ?? '—' }}</td>
                    <td style="font-variant-numeric:tabular-nums">${{ number_format($fv->total, 2) }}</td>
                    <td>{{ $fv->fecha_vencimiento ? $fv->fecha_vencimiento->format('d/m/Y') : '—' }}</td>
                    <td style="color:var(--red);font-weight:700">{{ $diasVencida }} día{{ $diasVencida === 1 ? '' : 's' }}</td>
                    <td><span class="badge-vencida">Vencida</span></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

@endsection
@push('scripts')
<script src="/js/otif-donut.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    salcomDrawOtifDonut('gaugeOT', {{ $otPercent }}, 'adminOtifPctOT', 180);
    salcomDrawOtifDonut('gaugeIF', {{ $ifPercent }}, 'adminOtifPctIF', 180);

    var search = document.getElementById('otifSearch');
    if (search) {
        search.addEventListener('input', function () {
            var q = this.value.toLowerCase().trim();
            document.querySelectorAll('#tableProveedores tbody tr').forEach(function (row) {
                var text = row.getAttribute('data-search') || '';
                row.style.display = !q || text.indexOf(q) !== -1 ? '' : 'none';
            });
        });
    }
});

function exportOtifTable(tableId, filename) {
    var table = document.getElementById(tableId);
    if (!table) return;
    var csv = [];
    table.querySelectorAll('tr').forEach(function (row) {
        if (row.style.display === 'none') return;
        var cols = row.querySelectorAll('th, td');
        var rowData = [];
        cols.forEach(function (col) {
            var text = col.innerText.replace(/"/g, '""').trim();
            rowData.push('"' + text + '"');
        });
        csv.push(rowData.join(','));
    });
    var blob = new Blob(['\uFEFF' + csv.join('\n')], { type: 'text/csv;charset=utf-8;' });
    var link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = filename + '_' + new Date().toISOString().slice(0, 10) + '.csv';
    link.click();
}

var filtroActualFacturas = null;
function filtrarFacturasOtif(estatus, card) {
    var tabla = document.getElementById('tableTodasFacturas');
    var filas = tabla.querySelectorAll('tbody tr');
    var titulo = document.getElementById('tituloFacturasFiltro');
    var meta = document.getElementById('metaFacturasFiltro');
    var labels = { pagada: 'Facturas pagadas', pendiente: 'Facturas pendientes', vencida: 'Facturas vencidas', cancelada: 'Facturas canceladas', todas: 'Todas las facturas' };

    // Quitar estilo activo de todos los stats
    document.querySelectorAll('.stat').forEach(function(s) { s.style.boxShadow = ''; s.style.border = ''; });

    if (filtroActualFacturas === estatus || estatus === 'todas') {
        filtroActualFacturas = null;
        filas.forEach(function(f) { f.style.display = ''; });
        titulo.textContent = 'Todas las facturas';
        meta.textContent = filas.length + ' facturas registradas';
        // Scroll a la tabla
        document.getElementById('seccionTodasFacturas').scrollIntoView({ behavior: 'smooth', block: 'start' });
        return;
    }

    filtroActualFacturas = estatus;
    var visibles = 0;
    filas.forEach(function(fila) {
        if (fila.getAttribute('data-estatus') === estatus) {
            fila.style.display = '';
            visibles++;
        } else {
            fila.style.display = 'none';
        }
    });

    titulo.textContent = labels[estatus] || 'Facturas';
    meta.textContent = visibles + ' factura' + (visibles !== 1 ? 's' : '');

    if (card) {
        card.style.boxShadow = '0 0 0 2px var(--purple)';
        card.style.border = '1.5px solid var(--purple)';
    }

    // Scroll a la tabla
    document.getElementById('seccionTodasFacturas').scrollIntoView({ behavior: 'smooth', block: 'start' });
}
</script>
@endpush
