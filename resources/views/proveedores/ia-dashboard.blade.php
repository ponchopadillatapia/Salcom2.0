@extends('layouts.proveedor')
@section('title', 'Dashboard IA')
@section('hero')
<div class="hero-band">
    <h1>Dashboard de Inteligencia Artificial</h1>
    <p>Análisis de tu operación — Powered by Claude</p>
</div>
@endsection
@push('styles')
<style>
    .ia-card{background:var(--white);border-radius:var(--radius-lg);overflow:hidden;margin-bottom:20px;box-shadow:var(--shadow-sm);transition:var(--transition)}
    .ia-card:hover{box-shadow:var(--shadow-md)}
    .ia-card-head{padding:18px 24px;border-bottom:1px solid var(--border-light);display:flex;align-items:center;gap:12px}
    .ia-card-head h3{font-size:16px;font-weight:700;color:var(--gray-text);letter-spacing:-0.3px}
    .ia-card-body{padding:24px}
    .ia-desc{font-size:14px;color:var(--gray-muted);margin-bottom:20px;line-height:1.6}
    .btn-ia{padding:12px 28px;background:var(--purple);color:#fff;border:none;border-radius:var(--radius-pill);font-size:14px;font-family:inherit;font-weight:600;cursor:pointer;transition:var(--transition);display:inline-flex;align-items:center;gap:8px}
    .btn-ia:hover{background:var(--purple-dark);transform:scale(1.03)}
    .btn-ia:active{transform:scale(0.97)}
    .btn-ia:disabled{opacity:.5;cursor:not-allowed;transform:none}
    .btn-ia svg{flex-shrink:0}

    /* Resultado formateado */
    .ia-result{margin-top:24px;padding-top:20px;border-top:1px solid var(--border-light)}
    .ia-result-header{display:flex;align-items:center;gap:10px;margin-bottom:16px}
    .ia-result-header h4{font-size:15px;font-weight:700;color:var(--gray-text)}
    .ia-result-time{font-size:11px;color:var(--gray-muted);margin-left:auto}

    .ia-response{background:var(--gray-soft);border-radius:var(--radius);padding:24px 28px;font-size:14px;line-height:1.8;color:var(--gray-text);max-height:600px;overflow-y:auto}
    .ia-response h1,.ia-response h2,.ia-response h3{color:var(--purple-dark);margin:20px 0 8px;font-weight:700}
    .ia-response h1{font-size:18px}.ia-response h2{font-size:16px}.ia-response h3{font-size:15px}
    .ia-response p{margin-bottom:12px}
    .ia-response strong{color:var(--gray-text);font-weight:700}
    .ia-response ul,.ia-response ol{padding-left:20px;margin-bottom:12px}
    .ia-response li{margin-bottom:6px}
    .ia-response table{width:100%;border-collapse:collapse;margin:16px 0;font-size:13px}
    .ia-response table th{background:var(--purple-light);color:var(--purple-dark);font-weight:700;padding:10px 14px;text-align:left;border:1px solid var(--border-light)}
    .ia-response table td{padding:10px 14px;border:1px solid var(--border-light)}
    .ia-response table tr:hover td{background:var(--white)}
    .ia-response code{background:var(--purple-light);padding:2px 6px;border-radius:4px;font-size:12px;color:var(--purple-dark)}
    .ia-response blockquote{border-left:3px solid var(--purple);padding-left:16px;margin:12px 0;color:var(--gray-muted);font-style:italic}

    .ia-error{background:var(--red-bg);border:1px solid rgba(255,59,48,0.2);border-radius:var(--radius);padding:16px 20px;font-size:14px;color:var(--red)}

    .data-preview{margin-top:16px}
    .data-preview summary{font-size:12px;font-weight:600;color:var(--gray-muted);cursor:pointer;padding:8px 0}
    .data-preview pre{background:var(--gray-soft);border:1px solid var(--border-light);border-radius:var(--radius);padding:12px 16px;font-size:11px;color:var(--gray-text);overflow-x:auto;max-height:250px;overflow-y:auto}

    .inv-table{width:100%;border-collapse:collapse;margin:12px 0}
    .inv-table th{font-size:11px;font-weight:700;color:var(--gray-muted);text-transform:uppercase;letter-spacing:.5px;padding:10px 16px;text-align:left;background:var(--gray-soft);border-bottom:1px solid var(--border-light)}
    .inv-table td{padding:10px 16px;font-size:13px;color:var(--gray-text);border-bottom:1px solid var(--border-light)}
    .inv-table tr:last-child td{border-bottom:none}
    .inv-table tr:hover td{background:var(--gray-soft)}

    .form-row{display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap}
    .form-group{display:flex;flex-direction:column;gap:6px;flex:1;min-width:200px}
    .form-group label{font-size:12px;font-weight:600;color:var(--gray-muted);letter-spacing:0.3px}
    .form-group select{border:1px solid var(--border-light);border-radius:var(--radius);padding:10px 14px;font-size:14px;font-family:inherit;color:var(--gray-text);outline:none;background:var(--gray-soft);transition:var(--transition)}
    .form-group select:focus{border-color:var(--purple);box-shadow:0 0 0 3px rgba(107,63,160,0.12)}

    @media(max-width:768px){.ia-card-body{padding:16px}.ia-response{padding:16px 20px}}
</style>
@endpush
@section('content')

{{-- PRONÓSTICO DE DEMANDA --}}
<div class="ia-card">
    <div class="ia-card-head">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--purple)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
        <h3>Pronóstico de demanda</h3>
    </div>
    <div class="ia-card-body">
        <p class="ia-desc">Analiza el historial de pedidos y predice la demanda para los próximos 3 meses.</p>
        <form method="POST" action="{{ route('proveedores.ia.pronostico') }}">
            @csrf
            <button type="submit" class="btn-ia">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                Generar pronóstico
            </button>
        </form>

        @if(isset($resultadoPronostico))
        <div class="ia-result">
            <div class="ia-result-header">
                <h4>Resultado del análisis</h4>
                <span class="ia-result-time">{{ $resultadoPronostico['generado'] }}</span>
            </div>
            @if($resultadoPronostico['analisis']['success'] ?? false)
                <div class="ia-response">{!! \Illuminate\Support\Str::markdown($resultadoPronostico['analisis']['content']) !!}</div>
            @else
                <div class="ia-error">{{ $resultadoPronostico['analisis']['error'] }}</div>
            @endif
            <details class="data-preview">
                <summary>Ver datos de entrada</summary>
                <pre>{{ json_encode($resultadoPronostico['historial'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
            </details>
        </div>
        @endif
    </div>
</div>

{{-- OPTIMIZACIÓN DE INVENTARIO --}}
<div class="ia-card">
    <div class="ia-card-head">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--purple)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
        <h3>Optimización de inventario</h3>
    </div>
    <div class="ia-card-body">
        <p class="ia-desc">Compara el stock actual contra la demanda proyectada y sugiere puntos de reorden.</p>
        <form method="POST" action="{{ route('proveedores.ia.inventario') }}">
            @csrf
            <button type="submit" class="btn-ia">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                Optimizar inventario
            </button>
        </form>

        @if(isset($resultadoInventario))
        <div class="ia-result">
            <div class="ia-result-header">
                <h4>Resultado del análisis</h4>
                <span class="ia-result-time">{{ $resultadoInventario['generado'] }}</span>
            </div>
            @if($resultadoInventario['analisis']['success'] ?? false)
                <div class="ia-response">{!! \Illuminate\Support\Str::markdown($resultadoInventario['analisis']['content']) !!}</div>
            @else
                <div class="ia-error">{{ $resultadoInventario['analisis']['error'] }}</div>
            @endif
        </div>
        @endif
    </div>
</div>

{{-- SELECCIÓN DE PROVEEDOR --}}
<div class="ia-card">
    <div class="ia-card-head">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--purple)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        <h3>Selección de proveedor</h3>
    </div>
    <div class="ia-card-body">
        <p class="ia-desc">Compara proveedores disponibles y recomienda el mejor por costo, tiempo y calidad.</p>
        <form method="POST" action="{{ route('proveedores.ia.proveedor') }}">
            @csrf
            <div class="form-row">
                <div class="form-group">
                    <label>Producto a analizar</label>
                    <select name="producto_id" required>
                        <option value="">Seleccionar producto...</option>
                        @foreach($productos ?? [] as $p)
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
        <div class="ia-result">
            <div class="ia-result-header">
                <h4>Análisis para {{ $resultadoProveedor['producto']['nombre'] ?? 'Producto' }}</h4>
                <span class="ia-result-time">{{ $resultadoProveedor['generado'] }}</span>
            </div>

            @if(isset($resultadoProveedor['proveedores']) && count($resultadoProveedor['proveedores']))
            <table class="inv-table" style="margin-bottom:20px">
                <thead><tr><th>Proveedor</th><th>Calificación</th><th>Puntualidad</th></tr></thead>
                <tbody>
                @foreach($resultadoProveedor['proveedores'] as $prov)
                    <tr>
                        <td><strong>{{ $prov['nombre'] }}</strong><br><span style="font-size:11px;color:var(--gray-muted)">{{ $prov['codigo'] }}</span></td>
                        <td>{{ $prov['calificacion'] }}/5</td>
                        <td>{{ $prov['entregas_a_tiempo'] }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            @endif

            @if($resultadoProveedor['analisis']['success'] ?? false)
                <div class="ia-response">{!! \Illuminate\Support\Str::markdown($resultadoProveedor['analisis']['content']) !!}</div>
            @else
                <div class="ia-error">{{ $resultadoProveedor['analisis']['error'] }}</div>
            @endif
        </div>
        @endif
    </div>
</div>

@endsection
