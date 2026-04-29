@extends('layouts.proveedor')
@section('title', 'Dashboard IA')
@section('hero')
<div class="hero-band">
    <h1>Dashboard de Inteligencia Artificial</h1>
    <p>Análisis automático de tu operación — Powered by Claude</p>
</div>
@endsection
@push('styles')
<style>
    .ia-card{background:var(--white);border:1px solid var(--border);border-radius:14px;overflow:hidden;margin-bottom:20px}
    .ia-card-head{padding:16px 24px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:12px}
    .ia-card-head h3{font-size:15px;font-weight:600;color:var(--purple-dark)}
    .ia-card-body{padding:24px}
    .ia-response{background:var(--purple-subtle);border:1px solid #e8ddf5;border-radius:10px;padding:20px 24px;font-size:13px;line-height:1.7;color:var(--gray-text);white-space:pre-wrap;word-wrap:break-word;max-height:500px;overflow-y:auto}
    .ia-response strong{color:var(--purple-dark)}
    .ia-error{background:var(--red-bg);border:1px solid #fca5a5;border-radius:10px;padding:16px 20px;font-size:13px;color:var(--red)}
    .ia-loading{text-align:center;padding:40px;color:var(--gray-muted);font-size:13px}
    .data-preview{margin-top:16px}
    .data-preview summary{font-size:12px;font-weight:600;color:var(--gray-muted);cursor:pointer;padding:8px 0}
    .data-preview pre{background:var(--gray-soft);border:1px solid var(--border);border-radius:8px;padding:12px 16px;font-size:11px;color:var(--gray-text);overflow-x:auto;max-height:250px;overflow-y:auto}
    .inv-table{width:100%;border-collapse:collapse;margin-top:12px}
    .inv-table th{font-size:11px;font-weight:700;color:#aaa;text-transform:uppercase;letter-spacing:.5px;padding:10px 16px;text-align:left;background:var(--gray-soft);border-bottom:1px solid var(--border)}
    .inv-table td{padding:10px 16px;font-size:13px;color:var(--gray-text);border-bottom:1px solid var(--border)}
    .inv-table tr:last-child td{border-bottom:none}
    .inv-table tr:hover td{background:var(--gray-soft)}
    @media(max-width:768px){.ia-card-body{padding:16px}}
</style>
@endpush
@section('content')

{{-- PRONÓSTICO DE DEMANDA --}}
<div class="ia-card">
    <div class="ia-card-head">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
        <h3>Pronóstico de demanda — {{ $resultadoPronostico['cliente'] ?? session('proveedor_codigo') }}</h3>
    </div>
    <div class="ia-card-body">
        @if($resultadoPronostico['analisis']['success'] ?? false)
            <div class="ia-response">{!! nl2br(e($resultadoPronostico['analisis']['content'])) !!}</div>
        @else
            <div class="ia-error">{{ $resultadoPronostico['analisis']['error'] ?? 'No se pudo generar el análisis' }}</div>
        @endif
        <details class="data-preview">
            <summary>Ver datos de entrada</summary>
            <pre>{{ json_encode($resultadoPronostico['historial'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
        </details>
    </div>
</div>

{{-- OPTIMIZACIÓN DE INVENTARIO --}}
<div class="ia-card">
    <div class="ia-card-head">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
        <h3>Optimización de inventario</h3>
    </div>
    <div class="ia-card-body">
        @if($resultadoInventario['analisis']['success'] ?? false)
            <div class="ia-response">{!! nl2br(e($resultadoInventario['analisis']['content'])) !!}</div>
        @else
            <div class="ia-error">{{ $resultadoInventario['analisis']['error'] ?? 'No se pudo generar el análisis' }}</div>
        @endif
    </div>
</div>

{{-- SELECCIÓN DE PROVEEDOR --}}
<div class="ia-card">
    <div class="ia-card-head">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        <h3>Selección de proveedor — {{ $resultadoProveedor['producto']['nombre'] ?? 'Producto' }}</h3>
    </div>
    <div class="ia-card-body">
        @if(isset($resultadoProveedor['proveedores']))
        <table class="inv-table" style="margin-bottom:16px">
            <thead><tr><th>Proveedor</th><th>Precio/u</th><th>Entrega</th><th>Calificación</th><th>Puntualidad</th></tr></thead>
            <tbody>
            @foreach($resultadoProveedor['proveedores'] as $prov)
                <tr>
                    <td><strong>{{ $prov['nombre'] }}</strong></td>
                    <td>${{ number_format($prov['precio_unitario'], 2) }}</td>
                    <td>{{ $prov['tiempo_entrega_dias'] }} días</td>
                    <td>⭐ {{ $prov['calificacion'] }}/5</td>
                    <td>{{ $prov['entregas_a_tiempo'] }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
        @endif

        @if($resultadoProveedor['analisis']['success'] ?? false)
            <div class="ia-response">{!! nl2br(e($resultadoProveedor['analisis']['content'])) !!}</div>
        @else
            <div class="ia-error">{{ $resultadoProveedor['analisis']['error'] ?? 'No se pudo generar el análisis' }}</div>
        @endif
    </div>
</div>

<p style="font-size:11px;color:var(--gray-muted);text-align:center;margin-top:8px">
    Análisis generado automáticamente por Claude (Anthropic) · {{ now()->format('d/m/Y H:i') }}
</p>

@endsection
