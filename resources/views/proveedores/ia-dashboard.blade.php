@extends('layouts.proveedor')
@section('title', 'Módulo de IA')
@section('hero')
<div class="hero-band">
    <h1>🤖 Módulo de Inteligencia Artificial</h1>
    <p>Pronóstico de demanda · Optimización de inventario · Selección de proveedor</p>
</div>
@endsection
@push('styles')
<style>
    .badge-mock{font-size:11px;color:#d97706;font-weight:600;background:#fffbeb;padding:3px 10px;border-radius:999px;display:inline-block;margin-bottom:20px}
    .tabs{display:flex;gap:0;border-bottom:2px solid var(--border);margin-bottom:24px}
    .tab{padding:12px 24px;font-size:14px;font-weight:600;color:var(--gray-muted);cursor:pointer;border-bottom:2px solid transparent;margin-bottom:-2px;transition:all .15s;background:none;border-top:none;border-left:none;border-right:none;font-family:inherit}
    .tab:hover{color:var(--purple);background:var(--purple-subtle)}
    .tab.active{color:var(--purple);border-bottom-color:var(--purple)}
    .tab-icon{margin-right:6px}
    .tab-panel{display:none}.tab-panel.active{display:block}

    .ia-card{background:var(--white);border:1px solid var(--border);border-radius:14px;overflow:hidden;margin-bottom:20px}
    .ia-card-head{padding:16px 24px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:12px}
    .ia-card-head h3{font-size:15px;font-weight:600;color:var(--purple-dark)}
    .ia-card-body{padding:24px}

    .form-row{display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;margin-bottom:0}
    .form-group{display:flex;flex-direction:column;gap:4px;flex:1;min-width:200px}
    .form-group label{font-size:12px;font-weight:600;color:var(--gray-muted);text-transform:uppercase;letter-spacing:0.5px}
    .form-group select,.form-group input{border:1.5px solid var(--border);border-radius:8px;padding:10px 14px;font-size:13px;font-family:inherit;color:var(--gray-text);outline:none;background:var(--white)}
    .form-group select:focus,.form-group input:focus{border-color:var(--purple);box-shadow:0 0 0 3px rgba(107,63,160,.1)}

    .btn-ia{padding:10px 24px;background:var(--purple);color:#fff;border:none;border-radius:10px;font-size:13px;font-family:inherit;font-weight:600;cursor:pointer;transition:all .15s;display:inline-flex;align-items:center;gap:8px;white-space:nowrap}
    .btn-ia:hover{background:var(--purple-dark);box-shadow:0 4px 12px rgba(107,63,160,.3)}
    .btn-ia:disabled{opacity:.6;cursor:not-allowed}
    .btn-ia svg{flex-shrink:0}

    .resultado{margin-top:20px}
    .resultado-header{display:flex;align-items:center;gap:10px;margin-bottom:12px}
    .resultado-header h4{font-size:14px;font-weight:600;color:var(--purple-dark)}
    .resultado-time{font-size:11px;color:var(--gray-muted)}

    .ia-response{background:var(--purple-subtle);border:1px solid #e8ddf5;border-radius:10px;padding:20px 24px;font-size:13px;line-height:1.7;color:var(--gray-text);white-space:pre-wrap;word-wrap:break-word;max-height:600px;overflow-y:auto}
    .ia-response strong{color:var(--purple-dark)}

    .ia-error{background:var(--red-bg);border:1px solid #fca5a5;border-radius:10px;padding:16px 20px;font-size:13px;color:var(--red)}

    .data-preview{margin-top:16px}
    .data-preview summary{font-size:12px;font-weight:600;color:var(--gray-muted);cursor:pointer;padding:8px 0}
    .data-preview pre{background:var(--gray-soft);border:1px solid var(--border);border-radius:8px;padding:12px 16px;font-size:11px;color:var(--gray-text);overflow-x:auto;max-height:300px;overflow-y:auto}

    .metrics-row{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:20px}
    .metric-card{background:var(--white);border-radius:12px;padding:18px 20px;border:1px solid var(--border);position:relative;overflow:hidden}
    .metric-card .accent{position:absolute;top:0;left:0;width:4px;height:100%;border-radius:12px 0 0 12px}
    .metric-label{font-size:12px;color:var(--gray-muted);font-weight:500;margin-bottom:6px;padding-left:8px}
    .metric-value{font-size:24px;font-weight:600;color:var(--purple-dark);padding-left:8px;line-height:1}
    .metric-sub{font-size:11px;color:#aaa;padding-left:8px;margin-top:4px}

    .inv-table{width:100%;border-collapse:collapse;margin-top:12px}
    .inv-table th{font-size:11px;font-weight:700;color:#aaa;text-transform:uppercase;letter-spacing:.5px;padding:10px 16px;text-align:left;background:var(--gray-soft);border-bottom:1px solid var(--border)}
    .inv-table td{padding:10px 16px;font-size:13px;color:var(--gray-text);border-bottom:1px solid var(--border)}
    .inv-table tr:last-child td{border-bottom:none}
    .inv-table tr:hover td{background:var(--gray-soft)}
    .stock-low{color:var(--red);font-weight:600}
    .stock-ok{color:var(--green);font-weight:600}
    .stock-high{color:var(--amber);font-weight:600}
    .trend-up::before{content:'↑ ';color:var(--green)}
    .trend-down::before{content:'↓ ';color:var(--red)}
    .trend-stable::before{content:'→ ';color:var(--gray-muted)}

    @media(max-width:768px){.metrics-row{grid-template-columns:1fr}.tabs{overflow-x:auto}.tab{white-space:nowrap;padding:10px 16px;font-size:13px}}
</style>
@endpush
@section('content')

<span class="badge-mock">🤖 Powered by Claude (Anthropic) — Datos de prueba</span>

{{-- TABS --}}
<div class="tabs">
    <button class="tab {{ ($tabActiva ?? 'pronostico') === 'pronostico' ? 'active' : '' }}" onclick="switchTab('pronostico')">
        <span class="tab-icon">📊</span> Pronóstico de demanda
    </button>
    <button class="tab {{ ($tabActiva ?? '') === 'inventario' ? 'active' : '' }}" onclick="switchTab('inventario')">
        <span class="tab-icon">📦</span> Optimización de inventario
    </button>
    <button class="tab {{ ($tabActiva ?? '') === 'proveedor' ? 'active' : '' }}" onclick="switchTab('proveedor')">
        <span class="tab-icon">🏭</span> Selección de proveedor
    </button>
</div>

{{-- ═══ TAB 1: PRONÓSTICO DE DEMANDA ═══ --}}
<div class="tab-panel {{ ($tabActiva ?? 'pronostico') === 'pronostico' ? 'active' : '' }}" id="panel-pronostico">
    <div class="ia-card">
        <div class="ia-card-head">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
            <h3>Pronóstico de demanda por cliente</h3>
        </div>
        <div class="ia-card-body">
            <p style="font-size:13px;color:var(--gray-muted);margin-bottom:16px">
                Selecciona un cliente para analizar su historial de pedidos y predecir la demanda futura usando IA.
            </p>
            <form method="POST" action="{{ route('proveedores.ia.pronostico') }}">
                @csrf
                <div class="form-row">
                    <div class="form-group">
                        <label for="codigo_cliente">Cliente</label>
                        <select name="codigo_cliente" id="codigo_cliente" required>
                            <option value="">Seleccionar cliente...</option>
                            @foreach($clientes as $c)
                                <option value="{{ $c['codigo'] }}" {{ (old('codigo_cliente', $resultadoPronostico['cliente'] ?? '') == $c['codigo']) ? 'selected' : '' }}>
                                    {{ $c['codigo'] }} — {{ $c['nombre'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn-ia">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                        Analizar con IA
                    </button>
                </div>
            </form>

            @if(isset($resultadoPronostico))
                <div class="resultado">
                    <div class="resultado-header">
                        <h4>📊 Análisis para {{ $resultadoPronostico['cliente'] }}</h4>
                        <span class="resultado-time">Generado: {{ $resultadoPronostico['generado'] }}</span>
                    </div>

                    @if($resultadoPronostico['analisis']['success'])
                        <div class="ia-response">{!! nl2br(e($resultadoPronostico['analisis']['content'])) !!}</div>
                    @else
                        <div class="ia-error">{{ $resultadoPronostico['analisis']['error'] }}</div>
                    @endif

                    <details class="data-preview">
                        <summary>Ver datos de entrada (historial de pedidos)</summary>
                        <pre>{{ json_encode($resultadoPronostico['historial'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                    </details>
                </div>
            @endif
        </div>
    </div>
</div>

{{-- ═══ TAB 2: OPTIMIZACIÓN DE INVENTARIO ═══ --}}
<div class="tab-panel {{ ($tabActiva ?? '') === 'inventario' ? 'active' : '' }}" id="panel-inventario">
    <div class="metrics-row">
        @php
            $inv = app(\App\Services\IaService::class)->obtenerInventarioActual();
            $dem = app(\App\Services\IaService::class)->obtenerDemandaProyectada();
            $alertas = 0;
            foreach($inv as $i => $item) {
                $demMensual = collect($dem)->firstWhere('sku', $item['sku']);
                if ($demMensual && $item['stock_actual'] < ($demMensual['demanda_mensual'] * 0.5)) $alertas++;
            }
            $valorTotal = collect($inv)->sum(fn($i) => $i['stock_actual'] * $i['costo_unitario']);
        @endphp
        <div class="metric-card">
            <div class="accent" style="background:var(--purple)"></div>
            <div class="metric-label">Productos en inventario</div>
            <div class="metric-value">{{ count($inv) }}</div>
            <div class="metric-sub">SKUs activos</div>
        </div>
        <div class="metric-card">
            <div class="accent" style="background:{{ $alertas > 0 ? 'var(--red)' : 'var(--green)' }}"></div>
            <div class="metric-label">Alertas de desabasto</div>
            <div class="metric-value">{{ $alertas }}</div>
            <div class="metric-sub">Stock < 2 semanas</div>
        </div>
        <div class="metric-card">
            <div class="accent" style="background:var(--blue)"></div>
            <div class="metric-label">Valor del inventario</div>
            <div class="metric-value">${{ number_format($valorTotal, 0) }}</div>
            <div class="metric-sub">Costo total</div>
        </div>
    </div>

    <div class="ia-card">
        <div class="ia-card-head">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
            <h3>Inventario actual vs. demanda proyectada</h3>
        </div>
        <div class="ia-card-body">
            <table class="inv-table">
                <thead>
                    <tr><th>SKU</th><th>Producto</th><th>Stock</th><th>Demanda/mes</th><th>Cobertura</th><th>Tendencia</th><th>Estado</th></tr>
                </thead>
                <tbody>
                @foreach($inv as $item)
                    @php
                        $demItem = collect($dem)->firstWhere('sku', $item['sku']);
                        $demMes = $demItem['demanda_mensual'] ?? 0;
                        $cobertura = $demMes > 0 ? round($item['stock_actual'] / $demMes, 1) : 999;
                        $tendencia = $demItem['tendencia'] ?? 'estable';
                        $estado = $cobertura < 0.5 ? 'low' : ($cobertura > 3 ? 'high' : 'ok');
                    @endphp
                    <tr>
                        <td><strong>{{ $item['sku'] }}</strong></td>
                        <td>{{ $item['nombre'] }}</td>
                        <td>{{ number_format($item['stock_actual']) }} {{ $item['unidad'] }}</td>
                        <td>{{ number_format($demMes) }} {{ $item['unidad'] }}</td>
                        <td>{{ $cobertura }} meses</td>
                        <td><span class="trend-{{ $tendencia }}">{{ ucfirst($tendencia) }}</span></td>
                        <td><span class="stock-{{ $estado }}">{{ $estado === 'low' ? '⚠ Crítico' : ($estado === 'high' ? '📦 Exceso' : '✓ OK') }}</span></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="ia-card">
        <div class="ia-card-head">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
            <h3>Análisis de IA — Optimización de inventario</h3>
        </div>
        <div class="ia-card-body">
            <p style="font-size:13px;color:var(--gray-muted);margin-bottom:16px">
                La IA analizará el inventario actual contra la demanda proyectada para sugerir puntos de reorden y cantidades óptimas.
            </p>
            <form method="POST" action="{{ route('proveedores.ia.inventario') }}">
                @csrf
                <button type="submit" class="btn-ia">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                    Optimizar con IA
                </button>
            </form>

            @if(isset($resultadoInventario))
                <div class="resultado">
                    <div class="resultado-header">
                        <h4>📦 Recomendaciones de inventario</h4>
                        <span class="resultado-time">Generado: {{ $resultadoInventario['generado'] }}</span>
                    </div>

                    @if($resultadoInventario['analisis']['success'])
                        <div class="ia-response">{!! nl2br(e($resultadoInventario['analisis']['content'])) !!}</div>
                    @else
                        <div class="ia-error">{{ $resultadoInventario['analisis']['error'] }}</div>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>

{{-- ═══ TAB 3: SELECCIÓN DE PROVEEDOR ═══ --}}
<div class="tab-panel {{ ($tabActiva ?? '') === 'proveedor' ? 'active' : '' }}" id="panel-proveedor">
    <div class="ia-card">
        <div class="ia-card-head">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            <h3>Selección inteligente de proveedor</h3>
        </div>
        <div class="ia-card-body">
            <p style="font-size:13px;color:var(--gray-muted);margin-bottom:16px">
                Selecciona un producto para que la IA compare proveedores y recomiende el mejor por costo, tiempo de entrega y calidad.
                Este módulo corresponde al nodo "IA: Selección proveedor / Mejor costo-tiempo" del diagrama de flujo.
            </p>
            <form method="POST" action="{{ route('proveedores.ia.proveedor') }}">
                @csrf
                <div class="form-row">
                    <div class="form-group">
                        <label for="producto_id">Producto</label>
                        <select name="producto_id" id="producto_id" required>
                            <option value="">Seleccionar producto...</option>
                            @foreach($productos as $p)
                                <option value="{{ $p['sku'] }}" {{ (old('producto_id', $resultadoProveedor['producto']['sku'] ?? '') == $p['sku']) ? 'selected' : '' }}>
                                    {{ $p['sku'] }} — {{ $p['nombre'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn-ia">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                        Recomendar proveedor
                    </button>
                </div>
            </form>

            @if(isset($resultadoProveedor))
                <div class="resultado">
                    <div class="resultado-header">
                        <h4>🏭 Análisis para {{ $resultadoProveedor['producto']['nombre'] }}</h4>
                        <span class="resultado-time">Generado: {{ $resultadoProveedor['generado'] }}</span>
                    </div>

                    {{-- Tabla comparativa de proveedores --}}
                    <table class="inv-table" style="margin-bottom:16px">
                        <thead>
                            <tr><th>Proveedor</th><th>Precio/u</th><th>Entrega</th><th>MOQ</th><th>Calificación</th><th>Puntualidad</th><th>Ubicación</th></tr>
                        </thead>
                        <tbody>
                        @foreach($resultadoProveedor['proveedores'] as $prov)
                            <tr>
                                <td><strong>{{ $prov['nombre'] }}</strong><br><span style="font-size:11px;color:var(--gray-muted)">{{ $prov['codigo'] }}</span></td>
                                <td>${{ number_format($prov['precio_unitario'], 2) }}</td>
                                <td>{{ $prov['tiempo_entrega_dias'] }} días</td>
                                <td>{{ number_format($prov['moq']) }}</td>
                                <td>⭐ {{ $prov['calificacion'] }}/5</td>
                                <td>{{ $prov['entregas_a_tiempo'] }}</td>
                                <td>{{ $prov['ubicacion'] }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>

                    @if($resultadoProveedor['analisis']['success'])
                        <div class="ia-response">{!! nl2br(e($resultadoProveedor['analisis']['content'])) !!}</div>
                    @else
                        <div class="ia-error">{{ $resultadoProveedor['analisis']['error'] }}</div>
                    @endif

                    <details class="data-preview">
                        <summary>Ver datos de entrada (producto y proveedores)</summary>
                        <pre>{{ json_encode(['producto' => $resultadoProveedor['producto'], 'proveedores' => $resultadoProveedor['proveedores']], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                    </details>
                </div>
            @endif
        </div>
    </div>
</div>

@endsection
@push('scripts')
<script>
function switchTab(tab) {
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    document.getElementById('panel-' + tab).classList.add('active');
    event.currentTarget.classList.add('active');
}
</script>
@endpush
