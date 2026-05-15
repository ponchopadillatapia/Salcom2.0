﻿@extends('layouts.proveedor')

@section('title', 'Business')

@section('hero')
<div class="hero-band">
    <h1>Business</h1>
    <p>Resumen de tu operación como proveedor — {{ now()->format('d/m/Y') }}</p>
</div>
@endsection

@push('styles')
<style>
    .biz-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px}
    .biz-card{background:var(--white);border-radius:var(--radius-lg);padding:24px;box-shadow:var(--shadow-sm);transition:var(--transition)}
    .biz-card:hover{box-shadow:var(--shadow-md)}
    .biz-card h3{font-size:16px;font-weight:700;color:var(--gray-text);margin-bottom:16px;letter-spacing:-0.3px}
    .biz-full{grid-column:1/-1}

    .biz-metric{display:flex;align-items:baseline;gap:8px;margin-bottom:8px}
    .biz-metric-label{font-size:13px;color:var(--gray-muted);font-weight:500}
    .biz-metric-value{font-size:28px;font-weight:700;letter-spacing:-0.5px}
    .biz-metric-unit{font-size:13px;color:var(--gray-muted)}
    .biz-metric-change{font-size:13px;font-weight:700;margin-left:8px}
    .biz-up{color:var(--green)}.biz-down{color:var(--red)}.biz-flat{color:var(--gray-muted)}
    .biz-link{font-size:12px;color:var(--blue);text-decoration:none;font-weight:600;margin-top:8px;display:inline-block}
    .biz-link:hover{opacity:0.7}

    /* OTIF donuts */
    .otif-wrap{display:flex;gap:32px;align-items:center;justify-content:center;flex-wrap:wrap}
    .otif-item{display:flex;flex-direction:column;align-items:center;gap:8px}
    .otif-donut{position:relative;width:120px;height:120px}
    .otif-donut canvas{position:absolute;top:0;left:0}
    .otif-center{position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);text-align:center}
    .otif-num{font-size:24px;font-weight:700;color:var(--gray-text)}
    .otif-label{font-size:11px;color:var(--gray-muted);font-weight:600;text-transform:uppercase;letter-spacing:0.5px}

    /* Tabla de artículos */
    .art-table{width:100%;border-collapse:collapse;margin-top:12px}
    .art-table th{font-size:11px;font-weight:700;color:var(--gray-muted);text-transform:uppercase;letter-spacing:.5px;padding:10px 14px;text-align:left;background:var(--gray-soft);border-bottom:1px solid var(--border-light)}
    .art-table td{padding:12px 14px;font-size:13px;color:var(--gray-text);border-bottom:1px solid var(--border-light)}
    .art-table tr:last-child td{border-bottom:none}
    .art-table tr:hover td{background:var(--gray-soft)}
    .art-bar{width:80px;height:8px;background:var(--border-light);border-radius:4px;overflow:hidden;display:inline-block;vertical-align:middle;margin-right:8px}
    .art-bar-fill{height:100%;border-radius:4px}
    .status-verde{color:var(--green);font-weight:700}
    .status-amarillo{color:var(--amber);font-weight:700}
    .status-rojo{color:var(--red);font-weight:700}

    /* Productos no entregados */
    .noentrega-item{display:flex;align-items:center;gap:12px;padding:12px 0;border-bottom:1px solid var(--border-light);font-size:13px}
    .noentrega-item:last-child{border-bottom:none}
    .noentrega-dot{width:10px;height:10px;border-radius:50%;background:var(--red);flex-shrink:0}
    .noentrega-name{flex:1;font-weight:600;color:var(--gray-text)}
    .noentrega-reason{font-size:12px;color:var(--gray-muted)}

    .biz-note{font-size:11px;color:var(--gray-muted);text-align:center;margin-top:16px}

    @media(max-width:768px){.biz-grid{grid-template-columns:1fr}.otif-wrap{flex-direction:column}}
</style>
@endpush

@section('content')

{{-- FILA 1: Negocio --}}
<div class="biz-grid" style="grid-template-columns:1fr;">
    {{-- NEGOCIO --}}
    <div class="biz-card">
        <h3>Negocio</h3>
        <div class="biz-metric">
            <span class="biz-metric-label">Ventas</span>
            <span class="biz-metric-value" style="color:var(--green)">$1,525,322.50</span>
            <span class="biz-metric-unit">MXN</span>
            <span class="biz-metric-change biz-up">+17%</span>
        </div>
        <div class="biz-metric">
            <span class="biz-metric-label">Unidades</span>
            <span class="biz-metric-value">3,523,487</span>
            <span class="biz-metric-unit">kg</span>
            <span class="biz-metric-change biz-up">+5%</span>
        </div>
    </div>
</div>

{{-- FILA 2: Totales por artículo (formato comparativo anual Salcom Industries) --}}
<div class="biz-grid" id="totales">
    <div class="biz-card biz-full">
        <h3>Totales — Reporte de ventas comparativo anual</h3>
        <div style="font-size:11px;color:var(--gray-muted);margin-bottom:12px;">SALCOM INDUSTRIES — Agrupado por producto/familia</div>
        <div style="display:flex;gap:24px;margin-bottom:16px;flex-wrap:wrap">
            <div>
                <span style="font-size:12px;color:var(--gray-muted)">Ventas totales 2026</span>
                <div style="font-size:20px;font-weight:700;color:var(--green)">$11,011,640.07</div>
            </div>
            <div>
                <span style="font-size:12px;color:var(--gray-muted)">Unidades totales 2026</span>
                <div style="font-size:20px;font-weight:700">1,152,664</div>
            </div>
            <div>
                <span style="font-size:12px;color:var(--gray-muted)">Variación unidades</span>
                <div style="font-size:20px;font-weight:700;color:var(--green)">+16%</div>
            </div>
            <div>
                <span style="font-size:12px;color:var(--gray-muted)">Variación ventas</span>
                <div style="font-size:20px;font-weight:700;color:var(--green)">+20%</div>
            </div>
        </div>

        <div style="overflow-x:auto;">
        <table class="art-table" style="min-width:750px;">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Producto / Cliente</th>
                    <th style="text-align:right;">Uds 2025</th>
                    <th style="text-align:right;">Uds 2026</th>
                    <th style="text-align:right;">Uds %</th>
                    <th style="text-align:right;">Ventas 2025</th>
                    <th style="text-align:right;">Ventas 2026</th>
                    <th style="text-align:right;">Ventas %</th>
                    <th>Resultado</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="font-size:12px;color:var(--gray-muted);">101000138</td>
                    <td style="font-weight:600;">Walmart Inc</td>
                    <td style="text-align:right;">678,424</td>
                    <td style="text-align:right;">848,749</td>
                    <td style="text-align:right;color:var(--green);font-weight:700;">+25%</td>
                    <td style="text-align:right;">$7,033,870</td>
                    <td style="text-align:right;font-weight:600;">$8,812,560</td>
                    <td style="text-align:right;color:var(--green);font-weight:700;">+25%</td>
                    <td><span class="art-bar"><span class="art-bar-fill" style="width:95%;background:var(--green)"></span></span><span class="status-verde">95%</span></td>
                </tr>
                <tr>
                    <td style="font-size:12px;color:var(--gray-muted);">101000119</td>
                    <td style="font-weight:600;">Dollar Tree Stores Inc.</td>
                    <td style="text-align:right;">155,792</td>
                    <td style="text-align:right;">136,965</td>
                    <td style="text-align:right;color:var(--red);font-weight:700;">-12%</td>
                    <td style="text-align:right;">$1,061,796</td>
                    <td style="text-align:right;font-weight:600;">$949,583</td>
                    <td style="text-align:right;color:var(--red);font-weight:700;">-11%</td>
                    <td><span class="art-bar"><span class="art-bar-fill" style="width:60%;background:var(--amber)"></span></span><span class="status-amarillo">60%</span></td>
                </tr>
                <tr>
                    <td style="font-size:12px;color:var(--gray-muted);">101000124</td>
                    <td style="font-weight:600;">Dollar General Corp.</td>
                    <td style="text-align:right;">102,900</td>
                    <td style="text-align:right;">134,400</td>
                    <td style="text-align:right;color:var(--green);font-weight:700;">+31%</td>
                    <td style="text-align:right;">$719,092</td>
                    <td style="text-align:right;font-weight:600;">$1,038,643</td>
                    <td style="text-align:right;color:var(--green);font-weight:700;">+44%</td>
                    <td><span class="art-bar"><span class="art-bar-fill" style="width:88%;background:var(--green)"></span></span><span class="status-verde">88%</span></td>
                </tr>
                <tr>
                    <td style="font-size:12px;color:var(--gray-muted);">101000120</td>
                    <td style="font-weight:600;">Family Dollar Stores</td>
                    <td style="text-align:right;">59,552</td>
                    <td style="text-align:right;">32,550</td>
                    <td style="text-align:right;color:var(--red);font-weight:700;">-45%</td>
                    <td style="text-align:right;">$386,392</td>
                    <td style="text-align:right;font-weight:600;">$210,852</td>
                    <td style="text-align:right;color:var(--red);font-weight:700;">-45%</td>
                    <td><span class="art-bar"><span class="art-bar-fill" style="width:35%;background:var(--red)"></span></span><span class="status-rojo">35%</span></td>
                </tr>
                <tr style="background:var(--green-bg);font-weight:700;">
                    <td></td>
                    <td>GRAN TOTAL</td>
                    <td style="text-align:right;">996,668</td>
                    <td style="text-align:right;">1,152,664</td>
                    <td style="text-align:right;color:var(--green);">+16%</td>
                    <td style="text-align:right;">$9,200,151</td>
                    <td style="text-align:right;">$11,011,640</td>
                    <td style="text-align:right;color:var(--green);">+20%</td>
                    <td></td>
                </tr>
            </tbody>
        </table>
        </div>
        <div style="margin-top:12px;font-size:11px;color:var(--gray-muted)">
            <span style="display:inline-block;width:10px;height:10px;border-radius:2px;background:var(--green);margin-right:4px"></span> Verde: &gt;80%
            <span style="display:inline-block;width:10px;height:10px;border-radius:2px;background:var(--amber);margin-left:16px;margin-right:4px"></span> Amarillo: 50-80%
            <span style="display:inline-block;width:10px;height:10px;border-radius:2px;background:var(--red);margin-left:16px;margin-right:4px"></span> Rojo: &lt;50%
        </div>
    </div>
</div>

{{-- FILA 3: Productos que no se entregan a tiempo --}}
<div class="biz-grid" id="noentrega">
    <div class="biz-card biz-full">
        <h3>Productos que no se entregan a tiempo y completos</h3>
        <div class="noentrega-item">
            <div class="noentrega-dot"></div>
            <div class="noentrega-name">SOLVENTE (Solvente técnico)</div>
            <div class="noentrega-reason">Retraso 3 días — Stock insuficiente</div>
            <span class="status-rojo">40%</span>
        </div>
        <div class="noentrega-item">
            <div class="noentrega-dot" style="background:var(--amber)"></div>
            <div class="noentrega-name">CLORO (Cloro industrial)</div>
            <div class="noentrega-reason">Entrega parcial — 60% del pedido</div>
            <span class="status-amarillo">60%</span>
        </div>
        <div class="noentrega-item">
            <div class="noentrega-dot" style="background:var(--amber)"></div>
            <div class="noentrega-name">PIGMENTO (Pigmento base agua)</div>
            <div class="noentrega-reason">Retraso 1 día — Problema logístico</div>
            <span class="status-amarillo">75%</span>
        </div>
    </div>
</div>

<div class="biz-note">Datos de prueba — se reemplazarán con datos reales de la API</div>

@endsection

@push('scripts')
<script>
// Business page — no donuts needed (OTIF moved to its own module)
</script>
@endpush
