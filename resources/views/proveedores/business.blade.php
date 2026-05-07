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
        <a href="#totales" class="biz-link">Ver detalles →</a>
    </div>
</div>

{{-- FILA 2: Totales por artículo --}}
<div class="biz-grid" id="totales">
    <div class="biz-card biz-full">
        <h3>Totales — Listado de productos y resultado por artículo</h3>
        <div style="display:flex;gap:24px;margin-bottom:16px;flex-wrap:wrap">
            <div>
                <span style="font-size:12px;color:var(--gray-muted)">Ventas totales</span>
                <div style="font-size:20px;font-weight:700;color:var(--green)">$1,525,322.50</div>
            </div>
            <div>
                <span style="font-size:12px;color:var(--gray-muted)">Unidades totales</span>
                <div style="font-size:20px;font-weight:700">3,523,487 kg</div>
            </div>
            <div>
                <span style="font-size:12px;color:var(--gray-muted)">Variación</span>
                <div style="font-size:20px;font-weight:700;color:var(--green)">+5%</div>
            </div>
            <div>
                <span style="font-size:12px;color:var(--gray-muted)">Vs. meta</span>
                <div style="font-size:20px;font-weight:700;color:var(--green)">+17%</div>
            </div>
        </div>

        <table class="art-table">
            <thead>
                <tr>
                    <th>Art.</th>
                    <th>Producto</th>
                    <th>Cantidad</th>
                    <th>Variación</th>
                    <th>Total estándar</th>
                    <th>Resultado</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>1</strong></td>
                    <td>PDR (Polvo de resina)</td>
                    <td>1,000 kg</td>
                    <td><span class="biz-up">+5%</span></td>
                    <td>10,000 kg</td>
                    <td><span class="art-bar"><span class="art-bar-fill" style="width:85%;background:var(--green)"></span></span><span class="status-verde">85%</span></td>
                </tr>
                <tr>
                    <td><strong>2</strong></td>
                    <td>CLORO (Cloro industrial)</td>
                    <td>500 lt</td>
                    <td><span class="biz-down">-10%</span></td>
                    <td>5,000 lt</td>
                    <td><span class="art-bar"><span class="art-bar-fill" style="width:60%;background:var(--amber)"></span></span><span class="status-amarillo">60%</span></td>
                </tr>
                <tr>
                    <td><strong>3</strong></td>
                    <td>SAL (Sal industrial)</td>
                    <td>300 kg</td>
                    <td><span class="biz-up">+23%</span></td>
                    <td>2,000 kg</td>
                    <td><span class="art-bar"><span class="art-bar-fill" style="width:92%;background:var(--green)"></span></span><span class="status-verde">92%</span></td>
                </tr>
                <tr>
                    <td><strong>4</strong></td>
                    <td>SOLVENTE (Solvente técnico)</td>
                    <td>200 lt</td>
                    <td><span class="biz-down">-5%</span></td>
                    <td>3,000 lt</td>
                    <td><span class="art-bar"><span class="art-bar-fill" style="width:40%;background:var(--red)"></span></span><span class="status-rojo">40%</span></td>
                </tr>
                <tr>
                    <td><strong>5</strong></td>
                    <td>PIGMENTO (Pigmento base agua)</td>
                    <td>150 kg</td>
                    <td><span class="biz-flat">0%</span></td>
                    <td>1,500 kg</td>
                    <td><span class="art-bar"><span class="art-bar-fill" style="width:75%;background:var(--amber)"></span></span><span class="status-amarillo">75%</span></td>
                </tr>
            </tbody>
        </table>
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
